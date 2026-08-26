"""
Image safety classifier wrapper.

Loads the ONNX model once, exposes classify_bytes() / classify_path().
Normalisation and softmax are baked into the ONNX graph, so preprocessing is
just: decode -> RGB -> resize to INPUT_SIZE -> NCHW float32 in 0..255.

Anything that isn't already a raster image (SVG, PDF, Office documents, video)
is turned into frames by converters.py first. Whatever the format, the file is
judged on its worst frame.
"""

from __future__ import annotations

import os
from dataclasses import dataclass, field, asdict
from pathlib import Path

import numpy as np
import onnxruntime as ort
from PIL import Image

import config
import converters

REPO_ROOT = Path(__file__).resolve().parent


# ---------------------------------------------------------------------------
# Result type
# ---------------------------------------------------------------------------

@dataclass
class Verdict:
    accepted: bool
    reason: str
    scores: dict = field(default_factory=dict)   # worst-frame class probabilities
    frames_checked: int = 0
    error: str | None = None
    # verdict is the machine-readable form of the outcome. MediaWiki keys its
    # AbuseFilter variable off this, so the strings are load-bearing:
    #   accept | reject | unsupported | undecodable
    verdict: str = "accept"
    media_type: str = "unknown"
    worst_source: str | None = None

    def to_dict(self) -> dict:
        return asdict(self)


# ---------------------------------------------------------------------------
# Model resolution / loading
# ---------------------------------------------------------------------------

def model_dir() -> Path:
    d = Path(config.MODEL_DIR)
    return d if d.is_absolute() else REPO_ROOT / d


def resolve_local_model() -> Path | None:
    """Return the cached .onnx for MODEL_REPO, or None if not downloaded yet."""
    slug = config.MODEL_REPO.replace("/", "__")
    target = model_dir() / slug
    if not target.is_dir():
        return None
    onnx_files = sorted(target.rglob("*.onnx"))
    if not onnx_files:
        return None
    # Prefer fp32 over fp16 for CPU inference; fp16 on CPU is usually slower.
    fp32 = [f for f in onnx_files if "fp16" not in f.name.lower()]
    return (fp32 or onnx_files)[0]


def download_model(verbose: bool = True) -> Path:
    """
    Fetch the ONNX weights from HuggingFace into MODEL_DIR.

    Filenames vary between repos, so we list the repo and take whatever .onnx
    is there rather than guessing a name.
    """
    from huggingface_hub import hf_hub_download, list_repo_files

    slug = config.MODEL_REPO.replace("/", "__")
    target = model_dir() / slug
    target.mkdir(parents=True, exist_ok=True)

    files = [f for f in list_repo_files(config.MODEL_REPO) if f.endswith(".onnx")]
    if not files:
        raise RuntimeError(
            f"{config.MODEL_REPO} publishes no .onnx weights.\n"
            f"Either pick a repo that does (e.g. OwenElliott/image-safety-classifier-s) "
            f"or export it yourself with optimum."
        )

    fp32 = [f for f in files if "fp16" not in f.lower()]
    chosen = (fp32 or files)[0]
    if verbose:
        print(f"  downloading {config.MODEL_REPO} :: {chosen}")

    path = hf_hub_download(
        repo_id=config.MODEL_REPO,
        filename=chosen,
        local_dir=str(target),
    )
    return Path(path)


