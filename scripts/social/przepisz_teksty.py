#!/usr/bin/env python3
"""Przepisanie tekstów [VID] — cechy aut z bazy, nie z głowy modelu.

Powód powstania: 04.09 cztery opisy były zmyślone (Z9 GT „kombi", Jetour T2 „PHEV" przy
serii benzynowej, Exeed VX i Denza N9 „siedmioosobowe" przy sześciu miejscach), a wszystkie
dziesięć zawierało słowo „homologacja", którego w treściach do klienta nie używamy
(feedback_nigdy_slowo_homologacja — zawsze „przygotowanie do rejestracji").

Każda cecha w tekście pochodzi z pola w bazie: `fuel`, `body`, `drive` z meta oferty
i `seat_count` z `_asiaauto_extra_prep`. Czego nie ma w bazie, tego skrypt nie napisze.

    python3 scripts/social/przepisz_teksty.py --pokaz     # tylko wypisuje teksty
    python3 scripts/social/przepisz_teksty.py --wgraj     # nowe kreacje + podmiana reklam
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

# klucz wideo → (nazwa handlowa, term `serie`, landing)
# Jetour: na sesji stał C-DM (hybryda, 5 miejsc, na placu w Rzeszowie) — nie seria benzynowa.
MODELE = {
    'jetour-t2':           ('Jetour T2 C-DM', 'T2 C-DM',                    '/samochody/jetour/t2-c-dm/#oferty'),
    'exeed-vx':            ('Exeed VX',       'VX',                         '/samochody/exeed/vx/#oferty'),
    'g318':                ('Deepal G318',    'G318',                       '/samochody/deepal/g318/#oferty'),
    'leopard-5-czarny':    ('BYD Leopard 5',  'Leopard 5 (Denza B5)',       '/samochody/byd/leopard-5/#oferty'),
    'leopard-5-niebieski': ('BYD Leopard 5',  'Leopard 5 (Denza B5)',       '/samochody/byd/leopard-5/#oferty'),
    'leopard-7':           ('BYD Leopard 7',  'Leopard 7 (Tai 7) FCB, PHEV','/samochody/byd/leopard-7/#oferty'),
    'z9-gt':               ('Denza Z9 GT',    'Z9 GT DM-i',                 '/samochody/denza/z9-gt-dm-i/#oferty'),
    'shark-6':             ('BYD Shark 6',    'Shark 6',                    '/samochody/byd/shark-6/#oferty'),
    'lynk-900':            ('Lynk & Co 900',  '900',                        '/samochody/lynk-co/900/#oferty'),
    'n9':                  ('Denza N9',       'N9 DM-i',                    '/samochody/denza/n9-dm-i/#oferty'),
}

# Zdanie zamykające — bez słowa „homologacja". „Gotowe do rejestracji" to przy okazji jeden
# z lepiej konwertujących nagłówków w Google Ads (2,05% konw/klik).
OGON = ('Transport, cło i przygotowanie do rejestracji po naszej stronie — '
        'auto gotowe do rejestracji w Polsce.')
STOPKA = 'Prima-Auto — bezpośredni importer aut z Chin'
NADWOZIE = {'suv': 'SUV', 'sedan': 'sedan', 'liftback': 'liftback', 'hatchback': 'hatchback',
            'pickup': 'pickup', 'coupe': 'coupé', 'kombi': 'kombi', 'van': 'van'}
PALIWO = {'phev': 'hybryda plug-in', 'erev': 'napęd elektryczny z agregatem',
          'petrol': 'benzyna', 'ev': 'napęd elektryczny', 'hybrid': 'hybryda',
          'diesel': 'diesel'}


def fakty():
    """Nadwozie, paliwo, napęd, miejsca i cena wejścia — wszystko z bazy, wartość najczęstsza."""
    termy = ','.join('"%s"' % m[1].replace('"', '') for m in MODELE.values())
    kod = ('$o=[]; foreach ([%s] as $n) { $t=get_term_by("name",$n,"serie"); if(!$t) continue; '
           '$q=new WP_Query(["post_type"=>"listings","post_status"=>"publish","posts_per_page"=>-1,'
           '"fields"=>"ids","tax_query"=>[["taxonomy"=>"serie","field"=>"term_id","terms"=>$t->term_id]]]);'
           '$c=[]; $b=[]; $f=[]; $d=[]; $s=[];'
           'foreach($q->posts as $id){'
           '  $p=(int)get_post_meta($id,"price",true); if($p>0)$c[]=$p;'
           '  foreach(["body"=>&$b,"fuel"=>&$f,"drive"=>&$d] as $k=>&$kosz){'
           '    $v=get_post_meta($id,$k,true); if($v)$kosz[$v]=($kosz[$v]??0)+1; }'
           '  $e=json_decode(get_post_meta($id,"_asiaauto_extra_prep",true),true); $tab=(array)$e;'
           '  array_walk_recursive($tab, function($v,$k) use (&$s){ if($k==="seat_count") $s[$v]=($s[$v]??0)+1; });'
           '}'
           'foreach([&$b,&$f,&$d,&$s] as &$kosz){ arsort($kosz); }'
           '$o[$n]=["cena"=>$c?min($c):0,"sztuk"=>count($q->posts),'
           '"body"=>$b?array_key_first($b):"","fuel"=>$f?array_key_first($f):"",'
           '"drive"=>$d?array_key_first($d):"","seats"=>$s?array_key_first($s):""];'
           '} echo json_encode($o);') % termy
    r = subprocess.run(['wp', 'eval', kod], cwd=WP, capture_output=True, text=True)
    try:
        return json.loads(r.stdout.strip().splitlines()[-1])
    except Exception:
        sys.exit(f'baza nie oddała faktów: {r.stderr[:300]}')


def zapas_globalny():
    kod = ('$w=new WP_Query(["post_type"=>"listings","post_status"=>"publish","posts_per_page"=>1,'
           '"fields"=>"ids"]); $f=function($v){ $q=new WP_Query(["post_type"=>"listings",'
           '"post_status"=>"publish","posts_per_page"=>1,"fields"=>"ids","meta_query"=>[['
           '"key"=>"_asiaauto_reservation_status","value"=>$v]]]); return $q->found_posts; };'
           'echo json_encode(["ofert"=>$w->found_posts,"plac"=>$f("on_lot"),"droga"=>$f("in_transit")]);')
    r = subprocess.run(['wp', 'eval', kod], cwd=WP, capture_output=True, text=True)
    return json.loads(r.stdout.strip().splitlines()[-1])


def teksty(klucz, f, g):
    nazwa, term, land = MODELE[klucz]
    cechy = [NADWOZIE.get(f['body'], f['body']), PALIWO.get(f['fuel'], f['fuel'])]
    if f['drive'] == 'awd':
        cechy.append('napęd 4x4')
    if f['seats']:
        cechy.append(f"{f['seats']} miejsc")
    cena = f"{f['cena'] // 1000} 000 zł"
    ofert = f"{g['ofert'] // 100 * 100:,}".replace(',', ' ')
    naglowek = f'{nazwa} — od {cena}'
    tekst = (f'{STOPKA}. {nazwa}: ' + ', '.join(c for c in cechy if c) + f', od {cena}. '
             f"Ponad {ofert} ofert do sprowadzenia, {g['plac']} aut na placu w Rzeszowie, "
             f"{g['droga']} w drodze do Polski. {OGON}")
    return naglowek, tekst, BAZA + land


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--pokaz', action='store_true')
    ap.add_argument('--wgraj', action='store_true')
    a = ap.parse_args()
    if not (a.pokaz or a.wgraj):
        ap.error('podaj --pokaz albo --wgraj')

    f_all, g = fakty(), zapas_globalny()
    stan = api.stan_wczytaj(STAN)
    wideo = api.stan_wczytaj('meta-wideo.json').get('wgrane', {})

    for klucz, (nazwa, term, land) in MODELE.items():
        f = f_all.get(term)
        if not f:
            print(f'{klucz}: brak serii „{term}" w bazie — pomijam'); continue
        naglowek, tekst, landing = teksty(klucz, f, g)
        kod = api.landing_zyje(landing)
        print(f'\n=== {klucz}  [{term}: {f["sztuk"]} szt., landing {kod}]')
        print(f'  {naglowek}')
        print(f'  {tekst}')
        if not a.wgraj:
            continue
        if kod != 200:
            print('  landing nie zwraca 200 — pomijam'); continue
        vid = (wideo.get(klucz) or {}).get('video_id')
        if not vid:
            print('  brak wgranego wideo — pomijam'); continue
        d, _ = api.get(f'{vid}/thumbnails?fields=uri,is_preferred&limit=5')
        mini = [t for t in (d or {}).get('data', []) if t.get('is_preferred')] or (d or {}).get('data', [])
        spec = {'page_id': api.PAGE, 'instagram_user_id': api.IG, 'video_data': {
            'video_id': vid, 'image_url': mini[0]['uri'], 'title': naglowek, 'message': tekst,
            'link_description': STOPKA,
            'call_to_action': {'type': 'LEARN_MORE', 'value': {'link': landing}}}}
        r, e = api.post(f'{api.ACT}/adcreatives',
                        {'name': f'[VID] {klucz} v2', 'object_story_spec': json.dumps(spec)},
                        waliduj=False)
        if e:
            print('  BŁĄD kreacji:', e); continue
        kre = r['id']
        r, e = api.post(f'{api.ACT}/ads', {
            'name': f'[VID] {klucz}', 'adset_id': stan['zestaw_vid'],
            'creative': json.dumps({'creative_id': kre}), 'status': 'PAUSED'}, waliduj=False)
        if e:
            print('  BŁĄD reklamy:', e); continue
        stara = stan.get(f'ad_{klucz}')
        if stara:
            api.post(stara, {'status': 'ARCHIVED'}, waliduj=False)
        stan[f'ad_{klucz}'] = r['id']
        stan[f'kreacja_{klucz}'] = kre
        api.stan_zapisz(STAN, stan)
        print(f'  nowa reklama {r["id"]}, stara {stara} do archiwum')


if __name__ == '__main__':
    main()
