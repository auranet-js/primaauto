#!/usr/bin/env python3
"""Makieta wariantów widoczności autolinku słownika + ikony „i" przy filtrach (T-250 krok 5, 03.09).
Tekst i pastylki prawdziwe: oferta AITO M9 (362371) i sekcja „Wyposażenie i technologie" 0.37.12."""
import pathlib
OUT = pathlib.Path('/home/host476470/domains/auratest.pl/public_html/fe4f58fec53ctmp/primaauto-makiety')

AKAPIT = ('AITO M9 w wersji {re} Ultra 52 kWh 6-miejscowej. Luksusowy SUV klasy premium '
          'z {lidar} 192-liniowym, {hud}, baterią {nmc} od {catl} oraz systemem {ota}. '
          'Zasięg łączny 1 362 km w cyklu {cltc}.')
SLOWA = {'re': ('erev', 'Range Extender'), 'lidar': ('lidar', 'LiDAR'), 'hud': ('ar-hud', 'AR-HUD'),
         'nmc': ('bateria-nmc', 'NMC'), 'catl': ('catl', 'CATL'), 'ota': ('ota', 'OTA'),
         'cltc': ('cltc-wltp', 'CLTC')}
def akapit(cls, ikona=False):
    d = {}
    for k, (slug, txt) in SLOWA.items():
        ico = '<span class="ico" aria-hidden="true">i</span>' if ikona else ''
        d[k] = f'<a href="https://primaauto.com.pl/wiki/{slug}/" class="{cls}">{txt}{ico}</a>'
    return AKAPIT.format(**d)

CHIPS = [('Lidar', '1 340'), ('Kamera 360°', '2 712'), ('AR-HUD', '714'), ('Dach panoramiczny', '1 658'),
         ('Tylna oś skrętna', '192'), ('Dolby Atmos', '748')]
def chipy(tryb):
    out = []
    for t, n in CHIPS:
        if tryb == 'ikona':
            out.append(f'<span class="chip">{t} <b>{n}</b></span><a class="chip-i" href="#" aria-label="Słownik: {t}">i</a>')
        elif tryb == 'link':
            out.append(f'<span class="chip">{t} <b>{n}</b></span>')
        else:
            out.append(f'<span class="chip chip--dot">{t} <b>{n}</b></span>')
    return '<div class="chips">' + ''.join(out) + '</div>'

