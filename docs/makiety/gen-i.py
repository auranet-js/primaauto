#!/usr/bin/env python3
"""Makieta I (03.09, runda 3): wygląd Otomoto, podział na sekcje wg wsadu Janka, desktop, promień 6 px na wszystkim."""
import json, pathlib
D = json.load(open('/home/host476470/projekty/primaauto/tmp/t116e3/dane-mockup.json'))
OUT = pathlib.Path('/home/host476470/domains/auratest.pl/public_html/fe4f58fec53ctmp/primaauto-makiety')
def n(x): return f"{int(x):,}".replace(",", " ")

def sel(label, val, on=False, ph=False, kropka=None):
    k = f'<i class="kropka" style="background:{kropka}"></i>' if kropka else ''
    return f'<div class="pole"><label>{label}</label><span class="sel{" on" if on else ""}{" ph" if ph else ""}">{k}<span>{val}</span><b></b></span></div>'
def inp(label, val, unit, on=False):
    cls = "inp on" if on else "inp ph"
    return f'<div class="pole"><label>{label}</label><span class="{cls}"><span>{val}</span><small>{unit}</small></span></div>'
def chips(lst):
    return '<div class="chips">' + ''.join(f'<span class="chip{" on" if on else ""}">{t}<b>{n(c)}</b></span>' for t,c,on in lst) + '<span class="chip wiecej">Więcej filtrów +9</span></div>'

NADWOZIE = sel('Marka pojazdu','Wszystkie') + sel('Model pojazdu','Najpierw wybierz markę',ph=True) + sel('Rodzaj nadwozia','SUV, Sedan',on=True) + \
           sel('Napęd 4x4','Tak (AWD)',on=True) + inp('Długość od','5,0','m',on=True) + inp('DMC do','3 500','kg')
NAPED    = sel('Silnik','Hybryda PHEV, EREV',on=True) + inp('Moc od','95','KM') + inp('Przyspieszenie 0–100 do','4,5','s',on=True) + \
           inp('Bateria od','1,6','kWh') + inp('Zasięg od','1 000','km',on=True)
STYL     = sel('Kolor nadwozia','Czarny',on=True,kropka='#1A1A1A') + sel('Kolor wnętrza','Czarny',on=True,kropka='#1A1A1A') + \
           sel('Materiał tapicerki','Skóra naturalna',on=True) + sel('Zawieszenie','Pneumatyczne')
TECH     = chips([('Lidar',1341,False),('Kamera 360°',2715,True),('Masaż przód',1681,True),('Masaż tył',994,True),
                  ('Wentylowane fotele',2540,False),('AR-HUD',740,False),('Autopilot miejski (NOA)',1330,False),('Nagłośnienie premium',890,True)])
OFERTY = '''<div class="oferty">
  <span class="of on"><b>131</b><span>Do sprowadzenia z Chin</span><small>ok. 8–10 tygodni</small></span>
  <span class="of"><b>7</b><span>W drodze do Polski</span><small>odbiór za 2–4 tygodnie</small></span>
  <span class="of"><b>4</b><span>Na placu w Polsce</span><small>do obejrzenia od ręki</small></span>
  <span class="btn">Pokaż 142 oferty</span>
</div>'''
KARTY = [("Zeekr 9X 2026 Ultra 70kWh 6-osobowy","448 000","2026 r. · 100 km · 3,1 s · PHEV · 5 239 mm · zasięg 1 240 km"),
         ("Denza N9 DM-i 2025 Premium","279 000","2025 r. · 12 000 km · 3,9 s · PHEV · 5 258 mm · zasięg 1 302 km"),
         ("AITO M9 2025 EREV Ultra 52 kWh","357 000","2025 r. · 100 km · 4,4 s · EREV · 5 230 mm · zasięg 1 362 km")]
karty = ''.join(f'<article class="karta"><div class="foto"></div><div class="tresc"><h3>{t}</h3><p class="cena">{c} PLN</p><p class="spec">{s}</p><div class="akcje"><span class="b1">Szczegóły</span><span class="b2">Zamów</span></div></div></article>' for t,c,s in KARTY)

def sekcja(t, inner, extra=''):
    return f'<section class="sek {extra}"><h2>{t}</h2>{inner}</section>'

