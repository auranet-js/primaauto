#!/usr/bin/env python3
"""
Okładka brandowa newsa/artykułu działu wiedzy (T-214) — deterministyczna,
bez AI (żadnego "wymyślania" wyglądu auta): 1200x675 WebP, kolory brandu
primaauto2026 (--c-primary #1B2A4A, --c-accent #D63031), NimbusSans-Bold.

Użycie:  python3 make_cover.py "Tytuł artykułu" /sciezka/out.webp [ETYKIETA]
Import:  from make_cover import make_cover; make_cover(title, out, label)
"""
import subprocess
import sys
from pathlib import Path

FONT_BOLD = "/usr/share/fonts/urw-base35/NimbusSans-Bold.otf"
FONT_REG = "/usr/share/fonts/urw-base35/NimbusSans-Regular.otf"
W, H = 1200, 675


def make_cover(title: str, out_path: str, label: str = "AKTUALNOŚCI") -> str:
    out = Path(out_path)
    out.parent.mkdir(parents=True, exist_ok=True)
    # Siatka techniczna (blueprint) — subtelna tekstura zamiast pustego tła
    grid = []
    for x in range(0, W + 1, 60):
        grid.append(f"line {x},0 {x},{H}")
    for y in range(0, H + 1, 60):
        grid.append(f"line 0,{y} {W},{y}")
    cmd = [
        "magick",
        "-size", f"{W}x{H}", "gradient:#22335A-#141F38",
        "-stroke", "#2A3B63", "-strokewidth", "1", "-draw", " ".join(grid),
        "-stroke", "none",
        # lewy pionowy akcent czerwony na całej wysokości
        "-fill", "#D63031", "-draw", f"rectangle 0,0 10,{H}",
        # etykieta sekcji — wycentrowana nad tytułem
        "(",
            "-size", "1000x60", "-background", "none",
            "-font", FONT_BOLD, "-fill", "#E8AC07", "-pointsize", "26",
            "-gravity", "Center", f"caption:{label.upper()}",
        ")",
        "-gravity", "Center", "-geometry", "+0-140", "-composite",
        # tytuł — duży, wycentrowany w pionie i poziomie
        "(",
            "-size", "1000x300", "-background", "none",
            "-font", FONT_BOLD, "-fill", "white",
            "-pointsize", "64", "-interline-spacing", "6",
            "-gravity", "Center", f"caption:{title}",
        ")",
        "-gravity", "Center", "-geometry", "+0+10", "-composite",
        # stopka brandowa — wycentrowana
        "(",
            "-size", "1000x40", "-background", "none",
            "-font", FONT_REG, "-fill", "#8DA0BC", "-pointsize", "24",
            "-gravity", "Center", "caption:PRIMA-AUTO · primaauto.com.pl",
        ")",
        "-gravity", "South", "-geometry", "+0+28", "-composite",
        "-quality", "85", str(out),
    ]
    subprocess.run(cmd, check=True, capture_output=True, text=True)
    return str(out)


if __name__ == "__main__":
    title = sys.argv[1]
    out = sys.argv[2]
    label = sys.argv[3] if len(sys.argv) > 3 else "AKTUALNOŚCI"
    print(make_cover(title, out, label))
