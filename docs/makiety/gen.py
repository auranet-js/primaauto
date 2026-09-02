#!/usr/bin/env python3
"""
Generator makiet paska filtrów (T-116). Trzy warianty na tych samych, prawdziwych
danych z produkcji, żeby porównanie dotyczyło projektu, a nie treści.
"""
import json, pathlib

D = json.load(open('/home/host476470/projekty/primaauto/tmp/t116e3/dane-mockup.json'))
OUT = pathlib.Path('/home/host476470/projekty/primaauto/tmp/t116e3/mockupy')

def n(x):
    return f"{int(x):,}".replace(",", " ")

MARKI = list(D['enum']['make'].items())[:14]
LAB_MAKE = D['labels']['make']
PALIWA = [(k, D['labels']['fuel'].get(k, k), v) for k, v in D['enum']['fuel'].items()][:6]
NADWOZIA = [(k, D['labels']['body'].get(k, k), v) for k, v in D['enum']['body'].items()][:7]
OFERTA = [('', 'Wszystkie'), ('do-sprowadzenia', 'Do sprowadzenia'),
          ('w-drodze', 'W drodze do Polski'), ('na-placu', 'Na placu w Rzeszowie')]
FLAGI = [('Kamera 360°', D['flags']['cam_360']), ('Lidar', D['flags']['lidar']),
         ('Masaż foteli przód', D['flags']['seat_massage_f']), ('Tempomat adaptacyjny', D['flags']['adaptive_cruise']),
         ('Pompa ciepła', D['flags']['heat_pump']), ('Zawieszenie pneumatyczne', D['flags']['air_susp'])]

KARTY = [
    ("Zeekr 9X 2026 Ultra 70kWh 6-osobowy", "448 000", "2026", "100 km", "4,2 s", "PHEV", "Shenzhen"),
    ("NIO ES8 2025 6-osobowy Executive Signature", "361 000", "2025", "18 000 km", "4,0 s", "EV", "Foshan"),
    ("AITO M8 2025 EREV Max+ 53.4kWh", "325 000", "2025", "17 300 km", "5,2 s", "EREV", "Kanton"),
]

def karty_html(cls=''):
    out = []
    for t, cena, rok, przeb, przysp, nap, miasto in KARTY:
        out.append(f'''
      <article class="karta {cls}">
        <div class="karta__foto" aria-hidden="true"></div>
        <div class="karta__tresc">
          <h3 class="karta__tytul">{t}</h3>
          <p class="karta__cena">{cena} PLN</p>
          <p class="karta__spec">{rok} r. <i>·</i> {przeb} <i>·</i> {przysp} <i>·</i> {nap}</p>
          <p class="karta__miasto">{miasto}</p>
          <div class="karta__akcje">
            <span class="btn btn--glowny">Szczegóły</span>
            <span class="btn btn--drugi">Zamów</span>
          </div>
        </div>
      </article>''')
    return ''.join(out)

BAZA_CSS = '''
*,*::before,*::after{box-sizing:border-box}
body{margin:0;background:#F5F6F8;color:#2D3748;font:15px/1.5 Inter,-apple-system,'Segoe UI',sans-serif}
.pasek-marki{background:#8B1113;color:#fff;padding:14px 24px;font-weight:700;letter-spacing:.02em}
.strona{max-width:1280px;margin:0 auto;padding:24px 20px 60px}
h1{font-size:30px;margin:0 0 20px;color:#1B2A4A;letter-spacing:-.01em}
.wariant-info{background:#fff;border-left:3px solid #1B2A4A;padding:12px 16px;margin:0 0 22px;font-size:13.5px;color:#5C6B7F}
.wariant-info b{color:#1B2A4A}
.karta{display:flex;gap:16px;background:#fff;border:1px solid #E1E4E8;border-radius:6px;overflow:hidden;margin-bottom:12px}
.karta__foto{width:210px;flex:0 0 210px;background:linear-gradient(135deg,#dfe3e8,#c9d0d8)}
.karta__tresc{padding:14px 16px 16px;flex:1}
.karta__tytul{margin:0 0 6px;font-size:16px;color:#1B2A4A}
.karta__cena{margin:0 0 6px;font-size:19px;font-weight:700;color:#C92A2B}
.karta__spec{margin:0 0 4px;font-size:14px;color:#2D3748}
.karta__spec i{color:#A0AAB6;font-style:normal;padding:0 2px}
.karta__miasto{margin:0 0 12px;font-size:13px;color:#5C6B7F}
.karta__akcje{display:flex;gap:8px}
.btn{display:inline-flex;align-items:center;padding:8px 16px;border-radius:6px;font-size:14px;font-weight:600}
.btn--glowny{background:#1B2A4A;color:#fff}
.btn--drugi{border:1px solid #C92A2B;color:#C92A2B}
'''

