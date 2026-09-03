#!/usr/bin/env python3
"""
Makiety E/F/G paska filtrów (T-116 domknięcie, 2026-09-03) — po odrzuceniu A–D i bocznej kolumny.
Wsad od Janka: pasek GÓRNY jak w 0.36, jak najwięcej filtrów widocznych od razu, zwijane dopiero
przy nadmiarze; ma pozwolić złożyć „auto marzeń" (PHEV/EREV, 5 miejsc, ≥5 m, ≤4,5 s do setki,
nagłośnienie, duże felgi, skóra, czarny, masaż na wszystkich fotelach, przebieg ≤15 tys.,
zasięg łączny ≥1000 km). Wzorce: mobile.de (szukaj szczegółowo) i Otomoto (rząd selectów + „Filtry").
Liczby prawdziwe (dane-mockup.json + pomiar extra_prep 03.09: 985 ofert ≥5 m, 968 z masażem na obu
rzędach, 753 z zasięgiem łącznym, 142 spełnia komplet kryteriów).
"""
import json, pathlib

D = json.load(open('/home/host476470/projekty/primaauto/tmp/t116e3/dane-mockup.json'))
OUT = pathlib.Path('/home/host476470/domains/auratest.pl/public_html/fe4f58fec53ctmp/primaauto-makiety')

def n(x): return f"{int(x):,}".replace(",", " ")
LAB = D['labels']
MARKI = [(k, LAB['make'].get(k, k), v) for k, v in list(D['enum']['make'].items())[:12]]
PALIWA = [(k, LAB['fuel'].get(k, k), v) for k, v in D['enum']['fuel'].items()][:6]
NADWOZIA = [(k, LAB['body'].get(k, k), v) for k, v in D['enum']['body'].items()][:6]
TAPICERKI = [('skora','Skóra naturalna'),('ekoskora','Ekoskóra'),('alcantara','Alcantara / nubuk'),('tkanina','Tkanina')]
NAGLOSNIENIE = [('HUAWEI SOUND',194),('Dynaudio',240),('Devialet',119),('Harman/Kardon',54),('Sony',105),('Bose',48),('Meridian',30),('Yamaha',27)]
KOLORY = [('white','#FFFFFF','Biały'),('black','#1A1A1A','Czarny'),('silver','#C0C0C0','Srebrny'),('dark-gray','#555','Ciemnoszary'),
          ('blue','#2563EB','Niebieski'),('red','#DC2626','Czerwony'),('green','#16A34A','Zielony'),('champagne','#D4C5A9','Szampan'),
          ('purple','#7C3AED','Fiolet'),('brown','#8B4513','Brąz')]
WYP = [('Masaż foteli przód',1681,True),('Masaż foteli tył',994,True),('Wentylowane fotele',2540,False),('Kamera 360°',2715,False),
       ('Lidar',1341,False),('AR-HUD',740,False),('Zawieszenie pneumatyczne',806,False),('Pompa ciepła',1300,False),
       ('V2L',2290,False),('Ładowanie 800 V',860,False),('Lodówka',800,False),('Tryb wartownika',2070,False),
       ('Podgrzewana kierownica',1990,False),('Fotel zero gravity',710,False),('Karaoke',1780,False),('Dolby Atmos',905,False)]
TOTAL = n(D['total'])
KARTY = [
    ("Zeekr 9X 2026 Ultra 70kWh 6-osobowy", "448 000", "2026 r.", "100 km", "5 239 mm", "3,1 s", "PHEV", "1 240 km", "Yamaha · 30 gł."),
    ("Denza N9 DM-i 2025 Premium", "279 000", "2025 r.", "12 000 km", "5 258 mm", "3,9 s", "PHEV", "1 302 km", "Devialet · 28 gł."),
    ("AITO M9 2025 EREV Ultra 52 kWh", "357 000", "2025 r.", "100 km", "5 230 mm", "4,4 s", "EREV", "1 362 km", "HUAWEI SOUND · 25 gł."),
]
def karty(cls=''):
    o = []
    for t, cena, rok, przeb, dl, przysp, nap, zas, sound in KARTY:
        o.append(f'''<article class="karta {cls}"><div class="karta__foto" aria-hidden="true"></div><div class="karta__tresc">
<h3 class="karta__tytul">{t}</h3><p class="karta__cena">{cena} PLN</p>
<p class="karta__spec">{rok} <i>·</i> {przeb} <i>·</i> {przysp} <i>·</i> {nap} <i>·</i> {dl} <i>·</i> zasięg {zas}</p>
<p class="karta__fajki">✓ masaż przód i tył &nbsp;✓ {sound} &nbsp;✓ skóra &nbsp;✓ felgi 21"</p>
<div class="karta__akcje"><span class="btn btn--glowny">Szczegóły</span><span class="btn btn--drugi">Zamów</span></div></div></article>''')
    return ''.join(o)

