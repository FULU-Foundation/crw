"""
imgguard configuration.

This is the ONLY file you should need to edit for normal tuning.
Everything else reads from here.

Every setting below can also be overridden by an environment variable with an
IMGGUARD_ prefix (IMGGUARD_SFW_MIN, IMGGUARD_PORT, ...). That exists so the
Docker deployment can set a couple of values without a bind mount; on a bare
metal install, ignore it and edit this file.
"""

import os


def _env(name: str, default, cast=None):
    raw = os.environ.get("IMGGUARD_" + name)
    if raw is None:
        return default
    if cast is bool:
        return raw.strip().lower() in ("1", "true", "yes", "on")
    if cast is not None:
        try:
            return cast(raw)
        except (TypeError, ValueError):
            return default
    return raw


# ---------------------------------------------------------------------------
# MODEL
# ---------------------------------------------------------------------------

# HuggingFace repo. Swap to -m / -s if you want something smaller, or to a
# different repo entirely (you'll need to check CLASS_NAMES matches).
MODEL_REPO = _env("MODEL_REPO", "OwenElliott/image-safety-classifier-l")

# Output class order for this model family. Do not reorder unless you've
# actually checked the model card.
CLASS_NAMES = ["NSFL", "NSFW", "SFW"]

# Input side length. SwiftFormer safety classifiers use 224.
INPUT_SIZE = 224

# Where the .onnx gets cached. Relative paths resolve to the repo root.
MODEL_DIR = _env("MODEL_DIR", "models")

# CPU threads for ONNX Runtime. 0 = let ORT decide.
# On a box that also runs a wiki, pinning this to 1 or 2 stops a burst of
# uploads from eating every core.
ORT_THREADS = _env("ORT_THREADS", 2, int)


# ---------------------------------------------------------------------------
# DECISION THRESHOLDS  <-- this is the bit you came here to change
# ---------------------------------------------------------------------------

# An image is ACCEPTED only if P(SFW) >= SFW_MIN.
#
# Note this thresholds on SFW confidence, not on NSFW confidence. That means
# "the model is unsure" behaves like "reject", which is the failure direction
# you want. Raise it to be stricter.
#
# 0.50 is the validated production value for consumerrights.wiki. It was
# measured, not guessed: backtested against the full corpus of images already
# uploaded to the wiki (zero false positives) and against a set of known-bad
# images (all rejected). Because the model emits a 3-class softmax, P(SFW) >=
# 0.50 is equivalent to "SFW is the winning class" - i.e. plain argmax. On this
# corpus the model is decisive rather than marginal, which is why argmax is
# sufficient and why raising the threshold buys strictness the corpus says
# isn't needed.
#
# Re-run backtest.py before changing this, and re-run it whenever MODEL_REPO
# changes: the number is a property of this model on this corpus, not a
# universal constant.
#
#   0.50  = validated for this wiki (equivalent to argmax)
#   0.90  = stricter; only justified if the FP sweep says so
#   0.98  = very aggressive, expect real false positives
SFW_MIN = _env("SFW_MIN", 0.50, float)

# Independent hard ceilings. Even if P(SFW) somehow clears the bar, exceeding
# either of these rejects. Set to 1.0 to disable a given check.
NSFW_MAX = _env("NSFW_MAX", 1.0, float)
NSFL_MAX = _env("NSFL_MAX", 1.0, float)

# Animated images (GIF / animated WebP / APNG) are sampled across frames and
# judged on the WORST frame. A safe first frame is otherwise a trivial bypass.
ANIMATION_MAX_FRAMES = _env("ANIMATION_MAX_FRAMES", 8, int)

# Reject anything that isn't a decodable still or animated raster image.
# Undecodable input is treated as a rejection rather than an error, so a
# malformed file can't slip past by crashing the classifier.
REJECT_UNDECODABLE = _env("REJECT_UNDECODABLE", True, bool)

# What to do with a file whose format we recognise but cannot look inside:
# legacy .doc/.xls, an Office file with no decodable embedded images, video
# when SCAN_VIDEO is off. The verdict is reported as "unsupported" either way;
# this only decides whether it counts as accepted.
#
# False (default) hands the decision to MediaWiki, which can apply its own
# policy and expose it to AbuseFilter. True rejects at the scanner. Leave this
# alone unless you're running imgguard without the MediaWiki extension.
REJECT_UNSUPPORTED = _env("REJECT_UNSUPPORTED", False, bool)


# ---------------------------------------------------------------------------
# FORMAT CONVERSION
# ---------------------------------------------------------------------------
#
# The wiki accepts more than JPEG and PNG. Anything it accepts that can carry a
# picture and that the scanner can't read is a bypass, so non-raster formats are
# converted to frames before classification. See converters.py; /health reports
# which backends are actually installed.

