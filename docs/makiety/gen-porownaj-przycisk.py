#!/usr/bin/env python3
"""Makiety T-115 etap 2 (17.09): przycisk „Porównaj”, wejście w nagłówku, pasek schowka, okienko zamiany.
Każdy wariant = PRAWDZIWA strona (HTML z primaauto.com.pl, bez skryptów) z wstrzykniętym CSS/HTML wariantu,
pokazana w ramkach 1366 px (pomniejszona) i 390 px. Stan obecny zawsze pierwszy.

Scenariusz danych (prawdziwe oferty z /samochody/ 17.09):
  schowek = Zeekr 8X PHEV 70kWh (2 oferty tej samej wersji na liście) + Xiaomi SU7 Ultra
  Li Auto L8 Livis, AITO M8, Denza Z9 GT, Xiaomi N90, Geely Atlas = bez wiersza wersji → przycisk „do modelu”
  pełny schowek (okienko zamiany) = + AITO M9 EREV Ultra, klik przy Zeekr 9X Ultra (oferta 390631)

Użycie: python3 docs/makiety/gen-porownaj-przycisk.py  → drop na auratest (katalog + index)."""
import html, re, shutil, subprocess, pathlib, urllib.request

DROP = pathlib.Path('/home/host476470/domains/auratest.pl/public_html/fe4f58fec53ctmp')
SUB = 'primaauto-mk-porownaj-2026-09-17'
OUT = DROP / SUB
URL_BASE = f'https://auratest.pl/fe4f58fec53ctmp/{SUB}/'
INDEX = DROP / 'primaauto-makieta-porownaj-2026-09-17.html'
FONTS = pathlib.Path('/home/host476470/domains/primaauto.com.pl/public_html/wp-content/themes/primaauto2026/fonts')
UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128 Safari/537.36'
e = html.escape


ZRODLA = pathlib.Path(__file__).resolve().parents[2] / 'tmp' / 'makiety-zrodla'


def pobierz(url, cache):
    """Zrzut strony zapisany raz w tmp/makiety-zrodla/ — katalog zmienia się co kilka minut, a stany na kartach są przypięte do ID ofert."""
    plik = ZRODLA / cache
    if plik.exists():
        return plik.read_text(encoding='utf-8')
    req = urllib.request.Request(url, headers={'User-Agent': UA})
    tresc = urllib.request.urlopen(req, timeout=60).read().decode('utf-8')
    ZRODLA.mkdir(parents=True, exist_ok=True)
    plik.write_text(tresc, encoding='utf-8')
    return tresc


def spec_stany(ids):
    """listing_id → 'ok' | 'model' (brak specid albo brak wiersza wersji)."""
    q = ("SELECT p.ID, IF(v.specid IS NULL,'model','ok') FROM wp7j_posts p "
         "LEFT JOIN wp7j_postmeta m ON m.post_id=p.ID AND m.meta_key='_asiaauto_spec_id' "
         "LEFT JOIN wp7j_asiaauto_versions v ON v.specid=m.meta_value WHERE p.ID IN (%s)" % ','.join(ids))
    r = subprocess.run(['wp', '--path=/home/host476470/domains/primaauto.com.pl/public_html', 'db', 'query', q,
                        '--skip-column-names'], capture_output=True, text=True, check=True).stdout
    return dict(l.split('\t') for l in r.strip().splitlines())


# ---------------------------------------------------------------- ikony
WAGA = ('<svg class="mk-i" width="{s}" height="{s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" '
        'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/>'
        '<path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/>'
        '<path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/></svg>')
SERCE = ('<svg class="mk-i" width="{s}" height="{s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" '
         'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3'
         'c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>')
FAJKA = ('<svg width="{s}" height="{s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" '
         'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>')
w = lambda s=18: WAGA.format(s=s)
h = lambda s=18: SERCE.format(s=s)
f = lambda s=12: FAJKA.format(s=s)

SCHOWEK = [
    dict(model='Zeekr 8X PHEV', wersja='8X 2026 70kWh', cena='523 000 zł',
         img='https://primaauto.com.pl/wp-content/uploads/asiaauto/2026/09/zeekr-8x-2026-shenzhen-59932450-1-350x250.webp'),
    dict(model='Xiaomi SU7 Ultra', wersja='Ultra', cena='344 000 zł',
         img='https://primaauto.com.pl/wp-content/uploads/asiaauto/2026/09/xiaomi-xiaomi-su7-ultra-2025-xiamen-59937317-1-350x250.webp'),
    dict(model='AITO M9', wersja='EREV Ultra 52kWh 6-osobowy', cena='386 000 zł',
         img='https://primaauto.com.pl/wp-content/uploads/asiaauto/2026/09/aito-aito-m9-2025-dongguan-59938611-1-350x250.webp'),
]
NOWA = dict(model='Zeekr 9X', wersja='Ultra 70kWh 6-osobowy')
IN_IDS = {'483268', '483281', '483226'}          # dwie oferty Zeekr 8X (ta sama wersja) + Xiaomi SU7 Ultra

# ---------------------------------------------------------------- wspólne wstawki
WSPOLNY_CSS = """
html{scroll-behavior:auto!important}
#cmplz-cookiebanner-container{display:none!important}
.mk-i{display:block;flex-shrink:0}
.mk-badge{position:absolute;top:-5px;right:-6px;min-width:17px;height:17px;padding:0 4px;border-radius:9px;background:#C92A2B;
  color:#fff;font:700 10px/17px Inter,sans-serif;text-align:center;box-shadow:0 0 0 2px #fff}
.mk-note{position:fixed;left:8px;top:8px;z-index:100000;background:#E8AC07;color:#1B2A4A;font:700 12px/1 Inter,sans-serif;
  padding:6px 9px;border-radius:4px;pointer-events:none;opacity:.95}
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


def czysc(doc):
    doc = re.sub(r'<script\b[^>]*>.*?</script>', '', doc, flags=re.S)
    font = ''.join("@font-face{font-family:'Inter';font-weight:%s;font-display:swap;src:url('%sInter-%s.woff2') format('woff2')}"
                   % (wg, URL_BASE, n) for wg, n in (('400', 'Regular'), ('600', 'SemiBold'), ('700', 'Bold')))
    return doc.replace('<head>', '<head><base href="https://primaauto.com.pl/">', 1).replace(
        '</head>', f'<style>{font}{WSPOLNY_CSS}</style></head>', 1)


def wstaw(doc, css='', body=''):
    return doc.replace('</head>', f'<style>{css}</style></head>', 1).replace('</body>', body + POMOCNIK_JS + '</body>', 1)


# ================================================================ A. NAGŁÓWEK
def naglowek(doc, wariant, ile=2):
    if wariant == 0:
        return doc
    if wariant == 1:   # ikony w białej pigułce obok telefonu
        ikony = (f'<a href="#" class="pa-pill__btn mk-hb" aria-label="Porównanie: 2 auta">{w(19)}<span class="mk-badge">2</span></a>'
                 f'<a href="#" class="pa-pill__btn mk-hb" aria-label="Ulubione">{h(19)}</a><span class="pa-pill__sep" aria-hidden="true"></span>')
        doc = doc.replace('<div class="pa-header__contact">', '<div class="pa-header__contact">' + ikony)
        css = '.mk-hb{position:relative} @media(max-width:768px){.pa-header .pa-pill__btn{width:32px}}'
        return wstaw(doc, css)
    if wariant == 2:   # osobne białe ikony na granacie, przed pigułką
        ikony = (f'<div class="mk-hic"><a href="#" aria-label="Porównanie: {ile} auta">{w(22)}<span class="mk-badge">{ile}</span></a>'
                 f'<a href="#" aria-label="Ulubione">{h(22)}</a></div>')
        doc = doc.replace('<div class="pa-header__contact">', ikony + '<div class="pa-header__contact">')
        css = """.mk-hic{display:flex;gap:4px;flex-shrink:0}