BAZA = '''
*,*::before,*::after{box-sizing:border-box}
body{margin:0;background:#F5F6F8;color:#2D3748;font:14px/1.45 Inter,-apple-system,'Segoe UI',sans-serif}
.pasek-marki{background:#9B0000;color:#fff;padding:14px 24px;font-weight:800;letter-spacing:.02em;display:flex;justify-content:space-between}
.pasek-marki span{font-weight:400;opacity:.85;font-size:13px}
.strona{max-width:1280px;margin:0 auto;padding:22px 20px 60px}
h1{font-size:28px;margin:0 0 6px;color:#1B2A4A;letter-spacing:-.01em}
.wariant-info{background:#fff;border-left:3px solid #1B2A4A;padding:10px 14px;margin:0 0 18px;font-size:13px;color:#5C6B7F}
.wariant-info b{color:#1B2A4A}
.karta{display:flex;gap:16px;background:#fff;border:1px solid #E1E4E8;border-radius:6px;overflow:hidden;margin-bottom:12px}
.karta__foto{width:230px;flex:0 0 230px;background:linear-gradient(135deg,#dfe3e8,#c9d0d8)}
.karta__tresc{padding:14px 16px 16px;flex:1}
.karta__tytul{margin:0 0 6px;font-size:16px;color:#1B2A4A}
.karta__cena{margin:0 0 6px;font-size:19px;font-weight:700;color:#C92A2B}
.karta__spec{margin:0 0 4px;font-size:14px;font-weight:600}
.karta__spec i{color:#A0AAB6;font-style:normal;padding:0 2px}
.karta__fajki{margin:0 0 12px;font-size:13px;color:#276749}
.karta__akcje{display:flex;gap:8px}
.btn{display:inline-flex;align-items:center;justify-content:center;padding:9px 16px;border-radius:6px;font-size:14px;font-weight:600;white-space:nowrap}
.btn--glowny{background:#1B2A4A;color:#fff}.btn--drugi{border:1px solid #C92A2B;color:#C92A2B}
.btn--akcja{background:#C92A2B;color:#fff;font-size:15px;padding:11px 22px}
/* kontrolki wspólne */
.pole{display:flex;flex-direction:column;gap:5px;min-width:0}
.pole>label,.pole>.lbl{font-size:11.5px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:#1B2A4A}
.sel,.inp{display:flex;align-items:center;justify-content:space-between;height:40px;padding:0 11px;background:#fff;border:1px solid #D8DDE3;border-radius:6px;color:#2D3748;white-space:nowrap;overflow:hidden}
.sel i{width:7px;height:7px;border-right:1.5px solid currentColor;border-bottom:1.5px solid currentColor;transform:rotate(45deg) translate(-1px,-2px);opacity:.55;flex:none;margin-left:8px}
.sel.on,.inp.on{border-color:#1B2A4A;color:#1B2A4A;font-weight:600}
.inp{justify-content:flex-end;gap:6px}.inp small{color:#5C6B7F;font-weight:400}.inp .ph{color:#9AA5B1;font-weight:400}
.para{display:flex;gap:6px;align-items:center}.para .inp{flex:1}.para em{color:#9AA5B1;font-style:normal}
.chip{display:inline-flex;align-items:center;gap:6px;height:34px;padding:0 12px;background:#fff;border:1px solid #D8DDE3;border-radius:17px;font-size:13px;white-space:nowrap}
.chip b{color:#5C6B7F;font-weight:400;font-size:12px}.chip.on{background:#1B2A4A;border-color:#1B2A4A;color:#fff}.chip.on b{color:rgba(255,255,255,.75)}
.chip.on::before{content:'✓';font-size:12px}
.kolory{display:flex;gap:7px;flex-wrap:wrap;align-items:center;min-height:40px}
.kol{width:26px;height:26px;border-radius:50%;border:2px solid transparent;display:flex;align-items:center;justify-content:center}
.kol i{width:18px;height:18px;border-radius:50%;display:block;box-shadow:inset 0 0 0 1px rgba(0,0,0,.12)}
.kol.on{border-color:#1B2A4A}.kol.on::after{content:'✓';position:absolute;color:#fff;font-size:11px;font-weight:700}
.kol{position:relative}
.wyczysc{color:#C92A2B;font-weight:600;text-decoration:underline;white-space:nowrap}
.toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 0;margin:6px 0 12px;border-bottom:1px solid #D8DDE3}
.licznik b{font-size:20px;font-weight:700;color:#1B2A4A}.licznik span{color:#5C6B7F}
.sort{border:1px solid #D8DDE3;border-radius:6px;padding:8px 12px;background:#fff}
.kryteria{display:flex;flex-wrap:wrap;gap:6px;margin:0 0 14px}
.kryt{display:inline-flex;align-items:center;gap:6px;height:28px;padding:0 10px;background:#EEF1F5;border-radius:14px;font-size:12.5px;color:#1B2A4A}
.kryt s{text-decoration:none;color:#5C6B7F;font-weight:700}
.sekcja-t{font-size:12px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#5C6B7F;margin:0 0 10px}
@media (max-width:768px){.karta{flex-direction:column}.karta__foto{width:100%;height:170px;flex:none}.strona{padding:16px 12px 90px}h1{font-size:24px}}
'''

