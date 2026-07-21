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
    cmd = [
        "magick",
        # tło: pionowy gradient granatu brandowego
        "-size", f"{W}x{H}", "gradient:#24355A-#131E36",
        # subtelna siatka/tekstura: przyciemniony pas dolny pod stopkę
        "-fill", "#0E1728", "-draw", f"rectangle 0,{H-90} {W},{H}",
        # czerwony akcent
        "-fill", "#D63031", "-draw", "rectangle 72,150 152,162",
        # etykieta sekcji
        "-font", FONT_BOLD, "-pointsize", "30", "-fill", "#E8AC07",
        "-annotate", "+72+120", label.upper(),
        # tytuł (zawijany caption jako osobna warstwa)
        "(",
            "-size", "1020x340", "-background", "none",
            "-font", FONT_BOLD, "-fill", "white",
            "-pointsize", "58", "-interline-spacing", "8",
            f"caption:{title}",
        ")",
        "-gravity", "West", "-geometry", "+72+30", "-composite",
        "-gravity", "NorthWest",
        # stopka brandowa
        "-font", FONT_BOLD, "-pointsize", "26", "-fill", "white",
        "-annotate", f"+72+{H-36}", "PRIMA-AUTO",
        "-font", FONT_REG, "-pointsize", "24", "-fill", "#8DA0BC",
        "-annotate", f"+248+{H-36}", "primaauto.com.pl — chińska motoryzacja bez tajemnic",
        "-quality", "85", str(out),
    ]
    subprocess.run(cmd, check=True, capture_output=True, text=True)
    return str(out)


if __name__ == "__main__":
    title = sys.argv[1]
    out = sys.argv[2]
    label = sys.argv[3] if len(sys.argv) > 3 else "AKTUALNOŚCI"
    print(make_cover(title, out, label))
