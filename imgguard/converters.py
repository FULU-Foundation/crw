"""
Format detection and frame extraction.

The wiki accepts a good deal more than JPEG and PNG, and every format it accepts
that can carry a picture is a bypass if the scanner can't read it. This module
turns an arbitrary upload into a sequence of PIL images for the classifier to
look at, so the policy applies uniformly across formats.

Everything here is content-sniffed. The filename is used only to disambiguate
cases where the container alone is ambiguous (a ZIP could be .docx or .xlsx),
never to decide whether something is safe: a caller-supplied extension is not
evidence.

  detect_kind(data, filename) -> 'raster' | 'svg' | 'pdf' | 'office' | 'video' | 'unknown'
  extract_frames(data, kind)  -> Iterator[(label, PIL.Image)]

`label` says where the frame came from ("frame 3", "page 2",
"word/media/image1.png"), so a rejection can name the offending part of a
multi-part file rather than just the file.

Backends are probed at import time and degrade gracefully: a missing PDF backend
makes PDFs unsupported, it doesn't break the service. `backend_report()` says
what's actually available, and the server exposes that on /health.
"""

from __future__ import annotations

import io
import os
import re
import shutil
import subprocess
import tempfile
import zipfile
from typing import Iterator

from PIL import Image, ImageFile, ImageSequence

import config

ImageFile.LOAD_TRUNCATED_IMAGES = True
Image.MAX_IMAGE_PIXELS = config.MAX_PIXELS


class UnsupportedMedia(Exception):
    """We recognised the format but have no backend that can read it."""


class ConversionError(Exception):
    """We have a backend, it ran, it failed."""


# ---------------------------------------------------------------------------
# Backend probing
# ---------------------------------------------------------------------------

def _have_module(name: str) -> bool:
    try:
        __import__(name)
        return True
    except Exception:
        return False


_HAS_CAIROSVG = _have_module("cairosvg")
_HAS_FITZ = _have_module("fitz")          # PyMuPDF

_BIN_RSVG = shutil.which("rsvg-convert")
_BIN_PDFTOPPM = shutil.which("pdftoppm")
_BIN_FFMPEG = shutil.which("ffmpeg")
# ImageMagick 7 renamed the binary; accept either.
_BIN_MAGICK = shutil.which("magick") or shutil.which("convert")


_PROBE_SVG = b'<svg xmlns="http://www.w3.org/2000/svg" width="4" height="4">' \
             b'<rect width="4" height="4" fill="#888"/></svg>'

_probe_cache: dict | None = None


def _probe_backends() -> dict:
    """
    Actually try each backend once, rather than trusting that an installed
    binary can do the job.

    ImageMagick is the reason this exists: `convert` is frequently present but
    delegates SVG to librsvg, so it reports as available and then fails on
    every SVG. A health endpoint that says "svg: convert" when no SVG can be
    rendered is worse than one that says "svg: unavailable", because it hides
    a silent bypass - unscannable uploads sailing through as errors.
    """
    svg, pdf = [], []

    if _HAS_CAIROSVG:
        try:
            import cairosvg
            cairosvg.svg2png(bytestring=_PROBE_SVG, output_width=4, output_height=4, unsafe=False)
            svg.append("cairosvg")
        except Exception:
            pass

    if _BIN_RSVG:
        try:
            _run([_BIN_RSVG, "--format=png", "--width=4"], stdin=_PROBE_SVG)
            svg.append("rsvg-convert")
        except Exception:
            pass

    if _BIN_MAGICK:
        try:
            _magick_svg_to_png(_PROBE_SVG, 4)
            svg.append(os.path.basename(_BIN_MAGICK))
        except Exception:
            pass

    # PDF backends are cheap to verify by import/presence; unlike SVG, neither
    # PyMuPDF nor poppler farms the work out to a delegate that might be absent.
    if _HAS_FITZ:
        pdf.append("pymupdf")
    if _BIN_PDFTOPPM:
        pdf.append("pdftoppm")
    if _BIN_MAGICK:
        pdf.append(os.path.basename(_BIN_MAGICK) + " (single page only)")

    return {
        "raster": ["pillow"],
        "svg": svg,
        "pdf": pdf,
        "office": ["zipfile"],
        "video": [os.path.basename(_BIN_FFMPEG)] if _BIN_FFMPEG else [],
    }


