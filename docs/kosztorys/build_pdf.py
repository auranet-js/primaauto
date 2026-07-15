#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Generator 3 PDF-ów do druku z kosztorysu Prima Auto (podział po etapach).

Reużywa dane, funkcje renderujące i CSS z build.py — PDF-y wyglądają identycznie
jak raport HTML. Kolumna "rynkowo" respektuje flagę pokazuj_rynkowo z meta.json.

Wymaga: wkhtmltopdf (HTML->PDF).

Użycie:
    python3 build_pdf.py            # generuje 3 PDF obok skryptu (pdf/)
    python3 build_pdf.py --deploy   # + kopiuje na tmp auratest i wypisuje URL-e

Podział:
    PDF 1 = Oś czasu + Etap 1 (koncepcja i budowa)
    PDF 2 = Etap 2 (rozwój od cutoveru)
    PDF 3 = Etap 3 (roadmapa) + prace regularne + koszty zewnętrzne
"""
import os, re, sys, subprocess, shutil, importlib.util

BASE = os.path.dirname(os.path.abspath(__file__))

# Import build.py jako moduł (bez side-effectu --deploy: build sprawdza sys.argv,
# a nasze argv nie zawiera --deploy przy zwykłym uruchomieniu poza deployem PDF).
_argv = sys.argv
sys.argv = ['build.py']                       # izolacja: build nie zobaczy naszych flag
spec = importlib.util.spec_from_file_location('build', os.path.join(BASE, 'build.py'))
b = importlib.util.module_from_spec(spec)
spec.loader.exec_module(b)
sys.argv = _argv

# CSS 1:1 z build.py (wycięty z wygenerowanego dokumentu — jedno źródło prawdy).
CSS = re.search(r'<style>(.*?)</style>', b.html_doc, re.S).group(1)

PDF_DIR = os.path.join(BASE, 'pdf')
os.makedirs(PDF_DIR, exist_ok=True)

HDR_H = b.hdr_h

def page(subtitle, body):
    """Pełny HTML jednej strony PDF — wspólny nagłówek projektu + podtytuł etapu."""
    return f"""<!doctype html><html lang="pl"><head><meta charset="utf-8">
<style>{CSS}
@page {{ size: A4; margin: 0; }}
h2 {{ page-break-after: avoid; }}
table, .timeline {{ page-break-inside: auto; }}
tr {{ page-break-inside: avoid; }}
</style></head><body><div class="wrap">
<header class="top">
<h1>Prima Auto — historia inwestycji i kosztorys prac</h1>
<div class="sub">Platforma importu samochodów z Chin · primaauto.com.pl · opracowanie: Auranet (Jan Schenk)</div>
<div class="stamp">{subtitle} · stan na {b.today}</div>
</header>
{body}
<footer>Dokument wygenerowany z ewidencji prac projektu (repozytorium, dziennik wydań, rejestr decyzji, logi pracy). Auranet · js@auranet.com.pl</footer>
</div></body></html>"""

# --- treść 3 stron (reużyte funkcje/sumy z build.py) ---

etap1_body = f"""
<h2>Oś czasu projektu</h2>
<div class="timeline">{b.rows_timeline()}</div>

