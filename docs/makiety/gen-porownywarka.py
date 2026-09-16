#!/usr/bin/env python3
"""Makieta P (16.09): porównywarka wersji /porownywarka/ w układzie versus.com (bez oceny punktowej).
Pięć porównań na prawdziwych danych: extra_prep po translatorze (oferta o najpełniejszych danych
dla danego specid) + wiersz wp7j_asiaauto_specs + żywe oferty tej wersji.
Dane: scratchpad porownania-dane.json (dane.php). Wynik: drop na auratest."""
import json, html, re, sys, pathlib

SRC = sys.argv[1] if len(sys.argv) > 1 else 'porownania-dane.json'
OUT = pathlib.Path('/home/host476470/domains/auratest.pl/public_html/fe4f58fec53ctmp/primaauto-porownywarka-makieta-2026-09-16.html')
D = json.load(open(SRC))
e = html.escape
for _ws in D.values():
    for _w in _ws:   # model bez marki (brak _serie_full_title, np. „i5”) → dopnij markę
        if not _w['model'].lower().startswith(_w['marka'].lower()):
            _w['model'] = f"{_w['marka']} {_w['model']}".strip()

TYTUL = {
    'zeekr-9x-wersje': 'Wersje jednego modelu',
    'denza-n8l-vs-nio-es8': 'SUV 6–7 os.',
    'denza-z9-gt-vs-byd-han-l': 'Sedan PHEV',
    'bmw-i5-vs-xiaomi-su7': 'Sedan EV (losowe)',
    'leapmotor-d19-vs-li-l8': 'SUV EREV (losowe)',
}
KOLORY = ['#1B2A4A', '#C92A2B', '#E8AC07']
WYPOSAZENIE = ('safety', 'lights', 'comfort', 'seats', 'mirrors', 'multimedia', 'parking', 'adas', 'remote', 'drive_modes')
PROG_KLUCZY = 250


def zl(x):
    return f"{int(x):,}".replace(",", " ") + " zł"


def num(v):
    if v in (None, '', 'NULL'):
        return None
    m = re.match(r'^\s*(-?\d+(?:[.,]\d+)?)\s*[^\d\-]*$', str(v))
    return float(m.group(1).replace(',', '.')) if m else None


def item(w, label):
    for cat in w['dane'].values():
        for i in cat.get('items', []):
            if i['label'] == label:
                return i['value']
    return None


def fmt(x):
    return (f"{x:,.1f}" if x % 1 else f"{x:,.0f}").replace(",", " ")


def nazwa(w):
    model = w['model'] or w['marka']
    return f"{model} {w['wersja']}".strip()


# ---------------------------------------------------------------- metryki
# (etykieta, getter, jednostka, kierunek: +1 więcej lepiej / -1 mniej lepiej)
def m_spec(k):
    return lambda w: num(w['specs'].get(k))


def m_item(label):
    return lambda w: num(item(w, label))


METRYKI = {
    'cena':     ('Cena od (oferty)', lambda w: float(w['oferty'][0]['cena']) if w['oferty'] else None, 'zł', -1),
    'moc':      ('Moc układu', m_spec('power_km'), 'KM', +1),
    'acc':      ('0–100 km/h', m_spec('accel_s'), 's', -1),
    'vmax':     ('Prędkość maksymalna', m_item('Prędkość maksymalna'), 'km/h', +1),
    'zas_l':    ('Zasięg łączny (CLTC)', m_spec('range_total'), 'km', +1),
    'zas_e':    ('Zasięg elektryczny (CLTC)', m_spec('range_cltc'), 'km', +1),
    'bat':      ('Pojemność baterii', m_spec('battery_kwh'), 'kWh', +1),
    'dl':       ('Długość', m_spec('length_mm'), 'mm', +1),
    'osie':     ('Rozstaw osi', m_item('Rozstaw osi'), 'mm', +1),
    'miejsca':  ('Liczba miejsc', m_spec('seats'), '', +1),
    'masa':     ('Masa własna', m_item('Masa własna'), 'kg', -1),
    'wyp':      ('Pozycje wyposażenia', lambda w: float(sum(len(w['dane'].get(c, {}).get('items', [])) for c in WYPOSAZENIE)), '', +1),
}
OBSZARY = [
    ('Cena', ['cena']),
    ('Osiągi', ['moc', 'acc']),
    ('Zasięg', ['zas_l', 'zas_e']),
    ('Przestrzeń', ['dl', 'osie', 'miejsca']),
    ('Wyposażenie', ['wyp']),
]


