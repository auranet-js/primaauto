#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Generator strony "Postęp prac i plan rozwoju" Prima Auto.

Rejestr zmian od spotkania 16.07.2026 (changelog, najświeższe u góry)
+ aktualna lista zadań do zrobienia (roadmapa z etap3.json + nowe niewycenione).
Godziny sprzed spotkania nie są tu rozpisywane — ich zestawienie żyje w kosztorysie (build.py).

Dane: dane/postep.json (changelog, nowe taski, ukrycia) + dane/etap3.json (roadmapa).

Użycie:
    python3 build_postep.py            # generuje postep.html obok skryptu
    python3 build_postep.py --deploy   # + kopiuje na zahaszowany URL auratest.pl

Odświeżanie: dopisz wpis changelog NA POCZĄTEK listy w dane/postep.json,
zrealizowane pozycje roadmapy dodaj do todo_ukryte_id, uruchom ponownie.
"""
import json, os, shutil, sys, datetime, html

BASE = os.path.dirname(os.path.abspath(__file__))
DANE = os.path.join(BASE, 'dane')
OUT = os.path.join(BASE, 'postep.html')
DEPLOY_DIR = os.path.expanduser('~/domains/auratest.pl/public_html/pa-postep-eb460f6c3c4c12755858')

ROZMIARY = {'S': 'mała', 'M': 'średnia', 'L': 'duża', 'XL': 'bardzo duża'}

def load(name):
    with open(os.path.join(DANE, name), encoding='utf-8') as f:
        return json.load(f)

def esc_par(t):
    """Escape + rozbicie na akapity po pustej linii — opisy w changelogu bywają wieloakapitowe."""
    parts = [p.strip() for p in str(t).split("\n\n") if p.strip()]
    if len(parts) <= 1:
        return esc(t)
    return "".join(f"<p>{esc(p)}</p>" for p in parts)


def esc(s):
    return html.escape(str(s), quote=False)

def fmt_h(v):
    if isinstance(v, float) and v == int(v):
        v = int(v)
    return str(v).replace('.', ',')

postep = load('postep.json')
etap3  = load('etap3.json')

ukryte = set(postep.get('todo_ukryte_id', []))
roadmapa = [p for p in etap3['pozycje'] if p.get('id') not in ukryte]

# 2026-08-06: todo_ukryte_id filtruje TEŻ todo_nowe. Wcześniej działało wyłącznie
# na roadmapę, więc dopisanie ID zrealizowanego zadania z todo_nowe nic nie dawało
# — zadanie zostawało na liście „do zrobienia" i nikt tego nie sygnalizował.
postep['todo_nowe'] = [t for t in postep['todo_nowe'] if t.get('id') not in ukryte]

# kolejność: todo_pierwsze (wyciągnięte na czoło) → nowe niewycenione → reszta roadmapy
pierwsze_ids = postep.get('todo_pierwsze', [])
wszystkie = {p['id']: p for p in roadmapa}
wszystkie.update({t['id']: t for t in postep['todo_nowe']})
pierwsze = [wszystkie[i] for i in pierwsze_ids if i in wszystkie]
nowe = [t for t in postep['todo_nowe'] if t['id'] not in pierwsze_ids]
# pozycje z roadmapy wyciągnięte tuż pod nowe (np. zadanie zależne od nowego)
po_nowych_ids = [i for i in postep.get('todo_po_nowych', []) if i in wszystkie and i not in pierwsze_ids]
po_nowych = [wszystkie[i] for i in po_nowych_ids]
reszta = [p for p in roadmapa if p['id'] not in pierwsze_ids and p['id'] not in po_nowych_ids]
todo_lista = pierwsze + nowe + po_nowych + reszta
nowe_ids = {t['id'] for t in postep['todo_nowe']}

today = datetime.date.today().strftime('%d.%m.%Y')

def rows_changelog():
    out = []
    for c in postep['changelog']:
        wersje = f"<span class='wersje'>{esc(c['wersje'])}</span>" if c.get('wersje') else ''
        link = ''
        if c.get('link'):
            link = (f"<div class=\"opis\"><a href=\"{esc(c['link']['url'])}\" target=\"_blank\" "
                    f"rel=\"noopener\">{esc(c['link']['tekst'])}</a></div>")
        out.append(f"""<tr>
