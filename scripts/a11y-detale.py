#!/usr/bin/env python3
"""Rozbicie oblanych audytow PSI na selektory, pary kolorow i snippety.

Czyta JSON-y wyprodukowane przez scripts/a11y-przesiew.py i wyciaga z nich to,
czego nie widac w samym wyniku punktowym: ktory selektor, jakie tlo, ile razy.
Dzieki temu naprawa idzie po klasach CSS, a nie po zgadywaniu.

Uzycie:
    python3 scripts/a11y-detale.py <katalog-raw> color-contrast mobile
    python3 scripts/a11y-detale.py <katalog-raw> link-name desktop
"""
import glob
import json
import os
import re
import sys
from collections import Counter, defaultdict


def main():
    katalog = sys.argv[1] if len(sys.argv) > 1 else "tmp/a11y-raw"
    cel = sys.argv[2] if len(sys.argv) > 2 else "color-contrast"
    tryb = sys.argv[3] if len(sys.argv) > 3 else "mobile"

    klasy = defaultdict(Counter)
    pary = Counter()
    snippety = defaultdict(Counter)

    pliki = sorted(glob.glob(os.path.join(katalog, "*-%s.json" % tryb)))
    if not pliki:
        print("Brak plikow %s/*-%s.json — najpierw odpal a11y-przesiew.py" % (katalog, tryb))
        return

    for plik in pliki:
        strona = os.path.basename(plik).rsplit("-", 1)[0]
        with open(plik) as f:
            lh = json.load(f)["lighthouseResult"]
        a = lh["audits"].get(cel, {})
        for it in a.get("details", {}).get("items", []):
            node = it.get("node", {})
            sel = node.get("selector", "")
            expl = node.get("explanation", "") or ""
            snip = (node.get("snippet", "") or "")[:150]
            klasy[strona][sel.split(" > ")[-1] if sel else "?"] += 1
            snippety[strona][snip] += 1
            m = re.search(r"#([0-9a-fA-F]{6}).{0,40}?#([0-9a-fA-F]{6})", expl)
            if m:
                pary[(m.group(1).lower(), m.group(2).lower())] += 1

    print("### AUDYT: %s (%s)\n" % (cel, tryb))
    if pary:
        print("-- pary kolorow (tekst / tlo) --")
        for (fg, bg), n in pary.most_common(20):
            print("   #%-8s na #%-8s  %3d x" % (fg, bg, n))
        print()

    print("-- selektory per strona --")
    for strona, c in klasy.items():
        print("\n  [%s] razem %d" % (strona, sum(c.values())))
        for sel, n in c.most_common(30):
            print("     %-70s %3d" % (sel[:70], n))

    if cel in ("link-name", "select-name", "heading-order", "target-size", "label"):
        print("\n-- snippety --")
        for strona, c in snippety.items():
            for snip, n in c.most_common(10):
                print("  [%-14s] %dx  %s" % (strona, n, snip))


if __name__ == "__main__":
    main()
