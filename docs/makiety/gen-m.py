#!/usr/bin/env python3
"""Makieta M (08.09): arkusz wyposażenia na telefonie po dojściu 11 cech (38 → 48).
Trzy warianty obok siebie na ekranach 390 px. Liczby z wp7j_asiaauto_specs (publish)."""
import json, pathlib

S = '/tmp/claude-1584/-home-host476470-projekty-primaauto/edfd0ba1-24f8-455b-a8f3-29461b5abdc8/scratchpad/flagi48.json'
D = json.load(open(S))
OUT = pathlib.Path('/home/host476470/domains/auratest.pl/public_html/fe4f58fec53ctmp')
FL, TOTAL = D['flagi'], D['total']
def n(x): return f"{int(x):,}".replace(",", " ")

ZAZ = {'cam_360'}
def wiersz(f, wciecie=False):
    on = f['k'] in ZAZ
    return (f'<label class="w{" on" if on else ""}{" wc" if wciecie else ""}">'
            f'<i class="bx"></i><span>{f["label"]}</span><b>{n(f["n"])}</b></label>')

# A — stan wdrożony: płaska lista, kolejność grupowa, bez nagłówków
A = ''.join(wiersz(f) for f in FL)

# B — nagłówki grup przyklejone przy przewijaniu
B, ostatnia = '', None
for f in FL:
    if f['grupa'] != ostatnia:
        ostatnia = f['grupa']
        ile = sum(1 for x in FL if x['grupa'] == ostatnia)
        B += f'<p class="gh">{ostatnia}<em>{ile}</em></p>'
    B += wiersz(f)

# C — skrót „najczęściej wybierane" na górze, reszta jak w B
TOP = ['cam_360', 'seat_heat_f', 'roof_panorama', 'adaptive_cruise', 'tailgate_el', 'seat_vent_f']
mapa = {f['k']: f for f in FL}
C = '<p class="gh gh--top">Najczęściej wybierane<em>6</em></p>' + ''.join(wiersz(mapa[k]) for k in TOP)
ostatnia = None
for f in FL:
    if f['grupa'] != ostatnia:
        ostatnia = f['grupa']
        ile = sum(1 for x in FL if x['grupa'] == ostatnia)
        C += f'<p class="gh">{ostatnia}<em>{ile}</em></p>'
    C += wiersz(f)

def telefon(tytul, opis, tresc, ile):
    return f'''<figure class="fon">
  <figcaption><b>{tytul}</b>{opis}</figcaption>
  <div class="ekran">
    <div class="ark">
      <i class="uchwyt"></i>
      <div class="top"><p>Wyposażenie</p><span class="ile">{ile}</span><button class="x">&times;</button></div>
      <div class="szukaj"><span>Szukaj...</span></div>
      <div class="lista">{tresc}</div>
      <div class="stopka"><span class="btn">Pokaż {n(TOTAL)} ofert</span></div>
    </div>
  </div>
</figure>'''

