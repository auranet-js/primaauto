#!/usr/bin/env python3
"""T-264: częstotliwość klików w główny przycisk (karta oferty / katalog → /zamow/) przed i po zmianie
„Zamów” → „Chcę to auto” (13.10.2026 ~13:30).

Źródło: logi serwera (~/domains/primaauto.com.pl/logs/*.tar.gz, jeden plik = jedna doba) — niezależne od zgody
na cookies (GA4 widzi ~1/3 wejść, patrz memory reference_meta_lpv_zanizone_przez_zgode).
- wyświetlenia oferty = unikalne (IP, URL) GET /oferta/<slug>/ → 200, ludzki UA, Referer z innej strony
- klik z oferty = unikalne (IP, listing_id) GET /zamow/?listing_id=N z Refererem /oferta/…-N/
- klik z listy = jw. z Refererem /samochody/, /w-rzeszowie/, /w-drodze/
  Scrapery (proxy rezydencjalne, Referer = ten sam /zamow/ albo /) i crawl bez Referera odpadają.
Mianownik z logów jest skażony (crawl katalogu, wejścia z Meta bez Referera) — CTR z logów tylko orientacyjnie.
Udział klików w wejściach liczy --ga4 (zgoda zaniża licznik i mianownik tak samo, porównanie przed/po uczciwe).
Zgłoszenia (wysłane formularze) z bazy: --zgloszenia. Doba 13.10 = przejściowa, nie wliczać do żadnego okresu.

Użycie: python3 scripts/pomiar-cta-zgloszenie.py [--zgloszenia] [--ga4 [OD]]"""
import collections, glob, io, re, subprocess, sys, tarfile
from datetime import datetime

LOGS = '/home/host476470/domains/primaauto.com.pl/logs'
WP = '/home/host476470/domains/primaauto.com.pl/public_html'
ZMIANA = datetime(2026, 10, 13, 13, 30)
BOT = re.compile(r'bot|crawl|spider|slurp|google|bing|facebookexternalhit|meta-external|curl|wget|python|go-http|'
                 r'headless|lighthouse|bytespider|gpt|claude|anthropic|ahrefs|semrush|dataforseo|mj12|petal|yandex|'
                 r'baidu|applebot|amazonbot|perplexity|duckduck|seznam|axios|okhttp|java/|scrapy|node-fetch|preview', re.I)
LINE = re.compile(r'^(\S+) \S+ \S+ \[([^\]]+)\] "GET (\S+) [^"]*" (\d{3}) \S+ "([^"]*)" "([^"]*)"')
TEST_KLIENCI = (10, 12, 13, 157)  # konta testowe (jak w scripts/rozliczenia-oznacz.php)

dni = collections.defaultdict(lambda: {'oferta': set(), 'klik': set(), 'klik_kat': set()})
for plik in sorted(glob.glob(f'{LOGS}/*.tar.gz*')):
    with tarfile.open(plik) as tar:
        for m in tar.getmembers():
            if not m.name.endswith('primaauto.com.pl.log'):
                continue
            for raw in io.TextIOWrapper(tar.extractfile(m), encoding='utf-8', errors='replace'):
                g = LINE.match(raw)
                if not g:
                    continue
                ip, ts, url, kod, ref, ua = g.groups()
                if kod != '200' or BOT.search(ua):
                    continue
                t = datetime.strptime(ts[:20], '%d/%b/%Y:%H:%M:%S')
                okres = 'po' if t >= ZMIANA else 'przed'
                d = dni[(t.date(), okres)]
                ref_path = re.sub(r'^https?://(www\.)?primaauto\.com\.pl', '', ref) if 'primaauto.com.pl' in ref else None
                if re.match(r'^/oferta/[^/?]+/?(\?|$)', url):
                    # scrapery wchodzą bez Referera albo z Refererem = ten sam URL; liczymy wejścia z innej strony
                    if ref not in ('', '-') and (ref_path or '').split('?')[0] != url.split('?')[0]:
                        d['oferta'].add((ip, url.split('?')[0]))
                elif url.startswith('/zamow/?') and ref_path is not None:
                    lid = re.search(r'listing_id=(\d+)', url)
                    lid = lid.group(1) if lid else ''
                    if lid and re.match(rf'^/oferta/[^?]*-{lid}/?', ref_path):
                        d['klik'].add((ip, lid))          # klik „Chcę to auto” z karty tej samej oferty
                    elif re.match(r'^/(samochody|w-rzeszowie|w-drodze)/', ref_path):
                        d['klik_kat'].add((ip, lid))      # klik z karty w katalogu
                    # Referer = /zamow/ lub / (strona główna nie ma kart) → scraper, pomijamy

