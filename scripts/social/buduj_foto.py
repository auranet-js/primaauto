#!/usr/bin/env python3
"""Kampania [FOTO] — karuzele ze zdjęć z sesji. Buduje bibliotekę, nic nie uruchamia.

Materiał wyłącznie z Dysku Google (`Prima Auto/sesje/`), nigdy z feedu produktowego:
zdjęcia z sesji są jedyne, których nie ma konkurencja importująca z tych samych giełd.

Powstaje:
  · karuzela „karty samochodów" — po jednym kadrze z każdego modelu, kolejność po cenie
  · wariant tej samej karuzeli na drugim kadrze — do porównania, który kadr niesie
  · karuzela per model — trzy kadry jednego auta, wszystkie karty na hub serii

    python3 scripts/social/buduj_foto.py --sonda
    python3 scripts/social/buduj_foto.py --buduj

Wszystko powstaje jako PAUSED. Włączenie z budżetem to osobna zgoda Janka.
"""
import argparse
import json
import os
import subprocess
import sys
import urllib.request
from datetime import datetime

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import meta_api as api
import drive_lib as dl

STAN = 'meta-foto.json'
SCRATCH = '/tmp/claude-1584/-home-host476470-projekty-primaauto/foto'
FFMPEG = '/home/host476470/bin/ffmpeg'
KADRY_NA_MODEL = 3
MIN_ZAPAS = 3        # poniżej tego seria zejdzie szybciej, niż karta zdąży zarobić
BUDZET_GR = '1500'
BAZA = 'https://primaauto.com.pl'
WP = '/home/host476470/domains/primaauto.com.pl/public_html'

# folder na Dysku → (klucz, term taksonomii `serie`, landing)
# Nazwa karty i cena idą z bazy (`_serie_full_title` + `price`) — nic nie wpisujemy z ręki,
# bo to na ręcznych opisach powstały „Z9 GT kombi", „VX 7 osób" i „T2 PHEV" przy serii benzynowej.
MODELE = {
    'Jetour-T2/Srebrny':   ('jetour-t2', 'T2 C-DM',                    '/samochody/jetour/t2-c-dm/#oferty'),
    'Exceed-VX/Granatowy': ('exeed-vx',  'VX',                         '/samochody/exeed/vx/#oferty'),
    'Deepal-G318':         ('g318',      'G318',                       '/samochody/deepal/g318/#oferty'),
    'Leopard-5/Czarny':    ('leopard-5', 'Leopard 5 (Denza B5)',       '/samochody/byd/leopard-5/#oferty'),
    'Leopard-7':           ('leopard-7', 'Leopard 7 (Tai 7) FCB, PHEV','/samochody/byd/leopard-7/#oferty'),
    'Denza-Z9GT':          ('z9-gt',     'Z9 GT DM-i',                 '/samochody/denza/z9-gt-dm-i/#oferty'),
    'Byd-Shark-6':         ('shark-6',   'Shark 6',                    '/samochody/byd/shark-6/#oferty'),
    'Lynk-Co-900':         ('lynk-900',  '900',                        '/samochody/lynk-co/900/#oferty'),
    'Denza-N9':            ('n9',        'N9 DM-i',                    '/samochody/denza/n9-dm-i/#oferty'),
    # Leopard-5/Niebieski to ta sama seria co Czarny — do karuzeli przekrojowej wchodzi raz.
}

# Tekst główny to dwa assety Google Ads sklejone dosłownie (konto 9506068500, 30 dni):
#   „Prima-Auto — bezpośredni importer aut z Chin. Ceny w ogłoszeniach, zamów online."  3,25% konw/klik
#   „Auta na placu w Rzeszowie i w drodze do Polski. Sprawdź ceny i dostępność."        8,70%
# Zero liczb, które rotują (zapas, ceny), zero słowa „homologacja", zero obietnicy rejestracji —
# kreacji w Mecie nie da się edytować, więc każde takie zdanie zestarzeje się razem z reklamą.
TEKST = ('Prima-Auto — bezpośredni importer aut z Chin. Ceny w ogłoszeniach, zamów online. '
         'Auta na placu w Rzeszowie i w drodze do Polski. Sprawdź ceny i dostępność.')


DOMENA = 'primaauto.com.pl'


