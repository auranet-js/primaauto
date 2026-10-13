#!/usr/bin/env python3
"""Makiety T-262 „specyfikacja PDF w zamówieniu” + T-263 „wydruk ogłoszenia do PDF” (13.10, wersja 2).

Wersja 2 po uwadze Janka: dokument ma się dobrze drukować — biały papier, zero dużych ciemnych teł, kolor marki
w typografii, liniach i drobnych akcentach. Projekt wg skilla frontend-design, jeden mocny element na wariant:
  A „Karta z salonu”     — strona 1 w cienkiej ramce jak naklejka na szybie auta w salonie, duże liczby,
  B „Rysunek techniczny” — schemat wymiarów (widok z góry i z przodu) rysowany z danych technicznych,
  C „Album”              — zdjęcia i duża nazwa modelu, najmniej tabel na starcie.
Każdy wariant w dwóch trybach: ogloszenie (T-263, adnotacja art. 66 KC) i zamowienie (T-262, blok danych zamówienia).
Plus stan obecny: Załącznik nr 1 umowy (renderAttachment1, 12 pól).

Paleta: papier #FFFFFF, granat #1B2A4A (nazwy, linie, rysunek), grafit #2F3746 (tekst), popiel #6B7484 (etykiety),
kreska #CDD3DB, czerwień #C92A2B tylko w cenie i liczbach wymiarów. Typografia: Barlow Condensed (nazwa, cena,
liczby), Barlow (tekst). Etykiety zdaniowo — bez wersalików i kropek środkowych.

PDF-y renderuje mPDF 8.3.1 z pluginu (render-mpdf.php) — ten sam silnik co umowa: tabele zamiast flex/grid,
zdjęcia kadrowane wcześniej (mPDF nie zna object-fit), schemat jako SVG (mPDF rysuje SVG z tekstem).
Dane: ogłoszenie #547360 BYD Seal 7 DM (HTML strony z 13.10 w tmp/makiety-zrodla), klient zamówienia fikcyjny.

Użycie: python3 docs/makiety/gen-t262-t263-pdf.py  → drop na auratest (katalog + index)."""
import html, re, shutil, subprocess, pathlib

DROP = pathlib.Path('/home/host476470/domains/auratest.pl/public_html/fe4f58fec53ctmp')
SUB = 'primaauto-mk-t262-t263-2026-10-13'
OUT = DROP / SUB
INDEX = DROP / 'primaauto-makieta-t262-t263-pdf-2026-10-13.html'
REPO = pathlib.Path(__file__).resolve().parents[2]
ZRODLO = REPO / 'tmp' / 'makiety-zrodla' / 't262-oferta-547360.html'
RENDER = pathlib.Path(__file__).with_name('render-mpdf.php')
UPL = pathlib.Path('/home/host476470/domains/primaauto.com.pl/public_html/wp-content/uploads/asiaauto/2026/10')
FOTO = lambda n: UPL / f'byd-seal-07-dm-2025-shenzhen-60175457-{n}.webp'
TMP = pathlib.Path('/tmp/claude-1584/-home-host476470-projekty-primaauto/mpdf-tmp')
e = html.escape

NAVY, RED, INK, ASH, LINE = '#1B2A4A', '#C92A2B', '#2F3746', '#6B7484', '#CDD3DB'
BORDO = '#9B0000'  # kolor logo i paska nagłówka strony (--c-header-bg)
# Logo szerokie odtworzone wektorowo (oryginał w uploads/2026/03 ma 200×55 px — za mało do druku).
LOGO = str(pathlib.Path(__file__).with_name('primaauto-logo-szerokie.svg'))
WYGENEROWANO = '13.10.2026, 13:40'
ZAM = {'nr': '#418236', 'data': '13.10.2026, 09:32', 'klient': 'Jan Kowalski (dane przykładowe)'}
OFERTA_URL = 'primaauto.com.pl/oferta/byd-seal-7-dm-2025-547360/'
KLAUZULA = ('Prima-Auto jest niezależnym importerem samochodów z Chin, nie autoryzowanym dealerem marki BYD. '
            'Nazwa marki służy wyłącznie identyfikacji pojazdu.')
FIRMA = 'Prima-Auto Ruslan Prima, ul. Jerzego Pleśniarowicza 2a lok. 38, Rzeszów, NIP 8133898576, REGON 525442846'


# ── dane z wyrenderowanej strony ogłoszenia ─────────────────────────────────────────────
def tekst(x):
    return ' '.join(html.unescape(re.sub(r'<[^>]+>', ' ', x)).split())


