#!/usr/bin/env python3
"""Strona z postami do wklejenia — z pliku MD pakietu robi HTML dla Andrzeja.

Andrzej nie dostaje `.md`, tylko adres, który otwiera na telefonie: kafelek na każdą
publikację, przycisk „Kopiuj tekst", link do pobrania wideo i data z godziną. Źródłem
jest zawsze plik pakietu w `docs/social/` — poprawki nanosisz tam, a tutaj tylko
przepuszczasz je na stronę.

    python3 scripts/social/pakiet_html.py \\
        docs/social/2026-09-21-pakiet-postow-do-wklejenia.md \\
        ~/domains/auratest.pl/public_html/fe4f58fec53ctmp/primaauto-publikacje-andrzej.html

Parser czyta nagłówki `## DD.MM (dzień) GG:MM — Auto, KADR …, format` oraz bloki ``` pod
nimi. Linki do plików bierze z tabeli „Pliki — linki bezpośrednie" na końcu pakietu.
"""
import html
import json
import re
import sys
from pathlib import Path

NAGLOWEK = re.compile(
    r'## (\d{2}\.\d{2}) \(([^)]+)\) (\d{2}:\d{2}) — (.+?), (PION|POZIOM) [^,]+, (\w+)\n\n'
    r'(.*?)```\n(.*?)\n```', re.S)


def wczytaj(zrodlo):
    src = Path(zrodlo).read_text()
    pion = dict(re.findall(r'\| ([^|]+?) \| \[pobierz\]\((https://drive[^)]+)\) \| \[pobierz\]\((?:[^)]+)\) \|', src))
    poziom = dict(re.findall(r'\| ([^|]+?) \| \[pobierz\]\((?:[^)]+)\) \| \[pobierz\]\((https://drive[^)]+)\) \|', src))
    wpisy = []
    for m in NAGLOWEK.finditer(src):
        data, dzien, godz, auto, kadr, _fmt, srodek, tekst = m.groups()
        hub = re.search(r'\*\*Link:\*\* (\S+)', srodek)
        auto = auto.strip()
        wpisy.append({
            'data': data, 'dzien': dzien, 'godz': godz, 'auto': auto, 'kadr': kadr,
            'hub': hub.group(1) if hub else '',
            'pobierz': (pion if kadr == 'PION' else poziom).get(auto, ''),
            'tekst': tekst.strip(), 'uwaga': '⚠️' in srodek,
        })
    return wpisy


def karta(i, w):
    badge = 'Reels' if w['kadr'] == 'PION' else 'Post na tablicy'
    kanaly = ('<span class="kanaly oba">Facebook + Instagram</span>' if w['kadr'] == 'PION'
              else '<span class="kanaly fb">tylko Facebook</span>')
    uwaga = ('<p class="uwaga">⚠️ Zanim opublikujesz — sprawdź, czy na stronie modelu są jeszcze '
             'auta. Jeśli lista jest pusta, zmień link na <code>primaauto.com.pl/samochody/byd/</code> '
             'i usuń zdanie o dostępności.</p>') if w['uwaga'] else ''
    return f'''<article class="karta">
 <header>
  <div class="kiedy"><span class="data">{w['data']}</span><span class="dzien">{w['dzien']}</span><span class="godz">{w['godz']}</span></div>
  <div class="co"><h2>{html.escape(w['auto'])}</h2><span class="tag {'pion' if w['kadr'] == 'PION' else 'poziom'}">{badge}</span>{kanaly}</div>
 </header>
 {uwaga}
 <div class="akcje">
  <a class="btn pobierz" href="{w['pobierz']}" target="_blank" rel="noopener">⬇ Pobierz wideo</a>
  <a class="btn link" href="{w['hub']}" target="_blank" rel="noopener">🔗 Sprawdź stronę modelu</a>
 </div>
 <div class="tresc">
  <button class="kopiuj" data-cel="t{i}">Kopiuj tekst</button>
  <pre id="t{i}">{html.escape(w['tekst'])}</pre>
 </div>
</article>'''