def kolory_html(on='black'):
    return '<div class="kolory">' + ''.join(f'<span class="kol{" on" if k==on else ""}" title="{nm}"><i style="background:{hx}"></i></span>' for k,hx,nm in KOLORY) + '</div>'
def wyp_chips(ile=None, cls='chip'):
    lst = WYP if ile is None else WYP[:ile]
    return ''.join(f'<span class="{cls}{" on" if on else ""}">{t} <b>{n(c)}</b></span>' for t,c,on in lst)
KRYTERIA = ['PHEV / EREV','5 miejsc +','Długość od 5,0 m','0–100 do 4,5 s','Zasięg łączny od 1 000 km','Przebieg do 15 000 km',
            'Felgi od 20"','Skóra naturalna','Czarny','Masaż przód','Masaż tył','Nagłośnienie: Devialet +2']
def kryteria_html():
    return '<div class="kryteria">' + ''.join(f'<span class="kryt">{k} <s>×</s></span>' for k in KRYTERIA) + '<span class="wyczysc" style="margin-left:6px">Wyczyść wszystko</span></div>'

# ============================================================ E — Otomoto
E = f'''<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Wariant E — Otomoto</title>
<style>{BAZA}
.panel{{background:#fff;border:1px solid #D8DDE3;border-radius:8px;padding:16px;margin-bottom:14px}}
.rzad{{display:grid;grid-template-columns:repeat(6,1fr) auto;gap:10px;align-items:end}}
.rzad2{{display:grid;grid-template-columns:repeat(6,1fr);gap:10px 12px;margin-top:14px;padding-top:14px;border-top:1px dashed #D8DDE3}}
.rzad3{{display:grid;grid-template-columns:repeat(4,1fr);gap:10px 12px;margin-top:12px}}
.rozwin{{display:flex;justify-content:space-between;align-items:center;margin-top:12px;font-size:13px}}
.rozwin a{{color:#1B2A4A;font-weight:600;text-decoration:underline}}
.chips{{display:flex;flex-wrap:wrap;gap:6px}}
@media (max-width:768px){{.rzad,.rzad2,.rzad3{{grid-template-columns:1fr 1fr}}.rzad .btn--akcja{{grid-column:1/-1}}.chips{{flex-wrap:nowrap;overflow-x:auto;padding-bottom:4px}}}}
</style>
<div class="pasek-marki">PRIMA-AUTO <span>makieta E · wzorzec: Otomoto</span></div>
<div class="strona">
<h1>Wyszukiwarka</h1>
<p class="wariant-info"><b>E — Otomoto.</b> Jeden rząd sześciu podstawowych filtrów i czerwony przycisk, pod nim „Więcej filtrów" rozwinięte na stałe w tej makiecie (w serwisie zwijane, z liczbą aktywnych). Wybrane kryteria wracają jako usuwalne pastylki nad wynikami. Stan: auto marzeń złożone, 142 oferty.</p>
<div class="panel">
  <div class="rzad">
    <div class="pole"><label>Marka</label><span class="sel">Wszystkie<i></i></span></div>
    <div class="pole"><label>Model</label><span class="sel" style="color:#9AA5B1">Najpierw marka<i></i></span></div>
    <div class="pole"><label>Napęd</label><span class="sel on">PHEV, EREV<i></i></span></div>
    <div class="pole"><label>Nadwozie</label><span class="sel">Wszystkie<i></i></span></div>
    <div class="pole"><label>Cena do</label><span class="inp"><span class="ph">1 068 000</span><small>zł</small></span></div>
    <div class="pole"><label>Rocznik od</label><span class="sel">2022<i></i></span></div>
    <span class="btn btn--akcja">Pokaż 142 oferty</span>
  </div>
  <div class="rzad2">
    <div class="pole"><label>Dostępność</label><span class="sel">Wszystkie · {TOTAL}<i></i></span></div>
    <div class="pole"><label>Przebieg do</label><span class="inp on">15 000<small>km</small></span></div>
    <div class="pole"><label>Miejsca od</label><span class="sel on">5<i></i></span></div>
    <div class="pole"><label>Długość od</label><span class="inp on">5,0<small>m</small></span></div>
    <div class="pole"><label>0–100 km/h do</label><span class="inp on">4,5<small>s</small></span></div>
    <div class="pole"><label>Zasięg łączny od</label><span class="inp on">1 000<small>km</small></span></div>
    <div class="pole"><label>Bateria od</label><span class="inp"><span class="ph">1,6</span><small>kWh</small></span></div>
    <div class="pole"><label>Moc od</label><span class="inp"><span class="ph">95</span><small>KM</small></span></div>
    <div class="pole"><label>Felgi od</label><span class="sel on">20"<i></i></span></div>
    <div class="pole"><label>Tapicerka</label><span class="sel on">Skóra naturalna<i></i></span></div>
    <div class="pole"><label>Nagłośnienie</label><span class="sel on">Devialet +2<i></i></span></div>
    <div class="pole"><label>Kolor nadwozia</label>{kolory_html()}</div>
  </div>
  <div class="rzad3" style="grid-template-columns:1fr">
    <div class="pole"><span class="lbl">Wyposażenie</span><div class="chips">{wyp_chips()}</div></div>
  </div>
  <div class="rozwin"><a href="#">Zwiń filtry ▴</a><span class="wyczysc">Wyczyść (12)</span></div>
</div>
{kryteria_html()}
<div class="toolbar"><p class="licznik" style="margin:0"><b>142</b> <span>oferty spełniają wszystkie kryteria</span></p><span class="sort">Sortuj: Najnowsze</span></div>
{karty()}
</div>'''