HTML = f'''<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Autolinki słownika — warianty (T-250)</title>
<style>
*,*::before,*::after{{box-sizing:border-box}}
body{{margin:0;background:#F5F6F8;color:#2D3748;font:15px/1.6 Inter,-apple-system,'Segoe UI',sans-serif}}
.naglowek{{background:#9B0000;color:#fff;padding:14px 24px;font-weight:800}}
.w{{max-width:900px;margin:0 auto;padding:24px 20px 60px}}
h1{{font-size:26px;color:#1B2A4A;margin:0 0 6px}}
p.lead{{color:#5C6B7F;margin:0 0 24px}}
.karta{{background:#fff;border:1px solid #E1E4E8;border-radius:6px;padding:18px 20px;margin-bottom:14px}}
.karta h2{{margin:0 0 4px;font-size:15px;color:#1B2A4A}}
.karta .opis{{margin:0 0 12px;font-size:13px;color:#5C6B7F}}
.karta .tresc{{font-size:15px}}
/* A — stan obecny */
.v-a{{color:inherit;text-decoration:underline;text-decoration-style:dotted;text-decoration-color:#A0AEC0;text-underline-offset:3px}}
/* B — kropkowane, ale w kolorze marki */
.v-b{{color:inherit;text-decoration:underline;text-decoration-style:dotted;text-decoration-color:#1B2A4A;text-decoration-thickness:2px;text-underline-offset:3px}}
/* C — pełne podkreślenie granatowe, tekst granatowy */
.v-c{{color:#1B2A4A;font-weight:600;text-decoration:underline;text-decoration-color:#9AA9C0;text-underline-offset:3px}}
.v-c:hover{{text-decoration-color:#1B2A4A}}
/* D — podkreślenie + ikonka „i" po słowie */
.v-d{{color:#1B2A4A;text-decoration:underline;text-decoration-color:#C3CCD9;text-underline-offset:3px;white-space:nowrap}}
.v-d .ico{{display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;margin-left:3px;
  border-radius:50%;background:#1B2A4A;color:#fff;font-size:10px;font-weight:700;font-style:normal;vertical-align:1px;text-decoration:none}}
/* pastylki */
.chips{{display:flex;flex-wrap:wrap;gap:8px;align-items:center}}
.chip{{display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 12px;background:#fff;border:1px solid #E1E4E8;border-radius:6px}}
.chip b{{color:#5C6B7F;font-weight:400;font-size:12px}}
.chip--dot{{text-decoration:underline;text-decoration-style:dotted;text-decoration-color:#A0AEC0;text-underline-offset:3px}}
.chip-i{{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;margin:0 6px 0 -4px;
  border:1px solid #C3CCD9;border-radius:50%;color:#1B2A4A;font-size:12px;font-weight:700;text-decoration:none;background:#fff}}
.chip-i:hover{{border-color:#1B2A4A;background:#EEF1F5}}
.tabela{{width:100%;border-collapse:collapse;font-size:13px}}
.tabela td{{padding:8px 10px;border-bottom:1px solid #F0F1F3}}
.tabela td:first-child{{color:#5C6B7F;width:45%}}
.uwaga{{background:#FFF8E6;border-left:3px solid #E8AC07;padding:10px 14px;font-size:13.5px;margin:0 0 18px}}
</style>
<div class="naglowek">PRIMA-AUTO — makieta T-250 krok 5</div>
<div class="w">
<h1>Autolinki słownika: jak mocno je pokazać</h1>
<p class="lead">Pomiar GA4 (44 dni): oferty dały <b>3 przejścia do słownika na 50 295 odsłon</b> (0,006 %),
aktualności przy ułamku tego ruchu dały 40. Tekst i liczby poniżej są prawdziwe (oferta AITO M9, pastylki 0.37.12).</p>

<p class="uwaga"><b>Ustalenie z 03.09:</b> tabela danych technicznych na ofercie <b>jest</b> autolinkowana
(6 linków w wartościach), tak samo jak opis — problemem jest więc sama widoczność, nie brak linków.</p>

<div class="karta"><h2>A — stan obecny</h2><p class="opis">Kolor tekstu dziedziczony, kropkowana szara linia. Świadomie „subtelny" (T-214).</p>
<p class="tresc">{akapit('v-a')}</p></div>

<div class="karta"><h2>B — ta sama kropkowana linia, ale w kolorze marki i grubsza</h2><p class="opis">Najmniejsza zmiana: nadal nie krzyczy, ale widać, że to nie zwykły tekst.</p>
<p class="tresc">{akapit('v-b')}</p></div>

<div class="karta"><h2>C — granatowy półgruby tekst z podkreśleniem</h2><p class="opis">Czyta się jak link. Ryzyko: w gęstym akapicie robi się „choinka".</p>
<p class="tresc">{akapit('v-c')}</p></div>

<div class="karta"><h2>D — podkreślenie + ikonka „i" po słowie</h2><p class="opis">Sygnał „to jest wyjaśnienie", nie „to jest inna strona oferty". Najbardziej jednoznaczny, najbardziej zajmuje miejsce.</p>
<p class="tresc">{akapit('v-d', ikona=True)}</p></div>

<div class="karta"><h2>Tabela danych technicznych — wariant D w praktyce</h2><p class="opis">Dziś linkowane są wartości; etykiety (Rozstaw osi, Promień skrętu) nie.</p>
<table class="tabela">
<tr><td>Napęd</td><td>{akapit('v-d', ikona=True).split('. ')[0].split('w wersji ')[1].split(' Ultra')[0]}</td></tr>
<tr><td>Bateria</td><td><a href="#" class="v-d">NMC<span class="ico">i</span></a> 52 kWh, <a href="#" class="v-d">CATL<span class="ico">i</span></a></td></tr>
<tr><td>Zasięg łączny</td><td>1 362 km (<a href="#" class="v-d">CLTC<span class="ico">i</span></a>)</td></tr>
<tr><td>Asystent</td><td><a href="#" class="v-d">LiDAR<span class="ico">i</span></a> 192-liniowy</td></tr>
</table></div>

<h1 style="font-size:20px;margin:28px 0 6px">Filtry: jak dojść z pastylki do hasła</h1>
<p class="lead">Pytanie z 03.09: dymek czy „i" przy nazwie. Dymek (<code>title</code>) odpada — nie działa na dotyku.</p>

<div class="karta"><h2>1 — osobna ikona „i" obok pastylki</h2><p class="opis">Klik w pastylkę filtruje, klik w „i" otwiera hasło. Dwa cele dotykowe, nic się nie myli. Zajmuje 28 px na pastylkę.</p>{chipy('ikona')}</div>

<div class="karta"><h2>2 — kropkowane podkreślenie nazwy w pastylce</h2><p class="opis">Bez dodatkowego miejsca, ale cała pastylka to checkbox — link musiałby być w środku kontrolki (kolizja dotykowa, axe: nested-interactive).</p>{chipy('kropka')}</div>

<div class="karta"><h2>3 — bez linku z pastylki</h2><p class="opis">Droga do hasła zostaje przez słownik i autolinki w treści. Wyszukiwarka pozostaje narzędziem, nie encyklopedią.</p>{chipy('link')}</div>
</div>'''
(OUT / 'autolinki-warianty.html').write_text(HTML, encoding='utf-8')
print('ok')
