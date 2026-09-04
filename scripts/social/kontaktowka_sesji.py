#!/usr/bin/env python3
"""Kontaktówka zdjęć z sesji — wszystkie kadry z Dysku na jednej stronie, do wyboru kart.

Zdjęcia z sesji są jedynym materiałem, którego nie ma w feedzie i którego nie ma konkurencja.
Żeby dało się z nich złożyć karuzelę, trzeba je najpierw zobaczyć obok siebie — stąd ta strona.
Pobiera oryginały z Dysku, robi miniatury (ffmpeg, bez rekompresji oryginałów) i składa HTML.

    python3 scripts/social/kontaktowka_sesji.py
"""
import html
import os
import subprocess
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import drive_lib as dl

FFMPEG = '/home/host476470/bin/ffmpeg'
DROP = '/home/host476470/domains/auratest.pl/public_html/fe4f58fec53ctmp'
KATALOG = os.path.join(DROP, 'primaauto-sesje-zdjecia')
STRONA = os.path.join(DROP, 'primaauto-sesje-kontaktowka-2026-09-04.html')
SZEROKOSC = 520


def zbierz(tok, fid, sciezka='', gl=0, out=None):
    out = out if out is not None else []
    for f in dl.lista(fid, tok):
        if f['mimeType'].endswith('folder') and gl < 2:
            zbierz(tok, f['id'], f"{sciezka}/{f['name']}", gl + 1, out)
        elif f['mimeType'].split('/')[-1] in ('jpeg', 'jpg', 'png', 'webp'):
            out.append((sciezka.strip('/'), f))
    return out


def main():
    tok = dl.access_token()
    zdjecia = zbierz(tok, dl.FOLDER_SESJE)
    os.makedirs(KATALOG, exist_ok=True)
    print(f'{len(zdjecia)} zdjęć na Dysku', file=sys.stderr)

    grupy = {}
    for model, f in sorted(zdjecia, key=lambda p: (p[0], p[1]['name'])):
        if not model:
            continue
        nazwa = f"{model.replace('/', '-')}-{f['id'][-8:]}.jpg"
        cel = os.path.join(KATALOG, nazwa)
        if not os.path.exists(cel):
            surowy = f'/tmp/kontaktowka-{f["id"][-8:]}'
            dl.pobierz(f['id'], surowy, tok)
            subprocess.run([FFMPEG, '-y', '-loglevel', 'error', '-i', surowy,
                            '-vf', f'scale={SZEROKOSC}:-2', '-q:v', '4', cel],
                           check=False)
            os.path.exists(surowy) and os.remove(surowy)
        grupy.setdefault(model, []).append((nazwa, f['name']))

    sekcje = []
    for model, pliki in grupy.items():
        kafle = ''.join(
            f"<figure><img src='primaauto-sesje-zdjecia/{html.escape(n)}' alt='' loading=lazy>"
            f"<figcaption>{html.escape(org[:40])}</figcaption></figure>"
            for n, org in pliki)
        sekcje.append(f"<section><h2>{html.escape(model)} <span>{len(pliki)} kadrów</span></h2>"
                      f"<div class=siatka>{kafle}</div></section>")

    open(STRONA, 'w', encoding='utf-8').write(f"""<!doctype html><html lang=pl><head>
<meta charset=utf-8><meta name=viewport content="width=device-width,initial-scale=1">
<title>Zdjęcia z sesji — kontaktówka</title><style>
:root {{ --tlo:#faf9f7; --tekst:#1a1a1a; --szary:#6b6b6b; --linia:#e4e1dc; }}
@media (prefers-color-scheme:dark) {{ :root:not([data-theme=light]) {{
  --tlo:#16151a; --tekst:#ece9e6; --szary:#9c9791; --linia:#332f3a; }} }}
body {{ background:var(--tlo); color:var(--tekst); margin:0; padding:28px 20px 70px;
  font:15px/1.5 -apple-system,system-ui,sans-serif; }}
.wrap {{ max-width:1240px; margin:0 auto; }}
h1 {{ font-size:25px; margin:0 0 6px; }}
h2 {{ font-size:17px; margin:34px 0 10px; border-bottom:1px solid var(--linia); padding-bottom:6px; }}
h2 span {{ color:var(--szary); font-weight:400; font-size:13px; }}
.siatka {{ display:grid; grid-template-columns:repeat(auto-fill,minmax(190px,1fr)); gap:10px; }}
figure {{ margin:0; }}
figure img {{ width:100%; border-radius:8px; display:block; background:#000; }}
figcaption {{ color:var(--szary); font-size:10.5px; margin-top:4px; word-break:break-all; }}
p.info {{ color:var(--szary); font-size:13.5px; max-width:70ch; }}
</style></head><body><div class=wrap>
<h1>Zdjęcia z sesji — kontaktówka</h1>
<p class=info>{len(zdjecia)} kadrów z dwóch sesji, dziesięć modeli. Materiał pod karuzele
i karty samochodów — nie z feedu produktowego, tylko z Dysku. Wskaż, które kadry mają
trafić na karty; kolejność kart to kolejność, w jakiej je wymienisz.</p>
{''.join(sekcje)}
</div></body></html>""")
    print('gotowe:', STRONA, file=sys.stderr)


if __name__ == '__main__':
    main()
