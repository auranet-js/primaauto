#!/usr/bin/env python3
"""Teksty reklam [VID] — dosłownie z assetów Google Ads, nic własnego.

Zasada Janka z 04.09: „po to ci kazałem wziąć assety z google ads, bo tam nie ma faktów
liczbowych i fantazji". Wersja poprzednia (w .bak) układała zdania z pól bazy i wpadała
w cechy zmyślone oraz w liczby, które się starzeją — kreacji w Mecie nie da się edytować.

Tu każde zdanie jest kopią assetu z konta 9506068500 (30 dni, konw/klik przy wpisie).
Skrypt NIE pisze zdań. Jedyne, co robi z bazą, to STRAŻNIK: nagłówek mówiący coś o aucie
(„6-osobowy SUV premium", „Terenowy PHEV z napędem 4x4", „Hybryda plug-in prosto z Chin")
wchodzi tylko wtedy, gdy `body`/`fuel`/`drive`/`seat_count` w bazie to potwierdzają.

Wybrane assety pomijają: liczby, które rotują (ceny, zapas), słowo „homologacja" oraz
obietnicę rejestracji („rejestracja po naszej stronie") — my przygotowujemy, klient rejestruje.

    python3 scripts/social/przepisz_teksty.py --pokaz    # wypisuje teksty + wynik strażnika
    python3 scripts/social/przepisz_teksty.py --wgraj    # nowe kreacje + podmiana reklam

Podmiana zachowuje status reklamy zastępowanej: ACTIVE zostaje ACTIVE (dostawa nie staje,
faza uczenia zestawu zostaje), PAUSED zostaje PAUSED. Stara reklama idzie do archiwum.
"""
import argparse
import json
import os
import subprocess
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import meta_api as api

WP = '/home/host476470/domains/primaauto.com.pl/public_html'
BAZA = 'https://primaauto.com.pl'
STAN = 'meta-start.json'

# ── Bank tekstów: kopiuj-wklej z assetów Google Ads (30 dni do 04.09.2026) ────────────
# kod: (tekst, konw/klik, kliki). Tekstów NIE przepisujemy — zmiana = inny asset z konta.
H = {
    'oferta':  ('Zobacz Ofertę Aut z Chin',     4.62, 173),
    'aktualne':('Aktualne Oferty z Chin',       4.24, 440),
    'phev':    ('Hybryda plug-in prosto z Chin',2.83, 106),
    'bezpo':   ('Import bezpośredni z Chin',    2.75, 409),
    'marka':   ('Prima-Auto - Samochody z Chin',2.67, 699),
    'rejestr': ('Gotowe do Rejestracji w PL',   2.05, 175),
    'importpa':('Import Aut z Chin - Prima Auto',1.93, 415),
    'szesc':   ('6-osobowy SUV premium',        5.30, 132),
    'teren':   ('Terenowy PHEV z napędem 4x4',  0.00, 149),
}
D = {
    'A': ('Prima-Auto — bezpośredni importer aut z Chin. Ceny w ogłoszeniach, zamów online.', 3.25, 727),
    'B': ('Aktualne ogłoszenia z Chin — codziennie. Umowa agencyjna, transport, cło.', 2.87, 650),
    'C': ('Bezpośredni Importer samochodów z Chin. Ceny w ogłoszeniach — zamów online w 1 klik.', 1.71, 351),
    'E': ('Auta na placu w Rzeszowie i w drodze do Polski. Sprawdź ceny i dostępność.', 8.70, 46),
    'F': ('Zamów auto online i zleć import pod dom w całej Polsce', 1.82, 161),
    'G': ('Każdy samochód przed zakupem jest przez nas weryfikowany, następnie sprowadzany do Polski', 4.26, 47),
    'J': ('Terenówki, pickup i GT prosto z Chin. Zobacz ceny i dostępność od ręki.', 0.46, 218),
    'K': ('Obejrzyj auto na żywo w Rzeszowie albo zamów online. Dostawa w całej Polsce.', 5.56, 18),
}
PODPIS = H['marka'][0]      # link_description — też asset, nie nasze zdanie

