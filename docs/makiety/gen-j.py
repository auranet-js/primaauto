#!/usr/bin/env python3
"""Makieta J (08.09): wyposażenie w kolumnach checkboxów wzorem mobile.de, desktop/tablet.
Liczby z wp7j_asiaauto_specs (status=publish), skład filtrów = stan v0.38.2."""
import json, pathlib, unicodedata

D = json.load(open('/tmp/claude-1584/-home-host476470-projekty-primaauto/edfd0ba1-24f8-455b-a8f3-29461b5abdc8/scratchpad/dane.json'))
OUT = pathlib.Path('/home/host476470/domains/auratest.pl/public_html/fe4f58fec53ctmp')
TOTAL = D['total']
FLAGI = D['flagi']

def n(x): return f"{int(x):,}".replace(",", " ")
def klucz(s):
    return ''.join(c for c in unicodedata.normalize('NFD', s.lower()) if unicodedata.category(c) != 'Mn').replace('ł','l')

def cbox(f, on=False):
    return (f'<label class="cb{" on" if on else ""}"><input type="checkbox"{" checked" if on else ""}>'
            f'<span>{f["label"]}</span><b>{n(f["n"])}</b></label>')

ZAZNACZONE = {'cam_360', 'seat_massage_f'}
def lista(items):
    return ''.join(cbox(f, f['k'] in ZAZNACZONE) for f in items)

# wariant A — alfabetycznie, jedna siatka 3 kolumny (mobile.de „Extras")
alfa = sorted(FLAGI, key=lambda f: klucz(f['label']))
WAR_A = f'<div class="cbs">{lista(alfa)}</div>'

# wariant B — podgrupy jak w FLAG_GROUPS, w każdej alfabetycznie
grupy = {}
for f in FLAGI: grupy.setdefault(f['grupa'], []).append(f)
WAR_B = ''.join(
    f'<div class="pod"><h3>{g} <em>{len(ff)}</em></h3><div class="cbs">{lista(sorted(ff, key=lambda f: klucz(f["label"])))}</div></div>'
    for g, ff in grupy.items())

def sel(label, val, on=False, ph=False, kropka=None):
    k = f'<i class="kropka" style="background:{kropka}"></i>' if kropka else ''
    return (f'<div class="pole"><label>{label}</label><span class="sel{" on" if on else ""}{" ph" if ph else ""}">'
            f'{k}<span>{val}</span><b></b></span></div>')
def inp(label, val, unit, on=False):
    return (f'<div class="pole"><label>{label}</label><span class="inp{" on" if on else " ph"}">'
            f'<span>{val}</span><small>{unit}</small></span></div>')

NADWOZIE = (sel('Marka pojazdu','Wszystkie') + sel('Model pojazdu','Najpierw wybierz markę', ph=True)
            + sel('Rodzaj nadwozia','SUV, Kombi', on=True) + sel('Napęd 4x4','Wszystkie')
            + inp('Długość od','4 800','mm') + inp('Szerokość do','1 950','mm')
            + sel('Liczba miejsc','5, 7'))
NAPED = (sel('Silnik','Elektryczny, PHEV', on=True) + inp('Moc od','300','KM', on=True)
         + inp('Przyspieszenie 0–100 do','5,0','s') + inp('Bateria od','60','kWh')
         + inp('Zasięg całkowity od','1 000','km') + inp('Zasięg na prądzie (CLTC) od','500','km'))
STYL = (sel('Kolor nadwozia','Czarny', on=True, kropka='#1A1A1A') + sel('Rodzaj zawieszenia','Pneumatyczne')
        + sel('Materiał tapicerki','Skóra naturalna') + sel('Marka nagłośnienia','Wszystkie'))

