#!/usr/bin/env python3
"""Makiety T-264 (13.10): „Zamów” → zgłoszenie chęci rezerwacji.
Każdy wariant = PRAWDZIWA strona primaauto.com.pl (HTML z 13.10, bez skryptów) z podmienionym tekstem/CSS,
w ramkach 1366 px (pomniejszona) i 390 px. Stan obecny zawsze pierwszy.

Sceny: A — przyciski na karcie oferty, B — strona /zamow/ krok 1, C — ekran po wysłaniu, D — mail statusu „weryfikacja”.
Oferta wzorcowa: BYD Seal 7 DM 2025 (#547360).

Użycie: python3 docs/makiety/gen-t264-zgloszenie.py  → drop na auratest (katalog + index)."""
import html, json, re, shutil, subprocess, pathlib, urllib.request, urllib.parse

DROP = pathlib.Path('/home/host476470/domains/auratest.pl/public_html/fe4f58fec53ctmp')
SUB = 'primaauto-mk-t264-2026-10-13'
OUT = DROP / SUB
URL_BASE = f'https://auratest.pl/fe4f58fec53ctmp/{SUB}/'
INDEX = DROP / 'primaauto-makieta-t264-zgloszenie-2026-10-13.html'
INDEX_W = DROP / 'primaauto-makieta-t264-wybrane-2026-10-13.html'
# Zestaw wybrany przez Janka 13.10 (quiz): A1, B1, C1, D1 + nadawca „Prima-Auto”, panel zostaje, pełna adnotacja.
WYBOR = {'A': 1, 'B': 1, 'C': 1, 'D': 1}
FONTS = pathlib.Path('/home/host476470/domains/primaauto.com.pl/public_html/wp-content/themes/primaauto2026/fonts')
WP = '/home/host476470/domains/primaauto.com.pl/public_html'
ZRODLA = pathlib.Path(__file__).resolve().parents[2] / 'tmp' / 'makiety-zrodla'
UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128 Safari/537.36'
LID = 547360
OFERTA = 'https://primaauto.com.pl/oferta/byd-seal-7-dm-2025-547360/'
e = html.escape

# Wspólne brzmienie adnotacji — jedno zdanie dla /zamow/, ekranu po wysłaniu i maila (do akceptu Janka).
ADNOTACJA = ('Zgłoszenie nie jest zamówieniem ani zawarciem umowy. Jest bezpłatne i do niczego Cię nie zobowiązuje — '
             'najpierw sprawdzamy dostępność i aktualną cenę auta u źródła, a umowę i depozyt ustalamy dopiero wtedy, '
             'gdy zdecydujesz, że chcesz kontynuować.')
TEL = '+48 721 730 507'

WSPOLNY_CSS = """
html{scroll-behavior:auto!important}
#cmplz-cookiebanner-container{display:none!important}
.mk-note{display:flex;gap:10px;align-items:flex-start;background:#F0F7F2;border-left:3px solid #38A169;padding:12px 14px;
  margin:0 0 18px;font:400 14px/1.5 Inter,sans-serif;color:#1B2A4A;border-radius:4px}
.mk-note svg{flex-shrink:0;margin-top:2px;color:#2F855A}
.mk-under{font:400 12px/1.4 Inter,sans-serif;color:#5C6B7F;text-align:center;margin:-2px 0 4px}
.mk-steps{display:flex;gap:6px;margin:0 0 16px;font:600 12px/1.3 Inter,sans-serif;color:#5C6B7F;flex-wrap:wrap}
.mk-steps span{background:#F5F6F8;border:1px solid #E1E4E8;border-radius:14px;padding:5px 10px}
.mk-steps span.on{background:#1B2A4A;border-color:#1B2A4A;color:#fff}
"""
POMOCNIK_JS = """<script>
document.addEventListener('click',function(ev){if(ev.target.closest('a,button'))ev.preventDefault();},true);
addEventListener('load',function(){
  var p=new URLSearchParams(location.search), sel=p.get('cel'), off=+(p.get('off')||0);
  if(!sel) return;
  var el=[].slice.call(document.querySelectorAll(sel)).filter(function(n){return n.offsetParent!==null;})[0];
  if(el) window.scrollTo({top:el.getBoundingClientRect().top+window.scrollY-off,behavior:'instant'});
});
</script>"""

IK_INFO = ('<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
           'stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>')