def dane():
    s = ZRODLO.read_text(encoding='utf-8')
    tech = []
    for m in re.finditer(r'<h3 class="aa-tech__title">(.*?)</h3>\s*<table class="aa-tech__table">(.*?)</table>', s, re.S):
        wiersze = []
        for r in re.findall(r'<tr[^>]*>(.*?)</tr>', m.group(2), re.S):
            k = re.findall(r'<t[hd][^>]*>(.*?)</t[hd]>', r, re.S)
            if len(k) >= 2:
                wiersze.append((tekst(k[0]), tekst(k[1])))
        tech.append((tekst(m.group(1)), wiersze))
    wyp = []
    for m in re.finditer(r'<h3 class="aa-equip__title">(.*?)</h3>(.*?)(?=<h3 class="aa-equip__title"|<script)', s, re.S):
        poz = [tekst(x) for x in re.findall(r'class="aa-equip__label">(.*?)</span>\s*</div>', m.group(2), re.S)]
        wyp.append((tekst(m.group(1)), poz))
    return tech, wyp


TECH, WYP = dane()
T = {k: v for _, w in TECH for k, v in w}
mm = lambda k: int(re.sub(r'\D', '', T[k]))
WYMIARY = {'dl': mm('Długość'), 'sz': mm('Szerokość'), 'wys': mm('Wysokość'), 'osie': mm('Rozstaw osi'),
           'kola': mm('Rozstaw kół przód')}
# Liczba + jednostka osobno: liczby składamy dużym Barlow Condensed, jednostkę mniejszą.
LICZBY = [('10 000', 'km', 'przebieg'), ('09/2025', '', 'pierwsza rejestracja'),
          (T['Moc układu elektrycznego (KM)'].split()[0], 'KM', 'moc układu elektrycznego'),
          (T['Zasięg elektryczny ( CLTC )'].split()[0], 'km', 'zasięg na prądzie (CLTC)'),
          (T['0-100 km/h'].split()[0].replace('.', ','), 's', 'od 0 do 100 km/h'),
          (T['Pojemność baterii (kWh)'].split()[0].replace('.', ','), 'kWh', 'bateria'),
          ('PHEV', '', 'hybryda plug-in'), ('1', '', 'właściciel')]
W_CENIE = ['Cło, VAT, akcyza, transport i odprawa celna', 'Homologacja i przygotowanie do rejestracji',
           'Zmiana języka interfejsu na polski lub angielski', 'Dodatkowy kluczyk lub pilot',
           'Dwa komplety filtrów: oleju, powietrza i kabinowy', 'Ładowarka 7 kW EU i przejściówka do ładowarek miejskich']
NAZWA, WERSJA = 'BYD Seal 7 DM', 'DM-i 1.5T 230km Flagship, rocznik 2025, 10 000 km'
CENA, NETTO = '169 000 PLN', 'cena z VAT, netto 137 398 PLN'
fmt = lambda n: f'{n:,}'.replace(',', ' ')


# ── zdjęcia i schemat ─────────────────────────────────────────────────────────────────────
def kadry():
    """mPDF nie przycina zdjęć (brak object-fit), więc kadrujemy wcześniej; przy okazji znika baner dealera."""
    plan = {'hero21': (1, '1024x512+0+240'), 'hero18': (1, '1024x540+0+228'), 'bok': (9, '1024x533+0+235'),
            'tyl': (13, '1024x533+0+235'), 'przod': (2, '1024x533+0+235'), 'wnetrze': (11, '1024x533+0+120'),
            'kanapa': (12, '1024x533+0+120')}
    for nazwa, (n, crop) in plan.items():
        subprocess.run(['magick', str(FOTO(n)), '-crop', crop, '+repage', '-resize', '1400x>', '-quality', '84',
                        str(OUT / f'{nazwa}.jpg')], check=True)
    f = {k: str(OUT / f'{k}.jpg') for k in plan}
    f['schemat'] = schemat()
    return f