# ---------------------------------------------------------------- WARIANT A
a_chips = ''.join(f'<div class="chip"><span>{e}</span><i></i></div>' for e in
                  ['Marka','Cena','Rocznik','Przebieg','Napęd','Nadwozie','Osiągi','Wyposażenie','Więcej'])
a_seg = ''.join(
    f'<label class="seg{" is-active" if k=="" else ""}"><span>{lab}</span><b>{n(D["oferta"][k])}</b></label>'
    for k, lab in OFERTA)
A = f'''<!doctype html><meta charset="utf-8"><title>Wariant A - stan obecny</title>
<style>{BAZA_CSS}
.oferta{{display:flex;gap:2px;background:#fff;border:1px solid #D8DDE3;border-radius:6px;padding:3px;margin-bottom:14px}}
.seg{{flex:1;display:flex;align-items:baseline;justify-content:center;gap:8px;padding:11px 14px;border-radius:4px;white-space:nowrap}}
.seg b{{color:#5C6B7F;font-size:13px;font-weight:400;font-variant-numeric:tabular-nums}}
.seg.is-active{{background:#1B2A4A}}.seg.is-active span{{color:#fff;font-weight:600}}.seg.is-active b{{color:rgba(255,255,255,.75)}}
.filtry{{display:flex;gap:10px;margin-bottom:16px}}
.chips{{display:flex;flex-wrap:wrap;gap:8px;flex:1}}
.chip{{display:inline-flex;align-items:center;gap:7px;padding:9px 13px;background:#fff;border:1px solid #D8DDE3;border-radius:21px;white-space:nowrap}}
.chip i{{width:7px;height:7px;border-right:1.5px solid currentColor;border-bottom:1.5px solid currentColor;transform:rotate(45deg) translate(-1px,-1px);opacity:.55}}
.wyczysc{{color:#C92A2B;font-weight:600;text-decoration:underline;white-space:nowrap;padding:9px 4px}}
.toolbar{{display:flex;align-items:baseline;justify-content:space-between;padding-bottom:12px;margin-bottom:14px;border-bottom:1px solid #D8DDE3}}
.licznik b{{font-size:21px;font-weight:700;color:#1B2A4A;font-variant-numeric:tabular-nums}}
.licznik span{{color:#5C6B7F}}
.sort{{border:1px solid #D8DDE3;border-radius:6px;padding:8px 12px;background:#fff;color:#2D3748}}
</style>
<div class="pasek-marki">PRIMA-AUTO</div>
<div class="strona">
  <h1>Wyszukiwarka zaawansowana</h1>
  <p class="wariant-info"><b>Wariant A - stan obecny (v0.36.0).</b> Dwa akcenty w pasku (granat + czerwony „Wyczyść"), en-dash w zakresach, listy jednokolumnowe, bez sprzężenia dotykowego.</p>
  <div class="oferta">{a_seg}</div>
  <div class="filtry"><div class="chips">{a_chips}</div><span class="wyczysc">Wyczyść</span></div>
  <div class="toolbar"><p class="licznik"><b>{n(D['total'])}</b> <span>ofert</span></p><span class="sort">Sortuj: Najnowsze</span></div>
  {karty_html()}
</div>'''

# ---------------------------------------------------------------- WARIANT B
b_chips = ''.join(f'<button class="chip"><span>{e}</span><i></i></button>' for e in
                  ['Marka','Cena','Rocznik','Przebieg','Napęd','Nadwozie','Osiągi','Wyposażenie','Więcej'])
b_seg = ''.join(
    f'<label class="seg{" is-active" if k=="" else ""}"><span>{lab}</span><b>{n(D["oferta"][k])}</b></label>'
    for k, lab in OFERTA)
b_marki = ''.join(f'<label class="opt"><input type=checkbox><span>{LAB_MAKE.get(k,k)}</span><b>{n(v)}</b></label>'
                  for k, v in MARKI)