<h2><span class="eno">Etap 1.</span> {b.esc(b.etap1['nazwa'].split('—',1)[1].strip() if '—' in b.etap1['nazwa'] else b.etap1['nazwa'])}</h2>
<p class="lead">{b.esc(b.etap1['opis'])} <strong>{b.esc(b.etap1['referencja_wyceny'])}.</strong></p>
<table>
<thead><tr><th>Zakres prac</th>{b.th_r('Rynkowo [h]')}<th class="num">{HDR_H}</th></tr></thead>
<tbody>{b.rows_etap1()}</tbody>
<tfoot><tr><td>Razem etap 1</td>{b.td_r(b.fmt_h(b.e1_r))}<td class="num">{b.fmt_h(b.e1_f)}</td></tr></tfoot>
</table>"""

etap2_body = f"""
<h2><span class="eno">Etap 2.</span> {b.esc(b.etap2['nazwa'].split('—',1)[1].strip() if '—' in b.etap2['nazwa'] else b.etap2['nazwa'])}</h2>
<p class="lead">{b.esc(b.etap2['opis'])}</p>
<table>
<thead><tr><th>Data</th><th>Praca</th>{b.th_r('Rynkowo [h]')}<th class="num">{HDR_H}</th></tr></thead>
<tbody>{b.rows_etap2()}</tbody>
<tfoot><tr><td colspan="2">Razem etap 2</td>{b.td_r(b.fmt_h(b.e2_r))}<td class="num">{b.fmt_h(b.e2_f)}</td></tr></tfoot>
</table>"""

etap3_body = f"""
<h2><span class="eno">Etap 3.</span> Plany rozwoju — wycena inwestycji</h2>
<p class="lead">{b.esc(b.etap3['opis'])}</p>
<table>
<thead><tr><th>Pozycja</th>{b.th_r('Rynkowo [h]')}<th class="num">{HDR_H}</th></tr></thead>
<tbody>{b.rows_etap3()}</tbody>
<tfoot><tr><td>Razem pełna roadmapa (widełki)</td>{b.td_r(f'{b.e3_r_od}–{b.e3_r_do}')}<td class="num">{b.e3_f_od}–{b.e3_f_do}</td></tr></tfoot>
</table>

<h2>{b.esc(b.regularne['nazwa'])}</h2>
<p class="lead">{b.esc(b.regularne['opis'])}</p>
<table>
<thead><tr><th>Zakres</th>{b.th_r('Rynkowo [h/mc]')}<th class="num">{b.hdr_hmc}</th></tr></thead>
<tbody>{b.rows_regularne()}</tbody>
<tfoot><tr><td>Razem miesięcznie (widełki)</td>{b.td_r(f'{b.reg_r_od}–{b.reg_r_do}')}<td class="num">{b.reg_f_od}–{b.reg_f_do}</td></tr></tfoot>
</table>
<div class="note">{b.esc(b.regularne['uwaga'])}</div>

<h2>Koszty zewnętrzne (poza godzinami)</h2>
<p class="lead">{b.esc(b.koszty['opis'])}</p>
<table>
<thead><tr><th>Pozycja</th><th class="num">Bieżąco</th><th class="num">Dotychczas</th></tr></thead>
<tbody>{b.rows_koszty()}</tbody>
</table>
<div class="note">{b.esc(b.koszty['uwaga'])}</div>"""

PAGES = [
    ('1-etap1', 'Etap 1 — koncepcja i budowa platformy', etap1_body),
    ('2-etap2', 'Etap 2 — rozwój od uruchomienia', etap2_body),
    ('3-etap3-koszty', 'Etap 3 — plany rozwoju, prace regularne, koszty', etap3_body),
]

WKHTML_OPTS = [
    '--page-size', 'A4',
    '--margin-top', '15', '--margin-bottom', '16',
    '--margin-left', '13', '--margin-right', '13',
    '--encoding', 'utf-8',
    '--enable-local-file-access',
    '--footer-right', 'Prima Auto · kosztorys · [page]/[topage]',
    '--footer-font-size', '8', '--footer-spacing', '4',
    '--quiet',
]

made = []
for slug, subtitle, body in PAGES:
    html_path = os.path.join(PDF_DIR, f'primaauto-kosztorys-{slug}.html')
    pdf_path  = os.path.join(PDF_DIR, f'primaauto-kosztorys-{slug}.pdf')
    with open(html_path, 'w', encoding='utf-8') as f:
        f.write(page(subtitle, body))
    subprocess.run(['wkhtmltopdf', *WKHTML_OPTS, html_path, pdf_path], check=True)
    os.remove(html_path)
    made.append(pdf_path)
    print(f'OK: {pdf_path}  ({os.path.getsize(pdf_path)//1024} KB)')

if '--deploy' in sys.argv:
    dst = os.path.expanduser('~/domains/auratest.pl/public_html/fe4f58fec53ctmp')
    print('\nDo druku:')
    for p in made:
        name = os.path.basename(p)
        shutil.copy(p, os.path.join(dst, name))
        print(f'  https://auratest.pl/fe4f58fec53ctmp/{name}')
