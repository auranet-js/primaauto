#!/usr/bin/env python3
"""Wariant D - makieta bez ograniczenia obecnym motywem. Dane prawdziwe."""
import json, pathlib
D = json.load(open('/home/host476470/projekty/primaauto/tmp/t116e3/dane-mockup.json'))
OUT = pathlib.Path('/home/host476470/projekty/primaauto/tmp/t116e3/mockupy')
def n(x): return f"{int(x):,}".replace(",", " ")

LM = D['labels']['make']
MARKI = [(LM.get(k, k), v) for k, v in list(D['enum']['make'].items())[:8]]
PALIWA = [(D['labels']['fuel'].get(k, k), v) for k, v in list(D['enum']['fuel'].items())[:4]]
NADW = [(D['labels']['body'].get(k, k), v) for k, v in list(D['enum']['body'].items())[:5]]
FL = D['flags']

KARTY = [
    ("Zeekr 9X", "2026 Ultra 70 kWh, 6-osobowy", "448 000", "100 km", "4,2 s", "PHEV", "1 200 km", "na placu"),
    ("NIO ES8", "2025 Executive Signature, 6-osobowy", "361 000", "18 000 km", "4,0 s", "EV", "605 km", "w drodze"),
    ("AITO M8", "2025 EREV Max+ 53,4 kWh", "325 000", "17 300 km", "5,2 s", "EREV", "1 020 km", "sprowadzimy"),
]

def karty():
    o = []
    for marka, wersja, cena, przeb, przysp, nap, zasieg, stan in KARTY:
        o.append(f'''
      <article class="of">
        <div class="of__foto" aria-hidden="true"></div>
        <div class="of__body">
          <div class="of__gora">
            <h3 class="of__nazwa">{marka} <span>{wersja}</span></h3>
            <p class="of__stan of__stan--{stan.replace(" ", "-")}">{stan}</p>
          </div>
          <p class="of__cena">{cena}<span> zl</span></p>
          <dl class="of__dane">
            <div><dt>przebieg</dt><dd>{przeb}</dd></div>
            <div><dt>0-100</dt><dd>{przysp}</dd></div>
            <div><dt>zasięg</dt><dd>{zasieg}</dd></div>
            <div><dt>napęd</dt><dd>{nap}</dd></div>
          </dl>
        </div>
      </article>''')
    return ''.join(o)

filtr_marki = ''.join(f'<label class="tg"><input type="checkbox"><span>{l}</span><b>{n(v)}</b></label>' for l, v in MARKI)
filtr_paliwo = ''.join(f'<label class="tg"><input type="checkbox"><span>{l}</span><b>{n(v)}</b></label>' for l, v in PALIWA)
filtr_nadw = ''.join(f'<label class="tg"><input type="checkbox"><span>{l}</span><b>{n(v)}</b></label>' for l, v in NADW)
filtr_wyp = ''.join(f'<label class="tg"><input type="checkbox"><span>{l}</span><b>{n(v)}</b></label>' for l, v in
    [('Kamera 360°', FL['cam_360']), ('Lidar', FL['lidar']), ('Masaż foteli', FL['seat_massage_f']),
     ('Pompa ciepła', FL['heat_pump']), ('Zawieszenie pneum.', FL['air_susp'])])