<td class="data-col">{esc(c['data'])}</td>
<td><strong>{esc(c['tytul'])}</strong> {wersje}<div class="opis">{esc_par(c['opis'])}</div>{link}</td>
<td class="num">{fmt_h(c.get('godz', 0))}</td></tr>""")
    return '\n'.join(out)

ch_sum = sum(c.get('godz', 0) for c in postep['changelog'])

# 2026-08-21: suma godzin zrealizowanych DO spotkania (etapy 1-2 z kosztorysu, realne).
# Liczona z danych, nie hardkodowana — kosztorys bywa korygowany wstecz.
etap1 = load('etap1.json')
etap2 = load('etap2.json')
przed_sum = (sum(b.get('godz_realne', 0) for b in etap1['bloki'])
             + sum(t.get('godz_realne', 0) for t in etap2['taski']))

def row_task(p, nowy=False):
    badge = '<span class="badge new">nowe</span> ' if nowy else ''
    rozmiar = ''
    if p.get('rozmiar'):
        rozmiar = f"<span class='rozmiar'>skala: {ROZMIARY.get(p['rozmiar'], p['rozmiar'])}</span>"
    idtxt = f"{esc(p['id'])} — " if p.get('id') else ''
    if p.get('realnie_od') is not None:
        godz = f"{fmt_h(p['realnie_od'])}–{fmt_h(p['realnie_do'])}"
    else:
        godz = '—'
    linki = ''
    if p.get('linki'):
        li = ' · '.join(f'<a href="{esc(l["url"])}" target="_blank" rel="noopener">{esc(l["tekst"])}</a>'
                        for l in p['linki'])
        linki = f'<div class="linki">Do obejrzenia: {li}</div>'
    pytania = ''
    if p.get('pytania'):
        li = ''.join(f'<li>{esc(q)}</li>' for q in p['pytania'])
        pytania = f'<div class="pytania"><strong>Do potwierdzenia z Tobą:</strong><ul>{li}</ul></div>'
    return f"""<tr>