def backend_report() -> dict:
    """What each media kind can currently be handled by. Surfaced on /health."""
    global _probe_cache
    if _probe_cache is None:
        _probe_cache = _probe_backends()
    return _probe_cache


def supported_kinds() -> set[str]:
    report = backend_report()
    kinds = {k for k, v in report.items() if v}
    if not config.SCAN_VIDEO:
        kinds.discard("video")
    return kinds


# ---------------------------------------------------------------------------
# Detection
# ---------------------------------------------------------------------------

_OFFICE_ZIP_MARKERS = (
    "word/",
    "xl/",
    "ppt/",
    "content.xml",       # ODF
    "Pictures/",         # ODF embedded media
    "[Content_Types].xml",
)

_VIDEO_EXTS = {"mp4", "m4v", "webm", "mov", "avi", "mkv", "flv", "wmv", "ogv"}
_OFFICE_EXTS = {"docx", "xlsx", "pptx", "odt", "ods", "odp", "doc", "xls", "ppt"}

# <svg ...> possibly preceded by an XML declaration, comments or a DOCTYPE.
_SVG_RE = re.compile(rb"<\s*svg[\s>]", re.IGNORECASE)


def _ext(filename: str | None) -> str:
    if not filename:
        return ""
    return os.path.splitext(filename)[1].lstrip(".").lower()


def detect_kind(data: bytes, filename: str | None = None) -> str:
    """
    Identify the container from its bytes.

    The filename is consulted only for ZIP and ISO-BMFF containers, which are
    genuinely ambiguous from magic bytes alone, and only to choose between
    handlers - never to upgrade something to 'supported' that we couldn't
    otherwise read.
    """
    head = data[:4096]
    ext = _ext(filename)

    # --- unambiguous magic numbers ---
    if head[:8] == b"\x89PNG\r\n\x1a\n":
        return "raster"
    if head[:3] == b"\xff\xd8\xff":
        return "raster"
    if head[:6] in (b"GIF87a", b"GIF89a"):
        return "raster"
    if head[:2] in (b"BM",) and len(data) > 6:
        return "raster"
    if head[:4] in (b"II*\x00", b"MM\x00*"):
        return "raster"
    if head[:4] == b"RIFF" and head[8:12] == b"WEBP":
        return "raster"
    if head[:4] == b"%PDF":
        return "pdf"
    if head[:4] == b"\x1a\x45\xdf\xa3":          # Matroska / WebM
        return "video"
    if head[:3] == b"FLV":
        return "video"
    if head[:4] == b"RIFF" and head[8:12] == b"AVI ":
        return "video"
    if head[:8] == b"\x30\x26\xb2\x75":          # ASF / WMV
        return "video"

    # --- ISO base media (MP4/MOV/M4V, and AVIF/HEIF which share the box format) ---
    if len(head) >= 12 and head[4:8] == b"ftyp":
        brand = head[8:12]
        if brand in (b"avif", b"avis", b"heic", b"heix", b"hevc", b"mif1", b"msf1"):
            return "raster"
        return "video"

    # --- SVG: text, needs a real look rather than a fixed prefix ---
    if _SVG_RE.search(head):
        return "svg"
    if head.lstrip()[:5] == b"<?xml" and _SVG_RE.search(data[:65536]):
        return "svg"

    # --- ZIP: OOXML, ODF, or something else entirely ---
    if head[:2] == b"PK":
        try:
            with zipfile.ZipFile(io.BytesIO(data)) as zf:
                names = zf.namelist()[:200]
            if any(n.startswith(_OFFICE_ZIP_MARKERS) or n in _OFFICE_ZIP_MARKERS for n in names):
                return "office"
        except Exception:
            pass
        if ext in _OFFICE_EXTS:
            return "office"
        return "unknown"

    # --- OLE2 compound file: legacy .doc/.xls/.ppt ---
    if head[:8] == b"\xd0\xcf\x11\xe0\xa1\xb1\x1a\xe1":
        return "office_legacy"

    # --- last resort: let Pillow have a go, it knows formats we haven't listed ---
    try:
        with Image.open(io.BytesIO(data)) as probe:
            probe.verify()
        return "raster"
    except Exception:
        pass

    if ext in _VIDEO_EXTS:
        return "video"
    return "unknown"


