#!/usr/bin/env python3
"""Makieta P2 (16.09): porównywarka /porownywarka/ wzorowana na cpubenchmark.net
(singleCompare.php + compare/AvsB). Wersja versus (gen-porownywarka.py) odrzucona.

Ustalenia Janka do tej makiety:
- 4 stałe kolumny: nazwy parametrów + 3 miejsca na auta (puste miejsce = „+ Dodaj auto”),
- nazwy parametrów linkowane do słownika /wiki/ (mapa AsiaAuto_Autolink::map()),
- wiersze zgodne z kartą oferty: sekcje z buildTechSpecSections() i renderEquipment(),
  te same etykiety, ta sama lista pomijanych kluczy,
- kolory wartości wg uznania.

Dane: scratchpad porownania-dane.json (dane.php + karta.php). Wynik: drop na auratest."""
import json, html, re, sys, pathlib

SRC = sys.argv[1] if len(sys.argv) > 1 else 'porownania-dane.json'
OUT = pathlib.Path('/home/host476470/domains/auratest.pl/public_html/fe4f58fec53ctmp/primaauto-porownywarka-makieta-cpu-2026-09-16.html')
D = json.load(open(SRC))
WIKI = D.pop('_wiki')
e = html.escape
SLOTY = 3
for _ws in D.values():
    for _w in _ws:   # model bez marki (brak _serie_full_title, np. „i5”) → dopnij markę
        if not _w['model'].lower().startswith(_w['marka'].lower()):
            _w['model'] = f"{_w['marka']} {_w['model']}".strip()

PASKI = ['#1B2A4A', '#C92A2B', '#E8AC07']


def zl(x):
    return f"{int(x):,}".replace(",", " ") + " zł"


def liczba(x, dec=0):
    return f"{x:,.{dec}f}".replace(",", " ").replace('.', ',')


def num(v):
    if v in (None, '', 'NULL'):
        return None
    m = re.match(r'^\s*(-?\d+(?:[.,]\d+)?)', str(v))
    return float(m.group(1).replace(',', '.')) if m else None


def nazwa(w):
    return f"{w['model']} {w['wersja']}".strip()


def ofert_pl(n):
    return 'oferta' if n == 1 else 'oferty' if 2 <= n % 10 <= 4 and not 12 <= n % 100 <= 14 else 'ofert'


def slug(s):
    tr = str.maketrans('ąćęłńóśźż', 'acelnoszz')
    return re.sub(r'[^a-z0-9]+', '-', s.lower().translate(tr)).strip('-')


def wiki(label):
    """Etykieta parametru → link do hasła, jeśli któryś alias słownika w niej występuje (najdłuższy wygrywa)."""
    best = None
    for a in WIKI:
        al = a['alias']
        flags = re.I if a['ci'] else 0
        if re.search(r'(?<![\wąćęłńóśźż])' + re.escape(al) + r'(?![\wąćęłńóśźż])', label, flags):
            if best is None or len(al) > len(best['alias']):
                best = a
    if not best:
        return e(label)
    return f'<a class="wk" href="{e(best["url"])}" target="_blank" rel="noopener" title="Słownik: {e(best["slug"])}">{e(label)}</a>'


moc = lambda w: num(w['specs'].get('power_km'))
acc = lambda w: num(w['specs'].get('accel_s'))
def rodzaj_zasiegu(ws):
    """Porównujemy ten sam rodzaj zasięgu u wszystkich: łączny, a gdy któremuś go brak — elektryczny."""
    if all(num(w['specs'].get('range_total')) for w in ws):
        elektryki = all(w['specs'].get('range_total') == w['specs'].get('range_cltc') for w in ws)
        return 'range_total', 'Zasięg CLTC' if elektryki else 'Zasięg łączny CLTC'
    if all(num(w['specs'].get('range_cltc')) for w in ws):
        return 'range_cltc', 'Zasięg elektryczny CLTC'
    return None, None
cena = lambda w: float(w['oferty'][0]['cena']) if w['oferty'] else None


def wartosc(w, label):
    for s in w['karta_tech'].values():
        for r in s['rows']:
            if r['label'] == label:
                return r['value']
    return None