# Dwie karuzele, po pięć modeli, dzielone progiem cenowym — inna karta to inny model
# i inny link. Trzy kadry tego samego auta pod jednym linkiem to pokaz slajdów, nie karuzela.
# (tytuł, modele, które kadry). Karta = model + własny link, nigdy drugi kadr tego samego
# auta — trzy zdjęcia pod jednym linkiem to pokaz slajdów, nie karuzela.
WSZYSTKIE = ['jetour-t2', 'exeed-vx', 'g318', 'leopard-5', 'leopard-7',
             'z9-gt', 'shark-6', 'lynk-900', 'n9']
KARUZELE = [
    ('Cała oferta', WSZYSTKIE, (0, 1)),
    ('Do 200 tysięcy', WSZYSTKIE[:5], (0,)),
    ('Od 200 tysięcy', WSZYSTKIE[4:], (0,)),
]


def zapas(termy):
    """Ile sztuk w serii — na karcie obok ceny, bo „od 183 tys. zł · 19 aut" mówi więcej
    niż sama cena i jest prawdą sprawdzalną w dniu budowy kreacji."""
    lista = ','.join('"%s"' % t.replace('"', '') for t in termy)
    kod = ('$o=[]; foreach ([%s] as $n){ $t=get_term_by("name",$n,"serie"); if(!$t) continue; '
           '$q=new WP_Query(["post_type"=>"listings","post_status"=>"publish","posts_per_page"=>1,'
           '"fields"=>"ids","tax_query"=>[["taxonomy"=>"serie","field"=>"term_id",'
           '"terms"=>$t->term_id]]]); $o[$n]=$q->found_posts; } echo json_encode($o);') % lista
    r = subprocess.run(['wp', 'eval', kod], cwd=WP, capture_output=True, text=True)
    try:
        return json.loads(r.stdout.strip().splitlines()[-1])
    except Exception:
        return {}


def ceny():
    """Cena wejścia w serię prosto z bazy — karta bez ceny nie robi selekcji, a cena
    wpisana na sztywno starzeje się razem z zapasem."""
    termy = ','.join('"%s"' % m[1].replace('"', '') for m in MODELE.values())
    kod = ('$o=[]; foreach ([%s] as $n) { $t=get_term_by("name",$n,"serie"); if(!$t) continue; '
           '$q=new WP_Query(["post_type"=>"listings","post_status"=>"publish","posts_per_page"=>-1,'
           '"fields"=>"ids","tax_query"=>[["taxonomy"=>"serie","field"=>"term_id","terms"=>$t->term_id]]]);'
           '$c=[]; foreach($q->posts as $id){ $p=(int)get_post_meta($id,"price",true); if($p>0)$c[]=$p; }'
           'if($c)$o[$n]=min($c); } echo json_encode($o);') % termy
    r = subprocess.run(['wp', 'eval', kod], cwd=WP, capture_output=True, text=True)
    try:
        return json.loads(r.stdout.strip().splitlines()[-1])
    except Exception:
        sys.exit(f'nie mam cen z bazy: {r.stderr[:200]}')


def tytuly():
    """Nazwa karty = tytuł huba serii z bazy (`_serie_full_title`), np. „Exeed VX (Omoda 11)".

    Janek, 04.09: „w tytułach ogłoszeń jest wszystko" — wariant, napęd i przydomek rynkowy
    siedzą już w tytule, więc karta nie potrzebuje naszego opisu cech i nie ma go skąd zmyślić.
    """
    termy = ','.join('"%s"' % m[1].replace('"', '') for m in MODELE.values())
    kod = ('$o=[]; foreach ([%s] as $n) { $t=get_term_by("name",$n,"serie"); if(!$t) continue; '
           '$f=get_term_meta($t->term_id,"_serie_full_title",true); $o[$n]=$f?:$t->name; } '
           'echo json_encode($o);') % termy
    r = subprocess.run(['wp', 'eval', kod], cwd=WP, capture_output=True, text=True)
    try:
        return json.loads(r.stdout.strip().splitlines()[-1])
    except Exception:
        sys.exit(f'nie mam tytułów serii z bazy: {r.stderr[:200]}')


def kadry(tok):
    """Pierwsze KADRY_NA_MODEL zdjęć z każdego folderu modelu, w kolejności nazw."""
    out = {}
    for folder, (klucz, *_) in MODELE.items():
        biezacy = dl.FOLDER_SESJE
        for czlon in folder.split('/'):
            pasuje = [f for f in dl.lista(biezacy, tok) if f['name'] == czlon]
            if not pasuje:
                sys.exit(f'nie ma folderu {folder} na Dysku')
            biezacy = pasuje[0]['id']
        zdjecia = sorted((f for f in dl.lista(biezacy, tok)
                          if f['mimeType'].split('/')[-1] in ('jpeg', 'jpg', 'png', 'webp')),
                         key=lambda f: f['name'])
        out[klucz] = zdjecia[:KADRY_NA_MODEL]
    return out