# ============================================================ F — mobile.de „szukaj szczegółowo"
def sekcja(tytul, inner, cols=4):
    return f'<section class="sek"><p class="sekcja-t">{tytul}</p><div class="siatka" style="grid-template-columns:repeat({cols},1fr)">{inner}</div></section>'
F_pod = f'''
<div class="pole"><label>Marka</label><span class="sel">Wszystkie<i></i></span></div>
<div class="pole"><label>Model</label><span class="sel" style="color:#9AA5B1">Najpierw marka<i></i></span></div>
<div class="pole"><label>Dostępność</label><span class="sel">Wszystkie · {TOTAL}<i></i></span></div>
<div class="pole"><label>Nadwozie</label><span class="sel">Wszystkie<i></i></span></div>
<div class="pole"><label>Cena</label><div class="para"><span class="inp"><span class="ph">100 000</span><small>zł</small></span><em>—</em><span class="inp"><span class="ph">1 068 000</span><small>zł</small></span></div></div>
<div class="pole"><label>Rocznik</label><div class="para"><span class="sel">2022<i></i></span><em>—</em><span class="sel">2026<i></i></span></div></div>
<div class="pole"><label>Przebieg do</label><span class="inp on">15 000<small>km</small></span></div>
<div class="pole"><label>Kolor nadwozia</label>{kolory_html()}</div>'''
F_nap = f'''
<div class="pole" style="grid-column:span 2"><span class="lbl">Rodzaj napędu</span><div class="chips">''' + ''.join(
    f'<span class="chip{" on" if k in ("phev","erev") else ""}">{l} <b>{n(v)}</b></span>' for k,l,v in PALIWA) + '''</div></div>
<div class="pole"><label>Skrzynia</label><span class="sel">Automatyczna<i></i></span></div>
<div class="pole"><label>Napęd kół</label><span class="sel">Wszystkie<i></i></span></div>
<div class="pole"><label>Moc od</label><span class="inp"><span class="ph">95</span><small>KM</small></span></div>
<div class="pole"><label>0–100 km/h do</label><span class="inp on">4,5<small>s</small></span></div>
<div class="pole"><label>Zasięg elektryczny od</label><span class="inp"><span class="ph">55</span><small>km</small></span></div>
<div class="pole"><label>Zasięg łączny od</label><span class="inp on">1 000<small>km</small></span></div>
<div class="pole"><label>Bateria od</label><span class="inp"><span class="ph">1,6</span><small>kWh</small></span></div>
<div class="pole"><label>Ładowanie DC od</label><span class="inp"><span class="ph">50</span><small>kW</small></span></div>'''
F_wym = '''
<div class="pole"><label>Długość od</label><span class="inp on">5,0<small>m</small></span></div>
<div class="pole"><label>Miejsca</label><span class="sel on">5 i więcej<i></i></span></div>
<div class="pole"><label>Felgi od</label><span class="sel on">20"<i></i></span></div>
<div class="pole"><label>Bagażnik od</label><span class="inp"><span class="ph">300</span><small>l</small></span></div>
<div class="pole"><label>Tapicerka</label><span class="sel on">Skóra naturalna<i></i></span></div>
<div class="pole"><label>Kolor wnętrza</label><span class="sel on">Czarny<i></i></span></div>
<div class="pole"><label>Szyberdach</label><span class="sel">Dowolny<i></i></span></div>
<div class="pole"><label>Nagłośnienie</label><span class="sel on">Devialet, Dynaudio, HUAWEI<i></i></span></div>'''
F_wyp = f'''<div class="pole" style="grid-column:1/-1"><div class="chips">{wyp_chips()}</div></div>'''
F = f'''<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Wariant F — mobile.de</title>
<style>{BAZA}
.form{{background:#fff;border:1px solid #D8DDE3;border-radius:8px;padding:6px 20px 10px;margin-bottom:16px}}
.sek{{padding:16px 0 18px;border-bottom:1px solid #EEF1F5}}.sek:last-child{{border:0}}
.siatka{{display:grid;gap:12px 14px;align-items:end}}
.chips{{display:flex;flex-wrap:wrap;gap:6px}}
.stopka{{position:sticky;bottom:0;background:#fff;border-top:1px solid #D8DDE3;margin:0 -20px;padding:12px 20px;display:flex;justify-content:space-between;align-items:center;gap:12px;border-radius:0 0 8px 8px}}
.stopka .licznik{{margin:0}}
@media (max-width:768px){{.siatka{{grid-template-columns:1fr 1fr !important}}.pole[style*="span 2"]{{grid-column:1/-1 !important}}.form{{padding:4px 14px 8px}}.stopka{{margin:0 -14px;padding:10px 14px}}}}
</style>
<div class="pasek-marki">PRIMA-AUTO <span>makieta F · wzorzec: mobile.de „szukaj szczegółowo"</span></div>
<div class="strona">
<h1>Wyszukiwarka zaawansowana</h1>
<p class="wariant-info"><b>F — mobile.de.</b> Wszystko widoczne od razu, bez rozwijania: jeden formularz w pięciu sekcjach (Podstawowe · Napęd i osiągi · Wymiary i wnętrze · Wyposażenie), przyklejona stopka z licznikiem i czerwonym „Pokaż". Wyniki pod formularzem, tak jak dziś. Stan: auto marzeń złożone.</p>
<div class="form">
{sekcja('Podstawowe', F_pod)}
{sekcja('Napęd i osiągi', F_nap)}
{sekcja('Wymiary i wnętrze', F_wym)}
{sekcja('Wyposażenie', F_wyp, 1)}
<div class="stopka"><p class="licznik"><b>142</b> <span>oferty spełniają kryteria</span> &nbsp;·&nbsp; <span class="wyczysc">Wyczyść (12)</span></p><span class="btn btn--akcja">Pokaż 142 oferty</span></div>
</div>
<div class="toolbar"><p class="licznik" style="margin:0"><b>142</b> <span>oferty</span></p><span class="sort">Sortuj: Najnowsze</span></div>
{karty()}
</div>'''