HTML = f'''<!doctype html><meta charset="utf-8"><title>Makieta I — Otomoto w sekcjach</title>
<style>
*,*::before,*::after{{box-sizing:border-box}}
body{{margin:0;background:#F5F6F8;color:#2D3748;font:14px/1.45 Inter,-apple-system,'Segoe UI',sans-serif}}
.naglowek{{background:#9B0000;color:#fff;padding:16px 24px;font-weight:800;letter-spacing:.02em}}
.strona{{max-width:1200px;margin:0 auto;padding:24px 20px 60px}}
h1{{font-size:28px;margin:0 0 16px;color:#1B2A4A}}
/* jeden promień dla wszystkiego, jak --aa-radius na stronie */
:root{{--r:6px;--granat:#1B2A4A;--linia:#E1E4E8;--szary:#5C6B7F;--akcent:#C92A2B}}
.panel{{background:#fff;border:1px solid var(--linia);border-radius:var(--r);padding:0 20px}}
.sek{{padding:16px 0 18px;border-bottom:1px solid var(--linia)}}.sek:last-child{{border:0}}
.sek h2{{margin:0 0 10px;font-size:12px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--szary)}}
.sek--nadwozie .siatka{{grid-template-columns:repeat(6,1fr)}}.sek--naped .siatka{{grid-template-columns:repeat(5,1fr)}}.sek--styl .siatka{{grid-template-columns:repeat(4,1fr)}}
.siatka{{display:grid;gap:12px}}
.pole{{display:flex;flex-direction:column;gap:5px;min-width:0}}
.pole label{{font-size:12px;font-weight:600;color:var(--granat)}}
.sel,.inp{{display:flex;align-items:center;height:42px;padding:0 12px;background:#fff;border:1px solid var(--linia);border-radius:var(--r);color:#2D3748;white-space:nowrap;overflow:hidden;gap:8px}}
.sel span{{flex:1;overflow:hidden;text-overflow:ellipsis}}
.sel b{{width:7px;height:7px;border-right:1.5px solid currentColor;border-bottom:1.5px solid currentColor;transform:rotate(45deg) translate(-1px,-2px);opacity:.55;flex:none}}
.inp{{justify-content:flex-end}}.inp small{{color:var(--szary)}}
.ph span{{color:#9AA5B1}}
.on{{border-color:var(--granat)}}.on span{{color:var(--granat);font-weight:600}}
.kropka{{width:14px;height:14px;border-radius:var(--r);flex:none;box-shadow:inset 0 0 0 1px rgba(0,0,0,.15)}}
.chips{{display:flex;flex-wrap:wrap;gap:8px}}
.chip{{display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 12px;border:1px solid var(--linia);border-radius:var(--r);background:#fff}}
.chip b{{color:var(--szary);font-weight:400;font-size:12px}}
.chip.on{{background:var(--granat);border-color:var(--granat);color:#fff}}.chip.on b{{color:rgba(255,255,255,.75)}}.chip.on::before{{content:'✓';font-size:12px}}
.chip.wiecej{{color:var(--granat);font-weight:600;border-style:dashed}}
.sek--oferty{{background:#F5F6F8;margin:0 -20px;padding:16px 20px 18px;border-radius:0 0 var(--r) var(--r)}}
.oferty{{display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:stretch}}
.of{{display:flex;flex-direction:column;gap:2px;padding:12px 16px;border:1px solid var(--linia);border-radius:var(--r);background:#fff}}
.of b{{font-size:22px;color:var(--granat);line-height:1.1}}.of span{{font-size:14px;font-weight:600;color:var(--granat)}}.of small{{font-size:12px;color:var(--szary)}}
.of.on{{background:var(--granat);border-color:var(--granat)}}.of.on b,.of.on span{{color:#fff}}.of.on small{{color:rgba(255,255,255,.75)}}
.btn{{display:flex;align-items:center;justify-content:center;padding:0 26px;background:var(--akcent);color:#fff;font-size:16px;font-weight:700;border-radius:var(--r);min-width:200px}}
.toolbar{{display:flex;align-items:center;justify-content:space-between;padding:18px 0 12px;margin-bottom:12px;border-bottom:1px solid var(--linia)}}
.toolbar b{{font-size:20px;color:var(--granat)}}.toolbar .sort{{border:1px solid var(--linia);border-radius:var(--r);padding:9px 12px;background:#fff}}
.karta{{display:flex;gap:16px;background:#fff;border:1px solid var(--linia);border-radius:var(--r);overflow:hidden;margin-bottom:12px}}
.foto{{width:230px;flex:0 0 230px;background:linear-gradient(135deg,#dfe3e8,#c9d0d8)}}
.tresc{{padding:14px 16px 16px}}.tresc h3{{margin:0 0 6px;font-size:16px;color:var(--granat)}}
.cena{{margin:0 0 6px;font-size:19px;font-weight:700;color:var(--akcent)}}.spec{{margin:0 0 12px;font-weight:600}}
.akcje{{display:flex;gap:8px}}.b1,.b2{{display:inline-flex;padding:9px 16px;border-radius:var(--r);font-weight:600}}.b1{{background:var(--granat);color:#fff}}.b2{{border:1px solid var(--akcent);color:var(--akcent)}}
</style>
<div class="naglowek">PRIMA-AUTO</div>
<div class="strona">
<h1>Wyszukiwarka</h1>
<div class="panel">
{sekcja('Nadwozie', f'<div class="siatka">{NADWOZIE}</div>', 'sek--nadwozie')}
{sekcja('Napęd', f'<div class="siatka">{NAPED}</div>', 'sek--naped')}
{sekcja('Styl i komfort', f'<div class="siatka">{STYL}</div>', 'sek--styl')}
{sekcja('Technologia i wyposażenie', TECH, 'sek--tech')}
{sekcja('Oferty', OFERTY, 'sek--oferty')}
</div>
<div class="toolbar"><span><b>142</b> oferty spełniają kryteria</span><span class="sort">Sortuj: Najnowsze</span></div>
{karty}
</div>'''
(OUT / 'wariant-i-otomoto-sekcje.html').write_text(HTML, encoding='utf-8')
print('ok')