# ---------------------------------------------------------------------------
# Raster
# ---------------------------------------------------------------------------

def _raster_frames(data: bytes) -> Iterator[tuple[str, Image.Image]]:
    """
    Still images yield one frame. Animations yield up to ANIMATION_MAX_FRAMES
    evenly spaced across the timeline - a safe first frame is otherwise a
    trivial bypass.
    """
    with Image.open(io.BytesIO(data)) as img:
        n = getattr(img, "n_frames", 1)
        if n <= 1:
            yield "image", img.copy()
            return

        cap = max(1, config.ANIMATION_MAX_FRAMES)
        if n <= cap:
            wanted = set(range(n))
        else:
            step = (n - 1) / (cap - 1) if cap > 1 else 0
            wanted = {int(round(i * step)) for i in range(cap)}

        for i, frame in enumerate(ImageSequence.Iterator(img)):
            if i in wanted:
                yield f"frame {i}", frame.copy()


# ---------------------------------------------------------------------------
# SVG
# ---------------------------------------------------------------------------

def _run(cmd: list[str], stdin: bytes | None = None) -> bytes:
    """Subprocess with a hard timeout and no inherited stdin."""
    try:
        proc = subprocess.run(
            cmd,
            input=stdin,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            timeout=config.SUBPROCESS_TIMEOUT,
            check=False,
        )
    except subprocess.TimeoutExpired as exc:
        raise ConversionError(f"{cmd[0]} timed out after {config.SUBPROCESS_TIMEOUT}s") from exc
    except OSError as exc:
        raise ConversionError(f"{cmd[0]} failed to start: {exc}") from exc

    if proc.returncode != 0:
        tail = proc.stderr.decode("utf-8", "replace").strip()[-400:]
        raise ConversionError(f"{cmd[0]} exited {proc.returncode}: {tail}")
    return proc.stdout


def _magick_svg_to_png(data: bytes, px: int) -> bytes:
    """
    ImageMagick reads SVG through a delegate, and the delegate handshake is
    unreliable on stdin, so hand it a real file in an otherwise empty temp
    directory. Empty matters: it is also the base directory for any relative
    reference in the document, so there is nothing there to resolve to.
    """
    cmd = [_BIN_MAGICK]
    if os.path.basename(_BIN_MAGICK) == "magick":
        cmd.append("convert")
    with tempfile.TemporaryDirectory() as td:
        src = os.path.join(td, "in.svg")
        with open(src, "wb") as fh:
            fh.write(data)
        cmd += ["-background", "white", "-density", "144",
                "-resize", f"{px}x{px}>", f"svg:{src}", "png:-"]
        return _run(cmd)


def _svg_frames(data: bytes) -> Iterator[tuple[str, Image.Image]]:
    """
    Rasterise SVG at SVG_RENDER_PX on the long edge.

    None of these backends is allowed to fetch remote resources. cairosvg is
    called with unsafe=False (its default), which refuses external references
    outright; rsvg-convert gets the document on stdin with no base URI, and
    ImageMagick gets it in an empty temp directory. That matters: an SVG able
    to pull in a remote bitmap would scan clean and then render as something
    else entirely in the browser.

    Backends are tried in quality order, skipping any that failed the startup
    probe, and a backend that fails on this particular document falls through
    to the next one.
    """
    px = config.SVG_RENDER_PX
    available = backend_report()["svg"]
    last_error: Exception | None = None

    if not available:
        raise UnsupportedMedia(
            "no working SVG backend (install python3-cairosvg or librsvg2-bin)"
        )

    for backend in available:
        try:
            if backend == "cairosvg":
                import cairosvg
                png = cairosvg.svg2png(
                    bytestring=data, output_width=px, output_height=px, unsafe=False
                )
            elif backend == "rsvg-convert":
                png = _run(
                    [_BIN_RSVG, "--format=png", f"--width={px}", "--keep-aspect-ratio"],
                    stdin=data,
                )
            else:
                png = _magick_svg_to_png(data, px)
            yield "svg", Image.open(io.BytesIO(png)).copy()
            return
        except Exception as exc:
            last_error = exc

    raise ConversionError(f"all SVG backends failed; last error: {last_error}")


