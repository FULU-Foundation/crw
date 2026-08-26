# imgguard

Local image safety classifier service. Wraps `OwenElliott/image-safety-classifier-l`
(28.5M param SwiftFormer, ONNX) behind a small HTTP API so a wiki upload handler
can get an accept/reject verdict without shipping images to a third party.

Three classes: **NSFL** (gore), **NSFW** (explicit/suggestive), **SFW**. Trained on
photos, drawings, Rule 34, screenshots, memes and AI-generated images, so it
handles illustrated and stylised content rather than photographs only.

The MediaWiki side lives in `extensions/ImgGuard`. This service knows nothing
about the wiki: it takes bytes, returns a verdict, and logs nothing.

## Quick start

```bash
./setup.sh          # venv + deps + model download + smoke test
./run.sh            # start the service on 127.0.0.1:8181
```

Tested on Linux Mint / Ubuntu. Needs Python 3.10+ and `python3-venv`.

For production use `systemd/imgguard.service` or `docker/Dockerfile` — `./run.sh`
in a terminal dies with the shell.

## The threshold

Everything tunable lives in `config.py`. The one you care about:

```python
SFW_MIN = 0.50   # accept only if P(SFW) >= this
```

This thresholds on **SFW confidence, not NSFW confidence**. "Model is unsure"
therefore behaves like "reject", which is the right failure direction for a
no-nudity-of-any-kind policy. Argmax would let ambiguous images through.

**0.50 is a measured value, not a placeholder.** It was backtested against the
full corpus of images already on consumerrights.wiki (no false positives) and
against a set of known-bad images (all rejected). Because the model emits a
three-class softmax, `P(SFW) >= 0.50` is arithmetically the same as "SFW won the
argmax" — but that equivalence is a fact about this threshold, not the reasoning
behind it. On this corpus the model turns out to be decisive rather than
marginal, which is why the extra strictness of a higher threshold buys nothing
the data says is needed.

Re-run `backtest.py` before changing it, and **whenever `MODEL_REPO` changes**.
The number is a property of this model on this corpus, not a constant.

`NSFW_MAX` / `NSFL_MAX` are independent hard ceilings, off by default (1.0).

## Format support

The wiki accepts a good deal more than JPEG and PNG. Every format it accepts
that can carry a picture and that the scanner can't read is a bypass, so
non-raster formats are converted to frames before classification. Whatever the
format, the file is judged on its **worst** frame.

| Format | Coverage | Backend |
|---|---|---|
| JPEG, PNG, BMP, TIFF, AVIF | full | Pillow |
| GIF, animated WebP, APNG | full, up to 8 frames sampled across the timeline | Pillow |
| SVG | full, rasterised at `SVG_RENDER_PX` | cairosvg, else `rsvg-convert`, else ImageMagick |
| PDF | up to `PDF_MAX_PAGES` pages **rendered** | PyMuPDF, else `pdftoppm`, else ImageMagick (page 1 only) |
| docx, xlsx, pptx, odt, ods, odp | embedded bitmaps only | stdlib `zipfile` |
| doc, xls, ppt (legacy OLE2) | none — reported `unsupported` | — |
| mp4, webm, mov, avi, mkv, flv, wmv | frame sampling, **off by default** | ffmpeg |

Things worth knowing about that table:

- **PDFs are rendered, not image-extracted.** Pulling out embedded bitmaps would
  miss anything drawn as vectors or set as text.
- **Office coverage is partial and honestly so.** Embedded bitmaps get scanned;
  vector shapes drawn in the document and EMF/WMF images do not. It catches the
  ordinary case — a picture pasted into a document — which is what actually
  turns up.
- **Video is capable but disabled** (`SCAN_VIDEO`). On consumerrights.wiki video
  uploads are sysop-only and sysops hold `imgguard-bypass`, so scanning them
  would burn CPU on files that are never checked. Enabling it needs *two*
  changes: `SCAN_VIDEO = True` here **and** the extensions added to
  `$wgImgGuardScanExtensions` on the wiki. Either alone does nothing.
- **Backends are probed by actually running them**, not by checking whether a
  binary exists. ImageMagick is the reason: `convert` is frequently installed
  but delegates SVG to librsvg, so it reports as available and then fails on
  every SVG. `GET /health` reports what genuinely works. A format with no
  backend returns `unsupported`, which is a different thing from a rejection and
  is reported as such.

Format detection is **content-based**. The `X-Filename` hint is consulted only
to disambiguate ZIP containers (`.docx` vs `.xlsx`) — a caller-supplied
extension is never evidence that something is safe.

SVG rasterisation refuses external resource loads in all three backends
(cairosvg with `unsafe=False`; the others get no base URI to resolve against).
An SVG that could pull in a remote bitmap would scan clean and then render as
something else entirely in the browser.

## API

```bash
curl -F "file=@photo.jpg" http://127.0.0.1:8181/classify
curl --data-binary @photo.jpg -H 'X-Filename: photo.jpg' http://127.0.0.1:8181/classify
```