def rozstrzygnij(ws, klucz):
    """Zwraca (indeks zwycięzcy | None, wartości). Parametr liczy się tylko, gdy mają go WSZYSTKIE wersje."""
    _, fn, _, kier = METRYKI[klucz]
    vals = [fn(w) for w in ws]
    if any(v is None for v in vals):
        return None, vals
    best = max(vals) if kier > 0 else min(vals)
    if vals.count(best) > 1:
        return -1, vals          # remis
    return vals.index(best), vals


def obszar(ws, klucze):
    if klucze == ['wyp'] and any(w['kluczy'] < PROG_KLUCZY for w in ws):
        return None, 'za mało danych u jednej z wersji'
    if 'zas_e' in klucze and (len({w['specs'].get('fuel') for w in ws}) > 1 or zas_e_dubel(ws)):
        klucze = ['zas_l']   # PHEV vs EV: zasięg elektryczny hybrydy to nie to samo co zasięg elektryka
    wygrane = [0] * len(ws)
    uzyte = 0
    for k in klucze:
        idx, _ = rozstrzygnij(ws, k)
        if idx is None:
            continue
        uzyte += 1
        if idx >= 0:
            wygrane[idx] += 1
    if uzyte == 0:
        return None, 'brak wspólnych danych'
    top = max(wygrane)
    if top == 0 or wygrane.count(top) > 1:
        return -1, 'remis'
    return wygrane.index(top), ''


def przewaga(vals, kier, jedn):
    best = max(vals) if kier > 0 else min(vals)
    reszta = [v for v in vals if v != best]
    if not reszta:
        return ''
    druga = max(reszta) if kier > 0 else min(reszta)
    d = abs(best - druga)
    if jedn in ('zł',):
        return f"{'−' if kier < 0 else '+'}{zl(d)}"
    if druga and d / druga >= 0.25 and jedn not in ('s',):
        return f"+{round(100 * d / druga)}%"
    znak = '−' if kier < 0 else '+'
    return f"{znak}{fmt(d)} {jedn}".strip()


# ---------------------------------------------------------------- render
def ofert_pl(n):
    return 'oferta' if n == 1 else 'oferty' if 2 <= n % 10 <= 4 and not 12 <= n % 100 <= 14 else 'ofert'


def zas_e_dubel(ws):
    # elektryk: zasięg elektryczny == łączny, drugi wiersz nic nie wnosi
    return all(w['specs'].get('range_cltc') == w['specs'].get('range_total') for w in ws)


def karta_hero(w, i):
    ofert = len(w['oferty'])
    cena = f"od <b>{zl(w['oferty'][0]['cena'])}</b> · {ofert} {ofert_pl(ofert)}" if ofert else 'obecnie brak aut'
    return f'''<div class="hero__v" style="--k:{KOLORY[i]}">
  <button class="x" title="Usuń z porównania" aria-label="Usuń">×</button>
  <img src="{e(w['zdjecie'] or '')}" alt="" loading="lazy">
  <p class="hero__marka">{e(w['model'])}</p>
  <p class="hero__wersja">{e(w['wersja'])}</p>
  <p class="hero__cena">{cena}</p>
</div>'''