# ---------------------------------------------------------------- wstęp („is newer … around 12% faster”)
def wstep(ws):
    zd = []
    jeden_model = len({x['model'] for x in ws}) == 1
    kto = lambda w: e(w['wersja'] if jeden_model else nazwa(w))
    prem = [(wartosc(w, 'Premiera rynkowa') or '', w) for w in ws]
    m = [(moc(w), w) for w in ws]
    if all(v for v, _ in m):
        b, wb = max(m, key=lambda x: x[0])
        druga = max(v for v, x in m if x is not wb)
        if b > druga:
            zd.append(f'{kto(wb)} ma o około {round(100 * (b - druga) / druga)}% większą moc układu ({liczba(b)} KM).')
    a = [(acc(w), w) for w in ws]
    if all(v for v, _ in a) and len({v for v, _ in a}) > 1:
        v, w = min(a, key=lambda x: x[0])
        zd.append(f'Najszybciej do 100 km/h przyspiesza {kto(w)}: {liczba(v, 1)} s.')
    klucz, etykieta = rodzaj_zasiegu(ws)
    z = [(num(w['specs'].get(klucz)), w) for w in ws] if klucz else []
    if z and len({v for v, _ in z}) > 1:
        v, w = max(z, key=lambda x: x[0])
        rodzaj = 'elektryczny ' if klucz == 'range_cltc' else ''
        zd.append(f'Najdłuższy zasięg {rodzaj}ma {kto(w)}: {liczba(v)} km według normy CLTC.')
    c = [(cena(w), w) for w in ws]
    if all(v for v, _ in c):
        v, w = min(c, key=lambda x: x[0])
        if max(x for x, _ in c) > v:
            zd.append(f'Najtańszy egzemplarz w naszej ofercie to {kto(w)} za {zl(v)}.')
    n = sum(len(w['oferty']) for w in ws)
    zd.append(f'Dane techniczne i wyposażenie pochodzą z katalogu producenta, ceny z {n} aktualnych {"oferty" if n == 1 else "ofert"} Prima-Auto i są odświeżane codziennie.')
    return ' '.join(zd)


# ---------------------------------------------------------------- tabela 4-kolumnowa
def komorki(vals):
    """Puste sloty do 3 kolumn."""
    return vals + [None] * (SLOTY - len(vals))


def naglowek(ws, sticky=True):
    th = ''
    for w in ws:
        th += f'<th class="k"><button class="x" aria-label="Usuń z porównania">×</button><b>{e(w["model"])}</b><small>{e(w["wersja"])}</small></th>'
    for _ in range(SLOTY - len(ws)):
        th += '<th class="k pusty"><button class="dodaj">+ Dodaj auto</button></th>'
    return f'<thead><tr class="h"><th class="p">Parametr</th>{th}</tr></thead>'


def pct_row(label, vals, fm, wiecej, podpis):
    best = max(vals) if wiecej else min(vals)
    td = ''
    for v in vals:
        d = (v - best) / best * 100 if wiecej else (best - v) / best * 100
        klasa = 'best' if v == best else 'gorsza'
        td += f'<td class="{klasa}"><b class="big">{fm(v)}</b><small>({liczba(d, 1)}%)</small></td>'
    td += '<td class="pusty"></td>' * (SLOTY - len(vals))
    return f'<tr class="key"><th class="p">{wiki(label)}<small>{podpis}</small></th>{td}</tr>'


