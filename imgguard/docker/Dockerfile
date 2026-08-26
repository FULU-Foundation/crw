# imgguard - image safety classifier service
#
# Deliberately not the smallest possible image: the converter backends are the
# point. A slim image that cannot rasterise SVG would silently downgrade every
# SVG upload to "unsupported", which on a wiki that renders SVG inline is a
# real gap rather than a cosmetic one.
#
# Build context is the imgguard/ directory:
#   docker build -f docker/Dockerfile -t imgguard .

FROM python:3.12-slim-bookworm

ENV PYTHONUNBUFFERED=1 \
    PIP_NO_CACHE_DIR=1 \
    HF_HOME=/opt/imgguard/.cache/huggingface

# libcairo2     : cairosvg's runtime dependency (primary SVG backend)
# librsvg2-bin  : rsvg-convert, the SVG fallback
# poppler-utils : pdftoppm, multi-page PDF rendering
RUN apt-get update && apt-get install -y --no-install-recommends \
        libcairo2 \
        librsvg2-bin \
        poppler-utils \
        curl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /opt/imgguard

COPY requirements.txt ./
RUN pip install --no-cache-dir -r requirements.txt

COPY *.py ./

# Bake the model into the image rather than fetching it on container start. A
# cold start that reaches out to HuggingFace is a cold start that fails when
# HuggingFace is having a bad day, and with FailClosed = true that stops
# uploads wiki-wide.
RUN python -c "from classifier import download_model; download_model(verbose=True)"

RUN useradd --system --uid 10001 --home /opt/imgguard imgguard \
    && chown -R imgguard:imgguard /opt/imgguard
USER imgguard

ENV IMGGUARD_HOST=0.0.0.0 \
    IMGGUARD_PORT=8181

EXPOSE 8181

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD curl -fsS http://127.0.0.1:8181/health || exit 1

CMD ["python", "-m", "uvicorn", "server:app", "--host", "0.0.0.0", "--port", "8181", "--log-level", "info"]