def schemat():
    """Widok z góry (długość, rozstaw osi, szerokość) + widok z przodu (wysokość), w skali, z danych.
    Działa dla każdej oferty z wymiarami — nie zależy od zdjęcia."""
    W = WYMIARY
    s = 0.064                                     # px na mm
    L, B, H, WB, TR = (W['dl'] * s, W['sz'] * s, W['wys'] * s, W['osie'] * s, W['kola'] * s)
    x0, y0 = 70, 40                               # lewy górny róg nadwozia (widok z góry)
    ov = (L - WB) / 2                              # zwisy przyjęte równo — schemat, nie rysunek producenta
    ax_r, ax_f = x0 + ov, x0 + ov + WB
    tw, tl = B * 0.12, L * 0.115                   # opona w widoku z góry
    fx = x0 + L + 70                               # widok z przodu
    fy = y0 + B / 2 + H / 2                        # podłoże widoku z przodu
    nav, red, ash = NAVY, RED, ASH

    def wym(x1, y1, x2, y2, txt, opis, pion=False, strona=1):
        """Linia wymiarowa z ogranicznikami + liczba (czerwona) i opis (popiel)."""
        g = [f'<line x1="{x1:.1f}" y1="{y1:.1f}" x2="{x2:.1f}" y2="{y2:.1f}" stroke="{red}" stroke-width="0.8"/>']
        if pion:
            g += [f'<line x1="{x1 - 4:.1f}" y1="{y:.1f}" x2="{x1 + 4:.1f}" y2="{y:.1f}" stroke="{red}" stroke-width="0.8"/>' for y in (y1, y2)]
            cx, cy = x1 + 8 * strona, (y1 + y2) / 2
            anchor = 'start' if strona > 0 else 'end'
            g.append(f'<text x="{cx:.1f}" y="{cy + 1:.1f}" font-family="barlowcond" font-size="13" fill="{red}" text-anchor="{anchor}">{txt}</text>')
            g.append(f'<text x="{cx:.1f}" y="{cy + 12:.1f}" font-family="barlow" font-size="8.5" fill="{ash}" text-anchor="{anchor}">{opis}</text>')
        else:
            g += [f'<line x1="{x:.1f}" y1="{y1 - 4:.1f}" x2="{x:.1f}" y2="{y1 + 4:.1f}" stroke="{red}" stroke-width="0.8"/>' for x in (x1, x2)]
            cy = y1 + (15 if strona > 0 else -6)
            g.append(f'<text x="{(x1 + x2) / 2:.1f}" y="{cy:.1f}" font-family="barlowcond" font-size="13" fill="{red}" text-anchor="middle">{txt}'
                     f'<tspan font-family="barlow" font-size="8.5" fill="{ash}">  {opis}</tspan></text>')
        return ''.join(g)

    pomoc = lambda x1, y1, x2, y2: f'<line x1="{x1:.1f}" y1="{y1:.1f}" x2="{x2:.1f}" y2="{y2:.1f}" stroke="{LINE}" stroke-width="0.6"/>'
    opony = ''.join(f'<rect x="{ax - tl / 2:.1f}" y="{y:.1f}" width="{tl:.1f}" height="{tw:.1f}" rx="2" fill="{nav}"/>'
                    for ax in (ax_r, ax_f) for y in (y0 - tw * 0.35, y0 + B - tw * 0.65))
    nadwozie = (f'<rect x="{x0}" y="{y0}" width="{L:.1f}" height="{B:.1f}" rx="{B * 0.3:.1f}" fill="#FFFFFF" stroke="{nav}" stroke-width="1.3"/>'
                f'<rect x="{x0 + L * 0.3:.1f}" y="{y0 + B * 0.16:.1f}" width="{L * 0.42:.1f}" height="{B * 0.68:.1f}" rx="{B * 0.2:.1f}" '
                f'fill="none" stroke="{nav}" stroke-width="0.7"/>')
    osie = ''.join(f'<line x1="{ax:.1f}" y1="{y0 - 14:.1f}" x2="{ax:.1f}" y2="{y0 + B + 14:.1f}" stroke="{ash}" stroke-width="0.5" stroke-dasharray="3 2"/>'
                   for ax in (ax_r, ax_f))
    fw_top = B * 0.62
    przod = (f'<path d="M{fx:.1f},{fy - H * 0.12:.1f} L{fx:.1f},{fy - H * 0.55:.1f} Q{fx:.1f},{fy - H * 0.6:.1f} {fx + B * 0.06:.1f},{fy - H * 0.6:.1f} '
             f'L{fx + (B - fw_top) / 2:.1f},{fy - H:.1f} L{fx + (B + fw_top) / 2:.1f},{fy - H:.1f} L{fx + B - B * 0.06:.1f},{fy - H * 0.6:.1f} '
             f'Q{fx + B:.1f},{fy - H * 0.6:.1f} {fx + B:.1f},{fy - H * 0.55:.1f} L{fx + B:.1f},{fy - H * 0.12:.1f} Z" '
             f'fill="#FFFFFF" stroke="{nav}" stroke-width="1.3"/>'
             + ''.join(f'<rect x="{x:.1f}" y="{fy - H * 0.2:.1f}" width="{tw:.1f}" height="{H * 0.2:.1f}" rx="2" fill="{nav}"/>'
                       for x in (fx + (B - TR) / 2 - tw / 2, fx + (B + TR) / 2 - tw / 2))
             + f'<line x1="{fx - 10:.1f}" y1="{fy:.1f}" x2="{fx + B + 10:.1f}" y2="{fy:.1f}" stroke="{ash}" stroke-width="0.6"/>')
    linie = (pomoc(x0, y0 + B + 4, x0, y0 + B + 28) + pomoc(x0 + L, y0 + B + 4, x0 + L, y0 + B + 28) +
             wym(x0, y0 + B + 24, x0 + L, y0 + B + 24, fmt(W['dl']) + ' mm', 'długość') +
             wym(ax_r, y0 - 22, ax_f, y0 - 22, fmt(W['osie']) + ' mm', 'rozstaw osi', strona=-1) +
             pomoc(x0 - 4, y0, x0 - 24, y0) + pomoc(x0 - 4, y0 + B, x0 - 24, y0 + B) +
             wym(x0 - 20, y0, x0 - 20, y0 + B, fmt(W['sz']) + ' mm', 'szerokość', pion=True, strona=-1) +
             pomoc(fx + B + 4, fy - H, fx + B + 24, fy - H) +
             wym(fx + B + 20, fy - H, fx + B + 20, fy, fmt(W['wys']) + ' mm', 'wysokość', pion=True))
    podpisy = (f'<text x="{x0 + L / 2:.1f}" y="{y0 + B + 58:.1f}" font-family="barlow" font-size="9" fill="{nav}" text-anchor="middle">Widok z góry</text>'
               f'<text x="{fx + B / 2:.1f}" y="{y0 + B + 58:.1f}" font-family="barlow" font-size="9" fill="{nav}" text-anchor="middle">Widok z przodu</text>')
    szer, wys = fx + B + 110, y0 + B + 66
    svg = (f'<svg xmlns="http://www.w3.org/2000/svg" width="{szer:.0f}" height="{wys:.0f}" viewBox="0 0 {szer:.0f} {wys:.0f}">'
           f'{osie}{opony}{nadwozie}{przod}{linie}{podpisy}</svg>')
    p = OUT / 'schemat-wymiarow.svg'
    p.write_text(svg, encoding='utf-8')
    return str(p)