# ============================================================ G — pasek górny, wszystko widoczne, wyposażenie zwijane
G = f'''<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Wariant G — pasek górny, wszystko od razu</title>
<style>{BAZA}
.pasek{{background:#fff;border:1px solid #D8DDE3;border-radius:8px;padding:14px 16px 12px;margin-bottom:12px}}
.dost{{display:flex;gap:2px;background:#F5F6F8;border-radius:6px;padding:3px;margin-bottom:12px}}
.seg{{flex:1;display:flex;align-items:baseline;justify-content:center;gap:8px;padding:8px 12px;border-radius:4px;white-space:nowrap;font-size:13.5px}}
.seg b{{color:#5C6B7F;font-size:12px;font-weight:400}}.seg.on{{background:#1B2A4A;color:#fff;font-weight:600}}.seg.on b{{color:rgba(255,255,255,.75)}}
.r{{display:grid;grid-template-columns:repeat(8,1fr);gap:8px 10px;margin-bottom:10px}}
.r .pole>label{{font-size:11px}}
.wyp{{display:flex;align-items:center;gap:8px;border-top:1px dashed #D8DDE3;padding-top:10px;margin-top:2px}}
.wyp .lbl{{font-size:11.5px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:#1B2A4A;flex:none}}
.wyp .chips{{display:flex;gap:6px;flex-wrap:wrap;flex:1}}
.wiecej{{color:#1B2A4A;font-weight:600;text-decoration:underline;white-space:nowrap;font-size:13px}}
.dol{{display:flex;justify-content:space-between;align-items:center;margin-top:10px;padding-top:10px;border-top:1px solid #EEF1F5}}
@media (max-width:768px){{.r{{grid-template-columns:1fr 1fr}}.dost{{flex-direction:column}}.seg{{justify-content:space-between}}.wyp{{flex-wrap:wrap}}.wyp .chips{{flex-wrap:nowrap;overflow-x:auto;padding-bottom:4px;width:100%}}}}
</style>
<div class="pasek-marki">PRIMA-AUTO <span>makieta G · pasek górny jak w 0.36, ale wszystko widoczne</span></div>
<div class="strona">
<h1>Wyszukiwarka zaawansowana</h1>
<p class="wariant-info"><b>G — pasek górny, wszystko od razu.</b> Układ z 0.36 (dostępność w jednej linii + rząd filtrów), ale filtry są kontrolkami, nie pigułkami z popoverami: dwa rzędy po osiem pól widać bez klikania, wyposażenie jako pastylki, a zwija się tylko reszta wyposażenia („Więcej +10"). Wyniki od razu pod paskiem, przeliczane w locie — bez przycisku „Pokaż".</p>
<div class="pasek">
  <div class="dost"><span class="seg on">Wszystkie <b>{TOTAL}</b></span><span class="seg">Do sprowadzenia <b>{n(D['oferta']['do-sprowadzenia'])}</b></span><span class="seg">W drodze do Polski <b>{n(D['oferta']['w-drodze'])}</b></span><span class="seg">Na placu w Rzeszowie <b>{n(D['oferta']['na-placu'])}</b></span></div>
  <div class="r">
    <div class="pole"><label>Marka</label><span class="sel">Wszystkie<i></i></span></div>
    <div class="pole"><label>Model</label><span class="sel" style="color:#9AA5B1">Najpierw marka<i></i></span></div>
    <div class="pole"><label>Napęd</label><span class="sel on">PHEV, EREV<i></i></span></div>
    <div class="pole"><label>Nadwozie</label><span class="sel">Wszystkie<i></i></span></div>
    <div class="pole"><label>Cena do</label><span class="inp"><span class="ph">1 068 000</span><small>zł</small></span></div>
    <div class="pole"><label>Rocznik od</label><span class="sel">2022<i></i></span></div>
    <div class="pole"><label>Przebieg do</label><span class="inp on">15 000<small>km</small></span></div>
    <div class="pole"><label>Skrzynia</label><span class="sel">Wszystkie<i></i></span></div>
  </div>
  <div class="r">
    <div class="pole"><label>Miejsca od</label><span class="sel on">5<i></i></span></div>
    <div class="pole"><label>Długość od</label><span class="inp on">5,0<small>m</small></span></div>
    <div class="pole"><label>0–100 do</label><span class="inp on">4,5<small>s</small></span></div>
    <div class="pole"><label>Zasięg łączny od</label><span class="inp on">1 000<small>km</small></span></div>
    <div class="pole"><label>Bateria od</label><span class="inp"><span class="ph">1,6</span><small>kWh</small></span></div>
    <div class="pole"><label>Felgi od</label><span class="sel on">20"<i></i></span></div>
    <div class="pole"><label>Tapicerka</label><span class="sel on">Skóra<i></i></span></div>
    <div class="pole"><label>Nagłośnienie</label><span class="sel on">Devialet +2<i></i></span></div>
  </div>
  <div class="r" style="grid-template-columns:auto 1fr;align-items:center;margin-bottom:0">
    <div class="pole"><label>Kolor</label>{kolory_html()}</div>
    <div class="pole"><label>Moc od</label><span class="inp" style="max-width:150px"><span class="ph">95</span><small>KM</small></span></div>
  </div>
  <div class="wyp"><span class="lbl">Wyposażenie</span><div class="chips">{wyp_chips(6)}</div><a class="wiecej" href="#">Więcej +10 ▾</a></div>
  <div class="dol"><span style="color:#5C6B7F;font-size:13px">12 aktywnych kryteriów &nbsp;·&nbsp; <span class="wyczysc">Wyczyść wszystko</span></span><span style="font-size:13px;color:#5C6B7F">wyniki przeliczają się w locie</span></div>
</div>
<div class="toolbar"><p class="licznik" style="margin:0"><b>142</b> <span>oferty spełniają wszystkie kryteria</span></p><span class="sort">Sortuj: Najnowsze</span></div>
{karty()}
</div>'''

