#!/usr/bin/env python3
"""
Score every image in the corpus and report how each candidate threshold
would have behaved.

Corpus layout:
  corpus/sfw/        assumed-good (wiki images). Rejections here are FALSE POSITIVES.
  corpus/nsfw/       known-bad, added by you. Acceptances here are FALSE NEGATIVES.
  corpus/unlabeled/  scored and written to CSV, excluded from error rates.

Outputs:
  backtest_scores.csv    per-image scores, sorted worst-first
  a threshold sweep table on stdout

  python backtest.py
  python backtest.py --sweep 0.85 0.90 0.95
"""

from __future__ import annotations

import argparse
import csv
import sys
import time
from pathlib import Path

import config
from classifier import get_classifier

REPO_ROOT = Path(__file__).resolve().parent
EXTENSIONS = {".jpg", ".jpeg", ".png", ".gif", ".webp", ".bmp", ".tif", ".tiff", ".avif"}


def corpus_root() -> Path:
    base = Path(config.CORPUS_DIR)
    return base if base.is_absolute() else REPO_ROOT / base


def collect(bucket: str) -> list[Path]:
    d = corpus_root() / bucket
    if not d.is_dir():
        return []
    return sorted(p for p in d.rglob("*") if p.is_file() and p.suffix.lower() in EXTENSIONS)


def score_all(paths: list[Path], label: str, clf) -> list[dict]:
    rows = []
    total = len(paths)
    for i, p in enumerate(paths, 1):
        t0 = time.perf_counter()
        try:
            scores, frames = clf.score_bytes(p.read_bytes())
            err = ""
        except Exception as exc:
            scores, frames, err = None, 0, str(exc)

        ms = (time.perf_counter() - t0) * 1000
        rows.append({
            "label": label,
            "path": str(p.relative_to(corpus_root())),
            "sfw": scores.get("SFW", 0.0) if scores else 0.0,
            "nsfw": scores.get("NSFW", 0.0) if scores else 0.0,
            "nsfl": scores.get("NSFL", 0.0) if scores else 0.0,
            "frames": frames,
            "ms": round(ms, 1),
            "error": err,
        })
        if i % 50 == 0 or i == total:
            print(f"  [{label}] {i}/{total}")
    return rows


def sweep_table(rows: list[dict], thresholds: list[float]) -> None:
    sfw_rows = [r for r in rows if r["label"] == "sfw"]
    nsfw_rows = [r for r in rows if r["label"] == "nsfw"]

    print()
    print(f"{'thresh':>8} | {'FP':>6} {'FP rate':>9} | {'FN':>6} {'FN rate':>9}")
    print("-" * 52)

    for t in thresholds:
        fp = sum(1 for r in sfw_rows if r["sfw"] < t)
        fn = sum(1 for r in nsfw_rows if r["sfw"] >= t)
        fp_rate = f"{fp / len(sfw_rows) * 100:.2f}%" if sfw_rows else "n/a"
        fn_rate = f"{fn / len(nsfw_rows) * 100:.2f}%" if nsfw_rows else "n/a"
        marker = "  <- config.SFW_MIN" if abs(t - config.SFW_MIN) < 1e-9 else ""
        print(f"{t:>8.2f} | {fp:>6} {fp_rate:>9} | {fn:>6} {fn_rate:>9}{marker}")

    print("-" * 52)
    print(f"clean corpus: {len(sfw_rows)} images   known-bad corpus: {len(nsfw_rows)} images")
    if not nsfw_rows:
        print("\nNo images in corpus/nsfw/ - FN column is meaningless until you add some.")


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--sweep", type=float, nargs="+", default=config.BACKTEST_SWEEP)
    ap.add_argument("--csv", default="backtest_scores.csv")
    ap.add_argument("--show-worst", type=int, default=15,
                    help="print the N lowest-scoring clean images")
    args = ap.parse_args()

    buckets = {b: collect(b) for b in ("sfw", "nsfw", "unlabeled")}
    if not any(buckets.values()):
        print(f"No images found under {corpus_root()}.", file=sys.stderr)
        print("Run fetch_wiki_images.py first, or drop files in yourself.", file=sys.stderr)
        return 1

    print(f"model: {config.MODEL_REPO}")
    for b, paths in buckets.items():
        print(f"  corpus/{b}: {len(paths)} images")
    print()

    clf = get_classifier(verbose=True)

    rows: list[dict] = []
    for bucket, paths in buckets.items():
        if paths:
            rows.extend(score_all(paths, bucket, clf))

    rows.sort(key=lambda r: r["sfw"])

    out = REPO_ROOT / args.csv
    with out.open("w", newline="") as fh:
        w = csv.DictWriter(fh, fieldnames=list(rows[0].keys()))
        w.writeheader()
        w.writerows(rows)

    sweep_table(rows, sorted(args.sweep))

    scored = [r for r in rows if not r["error"]]
    if scored:
        avg = sum(r["ms"] for r in scored) / len(scored)
        print(f"\nmean inference: {avg:.1f} ms/image over {len(scored)} images")

    errors = [r for r in rows if r["error"]]
    if errors:
        print(f"{len(errors)} images failed to decode (these count as rejections in prod)")

    worst = [r for r in rows if r["label"] == "sfw"][: args.show_worst]
    if worst:
        print(f"\nlowest-scoring clean images - eyeball these to sanity-check your threshold:")
        for r in worst:
            print(f"  P(SFW)={r['sfw']:.4f}  {r['path']}")

    print(f"\nfull scores written to {out}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
