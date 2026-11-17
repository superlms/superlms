"""
Readies the bundled Poppins faces for the Hindi in our PDFs.

dompdf prints text a character at a time, without OpenType shaping, so
App\\Support\\Pdf\\Devanagari shapes each Hindi word with HarfBuzz and writes
the glyphs it comes to as Private Use code points. This script makes the
faces carry them:

1. every glyph gets a Private Use code point, U+E000 + its glyph id — the
   half forms, conjuncts, reph and matra variants have no Unicode of their own;
2. every place HarfBuzz moves a mark to (devanagari-marks.json, made by
   devanagari-marks.php) becomes a glyph of its own: the mark as a component
   at that offset, so the shaper writes one character for a placed matra and
   a word stays plain text that dompdf never breaks.

Latin and every existing mapping are left as they are.

Run on the ORIGINAL faces (the glyph count must be the one the list was made
from), e.g. after `git show <rev>:resources/fonts/Poppins-Regular.ttf`:
    python resources/fonts/add-pua-cmap.py resources/fonts/devanagari-marks.json resources/fonts/Poppins-*.ttf

The three Poppins faces share one glyph order and one set of layout tables,
so a glyph id — and a placed mark's id — means the same glyph in each.
"""
import json
import sys

from fontTools.ttLib import TTFont
from fontTools.ttLib.tables._g_l_y_f import Glyph, GlyphComponent

PUA_BASE = 0xE000
PUA_LAST = 0xF8FF

marks_path, *font_paths = sys.argv[1:]
with open(marks_path, encoding="utf-8") as f:
    listing = json.load(f)
base_count, marks = listing["glyphs"], listing["marks"]

for path in font_paths:
    font = TTFont(path)
    order = font.getGlyphOrder()
    if len(order) != base_count:
        sys.exit(f"{path}: {len(order)} glyphs, the list was made from {base_count} — run this on the original face")
    if PUA_BASE + base_count + len(marks) - 1 > PUA_LAST:
        sys.exit(f"{path}: {base_count + len(marks)} glyphs do not fit the Private Use Area")

    glyf, hmtx = font["glyf"], font["hmtx"]
    added = []
    for i, (gid, dx, dy) in enumerate(marks):
        name = f"lmsmark{i:04d}"
        glyph = Glyph()
        glyph.numberOfContours = -1
        component = GlyphComponent()
        component.glyphName = order[gid]
        component.x, component.y = int(dx), int(dy)
        component.flags = 0
        glyph.components = [component]
        glyf.glyphs[name] = glyph
        added.append(name)

    font.setGlyphOrder(order + added)
    glyf.glyphOrder = order + added
    for name in added:
        glyph = glyf[name]
        glyph.recalcBounds(glyf)
        # A placed mark moves the pen as the mark does: not at all.
        hmtx.metrics[name] = (0, getattr(glyph, "xMin", 0))

    full = order + added
    for table in font["cmap"].tables:
        # The Unicode BMP subtables (format 4): Windows (3,1) and Unicode (0,3).
        if table.isUnicode() and table.format == 4:
            for gid, name in enumerate(full):
                table.cmap[PUA_BASE + gid] = name

    if "post" in font and font["post"].formatType == 2.0:
        font["post"].extraNames = []
        font["post"].mapping = {}
    os2 = font["OS/2"]
    os2.usLastCharIndex = max(os2.usLastCharIndex, PUA_BASE + len(full) - 1)
    font.save(path)
    print(f"{path}: {base_count} glyphs + {len(added)} placed marks at U+{PUA_BASE:04X}-U+{PUA_BASE + len(full) - 1:04X}")
