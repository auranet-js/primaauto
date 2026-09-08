#!/usr/bin/env python3
"""Makieta J2 (08.09, runda 2 po uwagach Janka): układ B (grupy), nowa grupa „Drzwi i klapa",
3 pozycje z zapasu, flaga zawieszenia usunięta jako duplikat enuma, sekcje zwijane na desktopie.
Liczby z wp7j_asiaauto_specs / _asiaauto_extra_prep (status=publish), stan bazy 08.09.2026."""
import json, pathlib, unicodedata

D = json.load(open('/tmp/claude-1584/-home-host476470-projekty-primaauto/edfd0ba1-24f8-455b-a8f3-29461b5abdc8/scratchpad/dane.json'))
OUT = pathlib.Path('/home/host476470/domains/auratest.pl/public_html/fe4f58fec53ctmp')
TOTAL = D['total']

def n(x): return f"{int(x):,}".replace(",", " ")
def klucz(s):
    return ''.join(c for c in unicodedata.normalize('NFD', s.lower()) if unicodedata.category(c) != 'Mn').replace('ł', 'l')

# stan produkcji minus air_susp (duplikat enuma „Rodzaj zawieszenia" — ta sama kolumna źródłowa)
FLAGI = [f for f in D['flagi'] if f['k'] != 'air_susp']
NOWE = [
    ('electric_back_door',      'Drzwi i klapa',        'Elektryczna klapa bagażnika',              2366),
    ('electric_back_door_mem',  'Drzwi i klapa',        'Pamięć pozycji klapy',                     2172),
    ('hidden_door_handle',      'Drzwi i klapa',        'Ukryte klamki (elektryczne)',              1657),
    ('electric_door',           'Drzwi i klapa',        'Drzwi domykane elektrycznie', 1220),
    ('inductive_back_door',     'Drzwi i klapa',        'Klapa otwierana kopnięciem',                772),
    ('frameless_door',          'Drzwi i klapa',        'Drzwi bezramkowe',                          270),
    ('sliding_door_el',         'Drzwi i klapa',        'Elektryczne drzwi przesuwne',                53),
    ('wing_door',               'Drzwi i klapa',        'Drzwi motylkowe / nożycowe',                 52),
    ('karaoke',                 'Ekrany i multimedia',  'Karaoke',                                  1793),
    ('seat_vent_r',            'Fotele i kierownica',  'Wentylowane fotele tył',                    1242),
    ('rear_ac',                 'Komfort i elektryka',  'Klimatyzacja niezależna z tyłu',           1172),
]
FLAGI += [{'k': k, 'grupa': g, 'label': l, 'n': c, 'nowe': True} for k, g, l, c in NOWE]

KOLEJNOSC = ['Fotele i kierownica', 'Kamery i asystenci', 'Ekrany i multimedia', 'Komfort i elektryka', 'Drzwi i klapa']
ZAZNACZONE = {'cam_360', 'seat_massage_f', 'electric_door'}

def cbox(f):
    on = f['k'] in ZAZNACZONE
    nowe = ' cb--nowe' if f.get('nowe') else ''
    tag = '<i class="tag">nowy</i>' if f.get('nowe') else ''
    return (f'<label class="cb{" on" if on else ""}{nowe}"><input type="checkbox"{" checked" if on else ""}>'
            f'<span>{f["label"]}</span>{tag}<b>{n(f["n"])}</b></label>')

grupy, bloki = {}, []
for f in FLAGI: grupy.setdefault(f['grupa'], []).append(f)
for g in KOLEJNOSC:
    ff = sorted(grupy[g], key=lambda f: klucz(f['label']))
    nowych = sum(1 for f in ff if f.get('nowe'))
    plus = f' <span class="plus">+{nowych}</span>' if nowych else ''
    bloki.append(f'<div class="pod"><h3>{g} <em>{len(ff)}</em>{plus}</h3>'
                 f'<div class="cbs">{"".join(cbox(f) for f in ff)}</div></div>')
WYPOSAZENIE = ''.join(bloki)
ILE_FLAG = len(FLAGI)

def sel(label, val, on=False, ph=False, kropka=None, nowy=False):
    k = f'<i class="kropka" style="background:{kropka}"></i>' if kropka else ''
    t = '<i class="tag">nowy</i>' if nowy else ''
    return (f'<div class="pole"><label>{label}{t}</label><span class="sel{" on" if on else ""}{" ph" if ph else ""}">'
            f'{k}<span>{val}</span><b></b></span></div>')
def inp(label, val, unit, on=False):
    return (f'<div class="pole"><label>{label}</label><span class="inp{" on" if on else " ph"}">'
            f'<span>{val}</span><small>{unit}</small></span></div>')