# ── wspólne klocki ───────────────────────────────────────────────────────────────────────
CSS = f"""<style>
body {{ font-family: barlow; color: {INK}; font-size: 9pt; }}
.nazwa {{ font-family: barlowcond; color: {NAVY}; }}
.cena {{ font-family: barlowcond; color: {RED}; }}
h2 {{ font-family: barlowcond; font-weight: normal; font-size: 15pt; color: {NAVY}; margin: 0 0 1mm; }}
h3 {{ font-family: barlow; font-weight: bold; font-size: 9pt; color: {NAVY}; margin: 0; padding: 0 0 1mm;
      border-bottom: 0.6pt solid {NAVY}; }}
table.t {{ width: 100%; border-collapse: collapse; margin: 0 0 3mm; }}
table.t td {{ padding: 0.75mm 0; border-bottom: 0.35pt solid {LINE}; vertical-align: top; font-size: 7.9pt; line-height: 1.15; }}
table.t td.k {{ color: {ASH}; width: 50%; padding-right: 2mm; }}
table.t td.v {{ color: {INK}; font-family: barlowmed; }}
.ety {{ font-size: 7.6pt; color: {ASH}; }}
.lista {{ font-size: 8.4pt; padding: 0.5mm 0; line-height: 1.2; }}
</style>"""


def naglowek(tryb, marka=False):
    if tryb == 'ogloszenie':
        prawy = ('Informacja handlowa, nie stanowi oferty w rozumieniu art. 66 Kodeksu cywilnego.<br>'
                 f'Wygenerowano {WYGENEROWANO}. Cena według stanu na ten dzień.')
    else:
        prawy = (f'Specyfikacja pojazdu do zamówienia {ZAM["nr"]}.<br>'
                 f'Dane z ogłoszenia zapisane w chwili złożenia zamówienia, {ZAM["data"]}.')
    if marka:
        return f"""<htmlpageheader name="hdr"><table width="100%" style="border-bottom:0.8pt solid {BORDO};padding-bottom:1.8mm"><tr>
<td width="38%" style="vertical-align:bottom"><img src="{LOGO}" style="width:34mm"></td>
<td width="62%" style="text-align:right;font-size:7pt;color:{ASH};vertical-align:bottom">{prawy}</td></tr></table></htmlpageheader>"""
    return f"""<htmlpageheader name="hdr"><table width="100%" style="border-bottom:0.5pt solid {LINE};padding-bottom:1.6mm"><tr>
<td width="38%" style="vertical-align:bottom"><span style="font-family:barlowcond;font-weight:bold;font-size:14pt;color:{NAVY}">PRIMA-AUTO</span>
<span style="font-family:barlowcond;font-size:7pt;color:{RED}"> CHINA CAR</span></td>
<td width="62%" style="text-align:right;font-size:7pt;color:{ASH};vertical-align:bottom">{prawy}</td></tr></table></htmlpageheader>"""


def stopka(tryb, marka=False):
    ref = 'Ogłoszenie 547360' if tryb == 'ogloszenie' else f'Zamówienie {ZAM["nr"]}'
    return f"""<htmlpagefooter name="ftr"><table width="100%" style="border-top:0.5pt solid {LINE};padding-top:1.6mm;font-size:6.8pt;color:{ASH}"><tr>
<td width="82%">{KLAUZULA}<br>{FIRMA}{', <b style="color:' + BORDO + '">primaauto.com.pl</b>' if marka else ''}</td>
<td width="18%" style="text-align:right;vertical-align:top">{ref}<br>strona {{PAGENO}} z {{nbpg}}</td></tr></table></htmlpagefooter>"""


def dokument(tryb, tresc, page_css='', marka=False):
    return f"""<html><head>{CSS}<style>@page {{ header: html_hdr; footer: html_ftr; }} {page_css}</style></head><body>
{naglowek(tryb, marka)}{stopka(tryb, marka)}{tresc}</body></html>"""


def tabela(wiersze):
    return '<table class="t">' + ''.join(f'<tr><td class="k">{e(k)}</td><td class="v">{e(v)}</td></tr>' for k, v in wiersze) + '</table>'


