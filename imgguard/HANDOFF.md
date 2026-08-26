# Handoff notes — read this first

Context dump for continuing work on **imgguard** in a new conversation. The
person you're talking to is the admin/sole moderator of a MediaWiki instance and
knows what he's doing technically — no need to over-explain basics, just pick up
where this left off.

## What this project is

A local, self-hosted image safety classifier that gets called from a wiki's
upload flow to auto-reject nudity — including in art, illustration, anime, and
AI-generated images, not just photos. Runs as a FastAPI service on `:8181`; the
wiki (PHP/MediaWiki) POSTs the upload to `/classify` and gets back
accept/reject.

**Policy intent, explicitly stated by the person:** zero tolerance for nudity of
any kind on this wiki. It's a consumer-rights reference site; there's no
legitimate use case for nudity in its content, and the rare false positive (e.g.
a screenshot showing a lingerie retailer's product page as evidence in a
complaint) is handled manually — the moderator (him) works it out directly with
the user. **Do not reintroduce a review-queue/quarantine design** — that was
proposed early on and explicitly rejected in favour of hard reject + manual
escalation. Respect that decision unless he says otherwise.

## Model

`OwenElliott/image-safety-classifier-l` — 28.5M param SwiftFormer, ONNX,
3-class output (NSFL / NSFW / SFW). Chosen because its training set explicitly
includes drawings, Rule 34, screenshots, memes and AI-generated images, not just
photos — a photo-only detector (NudeNet etc.) was ruled out early on for exactly
this reason.

## The threshold is settled — do not "fix" it

`config.py :: SFW_MIN = 0.50`.

An earlier version of this file warned that 0.50 was a temporary permissive
value for testing and must be raised before shipping. **That warning was wrong
and has been removed.** The person has since backtested 0.50 against the full
corpus of images already uploaded to the wiki (no false positives) and against a
set of known-bad images (all rejected). It is the validated production value.

Because the model emits a three-class softmax, `P(SFW) >= 0.50` is arithmetically
identical to argmax. That is a property of this threshold, not the argument for
it — the point is that on this corpus the model is decisive rather than
marginal, so a higher threshold would buy strictness the data says isn't needed
while costing false positives.

If you are picking this up to "finish the integration": leave `SFW_MIN` alone.
It only needs re-deriving if `MODEL_REPO` changes, in which case run
`backtest.py` and pick from the sweep.

## Current state

**Scanner — done.** Model wrapper, FastAPI service, `setup.sh`/`run.sh`, the
MediaWiki `allimages` scraper, the threshold-sweep backtest, and a converter
pipeline (`converters.py`) covering SVG, PDF, Office containers and video. All
tested end-to-end against a stub ONNX model with the same I/O shape, including
worst-frame selection across GIF frames, PDF pages and images embedded in a
docx.

**MediaWiki extension — done.** `extensions/ImgGuard`, MW 1.46. Hooks
`UploadVerifyFile` (scan, cache by SHA-1, don't block), `UploadStashFile` and
`UploadVerifyUpload` (block). Exposes `imgguard_*` variables to AbuseFilter.
Restricted log at `Special:Log/imgguard`. `extension.json` validates against
MediaWiki 1.46's own schema; all PHP lints clean under 8.3.

**Not yet done:**

- The real HuggingFace weights have never been downloaded and run — the sandbox
  that built this couldn't reach HuggingFace, so everything was exercised
  against a stub with matching I/O shape. First real run happens on `./setup.sh`.
  If `image-safety-classifier-l` turns out not to publish `.onnx` weights, drop
  `MODEL_REPO` to `-m` or `-s` (same family, documented to have ONNX).
- Never run against a live MediaWiki. `TESTING.md` has an offline local-stack
  procedure covering every branch of the flow.
- Copy-uploads (`$wgAllowCopyUploads = true`) run through `UploadFromUrlJob`,
  where `UploadVerifyFile` has no `$user` argument and falls back to
  `RequestContext`. The session is exported into the job so it should resolve,
  but this is the least-verified path. Failure direction is safe (stricter, not
  permissive).

## Design decisions worth preserving

- **Threshold logic is `P(SFW) >= SFW_MIN`**, not argmax and not P(NSFW)-based.
  "Model is uncertain" fails toward reject, which is correct for a strict policy.
- **The scan happens at `UploadVerifyFile` but the block happens at
  `UploadVerifyUpload`.** This looks like an odd split and is the single most
  important thing not to "simplify". AbuseFilter does its upload filtering in
  `UploadVerifyUpload`; MediaWiki runs same-hook handlers in registration order.
  Blocking at the earlier hook means AbuseFilter never runs and **no filter
  consequence — block, warn, blockautopromote, degroup, tag, throttle — can ever
  fire on an NSFW upload.** ImgGuard must also load *after* AbuseFilter in
  LocalSettings for the same reason; it detects the mistake and warns in the log.
- **`UploadStashFile` is hooked too, and not only for VisualEditor.**
  `SpecialUpload::showRecoverableUploadError()` calls `tryStashFile()`, so
  without it a rejected image gets written into `images/temp` on its way to
  displaying the rejection message.
- **The scanner is the single source of truth for accept/reject.** MediaWiki
  deliberately has no threshold of its own — two places applying a threshold is
  two places to drift. Filters can be *stricter* via `imgguard_score_sfw`, which
  is the intended way to tighten without touching the service.
- **`unsupported` is a distinct verdict, not a rejection.** "We couldn't look
  inside it" and "we looked and it's explicit" are different events and the log
  and AbuseFilter both need to tell them apart.
- **Backends are probed by running them.** ImageMagick reports SVG support it
  frequently doesn't have. A health endpoint that claims coverage it lacks hides
  a silent bypass.
- **Animated images** are judged on the worst of up to 8 sampled frames.
- **Undecodable files reject rather than throw** — a malformed upload can't slip
  through by crashing the classifier.
- **The service logs nothing itself.** Logging is MediaWiki's job, per his
  instruction, so it lands in one place with one retention policy.
- **The log is restricted** behind `imgguard-viewlog`. A public version would be
  a browsable list of accusations against named users, and for a false positive,
  a permanent public record of one. The user-facing message omits scores and
  thresholds for the same reason: that detail is a tuning aid for anyone trying
  to get past it.
- `config.py` is the single file meant for hand-editing; everything else reads
  from it. Env overrides exist only so the container can set a few values.

## Known gaps, stated rather than hidden

- **Legacy `.doc`/`.xls`/`.ppt` cannot be inspected.** Reported `unsupported`.
- **Office coverage is partial**: embedded bitmaps yes, vector shapes and
  EMF/WMF no.
- **`$wgMaxUploadSize` is 200 MB; the scanner caps at 32 MB.** Files in between
  are never classified. Governed by `$wgImgGuardOversizeAction`, which defaults
  to `allow` — that is a real hole and is called out in the PR.
- **A rejected file still touches disk**, briefly, in PHP's `upload_tmp_dir`,
  before any of our code runs. Unavoidable. What's guaranteed is that it never
  reaches `images/` or the upload stash.
- **`$wgUseInstantCommons = true`** means Commons images are embedded without
  passing through any upload hook at all.

## Everything else

`README.md` is the reference doc for the service, `extensions/ImgGuard/README.md`
for the wiki side, `TESTING.md` for the offline test procedure. This file is
just "what happened before you got here".