INDEX = f'''<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Makiety wyszukiwarki — PrimaAuto T-116</title>
<style>
body{{margin:0;background:#F5F6F8;color:#2D3748;font:15px/1.6 Inter,-apple-system,'Segoe UI',sans-serif}}
.w{{max-width:760px;margin:0 auto;padding:40px 20px 60px}}h1{{font-size:26px;color:#1B2A4A;margin:0 0 6px}}
p.lead{{color:#5C6B7F;margin:0 0 24px}}h2{{font-size:15px;color:#5C6B7F;text-transform:uppercase;letter-spacing:.5px;margin:28px 0 10px}}
a.k{{display:block;background:#fff;border:1px solid #D8DDE3;border-radius:6px;padding:16px 20px;margin-bottom:10px;text-decoration:none;color:inherit}}
a.k:hover{{border-color:#1B2A4A}}a.k b{{display:block;color:#1B2A4A;font-size:17px;margin-bottom:3px}}a.k span{{color:#5C6B7F;font-size:14px}}
a.k.stare{{opacity:.7}}
</style>
<div class="w">
<h1>Makiety wyszukiwarki</h1>
<p class="lead">Runda 2 (03.09): pasek <b>górny</b>, jak najwięcej filtrów widocznych od razu, kryteria „auta marzeń" (PHEV/EREV, 5 miejsc, ≥5 m, ≤4,5 s, nagłośnienie, felgi 20"+, skóra, czarny, masaż przód i tył, przebieg ≤15 tys., zasięg łączny ≥1 000 km). Liczby prawdziwe: komplet kryteriów spełnia dziś <b>142</b> oferty. Na telefonie każda makieta układa się sama — zwęź okno.</p>
<a class="k" href="wariant-e-otomoto.html"><b>E — Otomoto</b><span>Rząd sześciu podstawowych + „Pokaż", pod nim rozwijane „Więcej filtrów" (tu rozwinięte), wybrane kryteria jako usuwalne pastylki nad wynikami.</span></a>
<a class="k" href="wariant-f-mobilede.html"><b>F — mobile.de „szukaj szczegółowo"</b><span>Jeden długi formularz w sekcjach, wszystko widoczne, przyklejona stopka z licznikiem i „Pokaż 142 oferty".</span></a>
<a class="k" href="wariant-g-pasek-wszystko.html"><b>G — pasek górny jak w 0.36, ale wszystko od razu</b><span>Dostępność w jednej linii + dwa rzędy po osiem kontrolek + kolory + pastylki wyposażenia; zwija się tylko reszta wyposażenia. Wyniki liczone w locie.</span></a>
<h2>Runda 1 (02.09) — odrzucone</h2>
<a class="k stare" href="wariant-a-obecny.html"><b>A — stan 0.36.0</b><span>Pigułki z popoverami.</span></a>
<a class="k stare" href="wariant-b-taste.html"><b>B — wg taste-skill</b><span>To samo po Pre-Flight Check.</span></a>
<a class="k stare" href="wariant-c-rozwiniete.html"><b>C — rozwinięte kafle</b><span>Cztery kafle dostępności, filtry wierszami.</span></a>
<a class="k stare" href="wariant-d.html"><b>D — ciemny pulpit</b><span>Bez ograniczenia motywu.</span></a>
</div>'''