KAND_DRZWI = [
    ('Elektryczna klapa bagażnika', 'flaga', 2366, '78,2%'),
    ('Pamięć pozycji klapy', 'flaga', 2172, '71,8%'),
    ('Ukryte klamki (elektryczne)', 'flaga', 1657, '54,8%'),
    ('Drzwi domykane elektrycznie (soft-close)', 'flaga', 1220, '40,3%'),
    ('Klapa otwierana czujnikiem (kopnięciem)', 'flaga', 772, '25,5%'),
    ('Drzwi bezramkowe', 'flaga', 270, '8,9%'),
    ('Elektryczne drzwi przesuwne (MPV)', 'flaga', 53, '1,8%'),
    ('Drzwi motylkowe / nożycowe', 'flaga', 52, '1,7%'),
    ('Liczba drzwi', 'enum 5 / 4 / 2', 2946, '97,4%'),
    ('Typ drzwi', 'enum — 2 760 z 2 919 to zwykłe', 2919, '96,5%'),
]
KAND_RESZTA = [
    ('Liczba silników', 'enum 1–4', 2494, '82,4%'),
    ('Typ baterii', 'enum LFP / NMC', 2489, '82,3%'),
    ('Pojemność bagażnika', 'zakres, l', 2434, '80,4%'),
    ('Karaoke', 'flaga', 1793, '59,3%'),
    ('Podświetlenie ambientowe', 'flaga', 1415, '46,8%'),
    ('Wentylacja foteli z tyłu', 'flaga', 1242, '41,0%'),
    ('Klimatyzacja niezależna z tyłu', 'flaga', 1172, '38,7%'),
    ('Typ ekranu centralnego', 'enum LCD / AMOLED / OLED / MINI LED', 836, '27,6%'),
    ('Bagażnik przedni (frunk)', 'zakres, l', 613, '20,3%'),
]
def kandlista(items):
    return ''.join(
        f'<label class="cb cb--kand"><input type="checkbox"><span>{t}</span><em>{k}</em><b>{n(c)} · {p}</b></label>'
        for t, k, c, p in items)
kand_drzwi = kandlista(KAND_DRZWI)
kand_reszta = kandlista(KAND_RESZTA)

