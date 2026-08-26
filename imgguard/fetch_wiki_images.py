#!/usr/bin/env python3
"""
Pull images from a MediaWiki instance into corpus/sfw/ to use as a
false-positive test set.

Uses list=allimages, which is the sanctioned bulk-listing endpoint. Rate
limited via config.WIKI_REQUEST_DELAY. Set config.WIKI_USER_AGENT to
something with a contact address before running this against anything.

Already-downloaded files are skipped, so re-running tops up the corpus
incrementally rather than starting over.

  python fetch_wiki_images.py
  python fetch_wiki_images.py --limit 500
"""

from __future__ import annotations

import argparse
import sys
import time
from pathlib import Path

import requests

import config

REPO_ROOT = Path(__file__).resolve().parent
EXTENSIONS = {".jpg", ".jpeg", ".png", ".gif", ".webp", ".bmp", ".tif", ".tiff", ".avif"}


def corpus_path(bucket: str) -> Path:
    base = Path(config.CORPUS_DIR)
    if not base.is_absolute():
        base = REPO_ROOT / base
    p = base / bucket
    p.mkdir(parents=True, exist_ok=True)
    return p


def make_session() -> requests.Session:
    s = requests.Session()
    s.headers.update({"User-Agent": config.WIKI_USER_AGENT})
    return s


def iter_images(session: requests.Session, limit: int | None):
    """Yield image metadata dicts from the allimages API, following continuation."""
    params = {
        "action": "query",
        "list": "allimages",
        "ailimit": "500",
        "aiprop": "url|size|mime",
        "aisort": "name",
        "format": "json",
        "formatversion": "2",
    }
    seen = 0
    while True:
        resp = session.get(config.WIKI_API, params=params, timeout=30)
        resp.raise_for_status()
        payload = resp.json()

        if "error" in payload:
            raise RuntimeError(f"API error: {payload['error']}")

        for item in payload.get("query", {}).get("allimages", []):
            yield item
            seen += 1
            if limit is not None and seen >= limit:
                return

        cont = payload.get("continue")
        if not cont:
            return
        params.update(cont)
        time.sleep(config.WIKI_REQUEST_DELAY)


def safe_name(title: str) -> str:
    name = title.replace("File:", "").replace("/", "_").replace("\\", "_")
    return name.strip() or "unnamed"


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--limit", type=int, default=config.WIKI_MAX_IMAGES,
                    help="max images to fetch (default from config.WIKI_MAX_IMAGES)")
    ap.add_argument("--bucket", default="sfw",
                    help="corpus subfolder to write into (default: sfw)")
    args = ap.parse_args()

    if "CHANGE_ME" in config.WIKI_USER_AGENT:
        print("Set config.WIKI_USER_AGENT to something with a real contact address first.",
              file=sys.stderr)
        return 1

    dest = corpus_path(args.bucket)
    session = make_session()

    print(f"api    : {config.WIKI_API}")
    print(f"dest   : {dest}")
    print(f"limit  : {args.limit}")
    print(f"delay  : {config.WIKI_REQUEST_DELAY}s between requests\n")

    got = skipped = failed = 0
    try:
        for item in iter_images(session, args.limit):
            url = item.get("url")
            title = item.get("title") or item.get("name") or ""
            if not url:
                continue

            name = safe_name(title)
            if Path(name).suffix.lower() not in EXTENSIONS:
                continue

            out = dest / name
            if out.exists() and out.stat().st_size > 0:
                skipped += 1
                continue

            try:
                r = session.get(url, timeout=60)
                r.raise_for_status()
                out.write_bytes(r.content)
                got += 1
                if got % 25 == 0:
                    print(f"  {got} downloaded ({skipped} already present)")
            except Exception as exc:
                failed += 1
                print(f"  ! {name}: {exc}", file=sys.stderr)

            time.sleep(config.WIKI_REQUEST_DELAY)

    except KeyboardInterrupt:
        print("\ninterrupted; partial corpus is still usable")
    except Exception as exc:
        print(f"\nfetch failed: {exc}", file=sys.stderr)
        print("If the API is blocked, you can also just copy files straight out of "
              "the wiki's images/ directory on the server.", file=sys.stderr)
        return 1

    print(f"\ndone. downloaded={got} skipped={skipped} failed={failed}")
    print(f"corpus now holds {len(list(dest.iterdir()))} files in {dest}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