def tech_kolumny(n, odstep='6mm'):
    """Sekcje danych technicznych rozłożone na n kolumn możliwie równo (po liczbie wierszy)."""
    kol, wys = [[] for _ in range(n)], [0] * n
    for tytul, w in TECH:
        i = wys.index(min(wys))
        kol[i].append(f'<h3>{e(tytul)}</h3>' + tabela(w))
        wys[i] += len(w) + 3
    return ('<table width="100%" style="border-collapse:collapse"><tr>' +
            ''.join(f'<td width="{100 // n}%" style="vertical-align:top;padding-right:{odstep if i < n - 1 else "0"}">{"".join(k)}</td>'
                    for i, k in enumerate(kol)) + '</tr></table>')


def wyposazenie(n):
    kol = [[] for _ in range(n)]
    for i, (tytul, poz) in enumerate(WYP):
        kol[i % n].append(f'<h3>{e(tytul)}</h3><div style="height:1mm"></div>' +
                          ''.join(f'<div class="lista"><span style="color:{RED}">•</span>&nbsp; {e(p)}</div>' for p in poz) + '<br>')
    return ('<table width="100%"><tr>' + ''.join(f'<td width="{100 // n}%" style="vertical-align:top;padding-right:5mm">{"".join(k)}</td>'
                                                  for k in kol) + '</tr></table>')


def liczby(n, ile=8, duze=17):
    """Duże liczby z małym podpisem pod spodem; kolumny rozdziela pionowa kreska, bez teł."""
    poz = LICZBY[:ile]
    rzedy = [poz[i:i + n] for i in range(0, len(poz), n)]
    out = '<table width="100%" style="border-collapse:collapse">'
    for r in rzedy:
        out += '<tr>' + ''.join(
            f'<td width="{100 // n}%" style="padding:2.2mm 3mm 2.2mm {"0" if j == 0 else "3mm"};vertical-align:top;'
            f'{"border-left:0.5pt solid " + LINE + ";" if j else ""}">'
            f'<span class="nazwa" style="font-size:{duze}pt">{e(v)}</span>'
            f'<span style="font-family:barlowcond;font-size:{duze * 0.6:.0f}pt;color:{NAVY}"> {e(jd)}</span>'
            f'<div class="ety">{e(o)}</div></td>' for j, (v, jd, o) in enumerate(r)) + '</tr>'
    return out + '</table>'


def w_cenie_lub_zamowienie(tryb, kolumny=2):
    if tryb == 'ogloszenie':
        pol = (len(W_CENIE) + 1) // 2
        grupy = [W_CENIE[:pol], W_CENIE[pol:]] if kolumny == 2 else [W_CENIE]
        komorki = ''.join(f'<td width="{100 // len(grupy)}%" style="vertical-align:top;padding-right:4mm">' +
                          ''.join(f'<div class="lista"><span style="color:{RED}">+</span>&nbsp; {e(p)}</div>' for p in g) + '</td>'
                          for g in grupy)
        return f'<h3>W cenie auta</h3><table width="100%" style="margin-top:1mm"><tr>{komorki}</tr></table>'
    rows = [('Numer zamówienia', ZAM['nr']), ('Data złożenia', ZAM['data']), ('Zamawiający', ZAM['klient']),
            ('Cena pojazdu w zamówieniu', '169 000 PLN z VAT'), ('VIN', 'zostanie uzupełniony aneksem po weryfikacji pojazdu'),
            ('Ogłoszenie', OFERTA_URL)]
    return (f'<table width="100%" style="border:0.8pt solid {NAVY}"><tr><td style="padding:3mm 4mm">'
            f'<h3 style="border:none;padding:0 0 1mm">Dane zamówienia</h3>' + tabela(rows) +
            f'<div class="ety">Specyfikacja zapisana w chwili złożenia zamówienia. Późniejsza zmiana lub usunięcie ogłoszenia '
            f'nie zmienia tego dokumentu.</div></td></tr></table>')


def kontakt():
    return (f'<table width="100%" style="border-top:1.2pt solid {NAVY};margin-top:4mm"><tr>'
            f'<td style="padding-top:2.5mm"><span class="nazwa" style="font-size:13pt">Pytania o to auto?</span><br>'
            f'<span class="ety">Odbiór aut w Rzeszowie, ul. Przemysłowa 13A</span></td>'
            f'<td style="padding-top:2.5mm;text-align:right"><span class="cena" style="font-size:15pt">+48 721 730 507</span><br>'
            f'<span class="ety">china@primaauto.com.pl</span></td></tr></table>')


def tytul_i_cena(tryb, nazwa_pt=30, cena_pt=26):
    etykieta = 'Cena' if tryb == 'ogloszenie' else 'Cena w zamówieniu'
    return (f'<table width="100%"><tr><td style="vertical-align:bottom">'
            f'<div class="nazwa" style="font-size:{nazwa_pt}pt;line-height:1">{NAZWA}</div>'
            f'<div style="font-size:9.5pt;color:{ASH};padding-top:1mm">{WERSJA}</div></td>'
            f'<td style="text-align:right;vertical-align:bottom;white-space:nowrap"><div class="ety">{etykieta}</div>'
            f'<div class="cena" style="font-size:{cena_pt}pt;line-height:1">{CENA}</div>'
            f'<div class="ety">{NETTO}</div></td></tr></table>')


