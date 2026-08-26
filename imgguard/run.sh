#!/usr/bin/env bash
#
# Start the classifier service. Reads host/port/threshold from config.py.
#
#   ./run.sh
#
set -euo pipefail
cd "$(dirname "$0")"

[ -d .venv ] || { echo "No .venv found. Run ./setup.sh first." >&2; exit 1; }

# shellcheck disable=SC1091
source .venv/bin/activate

HOST=$(python -c 'import config; print(config.HOST)')
PORT=$(python -c 'import config; print(config.PORT)')
THRESH=$(python -c 'import config; print(config.SFW_MIN)')
MODEL=$(python -c 'import config; print(config.MODEL_REPO)')

echo "imgguard"
echo "  model     : $MODEL"
echo "  threshold : P(SFW) >= $THRESH"
echo "  listening : http://$HOST:$PORT"
echo "  docs      : http://$HOST:$PORT/docs"
echo

exec uvicorn server:app --host "$HOST" --port "$PORT" --log-level info "$@"