B = f'''<!doctype html><meta charset="utf-8"><title>Wariant B - wg taste-skill</title>
<style>{BAZA_CSS}
/* Shape lock: interaktywne = pill (999px), powierzchnie = 6px. Bez wyjątków. */
.oferta{{display:flex;gap:2px;background:#fff;border:1px solid #D8DDE3;border-radius:6px;padding:3px;margin-bottom:12px}}
.seg{{flex:1;display:flex;align-items:baseline;justify-content:center;gap:8px;padding:10px 14px;border-radius:4px;white-space:nowrap;transition:background-color .12s}}
.seg b{{color:#5C6B7F;font-size:13px;font-weight:400;font-variant-numeric:tabular-nums}}
.seg:active{{transform:translateY(1px)}}
.seg.is-active{{background:#1B2A4A}}.seg.is-active span{{color:#fff;font-weight:600}}.seg.is-active b{{color:rgba(255,255,255,.78)}}
.filtry{{display:flex;gap:10px;margin-bottom:14px;align-items:center}}
.chips{{display:flex;flex-wrap:wrap;gap:7px;flex:1}}
.chip{{display:inline-flex;align-items:center;gap:7px;padding:8px 13px;background:#fff;border:1px solid #D8DDE3;border-radius:999px;font:inherit;color:inherit;cursor:pointer;transition:border-color .12s,transform .06s}}
.chip:hover{{border-color:#B9C1CB}}
.chip:active{{transform:scale(.975)}}
.chip i{{width:7px;height:7px;border-right:1.5px solid currentColor;border-bottom:1.5px solid currentColor;transform:rotate(45deg) translate(-1px,-1px);opacity:.5}}
/* Color lock: jeden akcent w pasku (granat). Czerwień zarezerwowana dla kart. */
.wyczysc{{color:#5C6B7F;text-decoration:underline;text-underline-offset:3px;white-space:nowrap;padding:8px 2px;background:none;border:0;font:inherit;cursor:pointer}}
.wyczysc:hover{{color:#1B2A4A}}
.toolbar{{display:flex;align-items:baseline;justify-content:space-between;padding-bottom:10px;margin-bottom:12px;border-bottom:1px solid #D8DDE3}}
.licznik b{{font-size:21px;font-weight:700;color:#1B2A4A;font-variant-numeric:tabular-nums}}
.licznik span{{color:#5C6B7F}}
.sort{{border:1px solid #D8DDE3;border-radius:6px;padding:8px 12px;background:#fff;color:#2D3748}}
.pop{{margin:0 0 18px;background:#fff;border:1px solid #B9C1CB;border-radius:6px;padding:12px 16px 14px;max-width:520px;box-shadow:0 10px 28px rgba(27,42,74,.13)}}
.pop h4{{margin:2px 0 8px;font-size:13px;color:#5C6B7F;font-weight:600}}
/* §4.9: lista >5 pozycji nie jest jedną kolumną */
.opts{{display:grid;grid-template-columns:1fr 1fr;gap:0 18px}}
.opt{{display:flex;align-items:center;gap:9px;padding:7px 2px;cursor:pointer}}
.opt b{{margin-left:auto;color:#5C6B7F;font-size:13px;font-weight:400;font-variant-numeric:tabular-nums}}
.opt:hover{{background:#F5F6F8}}
</style>
<div class="pasek-marki">PRIMA-AUTO</div>
<div class="strona">
  <h1>Wyszukiwarka zaawansowana</h1>
  <p class="wariant-info"><b>Wariant B - wg taste-skill.</b> Jeden akcent w pasku (czerwień wyłącznie dla kart), zakresy z dywizem zamiast en-dasha, listy dwukolumnowe, sprzężenie dotykowe na kliknięciu, gęstość podniesiona do 7.</p>
  <div class="oferta">{b_seg}</div>
  <div class="filtry"><div class="chips">{b_chips}</div><button class="wyczysc">Wyczyść</button></div>
  <div class="pop"><h4>Marka</h4><div class="opts">{b_marki}</div></div>
  <div class="toolbar"><p class="licznik"><b>{n(D['total'])}</b> <span>ofert</span></p><span class="sort">Sortuj: Najnowsze</span></div>
  {karty_html()}
</div>'''

# ---------------------------------------------------------------- WARIANT C
c_seg = ''.join(
    f'<label class="seg{" is-active" if k=="na-placu" else ""}"><b>{n(D["oferta"][k])}</b><span>{lab}</span></label>'
    for k, lab in OFERTA)
