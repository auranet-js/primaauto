#!/usr/bin/env python3
"""Konwerter pism dla klienta: Markdown -> HTML w stylu pism Auranet (v5/v6).

Uzycie: python3 scripts/pismo-md2html.py docs/meta/prosby-v6-2026-08-28.md [wyjscie.html]

Styl jest celowo wklejony inline (jeden plik do wyslania/wystawienia, bez zaleznosci).
Zrodlo stylu: primaauto-prosby-koncowe-v5-2026-08-28.html.
"""
import re
import sys
from pathlib import Path

import markdown

STYLE = """:root{--tekst:#1b1f24;--szary:#5b6470;--linia:#e3e7ec;--tlo:#fff;--akcent:#0A66C2;--zolty:#fff8e1;--zoltyram:#e6c65c;--czerw:#fdf2f2;--czerwram:#d9534f}
*{box-sizing:border-box}
body{margin:0;background:#f4f6f8;color:var(--tekst);font:16px/1.65 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif}
.wrap{max-width:820px;margin:0 auto;padding:40px 22px 80px;background:var(--tlo)}
h1{font-size:28px;line-height:1.25;margin:0 0 6px;letter-spacing:-.02em}
h2{font-size:21px;margin:44px 0 12px;padding-top:22px;border-top:1px solid var(--linia);letter-spacing:-.01em}
h3{font-size:17px;margin:26px 0 8px}
p{margin:0 0 14px}
hr{border:0;border-top:1px solid var(--linia);margin:34px 0}
table{border-collapse:collapse;width:100%;margin:18px 0;font-size:15px;display:block;overflow-x:auto}
th,td{border:1px solid var(--linia);padding:9px 11px;text-align:left;vertical-align:top}
th{background:#f7f9fb;font-weight:600}
code{background:#f2f4f7;padding:2px 5px;border-radius:3px;font-size:.9em;font-family:ui-monospace,Menlo,Consolas,monospace;word-break:break-all}
blockquote{margin:18px 0;padding:14px 18px;background:var(--zolty);border-left:4px solid var(--zoltyram);border-radius:0 4px 4px 0}
blockquote.uwaga{background:var(--czerw);border-left-color:var(--czerwram)}
blockquote p:last-child{margin-bottom:0}
ul,ol{margin:0 0 14px;padding-left:22px}
li{margin-bottom:6px}
strong{font-weight:600}
a{color:var(--akcent)}
.prio{background:#f7f9fb;border:1px solid var(--linia);border-radius:5px;padding:4px 12px 16px;margin:26px 0}
.prio h3{margin-top:18px}
.kto{color:var(--szary);font-size:14px;margin:-4px 0 12px}
@media(max-width:640px){.wrap{padding:26px 16px 60px}h1{font-size:23px}h2{font-size:19px}}"""

SZABLON = """<!DOCTYPE html>
<html lang="pl"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>{tytul}</title>
<style>
{styl}
</style>
</head><body>
<div class="wrap">
{tresc}
</div>
</body></html>
"""


def konwertuj(md_tekst):
    html = markdown.markdown(md_tekst, extensions=['tables', 'sane_lists'])
    # cudzyslowy zamykajace ASCII w polskim tekscie -> typograficzne
    html = re.sub(r'„([^"]*?)"', r'„\1”', html)
    return html


def main():
    if len(sys.argv) < 2:
        sys.exit(__doc__)
    src = Path(sys.argv[1])
    dst = Path(sys.argv[2]) if len(sys.argv) > 2 else src.with_suffix('.html')
    md_tekst = src.read_text(encoding='utf-8')
    tytul = md_tekst.lstrip().split('\n', 1)[0].lstrip('# ').strip()
    dst.write_text(
        SZABLON.format(tytul=tytul, styl=STYLE, tresc=konwertuj(md_tekst)),
        encoding='utf-8',
    )
    print(f"{dst}  ({dst.stat().st_size} B)")


if __name__ == '__main__':
    main()