<td><strong>{idtxt}{esc(p['tytul'])}</strong> {rozmiar}<div class="opis">{badge}{esc(p['opis'])}</div>{linki}{pytania}<div class="skala">Status: {esc(p['status'])}</div></td>
<td class="num">{godz}</td></tr>"""

def rows_todo():
    return '\n'.join(row_task(p, nowy=p['id'] in nowe_ids) for p in todo_lista)

todo_od = sum(p['realnie_od'] for p in todo_lista if p.get('realnie_od') is not None)
todo_do = sum(p['realnie_do'] for p in todo_lista if p.get('realnie_do') is not None)

html_doc = f"""<!doctype html>
<html lang="pl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Prima Auto — postęp prac i plan rozwoju</title>
<style>
:root {{
  --ink: #1a2332; --ink-2: #4a5568; --ink-3: #8b95a5;
  --accent: #1f4e79; --accent-soft: #e8eff6;
  --surface: #ffffff; --bg: #f4f6f9; --line: #e2e7ee;
  --green: #2e7d43; --green-soft: #e7f3ea;
}}
* {{ box-sizing: border-box; margin: 0; padding: 0; }}
body {{ font: 15px/1.55 -apple-system, "Segoe UI", Roboto, Arial, sans-serif; color: var(--ink); background: var(--bg); }}
.wrap {{ max-width: none; margin: 0; padding: 32px 40px 80px; }}
.cols {{ display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: 40px; align-items: start; }}
.cols > section {{ min-width: 0; }}
.cols h2 {{ margin-top: 0; }}
header.top {{ border-bottom: 3px solid var(--accent); padding-bottom: 20px; margin-bottom: 28px; }}
header.top h1 {{ font-size: 26px; line-height: 1.25; }}
header.top .sub {{ color: var(--ink-2); margin-top: 6px; }}
header.top .stamp {{ color: var(--ink-3); font-size: 13px; margin-top: 10px; }}
h2 {{ font-size: 20px; margin: 44px 0 6px; }}
.lead {{ color: var(--ink-2); margin-bottom: 16px; }}
.note {{ background: var(--accent-soft); border-left: 4px solid var(--accent); border-radius: 0 8px 8px 0; padding: 14px 18px; margin: 16px 0; color: var(--ink-2); font-size: 14px; }}
.wersje {{ color: var(--ink-3); font-size: 12px; font-weight: 400; }}
table {{ width: 100%; border-collapse: collapse; background: var(--surface); border: 1px solid var(--line); border-radius: 10px; overflow: hidden; margin: 14px 0; }}
th {{ text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.4px; color: var(--ink-2); background: var(--accent-soft); padding: 10px 14px; border-bottom: 1px solid var(--line); }}
th.num, td.num {{ text-align: right; }}
td {{ padding: 12px 14px; border-bottom: 1px solid var(--line); vertical-align: top; }}
tr:last-child td {{ border-bottom: none; }}
td.num {{ font-variant-numeric: tabular-nums; font-weight: 600; white-space: nowrap; }}
td.data-col {{ white-space: nowrap; color: var(--ink-2); font-size: 13px; font-variant-numeric: tabular-nums; }}
tfoot td {{ background: var(--accent-soft); font-weight: 700; }}
.opis {{ color: var(--ink-2); font-weight: 400; margin-top: 4px; font-size: 14px; }}
.opis p {{ margin: 0 0 9px; }}
.opis p:last-child {{ margin-bottom: 0; }}
.skala {{ color: var(--ink-3); font-size: 12.5px; margin-top: 4px; }}
.linki {{ margin-top: 8px; font-size: 13.5px; }}
.linki a {{ color: var(--accent); font-weight: 600; }}
.pytania {{ margin-top: 8px; padding: 8px 12px; background: var(--accent-soft); border-radius: 6px; font-size: 13.5px; color: var(--ink-2); }}
.pytania strong {{ font-size: 12.5px; }}
.pytania ul {{ margin: 4px 0 0; padding-left: 18px; }}
.pytania li {{ margin: 2px 0; }}
.rozmiar {{ color: var(--ink-3); font-size: 12px; font-weight: 400; white-space: nowrap; }}
.badge {{ display: inline-block; font-size: 11px; text-transform: uppercase; letter-spacing: 0.4px; border-radius: 4px; padding: 2px 7px; }}
.badge.new {{ background: var(--green-soft); color: var(--green); }}
.note.przed-spotkaniem {{ font-size: 15px; color: var(--ink); }}
footer {{ margin-top: 60px; padding-top: 16px; border-top: 1px solid var(--line); color: var(--ink-3); font-size: 12.5px; }}
@media (max-width: 1100px) {{
    .cols {{ grid-template-columns: 1fr; gap: 8px; }}
    .cols > section + section h2 {{ margin-top: 36px; }}
}}
@media (max-width: 720px) {{
    .wrap {{ padding: 24px 16px 60px; }}
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
<h1>Prima Auto — postęp prac i plan rozwoju</h1>
<div class="sub">Platforma importu samochodów z Chin · primaauto.com.pl · opracowanie: Auranet (Jan Schenk)</div>
<div class="stamp">Stan na {today} · dokument roboczy, aktualizowany na bieżąco wraz z postępem prac</div>
</header>

<div class="cols">
<section>
<h2>Zrealizowane</h2>
<p class="lead">Rejestr prac wykonanych od spotkania {esc(postep['zamkniecie']['data'])}, z godzinami liczonymi tak samo jak w kosztorysie (realny czas pracy zespołu).</p>
<table>
<thead><tr><th>Data</th><th>Praca</th><th class="num">Godziny [h]</th></tr></thead>
<tbody>
{rows_changelog()}
</tbody>
<tfoot><tr><td colspan="2">Razem od spotkania {esc(postep['zamkniecie']['data'])}</td><td class="num">{fmt_h(ch_sum)}</td></tr></tfoot>
</table>

<div class="note przed-spotkaniem">Godziny zrealizowane do spotkania {esc(postep['zamkniecie']['data'])} (etapy 1–2 kosztorysu): <strong>{fmt_h(przed_sum)} h</strong></div>

</section>

<section>
<h2>W kolejce</h2>
<p class="lead">Aktualny plan rozwoju platformy z widełkami godzin. Kolejność odzwierciedla zależności i priorytety — niektóre zadania są fundamentem, który obniża koszt kolejnych. Pozycje oznaczone „nowe” zostały dopisane po spotkaniu {esc(postep['zamkniecie']['data'])}.</p>
<table>
<thead><tr><th>Zadanie</th><th class="num">Godziny [h]</th></tr></thead>
<tbody>
{rows_todo()}
</tbody>
<tfoot><tr><td>Razem pełna lista (widełki)</td><td class="num">{fmt_h(todo_od)}–{fmt_h(todo_do)}</td></tr></tfoot>
</table>
</section>
</div>

<footer>
Dokument generowany automatycznie z ewidencji prac projektu (repozytorium, dziennik wydań, rejestr decyzji). Auranet · js@auranet.com.pl
</footer>

</div>
</body>
</html>
"""

with open(OUT, 'w', encoding='utf-8') as f:
    f.write(html_doc)
print(f"OK: {OUT}")
print(f"Changelog: {len(postep['changelog'])} wpisów")
print(f"Todo: {len(postep['todo_nowe'])} nowych + {len(roadmapa)} z roadmapy (ukryte: {len(ukryte)})")

if '--deploy' in sys.argv:
    os.makedirs(DEPLOY_DIR, exist_ok=True)
    shutil.copy(OUT, os.path.join(DEPLOY_DIR, 'index.html'))
    print(f"DEPLOY: https://auratest.pl/{os.path.basename(DEPLOY_DIR)}/")
