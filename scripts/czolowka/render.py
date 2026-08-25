#!/usr/bin/env python3.12
"""Render czolowki Prima-Auto: canvas -> zrzuty klatek -> mp4.

Uzycie:
  LD_LIBRARY_PATH=~/opt/chrome-libs python3.12 render.py A light -o wynik.mp4

Deterministycznie: kazda klatka to jawne wywolanie window.render(t) i zrzut
elementu canvas. Kodowanie ffmpeg dopiero po zamknieciu przegladarki (limit
NPROC na LVE nie zniesie x264 obok zywego Chromium).
"""
import argparse, base64, os, pathlib, shutil, subprocess, tempfile

TU = pathlib.Path(__file__).parent
FFMPEG = os.path.expanduser("~/bin/ffmpeg")
FPS = 30
KADRY = {"9x16": (1080, 1920), "16x9": (1920, 1080)}


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("wariant", choices=["A", "B"])
    ap.add_argument("tlo", choices=["light", "dark"])
    ap.add_argument("-o", "--out", required=True)
    ap.add_argument("--sekundy", type=float, default=7.0)
    ap.add_argument("--kadr", choices=["9x16", "16x9"], default="9x16")
    ap.add_argument("--klatka", type=float, help="zrzut jednej klatki (sekunda) do PNG zamiast filmu")
    a = ap.parse_args()

    from playwright.sync_api import sync_playwright

    logo_data = "data:image/png;base64," + base64.b64encode(
        (TU / "primaauto-logo.png").read_bytes()).decode()
    W, H = KADRY[a.kadr]
    url = f"file://{TU / 'scena.html'}?wariant={a.wariant}&tlo={a.tlo}&kadr={a.kadr}"
    tmp = pathlib.Path(tempfile.mkdtemp(prefix="czolowka-"))

    n = 1 if a.klatka is not None else int(a.sekundy * FPS)
    with sync_playwright() as p:
        br = p.chromium.launch(args=["--force-color-profile=srgb", "--disable-lcd-text"])
        pg = br.new_page(viewport={"width": W, "height": H}, device_scale_factor=1)
        pg.add_init_script(f"window.LOGO_DATA = {logo_data!r};")
        pg.goto(url)
        pg.wait_for_function("() => window.gotowe && window.gotowe()", timeout=20000)
        canvas = pg.locator("#c")
        for i in range(n):
            t = a.klatka if a.klatka is not None else i / FPS
            pg.evaluate("t => window.render(t)", t)
            canvas.screenshot(path=str(tmp / f"{i:05d}.png"))
        br.close()

    if a.klatka is not None:
        shutil.copy(tmp / "00000.png", a.out)
    else:
        subprocess.run([
            FFMPEG, "-y", "-v", "error", "-framerate", str(FPS),
            "-i", str(tmp / "%05d.png"),
            "-c:v", "libx264", "-preset", "slow", "-crf", "17",
            "-pix_fmt", "yuv420p", "-movflags", "+faststart", a.out,
        ], check=True)
    shutil.rmtree(tmp, ignore_errors=True)
    print(a.out, os.path.getsize(a.out), "B")


if __name__ == "__main__":
    main()