IK_KLUCZ = ('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" '
            'stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="4.5"/><path d="M10.7 12.3 20 3m-3 3 2 2m-5 1 2 2"/></svg>')
IK_OK = ('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
         'stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>')


def pobierz(url, cache):
    """Zrzut strony zapisany raz w tmp/makiety-zrodla/ — oferta może zniknąć w rotacji."""
    plik = ZRODLA / cache
    if plik.exists():
        return plik.read_text(encoding='utf-8')
    req = urllib.request.Request(url, headers={'User-Agent': UA})
    tresc = urllib.request.urlopen(req, timeout=60).read().decode('utf-8')
    ZRODLA.mkdir(parents=True, exist_ok=True)
    plik.write_text(tresc, encoding='utf-8')
    return tresc


def czysc(doc):
    doc = re.sub(r'<script\b[^>]*>.*?</script>', '', doc, flags=re.S)
    font = ''.join("@font-face{font-family:'Inter';font-weight:%s;font-display:swap;src:url('%sInter-%s.woff2') format('woff2')}"
                   % (wg, URL_BASE, n) for wg, n in (('400', 'Regular'), ('600', 'SemiBold'), ('700', 'Bold')))
    return doc.replace('<head>', '<head><base href="https://primaauto.com.pl/">', 1).replace(
        '</head>', f'<style>{font}{WSPOLNY_CSS}</style></head>', 1)


def wstaw(doc, css='', body=''):
    return doc.replace('</head>', f'<style>{css}</style></head>', 1).replace('</body>', body + POMOCNIK_JS + '</body>', 1)


def podmien(doc, stare, nowe, ile=1):
    assert doc.count(stare) >= 1, f'brak w HTML: {stare[:80]}'
    return doc.replace(stare, nowe, ile if ile else -1)


# ================================================================ A. PRZYCISKI NA KARCIE OFERTY
def oferta(doc, w):
    if w == 0:
        return wstaw(doc)
    # desktop — boczna kolumna
    etykieta = {1: 'Chcę to auto', 2: 'Zarezerwuj', 3: 'Chcę zarezerwować'}[w]
    pod = '<p class="mk-under">Bezpłatne zgłoszenie — nie jest zamówieniem</p>'
    doc = podmien(doc, 'class="aa-cta__btn aa-cta__reserve">Zamów</a>',
                  f'class="aa-cta__btn aa-cta__reserve">{"" if w != 2 else IK_KLUCZ}{etykieta}</a>{pod}')
    # telefon — dolny pasek
    m = re.search(r'(<a [^>]*class="aa-mcta aa-mcta--order">)(.*?)</a>', doc, flags=re.S)
    assert m, 'brak przycisku w pasku mobilnym'
    ikona = {1: '', 2: IK_KLUCZ, 3: ''}[w]
    doc = doc.replace(m.group(0), f'{m.group(1)}{ikona} {etykieta}</a>', 1)
    css = ''
    if w == 3:
        # dwa rzędy: główny przycisk na całą szerokość + podpis, pod spodem Zadzwoń | WhatsApp
        doc = doc.replace(f'{m.group(1)}{ikona} {etykieta}</a>',
                          f'{m.group(1)}{etykieta} <small style="font-weight:400;opacity:.85">· bez zobowiązań</small></a>', 1)
        css = ('@media (max-width:768px){.aa-mobile-cta{grid-template-columns:1fr 1fr!important}'
               '.aa-mcta--order{grid-column:1/-1;padding:13px 8px!important;font-size:15px!important}}')
    return wstaw(doc, css)


