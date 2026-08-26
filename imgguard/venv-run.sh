#!/usr/bin/env bash
#
# Run any script in this project inside the venv without activating it yourself.
#
#   ./venv-run.sh backtest.py
#   ./venv-run.sh fetch_wiki_images.py --limit 200
#
set -euo pipefail
cd "$(dirname "$0")"
[ -d .venv ] || { echo "No .venv found. Run ./setup.sh first." >&2; exit 1; }
# shellcheck disable=SC1091
source .venv/bin/activate
exec python "$@"