HTML = f'''<!doctype html>
<meta charset="utf-8">
<title>Wariant D - Prima Auto</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{{box-sizing:border-box}}
:root{{
  --noc:#0E1826;          /* pasek sterowania, nie czern */
  --noc-2:#182437;
  --linia-noc:#2A3away;
  --linia-noc:#26334A;
  --dzien:#F4F5F7;        /* tlo wynikow */
  --papier:#FFFFFF;
  --tekst:#16202E;
  --tekst-2:#5B6A7D;
  --jasny:#E9EDF3;
  --jasny-2:#94A3B8;      /* 5,1:1 na --noc */
  /* Jeden akcent, dwa tony. Detektor impeccable zlapal 4,2:1 na jednym odcieniu
     w obie strony; ciemniejszy nosi bialy tekst (5,46:1), jasniejszy sluzy za tekst
     i znaczniki na nocy (6,43:1). To ten sam kolor, nie dwa akcenty. */
  --akcent:#C92A2B;
  --akcent-na-nocy:#FF6B6B;
  --r:4px;                /* shape lock: powierzchnie 4px, interaktywne pill */
}}
html{{scroll-behavior:smooth}}
body{{margin:0;background:var(--dzien);color:var(--tekst);
  font:16px/1.55 'Space Grotesk',-apple-system,'Segoe UI',sans-serif;
  font-variant-numeric:tabular-nums}}
.info{{background:#fff;border-bottom:1px solid #E2E6EC;padding:12px 24px;margin:0;font-size:13.5px;color:var(--tekst-2)}}
.info b{{color:var(--tekst)}}

/* ---------------------------------------------- pasek sterowania */
.ster{{background:var(--noc);color:var(--jasny);padding:26px 0 0}}
.w{{max-width:1180px;margin:0 auto;padding:0 24px}}
.marka{{font-size:15px;letter-spacing:.14em;font-weight:700;color:#fff;margin:0 0 22px}}
.marka i{{color:var(--akcent-na-nocy);font-style:normal}}

/* OS DOSTEPNOSCI - jedyny mocny element strony */
.os{{position:relative;padding:6px 0 4px}}
.os__tor{{position:absolute;left:0;right:0;top:19px;height:1px;background:linear-gradient(90deg,var(--linia-noc) 0%,var(--linia-noc) 62%,var(--akcent-na-nocy) 100%)}}
.os__punkty{{position:relative;display:grid;grid-template-columns:1.35fr 1fr 1fr .9fr;gap:0}}
.os__p{{position:relative;padding:0 0 18px;cursor:pointer;background:none;border:0;color:inherit;font:inherit;text-align:left}}
.os__p::before{{content:'';position:absolute;top:13px;left:0;width:13px;height:13px;border-radius:50%;
  background:var(--noc);border:1px solid var(--linia-noc);transition:border-color .15s,background-color .15s}}
.os__p:last-child{{text-align:right}}
.os__p:last-child::before{{left:auto;right:0}}
.os__p b{{display:block;font-size:29px;font-weight:700;line-height:1;color:#fff;padding-top:36px;letter-spacing:-.02em}}
.os__p em{{display:block;font-style:normal;font-size:13.5px;color:var(--jasny-2);margin-top:5px}}
.os__p:hover::before{{border-color:var(--jasny-2)}}
.os__p.on::before{{background:var(--akcent-na-nocy);border-color:var(--akcent-na-nocy)}}
.os__p.on em{{color:var(--jasny)}}
.os__opis{{display:flex;justify-content:space-between;font-size:11.5px;letter-spacing:.1em;color:var(--jasny-2);
  padding:2px 0 16px;border-bottom:1px solid var(--linia-noc)}}

/* filtry w pasku */
.fl{{display:grid;grid-template-columns:104px 1fr;gap:10px 16px;align-items:start;padding:14px 0}}
.fl + .fl{{border-top:1px solid var(--linia-noc)}}
.fl__t{{font-size:13px;color:var(--jasny-2);padding-top:7px}}
.tagi{{display:flex;flex-wrap:wrap;gap:6px}}
.tg{{display:inline-flex;align-items:center;gap:7px;padding:6px 13px;border:1px solid var(--linia-noc);
  border-radius:999px;font-size:14px;cursor:pointer;transition:border-color .12s,transform .06s,background-color .12s}}
.tg:hover{{border-color:var(--jasny-2)}}
.tg:active{{transform:scale(.97)}}
.tg input{{position:absolute;opacity:0;width:1px;height:1px}}
.tg b{{color:var(--jasny-2);font-size:12.5px;font-weight:400}}
.tg:has(input:checked){{background:var(--akcent);border-color:var(--akcent);color:#fff}}
.tg:has(input:checked) b{{color:rgba(255,255,255,.82)}}
.zakresy{{display:flex;flex-wrap:wrap;gap:8px}}
.zk{{display:inline-flex;align-items:center;gap:8px;border:1px solid var(--linia-noc);border-radius:999px;padding:5px 8px 5px 13px;font-size:14px}}
.zk span{{color:var(--jasny-2);font-size:13px}}
.zk input{{width:82px;background:transparent;border:0;border-bottom:1px solid var(--linia-noc);
  color:var(--jasny);font:inherit;font-size:14px;padding:2px 0;text-align:center}}
.zk input::placeholder{{color:#6B7A8D}}
.zk input:focus{{outline:0;border-bottom-color:var(--akcent-na-nocy)}}
.ster__stopka{{display:flex;justify-content:space-between;align-items:center;padding:14px 0 18px;border-top:1px solid var(--linia-noc);margin-top:6px}}
.wyczysc{{background:none;border:0;color:var(--jasny-2);font:inherit;font-size:14px;cursor:pointer;
  text-decoration:underline;text-underline-offset:4px;padding:4px 0}}
.wyczysc:hover{{color:#fff}}
.wynikow{{font-size:14px;color:var(--jasny-2)}}
.wynikow b{{color:#fff;font-size:19px;font-weight:600}}

/* ---------------------------------------------- wyniki */
.lista{{padding:26px 0 60px}}
.lista__gora{{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:16px}}
.lista__gora h2{{margin:0;font-size:15px;font-weight:500;color:var(--tekst-2)}}
.sort{{border:1px solid #D6DBE2;background:#fff;border-radius:var(--r);padding:8px 12px;font:inherit;font-size:14px;color:var(--tekst)}}
.of{{display:grid;grid-template-columns:236px 1fr;background:var(--papier);border:1px solid #E2E6EC;
  border-radius:var(--r);overflow:hidden;margin-bottom:10px;transition:border-color .12s}}
.of:hover{{border-color:#B7C0CC}}
.of__foto{{background:linear-gradient(140deg,#DDE2E8,#C4CCD6);min-height:152px}}
.of__body{{padding:15px 18px 16px}}
.of__gora{{display:flex;justify-content:space-between;align-items:flex-start;gap:14px}}
.of__nazwa{{margin:0;font-size:18px;font-weight:600;letter-spacing:-.01em}}
.of__nazwa span{{display:block;font-size:13.5px;font-weight:400;color:var(--tekst-2);margin-top:2px}}
.of__stan{{margin:2px 0 0;font-size:12px;padding:3px 9px;border-radius:999px;white-space:nowrap;background:#EDF0F4;color:var(--tekst-2)}}
.of__stan--na-placu{{background:var(--akcent);color:#fff}}
.of__cena{{margin:12px 0 0;font-size:25px;font-weight:700;letter-spacing:-.02em}}
.of__cena span{{font-size:15px;font-weight:400;color:var(--tekst-2)}}
.of__dane{{display:flex;flex-wrap:wrap;gap:0 26px;margin:12px 0 0}}
.of__dane div{{min-width:74px}}
.of__dane dt{{font-size:11.5px;color:var(--tekst-2);letter-spacing:.02em}}
.of__dane dd{{margin:1px 0 0;font-size:15px;font-weight:500}}
:focus-visible{{outline:2px solid var(--akcent-na-nocy);outline-offset:3px}}
@media (prefers-reduced-motion:reduce){{*{{transition:none!important}}html{{scroll-behavior:auto}}}}
@media (max-width:820px){{
  .os__punkty{{grid-template-columns:1fr 1fr}}
  .os__tor{{display:none}}
  .os__p,.os__p:last-child{{text-align:left;padding-bottom:14px}}
  .os__p b{{padding-top:26px;font-size:24px}}
  .os__p:last-child::before{{left:0;right:auto}}
  .os__opis{{display:none}}
  .fl{{grid-template-columns:1fr;gap:6px}}
  .of{{grid-template-columns:1fr}}
  .of__foto{{min-height:180px}}
}}
</style>

<p class="info"><b>Wariant D.</b> Bez trzymania sie obecnego motywu. Krój Space Grotesk, ciemny pulpit sterowania nad jasną listą, jeden akcent na całej stronie. Mocny element: oś dostępności zamiast czterech kafli.</p>

<header class="ster">
  <div class="w">
    <p class="marka">PRIMA<i>.</i>AUTO</p>

    <div class="os">
      <div class="os__tor"></div>
      <div class="os__punkty">
        <button class="os__p on"><b>{n(D['oferta'][''])}</b><em>wszystkie oferty</em></button>
        <button class="os__p"><b>{n(D['oferta']['do-sprowadzenia'])}</b><em>sprowadzimy z Chin</em></button>
        <button class="os__p"><b>{n(D['oferta']['w-drodze'])}</b><em>w drodze do Polski</em></button>
        <button class="os__p"><b>{n(D['oferta']['na-placu'])}</b><em>na placu w Rzeszowie</em></button>
      </div>
      <p class="os__opis"><span>KANTON</span><span>RZESZÓW</span></p>
    </div>

    <div class="fl"><p class="fl__t">Marka</p><div class="tagi">{filtr_marki}
      <label class="tg"><input type="checkbox"><span>wszystkie 40</span></label></div></div>
    <div class="fl"><p class="fl__t">Napęd</p><div class="tagi">{filtr_paliwo}</div></div>
    <div class="fl"><p class="fl__t">Nadwozie</p><div class="tagi">{filtr_nadw}</div></div>
    <div class="fl"><p class="fl__t">Wyposażenie</p><div class="tagi">{filtr_wyp}</div></div>
    <div class="fl"><p class="fl__t">Zakresy</p><div class="zakresy">
      <span class="zk"><span>cena</span><input placeholder="100 000"><span>-</span><input placeholder="1 068 000"></span>
      <span class="zk"><span>rocznik</span><input placeholder="2022"><span>-</span><input placeholder="2026"></span>
      <span class="zk"><span>moc KM</span><input placeholder="95"><span>-</span><input placeholder="1 548"></span>
      <span class="zk"><span>0-100 s</span><input placeholder="2,0"><span>-</span><input placeholder="12,9"></span>
      <span class="zk"><span>zasięg km</span><input placeholder="55"><span>-</span><input placeholder="1 036"></span>
    </div></div>

    <div class="ster__stopka">
      <button class="wyczysc">Wyczyść filtry</button>
      <p class="wynikow"><b>{n(D['total'])}</b> ofert spełnia kryteria</p>
    </div>
  </div>
</header>

<main class="lista">
  <div class="w">
    <div class="lista__gora">
      <h2>Najnowsze oferty</h2>
      <select class="sort"><option>Najnowsze</option><option>Cena rosnąco</option><option>Najmniejszy przebieg</option></select>
    </div>
    {karty()}
  </div>
</main>
'''
(OUT / 'wariant-d.html').write_text(HTML, encoding='utf-8')
print('wariant-d.html:', len(HTML), 'B')
zle = [z for z in ('—', '–') if z in HTML]
print('em/en-dash:', zle if zle else 'brak')