def sekcja(slug, ws):
    n = len(ws)
    h1 = ' vs '.join(nazwa(w) for w in ws) if len({w['model'] for w in ws}) > 1 else f"{ws[0]['model']}: " + ' vs '.join(w['wersja'] for w in ws)

    # obszary + werdykt
    wyniki = [(nazwa_o, *obszar(ws, kl), kl) for nazwa_o, kl in OBSZARY]
    licz = [sum(1 for _, idx, _, _ in wyniki if idx == i) for i in range(n)]
    rozstrzygniete = sum(1 for _, idx, _, _ in wyniki if idx is not None)
    top = max(licz)
    if top and licz.count(top) == 1:
        z = ws[licz.index(top)]
        werdykt = f'Wybierz <span style="color:{KOLORY[licz.index(top)]}">{e(nazwa(z))}</span> — prowadzi w {top} z {len(OBSZARY)} obszarów.'
    else:
        werdykt = 'Remis — żadna wersja nie prowadzi w większości obszarów.'

    kafle = ''
    for nazwa_o, idx, uwaga, kl in wyniki:
        wiersze = ''
        for k in kl:
            if k == 'zas_e' and zas_e_dubel(ws):
                continue
            lab, fn, jedn, kier = METRYKI[k]
            win, vals = rozstrzygnij(ws, k)
            cells = ''.join(
                f'<span class="{"w" if win == i else ""}" style="--k:{KOLORY[i]}">{(zl(v) if jedn == "zł" else fmt(v) + (" " + jedn if jedn else "")) if v is not None else "—"}</span>'
                for i, v in enumerate(vals))
            wiersze += f'<div class="kafel__r"><em>{lab}</em>{cells}</div>'
        if idx is None:
            glowa = f'<b class="nr">nierozstrzygnięty</b><small>{uwaga}</small>'
        elif idx == -1:
            glowa = '<b class="nr">remis</b>'
        else:
            glowa = f'<b style="color:{KOLORY[idx]}">{e(ws[idx]["wersja"] if len({w["model"] for w in ws}) == 1 else ws[idx]["model"])}</b>'
        kafle += f'<div class="kafel" style="--n:{n}"><p class="kafel__t">{nazwa_o}</p>{glowa}{wiersze}</div>'

    # największe różnice
    roz = []
    for k in ['moc', 'acc', 'vmax', 'zas_l', 'zas_e', 'bat', 'dl', 'osie', 'miejsca', 'masa', 'cena']:
        if k == 'zas_e' and zas_e_dubel(ws):
            continue
        lab, fn, jedn, kier = METRYKI[k]
        win, vals = rozstrzygnij(ws, k)
        if win is None or win == -1:
            continue
        lo = min(vals)
        rel = (max(vals) - lo) / lo if lo else 0
        roz.append((rel, k, win, vals))
    roz.sort(reverse=True)
    naj = ''
    for rel, k, win, vals in roz[:6]:
        lab, fn, jedn, kier = METRYKI[k]
        cells = ''.join(f'<td class="{"w" if win == i else ""}">{zl(v) if jedn == "zł" else fmt(v) + (" " + jedn if jedn else "")}</td>' for i, v in enumerate(vals))
        naj += f'<tr><th>{lab}</th>{cells}<td><span class="chip" style="--k:{KOLORY[win]}">{przewaga(vals, kier, jedn)}</span></td></tr>'
    kol = ''.join(f'<th style="color:{KOLORY[i]}">{e(w["wersja"] if len({x["model"] for x in ws}) == 1 else w["model"])}</th>' for i, w in enumerate(ws))

    # oferty
    of = ''
    for i, w in enumerate(ws):
        pozycje = ''
        for o in w['oferty'][:3]:
            status = {'on_lot': 'Na placu', 'in_transit': 'W drodze', 'stock': 'Na placu'}.get(o['rezerwacja'] or '', 'Do sprowadzenia')
            przeb = f"{int(o['przebieg']):,} km".replace(",", " ") if o['przebieg'] else 'nowy'
            pozycje += f'''<a class="of" href="{e(o['url'])}" target="_blank" rel="noopener">
  <img src="{e(o['miniatura'] or '')}" alt="" loading="lazy">
  <span><b>{zl(o['cena'])}</b><small>{o['rok']} · {przeb} · {status}</small></span></a>'''
        wiecej = f'<a class="of__all" href="{e(w["hub"])}" target="_blank" rel="noopener">{len(w["oferty"])} {ofert_pl(len(w["oferty"]))} tej wersji · cały model {e(w["model"])} →</a>'
        of += f'<div class="ofk" style="--k:{KOLORY[i]}"><p class="ofk__t">{e(nazwa(w))}</p>{pozycje}{wiecej}</div>'

    # wszystkie dane
    kategorie = []
    for w in ws:
        for cid, c in w['dane'].items():
            if cid not in [x[0] for x in kategorie]:
                kategorie.append((cid, c.get('label', cid)))
    wszystkie = ''
    razem = rozne = 0
    for cid, clab in kategorie:
        etykiety = []
        for w in ws:
            for it in w['dane'].get(cid, {}).get('items', []):
                if it['label'] not in etykiety:
                    etykiety.append(it['label'])
        rr, same = '', ''
        nr = ns = 0
        for lab in etykiety:
            vals = []
            for w in ws:
                v = next((it['value'] for it in w['dane'].get(cid, {}).get('items', []) if it['label'] == lab), None)
                vals.append(v)
            if cid in WYPOSAZENIE:
                show = ['<i class="ok">✓</i>' if v == 'Tak' else ('<i class="no">—</i>' if v is None else e(v)) for v in vals]
            else:
                show = [e(v) if v is not None else '<i class="no">—</i>' for v in vals]
            wiersz = f'<tr><th>{e(lab)}</th>' + ''.join(f'<td>{s}</td>' for s in show) + '</tr>'
            if len(set(vals)) > 1:
                rr += wiersz.replace('<tr>', '<tr class="d">', 1)
                nr += 1
            else:
                same += wiersz
                ns += 1
        razem += nr + ns
        rozne += nr
        sam = f'<details class="same"><summary>Pokaż {ns} identycznych</summary><table>{same}</table></details>' if ns else ''
        wszystkie += f'''<details class="grp" {"open" if nr else ""}><summary>{e(clab)} <em>{nr} różnic · {nr + ns} parametrów</em></summary>
<div class="tw"><table>{rr}</table></div>{sam}</details>'''

    uwagi = []
    for w in ws:
        if w['kluczy'] < PROG_KLUCZY:
            uwagi.append(f'{e(nazwa(w))}: dane z {w["kluczy"]} pól (niepełne) — puste pola to brak informacji, nie brak funkcji.')
    uwagi_html = ''.join(f'<p class="uwaga">{u}</p>' for u in uwagi)

    url = '/porownywarka/' + '-vs-'.join(re.sub(r'[^a-z0-9]+', '-', nazwa(w).lower()).strip('-') for w in ws) + '/'
    return f'''<section class="por" id="{slug}" data-kol="{n}">
<p class="url">{e(url)} <span>noindex</span></p>
<h1>{e(h1)}</h1>
<div class="hero" style="--n:{n}">{''.join(karta_hero(w, i) for i, w in enumerate(ws))}
  {'<button class="hero__add">+ Dodaj wersję</button>' if n < 3 else ''}
</div>
<div class="werdykt"><p class="lbl">Werdykt</p><p class="txt">{werdykt}</p>
<p class="baza">Liczymy tylko parametry dostępne dla wszystkich porównywanych wersji · {rozstrzygniete} z {len(OBSZARY)} obszarów rozstrzygnięte · <a href="#">Jak porównujemy →</a></p></div>
<h2>W czym się różnią</h2>
<div class="kafle">{kafle}</div>
<h2>Największe różnice</h2>
<div class="tw"><table class="naj"><thead><tr><th>Parametr</th>{kol}<th>Przewaga</th></tr></thead><tbody>{naj}</tbody></table></div>
<h2>Najlepsza cena teraz</h2>
<div class="oferty" style="--n:{n}">{of}</div>
<h2>Wszystkie dane techniczne <em>{rozne} różnic z {razem}</em></h2>
{uwagi_html}
<div class="dane" style="--n:{n}"><div class="dane__head"><span></span>{''.join(f'<b style="color:{KOLORY[i]}">{e(w["wersja"] if len({x["model"] for x in ws}) == 1 else w["model"])}</b>' for i, w in enumerate(ws))}</div>{wszystkie}</div>
</section>'''