def foto(src, szer):
    return f'<img src="{src}" style="width:{szer}">'


# ── warianty ─────────────────────────────────────────────────────────────────────────────
def wariant_a(tryb, F):
    """A „Karta z salonu” — strona 1 w cienkiej ramce jak karta na szybie, duże liczby, cena w czerwonym obrysie."""
    etykieta = 'Cena' if tryb == 'ogloszenie' else 'Cena w zamówieniu'
    gora = (f'<table width="100%"><tr><td style="vertical-align:bottom">'
            f'<div class="nazwa" style="font-size:32pt;line-height:1">{NAZWA}</div>'
            f'<div style="font-size:9.5pt;color:{ASH};padding-top:1mm">{WERSJA}</div></td>'
            f'<td style="vertical-align:bottom;text-align:right" width="34%">'
            f'<table style="border:1pt solid {RED}" align="right"><tr><td style="padding:2mm 4mm;text-align:right">'
            f'<div class="ety">{etykieta}</div><div class="cena" style="font-size:24pt;line-height:1">{CENA}</div>'
            f'<div class="ety">{NETTO}</div></td></tr></table></td></tr></table>')
    karta = (f'<table width="100%" style="border:0.8pt solid {NAVY}"><tr><td style="padding:5mm 5mm 4mm">'
             f'{gora}<div style="height:4mm"></div>{foto(F["hero21"], "170mm")}<div style="height:2mm"></div>'
             f'{liczby(4)}<div style="height:2mm;border-bottom:0.5pt solid {LINE}"></div><div style="height:3mm"></div>'
             f'{w_cenie_lub_zamowienie(tryb)}</td></tr></table>')
    p2 = '<h2>Dane techniczne</h2><div style="height:2mm"></div>' + tech_kolumny(2, '8mm')
    galeria = (f'<table width="100%" style="border-collapse:collapse"><tr>'
               f'<td width="50%" style="padding:0 1.5mm 3mm 0">{foto(F["bok"], "89mm")}</td><td width="50%" style="padding:0 0 3mm 1.5mm">{foto(F["tyl"], "89mm")}</td></tr>'
               f'<tr><td style="padding:0 1.5mm 0 0">{foto(F["wnetrze"], "89mm")}</td><td style="padding:0 0 0 1.5mm">{foto(F["kanapa"], "89mm")}</td></tr></table>')
    p3 = (galeria + '<div style="height:5mm"></div><h2>Wyposażenie</h2><div style="height:1mm"></div>' + wyposazenie(3) +
          (kontakt() if tryb == 'ogloszenie' else ''))
    return dokument(tryb, karta + '<pagebreak />' + p2 + '<pagebreak />' + p3)


def wariant_b(tryb, F):
    """B „Rysunek techniczny” — 2 strony; mocny element: schemat wymiarów z danych, w skali."""
    lista = ''.join(f'<tr><td class="k">{e(o[0].upper() + o[1:])}</td><td class="v">{e(v)}{" " + e(j) if j else ""}</td></tr>'
                    for v, j, o in LICZBY)
    gora = (f'<table width="100%" style="margin-top:4mm"><tr>'
            f'<td width="106mm" style="vertical-align:top;padding-right:6mm">{foto(F["hero18"], "100mm")}<br>'
            f'<img src="{F["bok"]}" style="width:32.4mm;margin-top:1.4mm"> <img src="{F["tyl"]}" style="width:32.4mm;margin-top:1.4mm"> '
            f'<img src="{F["wnetrze"]}" style="width:32.4mm;margin-top:1.4mm"></td>'
            f'<td style="vertical-align:top"><h3>Najważniejsze</h3><table class="t">{lista}</table></td></tr></table>')
    rysunek = (f'<div style="height:3mm"></div><h3>Wymiary</h3><div style="height:3mm"></div>'
               f'<div style="text-align:center"><img src="{F["schemat"]}" style="width:150mm"></div>'
               f'<div class="ety" style="text-align:center">Schemat w skali według danych z ogłoszenia. Rozstaw kół z przodu '
               f'{fmt(WYMIARY["kola"])} mm, masa własna {T["Masa własna"]}.</div><div style="height:4mm"></div>')
    p1 = (f'<div style="border-bottom:1.2pt solid {NAVY};padding-bottom:2mm">{tytul_i_cena(tryb, 28, 24)}</div>' + gora + rysunek +
          w_cenie_lub_zamowienie(tryb) + (kontakt() if tryb == 'ogloszenie' else ''))
    p2 = ('<h2>Dane techniczne</h2><div style="height:1.5mm"></div>' + tech_kolumny(3, '5mm') +
          '<h2>Wyposażenie</h2><div style="height:1mm"></div>' + wyposazenie(3))
    return dokument(tryb, p1 + '<pagebreak />' + p2, marka=True)