for nazwa, tresc in [('wariant-e-otomoto.html', E), ('wariant-f-mobilede.html', F), ('wariant-g-pasek-wszystko.html', G), ('index.html', INDEX)]:
    (OUT / nazwa).write_text(tresc, encoding='utf-8')
    print('zapisano', nazwa)

# ============================================================ H — paski sekcji (wsad Janka 03.09, 2. runda)
def pasek(tytul, inner, klasa=''):
    return f'<section class="sekp {klasa}"><div class="sekp__t"><b>{tytul}</b></div><div class="sekp__c">{inner}</div></section>'
H_top = f'''
<div class="pole"><label>Cena</label><div class="para"><span class="inp"><span class="ph">100 000</span><small>zł</small></span><em>—</em><span class="inp"><span class="ph">1 068 000</span><small>zł</small></span></div></div>
<div class="pole"><label>Rocznik od</label><span class="sel">2022<i></i></span></div>
<div class="pole"><label>Przebieg do</label><span class="inp on">15 000<small>km</small></span></div>
<div class="pole"><label>Miejsca od</label><span class="sel on">5<i></i></span></div>'''
H_nadw = '''
<div class="pole"><label>Marka</label><span class="sel">Wszystkie<i></i></span></div>
<div class="pole"><label>Model</label><span class="sel" style="color:#9AA5B1">Najpierw marka<i></i></span></div>
<div class="pole"><label>Rodzaj</label><span class="sel">SUV, Sedan<i></i></span></div>
<div class="pole"><label>4x4</label><span class="sel on">Tak (AWD)<i></i></span></div>
<div class="pole"><label>Długość od</label><span class="inp on">5,0<small>m</small></span></div>
<div class="pole"><label>DMC do</label><span class="inp"><span class="ph">3 500</span><small>kg</small></span></div>'''
H_nap = '''
<div class="pole"><label>Silnik</label><span class="sel on">PHEV, EREV<i></i></span></div>
<div class="pole"><label>Moc od</label><span class="inp"><span class="ph">95</span><small>KM</small></span></div>
<div class="pole"><label>Przyspieszenie do</label><span class="inp on">4,5<small>s</small></span></div>
<div class="pole"><label>Bateria od</label><span class="inp"><span class="ph">1,6</span><small>kWh</small></span></div>
<div class="pole"><label>Zasięg łączny od</label><span class="inp on">1 000<small>km</small></span></div>
<div class="pole"><label>Zasięg na prądzie od</label><span class="inp"><span class="ph">55</span><small>km</small></span></div>'''
H_styl = f'''
<div class="pole" style="min-width:300px"><label>Kolor nadwozia</label>{kolory_html()}</div>
<div class="pole"><label>Kolor wnętrza</label><span class="sel on">Czarny<i></i></span></div>
<div class="pole"><label>Materiał</label><span class="sel on">Skóra naturalna<i></i></span></div>
<div class="pole"><label>Zawieszenie</label><span class="sel">Dowolne · pneumatyczne · adaptacyjne<i></i></span></div>
<div class="pole"><label>Felgi od</label><span class="sel on">20"<i></i></span></div>'''
TECH = [('Lidar',1341,False),('Kamera 360°',2715,False),('Masaż przód',1681,True),('Masaż tył',994,True),('Wentylowane fotele',2540,False),
        ('AR-HUD',740,False),('Autopilot miejski (NOA)',1330,False),('Pompa ciepła',1300,False),('Ładowanie 800 V',860,False),
        ('V2L',2290,False),('Tryb wartownika',2070,False),('Lodówka',800,False)]
H_tech = '<div class="pole" style="flex:1"><label>Nagłośnienie</label><span class="sel on" style="max-width:220px">Devialet +2<i></i></span></div>' + \
    '<div class="pole" style="flex:4"><span class="lbl">Wyposażenie</span><div class="chips">' + \
    ''.join(f'<span class="chip{" on" if on else ""}">{t} <b>{n(c)}</b></span>' for t,c,on in TECH) + \
    '<a class="wiecej" href="#">Więcej +8 ▾</a></div></div>'