HTML = f'''<!doctype html><html lang="pl"><meta charset="utf-8">
<title>Prima Auto — makieta J: wyposażenie w kolumnach (desktop/tablet)</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
*,*::before,*::after{{box-sizing:border-box}}
:root{{--r:6px;--granat:#1B2A4A;--linia:#E1E4E8;--szary:#5C6B7F;--akcent:#C92A2B}}
body{{margin:0;background:#F5F6F8;color:#2D3748;font:14px/1.45 Inter,-apple-system,'Segoe UI',sans-serif}}
.naglowek{{background:#9B0000;color:#fff;padding:14px 24px;font-weight:800;letter-spacing:.02em}}
.strona{{max-width:1240px;margin:0 auto;padding:22px 20px 90px}}
h1{{font-size:24px;margin:0 0 4px;color:var(--granat)}}
.lead{{margin:0 0 18px;color:var(--szary);max-width:78ch}}
.panel{{background:#fff;border:1px solid var(--linia);border-radius:var(--r);padding:0 20px}}
.sek{{padding:16px 0 18px;border-bottom:1px solid var(--linia)}}.sek:last-child{{border:0}}
.sek h2{{margin:0 0 10px;font-size:12px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--szary)}}
.sek h2 em{{font-style:normal;color:#9AA5B1;font-weight:600}}
.siatka{{display:grid;gap:12px}}
.sek--nadwozie .siatka{{grid-template-columns:repeat(7,1fr)}}
.sek--naped .siatka{{grid-template-columns:repeat(6,1fr)}}
.sek--styl .siatka{{grid-template-columns:repeat(4,1fr)}}
.pole{{display:flex;flex-direction:column;gap:5px;min-width:0}}
.pole label{{font-size:12px;font-weight:600;color:var(--granat)}}
.sel,.inp{{display:flex;align-items:center;height:42px;padding:0 12px;background:#fff;border:1px solid var(--linia);border-radius:var(--r);white-space:nowrap;overflow:hidden;gap:8px}}
.sel span{{flex:1;overflow:hidden;text-overflow:ellipsis}}
.sel b{{width:7px;height:7px;border-right:1.5px solid currentColor;border-bottom:1.5px solid currentColor;transform:rotate(45deg) translate(-1px,-2px);opacity:.55;flex:none}}
.inp{{justify-content:flex-end}}.inp small{{color:var(--szary)}}
.ph span{{color:#9AA5B1}}
.on{{border-color:var(--granat)}}.on>span{{color:var(--granat);font-weight:600}}
.kropka{{width:14px;height:14px;border-radius:50%;border:1px solid rgba(0,0,0,.15);flex:none}}
/* --- kolumny checkboxów (wzór mobile.de „Extras") --- */
.cbs{{display:grid;grid-template-columns:repeat(3,1fr);gap:2px 24px}}
.cb{{display:flex;align-items:center;gap:9px;padding:7px 8px 7px 2px;border-radius:var(--r);cursor:pointer;min-width:0}}
.cb:hover{{background:#F7F9FC}}
.cb input{{width:17px;height:17px;flex:none;accent-color:var(--granat);margin:0}}
.cb>span{{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}}
.cb b{{font-weight:600;color:#9AA5B1;font-variant-numeric:tabular-nums;font-size:12px}}
.cb.on>span{{color:var(--granat);font-weight:600}}
.pod{{margin:0 0 14px}}
.pod h3{{margin:0 0 4px;font-size:12.5px;font-weight:700;color:var(--granat)}}
.pod h3 em{{font-style:normal;color:#9AA5B1;font-weight:600;margin-left:4px}}
/* przełącznik wariantów */
.przel{{display:flex;gap:8px;align-items:center;margin:0 0 14px}}
.przel span{{font-size:12px;color:var(--szary);margin-right:4px}}
.przel label{{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border:1px solid var(--linia);border-radius:var(--r);cursor:pointer;background:#fff;font-size:13px}}
.przel input{{accent-color:var(--granat);margin:0}}
.przel label:has(input:checked){{border-color:var(--granat);background:#F2F5FA;font-weight:600;color:var(--granat)}}
#warB{{display:none}}
body:has(#wB:checked) #warA{{display:none}}
body:has(#wB:checked) #warB{{display:block}}
/* kandydaci */
.kand{{margin-top:18px;padding:14px 16px;border:1px dashed #C6CEDA;border-radius:var(--r);background:#FAFBFD}}
.kand h3{{margin:0 0 2px;font-size:13px;color:var(--granat)}}
.kand p{{margin:0 0 10px;font-size:12.5px;color:var(--szary)}}
.kand h4{{margin:12px 0 4px;font-size:12.5px;color:var(--granat)}}
.uwaga-drzwi{{margin:12px 0 0;font-size:12.5px;line-height:1.5;color:var(--szary)}}
.uwaga-drzwi code{{background:#fff;border:1px solid var(--linia);border-radius:3px;padding:1px 4px}}
.cb--kand b{{white-space:nowrap}}
.cb--kand em{{font-style:normal;font-size:11.5px;color:#9AA5B1}}
.cb--kand>span{{flex:none}}
.cb--kand{{opacity:.85}}
/* stopka wyników */
.oferty{{display:grid;grid-template-columns:repeat(3,1fr) auto;gap:12px;align-items:stretch}}
.of{{display:flex;flex-direction:column;justify-content:center;border:1px solid var(--linia);border-radius:var(--r);padding:8px 12px}}
.of b{{font-size:18px;color:var(--granat)}}.of small{{color:var(--szary)}}
.of.on{{border-color:var(--granat);background:#F2F5FA}}
.btn{{display:flex;align-items:center;justify-content:center;background:var(--akcent);color:#fff;font-weight:700;border-radius:var(--r);padding:0 26px}}
.pasek{{margin-top:14px;background:#fff;border:1px solid var(--linia);border-radius:var(--r);padding:10px 14px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;box-shadow:0 -2px 12px rgba(27,42,74,.07)}}
.chip{{display:inline-flex;align-items:center;gap:7px;border:1px solid var(--linia);border-radius:99px;padding:5px 11px;font-size:12.5px;background:#F7F9FC}}
.chip i{{font-style:normal;color:#9AA5B1;font-weight:700}}
.reset{{color:var(--akcent);font-size:12.5px;font-weight:600;margin-left:auto}}
.uwagi{{margin:26px 0 0;padding:0;list-style:none;color:var(--szary);font-size:13px}}
.uwagi li{{padding:4px 0 4px 16px;position:relative}}
.uwagi li::before{{content:"—";position:absolute;left:0;color:#9AA5B1}}
@media (max-width:1100px){{
  .sek--nadwozie .siatka{{grid-template-columns:repeat(4,1fr)}}
  .sek--naped .siatka{{grid-template-columns:repeat(3,1fr)}}
  .sek--styl .siatka{{grid-template-columns:repeat(2,1fr)}}
  .cbs{{grid-template-columns:repeat(2,1fr)}}
  .oferty{{grid-template-columns:repeat(2,1fr)}}
}}
</style>
<div class="naglowek">PRIMA AUTO — makieta J (08.09.2026)</div>
<div class="strona">
<h1>Wyszukiwarka: wyposażenie w kolumnach, wzorem mobile.de</h1>
<p class="lead">Stan filtrów zgodny z produkcją (wtyczka 0.38.2). Liczby przy pozycjach to realna liczba
opublikowanych ofert z daną cechą — {n(TOTAL)} ofert w bazie. Zmiana dotyczy wyłącznie desktopu i tabletu;
telefon zostaje na arkuszach od dołu. Przełącznik pod sekcją wyposażenia pokazuje dwa układy do wyboru.</p>

<div class="panel">
  <section class="sek sek--nadwozie"><h2>Nadwozie</h2><div class="siatka">{NADWOZIE}</div></section>
  <section class="sek sek--naped"><h2>Silnik i osiągi</h2><div class="siatka">{NAPED}</div></section>
  <section class="sek sek--styl"><h2>Styl i komfort</h2><div class="siatka">{STYL}</div></section>
  <section class="sek sek--tech">
    <h2>Wyposażenie i technologie <em>— {len(FLAGI)} cech</em></h2>
    <div class="przel">
      <span>Układ:</span>
      <label><input type="radio" name="war" id="wA" checked> A — alfabetycznie, jedna lista</label>
      <label><input type="radio" name="war" id="wB"> B — z podziałem na grupy jak dziś</label>
    </div>
    <div id="warA">{WAR_A}</div>
    <div id="warB">{WAR_B}</div>
    <div class="kand">
      <h3>Do dołożenia — zmierzone na całej bazie ({n(TOTAL)} ofert publish, nie na próbce)</h3>
      <p>Liczba ofert z cechą i udział w bazie. Filtr ma sens tam, gdzie udział jest bliski połowie —
      cecha obecna w 90% ofert niczego nie odsiewa. Zaznacz, co wchodzi.</p>
      <h4>Drzwi i klapa</h4>
      <div class="cbs">{kand_drzwi}</div>
      <h4>Pozostałe</h4>
      <div class="cbs">{kand_reszta}</div>
      <p class="uwaga-drzwi"><b>Uwaga do „elektrycznego otwierania drzwi":</b> źródło nie zna takiej cechy dla
      drzwi bocznych — poza MPV-ami (53 oferty, elektryczne drzwi przesuwne). Klucz, który w poprzedniej wersji
      makiety nazwałem „Drzwi elektryczne", to w oryginale <code>电动吸合车门</code>, czyli elektryczne
      <b>domykanie</b> (soft-close), nie otwieranie. Elektryczne otwieranie w tych autach dotyczy klapy bagażnika.</p>
    </div>
  </section>
  <section class="sek">
    <h2>Oferty</h2>
    <div class="oferty">
      <span class="of on"><b>2 979</b><span>Do sprowadzenia z Chin</span><small>ok. 8–10 tygodni</small></span>
      <span class="of"><b>30</b><span>W drodze do Polski</span><small>odbiór za 2–4 tygodnie</small></span>
      <span class="of"><b>19</b><span>Na placu w Polsce</span><small>do obejrzenia od ręki</small></span>
      <span class="btn">Pokaż 1 208 ofert</span>
    </div>
  </section>
</div>

<div class="pasek">
  <span class="chip">Nadwozie: SUV, Kombi <i>×</i></span>
  <span class="chip">Silnik: Elektryczny, PHEV <i>×</i></span>
  <span class="chip">Moc od 300 KM <i>×</i></span>
  <span class="chip">Kolor: Czarny <i>×</i></span>
  <span class="chip">Kamera 360° <i>×</i></span>
  <span class="chip">Masaż foteli przód <i>×</i></span>
  <span class="reset">Wyczyść filtry</span>
</div>

<ul class="uwagi">
  <li>Wariant A odpowiada sekcji „Extras" z mobile.de: jedna alfabetyczna lista, wzrok leci w dół kolumny.</li>
  <li>Wariant B zachowuje dzisiejszy podział na cztery grupy — krótsze listy, ale cztery nagłówki do przeskoczenia.</li>
  <li>Telefon bez zmian: ten sam HTML zwija się do arkusza od dołu (ustalenie z 03.09).</li>
</ul>
</div></html>'''

p = OUT / 'primaauto-makieta-j-wyposazenie-kolumny-2026-09-08.html'
p.write_text(HTML, encoding='utf-8')
print(p)