```json
{
  "accepted": false,
  "verdict": "reject",
  "reason": "P(SFW)=0.0031 below threshold 0.50",
  "scores": {"NSFL": 0.0012, "NSFW": 0.9957, "SFW": 0.0031},
  "media_type": "pdf",
  "frames_checked": 4,
  "worst_source": "page 2",
  "elapsed_ms": 84.1,
  "model": "OwenElliott/image-safety-classifier-l",
  "sfw_min": 0.5
}
```

`verdict` is the machine-readable outcome and is what the MediaWiki extension
keys off:

| verdict | meaning |
|---|---|
| `accept` | model ran, passed |
| `reject` | model ran, failed the threshold |
| `unsupported` | format recognised, contents not inspectable. `accepted` reflects `REJECT_UNSUPPORTED`, which defaults to leaving the call to the caller |
| `undecodable` | corrupt, truncated, or matching no format we recognise |
| `oversize` | above `MAX_UPLOAD_MB`, refused before decoding |

Optional request headers: `X-Auth-Token` (if `AUTH_TOKEN` is set), `X-Filename`.

`GET /health` reports the loaded model, the active threshold and which converter
backends actually work. Interactive docs at `/docs`.

The service **does not log decisions** — that's the caller's job, per design. On
the wiki that means MediaWiki writes the log entry, so rejections are reviewable
at `Special:Log/imgguard` by someone with the right, and nothing sensitive lands
in a second place with a different retention policy.

## Backtesting

```bash
./venv-run.sh fetch_wiki_images.py      # pull wiki images -> corpus/sfw/
./venv-run.sh backtest.py               # score everything, sweep thresholds
```

Corpus layout:

| folder | meaning | counts as |
|---|---|---|
| `corpus/sfw/` | wiki images, assumed good | rejections = **false positives** |
| `corpus/nsfw/` | known-bad, you supply | acceptances = **false negatives** |
| `corpus/unlabeled/` | anything else | scored, excluded from rates |

Output is a sweep table plus `backtest_scores.csv` sorted worst-first. The FN
column stays meaningless until you put something in `corpus/nsfw/`.

Before running the fetcher, set `WIKI_USER_AGENT` in `config.py` to something
with a real contact address — it refuses to run while it still says `CHANGE_ME`.
It rate-limits to ~3 req/s and skips files already on disk, so re-running tops
up incrementally. If you have shell access to the wiki host, copying straight
out of the `images/` directory is faster and kinder to the API.

## Behaviour worth knowing

- **Animated images** are sampled across up to 8 frames and judged on the worst
  one. A safe first frame is otherwise a trivial bypass.
- **Undecodable files** are rejected, not errored. A malformed image can't slip
  past by crashing the classifier. Toggle with `REJECT_UNDECODABLE`.
- **"Unrecognised" and "unsupported" are deliberately different verdicts.**
  Unsupported means we identified the format and cannot, or by policy will
  not, look inside it - a known quantity the wiki can set a policy for.
  Unrecognised means the bytes match nothing we can decode, so we can say
  nothing at all about the content; that is rejected rather than inheriting
  the allow-by-default meant for legacy Office files.
- **Oversize payloads** are rejected before decode (`MAX_UPLOAD_MB`), checked
  against `Content-Length` first so an oversize body isn't buffered.
- **Decompression bombs** are capped by total pixels (`MAX_PIXELS`), and ZIP
  containers additionally by entry count and uncompressed size.
- **External converters** all run with a hard timeout (`SUBPROCESS_TIMEOUT`) and
  no inherited stdin. A hung converter must not hold an upload request open.
- **Total frames per file** are capped by `MAX_FRAMES_PER_FILE` across every
  format, bounding the worst case of a 200-page PDF.
- **Transparency** is flattened onto white, not black.
- `ORT_THREADS = 2` by default so an upload burst can't eat every core on a box
  that's also serving the wiki.

## Configuration by environment

Every setting in `config.py` can be overridden by an `IMGGUARD_`-prefixed
environment variable (`IMGGUARD_SFW_MIN`, `IMGGUARD_PORT`, `IMGGUARD_HOST`, …).
That exists so the Docker deployment can set a couple of values without a bind
mount. On a bare-metal install, ignore it and edit `config.py`.

Handy for testing: `IMGGUARD_SFW_MIN=1.1` makes every image fail and
`IMGGUARD_SFW_MIN=0.0` makes every image pass, which lets you exercise both
branches of the upload flow with completely innocuous test files.

## Swapping models

Change `MODEL_REPO` in `config.py` and re-run `./setup.sh`. The loader lists the
repo and picks whatever `.onnx` it publishes rather than guessing a filename,
and reads the input tensor name from the graph. If you move to a model with
different classes, update `CLASS_NAMES` to match its documented output order —
**and re-run the backtest**, because `SFW_MIN` does not transfer between models.

Sibling models: `-xs` (3.5M), `-s` (6.1M), `-m` (12.1M), `-l` (28.5M, default).

## Calibrate before trusting it

Published accuracy figures for every model in this space are self-reported on
proprietary test sets and are not comparable to each other. The only number that
means anything is the false-positive rate on *your* images. Run the backtest,
look at the lowest-scoring clean images it prints, and pick a threshold from
that rather than from this README.