STYL = '''
:root{--tlo:#f6f7f9;--karta:#fff;--tekst:#15181d;--slaby:#5d6470;--akcent:#0b6cf0;--ramka:#e2e5ea;--pre:#f2f4f7;--ostrzez:#fff4e5;--ostrzez-br:#f0a532}
@media (prefers-color-scheme:dark){:root:not([data-theme="light"]){--tlo:#0f1215;--karta:#171b20;--tekst:#eceef1;--slaby:#9aa3ae;--akcent:#4d9bff;--ramka:#272c33;--pre:#10141a;--ostrzez:#2e2416;--ostrzez-br:#c98520}}
:root[data-theme="dark"]{--tlo:#0f1215;--karta:#171b20;--tekst:#eceef1;--slaby:#9aa3ae;--akcent:#4d9bff;--ramka:#272c33;--pre:#10141a;--ostrzez:#2e2416;--ostrzez-br:#c98520}
*{box-sizing:border-box}
body{margin:0;background:var(--tlo);color:var(--tekst);font:16px/1.55 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;padding:0 16px 64px}
.naglowek{max-width:760px;margin:0 auto;padding:32px 0 8px}
h1{font-size:26px;margin:0 0 8px;letter-spacing:-.02em}
.lead{color:var(--slaby);margin:0}
.jak{max-width:760px;margin:16px auto 28px;background:var(--karta);border:1px solid var(--ramka);border-radius:12px;padding:16px 18px}
.jak ol{margin:8px 0 0;padding-left:20px}.jak li{margin:4px 0}
.jak .uwagi{margin:12px 0 0;color:var(--slaby);font-size:14px}
main{max-width:760px;margin:0 auto;display:flex;flex-direction:column;gap:18px}
.karta{background:var(--karta);border:1px solid var(--ramka);border-radius:12px;padding:16px 18px}
.karta header{display:flex;flex-wrap:wrap;gap:10px;align-items:baseline;justify-content:space-between;margin-bottom:12px}
.kiedy{display:flex;gap:8px;align-items:baseline}
.data{font-weight:700;font-size:19px}.dzien{color:var(--slaby);font-size:13px}
.godz{background:var(--akcent);color:#fff;border-radius:6px;padding:2px 8px;font-weight:600;font-size:14px}
.co{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
h2{font-size:17px;margin:0;font-weight:600}
.tag{font-size:12px;padding:2px 9px;border-radius:99px;border:1px solid var(--ramka);color:var(--slaby)}
.tag.pion{border-color:var(--akcent);color:var(--akcent)}
.kanaly{font-size:12px;padding:2px 9px;border-radius:99px;font-weight:600}
.kanaly.oba{background:#e8f0fe;color:#1a4fa0}
.kanaly.fb{background:var(--pre);color:var(--slaby);border:1px solid var(--ramka)}
@media (prefers-color-scheme:dark){:root:not([data-theme="light"]) .kanaly.oba{background:#1b2f4d;color:#9cc4ff}}
:root[data-theme="dark"] .kanaly.oba{background:#1b2f4d;color:#9cc4ff}
.uwaga{background:var(--ostrzez);border-left:3px solid var(--ostrzez-br);padding:10px 12px;border-radius:6px;font-size:14px;margin:0 0 12px}
.akcje{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px}
.btn{flex:1 1 180px;text-align:center;text-decoration:none;padding:11px 14px;border-radius:9px;font-weight:600;font-size:14px;border:1px solid var(--ramka);color:var(--tekst);background:var(--pre)}
.btn.pobierz{background:var(--akcent);color:#fff;border-color:transparent}
.tresc{position:relative}
pre{background:var(--pre);border:1px solid var(--ramka);border-radius:9px;padding:14px;margin:0;white-space:pre-wrap;word-wrap:break-word;font:14px/1.6 inherit}
.kopiuj{position:absolute;top:8px;right:8px;background:var(--karta);color:var(--tekst);border:1px solid var(--ramka);border-radius:7px;padding:7px 12px;font-size:13px;font-weight:600;cursor:pointer}
.kopiuj:hover{border-color:var(--akcent);color:var(--akcent)}
.kopiuj.ok{background:#1c9d54;color:#fff;border-color:transparent}
footer{max-width:760px;margin:36px auto 0;color:var(--slaby);font-size:13px;border-top:1px solid var(--ramka);padding-top:16px}
@media(max-width:520px){.karta header{flex-direction:column;gap:6px}h1{font-size:22px}}
'''

SKRYPT = '''
document.querySelectorAll('.kopiuj').forEach(function(b){
 b.addEventListener('click', function(){
  var t=document.getElementById(b.dataset.cel).textContent;
  navigator.clipboard.writeText(t).then(function(){
   b.textContent='Skopiowane \\u2713'; b.classList.add('ok');
   setTimeout(function(){b.textContent='Kopiuj tekst'; b.classList.remove('ok');},1800);
  });
 });
});
'''


def main():
    if len(sys.argv) != 3:
        sys.exit(__doc__)
    wpisy = wczytaj(sys.argv[1])
    if not wpisy:
        sys.exit('nie znalazłem ani jednego wpisu — sprawdź format nagłówków w pakiecie')
    braki = [w['auto'] for w in wpisy if not w['pobierz'] or not w['hub']]
    if braki:
        print('UWAGA, wpisy bez pliku albo bez linku:', braki)
    strona = f'''<!DOCTYPE html>
<html lang="pl"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Publikacje Prima Auto</title>
<style>{STYL}</style></head><body>
<div class="naglowek">
 <h1>Publikacje wideo — {wpisy[0]['data']} do {wpisy[-1]['data']}</h1>
 <p class="lead">{len(wpisy)} postów z sesji zdjęciowej. Wszystko gotowe: tekst, plik i link.</p>
</div>
<div class="jak">
 <strong>Jak to zrobić</strong>
 <ol>
  <li>Otwórz Planer w Meta Business Suite.</li>
  <li>Dla każdej pozycji niżej: pobierz wideo, skopiuj tekst, ustaw datę i godzinę z nagłówka.</li>
  <li>Pozycje oznaczone <em>Reels</em> publikuj jako rolkę, pozostałe jako zwykły post na tablicy.</li>
  <li>Przy rolkach zaznacz <strong>oba kanały: Facebook i Instagram</strong>. Posty poziome
      idą <strong>tylko na Facebooka</strong> — na Instagramie kadr 16:9 wyświetla się jako wąski pasek.
      Każdy kafelek ma to napisane przy nagłówku.</li>
 </ol>
 <p class="uwagi">Linki w tekstach prowadzą do stron modeli i kończą się na <code>#oferty</code> —
 dzięki temu strona otwiera się od razu na liście dostępnych aut, z pominięciem opisu.
 Proszę ich nie skracać ani nie podmieniać na konkretne samochody: te się sprzedają,
 a post zostaje.</p>
</div>
<main>
{chr(10).join(karta(i, w) for i, w in enumerate(wpisy))}
</main>
<footer>Ceny, liczby dostępnych aut i roczniki pochodzą z bazy primaauto.com.pl i były aktualne
w dniu przygotowania pakietu.</footer>
<script>{SKRYPT}</script></body></html>'''
    Path(sys.argv[2]).write_text(strona)
    print(f'zapisane: {sys.argv[2]} ({len(wpisy)} kart, {len(strona)} znaków)')


if __name__ == '__main__':
    main()
