#!/usr/bin/env bash
#
# One-shot setup. Creates the venv, installs deps, downloads the model,
# runs a smoke test. Safe to re-run.
#
#   ./setup.sh
#
set -euo pipefail

cd "$(dirname "$0")"

BLUE='\033[0;34m'; GREEN='\033[0;32m'; RED='\033[0;31m'; YEL='\033[0;33m'; OFF='\033[0m'
step() { echo -e "\n${BLUE}==>${OFF} $*"; }
ok()   { echo -e "${GREEN}  ok${OFF} $*"; }
warn() { echo -e "${YEL}  !${OFF} $*"; }
die()  { echo -e "${RED}  x${OFF} $*" >&2; exit 1; }

# --- python ----------------------------------------------------------------

step "Checking Python"
command -v python3 >/dev/null 2>&1 || die "python3 not found. sudo apt install python3"

PYV=$(python3 -c 'import sys; print("%d.%d" % sys.version_info[:2])')
python3 - <<'EOF' || die "Python 3.10+ required (dataclass and union syntax). Yours is too old."
import sys
sys.exit(0 if sys.version_info >= (3, 10) else 1)
EOF
ok "python3 $PYV"

# Mint/Ubuntu ship python3 without venv by default often enough to check.
if ! python3 -c "import venv, ensurepip" 2>/dev/null; then
    die "python venv module missing. Run:  sudo apt install python3-venv python3-pip"
fi

# --- venv ------------------------------------------------------------------

step "Creating virtualenv"
if [ -d .venv ]; then
    ok ".venv already exists, reusing"
else
    python3 -m venv .venv
    ok "created .venv"
fi

# shellcheck disable=SC1091
source .venv/bin/activate
python -m pip install --upgrade pip setuptools wheel --quiet
ok "pip $(pip --version | awk '{print $2}')"

# --- deps ------------------------------------------------------------------

step "Installing dependencies (this is the slow bit)"
pip install --quiet -r requirements.txt
ok "dependencies installed"

# --- model -----------------------------------------------------------------

step "Downloading model"
python - <<'EOF'
import config
from classifier import resolve_local_model, download_model

existing = resolve_local_model()
if existing:
    print(f"  already cached: {existing}")
else:
    path = download_model(verbose=True)
    print(f"  saved to {path}")
EOF
ok "model ready"

# --- smoke test ------------------------------------------------------------

step "Smoke test"
python - <<'EOF'
import io, time
from PIL import Image
import config
from classifier import get_classifier

clf = get_classifier(verbose=False)

buf = io.BytesIO()
Image.new("RGB", (512, 512), (200, 190, 180)).save(buf, format="PNG")
data = buf.getvalue()

clf.classify_bytes(data)  # warm

t0 = time.perf_counter()
for _ in range(10):
    v = clf.classify_bytes(data)
ms = (time.perf_counter() - t0) / 10 * 1000

print(f"  model      : {clf.model_path.name}")
print(f"  classes    : {config.CLASS_NAMES}")
print(f"  blank image: accepted={v.accepted}  scores={ {k: round(x,4) for k,x in v.scores.items()} }")
print(f"  latency    : {ms:.1f} ms/image ({config.ORT_THREADS or 'auto'} threads)")
EOF
ok "smoke test passed"

# --- converter backends ----------------------------------------------------

step "Checking format converters"
python - <<'PYCHECK'
import converters

report = converters.backend_report()
missing = []
for kind, backends in report.items():
    state = ", ".join(backends) if backends else "UNAVAILABLE"
    print(f"  {kind:<8} {state}")
    if not backends and kind != "video":
        missing.append(kind)

if missing:
    print()
    print("  The wiki accepts formats this build cannot look inside: "
          + ", ".join(missing))
    print("  Those uploads come back as 'unsupported' and get decided by policy")
    print("  rather than by the model. SVG matters most here - MediaWiki renders")
    print("  it inline, so an unscannable SVG is a real gap, not a cosmetic one.")
    print()
    if "svg" in missing:
        print("    sudo apt install libcairo2 librsvg2-bin")
    if "pdf" in missing:
        print("    sudo apt install poppler-utils")
PYCHECK
ok "converter check complete"

# --- corpus dirs -----------------------------------------------------------

step "Preparing corpus directories"
mkdir -p corpus/sfw corpus/nsfw corpus/unlabeled
ok "corpus/{sfw,nsfw,unlabeled}"

# --- done ------------------------------------------------------------------

echo
echo -e "${GREEN}Setup complete.${OFF}"
echo
echo "  Start the service:      ./run.sh"
echo "  Build a test corpus:    ./venv-run.sh fetch_wiki_images.py"
echo "  Run the backtest:       ./venv-run.sh backtest.py"
echo
if grep -q CHANGE_ME config.py; then
    warn "config.py still has CHANGE_ME in WIKI_USER_AGENT - set a contact address"
    warn "before running fetch_wiki_images.py."
fi
echo "  Threshold lives at config.py :: SFW_MIN (currently $(python -c 'import config; print(config.SFW_MIN)'))"
echo
echo "  0.50 is the validated production value for consumerrights.wiki: measured"
echo "  against the wiki's full existing corpus and a known-bad set, not guessed."
echo "  Re-run backtest.py before changing it, and whenever MODEL_REPO changes -"
echo "  the number is a property of this model on this corpus, not a constant."
echo