c_rows = ''
for tytul, pary in [
    ('Marka', [(LAB_MAKE.get(k,k), v) for k, v in MARKI[:7]]),
    ('Rodzaj napędu', [(l, v) for _, l, v in PALIWA]),
    ('Nadwozie', [(l, v) for _, l, v in NADWOZIA]),
    ('Wyposażenie', FLAGI),
]:
    pigulki = ''.join(f'<label class="tag"><input type=checkbox><span>{l}</span><b>{n(v)}</b></label>' for l, v in pary)
    c_rows += f'<div class="rzad"><h4>{tytul}</h4><div class="tagi">{pigulki}</div></div>'
C = f'''<!doctype html><meta charset="utf-8"><title>Wariant C - filtry rozwinięte</title>
<style>{BAZA_CSS}
.oferta{{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:#D8DDE3;border:1px solid #D8DDE3;border-radius:6px;overflow:hidden;margin-bottom:14px}}
.seg{{background:#fff;display:flex;flex-direction:column;gap:2px;padding:14px 16px;cursor:pointer}}
.seg b{{font-size:22px;font-weight:700;color:#1B2A4A;font-variant-numeric:tabular-nums;line-height:1.1}}
.seg span{{font-size:13.5px;color:#5C6B7F}}
.seg.is-active{{background:#1B2A4A}}.seg.is-active b,.seg.is-active span{{color:#fff}}
.seg.is-active span{{color:rgba(255,255,255,.8)}}
.panel{{background:#fff;border:1px solid #D8DDE3;border-radius:6px;padding:4px 18px 14px;margin-bottom:16px}}
.rzad{{display:grid;grid-template-columns:132px 1fr;gap:14px;align-items:start;padding:12px 0;border-top:1px solid #EDF0F3}}
.rzad:first-child{{border-top:0}}
.rzad h4{{margin:5px 0 0;font-size:13.5px;color:#5C6B7F;font-weight:600}}
.tagi{{display:flex;flex-wrap:wrap;gap:6px}}
.tag{{display:inline-flex;align-items:center;gap:7px;padding:6px 12px;border:1px solid #D8DDE3;border-radius:999px;cursor:pointer;font-size:14px;transition:border-color .12s,transform .06s}}
.tag:hover{{border-color:#B9C1CB}}
.tag:active{{transform:scale(.975)}}
.tag input{{margin:0;accent-color:#1B2A4A}}
.tag b{{color:#5C6B7F;font-size:12.5px;font-weight:400;font-variant-numeric:tabular-nums}}
.wiecej{{margin-top:10px;background:none;border:0;font:inherit;color:#5C6B7F;text-decoration:underline;text-underline-offset:3px;cursor:pointer;padding:4px 2px}}
.toolbar{{display:flex;align-items:baseline;justify-content:space-between;padding-bottom:10px;margin-bottom:12px;border-bottom:1px solid #D8DDE3}}
.licznik b{{font-size:21px;font-weight:700;color:#1B2A4A;font-variant-numeric:tabular-nums}}
.licznik span{{color:#5C6B7F}}
.sort{{border:1px solid #D8DDE3;border-radius:6px;padding:8px 12px;background:#fff;color:#2D3748}}
</style>
<div class="pasek-marki">PRIMA-AUTO</div>
<div class="strona">
  <h1>Wyszukiwarka zaawansowana</h1>
  <p class="wariant-info"><b>Wariant C - filtry rozwinięte, bez popoverów.</b> Rodzaj oferty jako cztery kafle z liczbą na pierwszym planie. Filtry widoczne od razu, wierszami; nic się nie chowa, nic nie trzeba klikać dwa razy. Pokazany stan: wybrane „Na placu w Rzeszowie".</p>
  <div class="oferta">{c_seg}</div>
  <div class="panel">{c_rows}<button class="wiecej">Cena, rocznik, przebieg, osiągi i 6 innych filtrów</button></div>
  <div class="toolbar"><p class="licznik"><b>19</b> <span>ofert</span></p><span class="sort">Sortuj: Najnowsze</span></div>
  {karty_html()}
</div>'''

for nazwa, tresc in [('wariant-a-obecny.html', A), ('wariant-b-taste.html', B), ('wariant-c-rozwiniete.html', C)]:
    (OUT / nazwa).write_text(tresc, encoding='utf-8')
    print(f'{nazwa}: {len(tresc)} B')

# kontrola: zero em/en-dash w widocznej treści wariantów B i C
for nazwa in ['wariant-b-taste.html', 'wariant-c-rozwiniete.html']:
    t = (OUT / nazwa).read_text(encoding='utf-8')
    zle = [z for z in ('—', '–') if z in t]
    print(f'{nazwa}: em/en-dash -> {zle if zle else "brak"}')