class Classifier:
    def __init__(self, verbose: bool = True):
        path = resolve_local_model()
        if path is None:
            if verbose:
                print("model not cached locally, fetching...")
            path = download_model(verbose=verbose)

        opts = ort.SessionOptions()
        if config.ORT_THREADS:
            opts.intra_op_num_threads = config.ORT_THREADS
            opts.inter_op_num_threads = 1
        opts.graph_optimization_level = ort.GraphOptimizationLevel.ORT_ENABLE_ALL

        self.session = ort.InferenceSession(
            str(path), sess_options=opts, providers=["CPUExecutionProvider"]
        )
        # Read the input name from the graph rather than hardcoding "image",
        # so swapping models doesn't silently break.
        self.input_name = self.session.get_inputs()[0].name
        self.model_path = path

    # -- preprocessing ------------------------------------------------------

    @staticmethod
    def _flatten(img: Image.Image) -> Image.Image:
        """RGBA/P/LA -> RGB on white. Transparent regions must not read as black."""
        if img.mode in ("RGBA", "LA") or (img.mode == "P" and "transparency" in img.info):
            base = Image.new("RGB", img.size, (255, 255, 255))
            rgba = img.convert("RGBA")
            base.paste(rgba, mask=rgba.split()[-1])
            return base
        return img.convert("RGB")

    def _to_tensor(self, img: Image.Image) -> np.ndarray:
        img = self._flatten(img).resize(
            (config.INPUT_SIZE, config.INPUT_SIZE), Image.BILINEAR
        )
        arr = np.asarray(img, dtype=np.float32).transpose(2, 0, 1)
        return arr[np.newaxis]  # [1, 3, H, W]

    # -- inference ----------------------------------------------------------

    def _infer(self, img: Image.Image) -> dict:
        probs = self.session.run(None, {self.input_name: self._to_tensor(img)})[0][0]
        return {name: float(p) for name, p in zip(config.CLASS_NAMES, probs)}

    def score_bytes(
        self, data: bytes, filename: str | None = None
    ) -> tuple[dict | None, int, str, str | None]:
        """
        Return (worst-frame scores, frames checked, media kind, worst frame label).

        Worst = lowest P(SFW), across every frame the converters produced:
        animation frames, PDF pages, images embedded in a document. Raises
        UnsupportedMedia / ConversionError from converters for callers that
        want to distinguish those; classify_bytes() handles them.
        """
        kind = converters.detect_kind(data, filename)
        worst: dict | None = None
        worst_src: str | None = None
        count = 0

        for label, frame in converters.extract_frames(data, kind):
            try:
                scores = self._infer(frame)
            finally:
                frame.close()
            count += 1
            if worst is None or scores.get("SFW", 0.0) < worst.get("SFW", 0.0):
                worst, worst_src = scores, label
            if count >= config.MAX_FRAMES_PER_FILE:
                break

        return worst, count, kind, worst_src

    # -- policy -------------------------------------------------------------

    @staticmethod
    def apply_policy(scores: dict, sfw_min: float | None = None) -> tuple[bool, str]:
        """Turn scores into an accept/reject decision. Pure function, no I/O."""
        sfw_min = config.SFW_MIN if sfw_min is None else sfw_min
        sfw = scores.get("SFW", 0.0)
        if sfw < sfw_min:
            return False, f"P(SFW)={sfw:.4f} below threshold {sfw_min:.2f}"
        if scores.get("NSFW", 0.0) > config.NSFW_MAX:
            return False, f"P(NSFW)={scores['NSFW']:.4f} exceeds ceiling {config.NSFW_MAX}"
        if scores.get("NSFL", 0.0) > config.NSFL_MAX:
            return False, f"P(NSFL)={scores['NSFL']:.4f} exceeds ceiling {config.NSFL_MAX}"
        return True, "clean"

    def classify_bytes(
        self,
        data: bytes,
        sfw_min: float | None = None,
        filename: str | None = None,
    ) -> Verdict:
        try:
            scores, frames, kind, worst_src = self.score_bytes(data, filename)
        except converters.UnsupportedMedia as exc:
            # We know what it is, we just can't see inside it. That is a
            # different thing from "this looks explicit", and the caller needs
            # to be able to tell them apart - so it gets its own verdict rather
            # than being folded into a rejection.
            return Verdict(
                accepted=not config.REJECT_UNSUPPORTED,
                reason=f"unsupported media: {exc}",
                scores={},
                frames_checked=0,
                error=None,
                verdict="unsupported",
                media_type=converters.detect_kind(data, filename),
            )
        except Exception as exc:
            # Includes ConversionError and anything Pillow throws. A malformed
            # file must not slip past by crashing the classifier.
            return Verdict(
                accepted=not config.REJECT_UNDECODABLE,
                reason="undecodable image",
                scores={},
                frames_checked=0,
                error=str(exc),
                verdict="undecodable",
            )

        if scores is None:
            return Verdict(
                accepted=not config.REJECT_UNDECODABLE,
                reason="no frames decoded",
                scores={},
                frames_checked=0,
                verdict="undecodable",
                media_type=kind,
            )

        ok, reason = self.apply_policy(scores, sfw_min)
        return Verdict(
            accepted=ok,
            reason=reason,
            scores=scores,
            frames_checked=frames,
            verdict="accept" if ok else "reject",
            media_type=kind,
            worst_source=worst_src,
        )

    def classify_path(self, path: str | os.PathLike, sfw_min: float | None = None) -> Verdict:
        p = Path(path)
        return self.classify_bytes(p.read_bytes(), sfw_min, filename=p.name)


_singleton: Classifier | None = None


def get_classifier(verbose: bool = False) -> Classifier:
    global _singleton
    if _singleton is None:
        _singleton = Classifier(verbose=verbose)
    return _singleton