# Warunek z bazy, który musi być spełniony, żeby nagłówek mówiący o aucie mógł wejść.
STRAZNIK = {
    'szesc': (lambda f: f['body'] == 'suv' and str(f['seats']) == '6', 'SUV z 6 miejscami'),
    'teren': (lambda f: f['body'] == 'suv' and f['fuel'] == 'phev' and f['drive'] == 'awd',
              'SUV PHEV z napędem 4x4'),
    'phev':  (lambda f: f['fuel'] == 'phev', 'napęd PHEV'),
}

# klucz wideo → (term `serie`, landing, nagłówek, opisy)
# Jetour: na sesji stał C-DM (hybryda) — landing prowadzi na serię C-DM, nie benzynową.
MODELE = {
    'leopard-5-czarny':    ('Leopard 5 (Denza B5)',        '/samochody/byd/leopard-5/#oferty',      'aktualne', 'AB'),
    'z9-gt':               ('Z9 GT DM-i',                  '/samochody/denza/z9-gt-dm-i/#oferty',   'oferta',   'AE'),
    'leopard-5-niebieski': ('Leopard 5 (Denza B5)',        '/samochody/byd/leopard-5/#oferty',      'teren',    'BE'),
    'leopard-7':           ('Leopard 7 (Tai 7) FCB, PHEV', '/samochody/byd/leopard-7/#oferty',      'phev',     'AG'),
    'shark-6':             ('Shark 6',                     '/samochody/byd/shark-6/#oferty',        'bezpo',    'JA'),
    'jetour-t2':           ('T2 C-DM',                     '/samochody/jetour/t2-c-dm/#oferty',     'phev',     'CF'),
    'exeed-vx':            ('VX',                          '/samochody/exeed/vx/#oferty',           'szesc',    'AG'),
    'g318':                ('G318',                        '/samochody/deepal/g318/#oferty',        'importpa', 'BE'),
    'lynk-900':            ('900',                         '/samochody/lynk-co/900/#oferty',        'szesc',    'BF'),
    'n9':                  ('N9 DM-i',                     '/samochody/denza/n9-dm-i/#oferty',      'rejestr',  'AK'),
}