def wariant_c(tryb, F):
    """C „Album” — zdjęcie na całą szerokość kolumny, nazwa modelu w dużej skali, mało tabel na starcie."""
    p1 = (foto(F['hero18'], '182mm') + '<div style="height:5mm"></div>' + tytul_i_cena(tryb, 40, 30) +
          f'<div style="height:4mm"></div><div style="border-top:0.6pt solid {NAVY}"></div>' + liczby(4, 4, 19) +
          f'<div style="border-top:0.5pt solid {LINE}"></div><div style="height:5mm"></div>' + w_cenie_lub_zamowienie(tryb) +
          (kontakt() if tryb == 'ogloszenie' else ''))
    miniatury = ''.join(f'<td width="25%" style="padding:3mm {"0" if k == 3 else "2mm"} 0 0">{foto(F[n], "44mm")}</td>'
                        for k, n in enumerate(('tyl', 'przod', 'wnetrze', 'kanapa')))
    p2 = (f'{foto(F["bok"], "182mm")}<table width="100%" style="border-collapse:collapse"><tr>{miniatury}</tr></table>'
          '<div style="height:6mm"></div><h2>Wyposażenie</h2><div style="height:1mm"></div>' + wyposazenie(3))
    p3 = '<h2>Dane techniczne</h2><div style="height:2mm"></div>' + tech_kolumny(2, '8mm')
    return dokument(tryb, p1 + '<pagebreak />' + p2 + '<pagebreak />' + p3)


def stan_obecny(F):
    """Załącznik nr 1 umowy — odwzorowanie renderAttachment1() (12 pól + wersja), font umowy (DejaVu)."""
    rows = [('Marka i model', 'BYD Seal 7 DM'), ('Paliwo', 'Plug-in hybryda (PHEV)'), ('Kolor', 'Biały'),
            ('Rok pierwszej rejestracji', '2025'), ('Nadwozie', 'Sedan'), ('Skrzynia biegów', 'Automatyczna'),
            ('Napęd', 'FWD'), ('Silnik', '1.5'), ('Moc', '156 KM'), ('Przebieg', '10 000 km'),
            ('VIN', 'zostanie uzupełniony w formie aneksu do niniejszej Umowy'), ('Lokalizacja (Chiny)', 'Shenzhen'),
            ('Wyposażenie', 'DM-i 1.5T 230km Flagship')]
    tr = ''.join(f'<tr style="background:{"#F7F8FA" if i % 2 == 0 else "#FFFFFF"}"><td style="padding:6px 10px;font-weight:bold;color:{NAVY};width:40%;border-bottom:0.5pt solid #E8EDF2">{e(k)}</td>'
                 f'<td style="padding:6px 10px;border-bottom:0.5pt solid #E8EDF2">{e(v)}</td></tr>' for i, (k, v) in enumerate(rows))
    return f"""<html><body style="font-family:dejavusanscondensed;font-size:9.5pt">
<div style="text-align:center;font-size:13pt;font-weight:bold;color:{NAVY}">Załącznik nr 1 do Umowy {ZAM["nr"]}</div>
<div style="text-align:center;font-size:10pt;color:#666;margin-bottom:16px">Opis pojazdu z ogłoszenia</div>
<div style="text-align:center;margin-bottom:14px"><img src="{F["hero18"]}" style="width:74mm"></div>
<table width="100%" cellpadding="0" cellspacing="0" style="border:0.5pt solid #E8EDF2">{tr}</table>
<p style="font-size:8pt;color:#999;margin-top:10px">Źródło ogłoszenia: https://{OFERTA_URL}</p></body></html>"""


WARIANTY = [('A', 'Karta z salonu', 'Pierwsza strona w cienkiej ramce, jak karta na szybie auta w salonie: nazwa, cena w czerwonym '
             'obrysie, duże zdjęcie i osiem najważniejszych liczb. Dane techniczne na stronie 2, zdjęcia i wyposażenie na 3.', wariant_a),
            ('B', 'Rysunek techniczny', 'Dwie strony. Na pierwszej zdjęcia, lista najważniejszych danych i schemat wymiarów w skali '
             '(widok z góry i z przodu), rysowany automatycznie z danych ogłoszenia. Najlepszy do druku, dla banku i leasingu.', wariant_b),
            ('C', 'Album', 'Zdjęcie na całą szerokość i nazwa modelu w dużej skali; na stronie 2 galeria i wyposażenie, na 3 '
             'dane techniczne. Najbardziej „prezentacyjny”, ale też najwięcej tuszu na zdjęcia.', wariant_c)]

# Wybór Janka 13.10: wariant B w brandingu Prima-Auto (logo). A i C zostają w kodzie jako historia makiety.
WYBRANE = {'B'}