H_oferty = '''
<div class="oferty">
  <span class="of on"><b>142</b><span>Wszystkie spełniające kryteria</span></span>
  <span class="of"><b>131</b><span>Do sprowadzenia z Chin</span><small>ok. 8–10 tygodni</small></span>
  <span class="of"><b>7</b><span>W drodze do Polski</span><small>odbiór za 2–4 tygodnie</small></span>
  <span class="of"><b>4</b><span>Na placu w Polsce</span><small>do obejrzenia od ręki</small></span>
</div>'''
H = f'''<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Wariant H — paski sekcji</title>
<style>{BAZA}
.sekp{{display:flex;background:#fff;border:1px solid #D8DDE3;border-radius:8px;margin-bottom:8px;overflow:hidden}}
.sekp__t{{flex:0 0 150px;background:#1B2A4A;color:#fff;padding:14px 14px;display:flex;align-items:center}}
.sekp__t b{{font-size:12.5px;letter-spacing:.5px;text-transform:uppercase;line-height:1.3}}
.sekp--top .sekp__t{{background:#F0F2F5;color:#1B2A4A}}
.sekp--oferty{{border-color:#1B2A4A;margin-top:4px}}
.sekp--oferty .sekp__c{{padding:10px 12px}}
.oferty{{display:flex;gap:8px;width:100%}}
.of{{flex:1;display:flex;flex-direction:column;gap:2px;padding:10px 14px;border:1px solid #D8DDE3;border-radius:6px;background:#F5F6F8}}
.of b{{font-size:22px;color:#1B2A4A;line-height:1.1}}.of span{{font-size:13.5px;font-weight:600;color:#1B2A4A}}.of small{{font-size:12px;color:#5C6B7F}}
.of.on{{background:#1B2A4A;border-color:#1B2A4A}}.of.on b,.of.on span{{color:#fff}}.of.on small{{color:rgba(255,255,255,.75)}}
.sekp__c{{flex:1;display:flex;gap:10px 12px;padding:10px 14px 12px;align-items:flex-end;flex-wrap:wrap}}
.sekp__c .pole{{flex:1 1 140px}}
.chips{{display:flex;flex-wrap:wrap;gap:6px;align-items:center}}
.wiecej{{color:#1B2A4A;font-weight:600;text-decoration:underline;white-space:nowrap;font-size:13px;margin-left:4px}}
.dol{{display:flex;justify-content:space-between;align-items:center;margin:4px 0 14px;font-size:13px;color:#5C6B7F}}
@media (max-width:768px){{.sekp{{flex-direction:column}}.sekp__t{{flex:none;padding:9px 12px}}.sekp__c{{display:grid;grid-template-columns:1fr 1fr}}.sekp__c .pole{{flex:none}}.oferty{{display:grid;grid-template-columns:1fr 1fr;grid-column:1/-1}}.sekp__c .pole[style*="min-width"],.sekp__c .pole:has(.para),.sekp__c .pole[style*="flex:4"]{{grid-column:1/-1;min-width:0 !important}}.chips{{flex-wrap:nowrap;overflow-x:auto;padding-bottom:4px}}}}
</style>
<div class="pasek-marki">PRIMA-AUTO <span>makieta H · paski sekcji wg wsadu 03.09</span></div>
<div class="strona">
<h1>Wyszukiwarka zaawansowana</h1>
<p class="wariant-info"><b>H — osobne paski sekcji.</b> Nadwozie · Napęd · Styl i komfort · Technologia i wyposażenie — każda sekcja to własny pasek z etykietą po lewej i kontrolkami w jednym rzędzie, wszystko widoczne od razu; zwija się tylko reszta wyposażenia. Piąty pasek na dole to podział wyników po filtrach: do sprowadzenia z Chin / w drodze do Polski / na placu; liczby przeliczają się po każdym zawężeniu. Jasny pasek Podstawowe na górze (cena, rocznik, przebieg, miejsca) to mój dodatek. Stan: auto marzeń złożone, 142 oferty.</p>
{pasek('Podstawowe', H_top, 'sekp--top')}
{pasek('Nadwozie', H_nadw)}
{pasek('Napęd', H_nap)}
{pasek('Styl i komfort', H_styl)}
{pasek('Technologia i wyposażenie', H_tech)}
{pasek('Oferty', H_oferty, 'sekp--oferty')}
<div class="dol"><span>12 aktywnych kryteriów &nbsp;·&nbsp; <span class="wyczysc">Wyczyść wszystko</span></span><span>wyniki przeliczają się w locie, bez przycisku „Pokaż"</span></div>
<div class="toolbar"><p class="licznik" style="margin:0"><b>142</b> <span>oferty spełniają wszystkie kryteria</span></p><span class="sort">Sortuj: Najnowsze</span></div>
{karty()}
</div>'''
(OUT / 'wariant-h-paski-sekcji.html').write_text(H, encoding='utf-8')
idx = (OUT / 'index.html').read_text(encoding='utf-8')
idx = idx.replace('<a class="k" href="wariant-e-otomoto.html">',
  '<a class="k" href="wariant-h-paski-sekcji.html" style="border-color:#1B2A4A"><b>H — osobne paski sekcji (wsad Janka 03.09)</b><span>Nadwozie: marka / model / rodzaj / 4x4 / długość / DMC · Napęd: silnik / moc / przyspieszenie / bateria / zasięg · Styl i komfort: kolor nadwozia / kolor wnętrza / materiał / zawieszenie · Technologia i wyposażenie: lidar, kamery, masaże… Każda sekcja to własny pasek, wszystko widoczne.</span></a>\n<a class="k" href="wariant-e-otomoto.html">')
(OUT / 'index.html').write_text(idx, encoding='utf-8')
print('zapisano wariant-h + index')