.mk-hic a{position:relative;display:flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:8px;color:#fff}
.mk-hic a:hover{background:rgba(255,255,255,.12)}
.mk-hic .mk-badge{top:2px;right:0;box-shadow:0 0 0 2px var(--c-header-bg,#9B0000)}
@media(max-width:768px){.mk-hic{gap:0;margin-left:auto}.mk-hic a{width:36px}}"""
        return wstaw(doc, css)
    if wariant == 3:   # desktop: ikona + etykieta; telefon: w menu pod hamburgerem, licznik na hamburgerze
        ikony = (f'<div class="mk-hlab"><a href="#">{w(18)}<span>Porównanie</span><span class="mk-cnt">2</span></a>'
                 f'<a href="#">{h(18)}<span>Ulubione</span></a></div>')
        doc = doc.replace('<nav class="pa-header__nav"', '<nav class="pa-header__nav"', 1)
        # tylko desktopowa pigułka: pierwsze wystąpienie
        doc = doc.replace('<div class="pa-header__contact">', ikony + '<div class="pa-header__contact">', 1)
        doc = doc.replace('<button type="button"\n                class="pa-hamburger"', '<button type="button"\n                class="pa-hamburger mk-hamb"', 1)
        doc = doc.replace('class="pa-mobile-menu__list">',
                          f'class="pa-mobile-menu__list"><li class="menu-item mk-mm"><a href="#">{w(18)} Porównanie <span class="mk-cnt">2</span></a></li>'
                          f'<li class="menu-item mk-mm"><a href="#">{h(18)} Ulubione</a></li>', 1)
        doc = re.sub(r'(<nav id="pa-mobile-menu"[^>]*?)\s+hidden>', r'\1>', doc, flags=re.S)
        css = """.mk-hlab{display:flex;gap:14px;flex-shrink:0;margin-right:6px}
.mk-hlab a{display:flex;align-items:center;gap:6px;color:#fff;font-size:14px;font-weight:600;white-space:nowrap}
.mk-cnt{display:inline-block;min-width:18px;height:18px;padding:0 5px;border-radius:9px;background:#C92A2B;color:#fff;font:700 11px/18px Inter,sans-serif;text-align:center}
.mk-hamb{position:relative;overflow:visible}
.mk-hamb::after{content:'2';position:absolute;top:-6px;right:-6px;min-width:17px;height:17px;border-radius:9px;background:#C92A2B;color:#fff;font:700 10px/17px Inter,sans-serif;text-align:center;box-shadow:0 0 0 2px var(--c-header-bg,#9B0000)}
.mk-mm a{display:flex!important;align-items:center;gap:8px;font-weight:700}
.mk-mm + .mk-mm{border-bottom:2px solid #E8AC07}
@media(max-width:768px){.pa-mobile-menu{display:block!important}}"""
        return wstaw(doc, css)


# ================================================================ B. KARTA W LISTINGU
def karty(doc, wariant, stany):
    if wariant == 0:
        return wstaw(doc)

    def jedna(m):
        a = m.group(0)
        lid = re.search(r'listing_id=(\d+)', a)
        lid = lid.group(1) if lid else ''
        st = 'in' if lid in IN_IDS else ('model' if stany.get(lid) == 'model' else 'add')
        if wariant == 1:   # okrągłe ikony na zdjęciu
            cls = ' is-in' if st == 'in' else ''
            tip = {'in': 'W porównaniu — kliknij, aby usunąć', 'add': 'Dodaj do porównania',
                   'model': 'Porównaj wersje tego modelu'}[st]
            ov = (f'<span class="mk-ov"><span class="mk-ob{cls}" title="{tip}">{w(18)}'
                  + (f'<span class="mk-ok">{f(9)}</span>' if st == 'in' else '') +
                  f'</span><span class="mk-ob" title="Dodaj do ulubionych">{h(18)}</span></span>')
            return a.replace('<div class="aa-card__image">', '<div class="aa-card__image">' + ov, 1)
        if wariant == 2:   # w rzędzie akcji
            txt = {'in': f'{f(12)}<span>W porównaniu</span>', 'add': f'{w(16)}<span>Porównaj</span>',
                   'model': f'{w(16)}<span>Porównaj wersje</span>'}[st]
            cls = ' is-in' if st == 'in' else ''
            btn = (f'<span class="mk-act{cls}" role="button">{txt}</span>'
                   f'<span class="mk-act mk-act--ico" role="button" aria-label="Dodaj do ulubionych">{h(16)}</span>')
            return re.sub(r'(<div class="aa-card__actions">.*?)(\s*</div>\s*</div>\s*</article>)', lambda x: x.group(1) + btn + x.group(2),
                          a, count=1, flags=re.S)
        if wariant == 3:   # przy cenie: pole wyboru + serce
            if st == 'model':
                chk = f'<span class="mk-chk mk-chk--link">{w(15)}Porównaj wersje modelu →</span>'
            else:
                chk = (f'<span class="mk-chk{" is-in" if st == "in" else ""}"><span class="mk-box">{f(11) if st == "in" else ""}</span>'
                       f'{"W porównaniu" if st == "in" else "Porównaj"}</span>')
            return re.sub(r'(<div class="aa-card__price">.*?</div>)',
                          lambda x: f'<div class="mk-prow">{x.group(1)}{chk}<span class="mk-heart" aria-label="Dodaj do ulubionych">{h(20)}</span></div>',
                          a, count=1, flags=re.S)

    doc = re.sub(r'<article class="aa-card">.*?</article>', jedna, doc, flags=re.S)
    css = {
        1: """.aa-card__image{position:relative}
.mk-ov{position:absolute;top:8px;right:8px;display:flex;gap:6px;z-index:2}
.mk-ob{position:relative;display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;
  background:rgba(255,255,255,.94);color:#1B2A4A;box-shadow:0 1px 4px rgba(0,0,0,.25)}
.mk-ob.is-in{background:#1B2A4A;color:#fff}
.mk-ok{position:absolute;right:-3px;bottom:-3px;width:16px;height:16px;border-radius:50%;background:#2F855A;color:#fff;
  display:flex;align-items:center;justify-content:center;box-shadow:0 0 0 2px #fff}""",
        2: """.mk-act{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:7px 12px;border:1px solid #CBD5E0;
  border-radius:6px;color:#1B2A4A;font:600 13px/1.4 Inter,sans-serif;white-space:nowrap;background:#fff;cursor:pointer}
.aa-card__actions .mk-act:not(.mk-act--ico){margin-left:auto}
.mk-act--ico{padding:7px 9px}
.mk-act.is-in{background:#E6F4EA;border-color:#2F855A;color:#276749}
@media(max-width:768px){.aa-card__actions .mk-act:not(.mk-act--ico){margin-left:0}.mk-act span{display:none}.mk-act{padding:8px 10px}}""",
        3: """.mk-prow{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.mk-chk{display:inline-flex;align-items:center;gap:7px;font:600 13px/1 Inter,sans-serif;color:#5C6B7F;cursor:pointer;margin-left:auto}
.mk-box{width:18px;height:18px;border:1.5px solid #A0AEC0;border-radius:4px;display:flex;align-items:center;justify-content:center;background:#fff}
.mk-chk.is-in{color:#276749}.mk-chk.is-in .mk-box{background:#2F855A;border-color:#2F855A;color:#fff}
.mk-chk--link{color:#1B2A4A;text-decoration:underline;text-underline-offset:3px}
.mk-heart{display:flex;color:#5C6B7F;cursor:pointer}
@media(max-width:768px){.mk-chk{margin-left:0}.mk-prow{gap:10px}.mk-heart{margin-left:auto}}""",
    }[wariant]
    return wstaw(doc, css)


# ================================================================ C. KARTA PRODUKTU
def produkt(doc, wariant, tylko_zawartosc=False):
    if wariant == 0:
        return doc if tylko_zawartosc else wstaw(doc)
    pb = r'(<div class="aa-single__price-box">.*?</div>)'
    if wariant == 1:
        doc = re.sub(pb, lambda x: x.group(1) + f'<div class="mk-pp"><span class="mk-pbtn" role="button">{w(17)}Porównaj</span>'
                     f'<span class="mk-pbtn" role="button">{h(17)}Zapisz</span></div>', doc, flags=re.S)
        css = """.mk-pp{display:flex;gap:8px;margin-top:10px;position:relative}
.mk-pbtn{flex:1;display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:9px 12px;border:1px solid #CBD5E0;border-radius:6px;
  background:#fff;color:#1B2A4A;font:600 14px/1.2 Inter,sans-serif;cursor:pointer;white-space:nowrap}
.mk-pbtn:hover{border-color:#1B2A4A}"""
    elif wariant == 2:
        doc = re.sub(pb, lambda x: f'<div class="mk-prow2">{x.group(1)}<div class="mk-ics"><span class="mk-ic" title="Dodaj do porównania">{w(20)}</span>'
                     f'<span class="mk-ic" title="Dodaj do ulubionych">{h(20)}</span></div></div>', doc, flags=re.S)
        css = """.mk-prow2{display:flex;align-items:center;gap:10px}.mk-prow2 .aa-single__price-box{flex:1;min-width:0}
.mk-ics{display:flex;gap:6px}
.mk-ic{display:flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:50%;border:1px solid #CBD5E0;background:#fff;color:#1B2A4A;cursor:pointer}"""
    elif wariant == 3:
        doc = re.sub(r'(<div class="aa-cta">.*?</div>)', lambda x: x.group(1) +
                     f'<div class="mk-ctx"><span>{w(16)}Dodaj do porównania</span><span>{h(16)}Zapisz w ulubionych</span></div>',
                     doc, count=1, flags=re.S)
        doc = doc.replace('<div class="aa-mobile-cta">', f'<div class="aa-mobile-cta mk-m5"><a href="#" class="mk-mi" aria-label="Porównaj">{w(20)}</a>'
                          f'<a href="#" class="mk-mi" aria-label="Ulubione">{h(20)}</a>', 1)
        css = """.mk-ctx{display:flex;justify-content:center;gap:18px;margin-top:12px;font:600 13px/1 Inter,sans-serif;color:#1B2A4A}
.mk-ctx span{display:inline-flex;align-items:center;gap:6px;text-decoration:underline;text-underline-offset:3px;cursor:pointer}
@media(max-width:768px){.aa-mobile-cta.mk-m5{grid-template-columns:44px 44px repeat(3,1fr)!important;gap:5px}
.mk-mi{display:flex;align-items:center;justify-content:center;border:1px solid #CBD5E0;border-radius:6px;color:#1B2A4A!important;background:#fff}
.aa-mobile-cta.mk-m5 .aa-mcta{font-size:12px;padding:11px 4px;gap:3px}}"""
    return doc if tylko_zawartosc else wstaw(doc, css)


PRODUKT_CSS = {}


def produkt_css(wariant):
    """CSS wariantu karty produktu bez wstawiania (do scen D/E, które używają wariantu 1 jako tła)."""
    probka = produkt('<head></head><body><div class="aa-single__price-box"></div></body>', wariant)
    m = re.search(r'<style>(.*?)</style>', probka, flags=re.S)
    return m.group(1) if m else ''


# ================================================================ D. PASEK SCHOWKA
def chip(a, x=True):
    return (f'<span class="mk-chip"><img src="{a["img"]}" alt=""><span><b>{e(a["model"])}</b><small>{e(a["wersja"])}</small></span>'
            + ('<span class="mk-x" aria-label="Usuń">×</span>' if x else '') + '</span>')


def pasek(doc, wariant, telefon):
    two = SCHOWEK[:2]
    if wariant == 1:
        body = (f'<div class="mk-bar"><div class="mk-bar__in"><span class="mk-bar__t">{w(20)}<span>Porównanie <b>2/3</b></span></span>'
                f'<span class="mk-bar__chips">{chip(two[0])}{chip(two[1])}<span class="mk-chip mk-chip--pusty">+ dodaj trzecie auto</span></span>'
                f'<span class="mk-bar__go">Porównaj →</span><span class="mk-bar__cl">Wyczyść</span><span class="mk-bar__zw" aria-label="Zwiń">˅</span></div></div>')
        css = """.mk-bar{position:fixed;left:0;right:0;bottom:0;z-index:99990;background:#1B2A4A;color:#fff;box-shadow:0 -4px 16px rgba(0,0,0,.2)}
.mk-bar__in{max-width:1200px;margin:0 auto;display:flex;align-items:center;gap:14px;padding:10px 16px;font:14px/1.2 Inter,sans-serif}
.mk-bar__t{display:flex;align-items:center;gap:8px;white-space:nowrap}
.mk-bar__chips{display:flex;gap:8px;flex:1;min-width:0}
.mk-chip{display:flex;align-items:center;gap:8px;background:#fff;color:#1B2A4A;border-radius:6px;padding:4px 8px 4px 4px;min-width:0}
.mk-chip img{width:52px;height:37px;object-fit:cover;border-radius:4px}
.mk-chip b{display:block;font-size:13px}.mk-chip small{display:block;font-size:12px;color:#5C6B7F;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:170px}
.mk-x{font-size:18px;color:#5C6B7F;padding:0 2px 0 6px;cursor:pointer}
.mk-chip--pusty{background:transparent;border:1px dashed rgba(255,255,255,.5);color:rgba(255,255,255,.8);padding:12px 14px;font-size:13px}
.mk-bar__go{background:#C92A2B;color:#fff;font-weight:700;padding:10px 18px;border-radius:6px;white-space:nowrap}
.mk-bar__cl{text-decoration:underline;opacity:.85;white-space:nowrap}.mk-bar__zw{font-size:18px;opacity:.85}
@media(max-width:768px){.mk-bar{bottom:62px}.mk-bar__in{gap:10px;padding:8px 12px}.mk-bar__chips,.mk-bar__cl{display:none}
.mk-bar__t{flex:1}.mk-bar__go{padding:9px 14px}}"""
    elif wariant == 2:
        body = (f'<div class="mk-pill"><span class="mk-pill__go">{w(20)}<span>Porównaj <b>2 auta</b></span><span>→</span></span>'
                f'<span class="mk-pill__x" aria-label="Zamknij">×</span></div>')
        css = """.mk-pill{position:fixed;right:24px;bottom:24px;z-index:99990;display:flex;align-items:center;background:#1B2A4A;color:#fff;
  border-radius:999px;box-shadow:0 6px 20px rgba(0,0,0,.28);font:15px/1 Inter,sans-serif}
.mk-pill__go{display:flex;align-items:center;gap:9px;padding:13px 8px 13px 18px}
.mk-pill__x{padding:13px 16px 13px 10px;font-size:18px;opacity:.75;border-left:1px solid rgba(255,255,255,.2);margin-left:6px}
@media(max-width:768px){.mk-pill{right:12px;bottom:74px;font-size:14px}.mk-pill__go{padding:11px 6px 11px 14px}.mk-pill__x{padding:11px 12px 11px 8px}}"""
    else:
        karty_h = ''.join(f'<span class="mk-dc"><img src="{a["img"]}" alt=""><b>{e(a["model"])}</b><small>{e(a["wersja"])}</small>'
                          f'<em>od {a["cena"]}</em><span class="mk-x">×</span></span>' for a in two)
        if telefon:
            body = f'<div class="mk-drw mk-drw--zw"><span class="mk-drw__tab">{w(18)}Porównanie <b>2/3</b><span class="mk-drw__go">Porównaj →</span><span>˄</span></span></div>'
        else:
            body = (f'<div class="mk-drw"><span class="mk-drw__tab">{w(18)}Porównanie <b>2/3</b> <span>˅</span></span>'
                    f'<div class="mk-drw__in">{karty_h}<span class="mk-dc mk-dc--pusty">+ Dodaj auto<small>kliknij „Porównaj” na dowolnej karcie</small></span>'
                    f'<span class="mk-drw__side"><span class="mk-bar__go">Porównaj →</span><span class="mk-drw__cl">Wyczyść</span></span></div></div>')
        css = """.mk-drw{position:fixed;left:0;right:0;bottom:0;z-index:99990;font:14px/1.25 Inter,sans-serif}
.mk-drw__tab{position:absolute;left:50%;transform:translateX(-50%);bottom:100%;display:flex;align-items:center;gap:8px;background:#1B2A4A;color:#fff;
  padding:9px 18px;border-radius:10px 10px 0 0;white-space:nowrap;box-shadow:0 -3px 10px rgba(0,0,0,.15)}
.mk-drw__in{background:#fff;border-top:3px solid #1B2A4A;box-shadow:0 -6px 20px rgba(0,0,0,.15);display:flex;gap:14px;justify-content:center;align-items:stretch;padding:14px 16px}
.mk-dc{position:relative;width:210px;border:1px solid #E1E4E8;border-radius:8px;padding:8px;color:#1B2A4A;display:flex;flex-direction:column;gap:2px}
.mk-dc img{width:100%;height:92px;object-fit:cover;border-radius:5px;margin-bottom:4px}
.mk-dc small{color:#5C6B7F;font-size:12px}.mk-dc em{font-style:normal;font-weight:700;color:#C92A2B;font-size:13px}
.mk-dc .mk-x{position:absolute;top:12px;right:12px;background:#fff;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:17px;line-height:1;padding:0}
.mk-dc--pusty{border:2px dashed #CBD5E0;align-items:center;justify-content:center;text-align:center;color:#5C6B7F;font-weight:600}
.mk-drw__side{display:flex;flex-direction:column;justify-content:center;gap:10px;align-items:center}
.mk-bar__go{background:#C92A2B;color:#fff;font-weight:700;padding:11px 20px;border-radius:6px;white-space:nowrap}
.mk-drw__cl{text-decoration:underline;color:#5C6B7F}
.mk-drw--zw{bottom:62px}.mk-drw--zw .mk-drw__tab{bottom:0;border-radius:10px 10px 0 0;padding:8px 8px 8px 14px}
.mk-drw__go{background:#C92A2B;border-radius:6px;padding:6px 10px;font-weight:700;margin-left:6px}"""
    return wstaw(doc, css, body)


# ================================================================ E. OKIENKO ZAMIANY
def zamiana(doc, wariant, telefon):
    lista = ''.join(f'<span class="mk-zr"><img src="{a["img"]}" alt=""><span><b>{e(a["model"])}</b><small>{e(a["wersja"])}</small></span>'
                    f'<span class="mk-zb">Zamień</span></span>' for a in SCHOWEK)
    nowa = f'<b>{NOWA["model"]} {NOWA["wersja"]}</b>'
    tresc = (f'<span class="mk-zh">Porównanie ma już 3 auta</span><span class="mk-zs">Które zamienić na {nowa}?</span>{lista}'
             f'<span class="mk-zf"><span class="mk-zc">Anuluj</span><span class="mk-zl">Otwórz obecne porównanie →</span></span>')
    base_css = produkt_css(1) + """
.mk-zr{display:flex;align-items:center;gap:10px;padding:8px;border:1px solid #E1E4E8;border-radius:8px;margin-top:8px}
.mk-zr img{width:64px;height:46px;object-fit:cover;border-radius:5px}.mk-zr > span:nth-child(2){flex:1;min-width:0}
.mk-zr b{display:block;font-size:14px;color:#1B2A4A}.mk-zr small{display:block;font-size:12px;color:#5C6B7F}
.mk-zb{border:1px solid #1B2A4A;color:#1B2A4A;border-radius:6px;padding:7px 12px;font-weight:600;font-size:13px;white-space:nowrap}
.mk-zh{display:block;font:700 18px/1.25 Inter,sans-serif;color:#1B2A4A}.mk-zs{display:block;font-size:14px;color:#2D3748;margin-top:4px}
.mk-zf{display:flex;justify-content:space-between;align-items:center;margin-top:14px;font-size:14px}
.mk-zc{padding:9px 16px;border-radius:6px;background:#EDF2F7;color:#1B2A4A;font-weight:600}.mk-zl{color:#1B2A4A;text-decoration:underline}
.mk-pp .mk-pbtn:first-child{border-color:#1B2A4A;box-shadow:0 0 0 2px rgba(27,42,74,.25)}"""
    doc = produkt(doc, 1, tylko_zawartosc=True)
    if wariant == 1:
        body = f'<div class="mk-ovl"></div><div class="mk-modal" role="dialog">{tresc}</div>'
        css = base_css + """.mk-ovl{position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:100001}
.mk-modal{position:fixed;left:50%;top:50%;transform:translate(-50%,-50%);width:min(460px,calc(100vw - 32px));background:#fff;border-radius:10px;
  padding:20px;z-index:100002;font-family:Inter,sans-serif;box-shadow:0 20px 50px rgba(0,0,0,.35)}"""
        return wstaw(doc, css, body)
    if wariant == 2:
        if telefon:
            body = f'<div class="mk-ovl"></div><div class="mk-sheet" role="dialog"><span class="mk-grip"></span>{tresc}</div>'
            css = base_css + """.mk-ovl{position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:100001}
.mk-sheet{position:fixed;left:0;right:0;bottom:0;background:#fff;border-radius:16px 16px 0 0;padding:10px 16px 18px;z-index:100002;font-family:Inter,sans-serif;box-shadow:0 -10px 30px rgba(0,0,0,.25)}
.mk-grip{display:block;width:40px;height:4px;border-radius:2px;background:#CBD5E0;margin:0 auto 12px}"""
            return wstaw(doc, css, body)
        # desktop: dymek pod przyciskiem „Porównaj” w bocznej kolumnie
        doc = doc.replace('<div class="mk-pp">', f'<div class="mk-pp"><div class="mk-pop" role="dialog">{tresc}</div>', 1)
        css = base_css + """.mk-pop{position:absolute;top:calc(100% + 10px);left:0;width:420px;background:#fff;border:1px solid #CBD5E0;border-radius:10px;padding:16px;
  z-index:50;box-shadow:0 14px 34px rgba(0,0,0,.22);font-family:Inter,sans-serif}
.mk-pop::before{content:'';position:absolute;top:-7px;left:40px;width:12px;height:12px;background:#fff;border-left:1px solid #CBD5E0;border-top:1px solid #CBD5E0;transform:rotate(45deg)}
.aa-single__sidebar{overflow:visible!important}"""
        return wstaw(doc, css, body='')
    # wariant 3: pasek schowka w trybie zamiany
    chips = ''.join(f'<span class="mk-zchip"><img src="{a["img"]}" alt=""><span><b>{e(a["model"])}</b><small>{e(a["wersja"])}</small></span>'
                    f'<span class="mk-zb">⇄ Zamień</span></span>' for a in SCHOWEK)
    body = (f'<div class="mk-zbar"><div class="mk-zbar__in"><span class="mk-zbar__t">{w(20)}<span>Porównanie jest pełne (3/3). '
            f'Wybierz auto do zamiany na {nowa}:</span></span><span class="mk-zbar__chips">{chips}</span>'
            f'<span class="mk-zc">Anuluj</span></div></div>')
    css = base_css + """.mk-zbar{position:fixed;left:0;right:0;bottom:0;z-index:99990;background:#FFF8E1;border-top:3px solid #E8AC07;box-shadow:0 -6px 18px rgba(0,0,0,.18);font:14px/1.25 Inter,sans-serif;color:#1B2A4A}
.mk-zbar__in{max-width:1200px;margin:0 auto;display:flex;align-items:center;gap:12px;padding:10px 16px}
.mk-zbar__t{display:flex;align-items:center;gap:8px;max-width:260px}
.mk-zbar__chips{display:flex;gap:8px;flex:1}
.mk-zchip{display:flex;align-items:center;gap:8px;background:#fff;border:1px solid #E1E4E8;border-radius:6px;padding:4px 6px 4px 4px;flex:1;min-width:0}
.mk-zchip img{width:52px;height:37px;object-fit:cover;border-radius:4px}.mk-zchip > span:nth-child(2){flex:1;min-width:0}
.mk-zchip b{display:block;font-size:13px}.mk-zchip small{display:block;font-size:11px;color:#5C6B7F;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
@media(max-width:768px){.mk-zbar{bottom:62px}.mk-zbar__in{flex-direction:column;align-items:stretch;gap:6px;padding:10px 12px}
.mk-zbar__t{max-width:none}.mk-zbar__chips{flex-direction:column;gap:6px}.mk-zbar .mk-zc{text-align:center}}"""
    return wstaw(doc, css, body)


# ================================================================ SCENY
def main():
    OUT.mkdir(parents=True, exist_ok=True)
    for n in ('Regular', 'SemiBold', 'Bold'):
        shutil.copy(FONTS / f'Inter-{n}.woff2', OUT)
    kat = czysc(pobierz('https://primaauto.com.pl/samochody/', 'kat-2026-09-17.html'))
    prod = czysc(pobierz('https://primaauto.com.pl/oferta/zeekr-9x-2026-390631/', 'prod-390631-2026-09-17.html'))
    stany = spec_stany(sorted(set(re.findall(r'listing_id=(\d+)', kat))))

    def plik(nazwa, tresc):
        (OUT / nazwa).write_text(tresc, encoding='utf-8')
        return nazwa

    # (klucz, tytuł, opis sceny, [(wariant, nazwa, opis, plik_desktop, plik_tel)], parametry ramek)
    sceny = []
    A = [('Stan obecny', 'Nagłówek bez wejścia do porównania i ulubionych.'),
         ('A1 · Ikony w białej pigułce', 'Waga z licznikiem i serce dołączone do pigułki telefon/WhatsApp. Jeden element, ten sam na desktopie i telefonie. Uwaga: na telefonie pigułka się wydłuża i ściska logo.'),
         ('A2 · Osobne białe ikony', 'Waga i serce jako białe ikony na czerwonym pasku, przed pigułką. Pigułka zostaje „kontaktowa”. Na telefonie logo też się zmniejsza, ale mniej niż w A1.'),
         ('A3 · Etykiety na desktopie, menu na telefonie', 'Desktop: „Porównanie 2” i „Ulubione” z tekstem — przy 1366 px menu łamie się już w dwie linie (a dojdą jeszcze „Wyszukiwarka” i „Porównywarka”). Telefon: bez nowych ikon w pasku — licznik na hamburgerze, pozycje na górze menu (pokazane otwarte).')]
    sceny.append(('A', 'Nagłówek — wejście do porównania i ulubionych', 'Schowek: 2 auta. Serce = miejsce zarezerwowane dla T-114 (dziś bez działania).',
                  [(i, t, d, plik(f'A{i}.html', naglowek(kat, i) if i else wstaw(kat))) for i, (t, d) in enumerate(A)],
                  dict(d_cel='', d_h=150, t_cel='', t_h=[140, 140, 140, 560])))
    B = [('Stan obecny', 'Karta z renderCard() bez przycisku.'),
         ('B1 · Ikony na zdjęciu', 'Okrągłe przyciski w prawym górnym rogu zdjęcia. W porównaniu: granatowa waga z zielonym ✓. Nie zabiera miejsca w treści karty.'),
         ('B2 · W rzędzie akcji', 'Obok „Szczegóły” / „Zamów”: „Porównaj” (zielone „W porównaniu” po dodaniu) + serce. Na telefonie same ikony.'),
         ('B3 · Pole wyboru przy cenie', 'Klasyczne „☐ Porównaj” jak w sklepach, serce po prawej. Oferta bez wersji: link „Porównaj wersje modelu →”.')]
    sceny.append(('B', 'Karta w listingu (katalog, wyszukiwarka, huby)',
                  'Prawdziwa góra /samochody/ z 17.09, w kolejności: Li Auto L9 — do dodania; dwie oferty Zeekr 8X (ta sama wersja) — w porównaniu; AITO M9 — do dodania; Li Auto L8 — bez wiersza wersji → przycisk do modelu; Xiaomi SU7 Ultra — w porównaniu.',
                  [(i, t, d, plik(f'B{i}.html', karty(kat, i, stany))) for i, (t, d) in enumerate(B)],
                  dict(d_cel='.aa-inv__grid', d_off=100, d_h=1150, t_cel='.aa-inv__grid', t_off=125, t_h=[2000] * 4)))
    C = [('Stan obecny', 'Cena, a przyciski kontaktu w bocznej kolumnie / dolnym pasku.'),
         ('C1 · Dwa przyciski pod ceną', '„Porównaj” i „Zapisz” na pełną szerokość pod ceną (desktop: boczna kolumna, telefon: nagłówek oferty). Dolny pasek bez zmian.'),
         ('C2 · Ikony obok ceny', 'Dwie okrągłe ikony w jednym rzędzie z ceną. Najmniej miejsca, mniej czytelne dla nowego klienta.'),
         ('C3 · Przy przyciskach kontaktu', 'Desktop: linki pod Zamów/Zadzwoń/WhatsApp. Telefon: waga i serce jako dwie wąskie ikony w dolnym pasku (kontakt się zwęża).')]
    sceny.append(('C', 'Karta produktu', 'Zeekr 9X 2026 Ultra 70kWh (oferta 390631), wersja jeszcze nie w porównaniu.',
                  [(i, t, d, plik(f'C{i}.html', produkt(prod, i))) for i, (t, d) in enumerate(C)],
                  dict(d_cel='', d_h=820, t_cel='', t_h=[844] * 4)))
    D = [('B/C · bez paska', 'Dziś nic nie informuje, że auto trafiło do porównania.'),
         ('D1 · Pełny pasek na dole', 'Granatowy pasek z miniaturami, „+ dodaj trzecie”, Porównaj, Wyczyść, zwiń. Telefon: skrót „Porównanie 2/3 · Porównaj →” nad dolnym paskiem kontaktu.'),
         ('D2 · Pływająca pigułka', 'Tylko „Porównaj 2 auta →” w prawym dolnym rogu + zamknięcie. Najmniej zasłania.'),
         ('D3 · Szuflada z kartami', 'Desktop: rozwinięta szuflada z kartami aut i ceną „od”. Telefon: zwinięta zakładka nad dolnym paskiem, rozwija się po dotknięciu.')]
    sceny.append(('D', 'Pasek schowka po dodaniu', 'Desktop: katalog; telefon: karta produktu (zderzenie z dolnym paskiem kontaktu). Schowek: Zeekr 8X PHEV + Xiaomi SU7 Ultra.',
                  [(i, t, d, plik(f'D{i}-d.html', pasek(kat, i, False) if i else wstaw(kat)), plik(f'D{i}-t.html', pasek(prod, i, True) if i else wstaw(prod)))
                   for i, (t, d) in enumerate(D)],
                  dict(d_cel='.aa-inv__grid', d_off=100, d_h=760, t_cel='', t_h=[844] * 4)))
    E = [('E1 · Okienko na środku', 'Klasyczne okno z trzema autami i „Zamień”. Tak samo na desktopie i telefonie.'),
         ('E2 · Dymek (desktop) / arkusz od dołu (telefon)', 'Desktop: dymek przy przycisku, bez zaciemnienia. Telefon: arkusz od dołu — ten sam wzorzec co filtry wyszukiwarki (T-252).'),
         ('E3 · Pasek schowka w trybie zamiany', 'Bez okna: pasek schowka zmienia kolor i przy każdym aucie pojawia się „⇄ Zamień”.')]
    sceny.append(('E', 'Pełny schowek — okienko zamiany', 'Schowek: Zeekr 8X PHEV, Xiaomi SU7 Ultra, AITO M9. Klient klika „Porównaj” przy Zeekr 9X Ultra (tło: przycisk z C1).',
                  [(i + 1, t, d, plik(f'E{i + 1}-d.html', zamiana(prod, i + 1, False)), plik(f'E{i + 1}-t.html', zamiana(prod, i + 1, True)))
                   for i, (t, d) in enumerate(E)],
                  dict(d_cel='', d_h=820, t_cel='', t_h=[844] * 3)))
    Z = [('1 · Katalog po dodaniu dwóch aut', 'Nagłówek A2 (waga z licznikiem 2), ikony B1 na zdjęciach: Zeekr 8X ×2 i Xiaomi SU7 Ultra w porównaniu, Li Auto L8 → do modelu. Pasek D1 z X w kółku (zamyka pasek; porównanie dalej pod wagą w nagłówku). Na telefonie pasek w wersji skróconej — dotknięcie rozwija listę aut.'),
         ('2 · Karta produktu, wersja jeszcze nie w porównaniu', 'Waga i serce na głównym zdjęciu galerii, jak na kartach listingu (ikona pełnego ekranu przeniesiona na dół). Na telefonie pasek schowka nad dolnym paskiem kontaktu.'),
         ('3 · Czwarte auto — komunikat i rozwinięty pasek', 'Zamiast okna: komunikat „maksymalnie 3 auta” i pasek z wyraźnym × przy każdym aucie (na telefonie rozwinięty w listę). Po usunięciu jednego klikane auto wchodzi samo na zwolnione miejsce.')]
    K = [('Obecny moduł + A2', 'Pigułka telefon/WhatsApp i żółty przycisk menu jak dziś, obok waga i serce z A2. Na telefonie logo się ściska.'),
         ('K1 · Jeden rząd ikon, bez pigułki', 'Waga, serce | telefon, WhatsApp — wszystkie białe na pasku, kreska oddziela kontakt. Menu na telefonie: same białe kreski, bez żółtego tła. Najwęższy, ale kontakt i menu przestają się wyróżniać.'),
         ('K2 · Przycisk „Kontakt” z listą', 'Jeden biały przycisk „Kontakt ▾” (na telefonie sama słuchawka) rozwija: Zadzwoń z numerem / WhatsApp. Pokazane rozwinięte na desktopie. Menu na telefonie: białe kreski w obrysie. Kosztuje klik więcej do kontaktu.'),
         ('K3 · Kolorowe kółka', 'Waga i serce białe, telefon w białym kółku, WhatsApp w zielonym. Kontakt nadal wyróżniony, a moduł węższy o ramę pigułki. Menu na telefonie: żółte kółko w rozmiarze pozostałych ikon.')]
    K += [('K4 · Kontakt w doku na dole telefonu', 'Na telefonie nagłówek ma tylko logo, wagę, serce i menu, a „Zadzwoń” i „WhatsApp” są stale pod kciukiem na dole ekranu — ten sam wzór co dolny pasek na karcie oferty, więc na całym serwisie kontakt jest w jednym miejscu. Desktop: kolorowe kółka jak K3. Koszt: dok zasłania 64 px treści, pasek schowka staje nad nim.'),
          ('K5 · Stały pasek kontaktu nad nagłówkiem', 'Granatowy pasek (dziś „Nowość”) zostaje na stałe: na desktopie nowość po lewej, numer 721 730 507 i zielony WhatsApp po prawej; na telefonie dwa pola na całą szerokość. Czerwony nagłówek zostaje dla logo, wagi, serca i menu. Koszt: na telefonie znika miejsce na komunikat „Nowość”.'),
          ('K6 · Ikony z podpisami', 'Porównaj · Ulubione · Zadzwoń · WhatsApp · Menu — każda ikona z krótkim podpisem, kontakt w kolorowych kółkach. Nikt nie musi zgadywać, co znaczy waga. Koszt: najciaśniej na 390 px, logo mniejsze.')]
    sceny.insert(0, ('K', 'Moduł kontaktu i menu w nagłówku (razem z A2)', 'Pigułka telefon/WhatsApp i przycisk menu na telefonie przeprojektowane tak, żeby razem z wagą i sercem zajmowała mniej miejsca. Schowek: 2 auta.',
                     [(i, t, d, plik(f'K{i}.html', kontakt(kat, i) if i < 4 else kontakt2(kat, i))) for i, (t, d) in enumerate(K)],
                     dict(d_cel='', d_h=[150, 150, 280, 150, 150, 150, 150], t_cel='', t_h=[140, 140, 140, 140, 844, 140, 140])))
    sceny.insert(0, ('W', 'Wybrany zestaw (A2 · B1 · C na zdjęciu · D1 · E komunikat)', 'Złożone razem na prawdziwych stronach.',
                     [(0, Z[0][0], Z[0][1], plik('W1-d.html', zestaw_katalog(kat, stany)), plik('W1-t.html', zestaw_katalog(kat, stany))),
                      (1, Z[1][0], Z[1][1], plik('W2.html', zestaw_produkt(prod, False)), 'W2.html'),
                      (2, Z[2][0], Z[2][1], plik('W3.html', zestaw_produkt(prod, True)), 'W3.html')],
                     dict(d_cel='.aa-inv__grid', d_off=100, d_h=820, t_cel='', t_h=[844] * 3, d_cel_l=['.aa-inv__grid', '', ''])))
    for sc in sceny:
        (DROP / f'primaauto-makieta-porownaj-{sc[0]}-2026-09-17.html').write_text(strona(sceny, sc[0]), encoding='utf-8')
    INDEX.write_text(strona(sceny, 'W'), encoding='utf-8')
    print('OK', len(sceny), 'stron,', len(list(OUT.iterdir())), 'plików w', OUT)


# ================================================================ ZESTAW WYBRANY (17.09): A2 + B1 + C4 + D1' + E4
ZAMKNIJ = ('<span class="mk-xc" role="button" aria-label="Zamknij pasek"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" '
           'stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/>'
           '<path d="m15 9-6 6M9 9l6 6"/></svg></span>')
OV_CSS = """.mk-ov{position:absolute;top:8px;right:8px;display:flex;gap:6px;z-index:5}
.mk-ob{position:relative;display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;
  background:rgba(255,255,255,.94);color:#1B2A4A;box-shadow:0 1px 4px rgba(0,0,0,.25)}
.mk-ob.is-in{background:#1B2A4A;color:#fff}
.mk-ob.is-act{box-shadow:0 0 0 3px #E8AC07,0 1px 4px rgba(0,0,0,.25)}
.mk-ok{position:absolute;right:-3px;bottom:-3px;width:16px;height:16px;border-radius:50%;background:#2F855A;color:#fff;
  display:flex;align-items:center;justify-content:center;box-shadow:0 0 0 2px #fff}
.aa-gallery__main .mk-ov{top:12px;right:12px;gap:8px}.aa-gallery__main .mk-ob{width:42px;height:42px}
.aa-gallery .aa-gallery__fullscreen{top:auto!important;bottom:12px!important;opacity:1!important}"""
BAR_CSS = """.mk-bar{position:fixed;left:0;right:0;bottom:0;z-index:99990;background:#1B2A4A;color:#fff;box-shadow:0 -4px 16px rgba(0,0,0,.2);font-family:Inter,sans-serif}
body:has(.aa-mobile-cta) .mk-bar{bottom:0}
.mk-bar__in{max-width:1200px;margin:0 auto;display:flex;align-items:center;gap:14px;padding:10px 16px;font-size:14px;line-height:1.2}
.mk-bar__t{display:flex;align-items:center;gap:8px;white-space:nowrap}
.mk-bar__chips{display:flex;gap:8px;flex:1;min-width:0}
.mk-chip{display:flex;align-items:center;gap:8px;background:#fff;color:#1B2A4A;border-radius:6px;padding:4px 6px 4px 4px;min-width:0}
.mk-chip img{width:52px;height:37px;object-fit:cover;border-radius:4px}
.mk-chip b{display:block;font-size:13px}.mk-chip small{display:block;font-size:12px;color:#5C6B7F;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:170px}
.mk-x{display:flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;background:#EDF2F7;color:#1B2A4A;font-size:17px;line-height:1;margin-left:4px;cursor:pointer;flex-shrink:0}
.mk-chip--pusty{background:transparent;border:1px dashed rgba(255,255,255,.5);color:rgba(255,255,255,.8);padding:12px 14px;font-size:13px}
.mk-bar__go{background:#C92A2B;color:#fff;font-weight:700;padding:10px 18px;border-radius:6px;white-space:nowrap}
.mk-bar__cl{text-decoration:underline;opacity:.85;white-space:nowrap}
.mk-xc{display:flex;color:#fff;opacity:.9;cursor:pointer}
.mk-bar__rows{display:none}
@media(max-width:768px){
  body:has(.aa-mobile-cta) .mk-bar{bottom:62px}
  .mk-bar__in{gap:10px;padding:8px 12px}.mk-bar__chips,.mk-bar__cl{display:none}.mk-bar__t{flex:1}.mk-bar__go{padding:9px 14px}}
/* pełny schowek: komunikat + rozwinięty pasek */
.mk-bar.is-full{border-top:3px solid #E8AC07}
.mk-msg{background:#FFF8E1;color:#1B2A4A;border-bottom:1px solid #F6E05E}
.mk-msg__in{max-width:1200px;margin:0 auto;padding:9px 16px;font-size:14px;display:flex;gap:8px;align-items:center}
.mk-msg__in b{white-space:nowrap}
.mk-bar.is-full .mk-x{background:#FED7D7;color:#C53030;width:30px;height:30px;font-size:20px}
@media(max-width:768px){
  .mk-msg__in{padding:9px 12px;font-size:13px;align-items:flex-start}
  .mk-bar.is-full .mk-bar__rows{display:flex;flex-direction:column;gap:6px;padding:8px 12px 0}
  .mk-bar.is-full .mk-chip{padding:4px 6px 4px 4px}.mk-bar.is-full .mk-chip > span:nth-child(2){flex:1;min-width:0}
  .mk-bar.is-full .mk-chip small{max-width:none}}"""


def ov(stan, duze=False):
    s = 20 if duze else 18
    cls = {'in': ' is-in', 'act': ' is-act', 'add': '', 'model': ''}[stan]
    return (f'<span class="mk-ov"><span class="mk-ob{cls}">{w(s)}' + (f'<span class="mk-ok">{f(9)}</span>' if stan == 'in' else '') +
            f'</span><span class="mk-ob">{h(s)}</span></span>')


def bar(auta, pelny=False, nowa=''):
    chips = ''.join(f'<span class="mk-chip"><img src="{a["img"]}" alt=""><span><b>{e(a["model"])}</b><small>{e(a["wersja"])}</small></span>'
                    f'<span class="mk-x" role="button" aria-label="Usuń z porównania">×</span></span>' for a in auta)
    pusty = '' if len(auta) >= 3 else '<span class="mk-chip mk-chip--pusty">+ dodaj trzecie auto</span>'
    msg = (f'<div class="mk-msg"><div class="mk-msg__in">{w(18)}<span><b>Możesz porównać maksymalnie 3 auta.</b> '
           f'Usuń jedno z paska — {e(nowa)} wejdzie na jego miejsce.</span></div></div>') if pelny else ''
    return (f'<div class="mk-bar{" is-full" if pelny else ""}">{msg}<div class="mk-bar__rows">{chips}</div>'
            f'<div class="mk-bar__in"><span class="mk-bar__t">{w(20)}<span>Porównanie <b>{len(auta)}/3</b></span></span>'
            f'<span class="mk-bar__chips">{chips}{pusty}</span><span class="mk-bar__go">Porównaj →</span>'
            f'<span class="mk-bar__cl">Wyczyść</span>{ZAMKNIJ}</div></div>')


def kontakt(doc, wariant):
    """K — przeprojektowanie modułu kontaktu razem z ikonami A2 (wariant 0 = A2 bez zmian)."""
    if wariant == 0:
        return naglowek(doc, 2, 2)
    i = doc.find('<div class="pa-header__contact">')
    pill = doc[i:doc.find('</div>', i) + 6]
    svg = re.findall(r'<svg.*?</svg>', pill, flags=re.S)
    tel, wa = svg[0], svg[1]
    mk = lambda x, n: re.sub(r'width="18" height="18"', f'width="{n}" height="{n}"', x, count=1)
    ikony = (f'<a href="#" class="mk-u" aria-label="Porównanie: 2 auta">{w(21)}<span class="mk-badge">2</span></a>'
             f'<a href="#" class="mk-u" aria-label="Ulubione">{h(21)}</a>')
    if wariant == 1:
        modul = (f'<div class="mk-k mk-k1">{ikony}<span class="mk-sep"></span>'
                 f'<a href="#" class="mk-u" aria-label="Zadzwoń">{mk(tel, 20)}</a><a href="#" class="mk-u" aria-label="WhatsApp">{mk(wa, 20)}</a></div>')
        css = ".mk-sep{width:1px;height:22px;background:rgba(255,255,255,.35);margin:0 4px}\n.pa-hamburger{background:transparent!important;width:34px!important;height:34px!important;padding:8px 6px!important}\n.pa-hamburger span{background:#fff!important}"
    elif wariant == 2:
        modul = (f'<div class="mk-k mk-k2">{ikony}<span class="mk-kt"><span class="mk-ktb">{mk(tel, 16)}<span class="mk-ktl">Kontakt</span>'
                 f'<span class="mk-ktl">▾</span></span><span class="mk-dd"><a href="#">{mk(tel, 18)}<span><b>Zadzwoń</b><small>721 730 507</small></span></a>'
                 f'<a href="#">{mk(wa, 18)}<span><b>WhatsApp</b><small>napisz wiadomość</small></span></a></span></span></div>')
        css = """.mk-kt{position:relative}
.mk-ktb{display:flex;align-items:center;gap:6px;background:#fff;color:#9B0000;border-radius:999px;padding:7px 12px;font:700 14px/1 Inter,sans-serif}
.mk-dd{position:absolute;right:0;top:calc(100% + 8px);background:#fff;border-radius:8px;box-shadow:0 10px 28px rgba(0,0,0,.25);padding:6px;min-width:250px;z-index:50}
.pa-header .mk-dd a{display:flex;gap:10px;align-items:center;padding:9px 10px;border-radius:6px;color:#1B2A4A}
.pa-header .mk-dd a:hover{background:#F5F6F8}.mk-dd b{display:block;font-size:14px}.mk-dd small{display:block;font-size:12px;color:#5C6B7F}
.pa-header__inner--mobile .mk-dd{display:none}
@media(max-width:768px){.mk-ktl{display:none}.mk-ktb{padding:8px}}
.pa-hamburger{background:transparent!important;border:1.5px solid rgba(255,255,255,.7)!important;width:34px!important;height:34px!important;padding:9px 7px!important;border-radius:8px!important}
.pa-hamburger span{background:#fff!important}"""
    else:
        modul = (f'<div class="mk-k mk-k3">{ikony}<a href="#" class="mk-c mk-c--tel" aria-label="Zadzwoń">{mk(tel, 17)}</a>'
                 f'<a href="#" class="mk-c mk-c--wa" aria-label="WhatsApp">{mk(wa, 17)}</a></div>')
        css = """.mk-k3 .mk-c{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;margin-left:2px}
.pa-header a.mk-c--tel{background:#fff;color:#9B0000}.pa-header a.mk-c--wa{background:#25D366;color:#fff}
.pa-hamburger{width:34px!important;height:34px!important;padding:9px 8px!important;border-radius:50%!important}"""
    doc = doc.replace(pill, modul)
    css += """
.mk-k{display:flex;align-items:center;gap:2px;flex-shrink:0}
.pa-header a.mk-u{position:relative;display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;color:#fff}
.pa-header a.mk-u:hover{background:rgba(255,255,255,.12)}
.mk-u .mk-badge{top:1px;right:-1px;box-shadow:0 0 0 2px var(--c-header-bg,#9B0000)}
@media(max-width:768px){.pa-header__inner--mobile .mk-k{margin-left:auto}.pa-header a.mk-u{width:34px}}"""
    return wstaw(doc, css)


def kontakt2(doc, wariant):
    """K4–K6 (po GA4 90 dni: telefon 257 / WhatsApp 249 kliknięć na telefonach — oba kanały równorzędne i bez dodatkowego kliku)."""
    i = doc.find('<div class="pa-header__contact">')
    pill = doc[i:doc.find('</div>', i) + 6]
    svg = re.findall(r'<svg.*?</svg>', pill, flags=re.S)
    tel, wa = svg[0], svg[1]
    mk = lambda x, n: re.sub(r'width="18" height="18"', f'width="{n}" height="{n}"', x, count=1)
    ikony = (f'<a href="#" class="mk-u" aria-label="Porównanie: 2 auta">{w(21)}<span class="mk-badge">2</span></a>'
             f'<a href="#" class="mk-u" aria-label="Ulubione">{h(21)}</a>')
    kolka = (f'<a href="#" class="mk-c mk-c--tel" aria-label="Zadzwoń">{mk(tel, 17)}</a>'
             f'<a href="#" class="mk-c mk-c--wa" aria-label="WhatsApp">{mk(wa, 17)}</a>')
    j = doc.find(pill, i + len(pill))          # druga pigułka = nagłówek mobilny
    zamien = lambda d, desk, mob: d[:i] + desk + d[i + len(pill):j] + mob + d[j + len(pill):]
    css = """
.mk-k{display:flex;align-items:center;gap:2px;flex-shrink:0}
.pa-header a.mk-u{position:relative;display:flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:8px;color:#fff}
.mk-u .mk-badge{top:2px;right:0;box-shadow:0 0 0 2px var(--c-header-bg,#9B0000)}
.mk-k .mk-c{display:flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:50%;margin-left:4px}
.pa-header a.mk-c--tel{background:#fff;color:#9B0000}.pa-header a.mk-c--wa{background:#25D366;color:#fff}
.pa-hamburger{width:40px!important;height:40px!important;padding:12px 10px!important;border-radius:50%!important}
@media(max-width:768px){.pa-header__inner--mobile .mk-k{margin-left:auto}}"""
    body = ''
    if wariant == 4:     # dok kontaktu na dole telefonu
        doc = zamien(doc, f'<div class="mk-k">{ikony}{kolka}</div>', f'<div class="mk-k">{ikony}</div>')
        body = (f'<nav class="mk-dock" aria-label="Kontakt"><a href="#" class="mk-dock__tel">{mk(tel, 18)}Zadzwoń</a>'
                f'<a href="#" class="mk-dock__wa">{mk(wa, 18)}WhatsApp</a></nav>')
        css += """
.mk-dock{display:none}
@media(max-width:768px){
 .mk-dock{display:grid;grid-template-columns:1fr 1fr;gap:8px;position:fixed;left:0;right:0;bottom:0;z-index:9999;background:#fff;
   padding:8px 10px calc(8px + env(safe-area-inset-bottom,0px));border-top:1px solid #E1E4E8;box-shadow:0 -2px 12px rgba(0,0,0,.1)}
 .mk-dock a{display:flex;align-items:center;justify-content:center;gap:7px;height:44px;border-radius:6px;font:700 15px/1 Inter,sans-serif;color:#fff}
 .mk-dock__tel{background:#1B2A4A}.mk-dock__wa{background:#25D366}
 body{padding-bottom:64px}}"""
    elif wariant == 5:   # stały pasek kontaktu nad nagłówkiem
        doc = zamien(doc, f'<div class="mk-k">{ikony}</div>', f'<div class="mk-k">{ikony}</div>')
        n0 = doc.find('<div class="pa-news"')
        n1 = doc.find('</div>', doc.find('</div>', n0) + 6) + 6
        news = doc[n0:n1]
        tag = re.search(r'<span class="pa-news__tag">.*?</span>\s*<span class="pa-news__txt">.*?</span>\s*<a class="pa-news__cta".*?</a>', news, flags=re.S)
        top = (f'<div class="mk-top"><div class="mk-top__in"><span class="mk-top__news">{tag.group(0) if tag else ""}</span>'
               f'<span class="mk-top__k"><a href="#" class="mk-top__tel">{mk(tel, 15)}<span class="mk-dl">721 730 507</span><span class="mk-ml">Zadzwoń</span></a>'
               f'<a href="#" class="mk-top__wa">{mk(wa, 15)}WhatsApp</a></span></div></div>')
        doc = doc[:n0] + top + doc[n1:]
        css += """
.mk-top{background:#1B2A4A;color:#fff;font:14px/1 Inter,sans-serif}
.mk-top__in{max-width:1400px;margin:0 auto;height:36px;display:flex;align-items:center;justify-content:space-between;padding:0 20px}
.mk-top__news{display:flex;align-items:center;gap:8px}
.mk-top__k{display:flex;align-items:stretch;height:100%}
.mk-top a{display:flex;align-items:center;gap:7px;color:#fff;padding:0 14px;font-weight:600}
.mk-top__tel .mk-ml{display:none}.mk-top__wa{background:#25D366}
.mk-top__tel{border-left:1px solid rgba(255,255,255,.2)}
@media(max-width:768px){
 .mk-top__in{padding:0;height:40px}.mk-top__news,.mk-dl{display:none}.mk-top__tel .mk-ml{display:inline}
 .mk-top__k{width:100%}.mk-top a{flex:1;justify-content:center;font-size:15px;border:0}}"""
    else:                # 6: ikony z podpisami
        def lab(ikona, t, kl=''):
            return f'<a href="#" class="mk-l {kl}"><span class="mk-l__i">{ikona}</span><span class="mk-l__t">{t}</span></a>'
        por = lab(f'{w(20)}<span class="mk-badge">2</span>', 'Porównaj')
        ul = lab(h(20), 'Ulubione')
        tl = lab(mk(tel, 16), 'Zadzwoń', 'mk-l--tel')
        wl = lab(mk(wa, 16), 'WhatsApp', 'mk-l--wa')
        menu = ('<button type="button" class="mk-l mk-l--menu" aria-label="Otwórz menu"><span class="mk-l__i">'
                '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true">'
                '<path d="M4 7h16M4 12h16M4 17h16"/></svg></span><span class="mk-l__t">Menu</span></button>')
        doc = zamien(doc, f'<div class="mk-k mk-k6">{por}{ul}{tl}{wl}</div>', f'<div class="mk-k mk-k6">{por}{ul}{tl}{wl}{menu}</div>')
        css += """
.pa-header__inner--mobile .pa-hamburger{display:none!important}
.mk-k6{gap:0}
.pa-header .mk-l{display:flex;flex-direction:column;align-items:center;gap:4px;width:62px;color:#fff;background:none;border:0;padding:0;cursor:pointer;font-family:Inter,sans-serif}
.mk-l__i{position:relative;display:flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%}
.mk-l__t{font-size:11px;font-weight:600;line-height:1;white-space:nowrap;letter-spacing:-.1px}
.mk-l .mk-badge{top:-4px;right:-7px;box-shadow:0 0 0 2px var(--c-header-bg,#9B0000)}
.mk-l--tel .mk-l__i{background:#fff;color:#9B0000}.mk-l--wa .mk-l__i{background:#25D366;color:#fff}
.mk-l--menu .mk-l__i{background:#E8AC07;color:#1B2A4A}
@media(max-width:768px){.pa-header .mk-l{width:auto;padding:0 3px}.mk-l__t{font-size:10px;letter-spacing:-.2px}.mk-k6{gap:2px}
.pa-header__inner--mobile{padding-inline:10px!important;gap:4px!important}.pa-header__inner--mobile .pa-logo__main{font-size:16px}.pa-header__inner--mobile .pa-logo__sub{font-size:9px}}"""
    return wstaw(doc, css, body)


def bez_js(doc):
    return doc.replace(POMOCNIK_JS, '')


def zestaw_katalog(kat, stany):
    doc = bez_js(naglowek(kat, 2, 2))
    def jedna(m):
        a = m.group(0)
        lid = re.search(r'listing_id=(\d+)', a)
        lid = lid.group(1) if lid else ''
        st = 'in' if lid in IN_IDS else ('model' if stany.get(lid) == 'model' else 'add')
        return a.replace('<div class="aa-card__image">', '<div class="aa-card__image">' + ov(st), 1)
    doc = re.sub(r'<article class="aa-card">.*?</article>', jedna, doc, flags=re.S)
    return wstaw(doc, OV_CSS + BAR_CSS + '.aa-card__image{position:relative}', bar(SCHOWEK[:2]))


def zestaw_produkt(prod, pelny):
    doc = bez_js(naglowek(prod, 2, 3 if pelny else 2))
    doc = doc.replace('<div class="aa-gallery__main">', '<div class="aa-gallery__main">' + ov('act' if pelny else 'add', True), 1)
    auta = SCHOWEK if pelny else SCHOWEK[:2]
    return wstaw(doc, OV_CSS + BAR_CSS, bar(auta, pelny, f'{NOWA["model"]} {NOWA["wersja"]}'))


def ramka(src, szer, wys, skala, cel='', off=0):
    q = f'?cel={urllib.parse.quote(cel)}&off={off}' if cel else ''
    return (f'<div class="fr" style="width:{round(szer * skala)}px;height:{round(wys * skala)}px">'
            f'<iframe loading="lazy" src="{SUB}/{src}{q}" style="width:{szer}px;height:{wys}px;transform:scale({skala})"></iframe></div>'
            f'<a class="full" href="{SUB}/{src}{q}" target="_blank">otwórz osobno ↗</a>')


def strona(sceny, akt):
    nav = ''.join((f'<b>{k}. {e(t)}</b>' if k == akt else f'<a href="primaauto-makieta-porownaj-{k}-2026-09-17.html">{k}. {e(t)}</a>') for k, t, *_ in sceny)
    out = []
    for k, tyt, opis, warianty, p in [x for x in sceny if x[0] == akt]:
        rows = []
        for idx, row in enumerate(warianty):
            i, t, d = row[:3]
            pd, pt = (row[3], row[3]) if len(row) == 4 else (row[3], row[4])
            th = p['t_h'][idx]
            rows.append(f'<div class="war"><h3>{e(t)}</h3><p>{e(d)}</p><div class="pair">'
                        f'<div><span class="lab">Desktop 1366 px (pomniejszone)</span>{ramka(pd, 1366, p["d_h"][idx] if isinstance(p["d_h"], list) else p["d_h"], .62, p.get("d_cel_l", [p["d_cel"]] * 9)[idx], p.get("d_off", 0))}</div>'
                        f'<div><span class="lab">Telefon 390 px</span>{ramka(pt, 390, th, 1 if th <= 900 else .6, p["t_cel"], p.get("t_off", 0))}</div>'
                        f'</div></div>')
        out.append(f'<section id="s{k}"><h2>{k}. {e(tyt)}</h2><p class="op">{e(opis)}</p>{"".join(rows)}</section>')
    return f"""<!doctype html><html lang="pl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex"><title>Przycisk Porównaj — makiety</title>
<style>
:root{{--bg:#F5F6F8;--fg:#1B2A4A;--mut:#5C6B7F;--bd:#E1E4E8;--card:#fff}}
@media (prefers-color-scheme:dark){{:root:not([data-theme="light"]){{--bg:#10151f;--fg:#e8edf5;--mut:#9aa7b8;--bd:#2a3342;--card:#171e2b}}}}
body{{margin:0;background:var(--bg);color:var(--fg);font:15px/1.5 system-ui,-apple-system,Segoe UI,sans-serif}}
header{{position:sticky;top:0;z-index:5;background:var(--card);border-bottom:1px solid var(--bd);padding:10px 16px;display:flex;gap:14px;flex-wrap:wrap;align-items:center}}
header b{{font-size:14px}}header span{{font-weight:700}}header a{{color:var(--fg);font-size:14px}}
main{{max-width:1320px;margin:0 auto;padding:16px}}
.intro{{background:var(--card);border:1px solid var(--bd);border-radius:10px;padding:14px 18px}}
section{{margin-top:34px}}h2{{margin:0 0 4px;font-size:24px}}.op{{color:var(--mut);margin:0 0 10px}}
.war{{background:var(--card);border:1px solid var(--bd);border-radius:10px;padding:14px 16px;margin-top:14px}}
.war h3{{margin:0;font-size:17px}}.war p{{margin:4px 0 12px;color:var(--mut);max-width:900px}}
.pair{{display:flex;gap:22px;align-items:flex-start;flex-wrap:wrap}}
.lab{{display:block;font-size:12px;color:var(--mut);margin-bottom:4px}}
.fr{{overflow:hidden;border:1px solid var(--bd);border-radius:6px;background:#fff;max-width:calc(100vw - 64px)}}
.fr iframe{{border:0;transform-origin:0 0;display:block}}
.full{{display:inline-block;font-size:12px;color:var(--mut);margin-top:4px}}
</style></head><body>
<header><span>T-115 · makiety 17.09:</span>{nav}</header>
<main><div class="intro"><p style="margin:0">{'<b>Zestaw wybrany 17.09</b> — A2, B1, ikony na zdjęciu w karcie produktu, D1 z X w kółku, zamiast okna zamiany komunikat + rozwinięty pasek. Strony A–E zostają jako historia wyboru. ' if akt == 'W' else ''}Każda ramka to <b>prawdziwa strona primaauto.com.pl</b> (dzisiejszy HTML, bez skryptów) z dołożonym wariantem.
Wybierz po jednym wariancie w A–E (np. „A2, B1, C1, D1, E2”). Zasady działania ustalone w quizie są wspólne dla wszystkich wariantów — różni się miejsce i forma.
Serce to miejsce zarezerwowane dla ulubionych (T-114) — pokazane, żeby układ od razu je mieścił.</p></div>
{''.join(out)}</main></body></html>"""


if __name__ == '__main__':
    import urllib.parse
    main()