def kwadrat(plik_zrodlowy, cel):
    """Kadr 1:1 1080×1080 z poziomego oryginału — środek klatki, bez rozciągania.

    Karuzela w Aktualnościach chce kwadratu. Kadrowanie do 9:16 obcięłoby auto po bokach,
    dlatego umiejscowienia pionowe w tej kampanii odpadają.
    """
    subprocess.run([FFMPEG, '-y', '-loglevel', 'error', '-i', plik_zrodlowy,
                    '-vf', "crop='min(iw,ih)':'min(iw,ih)',scale=1080:1080",
                    '-q:v', '2', cel], check=True)


def wyslij_obraz(sciezka):
    """Multipart do /adimages — zwraca hash, którym posługuje się kreacja."""
    granica = '----primaauto' + datetime.now().strftime('%H%M%S%f')
    czesci = [f'--{granica}\r\nContent-Disposition: form-data; name="access_token"\r\n\r\n'
              f'{api.token()}\r\n'.encode(),
              f'--{granica}\r\nContent-Disposition: form-data; name="filename"; '
              f'filename="{os.path.basename(sciezka)}"\r\nContent-Type: image/jpeg\r\n\r\n'.encode(),
              open(sciezka, 'rb').read(),
              f'\r\n--{granica}--\r\n'.encode()]
    req = urllib.request.Request(api.GRAPH + api.ACT + '/adimages', data=b''.join(czesci),
                                 headers={'Content-Type': f'multipart/form-data; boundary={granica}'})
    try:
        d = json.load(urllib.request.urlopen(req, timeout=300))
        return list(d['images'].values())[0]['hash'], None
    except Exception as e:
        return None, (e.read().decode()[:250] if hasattr(e, 'read') else str(e))