def fakty():
    """Nadwozie, paliwo, napęd i liczba miejsc per seria — wyłącznie dla strażnika."""
    termy = ','.join('"%s"' % m[0].replace('"', '') for m in MODELE.values())
    kod = ('$o=[]; foreach ([%s] as $n) { $t=get_term_by("name",$n,"serie"); if(!$t) continue; '
           '$q=new WP_Query(["post_type"=>"listings","post_status"=>"publish","posts_per_page"=>-1,'
           '"fields"=>"ids","tax_query"=>[["taxonomy"=>"serie","field"=>"term_id","terms"=>$t->term_id]]]);'
           '$b=[]; $f=[]; $d=[]; $s=[];'
           'foreach($q->posts as $id){'
           '  foreach(["body"=>&$b,"fuel"=>&$f,"drive"=>&$d] as $k=>&$kosz){'
           '    $v=get_post_meta($id,$k,true); if($v)$kosz[$v]=($kosz[$v]??0)+1; }'
           '  $e=json_decode(get_post_meta($id,"_asiaauto_extra_prep",true),true); $tab=(array)$e;'
           '  array_walk_recursive($tab, function($v,$k) use (&$s){ if($k==="seat_count") $s[$v]=($s[$v]??0)+1; });'
           '}'
           'foreach([&$b,&$f,&$d,&$s] as &$kosz){ arsort($kosz); }'
           '$o[$n]=["sztuk"=>count($q->posts),"body"=>$b?array_key_first($b):"",'
           '"fuel"=>$f?array_key_first($f):"","drive"=>$d?array_key_first($d):"",'
           '"seats"=>$s?array_key_first($s):""];'
           '} echo json_encode($o);') % termy
    r = subprocess.run(['wp', 'eval', kod], cwd=WP, capture_output=True, text=True)
    try:
        return json.loads(r.stdout.strip().splitlines()[-1])
    except Exception:
        sys.exit(f'baza nie oddała faktów: {r.stderr[:300]}')


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--pokaz', action='store_true')
    ap.add_argument('--wgraj', action='store_true')
    a = ap.parse_args()
    if not (a.pokaz or a.wgraj):
        ap.error('podaj --pokaz albo --wgraj')

    f_all = fakty()
    stan = api.stan_wczytaj(STAN)
    wideo = api.stan_wczytaj('meta-wideo.json').get('wgrane', {})

    statusy = {}
    if a.wgraj:
        d, e = api.get(stan['zestaw_vid'] + '/ads?fields=id,status&limit=50')
        if e:
            sys.exit(f'nie mogę odczytać statusów reklam: {e}')
        statusy = {x['id']: x['status'] for x in d['data']}

    for klucz, (term, land, hk, dk) in MODELE.items():
        f = f_all.get(term)
        if not f:
            print(f'{klucz}: brak serii „{term}" w bazie — pomijam'); continue
        naglowek = H[hk][0]
        tekst = ' '.join(D[k][0] for k in dk)
        landing = BAZA + land
        print(f'\n=== {klucz}  [{term}: {f["sztuk"]} szt.]')
        print(f'  nagłówek: {naglowek}   ({H[hk][1]}% konw/klik, {H[hk][2]} kl)')
        print(f'  tekst:    {tekst}')
        print(f'  podpis:   {PODPIS}   →  {landing}')

        war = STRAZNIK.get(hk)
        if war and not war[0](f):
            print(f'  STRAŻNIK ODRZUCA: nagłówek zakłada {war[1]}, a baza mówi '
                  f'body={f["body"]} fuel={f["fuel"]} drive={f["drive"]} miejsca={f["seats"]}')
            continue
        if war:
            print(f'  strażnik OK — baza potwierdza {war[1]}')
        if not a.wgraj:
            continue

        kod = api.landing_zyje(landing)
        if kod != 200:
            print(f'  landing zwraca {kod} — pomijam'); continue
        vid = (wideo.get(klucz) or {}).get('video_id')
        if not vid:
            print('  brak wgranego wideo — pomijam'); continue
        d, _ = api.get(f'{vid}/thumbnails?fields=uri,is_preferred&limit=5')
        mini = [t for t in (d or {}).get('data', []) if t.get('is_preferred')] or (d or {}).get('data', [])
        spec = {'page_id': api.PAGE, 'instagram_user_id': api.IG, 'video_data': {
            'video_id': vid, 'image_url': mini[0]['uri'], 'title': naglowek, 'message': tekst,
            'link_description': PODPIS,
            'call_to_action': {'type': 'LEARN_MORE', 'value': {'link': landing}}}}
        r, e = api.post(f'{api.ACT}/adcreatives',
                        {'name': f'[VID] {klucz} v3-ads', 'object_story_spec': json.dumps(spec)},
                        waliduj=False)
        if e:
            print('  BŁĄD kreacji:', e); continue
        kre = r['id']
        stara = stan.get(f'ad_{klucz}')
        status = statusy.get(stara, 'PAUSED')
        r, e = api.post(f'{api.ACT}/ads', {
            'name': f'[VID] {klucz}', 'adset_id': stan['zestaw_vid'],
            'creative': json.dumps({'creative_id': kre}), 'status': status}, waliduj=False)
        if e:
            print('  BŁĄD reklamy:', e); continue
        if stara:
            _, e2 = api.post(stara, {'status': 'ARCHIVED'}, waliduj=False)
            if e2:
                print('  UWAGA: stara reklama NIE poszła do archiwum:', e2)
        stan[f'ad_{klucz}'] = r['id']
        stan[f'kreacja_{klucz}'] = kre
        api.stan_zapisz(STAN, stan)
        print(f'  nowa reklama {r["id"]} ({status}), stara {stara} do archiwum')


if __name__ == '__main__':
    main()