# SVG is rasterised to this many pixels on the long edge before scanning.
SVG_RENDER_PX = _env("SVG_RENDER_PX", 1024, int)

# PDFs are rendered (not image-extracted) so vector art and text count too.
PDF_MAX_PAGES = _env("PDF_MAX_PAGES", 8, int)
PDF_RENDER_DPI = _env("PDF_RENDER_DPI", 96, int)

# Office containers (docx/xlsx/pptx/odt/ods) have their embedded bitmaps pulled
# out of the ZIP and scanned individually. These caps bound a zip bomb.
OFFICE_MAX_IMAGES = _env("OFFICE_MAX_IMAGES", 20, int)
OFFICE_MAX_ENTRY_BYTES = _env("OFFICE_MAX_ENTRY_BYTES", 32 * 1024 * 1024, int)
OFFICE_MAX_TOTAL_BYTES = _env("OFFICE_MAX_TOTAL_BYTES", 128 * 1024 * 1024, int)

# Video frame sampling via ffmpeg. Off by default: on consumerrights.wiki video
# uploads are restricted to sysops, who hold imgguard-bypass anyway, so this
# would burn CPU on files that are never scanned. Turn it on if that changes.
SCAN_VIDEO = _env("SCAN_VIDEO", False, bool)
VIDEO_MAX_FRAMES = _env("VIDEO_MAX_FRAMES", 12, int)
VIDEO_SAMPLE_INTERVAL = _env("VIDEO_SAMPLE_INTERVAL", 5, int)   # seconds between samples

# Hard timeout for any external converter (rsvg-convert, pdftoppm, ffmpeg,
# ImageMagick). A hung converter must not hold an upload request open.
SUBPROCESS_TIMEOUT = _env("SUBPROCESS_TIMEOUT", 20, int)

# Absolute cap on frames classified for a single upload, across every format.
# Bounds the worst case: a 200-page PDF or a long video.
MAX_FRAMES_PER_FILE = _env("MAX_FRAMES_PER_FILE", 24, int)


# ---------------------------------------------------------------------------
# SERVICE
# ---------------------------------------------------------------------------

# 127.0.0.1 for a bare-metal install alongside the wiki. The Docker deployment
# sets IMGGUARD_HOST=0.0.0.0 and relies on the compose network for isolation.
HOST = _env("HOST", "127.0.0.1")
PORT = _env("PORT", 8181, int)

# Optional shared secret. If set to a non-empty string, callers must send
# it as the X-Auth-Token header. Bound to localhost by default so this is
# belt-and-braces, but useful if you ever expose it on a private interface.
# Set this if you run the Docker deployment.
AUTH_TOKEN = _env("AUTH_TOKEN", "")

# Reject uploads larger than this before decoding them, in megabytes.
# Note the wiki's own $wgMaxUploadSize is 200 MB; anything between the two
# limits never reaches the model, so MediaWiki decides what to do with it
# ($wgImgGuardOversizeAction).
MAX_UPLOAD_MB = _env("MAX_UPLOAD_MB", 32, int)

# Decompression-bomb guard: refuse images above this many total pixels.
# 80MP is comfortably above any legitimate screenshot or photo.
MAX_PIXELS = _env("MAX_PIXELS", 80_000_000, int)


# ---------------------------------------------------------------------------
# WIKI CORPUS FETCHER
# ---------------------------------------------------------------------------

WIKI_API = _env("WIKI_API", "https://consumerrights.wiki/api.php")

# Set this to something with a contact address. Wiki operators (including you)
# should be able to tell who is hammering the API.
WIKI_USER_AGENT = _env(
    "WIKI_USER_AGENT",
    "imgguard-backtest/1.0 (moderation tooling; contact: CHANGE_ME@example.com)",
)

# Seconds between API/download requests. Be polite.
WIKI_REQUEST_DELAY = _env("WIKI_REQUEST_DELAY", 0.34, float)

# Cap on how many images to pull. None = all of them.
WIKI_MAX_IMAGES = _env("WIKI_MAX_IMAGES", 2000, int)


# ---------------------------------------------------------------------------
# CORPUS LAYOUT
# ---------------------------------------------------------------------------
#
#   corpus/
#     sfw/        wiki images land here; assumed known-good
#     nsfw/       drop known-positive test images here yourself
#     unlabeled/  anything else; scored but not counted in error rates
#
CORPUS_DIR = _env("CORPUS_DIR", "corpus")

# Thresholds the backtest sweeps over when building its table.
BACKTEST_SWEEP = [0.50, 0.70, 0.80, 0.90, 0.95, 0.98, 0.99]