NADWOZIE = (sel('Marka pojazdu', 'Wszystkie') + sel('Model pojazdu', 'Najpierw wybierz markę', ph=True)
            + sel('Rodzaj nadwozia', 'SUV, Kombi', on=True) + sel('Napęd 4x4', 'Wszystkie')
            + inp('Długość od', '4 800', 'mm') + inp('Szerokość do', '1 950', 'mm')
            + sel('Liczba miejsc', '5, 7') + sel('Liczba drzwi', 'Wszystkie', nowy=True)
            + sel('Typ drzwi', 'Wszystkie', nowy=True))
NAPED = (sel('Silnik', 'Elektryczny, PHEV', on=True) + inp('Moc od', '300', 'KM', on=True)
         + inp('Przyspieszenie 0–100 do', '5,0', 's') + inp('Bateria od', '60', 'kWh')
         + inp('Zasięg całkowity od', '1 000', 'km') + inp('Zasięg na prądzie (CLTC) od', '500', 'km'))
STYL = (sel('Kolor nadwozia', 'Czarny', on=True, kropka='#1A1A1A') + sel('Rodzaj zawieszenia', 'Pneumatyczne', on=True)
        + sel('Materiał tapicerki', 'Skóra naturalna') + sel('Marka nagłośnienia', 'Wszystkie'))

def sekcja(sid, tytul, tresc, klasa, podsum, ile=None, otwarta=True):
    licz = f'<span class="licz">{ile}</span>' if ile else ''
    return f'''<details class="sek {klasa}"{' open' if otwarta else ''}>
  <summary><span class="tyt">{tytul}</span>{licz}<span class="podsum">{podsum}</span><b class="chev"></b></summary>
  <div class="wnetrze">{tresc}</div></details>'''

OFERTY = ("<div class=\"oferty\">"
  "<span class='of on'><b>2 979</b><span>Do sprowadzenia z Chin</span><small>ok. 8-10 tygodni</small></span>"
  "<span class='of'><b>30</b><span>W drodze do Polski</span><small>odbiór za 2-4 tygodnie</small></span>"
  "<span class='of'><b>19</b><span>Na placu w Polsce</span><small>do obejrzenia od ręki</small></span>"
  "<span class='btn'>Pokaż 604 oferty</span></div>")
SEK_OFERTY = sekcja('s5', 'Oferty', OFERTY, 'sek--oferty', '')