# ---------------------------------------------------------------------------
# PDF
# ---------------------------------------------------------------------------

def _pdf_frames(data: bytes) -> Iterator[tuple[str, Image.Image]]:
    """Render up to PDF_MAX_PAGES pages. Rendering, not image extraction: text
    and vector drawings can be just as much of a problem as embedded bitmaps."""
    cap = max(1, config.PDF_MAX_PAGES)
    dpi = config.PDF_RENDER_DPI

    if _HAS_FITZ:
        import fitz
        try:
            doc = fitz.open(stream=data, filetype="pdf")
        except Exception as exc:
            raise ConversionError(f"PyMuPDF could not open the PDF: {exc}") from exc
        try:
            for i in range(min(doc.page_count, cap)):
                pix = doc.load_page(i).get_pixmap(dpi=dpi, alpha=False)
                yield f"page {i + 1}", Image.open(io.BytesIO(pix.tobytes("png"))).copy()
        finally:
            doc.close()
        return

    if _BIN_PDFTOPPM:
        with tempfile.TemporaryDirectory() as td:
            src = os.path.join(td, "in.pdf")
            with open(src, "wb") as fh:
                fh.write(data)
            _run([
                _BIN_PDFTOPPM, "-png", "-r", str(dpi),
                "-f", "1", "-l", str(cap),
                src, os.path.join(td, "page"),
            ])
            pages = sorted(p for p in os.listdir(td) if p.startswith("page") and p.endswith(".png"))
            if not pages:
                raise ConversionError("pdftoppm produced no pages")
            for i, name in enumerate(pages[:cap], start=1):
                with open(os.path.join(td, name), "rb") as fh:
                    yield f"page {i}", Image.open(io.BytesIO(fh.read())).copy()
        return

    if _BIN_MAGICK:
        # Last resort, and a weak one: ImageMagick renders PDFs through
        # Ghostscript and concatenates multi-page output into a stream Pillow
        # only reads the first frame of. Effectively a first-page-only scan,
        # which is exactly the kind of partial coverage that lets page 2 slip
        # through - install pymupdf or poppler-utils instead.
        cmd = [_BIN_MAGICK]
        if os.path.basename(_BIN_MAGICK) == "magick":
            cmd.append("convert")
        with tempfile.TemporaryDirectory() as td:
            src = os.path.join(td, "in.pdf")
            with open(src, "wb") as fh:
                fh.write(data)
            cmd += ["-density", str(dpi), f"pdf:{src}[0]",
                    "-background", "white", "-alpha", "remove", "png:-"]
            png = _run(cmd)
        yield "page 1", Image.open(io.BytesIO(png)).copy()
        return

    raise UnsupportedMedia(
        "no PDF backend available (install pymupdf or poppler-utils)"
    )


# ---------------------------------------------------------------------------
# Office containers (OOXML / ODF)
# ---------------------------------------------------------------------------

_OFFICE_MEDIA_PREFIXES = ("word/media/", "xl/media/", "ppt/media/", "Pictures/", "media/")
_OFFICE_IMAGE_EXTS = {".png", ".jpg", ".jpeg", ".gif", ".webp", ".bmp", ".tif", ".tiff", ".emf", ".wmf"}