def render(nazwa, tresc):
    h = OUT / f'{nazwa}.html'
    h.write_text(tresc, encoding='utf-8')
    pdf = OUT / f'{nazwa}.pdf'
    print(subprocess.run(['php', str(RENDER), str(h), str(pdf), str(TMP)], check=True, capture_output=True, text=True).stdout.strip())
    for p in OUT.glob(f'{nazwa}-s*.png'):
        p.unlink()
    subprocess.run(['gs', '-q', '-dNOPAUSE', '-dBATCH', '-sDEVICE=png16m', '-r60', '-dTextAlphaBits=4', '-dGraphicsAlphaBits=4',
                    f'-sOutputFile={OUT}/{nazwa}-s%d.png', str(pdf)], check=True)
    h.unlink()  # HTML pośredni nie jest dla oglądającego — liczy się PDF z mPDF
    return sorted(OUT.glob(f'{nazwa}-s*.png'), key=lambda p: int(p.stem.rsplit('-s', 1)[1]))


def strona(wyniki):
    def miniatury(nazwa, strony):
        return (f'<a class="doc" href="{SUB}/{nazwa}.pdf">' +
                ''.join(f'<img src="{SUB}/{p.name}" alt="strona {i + 1}">' for i, p in enumerate(strony)) +
                f'<span>Otwórz PDF ({len(strony)} str.)</span></a>')
    sekcje = [f'<section><h2>Stan obecny: Załącznik nr 1 do umowy</h2><p>Tyle dziś dostaje klient o zamówionym aucie: '
              f'12 pól i miniatura, bez danych technicznych i wyposażenia.</p>{miniatury("stan-obecny", wyniki["stan-obecny"])}</section>']
    for lit, nazwa, opis, _ in [w for w in WARIANTY if w[0] in WYBRANE]:
        sekcje.append(f'<section><h2>Wariant {lit}: {nazwa}</h2><p>{opis}</p>'
                      f'<h3>T-263, wydruk ogłoszenia (klient, przycisk „Pobierz PDF”)</h3>{miniatury(f"{lit}-ogloszenie", wyniki[f"{lit}-ogloszenie"])}'
                      f'<h3>T-262, specyfikacja w zamówieniu (ten sam wzór z blokiem „Dane zamówienia”)</h3>{miniatury(f"{lit}-zamowienie", wyniki[f"{lit}-zamowienie"])}</section>')
    return f"""<!doctype html><html lang="pl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>T-262 / T-263 — makiety PDF</title><style>
body{{margin:0;background:#EEF0F3;font:15px/1.5 system-ui,sans-serif;color:{INK}}}
main{{max-width:1180px;margin:0 auto;padding:24px 16px 60px}} h1{{color:{NAVY};margin:0 0 6px}}
section{{background:#fff;border-radius:8px;padding:18px 20px;margin:18px 0}} h2{{color:{NAVY};margin:0 0 4px;font-size:20px}}
h3{{font-size:14px;color:{ASH};margin:16px 0 8px;font-weight:600}}
.doc{{display:flex;gap:10px;align-items:flex-end;overflow-x:auto;text-decoration:none;padding-bottom:6px}}
.doc img{{height:330px;box-shadow:0 1px 6px rgba(0,0,0,.18);background:#fff}} .doc span{{white-space:nowrap;color:{RED};font-weight:600}}
.uw{{font-size:14px;color:{ASH}}}
@media (max-width:600px){{.doc img{{height:230px}}}}
</style></head><body><main>
<h1>T-262 „specyfikacja PDF w zamówieniu” i T-263 „wydruk ogłoszenia do PDF”, wariant B z logo</h1>
<p class="uw">Makiety z 13.10.2026 na ogłoszeniu #547360 (BYD Seal 7 DM). Wybrany wariant B „Rysunek techniczny” w brandingu Prima-Auto: logo w nagłówku (wektor, ostre w druku), linia w kolorze logo, adres strony w stopce. Projekt pod druk: biały papier,
bez dużych ciemnych powierzchni, kolor marki w liniach i typografii. PDF-y wyrenderował <b>mPDF 8.3.1 z pluginu</b>, tym samym
silnikiem co umowa. Font Barlow trzeba będzie dołożyć do pluginu (licencja OFL). Dane zamówienia w T-262 są fikcyjne. Adnotacje
prawne są w nagłówku i stopce każdej strony. Kliknij miniatury, żeby otworzyć PDF.</p>
{''.join(sekcje)}</main></body></html>"""


if __name__ == '__main__':
    if OUT.exists():
        shutil.rmtree(OUT)
    OUT.mkdir(parents=True)
    TMP.mkdir(parents=True, exist_ok=True)
    F = kadry()
    wyniki = {'stan-obecny': render('stan-obecny', stan_obecny(F))}
    for lit, _, _, fn in [w for w in WARIANTY if w[0] in WYBRANE]:
        for tryb in ('ogloszenie', 'zamowienie'):
            wyniki[f'{lit}-{tryb}'] = render(f'{lit}-{tryb}', fn(tryb, F))
    INDEX.write_text(strona(wyniki), encoding='utf-8')
    print('https://auratest.pl/fe4f58fec53ctmp/' + INDEX.name)