HTML = f'''<!doctype html><html lang="pl"><meta charset="utf-8">
<title>Prima Auto — makieta J2: grupy, drzwi i klapa, zwijanie sekcji</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
*,*::before,*::after{{box-sizing:border-box}}
:root{{--r:6px;--granat:#1B2A4A;--linia:#E1E4E8;--szary:#5C6B7F;--akcent:#C92A2B}}
body{{margin:0;background:#F5F6F8;color:#2D3748;font:14px/1.45 Inter,-apple-system,'Segoe UI',sans-serif}}
.naglowek{{background:#9B0000;color:#fff;padding:14px 24px;font-weight:800;letter-spacing:.02em}}
.strona{{max-width:1240px;margin:0 auto;padding:22px 20px 90px}}
h1{{font-size:24px;margin:0 0 4px;color:var(--granat)}}
.lead{{margin:0 0 16px;color:var(--szary);max-width:80ch}}
.panel{{background:#fff;border:1px solid var(--linia);border-radius:var(--r);padding:0 20px}}
.paskiem{{display:flex;justify-content:flex-end;margin:0 0 8px}}
.zwin{{font-size:12.5px;color:var(--granat);border:1px solid var(--linia);background:#fff;border-radius:var(--r);padding:6px 12px;cursor:pointer;font-weight:600}}
/* --- sekcja zwijana --- */
.sek{{border-bottom:1px solid var(--linia)}}.sek:last-of-type{{border:0}}
.sek>summary{{display:flex;align-items:center;gap:10px;padding:14px 0;cursor:pointer;list-style:none;user-select:none}}
.sek>summary::-webkit-details-marker{{display:none}}
.sek>summary:hover .tyt{{color:var(--granat)}}
.tyt{{font-size:12px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--szary)}}
.licz{{background:var(--granat);color:#fff;border-radius:99px;min-width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:11.5px;font-weight:700;padding:0 6px}}
.podsum{{flex:1;color:var(--szary);font-size:12.5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}}
.sek[open] .podsum{{visibility:hidden}}
.chev{{width:8px;height:8px;border-right:2px solid #9AA5B1;border-bottom:2px solid #9AA5B1;transform:rotate(45deg);flex:none;margin-right:2px;transition:transform .15s}}
.sek[open] .chev{{transform:rotate(-135deg)}}
.wnetrze{{padding:0 0 18px}}
.siatka{{display:grid;gap:12px}}
.sek--nadwozie .siatka{{grid-template-columns:repeat(5,1fr)}}
.sek--naped .siatka{{grid-template-columns:repeat(6,1fr)}}
.sek--styl .siatka{{grid-template-columns:repeat(4,1fr)}}
.pole{{display:flex;flex-direction:column;gap:5px;min-width:0}}
.pole label{{font-size:12px;font-weight:600;color:var(--granat);display:flex;align-items:center;gap:6px}}
.sel,.inp{{display:flex;align-items:center;height:42px;padding:0 12px;background:#fff;border:1px solid var(--linia);border-radius:var(--r);white-space:nowrap;overflow:hidden;gap:8px}}
.sel span{{flex:1;overflow:hidden;text-overflow:ellipsis}}
.sel b{{width:7px;height:7px;border-right:1.5px solid currentColor;border-bottom:1.5px solid currentColor;transform:rotate(45deg) translate(-1px,-2px);opacity:.55;flex:none}}
.inp{{justify-content:flex-end}}.inp small{{color:var(--szary)}}
.ph span{{color:#9AA5B1}}
.on{{border-color:var(--granat)}}.on>span{{color:var(--granat);font-weight:600}}
.kropka{{width:14px;height:14px;border-radius:50%;border:1px solid rgba(0,0,0,.15);flex:none}}
/* --- checkboxy w kolumnach --- */
.cbs{{display:grid;grid-template-columns:repeat(3,1fr);gap:2px 24px}}
.cb{{display:flex;align-items:center;gap:9px;padding:7px 8px 7px 2px;border-radius:var(--r);cursor:pointer;min-width:0}}
.cb:hover{{background:#F7F9FC}}
.cb input{{width:17px;height:17px;flex:none;accent-color:var(--granat);margin:0}}
.cb>span{{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}}
.cb b{{font-weight:600;color:#9AA5B1;font-variant-numeric:tabular-nums;font-size:12px}}
.cb.on>span{{color:var(--granat);font-weight:600}}
.tag{{font-style:normal;font-size:10px;font-weight:700;letter-spacing:.03em;text-transform:uppercase;color:#0F7B3E;background:#E7F5EC;border-radius:3px;padding:1px 5px;flex:none}}
.pod{{margin:0 0 16px}}.pod:last-child{{margin-bottom:0}}
.pod h3{{margin:0 0 4px;font-size:12.5px;font-weight:700;color:var(--granat)}}
.pod h3 em{{font-style:normal;color:#9AA5B1;font-weight:600;margin-left:4px}}
.plus{{color:#0F7B3E;font-weight:700;font-size:11.5px}}
/* --- stopka --- */
.oferty{{display:grid;grid-template-columns:repeat(3,1fr) auto;gap:12px}}
.of{{display:flex;flex-direction:column;justify-content:center;border:1px solid var(--linia);border-radius:var(--r);padding:8px 12px}}
.of b{{font-size:18px;color:var(--granat)}}.of small{{color:var(--szary)}}
.of.on{{border-color:var(--granat);background:#F2F5FA}}
.btn{{display:flex;align-items:center;justify-content:center;background:var(--akcent);color:#fff;font-weight:700;border-radius:var(--r);padding:0 26px}}
.pasek{{margin-top:14px;background:#fff;border:1px solid var(--linia);border-radius:var(--r);padding:10px 14px;display:flex;flex-wrap:wrap;gap:8px;align-items:center}}
.chip{{display:inline-flex;align-items:center;gap:7px;border:1px solid var(--linia);border-radius:99px;padding:5px 11px;font-size:12.5px;background:#F7F9FC}}
.chip i{{font-style:normal;color:#9AA5B1;font-weight:700}}
.reset{{color:var(--akcent);font-size:12.5px;font-weight:600;margin-left:auto}}
.uwagi{{margin:26px 0 0;padding:0;list-style:none;color:var(--szary);font-size:13px;max-width:100ch}}
.uwagi li{{padding:5px 0 5px 16px;position:relative}}
.uwagi li::before{{content:"—";position:absolute;left:0;color:#9AA5B1}}
.uwagi b{{color:var(--granat)}}
@media (max-width:1100px){{
  .sek--nadwozie .siatka,.sek--naped .siatka{{grid-template-columns:repeat(3,1fr)}}
  .sek--styl .siatka{{grid-template-columns:repeat(2,1fr)}}
  .cbs{{grid-template-columns:repeat(2,1fr)}}
  .oferty{{grid-template-columns:repeat(2,1fr)}}
  .podsum{{display:none}}
}}
</style>
<div class="naglowek">PRIMA AUTO — makieta J2 (08.09.2026)</div>
<div class="strona">
<h1>Filtry w grupach, sekcje zwijane, nowa grupa „Drzwi i klapa"</h1>
<p class="lead">Układ B wg Twojego wyboru. Wyposażenie ma teraz {ILE_FLAG} cech w pięciu grupach —
11 dołożonych, „Zawieszenie pneumatyczne" usunięte jako duplikat. Liczby to realne oferty
z danej cechy w bazie ({n(TOTAL)} ofert). Kliknij nagłówek sekcji, żeby ją zwinąć.</p>

<div class="paskiem"><button class="zwin" id="zwinBtn">Zwiń filtry</button></div>
<div class="panel">
{sekcja('s1', 'Nadwozie', f'<div class="siatka">{NADWOZIE}</div>', 'sek--nadwozie', 'SUV, Kombi', ile=1)}
{sekcja('s2', 'Silnik i osiągi', f'<div class="siatka">{NAPED}</div>', 'sek--naped', 'Elektryczny, PHEV · od 300 KM', ile=2)}
{sekcja('s3', 'Styl i komfort', f'<div class="siatka">{STYL}</div>', 'sek--styl', 'Czarny · Pneumatyczne', ile=2)}
{sekcja('s4', f'Wyposażenie i technologie', WYPOSAZENIE, 'sek--tech',
        'Kamera 360°, Masaż foteli przód, Drzwi domykane elektrycznie', ile=3)}
{SEK_OFERTY}
</div>

<div class="pasek">
  <span class="chip">Nadwozie: SUV, Kombi <i>×</i></span>
  <span class="chip">Silnik: Elektryczny, PHEV <i>×</i></span>
  <span class="chip">Moc od 300 KM <i>×</i></span>
  <span class="chip">Kolor: Czarny <i>×</i></span>
  <span class="chip">Zawieszenie: Pneumatyczne <i>×</i></span>
  <span class="chip">Kamera 360° <i>×</i></span>
  <span class="chip">Masaż foteli przód <i>×</i></span>
  <span class="chip">Drzwi domykane elektrycznie <i>×</i></span>
  <span class="reset">Wyczyść filtry</span>
</div>

<ul class="uwagi">
  <li><b>Zwijanie.</b> Klik w nagłówek zwija sekcję. Zwinięta pokazuje w jednej linii, co jest w niej wybrane,
  a plakietka przy nazwie mówi ile filtrów działa — nic nie znika po cichu. Stan zapamiętuje przeglądarka,
  więc wracasz do swojego układu. „Zwiń filtry" składa panel do pięciu wierszy.</li>
  <li><b>Zawieszenie pneumatyczne wyleciało z wyposażenia.</b> Sprawdzone w kodzie: „Rodzaj zawieszenia →
  Pneumatyczne" powstaje z tej samej flagi (<code>air_suspension</code>) — te same 882 oferty w dwóch miejscach.
  Enum jest bogatszy, bo ma jeszcze „Adaptacyjne" (644 oferty), więc został on.</li>
  <li><b>Liczba drzwi i Typ drzwi</b> poszły do sekcji Nadwozie jako listy, nie do checkboxów — to nie są
  cechy tak/nie. Zastrzeżenie: 2 760 z 2 919 aut ma zwykłe drzwi, więc „Typ drzwi" przyda się tylko komuś,
  kto celowo szuka przesuwnych (30) albo motylkowych (2).</li>
  <li><b>Czego nie dołożyłem z drzwi:</b> „Elektryczne domykanie klapy" (<code>power_suction_tailgate</code>,
  73 oferty, 2,4%) — nie było go w poprzedniej liście, a dubluje się w odbiorze z „Elektryczną klapą". Powiedz,
  jeśli ma wejść.</li>
  <li><b>Telefon bez zmian</b> — ten sam HTML zwija się do arkuszy od dołu (ustalenie z 03.09).</li>
</ul>
</div>
<script>
const KLUCZ = 'aas-makieta-j2-zwiniete';
const sek = [...document.querySelectorAll('details.sek')];
sek.forEach((d, i) => {{
  d.id = 'sek' + i;
  d.addEventListener('toggle', zapisz);
}});
try {{
  const stan = JSON.parse(localStorage.getItem(KLUCZ) || '[]');
  if (stan.length) sek.forEach((d, i) => d.open = !stan.includes(i));
}} catch (e) {{}}
function zapisz() {{
  try {{ localStorage.setItem(KLUCZ, JSON.stringify(sek.map((d, i) => d.open ? -1 : i).filter(i => i >= 0))); }} catch (e) {{}}
  const btn = document.getElementById('zwinBtn');
  btn.textContent = sek.some(d => d.open) ? 'Zwiń filtry' : 'Rozwiń filtry';
}}
document.getElementById('zwinBtn').addEventListener('click', () => {{
  const zwijamy = sek.some(d => d.open);
  sek.forEach(d => d.open = !zwijamy);
  zapisz();
}});
zapisz();
</script>
</html>'''

p = OUT / 'primaauto-makieta-j2-grupy-drzwi-zwijanie-2026-09-08.html'
p.write_text(HTML, encoding='utf-8')
print(p)