# ================================================================ B/C. KREATOR /zamow/
def kreator(doc, w, krok):
    css = ''
    if krok == 2:
        # stan po wysłaniu: panel 2 widoczny, numer zgłoszenia i telefon jak po odpowiedzi API
        doc = doc.replace('data-panel="1" >', 'data-panel="1" style="display:none">', 1)
        doc = podmien(doc, 'data-panel="2" style="display:none">', 'data-panel="2">')
        doc = re.sub(r'(aa-wiz__order-num">)#\d*', r'\g<1>#547402', doc, count=1)
        doc = doc.replace('<strong class="aa-wiz__phone"></strong>', f'<strong class="aa-wiz__phone">{TEL}</strong>', 1)
        doc = doc.replace('aa-wiz__step-dot--active', '')
        doc = re.sub(r'class="aa-wiz__step-dot\s+([^"]*)"\s+data-step="2"',
                     'class="aa-wiz__step-dot aa-wiz__step-dot--done aa-wiz__step-dot--active" data-step="2"', doc, count=1)
    if w == 0:
        return wstaw(doc, css)

    h1 = {(1, 1): 'Zgłoszenie rezerwacji', (2, 1): 'Chcę to auto',
          (1, 2): 'Zgłoszenie rezerwacji', (2, 2): 'Zgłoszenie rezerwacji'}[(w, krok)]
    doc = podmien(doc, '<h1 class="entry-title">Zamów</h1>', f'<h1 class="entry-title">{h1}</h1>')
    note = f'<div class="mk-note">{IK_INFO}<span>{e(ADNOTACJA)}</span></div>'
    if krok == 1:
        if w == 1:
            doc = podmien(doc, '<h2 class="aa-wiz__title">Poproś o rezerwację</h2>',
                          '<h2 class="aa-wiz__title">Zgłoś chęć rezerwacji</h2>')
            doc = re.sub(r'(<p class="aa-wiz__desc">Podaj dane kontaktowe[^<]*</p>)', r'\1' + note, doc, count=1)
        else:
            kroki = ('<div class="mk-steps"><span class="on">1. Zgłoszenie</span><span>2. Dostępność i cena</span>'
                     '<span>3. Umowa i depozyt</span></div>')
            doc = podmien(doc, '<h2 class="aa-wiz__title">Poproś o rezerwację</h2>',
                          '<h2 class="aa-wiz__title">Poproś o rezerwację</h2>' + kroki)
            doc = re.sub(r'(<button type="submit" class="aa-wiz__btn aa-wiz__btn--primary">\s*<span class="aa-wiz__btn-text">Wyślij zgłoszenie</span>)',
                         f'<p class="mk-under" style="text-align:left;margin:0 0 10px">{e(ADNOTACJA)}</p>' + r'\1', doc, count=1)
    else:
        tyt = {1: 'Otrzymaliśmy Twoje zgłoszenie', 2: 'Dziękujemy za przesłanie zgłoszenia'}[w]
        doc = podmien(doc, '<h2 class="aa-wiz__title">Weryfikujemy zgłoszenie</h2>', f'<h2 class="aa-wiz__title">{tyt}</h2>')
        doc = re.sub(r'<p class="aa-wiz__desc">Sprawdzamy dostępność pojazdu z ogłoszenia\..*?</p>',
                     '<p class="aa-wiz__desc">Sprawdzamy dostępność i aktualną cenę tego auta u źródła. Skontaktujemy się z Tobą '
                     'telefonicznie lub mailowo w ciągu <strong>24 godzin</strong> w sprawie dalszych kroków rezerwacji.</p>'
                     + note, doc, count=1, flags=re.S)
    return wstaw(doc, css)


# ================================================================ D. MAIL STATUSU „weryfikacja”
def szablony():
    r = subprocess.run(['wp', f'--path={WP}', 'eval',
                        '$t=get_option("asiaauto_order_email_templates")["status_weryfikacja"];'
                        '$m=new ReflectionMethod("AsiaAuto_Order_Mail","responsiveStyle");$m->setAccessible(true);'
                        '$t["style"]=$m->invoke(null);echo json_encode($t);'],
                       capture_output=True, text=True, check=True).stdout
    return json.loads(r)


def mail(tpl, w):
    zm = {'{customer_name}': 'Marek', '{order_id}': '547402', '{listing_title}': 'BYD Seal 7 DM 2025 DM-i 1.5T 230km Flagship',
          '{magic_link}': '#', '{company_phone}': TEL}
    temat, tresc, od = tpl['subject'], tpl['body'], 'Zamówienia Prima-Auto'
    if w == 1:
        od = 'Prima-Auto'
        temat = 'Prima-Auto — sprawdzamy dostępność (zgłoszenie #{order_id})'
        tresc = podmien(tresc, 'Status Twojego zamówienia zmienił się na <b>Weryfikacja</b>.',
                        'Twoje zgłoszenie jest w weryfikacji — sprawdzamy u źródła dostępność i aktualną cenę auta. '
                        f'Odezwiemy się w sprawie dalszych kroków rezerwacji.</p><p style="margin:0 0 16px;color:#5C6B7F;font-size:14px;">{e(ADNOTACJA)}')
        tresc = podmien(tresc, '>Podgląd zamówienia</a>', '>Śledź status zgłoszenia</a>')
    for k, v in zm.items():
        temat, tresc = temat.replace(k, v), tresc.replace(k, v)
    glowa = (f'<div style="font:14px/1.6 Arial,sans-serif;color:#111827;background:#fff;border-bottom:1px solid #E5E7EB;padding:10px 16px">'
             f'<div><span style="color:#6B7280">Od:</span> <b>{e(od)}</b> &lt;china@primaauto.com.pl&gt;</div>'
             f'<div><span style="color:#6B7280">Temat:</span> <b>{e(temat)}</b></div></div>')
    return f'<!doctype html><html lang="pl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{{margin:0;background:#F3F4F6}}</style>{tpl["style"]}</head><body>{glowa}{tresc}</body></html>'