def karta(hasz, tytul, opis, landing):
    return {'link': landing, 'image_hash': hasz, 'name': tytul[:40], 'description': opis[:30],
            'call_to_action': {'type': 'LEARN_MORE', 'value': {'link': landing}}}


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--sonda', action='store_true')
    ap.add_argument('--buduj', action='store_true')
    ap.add_argument('--przepisz', action='store_true',
                    help='przebuduj kreacje z aktualnych tekstów i podmień istniejące reklamy '
                         '(status zachowany, stara reklama do archiwum)')
    a = ap.parse_args()
    if not (a.sonda or a.buduj or a.przepisz):
        ap.error('podaj --sonda, --buduj albo --przepisz')
    waliduj = not (a.buduj or a.przepisz)
    stan = api.stan_wczytaj(STAN)
    os.makedirs(SCRATCH, exist_ok=True)

    cennik, tytul = ceny(), tytuly()
    print('CENY WEJŚCIA W SERIĘ')
    for folder, (klucz, term, land) in MODELE.items():
        print(f'  {tytul[term]:26} {cennik.get(term, 0)//1000:>4} tys.  {land}')

    print('\nLANDINGI')
    for folder, (klucz, term, land) in MODELE.items():
        kod = api.landing_zyje(BAZA + land)
        print(f'  {tytul[term]:26} {kod}')
        if kod != 200:
            sys.exit(f'{term}: landing nie zwraca 200 — przerywam, reklama marnowałaby budżet')

    hasze = stan.setdefault('hasze', {})
    print('\nKADRY Z DYSKU → KWADRAT 1080 → META')
    if a.przepisz:
        print(f'  przepisanie tekstów — korzystam z {len(hasze)} wgranych kadrów, Dysku nie ruszam')
    wybrane = {} if a.przepisz else kadry(dl.access_token())
    for folder, (klucz, term, land) in MODELE.items():
        for i, f in enumerate(wybrane.get(klucz, [])):
            etykieta = f'{klucz}-{i}'
            if etykieta in hasze:
                print(f'  {etykieta:22} już wgrany')
                continue
            if waliduj:
                print(f'  {etykieta:22} (sonda — nie wgrywam) {f["name"][:44]}')
                continue
            surowy = os.path.join(SCRATCH, f'{etykieta}-oryginal')
            kwadratowy = os.path.join(SCRATCH, f'{etykieta}.jpg')
            dl.pobierz(f['id'], surowy, tok)
            kwadrat(surowy, kwadratowy)
            os.remove(surowy)
            h, e = wyslij_obraz(kwadratowy)
            if e:
                sys.exit(f'{etykieta}: {e}')
            hasze[etykieta] = h
            api.stan_zapisz(STAN, stan)
            print(f'  {etykieta:22} {h}')

    if waliduj and not hasze:
        print('\nSonda kończy się tutaj — kreacje wymagają wgranych obrazów.')
        return

    print('\nKAMPANIA I ZESTAW')
    kamp = stan.get('kampania')
    if not kamp:
        r, e = api.post(f'{api.ACT}/campaigns', {
            # Cel konwersyjny, nie ruch: optymalizujemy na otwarcie oferty (ViewContent),
            # nie na samo załadowanie strony. Celu kampanii nie da się zmienić po utworzeniu.
            'name': '[FOTO] Zdjęcia z sesji — karuzele', 'objective': 'OUTCOME_LEADS',
            'status': 'PAUSED', 'special_ad_categories': json.dumps([]),
            'buying_type': 'AUCTION', 'is_adset_budget_sharing_enabled': 'false'}, waliduj=waliduj)
        print('  kampania:', r or e)
        kamp = (r or {}).get('id')
        if kamp and not waliduj:
            stan['kampania'] = kamp
            api.stan_zapisz(STAN, stan)
    else:
        print('  kampania: już istnieje', kamp)

    zestaw = stan.get('zestaw')
    if kamp and not zestaw:
        grupy, _ = api.get(f'{api.ACT}/customaudiences?fields=id,name&limit=100')
        mam = {g['name']: g['id'] for g in grupy['data']}
        wyklucz = [{'id': mam[n], 'name': n} for n in
                   ('Wszyscy odwiedzający — 180 dni', 'Kontakt tel./WhatsApp — 180 dni')]
        r, e = api.post(f'{api.ACT}/adsets', {
            'name': 'Karuzele PL 25-65 — zdjęcia z sesji', 'campaign_id': kamp, 'status': 'PAUSED',
            'daily_budget': BUDZET_GR, 'billing_event': 'IMPRESSIONS',
            'optimization_goal': 'OFFSITE_CONVERSIONS', 'bid_strategy': 'LOWEST_COST_WITHOUT_CAP',
            'destination_type': 'WEBSITE',
            'promoted_object': json.dumps({'pixel_id': api.PIKSEL,
                                           'custom_event_type': 'CONTENT_VIEW'}),
            'dsa_beneficiary': api.DSA_PODMIOT, 'dsa_payor': api.DSA_PODMIOT,
            'targeting': json.dumps({
                'geo_locations': {'countries': ['PL'], 'location_types': ['home']},
                'age_min': 25, 'age_max': 65,
                'targeting_automation': {'advantage_audience': 0},
                'publisher_platforms': ['facebook', 'instagram'],
                # Kadr kwadratowy nie wchodzi w pion — Relacje i Rolki odpadają świadomie.
                'facebook_positions': ['feed', 'marketplace', 'search'],
                'instagram_positions': ['stream'],   # Eksploruj wycofane w tej wersji API
                'excluded_custom_audiences': wyklucz})}, waliduj=waliduj)
        print('  zestaw:', r or e)
        zestaw = (r or {}).get('id')
        if zestaw and not waliduj:
            stan['zestaw'] = zestaw
            api.stan_zapisz(STAN, stan)
    elif zestaw:
        print('  zestaw: już istnieje', zestaw)

    tekst = TEKST
    print('\nTEKST GŁÓWNY:', tekst)

    print('KREACJE')
    po_kluczu = {m[0]: (folder, *m) for folder, m in MODELE.items()}
    zapasy = zapas([m[1] for m in MODELE.values()])
    kreacje = []
    for tytul_kar, klucze, kadry_nr in KARUZELE:
        for nr in kadry_nr:
            karty = []
            for kl in klucze:
                folder, _, term, land = po_kluczu[kl]
                h = hasze.get(f'{kl}-{nr}')
                if not h:
                    continue
                sztuk = zapasy.get(term, 0)
                if sztuk < MIN_ZAPAS:
                    print(f'    {tytul[term]}: zapas {sztuk} — pomijam kartę')
                    continue
                # Nazwa z bazy, cena z bazy, nic pomiędzy. Liczba sztuk starzeje się
                # szybciej niż kreacja — zapas służy tylko za filtr powyżej.
                karty.append(karta(h, tytul[term], f'od {cennik[term]//1000} 000 zł',
                                   BAZA + land))
            if len(karty) >= 2:
                etykieta = f'[FOTO] {tytul_kar}' + (f' — kadr {nr + 1}' if len(kadry_nr) > 1 else '')
                kreacje.append((etykieta, karty))

    statusy = {}
    if a.przepisz:
        d, e = api.get(f'{zestaw}/ads?fields=id,status&limit=50')
        if e:
            sys.exit(f'nie mogę odczytać statusów reklam: {e}')
        statusy = {x['id']: x['status'] for x in d['data']}

    for nazwa_kre, karty in kreacje:
        klucz_stanu = f'kreacja:{nazwa_kre}'
        if a.przepisz:
            spec = {'page_id': api.PAGE, 'instagram_user_id': api.IG,
                    'link_data': {'link': karty[0]['link'], 'message': tekst,
                                  'caption': DOMENA, 'child_attachments': karty,
                                  'multi_share_optimized': True, 'multi_share_end_card': True}}
            r, e = api.post(f'{api.ACT}/adcreatives',
                            {'name': f'{nazwa_kre} v2', 'object_story_spec': json.dumps(spec)},
                            waliduj=False)
            print(f'  {nazwa_kre} ({len(karty)} kart): kreacja {r or e}')
            if e:
                continue
            kre = r['id']
            stara = stan.get(f'reklama:{nazwa_kre}')
            status = statusy.get(stara, 'PAUSED')
            r, e = api.post(f'{api.ACT}/ads', {
                'name': nazwa_kre, 'adset_id': zestaw,
                'creative': json.dumps({'creative_id': kre}), 'status': status}, waliduj=False)
            if e:
                print('    BŁĄD reklamy:', e); continue
            if stara:
                _, e2 = api.post(stara, {'status': 'ARCHIVED'}, waliduj=False)
                if e2:
                    print('    UWAGA: stara reklama NIE poszła do archiwum:', e2)
            stan[klucz_stanu] = kre
            stan[f'reklama:{nazwa_kre}'] = r['id']
            api.stan_zapisz(STAN, stan)
            print(f'    nowa reklama {r["id"]} ({status}), stara {stara} do archiwum')
            continue
        # Gotowa kreacja nie zwalnia z zrobienia reklamy — pomijamy krok, nie pozycję.
        if klucz_stanu in stan:
            print(f'  {nazwa_kre}: kreacja już istnieje ({stan[klucz_stanu]})')
            kre = stan[klucz_stanu]
            if f'reklama:{nazwa_kre}' not in stan and zestaw:
                r, e = api.post(f'{api.ACT}/ads', {
                    'name': nazwa_kre, 'adset_id': zestaw,
                    'creative': json.dumps({'creative_id': kre}), 'status': 'PAUSED'},
                    waliduj=waliduj)
                print(f'    reklama: {r or e}')
                if not waliduj and r:
                    stan[f'reklama:{nazwa_kre}'] = r['id']
                    api.stan_zapisz(STAN, stan)
            continue
        spec = {'page_id': api.PAGE, 'instagram_user_id': api.IG,
                'link_data': {'link': karty[0]['link'], 'message': tekst,
                              'caption': DOMENA, 'child_attachments': karty,
                              'multi_share_optimized': True, 'multi_share_end_card': True}}
        r, e = api.post(f'{api.ACT}/adcreatives',
                        {'name': nazwa_kre, 'object_story_spec': json.dumps(spec)}, waliduj=waliduj)
        print(f'  {nazwa_kre} ({len(karty)} kart): {r or e}')
        kre = (r or {}).get('id')
        if kre and not waliduj:
            stan[klucz_stanu] = kre
            api.stan_zapisz(STAN, stan)
        if kre and zestaw:
            r, e = api.post(f'{api.ACT}/ads', {
                'name': nazwa_kre, 'adset_id': zestaw,
                'creative': json.dumps({'creative_id': kre}), 'status': 'PAUSED'}, waliduj=waliduj)
            print(f'    reklama: {r or e}')
            if not waliduj and r:
                stan[f'reklama:{nazwa_kre}'] = r['id']
                api.stan_zapisz(STAN, stan)

    if not a.przepisz:
        print('\nWszystko PAUSED. Nic się nie wyświetla.')


if __name__ == '__main__':
    main()
