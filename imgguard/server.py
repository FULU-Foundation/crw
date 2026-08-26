"""
Local HTTP service wrapping the image safety classifier.

  POST /classify   multipart form field "file", or a raw image body
  GET  /health     liveness, loaded model, active threshold, converter backends

Optional request headers on /classify:

  X-Auth-Token     shared secret, if config.AUTH_TOKEN is set
  X-Filename       original filename. A *hint* only: used to disambiguate ZIP
                   containers (.docx vs .xlsx) and nothing else. Format
                   detection is always content-based, because a caller-supplied
                   extension is not evidence of anything.

Response:
  {
    "accepted": false,
    "verdict": "reject",
    "reason": "P(SFW)=0.0031 below threshold 0.50",
    "scores": {"NSFL": 0.0012, "NSFW": 0.9957, "SFW": 0.0031},
    "media_type": "pdf",
    "frames_checked": 4,
    "worst_source": "page 2",
    "elapsed_ms": 84.1,
    "model": "image-safety-classifier-l",
    "sfw_min": 0.5
  }

Deliberately does no logging of decisions - that's the caller's job. On the
wiki that means MediaWiki writes the log entry, so rejections are reviewable at
Special:Log/imgguard by someone with the right, and nothing sensitive lands in
a second place with a different retention policy.
"""

from __future__ import annotations

import time
from contextlib import asynccontextmanager

from fastapi import FastAPI, File, Header, HTTPException, Request, UploadFile
from fastapi.responses import JSONResponse

import config
import converters
from classifier import get_classifier

MAX_BYTES = config.MAX_UPLOAD_MB * 1024 * 1024


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Load the model and run one inference so the first real request is fast."""
    import io
    from PIL import Image

    clf = get_classifier(verbose=True)
    buf = io.BytesIO()
    Image.new("RGB", (64, 64), (128, 128, 128)).save(buf, format="PNG")
    clf.classify_bytes(buf.getvalue())

    print(f"imgguard ready  model={clf.model_path.name}  SFW_MIN={config.SFW_MIN}")
    for kind, state in _backend_status().items():
        print(f"  {kind:<8} {state}")
    yield


app = FastAPI(title="imgguard", version="1.1", docs_url="/docs", lifespan=lifespan)


def _backend_status() -> dict[str, str]:
    """
    Human-readable backend state for /health and the startup banner.

    Distinguishes "we chose not to" from "we can't", which the raw report
    cannot: both look like an empty list. Video is off by default, so without
    this it reports UNAVAILABLE and reads as a broken install rather than a
    deliberate setting.
    """
    out = {}
    for kind, backends in converters.backend_report().items():
        if kind == "video" and not config.SCAN_VIDEO:
            out[kind] = "disabled by config (SCAN_VIDEO=false)"
        elif backends:
            out[kind] = ", ".join(backends)
        else:
            out[kind] = "UNAVAILABLE"
    return out


def _check_auth(token: str | None) -> None:
    if config.AUTH_TOKEN and token != config.AUTH_TOKEN:
        raise HTTPException(status_code=401, detail="bad or missing X-Auth-Token")


def _rejection(reason: str, verdict: str = "reject") -> JSONResponse:
    """A refusal that never reached the model still has to look like a verdict,
    so the caller has one response shape to handle."""
    return JSONResponse({
        "accepted": False,
        "verdict": verdict,
        "reason": reason,
        "scores": {},
        "media_type": "unknown",
        "frames_checked": 0,
        "worst_source": None,
        "elapsed_ms": 0.0,
        "model": config.MODEL_REPO,
        "sfw_min": config.SFW_MIN,
    })


@app.get("/health")
def health():
    clf = get_classifier()
    return {
        "status": "ok",
        "model_repo": config.MODEL_REPO,
        "model_file": clf.model_path.name,
        "classes": config.CLASS_NAMES,
        "sfw_min": config.SFW_MIN,
        "max_upload_mb": config.MAX_UPLOAD_MB,
        "backends": _backend_status(),
        "supported_kinds": sorted(converters.supported_kinds()),
        "scan_video": config.SCAN_VIDEO,
    }


@app.post("/classify")
async def classify(
    request: Request,
    file: UploadFile | None = File(default=None),
    x_auth_token: str | None = Header(default=None),
    x_filename: str | None = Header(default=None),
):
    _check_auth(x_auth_token)

    # Check the declared length before reading the body, so an oversize upload
    # is refused rather than buffered.
    declared = request.headers.get("content-length")
    if declared is not None and declared.isdigit() and int(declared) > MAX_BYTES:
        return _rejection(
            f"payload {declared} bytes exceeds MAX_UPLOAD_MB={config.MAX_UPLOAD_MB}",
            verdict="oversize",
        )

    if file is not None:
        data = await file.read()
        filename = x_filename or file.filename
    else:
        data = await request.body()
        filename = x_filename

    if not data:
        raise HTTPException(
            status_code=400,
            detail="empty body; send multipart 'file' or a raw image",
        )
    if len(data) > MAX_BYTES:
        return _rejection(
            f"payload {len(data)} bytes exceeds MAX_UPLOAD_MB={config.MAX_UPLOAD_MB}",
            verdict="oversize",
        )

    t0 = time.perf_counter()
    verdict = get_classifier().classify_bytes(data, filename=filename)
    payload = verdict.to_dict()
    payload["elapsed_ms"] = round((time.perf_counter() - t0) * 1000, 2)
    payload["model"] = config.MODEL_REPO
    payload["sfw_min"] = config.SFW_MIN
    return JSONResponse(payload)


if __name__ == "__main__":
    import uvicorn

    uvicorn.run(app, host=config.HOST, port=config.PORT, log_level="info")