def tabela(ws):
    rows = '<tbody class="grp open"><tr class="sek"><th colspan="4">Podsumowanie</th></tr>'
    rows += '<tr><th class="p">Cena</th>' + ''.join(
        (f'<td><a class="buy" href="{e(w["hub"])}" target="_blank" rel="noopener">od {zl(cena(w))}</a><small class="of">{len(w["oferty"])} {ofert_pl(len(w["oferty"]))} tej wersji</small></td>'
         if w['oferty'] else '<td><span class="brak">brak aut</span></td>') for w in ws) + '<td class="pusty"></td>' * (SLOTY - len(ws)) + '</tr>'
    klucz, etykieta = rodzaj_zasiegu(ws)
    zasieg = lambda w: num(w['specs'].get(klucz)) if klucz else None
    for label, fn, fm, wiecej in [('Moc układu', moc, lambda v: liczba(v) + ' KM', True),
                                  ('0-100 km/h', acc, lambda v: liczba(v, 1) + ' s', False),
                                  (etykieta or 'Zasięg', zasieg, lambda v: liczba(v) + ' km', True)]:
        vals = [fn(w) for w in ws]
        if all(v is not None for v in vals):
            rows += pct_row(label, vals, fm, wiecej, '% różnicy do najlepszej')
    rows += '</tbody>'

    # sekcje karty oferty: techniczne, potem wyposażenie
    kolejnosc = []
    for w in ws:
        for k, s in w['karta_tech'].items():
            if ('t', k) not in [x[:2] for x in kolejnosc]:
                kolejnosc.append(('t', k, s['title']))
    for w in ws:
        for k, s in w['karta_wyp'].items():
            if ('w', k) not in [x[:2] for x in kolejnosc]:
                kolejnosc.append(('w', k, s['title']))
    razem = rozne = 0
    for i, (typ, k, title) in enumerate(kolejnosc):
        zrodlo = 'karta_tech' if typ == 't' else 'karta_wyp'
        etyk = []
        for w in ws:
            for r in w[zrodlo].get(k, {}).get('rows', []):
                if typ == 'w' and re.fullmatch(r'\d+(\.\d+)?', str(r['value'])):
                    continue   # karta: liczby tylko w danych technicznych
                if r['label'] not in etyk:
                    etyk.append(r['label'])
        if typ == 't' and k == 'podstawowe':
            etyk = [x for x in etyk if x not in ('Marka', 'Model', 'Kolor nadwozia', 'Kolor wnętrza')]  # egzemplarz, nie wersja
        body, nd = '', 0
        for label in etyk:
            vals = []
            for w in ws:
                r = next((r for r in w[zrodlo].get(k, {}).get('rows', []) if r['label'] == label), None)
                vals.append(r)
            surowe = [r['value'] if r else None for r in vals]
            rozna = len(set(surowe)) > 1
            nd += rozna
            td = ''
            for r in vals:
                if r is None:
                    td += '<td class="nie">—</td>'
                elif typ == 'w' and r.get('std') and r['value'] == 'Tak':
                    td += '<td class="tak">✓</td>'
                else:
                    td += f'<td>{e(str(r["value"]))}</td>'
            td += '<td class="pusty"></td>' * (SLOTY - len(ws))
            body += f'<tr class="{"d" if rozna else "s"}"><th class="p">{wiki(label)}</th>{td}</tr>'
        razem += len(etyk)
        rozne += nd
        otwarta = i < 1
        body_html = f'<tbody class="grp{" open" if otwarta else ""}"><tr class="sek" tabindex="0"><th colspan="4"><span class="tg"></span>{e(title)}<em>{nd} różnic · {len(etyk)} pozycji</em></th></tr>{body}</tbody>'
        rows += body_html
    return razem, rozne, f'<div class="tw"><table class="cmp">{naglowek(ws)}{rows}</table></div>'


def wykres(ws, tytul, podtytul, fn, fm):
    vals = [fn(w) for w in ws]
    if any(v is None for v in vals):
        return ''
    mx = max(vals)
    wiersze = ''.join(
        f'<tr><td class="nm">{e(nazwa(w))}</td><td class="bar"><i style="width:{100 * v / mx:.1f}%;background:{PASKI[i]}"></i></td><td class="val">{fm(v)}</td></tr>'
        for i, (w, v) in enumerate(zip(ws, vals)))
    return f'<div class="chart"><h2>{tytul}</h2><p>{podtytul}</p><table>{wiersze}</table></div>'


def strona(slug_, ws):
    h1 = ' vs '.join(nazwa(w) for w in ws)
    url = '/porownywarka/' + '-vs-'.join(slug(nazwa(w)) for w in ws) + '/'
    razem, rozne, tab = tabela(ws)
    wykresy = (wykres(ws, 'Moc układu (KM)', 'więcej = mocniej', moc, lambda v: liczba(v)) +
               wykres(ws, 'Przyspieszenie 0-100 km/h (s)', 'mniej = szybciej', acc, lambda v: liczba(v, 1)) +
               (wykres(ws, rodzaj_zasiegu(ws)[1] + ' (km)', 'więcej = dalej', lambda w: num(w['specs'].get(rodzaj_zasiegu(ws)[0])), lambda v: liczba(v)) if rodzaj_zasiegu(ws)[0] else '') +
               wykres(ws, 'Cena od (zł)', 'najtańsza aktualna oferta tej wersji', cena, lambda v: liczba(v)))
    return f'''<section class="scr" id="{slug_}">
<p class="crumbs"><a href="#">Prima-Auto</a> › <a href="#" data-s="start">Porównywarka</a> › {e(h1)}</p>
<p class="url">{e(url)} <span>noindex</span></p>
<h1>{e(h1)}</h1>
<p class="intro">{wstep(ws)}</p>
<div class="bar-opcje"><label><input type="checkbox" class="tylko"> Pokaż tylko różnice</label><span>{rozne} różnic w {razem} pozycjach · sekcje jak na karcie oferty</span></div>
{tab}
<div class="charts">{wykresy}</div>
</section>'''