# ================================================================ STRONA ZBIORCZA
def ramka(src, szer, wys, skala, cel='', off=0):
    q = f'?cel={urllib.parse.quote(cel)}&off={off}' if cel else ''
    return (f'<div class="fr" style="width:{round(szer * skala)}px;height:{round(wys * skala)}px">'
            f'<iframe loading="lazy" src="{SUB}/{src}{q}" style="width:{szer}px;height:{wys}px;transform:scale({skala})"></iframe></div>'
            f'<a class="full" href="{SUB}/{src}{q}" target="_blank">otwórz osobno ↗</a>')


def strona(sceny, wybrane=False):
    nav = ''.join(f'<a href="#s{k}">{k}. {e(t)}</a>' for k, t, *_ in sceny)
    out = []
    for k, tyt, opis, warianty, p in sceny:
        rows = []
        for t, d, plik in warianty:
            par = []
            if p.get('d_h'):
                par.append(f'<div><span class="lab">{p.get("d_lab", "Desktop 1366 px (pomniejszone)")}</span>'
                           f'{ramka(plik, p.get("d_w", 1366), p["d_h"], p.get("d_s", .62), p.get("d_cel", ""), p.get("d_off", 0))}</div>')
            par.append(f'<div><span class="lab">Telefon 390 px</span>{ramka(plik, 390, p["t_h"], 1, p.get("t_cel", ""), p.get("t_off", 0))}</div>')
            rows.append(f'<div class="war"><h3>{e(t)}</h3><p>{d}</p><div class="pair">{"".join(par)}</div></div>')
        out.append(f'<section id="s{k}"><h2>{k}. {e(tyt)}</h2><p class="op">{opis}</p>{"".join(rows)}</section>')
    return f"""<!doctype html><html lang="pl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex"><title>Zgłoszenie zamiast zamówienia — makiety</title>
<style>
:root{{--bg:#F5F6F8;--fg:#1B2A4A;--mut:#5C6B7F;--bd:#E1E4E8;--card:#fff}}
@media (prefers-color-scheme:dark){{:root:not([data-theme="light"]){{--bg:#10151f;--fg:#e8edf5;--mut:#9aa7b8;--bd:#2a3342;--card:#171e2b}}}}
body{{margin:0;background:var(--bg);color:var(--fg);font:15px/1.5 system-ui,-apple-system,Segoe UI,sans-serif}}
header{{position:sticky;top:0;z-index:5;background:var(--card);border-bottom:1px solid var(--bd);padding:10px 16px;display:flex;gap:14px;flex-wrap:wrap;align-items:center}}
header span{{font-weight:700}}header a{{color:var(--fg);font-size:14px}}
main{{max-width:1320px;margin:0 auto;padding:16px}}
.intro{{background:var(--card);border:1px solid var(--bd);border-radius:10px;padding:14px 18px}}
section{{margin-top:34px}}h2{{margin:0 0 4px;font-size:24px}}.op{{color:var(--mut);margin:0 0 10px;max-width:900px}}
.war{{background:var(--card);border:1px solid var(--bd);border-radius:10px;padding:14px 16px;margin-top:14px}}
.war h3{{margin:0;font-size:17px}}.war p{{margin:4px 0 12px;color:var(--mut);max-width:900px}}
.pair{{display:flex;gap:22px;align-items:flex-start;flex-wrap:wrap}}
.lab{{display:block;font-size:12px;color:var(--mut);margin-bottom:4px}}
.fr{{overflow:hidden;border:1px solid var(--bd);border-radius:6px;background:#fff;max-width:calc(100vw - 64px)}}
.fr iframe{{border:0;transform-origin:0 0;display:block}}
.full{{display:inline-block;font-size:12px;color:var(--mut);margin-top:4px}}
blockquote{{margin:8px 0 0;padding:8px 12px;border-left:3px solid #38A169;background:var(--bg)}}
</style></head><body>
<header><span>T-264 · makiety 13.10:</span>{nav}</header>
<main><div class="intro">{'<p style="margin:0 0 10px"><b>Zestaw wybrany 13.10: A1, B1, C1, D1.</b> Nadawca maili „Prima-Auto”, zakładka „Moje zamówienia” w panelu zostaje (zmienia się tylko pusty stan), adnotacja w pełnym brzmieniu. Wszystkie warianty: <a href="primaauto-makieta-t264-zgloszenie-2026-10-13.html">strona wyboru</a>.</p>' if wybrane else ''}<p style="margin:0">Każda ramka to <b>prawdziwa strona primaauto.com.pl</b> (HTML z 13.10, bez skryptów) z podmienionym tekstem.
Oferta wzorcowa: BYD Seal 7 DM 2025. Zmieniają się tylko teksty i układ paska — logika zamówień, statusy i adres <code>/zamow/</code> zostają.
{'' if wybrane else 'Wybierz po jednym wariancie w A–D (np. „A1, B1, C1, D1”).'}</p>
<p style="margin:10px 0 0">Wspólne zdanie adnotacji (B, C, D — do akceptu):</p><blockquote>{e(ADNOTACJA)}</blockquote></div>
{''.join(out)}</main></body></html>"""


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    for n in ('Regular', 'SemiBold', 'Bold'):
        shutil.copy(FONTS / f'Inter-{n}.woff2', OUT)
    prod = czysc(pobierz(OFERTA, f'prod-{LID}-2026-10-13.html'))
    zam = czysc(pobierz(f'https://primaauto.com.pl/zamow/?listing_id={LID}', f'zamow-{LID}-2026-10-13.html'))
    tpl = szablony()

    def plik(nazwa, tresc):
        (OUT / nazwa).write_text(tresc, encoding='utf-8')
        return nazwa

    sceny = []
    A = [('Stan obecny', '„Zamów” w bocznej kolumnie; na telefonie ikona koszyka + „Zamów” w pasku trzech przycisków.'),
         ('A1 · „Chcę to auto”', 'Etykieta z rozmowy 13.10. Na telefonie bez ikony — z ikoną nie mieści się w 1/3 paska. '
          'Na desktopie pod przyciskiem podpis „Bezpłatne zgłoszenie — nie jest zamówieniem”.'),
         ('A2 · „Zarezerwuj” z kluczykiem', 'Najkrótsza etykieta, mieści ikonę także na telefonie. Kluczyk zamiast koszyka. '
          'Ryzyko: „Zarezerwuj” brzmi jak natychmiastowa rezerwacja, a rezerwacja następuje dopiero po depozycie. Podpis na desktopie jak w A1.'),
         ('A3 · „Chcę zarezerwować” w dwóch rzędach', 'Pasek mobilny w dwóch rzędach: główny przycisk na całą szerokość z dopiskiem „· bez zobowiązań”, '
          'pod nim Zadzwoń | WhatsApp. Najczytelniej i najbezpieczniej prawnie, ale pasek jest ~50 px wyższy i zasłania więcej oferty.')]
    sceny.append(('A', 'Przyciski na karcie oferty', 'Desktop: boczna kolumna z ceną. Telefon: przyklejony pasek na dole ekranu (79,6% ruchu).',
                  [(t, e(d), plik(f'A{i}.html', oferta(prod, i))) for i, (t, d) in enumerate(A)],
                  dict(d_h=760, d_cel='.aa-cta', d_off=420, t_h=760)))
    B = [('Stan obecny', 'Nagłówek strony „Zamów”, w formularzu „Poproś o rezerwację” i „Wyślij zgłoszenie” — formularz już mówi językiem zgłoszenia, nagłówek nie.'),
         ('B1 · Nagłówek „Zgłoszenie rezerwacji” + ramka nad formularzem', 'Nagłówek strony i formularza zmienione, adnotacja w zielonej ramce zaraz pod opisem — klient czyta ją przed wpisaniem danych.'),
         ('B2 · Nagłówek „Chcę to auto” + trzy kroki + adnotacja nad przyciskiem', 'Nagłówek powtarza przycisk z karty oferty (pasuje do A1). Pod tytułem formularza trzy kroki pokazują, że zgłoszenie to dopiero pierwszy z nich. '
          'Adnotacja drobnym drukiem tuż nad „Wyślij zgłoszenie”.')]
    sceny.append(('B', 'Strona /zamow/ — formularz zgłoszenia', 'Adres <code>/zamow/</code> zostaje (linki w mailach, kampaniach, GA4). Zmienia się nagłówek strony (dziś „Zamów”) i tekst nad formularzem.',
                  [(t, e(d), plik(f'B{i}.html', kreator(zam, i, 1))) for i, (t, d) in enumerate(B)],
                  dict(d_h=900, d_cel='.aa-wiz', d_off=140, t_h=900, t_cel='.aa-wiz__panel[data-panel="1"]', t_off=90)))
    C = [('Stan obecny', '„Weryfikujemy zgłoszenie” + numer zgłoszenia. Brak słowa „zamówienie”, ale też brak informacji, że to niczego nie przesądza.'),
         ('C1 · „Otrzymaliśmy Twoje zgłoszenie”', 'Brzmienie z rozmowy: „otrzymaliśmy Twoje zgłoszenie… w sprawie dalszych kroków rezerwacji”. Pod spodem ta sama adnotacja co na formularzu.'),
         ('C2 · „Dziękujemy za przesłanie zgłoszenia”', 'Druga wersja z rozmowy. Treść i adnotacja jak w C1.')]
    sceny.append(('C', 'Ekran po wysłaniu formularza', 'To samo miejsce, w które wraca klient z linku w mailu, dopóki zgłoszenie jest w weryfikacji.',
                  [(t, e(d), plik(f'C{i}.html', kreator(zam, i, 2))) for i, (t, d) in enumerate(C)],
                  dict(d_h=760, d_cel='.aa-wiz', d_off=140, t_h=760, t_cel='.aa-wiz__panel[data-panel="2"]', t_off=90)))
    D = [('Stan obecny', 'Drugi mail, który klient dostaje (status „Weryfikacja”): „Status Twojego zamówienia…”, przycisk „Podgląd zamówienia”, temat „(zamówienie #…)”, nadawca „Zamówienia Prima-Auto”. '
          'Pierwszy mail („przyjęliśmy Twoje zgłoszenie… nic nie kosztuje i do niczego nie zobowiązuje”) jest już dobry i zostaje.'),
         ('D1 · Język zgłoszenia', 'Temat „sprawdzamy dostępność (zgłoszenie #…)”, treść o weryfikacji u źródła i dalszych krokach rezerwacji, adnotacja drobniej, '
          'przycisk „Śledź status zgłoszenia” jak w pierwszym mailu, nadawca „Prima-Auto”. Ta sama zmiana dla maila statusu „Nowe”.')]
    sceny.append(('D', 'Mail statusu „Weryfikacja”', 'Szablon z bazy (wersja edytowana w panelu) ze stylami mobilnymi z <code>responsiveStyle()</code>, podstawione przykładowe dane. '
                  'W przeglądarce o szerokości 390 px mail rozjeżdża się do 749 px — <b>tak samo w stanie obecnym</b> (pasek postępu „Zgłoszenie › … › Odbiór” nie łamie się). '
                  'Jak to wygląda w Gmailu i Outlooku na telefonie — niezweryfikowane.',
                  [(t, e(d), plik(f'D{i}.html', mail(tpl, i))) for i, (t, d) in enumerate(D)],
                  dict(d_h=820, d_w=640, d_s=1, d_lab='Program pocztowy 640 px', t_h=820)))
    INDEX.write_text(strona(sceny), encoding='utf-8')
    # strona z samym wybranym zestawem: w każdej scenie tylko wariant z WYBOR (bez stanu obecnego)
    tylko = [(k, t, o, [w[WYBOR[k]]], p) for k, t, o, w, p in sceny]
    INDEX_W.write_text(strona(tylko, wybrane=True), encoding='utf-8')
    print('https://auratest.pl/fe4f58fec53ctmp/' + INDEX.name)
    print('https://auratest.pl/fe4f58fec53ctmp/' + INDEX_W.name)


if __name__ == '__main__':
    main()