def _office_frames(data: bytes) -> Iterator[tuple[str, Image.Image]]:
    """
    Pull embedded bitmaps out of the ZIP container.

    This does not render the document, so it will miss anything drawn with
    vector shapes, and EMF/WMF usually won't decode. It catches the ordinary
    case - a picture pasted into a document - which is what actually turns up.
    Everything here is bounded: entry count, per-entry size and total size, so
    a zip bomb costs a rejection rather than the box.
    """
    seen = 0
    total = 0
    try:
        zf = zipfile.ZipFile(io.BytesIO(data))
    except Exception as exc:
        raise ConversionError(f"not a readable ZIP container: {exc}") from exc

    with zf:
        for info in zf.infolist():
            if seen >= config.OFFICE_MAX_IMAGES:
                break
            name = info.filename
            if not name.startswith(_OFFICE_MEDIA_PREFIXES):
                continue
            if os.path.splitext(name)[1].lower() not in _OFFICE_IMAGE_EXTS:
                continue
            if info.file_size > config.OFFICE_MAX_ENTRY_BYTES:
                continue
            if total + info.file_size > config.OFFICE_MAX_TOTAL_BYTES:
                break

            try:
                blob = zf.read(name)
            except Exception:
                continue
            total += len(blob)

            try:
                img = Image.open(io.BytesIO(blob))
                img.load()
            except Exception:
                # EMF/WMF and other things Pillow won't touch. Not a failure of
                # the scan; the remaining images still get looked at.
                continue

            seen += 1
            yield name, img

    if seen == 0:
        raise UnsupportedMedia("no decodable embedded images in this document")


# ---------------------------------------------------------------------------
# Video
# ---------------------------------------------------------------------------

def _video_frames(data: bytes) -> Iterator[tuple[str, Image.Image]]:
    """Sample frames with ffmpeg's thumbnail filter. Off by default; see
    config.SCAN_VIDEO."""
    if not _BIN_FFMPEG:
        raise UnsupportedMedia("no video backend available (install ffmpeg)")

    cap = max(1, config.VIDEO_MAX_FRAMES)
    with tempfile.TemporaryDirectory() as td:
        src = os.path.join(td, "in")
        with open(src, "wb") as fh:
            fh.write(data)
        _run([
            _BIN_FFMPEG, "-nostdin", "-loglevel", "error",
            "-i", src,
            "-vf", f"fps=1/{config.VIDEO_SAMPLE_INTERVAL},scale=512:-1",
            "-frames:v", str(cap),
            "-f", "image2", os.path.join(td, "f%04d.png"),
        ])
        frames = sorted(p for p in os.listdir(td) if p.startswith("f") and p.endswith(".png"))
        if not frames:
            raise ConversionError("ffmpeg produced no frames")
        for i, name in enumerate(frames[:cap]):
            with open(os.path.join(td, name), "rb") as fh:
                yield f"frame @ {i * config.VIDEO_SAMPLE_INTERVAL}s", Image.open(io.BytesIO(fh.read())).copy()


# ---------------------------------------------------------------------------
# Entry point
# ---------------------------------------------------------------------------

_DISPATCH = {
    "raster": _raster_frames,
    "svg": _svg_frames,
    "pdf": _pdf_frames,
    "office": _office_frames,
    "video": _video_frames,
}


def extract_frames(data: bytes, kind: str) -> Iterator[tuple[str, Image.Image]]:
    """Yield (label, image) for a detected kind. Raises UnsupportedMedia or
    ConversionError; the caller decides what those mean for the verdict."""
    if kind == "video" and not config.SCAN_VIDEO:
        raise UnsupportedMedia("video scanning is disabled (config.SCAN_VIDEO)")
    if kind == "office_legacy":
        raise UnsupportedMedia(
            "legacy OLE2 Office files (.doc/.xls/.ppt) cannot be inspected"
        )

    if kind == "unknown":
        # Not the same thing as UnsupportedMedia, and the difference decides
        # whether the upload is allowed. "Unsupported" means we identified the
        # format and chose not to (or cannot) look inside - a known quantity the
        # wiki can have a policy about. "Unknown" means the bytes match nothing
        # we recognise, so we cannot say anything about the content at all, and
        # a file we cannot say anything about must not be waved through on the
        # strength of an allow-by-default policy meant for legacy .doc files.
        raise ConversionError(
            "unrecognised container: the bytes match no format we can decode"
        )

    handler = _DISPATCH.get(kind)
    if handler is None:
        raise UnsupportedMedia(f"no handler for container type ({kind})")
    return handler(data)