# ---------------------------------------------------------------- strona
sekcje = ''.join(sekcja(s, ws) for s, ws in D.items())
tabs = ''.join(f'<button data-t="{s}"{" class=on" if i == 0 else ""}>{i + 1}. {e(TYTUL.get(s, s))}</button>' for i, s in enumerate(D))
popularne = ''.join(f'<a href="#{s}" data-t="{s}">{e(" vs ".join((w["model"] if len({x["model"] for x in ws}) > 1 else w["wersja"]) for w in ws))}</a>' for s, ws in D.items())

page = f'''<!doctype html><html lang="pl"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title>Porównywarka — makieta P</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root{{--pri:#1B2A4A;--acc:#C92A2B;--sec:#5C6B7F;--bg:#F5F6F8;--bd:#E1E4E8;--tx:#2D3748;--ok:#38A169}}
*{{box-sizing:border-box}}body{{margin:0;font:15px/1.5 Inter,system-ui,sans-serif;color:var(--tx);background:var(--bg)}}
.meta{{background:#111827;color:#E5E7EB;padding:10px 16px;font-size:13px;position:sticky;top:0;z-index:20}}
.meta__row{{display:flex;gap:8px;flex-wrap:wrap;align-items:center;max-width:1200px;margin:0 auto}}
.meta button{{background:#374151;color:#fff;border:0;border-radius:6px;padding:6px 10px;font:inherit;cursor:pointer}}
.meta button.on{{background:var(--acc)}}.meta .sep{{flex:1}}
.hdr{{background:#9B0000;color:#fff}}.hdr__in{{max-width:1200px;margin:0 auto;padding:12px 16px;display:flex;gap:12px;align-items:center}}
.hdr b{{font-size:18px}}.hdr .vs{{margin-left:auto;background:rgba(255,255,255,.15);border-radius:20px;padding:4px 12px;font-weight:700;font-size:13px}}
.wrap{{max-width:1200px;margin:0 auto;padding:0 16px 48px;transition:max-width .2s}}
body.tel .wrap{{max-width:390px;background:var(--bg);box-shadow:0 0 0 1px var(--bd)}}
.szuk{{margin:16px 0 4px;position:relative}}.szuk input{{width:100%;padding:12px 14px 12px 40px;border:1px solid var(--bd);border-radius:8px;font:inherit;background:#fff}}
.szuk:before{{content:"⌕";position:absolute;left:14px;top:9px;font-size:20px;color:var(--sec)}}
.szuk__pod{{position:absolute;left:0;right:0;top:48px;background:#fff;border:1px solid var(--bd);border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,.1);z-index:5;display:none}}
.szuk:focus-within .szuk__pod{{display:block}}.szuk__pod p{{margin:0;padding:6px 14px;font-size:12px;color:var(--sec);background:var(--bg)}}
.szuk__pod a{{display:flex;justify-content:space-between;padding:9px 14px;color:var(--tx);text-decoration:none;border-top:1px solid var(--bd)}}.szuk__pod a small{{color:var(--sec)}}
.por{{display:none}}.por.on{{display:block}}
.url{{font-size:12px;color:var(--sec);margin:14px 0 0;word-break:break-all}}.url span{{background:#FEF3C7;color:#92400E;border-radius:4px;padding:1px 6px;margin-left:4px}}
h1{{font-size:26px;line-height:1.25;color:var(--pri);margin:6px 0 16px}}
h2{{font-size:20px;color:var(--pri);margin:36px 0 12px}}h2 em{{font-style:normal;font-weight:400;font-size:14px;color:var(--sec);margin-left:6px}}
.hero{{display:grid;grid-template-columns:repeat(var(--n),1fr);gap:12px;position:relative}}
.hero__v{{background:#fff;border-radius:8px;padding:12px;border-top:4px solid var(--k);position:relative;box-shadow:0 1px 4px rgba(0,0,0,.08)}}
.hero__v img{{width:100%;aspect-ratio:16/10;max-height:190px;object-fit:cover;border-radius:6px;background:var(--bg)}}
.hero__v .x{{position:absolute;right:6px;top:6px;border:0;background:rgba(0,0,0,.55);color:#fff;border-radius:50%;width:26px;height:26px;cursor:pointer;z-index:2}}
.hero__marka{{margin:8px 0 0;font-weight:700;color:var(--pri)}}.hero__wersja{{margin:0;font-size:13px;color:var(--sec)}}.hero__cena{{margin:6px 0 0;font-size:13px}}
.hero__add{{position:absolute;right:-6px;top:40%;transform:translateX(100%);border:1px dashed var(--sec);background:#fff;border-radius:8px;padding:10px;cursor:pointer}}
.werdykt{{text-align:center;margin:20px 0 0;padding:16px;background:#fff;border-radius:8px}}.werdykt .lbl{{margin:0;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:var(--sec)}}
.werdykt .txt{{margin:4px 0;font-size:20px;font-weight:700;color:var(--pri)}}.werdykt .baza{{margin:0;font-size:12px;color:var(--sec)}}.werdykt a{{color:var(--sec)}}
.kafle{{display:grid;grid-template-columns:repeat(5,1fr);gap:10px}}
.kafel{{background:#fff;border-radius:8px;padding:12px;box-shadow:0 1px 4px rgba(0,0,0,.08)}}
.kafel__t{{margin:0;font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:var(--sec)}}.kafel>b{{display:block;font-size:15px;margin:2px 0 8px}}.kafel .nr{{color:var(--sec);font-weight:600}}
.kafel small{{display:block;color:var(--sec);font-size:11px;margin:-6px 0 8px}}
.kafel__r{{font-size:12px;border-top:1px solid var(--bd);padding-top:6px;margin-top:6px}}.kafel__r em{{display:block;font-style:normal;color:var(--sec)}}
.kafel__r span{{display:inline-block;margin-right:8px;padding-left:8px;border-left:3px solid var(--k)}}.kafel__r span.w{{font-weight:700}}
.tw{{overflow-x:auto}}table{{border-collapse:collapse;width:100%;background:#fff}}
th,td{{padding:9px 10px;border-bottom:1px solid var(--bd);text-align:left;font-size:14px;vertical-align:top}}
.naj thead th{{font-size:12px;text-transform:uppercase;letter-spacing:.04em}}.naj td.w{{font-weight:700}}
.chip{{background:color-mix(in srgb,var(--k) 12%,#fff);color:var(--k);border-radius:12px;padding:2px 8px;font-size:12px;font-weight:700;white-space:nowrap}}
.oferty{{display:grid;grid-template-columns:repeat(var(--n),1fr);gap:12px}}
.ofk{{background:#fff;border-radius:8px;padding:12px;border-top:4px solid var(--k)}}.ofk__t{{margin:0 0 8px;font-weight:700;color:var(--pri);font-size:14px}}
.of{{display:flex;gap:10px;align-items:center;padding:8px 0;border-top:1px solid var(--bd);color:var(--tx);text-decoration:none}}
.of img{{width:84px;height:60px;object-fit:cover;border-radius:4px;background:var(--bg)}}.of b{{display:block;color:var(--acc)}}.of small{{color:var(--sec)}}
.of__all{{display:block;margin-top:8px;font-size:13px;color:var(--pri);font-weight:600}}
.uwaga{{background:#FEF3C7;color:#78350F;border-radius:6px;padding:8px 12px;font-size:13px;margin:0 0 10px}}
.dane__head{{display:grid;grid-template-columns:minmax(120px,1.3fr) repeat(var(--n),1fr);gap:0;background:#fff;position:sticky;top:44px;z-index:3;border-bottom:2px solid var(--bd);padding:8px 10px;font-size:13px}}
.grp{{background:#fff;margin-top:8px;border-radius:8px}}.grp>summary{{padding:12px;font-weight:700;color:var(--pri);cursor:pointer}}.grp summary em{{font-style:normal;font-weight:400;color:var(--sec);font-size:13px;margin-left:6px}}
.dane table{{table-layout:fixed}}.dane th{{width:calc(100% * 1.3 / (1.3 + var(--n)));font-weight:400;color:var(--sec);position:sticky;left:0;background:#fff}}
.dane tr.d td{{font-weight:600}}.ok{{color:var(--ok);font-style:normal;font-weight:700}}.no{{color:#A0AEC0;font-style:normal}}
.same>summary{{padding:8px 12px;font-size:13px;color:var(--sec);cursor:pointer}}
.pop{{display:flex;flex-wrap:wrap;gap:8px}}.pop a{{background:#fff;border:1px solid var(--bd);border-radius:20px;padding:6px 12px;color:var(--pri);text-decoration:none;font-size:13px}}
@media (max-width:760px){{.kafle{{grid-template-columns:1fr 1fr}}.kafle .kafel:last-child{{grid-column:1/-1}}}}
body.tel .kafle{{grid-template-columns:1fr 1fr}}body.tel .kafle .kafel:last-child{{grid-column:1/-1}}
body.tel h1{{font-size:21px}}body.tel .werdykt .txt{{font-size:17px}}body.tel .hero{{gap:6px}}body.tel .hero__v{{padding:6px}}
body.tel .hero__wersja,body.tel .hero__cena{{font-size:11px}}body.tel .hero__marka{{font-size:13px}}
body.tel .oferty{{grid-template-columns:1fr}}body.tel .of img{{width:64px;height:46px}}
body.tel th,body.tel td{{font-size:12px;padding:7px 6px}}body.tel .hero__add{{position:static;transform:none;grid-column:1/-1}}
@media (max-width:600px){{h1{{font-size:21px}}.hero{{gap:6px}}.hero__v{{padding:6px}}.hero__wersja,.hero__cena{{font-size:11px}}.oferty{{grid-template-columns:1fr}}th,td{{font-size:12px;padding:7px 6px}}.hero__add{{position:static;transform:none;grid-column:1/-1}}}}
</style></head><body>
<div class="meta"><div class="meta__row"><strong>Makieta P · porównywarka</strong>{tabs}<span class="sep"></span>
<button id="tel">Podgląd telefonu 390 px</button></div></div>
<div class="hdr"><div class="hdr__in"><b>Prima-Auto</b><span>Porównywarka samochodów</span><span class="vs" title="Schowek porównania">VS 2</span></div></div>
<div class="wrap">
<div class="szuk"><input placeholder="Wpisz model lub wersję, np. „leopard 5”, „9x hyper”" aria-label="Dodaj wersję do porównania">
<div class="szuk__pod"><p>Zeekr 9X · 7 wersji</p>
<a href="#">Ultra 55kWh 6-osobowy <small>12 ofert od 414 000 zł</small></a><a href="#">Ultra 70kWh 6-osobowy <small>13 ofert od 419 000 zł</small></a>
<a href="#">Hyper 70kWh 6-osobowy <small>8 ofert od 475 000 zł</small></a><a href="#">Black Edition 70kWh 5-osobowy <small>1 oferta od 540 000 zł</small></a></div></div>
{sekcje}
<h2>Popularne porównania</h2><div class="pop">{popularne}</div>
</div>
<script>
const pokaz=s=>{{document.querySelectorAll('.por').forEach(p=>p.classList.toggle('on',p.id===s));document.querySelectorAll('.meta button[data-t]').forEach(b=>b.classList.toggle('on',b.dataset.t===s));scrollTo(0,0)}};
document.querySelectorAll('[data-t]').forEach(b=>b.addEventListener('click',ev=>{{ev.preventDefault();pokaz(b.dataset.t)}}));
pokaz(document.querySelector('.por').id);
document.getElementById('tel').addEventListener('click',ev=>{{document.body.classList.toggle('tel');ev.target.classList.toggle('on')}});
</script></body></html>'''
OUT.write_text(page)
print(OUT, len(page))
