#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Generator raportu kosztorysowego Prima Auto.

Dane: docs/kosztorys/dane/*.json  →  raport HTML (self-contained).

Użycie:
    python3 build.py                 # generuje raport.html obok skryptu
    python3 build.py --deploy        # + kopiuje na zahaszowany URL auratest.pl

Odświeżanie: edytuj pliki w dane/ (dopisz taski/pozycje), uruchom ponownie.
"""
import json, os, shutil, sys, datetime, html

BASE = os.path.dirname(os.path.abspath(__file__))
DANE = os.path.join(BASE, 'dane')
OUT = os.path.join(BASE, 'raport.html')
DEPLOY_DIR = os.path.expanduser('~/domains/auratest.pl/public_html/pa-kosztorys-3ee728365b3d1a5b895e')

OBSZARY = {
    'budowa-softu':      'Rozwój oprogramowania',
    'import-sync':       'Import i dane katalogu',
    'che168':            'Drugie źródło (Che168)',
    'seo-content':       'SEO i treści',
    'ads-analityka':     'Reklamy i analityka',
    'prawne':            'Obsługa prawna',
    'infra-utrzymanie':  'Infrastruktura i utrzymanie',
    'ux-front':          'Wygląd i użyteczność',
}

def load(name):
    with open(os.path.join(DANE, name), encoding='utf-8') as f:
        return json.load(f)

def esc(s):
    return html.escape(str(s), quote=False)

def fmt_h(v):
    if isinstance(v, float) and v == int(v):
        v = int(v)
    return str(v).replace('.', ',')

meta   = load('meta.json')
etap1  = load('etap1.json')
etap2  = load('etap2.json')
etap3  = load('etap3.json')
regularne = load('regularne.json')
koszty = load('koszty.json')

# Przełącznik kolumny "rynkowo". False = kolumna ukryta w raporcie (dane zostają w dane/*.json).
# Decyzja Janka 2026-07-14. Przywrócenie: "pokazuj_rynkowo": true w meta.json + przebudowa.
SHOW_R = bool(meta.get('pokazuj_rynkowo', True))

def th_r(label):
    """Nagłówek kolumny rynkowej — pusty string gdy ukryta."""
    return f'<th class="num">{label}</th>' if SHOW_R else ''

def td_r(value):
    """Komórka kolumny rynkowej — pusty string gdy ukryta."""
    return f'<td class="num">{value}</td>' if SHOW_R else ''

e1_r = sum(b['godz_rynkowe'] for b in etap1['bloki'])
e1_f = sum(b['godz_realne']  for b in etap1['bloki'])
e2_r = sum(t['godz_rynkowe'] for t in etap2['taski'])
e2_f = sum(t['godz_realne']  for t in etap2['taski'])
e3_r_od = sum(p['rynkowo_od'] for p in etap3['pozycje'])
e3_r_do = sum(p['rynkowo_do'] for p in etap3['pozycje'])
e3_f_od = sum(p['realnie_od'] for p in etap3['pozycje'])
e3_f_do = sum(p['realnie_do'] for p in etap3['pozycje'])
reg_r_od = sum(p['rynkowo_od'] for p in regularne['pozycje'])
reg_r_do = sum(p['rynkowo_do'] for p in regularne['pozycje'])
reg_f_od = sum(p['realnie_od'] for p in regularne['pozycje'])
reg_f_do = sum(p['realnie_do'] for p in regularne['pozycje'])

# godziny per obszar (etap 2)
per_obszar = {}
for t in etap2['taski']:
    o = per_obszar.setdefault(t['obszar'], {'r': 0, 'f': 0, 'n': 0})
    o['r'] += t['godz_rynkowe']; o['f'] += t['godz_realne']; o['n'] += 1
per_obszar = dict(sorted(per_obszar.items(), key=lambda kv: -kv[1]['r']))
max_r = max(o['r'] for o in per_obszar.values())

today = datetime.date.today().strftime('%d.%m.%Y')

def rows_etap1():
    out = []
    for b in etap1['bloki']:
        out.append(f"""<tr>
<td><strong>{esc(b['nazwa'])}</strong><div class="opis">{esc(b['opis'])}</div><div class="skala">{esc(b['skala'])}</div></td>
{td_r(fmt_h(b['godz_rynkowe']))}<td class="num">{fmt_h(b['godz_realne'])}</td></tr>""")
    return '\n'.join(out)

def rows_etap2():
    out = []
    for t in etap2['taski']:
        out.append(f"""<tr>
<td class="data-col">{esc(t['data'])}</td>
<td><span class="badge">{esc(OBSZARY.get(t['obszar'], t['obszar']))}</span><br><strong>{esc(t['tytul'])}</strong><div class="opis">{esc(t['opis'])}</div><div class="skala">{esc(t['skala'])}</div></td>
{td_r(fmt_h(t['godz_rynkowe']))}<td class="num">{fmt_h(t['godz_realne'])}</td></tr>""")
    return '\n'.join(out)

def rows_etap3():
    out = []
    for p in etap3['pozycje']:
        idtxt = f"{esc(p['id'])} — " if p.get('id') else ''
        out.append(f"""<tr>
<td><strong>{idtxt}{esc(p['tytul'])}</strong><div class="opis">{esc(p['opis'])}</div><div class="skala">Status: {esc(p['status'])}</div></td>
{td_r(f"{p['rynkowo_od']}–{p['rynkowo_do']}")}
<td class="num">{p['realnie_od']}–{p['realnie_do']}</td></tr>""")
    return '\n'.join(out)

def rows_regularne():
    out = []
    for p in regularne['pozycje']:
        out.append(f"""<tr>
<td><strong>{esc(p['tytul'])}</strong><div class="opis">{esc(p['opis'])}</div></td>
{td_r(f"{p['rynkowo_od']}–{p['rynkowo_do']}")}
<td class="num">{p['realnie_od']}–{p['realnie_do']}</td></tr>""")
    return '\n'.join(out)

def rows_obszary():
    out = []
    base = 'r' if SHOW_R else 'f'
    mx = max(o[base] for o in per_obszar.values())
    for k, o in per_obszar.items():
        pct = round(o[base] / mx * 100)
        if SHOW_R:
            val = f"""{fmt_h(o['r'])} h <span class="obar-meta">rynkowo</span> · {fmt_h(o['f'])} h <span class="obar-meta">realnie</span>"""
        else:
            val = f"""{fmt_h(o['f'])} h"""
        out.append(f"""<div class="obar-row">
<div class="obar-label">{esc(OBSZARY.get(k, k))} <span class="obar-meta">({o['n']} prac)</span></div>
<div class="obar-track"><div class="obar-fill" style="width:{pct}%"></div></div>
<div class="obar-val">{val}</div>
</div>""")
    return '\n'.join(out)

def rows_timeline():
    out = []
    for t in meta['timeline_kluczowe']:
        out.append(f"""<div class="tl-row"><div class="tl-date">{esc(t['data'])}</div><div class="tl-dot"></div><div class="tl-text">{esc(t['wydarzenie'])}</div></div>""")
    return '\n'.join(out)

def rows_koszty():
    out = []
    for p in koszty['pozycje']:
        hist = ''
        if p.get('historia'):
            items = ''.join(f"<li>{esc(h['okres'])}: <strong>{esc(h['kwota'])}</strong></li>" for h in p['historia'])
            hist = f"<ul class='hist'>{items}</ul>"
        out.append(f"""<tr>
<td><strong>{esc(p['nazwa'])}</strong><div class="opis">{esc(p['opis'])}</div>{hist}</td>
<td class="num nowrap">{esc(p.get('biezaco_mc','—'))}</td>
<td class="num nowrap">{esc(p.get('suma_dotychczas','—'))}</td></tr>""")
    return '\n'.join(out)

nl = '\n'

# --- warianty nagłówka/kafelków/noty zależne od SHOW_R ---
if SHOW_R:
    tiles_html = f"""<div class="tile acc"><div class="v">{fmt_h(e1_r + e2_r)} <small>h</small></div><div class="l">wartość rynkowa wykonanych prac (etapy 1-2), w godzinach klasycznego zespołu</div></div>
<div class="tile"><div class="v">{fmt_h(e1_f + e2_f)} <small>h</small></div><div class="l">realny czas pracy zespołu Auranet (z warsztatem AI)</div></div>"""
    nota_html = f"""{esc(meta['metodologia']['godziny_rynkowe'])}<br><br>{esc(meta['metodologia']['godziny_realne'])}<br><br>{esc(meta['metodologia']['uwaga'])}"""
    footer_uwaga = ' Wartości „rynkowo" są estymatą godzin klasycznego zespołu — mogą podlegać korekcie.'
    hdr_h, hdr_hmc = 'Godziny [h]', 'Godziny [h/mc]'
else:
    tiles_html = f"""<div class="tile acc"><div class="v">{fmt_h(e1_f + e2_f)} <small>h</small></div><div class="l">łączny czas pracy nad platformą (etapy 1-2)</div></div>
<div class="tile"><div class="v">{len(etap1['bloki']) + len(etap2['taski'])}</div><div class="l">wykonanych prac: {len(etap1['bloki'])} bloków budowy + {len(etap2['taski'])} zadań rozwojowych</div></div>"""
    nota_html = f"""{esc(meta['metodologia']['godziny_realne'])}"""
    footer_uwaga = ''
    hdr_h, hdr_hmc = 'Godziny [h]', 'Godziny [h/mc]'

html_doc = f"""<!doctype html>
<html lang="pl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Prima Auto — kosztorys i historia inwestycji</title>
<style>
:root {{
  --ink: #1a2332; --ink-2: #4a5568; --ink-3: #8b95a5;
  --accent: #1f4e79; --accent-soft: #e8eff6;
  --surface: #ffffff; --bg: #f4f6f9; --line: #e2e7ee;
  --red: #b03030;
}}
* {{ box-sizing: border-box; margin: 0; padding: 0; }}
body {{ font: 15px/1.55 -apple-system, "Segoe UI", Roboto, Arial, sans-serif; color: var(--ink); background: var(--bg); }}
.wrap {{ max-width: 980px; margin: 0 auto; padding: 32px 20px 80px; }}
header.top {{ border-bottom: 3px solid var(--accent); padding-bottom: 20px; margin-bottom: 28px; }}
header.top h1 {{ font-size: 26px; line-height: 1.25; }}
header.top .sub {{ color: var(--ink-2); margin-top: 6px; }}
header.top .stamp {{ color: var(--ink-3); font-size: 13px; margin-top: 10px; }}
h2 {{ font-size: 20px; margin: 44px 0 6px; }}
h2 .eno {{ color: var(--accent); }}
.lead {{ color: var(--ink-2); margin-bottom: 16px; max-width: 860px; }}
.tiles {{ display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; margin: 20px 0 8px; }}
.tile {{ background: var(--surface); border: 1px solid var(--line); border-radius: 10px; padding: 16px 18px; }}
.tile .v {{ font-size: 28px; font-weight: 700; letter-spacing: -0.5px; }}
.tile .v small {{ font-size: 15px; font-weight: 500; color: var(--ink-2); }}
.tile .l {{ color: var(--ink-2); font-size: 13px; margin-top: 3px; }}
.tile.acc {{ border-top: 3px solid var(--accent); }}
table {{ width: 100%; border-collapse: collapse; background: var(--surface); border: 1px solid var(--line); border-radius: 10px; overflow: hidden; margin: 14px 0; }}
th {{ text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.4px; color: var(--ink-2); background: var(--accent-soft); padding: 10px 14px; border-bottom: 1px solid var(--line); }}
th.num, td.num {{ text-align: right; }}
td {{ padding: 12px 14px; border-bottom: 1px solid var(--line); vertical-align: top; }}
tr:last-child td {{ border-bottom: none; }}
td.num {{ font-variant-numeric: tabular-nums; font-weight: 600; white-space: nowrap; }}
td.nowrap {{ white-space: normal; font-weight: 500; font-size: 13.5px; }}
td.data-col {{ white-space: nowrap; color: var(--ink-2); font-size: 13px; font-variant-numeric: tabular-nums; }}
.opis {{ color: var(--ink-2); font-weight: 400; margin-top: 4px; font-size: 14px; }}
.skala {{ color: var(--ink-3); font-size: 12.5px; margin-top: 4px; }}
.badge {{ display: inline-block; font-size: 11px; text-transform: uppercase; letter-spacing: 0.4px; background: var(--accent-soft); color: var(--accent); border-radius: 4px; padding: 2px 7px; margin-bottom: 5px; }}
tfoot td {{ background: var(--accent-soft); font-weight: 700; }}
.tl-row {{ display: grid; grid-template-columns: 110px 18px 1fr; gap: 10px; align-items: baseline; padding: 7px 0; }}
.tl-date {{ text-align: right; color: var(--ink-2); font-size: 13px; font-variant-numeric: tabular-nums; white-space: nowrap; }}
.tl-dot {{ width: 10px; height: 10px; border-radius: 50%; background: var(--accent); position: relative; top: 1px; justify-self: center; }}
.timeline {{ background: var(--surface); border: 1px solid var(--line); border-radius: 10px; padding: 18px 22px; margin: 14px 0; }}
.obars {{ background: var(--surface); border: 1px solid var(--line); border-radius: 10px; padding: 18px 22px; margin: 14px 0; }}
.obar-row {{ display: grid; grid-template-columns: 240px 1fr 260px; gap: 12px; align-items: center; padding: 6px 0; }}
.obar-label {{ font-size: 14px; }}
.obar-track {{ background: var(--bg); border-radius: 4px; height: 14px; overflow: hidden; }}
.obar-fill {{ background: var(--accent); height: 100%; border-radius: 0 4px 4px 0; min-width: 3px; }}
.obar-val {{ font-size: 13.5px; font-variant-numeric: tabular-nums; text-align: right; }}
.obar-meta {{ color: var(--ink-3); font-size: 12px; }}
.note {{ background: var(--accent-soft); border-left: 4px solid var(--accent); border-radius: 0 8px 8px 0; padding: 14px 18px; margin: 16px 0; color: var(--ink-2); font-size: 14px; }}
ul.hist {{ margin: 8px 0 0 18px; color: var(--ink-2); font-size: 13.5px; }}
footer {{ margin-top: 60px; padding-top: 16px; border-top: 1px solid var(--line); color: var(--ink-3); font-size: 12.5px; }}
@media (max-width: 720px) {{
  .obar-row {{ grid-template-columns: 1fr; gap: 4px; }}
  .obar-val {{ text-align: left; }}
  .tl-row {{ grid-template-columns: 90px 14px 1fr; }}
  td.data-col {{ font-size: 11px; }}
}}
@media print {{
  body {{ background: #fff; }}
  .wrap {{ max-width: none; padding: 0; }}
}}
</style>
</head>
<body>
<div class="wrap">

<header class="top">
<h1>Prima Auto — historia inwestycji i kosztorys prac</h1>
<div class="sub">Platforma importu samochodów z Chin · primaauto.com.pl · opracowanie: Auranet (Jan Schenk)</div>
<div class="stamp">Stan na {today} · dokument roboczy do rozmowy, aktualizowany wraz z postępem prac</div>
</header>

<div class="tiles">
{tiles_html}
<div class="tile"><div class="v">{meta['liczby_projektu']['wydania_pluginu_po_cutover'] }+</div><div class="l">wydań oprogramowania od startu na primaauto.com.pl</div></div>
<div class="tile"><div class="v">{meta['liczby_projektu']['listings_w_bazie']}</div><div class="l">ogłoszeń w katalogu</div></div>
<div class="tile acc"><div class="v">{meta['liczby_projektu']['zamowienia_klientow']} <small>/ {meta['liczby_projektu']['zamowienia_potwierdzone']}</small></div><div class="l">zamówień od klientów (bez anulowanych) · z tego <strong>{meta['liczby_projektu']['zamowienia_potwierdzone']} potwierdzonych</strong> i dalej w procesie</div></div>
<div class="tile"><div class="v">{meta['liczby_projektu']['zamowienia_wewnetrzne']}</div><div class="l">zamówień wewnętrznych — auta sprowadzane na stan (w drodze, na placu, dostarczone)</div></div>
</div>

<div class="note"><strong>Jak czytać godziny.</strong> {nota_html}</div>

<h2>Oś czasu projektu</h2>
<div class="timeline">
{rows_timeline()}
</div>

<h2><span class="eno">Etap 1.</span> {esc(etap1['nazwa'].split('—',1)[1].strip() if '—' in etap1['nazwa'] else etap1['nazwa'])}</h2>
<p class="lead">{esc(etap1['opis'])} <strong>{esc(etap1['referencja_wyceny'])}.</strong></p>
<table>
<thead><tr><th>Zakres prac</th>{th_r('Rynkowo [h]')}<th class="num">{hdr_h}</th></tr></thead>
<tbody>
{rows_etap1()}
</tbody>
<tfoot><tr><td>Razem etap 1</td>{td_r(fmt_h(e1_r))}<td class="num">{fmt_h(e1_f)}</td></tr></tfoot>
</table>

<h2><span class="eno">Etap 2.</span> {esc(etap2['nazwa'].split('—',1)[1].strip() if '—' in etap2['nazwa'] else etap2['nazwa'])}</h2>
<p class="lead">{esc(etap2['opis'])}</p>

<table>
<thead><tr><th>Data</th><th>Praca</th>{th_r('Rynkowo [h]')}<th class="num">{hdr_h}</th></tr></thead>
<tbody>
{rows_etap2()}
</tbody>
<tfoot><tr><td colspan="2">Razem etap 2</td>{td_r(fmt_h(e2_r))}<td class="num">{fmt_h(e2_f)}</td></tr></tfoot>
</table>

<h2><span class="eno">Etap 3.</span> Plany rozwoju — wycena inwestycji</h2>
<p class="lead">{esc(etap3['opis'])}</p>
<table>
<thead><tr><th>Pozycja</th>{th_r('Rynkowo [h]')}<th class="num">{hdr_h}</th></tr></thead>
<tbody>
{rows_etap3()}
</tbody>
<tfoot><tr><td>Razem pełna roadmapa (widełki)</td>{td_r(f'{e3_r_od}–{e3_r_do}')}<td class="num">{e3_f_od}–{e3_f_do}</td></tr></tfoot>
</table>

<h2>{esc(regularne['nazwa'])}</h2>
<p class="lead">{esc(regularne['opis'])}</p>
<table>
<thead><tr><th>Zakres</th>{th_r('Rynkowo [h/mc]')}<th class="num">{hdr_hmc}</th></tr></thead>
<tbody>
{rows_regularne()}
</tbody>
<tfoot><tr><td>Razem miesięcznie (widełki)</td>{td_r(f'{reg_r_od}–{reg_r_do}')}<td class="num">{reg_f_od}–{reg_f_do}</td></tr></tfoot>
</table>
<div class="note">{esc(regularne['uwaga'])}</div>

<h2>Koszty zewnętrzne (poza godzinami)</h2>
<p class="lead">{esc(koszty['opis'])}</p>
<table>
<thead><tr><th>Pozycja</th><th class="num">Bieżąco</th><th class="num">Dotychczas</th></tr></thead>
<tbody>
{rows_koszty()}
</tbody>
</table>
<div class="note">{esc(koszty['uwaga'])}</div>

<footer>
Dokument wygenerowany automatycznie z ewidencji prac projektu (repozytorium, dziennik wydań, rejestr decyzji, logi pracy).{footer_uwaga} Auranet · js@auranet.com.pl
</footer>

</div>
</body>
</html>
"""

with open(OUT, 'w', encoding='utf-8') as f:
    f.write(html_doc)
print(f"OK: {OUT}")
print(f"Etap 1: {e1_r} h rynkowo / {e1_f} h realnie ({len(etap1['bloki'])} bloków)")
print(f"Etap 2: {e2_r} h rynkowo / {e2_f} h realnie ({len(etap2['taski'])} tasków)")
print(f"Etap 3: {e3_r_od}-{e3_r_do} h rynkowo / {e3_f_od}-{e3_f_do} h realnie ({len(etap3['pozycje'])} pozycji)")
print(f"Regularne: {reg_r_od}-{reg_r_do} h/mc rynkowo / {reg_f_od}-{reg_f_do} h/mc realnie ({len(regularne['pozycje'])} pozycji)")

if '--deploy' in sys.argv:
    os.makedirs(DEPLOY_DIR, exist_ok=True)
    shutil.copy(OUT, os.path.join(DEPLOY_DIR, 'index.html'))
    print(f"DEPLOY: https://auratest.pl/{os.path.basename(DEPLOY_DIR)}/")