HTML = f'''<!doctype html><html lang="pl"><meta charset="utf-8">
<title>Prima Auto — makieta M: arkusz wyposażenia na telefonie</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
*,*::before,*::after{{box-sizing:border-box}}
:root{{--r:6px;--granat:#1B2A4A;--linia:#E1E4E8;--linia2:#B9C1CB;--szary:#5C6B7F;--akcent:#C92A2B}}
body{{margin:0;background:#F5F6F8;color:#2D3748;font:14px/1.45 Inter,-apple-system,'Segoe UI',sans-serif}}
.naglowek{{background:#9B0000;color:#fff;padding:14px 24px;font-weight:800}}
.strona{{max-width:1320px;margin:0 auto;padding:22px 20px 70px}}
h1{{font-size:24px;margin:0 0 4px;color:var(--granat)}}
.lead{{margin:0 0 22px;color:var(--szary);max-width:86ch}}
.rzad{{display:flex;gap:28px;flex-wrap:wrap}}
.fon{{margin:0;width:390px}}
.fon figcaption{{margin:0 0 8px;font-size:13px;color:var(--szary);line-height:1.5}}
.fon figcaption b{{display:block;font-size:15px;color:var(--granat);margin-bottom:2px}}
.ekran{{height:720px;border:1px solid var(--linia);border-radius:18px;overflow:hidden;background:#fff;
  box-shadow:0 6px 24px rgba(27,42,74,.09);display:flex;flex-direction:column;justify-content:flex-end}}
.ark{{display:flex;flex-direction:column;height:92%;background:#fff;border-top:1px solid var(--linia);
  border-radius:14px 14px 0 0;box-shadow:0 -6px 20px rgba(27,42,74,.10)}}
.uchwyt{{width:36px;height:4px;background:var(--linia2);border-radius:2px;margin:8px auto 0}}
.top{{display:flex;align-items:center;gap:10px;padding:10px 16px 8px}}
.top p{{margin:0;font-size:17px;font-weight:700;color:var(--granat)}}
.ile{{background:var(--granat);color:#fff;border-radius:99px;min-width:20px;height:20px;display:inline-flex;
  align-items:center;justify-content:center;font-size:11.5px;font-weight:700;padding:0 6px}}
.x{{margin-left:auto;width:32px;height:32px;border:0;background:none;font-size:22px;color:var(--szary);cursor:default}}
.szukaj{{padding:0 16px 8px}}
.szukaj span{{display:block;border:1px solid var(--linia);border-radius:var(--r);padding:9px 12px;color:#9AA5B1;font-size:15px}}
.lista{{flex:1 1 auto;overflow-y:auto;padding:0 16px}}
.w{{display:flex;align-items:center;gap:10px;min-height:44px;padding:0 6px;cursor:default}}
.w i.bx{{flex:0 0 auto;width:22px;height:22px;border:2px solid var(--linia2);border-radius:4px}}
.w>span{{flex:1 1 auto;font-size:15px}}
.w b{{margin-left:auto;font-size:13px;font-weight:400;color:var(--szary);font-variant-numeric:tabular-nums}}
.w.on i.bx{{background:var(--granat);border-color:var(--granat);position:relative}}
.w.on i.bx::after{{content:'✓';position:absolute;inset:0;color:#fff;font-size:14px;line-height:18px;text-align:center}}
.gh{{position:sticky;top:0;z-index:1;margin:0;padding:10px 6px 4px;background:#fff;font-size:12px;font-weight:700;
  letter-spacing:.4px;text-transform:uppercase;color:var(--szary);border-bottom:1px solid var(--linia)}}
.gh em{{font-style:normal;float:right;color:#9AA5B1;letter-spacing:0}}
.gh--top{{color:var(--granat)}}
.stopka{{padding:10px 16px 14px;border-top:1px solid var(--linia)}}
.btn{{display:block;text-align:center;background:var(--akcent);color:#fff;font-weight:700;border-radius:var(--r);padding:13px}}
.uwagi{{margin:28px 0 0;padding:0;list-style:none;color:var(--szary);font-size:13px;max-width:100ch}}
.uwagi li{{padding:5px 0 5px 16px;position:relative}}
.uwagi li::before{{content:"—";position:absolute;left:0;color:#9AA5B1}}
.uwagi b{{color:var(--granat)}}
</style>
<div class="naglowek">PRIMA AUTO — makieta M (08.09.2026)</div>
<div class="strona">
<h1>Arkusz wyposażenia na telefonie — 48 cech zamiast 38</h1>
<p class="lead">Po dzisiejszym wdrożeniu arkusz ma {len(FL)} pozycji. Wariant A jest tym, co stoi teraz na
produkcji — nic nie zmieniałem w wyglądzie. B i C to propozycje; obie zostają tym samym arkuszem
(zasada z 03.09: wyposażenie zachowuje się jak marka i model), zmienia się tylko orientacja w długiej liście.
Ekrany 390 px, liczby prawdziwe — {n(TOTAL)} ofert w bazie.</p>

<div class="rzad">
{telefon('A — stan obecny (wdrożony)', 'Płaska lista, kolejność grupowa, bez nagłówków. 48 przewinięć w jednym ciągu — po trzecim ekranie nie wiadomo, gdzie się jest ani ile zostało.', A, '1')}
{telefon('B — nagłówki grup przyklejone', 'Ten sam podział co na komputerze. Nagłówek zostaje przy górnej krawędzi przy przewijaniu, więc zawsze widać, w której grupie się jest. Zero dodatkowych kliknięć.', B, '1')}
{telefon('C — skrót na górze + grupy', 'Nad grupami sześć cech, które klienci wybierają najczęściej. Reszta bez zmian. Skraca drogę do typowego wyboru, ale duplikuje pozycje na liście.', C, '1')}
</div>

<ul class="uwagi">
  <li><b>Rekomendacja: B.</b> Kosztuje jedną regułę CSS (nagłówki są już w HTML, dziś ukryte na telefonie)
  i nie zmienia zachowania arkusza. C dokłada duplikaty tej samej cechy w dwóch miejscach listy — przy
  zaznaczeniu trzeba by je synchronizować, a szukajka pokazywałaby pozycję dwa razy.</li>
  <li><b>Czego nie proponuję:</b> podziału na dwa poziomy (lista grup → wejście w grupę). To był pierwszy
  pomysł z 03.09 i wtedy go ściąłeś — wyposażenie ma zachowywać się jak marka i model. Nadal tak uważam.</li>
  <li><b>Szukajka zostaje</b> niezależnie od wariantu — przy 48 pozycjach to najszybsza droga dla kogoś,
  kto wie, czego chce.</li>
</ul>
</div></html>'''

p = OUT / 'primaauto-makieta-m-arkusz-telefon-2026-09-08.html'
p.write_text(HTML, encoding='utf-8')
print(p)