# ---------------------------------------------------------------- start (singleCompare)
popularne = [(nazwa(ws[0]), nazwa(ws[1]), s) for s, ws in D.items()]
popularne += [('Zeekr 8X Max 55kWh', 'Zeekr 9X Ultra 55kWh 6-osobowy', None),
              ('BYD Leopard 5 210KM Ultra', 'BYD Leopard 8 7-osobowy', None),
              ('BYD Leopard 5', 'BYD Leopard 7', None)]
pop = ''.join(f'<tr><td class="l"><a href="#" {"data-s=" + s if s else ""}>{e(a)}</a></td><td class="vs">vs</td><td><a href="#" {"data-s=" + s if s else ""}>{e(b)}</a></td></tr>' for a, b, s in popularne)
z9 = D['zeekr-9x-wersje']
start = f'''<section class="scr on" id="start">
<p class="crumbs"><a href="#">Prima-Auto</a> › Porównywarka</p>
<p class="url">/porownywarka/ <span>noindex</span></p>
<h1>Porównywarka samochodów</h1>
<p class="intro">Wybierz do trzech wersji aut z Chin i porównaj dane techniczne, wyposażenie oraz ceny z aktualnych ofert.</p>
<div class="tw"><table class="cmp start">{naglowek(z9[:2])}
<tbody class="open"><tr><th class="p">Dodaj auto</th><td colspan="3"><div class="szuk"><input placeholder="Wpisz model lub wersję, np. „leopard 5”, „9x hyper”" aria-label="Dodaj auto do porównania">
<div class="pod"><p>Zeekr 9X · 7 wersji</p>
<a href="#"><b>Hyper 70kWh 6-osobowy</b><small>8 ofert · od 475 000 zł</small></a>
<a href="#"><b>Max 55kWh 6-osobowy</b><small>1 oferta · od 446 000 zł</small></a>
<a href="#"><b>Black Edition 70kWh 5-osobowy</b><small>1 oferta · od 540 000 zł</small></a></div></div></td></tr>
<tr><th class="p"></th><td colspan="3" style="background:#fff"><button class="btn" data-s="zeekr-9x-wersje">Porównaj</button></td></tr></tbody></table></div>
<h2>Popularne porównania (ostatnia doba)</h2>
<table class="pop">{pop}</table>
</section>'''

ekrany = start + ''.join(strona(s, ws) for s, ws in D.items())
tabs = '<button data-s="start" class="on">Start</button>' + ''.join(
    f'<button data-s="{s}">{i + 1}. {e(" vs ".join(w["model"] if len({x["model"] for x in ws}) > 1 else w["wersja"].split()[0] for w in ws))}</button>'
    for i, (s, ws) in enumerate(D.items()))

