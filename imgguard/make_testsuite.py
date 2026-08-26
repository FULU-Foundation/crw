#!/usr/bin/env python3
"""
Build an upload test suite from one known-good and one known-bad image.

    ./venv-run.sh make_testsuite.py --sfw sfw.png --nsfw nsfw.jpg
    ./venv-run.sh make_testsuite.py --sfw sfw.png --nsfw nsfw.jpg --scan

Takes your two source images and produces every format consumerrights.wiki
accepts, in clean and dirty variants, plus the awkward cases that a naive
scanner gets wrong: a bad frame buried late in an animation, a bad page deep in
a PDF, a bad picture inside a document, content that lies about its extension.

Files are sorted into directories by what should happen to them, so you can
upload a whole directory and know in advance what you're looking for:

    01-expect-accept/       should upload normally
    02-expect-reject/       should be blocked
    03-expect-reject-hard/  should be blocked, and would fool a first-frame-only scanner
    04-known-gaps/          WILL be accepted; documents a real limit, not a bug to hunt
    05-edge-cases/          malformed, mislabelled, uncheckable
    99-extra-formats/       scanner handles these, the wiki doesn't accept them (--extras)

--scan posts every file straight at the classifier and prints expected versus
actual, which checks the whole pipeline in about a minute instead of forty
manual uploads. Do that first; then hand-upload a handful through the wiki to
confirm the MediaWiki side.

NOTE ON THE OUTPUT: this suite contains real explicit imagery derived from the
image you supply. It is a moderation test corpus. Keep it out of shared drives
and delete it when you are done.
"""

from __future__ import annotations

import argparse
import io
import os
import shutil
import struct
import sys
import zipfile
from dataclasses import dataclass, field
from pathlib import Path

from PIL import Image

# Expectation keywords used in directory names and the manifest.
ACCEPT = "accept"
REJECT = "reject"
GAP = "gap"
EDGE = "edge"

DIRS = {
    ACCEPT: "01-expect-accept",
    REJECT: "02-expect-reject",
    "reject_hard": "03-expect-reject-hard",
    GAP: "04-known-gaps",
    EDGE: "05-edge-cases",
    "extra": "99-extra-formats",
}


@dataclass
class Case:
    name: str
    bucket: str
    note: str
    expect: str
    data: bytes = b""
    path: Path | None = None
    # Some files are expected to be stopped by MediaWiki's own checks before
    # ImgGuard is ever consulted. Both outcomes are a pass; the manifest says so.
    mw_may_block: bool = False


CASES: list[Case] = []


def add(name, bucket, expect, note, data, mw_may_block=False):
    CASES.append(Case(name, bucket, note, expect, data, mw_may_block=mw_may_block))


# ---------------------------------------------------------------------------
# Encoding helpers
# ---------------------------------------------------------------------------

def enc(img: Image.Image, fmt: str, **kw) -> bytes:
    buf = io.BytesIO()
    out = img
    if fmt in ("JPEG", "PDF") and img.mode not in ("RGB", "L"):
        out = img.convert("RGB")
    out.save(buf, format=fmt, **kw)
    return buf.getvalue()


def vary(img: Image.Image, i: int) -> Image.Image:
    """
    Stamp a small per-frame marker so consecutive frames are never byte-identical.

    Without this the test suite lies to you. PIL's GIF and WebP encoders drop
    frames identical to their predecessor and just extend the previous frame's
    duration, so a nominally 60-frame animation is written as 3 frames and a
    test aimed at frame-sampling behaviour silently stops testing it. The patch
    is 10x10 in a corner - far too small to shift a classifier verdict, big
    enough to defeat the de-duplication.
    """
    out = img.copy()
    shade = (i * 37) % 256
    out.paste((shade, (shade * 3) % 256, (shade * 7) % 256), (0, 0, 10, 10))
    return out