print(f'{"doba":<12}{"okres":<7}{"oferty":>8}{"klik z oferty":>15}{"CTR %":>8}{"klik z listy":>14}')
suma = collections.defaultdict(lambda: [0, 0, 0, 0])
for (dzien, okres), d in sorted(dni.items()):
    o, k, kk = len(d['oferta']), len(d['klik']), len(d['klik_kat'])
    print(f'{dzien!s:<12}{okres:<7}{o:>8}{k:>15}{(100 * k / o if o else 0):>8.2f}{kk:>14}')
    s = suma[okres]; s[0] += o; s[1] += k; s[2] += kk; s[3] += 1
for okres, (o, k, kk, n) in suma.items():
    print(f'RAZEM {okres}: {n} dób, oferty {o}, klik z oferty {k} (CTR {100 * k / o if o else 0:.2f}%), klik z listy {kk}')

if '--zgloszenia' in sys.argv:
    sql = ("SELECT DATE(p.post_date) d, SUM(p.post_date >= '2026-10-13 13:30:00') po, COUNT(*) n "
           "FROM wp7j_posts p JOIN wp7j_postmeta c ON c.post_id=p.ID AND c.meta_key='_order_customer_id' "
           "JOIN wp7j_postmeta ty ON ty.post_id=p.ID AND ty.meta_key='_order_type' AND ty.meta_value='customer' "
           f"WHERE p.post_type='asiaauto_order' AND p.post_date >= '2026-09-01' AND c.meta_value NOT IN {TEST_KLIENCI} "
           "GROUP BY d ORDER BY d")
    print('\nZgłoszenia (formularz /zamow/ wysłany, bez kont testowych) — doba | po zmianie | razem')
    print(subprocess.run(['wp', f'--path={WP}', 'db', 'query', sql, '--skip-column-names'],
                         capture_output=True, text=True).stdout)

if '--ga4' in sys.argv:
    sys.path.insert(0, str(__import__('pathlib').Path(__file__).parent))
    from ga4_query import token, run, rows
    i = sys.argv.index('--ga4')
    od = sys.argv[i + 1] if len(sys.argv) > i + 1 and not sys.argv[i + 1].startswith('-') else '2026-09-15'
    tok = token()

    def raport(filtr):
        d = run(tok, {'dateRanges': [{'startDate': od, 'endDate': 'yesterday'}],
                      'dimensions': [{'name': 'date'}, {'name': 'eventName'}], 'metrics': [{'name': 'eventCount'}],
                      'dimensionFilter': filtr, 'limit': 10000})
        out = collections.defaultdict(dict)
        for (dz, ev), (n,) in rows(d)[2]:
            out[dz][ev] = int(n)
        return out

    pv = lambda prefix: {'andGroup': {'expressions': [
        {'filter': {'fieldName': 'eventName', 'stringFilter': {'value': 'page_view'}}},
        {'filter': {'fieldName': 'pagePath', 'stringFilter': {'matchType': 'BEGINS_WITH', 'value': prefix}}}]}}
    oferta, zamow = raport(pv('/oferta/')), raport(pv('/zamow/'))
    zdarz = raport({'filter': {'fieldName': 'eventName', 'inListFilter': {'values': ['zamowienie_start', 'generate_lead']}}})
    print(f'\nGA4 (od {od}) — doba | pv /oferta/ | pv /zamow/ | % | zamowienie_start | generate_lead')
    sumy = collections.defaultdict(lambda: [0, 0, 0, 0, 0])
    for dz in sorted(oferta):
        o, z = oferta[dz].get('page_view', 0), zamow.get(dz, {}).get('page_view', 0)
        s, l = zdarz.get(dz, {}).get('zamowienie_start', 0), zdarz.get(dz, {}).get('generate_lead', 0)
        okres = 'przejscie' if dz == '20261013' else ('po' if dz > '20261013' else 'przed')
        print(f'{dz} {okres:<10}{o:>7}{z:>6}{(100 * z / o if o else 0):>7.2f}{s:>6}{l:>5}')
        x = sumy[okres]; x[0] += o; x[1] += z; x[2] += s; x[3] += l; x[4] += 1
    for okres, (o, z, s, l, n) in sumy.items():
        print(f'GA4 RAZEM {okres}: {n} dób, pv oferta {o}, pv /zamow/ {z} ({100 * z / o if o else 0:.2f}%), '
              f'zamowienie_start {s}, generate_lead {l}')