page = f'''<!doctype html><html lang="pl"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title>Porównywarka — makieta P2</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root{{--pri:#1B2A4A;--acc:#C92A2B;--sec:#5C6B7F;--bg:#F5F6F8;--bd:#E5E7EB;--tx:#1B2A4A;--lab:#374151;--row:#FAFBFC;--ok:#2F855A;--bad:#C53030;--diff:#FFF8E1}}
*{{box-sizing:border-box}}body{{margin:0;font:15px/24px Inter,system-ui,-apple-system,sans-serif;color:var(--tx);background:var(--bg)}}
.meta{{background:#111827;color:#E5E7EB;padding:8px 20px;font-size:13px}}
.meta__r{{display:flex;gap:6px;flex-wrap:wrap;align-items:center;max-width:1200px;margin:0 auto}}
.meta button{{background:#374151;color:#fff;border:0;border-radius:4px;padding:5px 9px;font:inherit;cursor:pointer}}.meta button.on{{background:var(--acc)}}
.hdr{{background:#9B0000;height:70px}}.hdr__in{{max-width:1200px;height:70px;margin:0 auto;padding:0 20px;display:flex;align-items:center;gap:28px}}
.hdr img{{height:38px}}.hdr nav{{display:flex;gap:22px;margin-left:auto}}.hdr nav a{{color:#fff;text-decoration:none;font-size:14px;font-weight:600}}
.hdr .vs{{background:#fff;color:#9B0000;border-radius:14px;padding:2px 10px;font-weight:700;font-size:13px}}
.wrap{{max-width:1200px;margin:0 auto;padding:18px 20px 60px}}
.crumbs{{font-size:13px;color:var(--sec);margin:0 0 6px}}.crumbs a{{color:var(--sec);text-decoration:none}}
.scr{{display:none}}.scr.on{{display:block}}
.url{{font-size:12px;color:var(--sec);margin:0 0 4px}}.url span{{background:#FEF3C7;color:#92400E;border-radius:4px;padding:1px 6px}}
h1{{font-size:36px;font-weight:700;color:var(--pri);margin:4px 0 14px;line-height:1.2}}
h2{{font-size:26px;font-weight:600;color:#111827;margin:44px 0 14px}}
p.intro{{margin:0 0 20px;max-width:960px}}
.bar-opcje{{display:flex;gap:18px;align-items:center;margin:0 0 10px;font-size:14px;color:var(--sec)}}.bar-opcje label{{color:var(--lab);font-weight:600;cursor:pointer}}
.tw{{background:#fff;border-radius:8px;overflow:hidden;border:1px solid var(--bd)}}
table.cmp{{border-collapse:separate;border-spacing:0;width:100%;table-layout:fixed}}
.cmp th,.cmp td{{padding:9px 13px;text-align:left;font-size:15px;vertical-align:top;border-bottom:1px solid var(--bd)}}
.cmp th.p{{width:28%;font-weight:600;color:var(--lab);background:#F8FAFC;border-right:1px solid var(--bd)}}
.cmp td{{width:24%;color:var(--tx)}}.cmp tbody tr:nth-child(even) td{{background:var(--row)}}
.cmp thead th{{position:sticky;top:0;background:var(--pri);color:#fff;z-index:2;vertical-align:top;border-bottom:0}}
.cmp thead th.p{{background:var(--pri);color:#fff}}
.cmp th.k{{position:sticky;top:0;padding-right:36px}}.cmp th.k b{{display:block;font-size:16px}}.cmp th.k small{{display:block;color:#B0BEC5;font-weight:400;font-size:13px;line-height:18px}}
.cmp th.k:nth-child(2){{box-shadow:inset 0 -4px {PASKI[0]}}}.cmp th.k:nth-child(3){{box-shadow:inset 0 -4px {PASKI[1]}}}.cmp th.k:nth-child(4){{box-shadow:inset 0 -4px {PASKI[2]}}}
.cmp th.k:nth-child(2){{box-shadow:inset 0 -4px #8FA3BF}}
.cmp .x{{position:absolute;right:10px;top:10px;border:0;background:rgba(255,255,255,.18);color:#fff;width:22px;height:22px;border-radius:4px;cursor:pointer;line-height:1}}
.cmp th.pusty{{box-shadow:none!important;vertical-align:middle}}.dodaj{{width:100%;border:1px dashed rgba(255,255,255,.55);background:transparent;color:#fff;padding:10px 4px;border-radius:6px;font:600 14px Inter;cursor:pointer}}
.cmp td.pusty{{background:repeating-linear-gradient(135deg,#fff,#fff 7px,#F6F7F9 7px,#F6F7F9 14px)!important}}
.cmp tr.sek th{{background:#EEF2F6;color:var(--pri);font-weight:700;font-size:15px;cursor:pointer;padding:11px 13px}}.cmp tr.sek em{{font-style:normal;font-weight:400;color:var(--sec);margin-left:10px;font-size:13px}}
.cmp tr.sek .tg:before{{content:"▸";display:inline-block;width:16px;color:var(--sec)}}.cmp tbody.open tr.sek .tg:before{{content:"▾"}}
.cmp tbody:not(.open) tr:not(.sek){{display:none}}
.scr.tylko-r .cmp tr.s{{display:none}}
.cmp tr.d td{{background:var(--diff)!important;font-weight:600}}
td.tak{{color:var(--ok);font-weight:700;font-size:17px}}td.nie{{color:#A0AEC0}}
.buy{{display:inline-block;background:var(--acc);color:#fff;font-weight:700;padding:5px 12px;border-radius:4px;text-decoration:none;white-space:nowrap}}.buy:hover{{background:#B52828}}.cmp small.of{{display:block;color:var(--sec);font-size:13px;margin-top:3px}}.brak{{color:var(--sec)}}
tr.key th small{{display:block;font-size:12px;font-weight:400;color:var(--sec)}}tr.key .big{{display:block;font-size:24px;line-height:1.25}}tr.key small{{font-style:italic;font-size:13px}}
tr.key td.best .big{{color:var(--ok)}}tr.key td.gorsza .big{{color:var(--pri)}}tr.key td.gorsza small{{color:var(--bad)}}tr.key td.best small{{color:var(--sec)}}
a.wk{{color:inherit;text-decoration:underline dotted #9AA8B6;text-underline-offset:4px}}a.wk:hover{{color:var(--acc)}}
.charts{{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:32px}}
.chart{{background:#fff;border:1px solid var(--bd);border-radius:8px;padding:16px 18px}}.chart h2{{margin:0;font-size:19px;color:#374151}}.chart p{{margin:0 0 10px;font-size:13px;color:var(--sec);font-style:italic}}
.chart table{{width:100%;border-collapse:collapse}}.chart td{{padding:7px 4px;font-size:14px;border-top:1px solid var(--bd)}}.chart .nm{{width:40%}}.chart .bar i{{display:block;height:10px;border-radius:5px}}.chart .val{{width:90px;text-align:right;font-weight:600}}
.start td{{width:auto}}.szuk{{position:relative;max-width:640px}}.szuk input{{width:100%;padding:11px 14px;border:1px solid #cfd6dc;border-radius:6px;font:inherit}}
.pod{{display:none;position:absolute;left:0;right:0;top:48px;background:#fff;border:1px solid #cfd6dc;border-radius:6px;z-index:5;box-shadow:0 6px 16px rgba(0,0,0,.12)}}.szuk:focus-within .pod{{display:block}}
.pod p{{margin:0;padding:6px 12px;background:var(--bg);font-size:13px;color:var(--sec)}}.pod a{{display:block;padding:9px 12px;color:var(--tx);text-decoration:none;border-top:1px solid var(--bd)}}.pod small{{display:block;color:var(--sec)}}
.btn{{background:var(--acc);color:#fff;border:0;padding:11px 28px;border-radius:6px;font:700 15px Inter;cursor:pointer}}
table.pop{{width:100%;border-collapse:collapse;background:#fff;border:1px solid var(--bd);border-radius:8px;overflow:hidden}}.pop td{{padding:9px 13px;border-bottom:1px solid var(--bd)}}.pop tr:nth-child(even) td{{background:var(--row)}}
.pop td.l{{text-align:right;width:46%}}.pop td.vs{{text-align:center;width:8%;color:var(--sec);font-weight:600}}.pop a{{color:var(--pri);text-decoration:none;font-weight:600}}.pop a:hover{{color:var(--acc)}}
</style></head><body>
<div class="meta"><div class="meta__r"><strong>Makieta P2 · desktop 1200 px</strong>{tabs}</div></div>
<div class="hdr"><div class="hdr__in"><img src="https://primaauto.com.pl/wp-content/uploads/2026/03/primaauto-logo-szerokie.png" alt="Prima-Auto"><nav><a href="#">Samochody z Chin</a><a href="#">Dostępne od ręki</a><a href="#">W drodze</a><a href="#">Marki</a><a href="#">Klienci</a><a href="#">Informacje</a><a href="#">Wiedza</a><a href="#">Kontakt</a></nav><span class="vs" title="Porównanie">VS 2</span></div></div>
<div class="wrap">{ekrany}</div>
<script>
const pokaz=s=>{{if(!document.getElementById(s))return;document.querySelectorAll('.scr').forEach(x=>x.classList.toggle('on',x.id===s));document.querySelectorAll('.meta button[data-s]').forEach(b=>b.classList.toggle('on',b.dataset.s===s));scrollTo(0,0)}};
document.addEventListener('click',ev=>{{
  const t=ev.target.closest('[data-s]');if(t){{ev.preventDefault();pokaz(t.dataset.s);return}}
  const sek=ev.target.closest('tr.sek');if(sek&&!ev.target.closest('a'))sek.parentElement.classList.toggle('open');
}});
document.querySelectorAll('.tylko').forEach(c=>c.addEventListener('change',()=>c.closest('.scr').classList.toggle('tylko-r',c.checked)));

</script></body></html>'''
OUT.write_text(page)
print(OUT, len(page))