def anim(frames: list[Image.Image], fmt: str, duration=120) -> bytes:
    frames = [vary(f, i) for i, f in enumerate(frames)]
    buf = io.BytesIO()
    head, rest = frames[0], frames[1:]
    if fmt == "GIF":
        head.save(buf, format="GIF", save_all=True, append_images=rest,
                  duration=duration, loop=0)
    elif fmt == "WEBP":
        head.save(buf, format="WEBP", save_all=True, append_images=rest,
                  duration=duration, loop=0)
    elif fmt == "PNG":  # APNG
        head.save(buf, format="PNG", save_all=True, append_images=rest,
                  duration=duration, loop=0)
    return buf.getvalue()


def pdf(pages: list[Image.Image]) -> bytes:
    buf = io.BytesIO()
    pages = [p.convert("RGB") for p in pages]
    pages[0].save(buf, format="PDF", save_all=True, append_images=pages[1:])
    return buf.getvalue()


# ---------------------------------------------------------------------------
# Office containers
#
# These are built to be structurally convincing enough that MediaWiki's MIME
# detection accepts them, because a file rejected at the MIME check never
# reaches ImgGuard and tells you nothing about the scanner.
# ---------------------------------------------------------------------------

OOXML_TYPES = {
    "docx": ("word", "application/vnd.openxmlformats-officedocument.wordprocessingml.document"),
    "xlsx": ("xl", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"),
    "pptx": ("ppt", "application/vnd.openxmlformats-officedocument.presentationml.presentation"),
}

ODF_TYPES = {
    "odt": "application/vnd.oasis.opendocument.text",
    "ods": "application/vnd.oasis.opendocument.spreadsheet",
    "odp": "application/vnd.oasis.opendocument.presentation",
}


def ooxml(kind: str, images: list[tuple[str, bytes]]) -> bytes:
    """
    Build an OOXML container that MIME detection will actually recognise.

    The entry order and the presence of docProps are not cosmetic. libmagic's
    msooxml rule navigates the archive by arithmetic - it reads the first local
    header's compressed size to jump to the second entry, then inspects the
    third entry's name for `word/`, `xl/` or `ppt/`. A minimal three-entry ZIP
    can fall through that rule and get matched by something else entirely
    (older versions land on application/epub+zip), and CRW sets
    $wgMimeDetectorCommand = 'file -bi', so whatever the container's `file`
    binary decides is final. So: canonical ordering, docProps present, and the
    media last, where its size cannot push the small XML parts out of the
    window libmagic searches.
    """
    root, ctype = OOXML_TYPES[kind]
    main = {"word": "document.xml", "xl": "workbook.xml", "ppt": "presentation.xml"}[root]

    buf = io.BytesIO()
    with zipfile.ZipFile(buf, "w", zipfile.ZIP_DEFLATED) as z:
        z.writestr("[Content_Types].xml",
                   '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\r\n'
                   '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                   '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                   '<Default Extension="xml" ContentType="application/xml"/>'
                   '<Default Extension="png" ContentType="image/png"/>'
                   '<Default Extension="jpeg" ContentType="image/jpeg"/>'
                   f'<Override PartName="/{root}/{main}" ContentType="{ctype}"/>'
                   '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
                   '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
                   '</Types>')
        z.writestr("_rels/.rels",
                   '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\r\n'
                   '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                   f'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="{root}/{main}"/>'
                   '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
                   '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
                   '</Relationships>')
        # Third entry must carry the root prefix - this is what libmagic looks at.
        z.writestr(f"{root}/{main}", _ooxml_main(root))
        rels = "".join(
            f'<Relationship Id="rId{i + 10}" '
            'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" '
            f'Target="media/{name}"/>' for i, (name, _) in enumerate(images))
        z.writestr(f"{root}/_rels/{main}.rels",
                   '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\r\n'
                   '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                   f'{rels}</Relationships>')
        z.writestr("docProps/core.xml",
                   '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\r\n'
                   '<cp:coreProperties '
                   'xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
                   'xmlns:dc="http://purl.org/dc/elements/1.1/">'
                   '<dc:title>imgguard test fixture</dc:title></cp:coreProperties>')
        z.writestr("docProps/app.xml",
                   '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\r\n'
                   '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">'
                   '<Application>imgguard-testsuite</Application></Properties>')
        # Media last: large parts must not crowd out the small XML entries that
        # detection reads from the front of the archive.
        for name, blob in images:
            z.writestr(f"{root}/media/{name}", blob)
    return buf.getvalue()


def _ooxml_main(root: str) -> str:
    if root == "word":
        return ('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\r\n'
                '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
                '<w:body><w:p><w:r><w:t>imgguard test fixture</w:t></w:r></w:p></w:body></w:document>')
    if root == "xl":
        return ('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\r\n'
                '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                '<sheets><sheet name="Sheet1" sheetId="1" r:id="rId1" '
                'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/></sheets></workbook>')
    return ('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\r\n'
            '<p:presentation xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"/>')


def odf(kind: str, images: list[tuple[str, bytes]]) -> bytes:
    """
    ODF requires 'mimetype' first and stored uncompressed - detection reads its
    contents straight out of the archive at a fixed offset, so compressing it or
    moving it makes the file unidentifiable. The remaining parts are here
    because a one-entry ODF is unusual enough that some magic rules skip it.
    """
    buf = io.BytesIO()
    with zipfile.ZipFile(buf, "w", zipfile.ZIP_DEFLATED) as z:
        info = zipfile.ZipInfo("mimetype")
        info.compress_type = zipfile.ZIP_STORED
        z.writestr(info, ODF_TYPES[kind])

        entries = "".join(
            f'<manifest:file-entry manifest:full-path="Pictures/{n}" '
            f'manifest:media-type="image/png"/>' for n, _ in images)
        z.writestr("META-INF/manifest.xml",
                   '<?xml version="1.0" encoding="UTF-8"?>\n'
                   '<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0" '
                   'manifest:version="1.2">'
                   f'<manifest:file-entry manifest:full-path="/" manifest:media-type="{ODF_TYPES[kind]}"/>'
                   '<manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/>'
                   '<manifest:file-entry manifest:full-path="styles.xml" manifest:media-type="text/xml"/>'
                   '<manifest:file-entry manifest:full-path="meta.xml" manifest:media-type="text/xml"/>'
                   f'{entries}</manifest:manifest>')
        z.writestr("content.xml",
                   '<?xml version="1.0" encoding="UTF-8"?>\n'
                   '<office:document-content '
                   'xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
                   'xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" '
                   'office:version="1.2"><office:body><office:text>'
                   '<text:p>imgguard test fixture</text:p>'
                   '</office:text></office:body></office:document-content>')
        z.writestr("styles.xml",
                   '<?xml version="1.0" encoding="UTF-8"?>\n'
                   '<office:document-styles '
                   'xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
                   'office:version="1.2"/>')
        z.writestr("meta.xml",
                   '<?xml version="1.0" encoding="UTF-8"?>\n'
                   '<office:document-meta '
                   'xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
                   'office:version="1.2"/>')
        for name, blob in images:
            z.writestr(f"Pictures/{name}", blob)
    return buf.getvalue()


def ole2_stub() -> bytes:
    """A legacy .doc header. Not a real document - just enough to be detected as
    OLE2 compound storage, which is the branch we want to exercise."""
    header = b"\xd0\xcf\x11\xe0\xa1\xb1\x1a\xe1" + b"\x00" * 16
    header += struct.pack("<HHHH", 0x003E, 0x0003, 0xFFFE, 0x0009)
    return header + b"\x00" * (4096 - len(header))


def svg_with_raster(blob: bytes, mime="image/png") -> bytes:
    import base64
    b64 = base64.b64encode(blob).decode()
    return (
        '<?xml version="1.0" encoding="UTF-8"?>\n'
        '<svg xmlns="http://www.w3.org/2000/svg" '
        'xmlns:xlink="http://www.w3.org/1999/xlink" '
        'width="640" height="480" viewBox="0 0 640 480">\n'
        '  <rect width="640" height="480" fill="#ffffff"/>\n'
        f'  <image x="0" y="0" width="640" height="480" '
        f'xlink:href="data:{mime};base64,{b64}"/>\n'
        '</svg>\n'
    ).encode()


# ---------------------------------------------------------------------------
# Suite construction
# ---------------------------------------------------------------------------

def build(sfw: Image.Image, nsfw: Image.Image, include_large: bool, extras: bool):
    sfw_png = enc(sfw, "PNG")
    nsfw_png = enc(nsfw, "PNG")
    nsfw_jpg = enc(nsfw, "JPEG", quality=92)

    # -- plain rasters, both polarities -------------------------------------
    for label, img in (("sfw", sfw), ("nsfw", nsfw)):
        expect = ACCEPT if label == "sfw" else REJECT
        add(f"{label}.png", expect, expect, "plain PNG", enc(img, "PNG"))
        add(f"{label}.jpg", expect, expect, "plain JPEG", enc(img, "JPEG", quality=92))
        add(f"{label}.jpeg", expect, expect, "plain JPEG, .jpeg extension",
            enc(img, "JPEG", quality=92))
        add(f"{label}.webp", expect, expect, "plain WebP", enc(img, "WEBP", quality=90))
        add(f"{label}-static.gif", expect, expect, "single-frame GIF",
            enc(img.convert("P", palette=Image.ADAPTIVE), "GIF"))

    # -- transparency: must flatten to white, not black ---------------------
    rgba = nsfw.convert("RGBA")
    add("nsfw-alpha.png", REJECT, REJECT,
        "RGBA with an alpha channel; flattening to black instead of white would "
        "mask the content", enc(rgba, "PNG"))

    # -- animations ---------------------------------------------------------
    s = sfw.convert("RGB").resize((320, 240))
    n = nsfw.convert("RGB").resize((320, 240))

    add("sfw-animated.gif", ACCEPT, ACCEPT, "8 clean frames",
        anim([s] * 8, "GIF"))
    add("nsfw-alternating.gif", "reject_hard", REJECT,
        "alternating clean/explicit frames", anim([s, n] * 4, "GIF"))
    add("nsfw-frame7of8.gif", "reject_hard", REJECT,
        "clean first frame, explicit at frame 7 - defeats a first-frame-only scanner",
        anim([s] * 7 + [n], "GIF"))
    add("nsfw-frame1of12.gif", "reject_hard", REJECT,
        "explicit first frame only, clean afterwards",
        anim([n] + [s] * 11, "GIF"))
    add("nsfw-alternating.webp", "reject_hard", REJECT,
        "animated WebP, alternating frames", anim([s, n] * 4, "WEBP"))
    add("nsfw-frame5of8.png", "reject_hard", REJECT,
        "APNG with an explicit frame in the middle",
        anim([s] * 4 + [n] + [s] * 3, "PNG"))

    # A single bad frame late in a long animation. Sampling is evenly spaced
    # and capped, so this one is expected to slip through. It is here to make
    # the limit visible and measurable rather than theoretical.
    long_frames = [s] * 60
    long_frames[55] = n
    add("gap-nsfw-frame55of60.gif", GAP, ACCEPT,
        "one explicit frame at 55/60. Sampling takes 8 evenly spaced frames "
        "(0, 8, 17, 25, 34, 42, 51, 59) and none is 55. EXPECTED TO BE ACCEPTED "
        "- this measures the sampling limit rather than a bug. Raise "
        "ANIMATION_MAX_FRAMES if long animations are a realistic vector; the "
        "cost is linear in scan time",
        anim(long_frames, "GIF", duration=60))

    # -- PDF ----------------------------------------------------------------
    clean_page = sfw.convert("RGB")
    dirty_page = nsfw.convert("RGB")
    add("sfw.pdf", ACCEPT, ACCEPT, "3 clean pages", pdf([clean_page] * 3))
    add("nsfw-page1.pdf", REJECT, REJECT, "explicit on page 1", pdf([dirty_page]))
    add("nsfw-page3of5.pdf", "reject_hard", REJECT,
        "clean cover, explicit on page 3 - defeats a cover-page-only scanner",
        pdf([clean_page, clean_page, dirty_page, clean_page, clean_page]))

    deep = [clean_page] * 12
    deep[11] = dirty_page
    add("gap-nsfw-page12of12.pdf", GAP, ACCEPT,
        "explicit on page 12. PDF_MAX_PAGES is 8, so pages 9+ are never "
        "rendered. EXPECTED TO BE ACCEPTED - raise PDF_MAX_PAGES if this matters",
        pdf(deep))

    # -- Office documents ---------------------------------------------------
    for kind in ("docx", "xlsx"):
        add(f"sfw.{kind}", ACCEPT, ACCEPT, f"{kind} with a clean embedded image",
            ooxml(kind, [("image1.png", sfw_png)]))
        add(f"nsfw.{kind}", REJECT, REJECT, f"{kind} with an explicit embedded image",
            ooxml(kind, [("image1.png", nsfw_png)]))
    add("nsfw-second-image.docx", "reject_hard", REJECT,
        "docx whose first embedded image is clean and second is not",
        ooxml("docx", [("image1.png", sfw_png), ("image2.jpeg", nsfw_jpg)]))

    for kind in ("odt", "ods"):
        add(f"sfw.{kind}", ACCEPT, ACCEPT, f"{kind} with a clean embedded image",
            odf(kind, [("10000.png", sfw_png)]))
        add(f"nsfw.{kind}", REJECT, REJECT, f"{kind} with an explicit embedded image",
            odf(kind, [("10000.png", nsfw_png)]))

    add("gap-nsfw-no-media.docx", GAP, ACCEPT,
        "docx with no extractable bitmap. Office coverage is embedded images "
        "only; anything drawn as vector shapes is invisible to the scanner. "
        "EXPECTED TO BE ACCEPTED as 'unsupported'",
        ooxml("docx", []))

    # -- SVG ----------------------------------------------------------------
    add("sfw.svg", ACCEPT, ACCEPT, "SVG wrapping a clean raster",
        svg_with_raster(sfw_png))
    add("nsfw.svg", REJECT, REJECT,
        "SVG wrapping an explicit raster - must be rasterised, not treated as text",
        svg_with_raster(nsfw_png))
    add("sfw-vector-only.svg", ACCEPT, ACCEPT, "pure vector shapes, no raster",
        b'<?xml version="1.0"?>\n<svg xmlns="http://www.w3.org/2000/svg" '
        b'width="400" height="300"><rect width="400" height="300" fill="#4488cc"/>'
        b'<circle cx="200" cy="150" r="80" fill="#ffcc00"/></svg>\n')

    # Security probe rather than a content test: the renderer must not fetch
    # this. If it did, an SVG could scan clean and then display anything at all.
    add("edge-svg-remote-ref.svg", EDGE, ACCEPT,
        "references a remote bitmap. The scanner must NOT fetch it - all three "
        "SVG backends refuse external resources. Expected to rasterise to a "
        "blank/placeholder image and be accepted. Check the scanner made no "
        "outbound connection",
        b'<?xml version="1.0"?>\n<svg xmlns="http://www.w3.org/2000/svg" '
        b'xmlns:xlink="http://www.w3.org/1999/xlink" width="400" height="300">'
        b'<image width="400" height="300" '
        b'xlink:href="http://127.0.0.1:9/should-never-be-fetched.png"/></svg>\n',
        mw_may_block=True)

    # -- edge cases ---------------------------------------------------------
    add("edge-truncated.png", EDGE, REJECT,
        "PNG cut off mid-file. Must be rejected as undecodable, not crash the "
        "scanner or slip through", nsfw_png[:len(nsfw_png) // 3])
    add("edge-empty.png", EDGE, REJECT, "zero bytes", b"")
    add("edge-nsfw-mislabelled.png", EDGE, REJECT,
        "explicit JPEG bytes with a .png extension. Format detection is "
        "content-based, so the scanner should still catch it. MediaWiki may "
        "reject it first on the MIME/extension mismatch - either block is a pass",
        nsfw_jpg, mw_may_block=True)
    add("edge-legacy.doc", EDGE, ACCEPT,
        "OLE2 compound file. Cannot be inspected; expected verdict 'unsupported', "
        "allowed by default under $wgImgGuardUnsupportedAction",
        ole2_stub(), mw_may_block=True)
    add("edge-not-an-image.png", EDGE, REJECT,
        "plain text with an image extension", b"this is definitely not an image\n" * 50,
        mw_may_block=True)

    if include_large:
        # Deliberately incompressible, so the file is genuinely large rather
        # than a small file claiming to be big.
        big = Image.effect_noise((3400, 3400), 128).convert("RGB")
        add("edge-oversize.png", EDGE, ACCEPT,
            "above $wgImgGuardMaxFileSize (32 MB). Never sent to the scanner; "
            "verdict 'oversize', allowed by default. This is the documented hole "
            "between the wiki's 200 MB limit and the scanner's 32 MB one",
            enc(big, "PNG"))

    # -- formats the scanner handles but this wiki does not accept ----------
    if extras:
        for label, img in (("sfw", sfw), ("nsfw", nsfw)):
            add(f"{label}.bmp", "extra", ACCEPT if label == "sfw" else REJECT,
                "BMP - not in $wgFileExtensions, so MediaWiki blocks it first",
                enc(img, "BMP"), mw_may_block=True)
            add(f"{label}.tiff", "extra", ACCEPT if label == "sfw" else REJECT,
                "TIFF - not in $wgFileExtensions", enc(img, "TIFF"), mw_may_block=True)
            try:
                add(f"{label}.avif", "extra", ACCEPT if label == "sfw" else REJECT,
                    "AVIF - not in $wgFileExtensions", enc(img, "AVIF"),
                    mw_may_block=True)
            except Exception:
                pass
        add("nsfw.pptx", "extra", REJECT,
            "pptx - not in $wgFileExtensions", ooxml("pptx", [("image1.png", nsfw_png)]),
            mw_may_block=True)
        add("nsfw.odp", "extra", REJECT,
            "odp - not in $wgFileExtensions", odf("odp", [("10000.png", nsfw_png)]),
            mw_may_block=True)


# ---------------------------------------------------------------------------
# Output
# ---------------------------------------------------------------------------

def write_suite(out: Path):
    if out.exists():
        shutil.rmtree(out)
    for d in DIRS.values():
        (out / d).mkdir(parents=True, exist_ok=True)

    for c in CASES:
        c.path = out / DIRS[c.bucket] / c.name
        c.path.write_bytes(c.data)

    lines = [
        "# Upload test suite",
        "",
        "Generated by `make_testsuite.py`. **Contains explicit imagery derived "
        "from the source you supplied** - it is a moderation test corpus. Keep it "
        "off shared storage and delete it when you are done.",
        "",
        "Upload each file as a **non-admin account**. Admin holds "
        "`imgguard-bypass` and will sail through everything, which looks like a "
        "pass and is not one.",
        "",
        "Before starting, confirm a normal upload actually succeeds. If the "
        "images directory is unwritable, every file fails at the storage layer "
        "and the whole suite passes for the wrong reason.",
        "",
    ]

    for bucket, dirname in DIRS.items():
        rows = [c for c in CASES if c.bucket == bucket]
        if not rows:
            continue
        heading = {
            ACCEPT: "Should upload normally",
            REJECT: "Should be blocked",
            "reject_hard": "Should be blocked - the ones that matter",
            GAP: "Known gaps: these WILL be accepted",
            EDGE: "Edge cases",
            "extra": "Formats the wiki does not accept",
        }[bucket]
        lines += [f"## `{dirname}/` — {heading}", ""]
        if bucket == "reject_hard":
            lines += [
                "Each of these has clean content in the obvious place and "
                "explicit content somewhere a lazy scanner would not look. If any "
                "one of them uploads successfully, worst-frame selection is "
                "broken.",
                "",
            ]
        if bucket == GAP:
            lines += [
                "**These are expected to upload successfully.** They document "
                "real limits of the current configuration. Do not treat them as "
                "failures; treat them as the price of the frame and page caps, "
                "and raise the caps if the trade looks wrong to you.",
                "",
            ]
        lines += ["| File | Expected | What it tests |", "|---|---|---|"]
        for c in rows:
            exp = {ACCEPT: "accept", REJECT: "**REJECT**"}.get(c.expect, c.expect)
            if c.mw_may_block:
                exp += " *(or MediaWiki blocks first)*"
            lines.append(f"| `{c.name}` | {exp} | {c.note} |")
        lines.append("")

    lines += [
        "## Checking a rejected file was not saved",
        "",
        "```bash",
        "docker compose exec crw-local \\",
        "  find /var/www/html/images -newermt '-5 minutes' -type f",
        "```",
        "",
        "Empty after a rejection is the pass. Run it after a *successful* upload "
        "too, to prove the check can actually see files.",
        "",
        "## Reviewing verdicts",
        "",
        "`Special:Log/imgguard` as an admin. Check `frames_checked` and the worst "
        "source line - for `nsfw-page3of5.pdf` it should name page 3, and for "
        "`nsfw-second-image.docx` it should name `word/media/image2.jpeg`. Getting "
        "the right verdict via the wrong frame still means something is wrong.",
        "",
    ]
    (out / "MANIFEST.md").write_text("\n".join(lines))


def scan_all(url: str, token: str):
    import requests

    ok = bad = 0
    print(f"\nScanning {len(CASES)} files against {url}\n")
    print(f"{'file':<34} {'expected':<9} {'actual':<12} {'frames':<7} worst")
    print("-" * 92)

    for c in CASES:
        headers = {"X-Filename": c.name, "Content-Type": "application/octet-stream"}
        if token:
            headers["X-Auth-Token"] = token
        try:
            r = requests.post(f"{url.rstrip('/')}/classify", data=c.data,
                              headers=headers, timeout=60)
            j = r.json()
        except Exception as exc:
            print(f"{c.name:<34} {c.expect:<9} ERROR: {exc}")
            bad += 1
            continue

        verdict = j.get("verdict", "?")
        allowed = j.get("accepted", False)
        actual = ACCEPT if allowed else REJECT
        match = actual == c.expect
        mark = " " if match else "X"
        print(f"{mark}{c.name:<33} {c.expect:<9} {verdict:<12} "
              f"{j.get('frames_checked', 0):<7} {j.get('worst_source') or ''}")
        ok, bad = (ok + 1, bad) if match else (ok, bad + 1)

    print("-" * 92)
    print(f"{ok} as expected, {bad} not")
    if bad:
        print("\nRows marked X did not match. Anything in 04-known-gaps/ is "
              "expected to be accepted, so check those against the manifest "
              "before treating a mismatch as a fault.")
    return bad


def main():
    ap = argparse.ArgumentParser(
        description="Generate an upload test suite from one SFW and one NSFW image.")
    ap.add_argument("--sfw", required=True, help="known-good source image")
    ap.add_argument("--nsfw", required=True, help="known-bad source image")
    ap.add_argument("--out", default="testsuite", help="output directory")
    ap.add_argument("--scan", nargs="?", const="http://127.0.0.1:8181",
                    metavar="URL",
                    help="also POST every file to the classifier and compare")
    ap.add_argument("--token", default=os.environ.get("IMGGUARD_AUTH_TOKEN", ""),
                    help="X-Auth-Token, defaults to $IMGGUARD_AUTH_TOKEN")
    ap.add_argument("--include-large", action="store_true",
                    help="also build a >32 MB file (slow, ~35 MB on disk)")
    ap.add_argument("--extras", action="store_true",
                    help="also build formats the wiki does not accept")
    args = ap.parse_args()

    for p in (args.sfw, args.nsfw):
        if not Path(p).is_file():
            sys.exit(f"not found: {p}")

    with Image.open(args.sfw) as f:
        sfw = f.convert("RGB").copy()
    with Image.open(args.nsfw) as f:
        nsfw = f.convert("RGB").copy()

    # Big sources make big documents and slow PDFs without improving the test.
    for img in (sfw, nsfw):
        img.thumbnail((1024, 1024), Image.LANCZOS)

    print(f"sources: {args.sfw} {sfw.size}   {args.nsfw} {nsfw.size}")
    build(sfw, nsfw, args.include_large, args.extras)

    out = Path(args.out)
    write_suite(out)

    total = sum(len(c.data) for c in CASES)
    print(f"wrote {len(CASES)} files to {out}/ ({total / 1024 / 1024:.1f} MB)")
    for bucket, dirname in DIRS.items():
        n = len([c for c in CASES if c.bucket == bucket])
        if n:
            print(f"  {dirname:<24} {n}")
    print(f"\nread {out}/MANIFEST.md before uploading")

    if args.scan:
        sys.exit(1 if scan_all(args.scan, args.token) else 0)


if __name__ == "__main__":
    main()
