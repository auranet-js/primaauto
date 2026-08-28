#!/usr/bin/env python3
"""Rotacja kampanii [POST] — promowanie postów, które Andrzej już opublikował.

Narzędzie NIE wybiera samo. Kładzie obok siebie dwie tabele — co dziś pracuje i z jakim
wynikiem, oraz co świeżego jest do wzięcia — a decyzję „co wchodzi, co wychodzi" podejmuje
Janek. Wstawienie i wyjęcie to osobne, jawne polecenia z podanym identyfikatorem.

    python3 scripts/social/rotator_postow.py                    # zestawienie do decyzji
    python3 scripts/social/rotator_postow.py --wstaw 1146829831857839_1221121171954160
    python3 scripts/social/rotator_postow.py --wyjmij 1203456789

Kreacja nie powstaje tutaj: bierzemy gotowy post przez `object_story_id`, więc reklama
dokłada mu płatne wyświetlenia, a dorobek organiczny i komentarze zostają przy tym samym
obiekcie. Odwrotnie niż `rotator_wideo.py`, który wgrywa plik z Dysku.

Czego w dolnej tabeli NIE ma: zasięgu i zaangażowania organicznego. System User nie ma
scope'u `read_insights` (sprawdzone 28.08) — `{post}/insights` odrzuca każdą metrykę.
Do czasu rozszerzenia scope'ów świeży post opisujemy tym, co widać z bazy: oferta, zapas
w serii, cena, żywy landing.
"""
import argparse
import json
import os
import re
import subprocess
import sys
from datetime import datetime, timezone

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import meta_api as api

WP = '/home/host476470/domains/primaauto.com.pl/public_html'
STAN = 'meta-posty.json'
SLOTY = 3          # ile kreacji trzymamy równolegle w kampanii — 2 albo 3
SWIEZE = 10        # ile ostatnich postów pokazujemy do wyboru


def posty(limit=SWIEZE):
    pola = 'id,created_time,message,permalink_url,is_eligible_for_promotion,attachments{type}'
    d, e = api.get(f'{api.PAGE}/published_posts?fields={pola}&limit={limit}', api.page_token())
    if e:
        sys.exit(f'nie mogę odczytać postów Strony: {e}')
    dane = d.get('data', [])
    for p in dane:
        m = re.findall(r'https?://primaauto\.com\.pl/oferta/[^\s<>"\)]+', p.get('message') or '')
        p['oferta_url'] = (m[0].rstrip('/') + '/') if m else None
        szukaj = re.search(r'-(\d+)/?$', m[0]) if m else None
        p['oferta_id'] = int(szukaj.group(1)) if szukaj else None
    return dane


def oferty_z_bazy(ids):
    """Zapas w serii i cena — jedno wywołanie WP-CLI na wszystkie oferty naraz."""
    ids = [i for i in ids if i]
    if not ids:
        return {}
    kod = '''
$out = [];
foreach ([%s] as $id) {
  $p = get_post($id);
  if (!$p || $p->post_type !== "listings") { continue; }
  $zapas = 0; $serie = "";
  foreach (wp_get_post_terms($p->ID, "serie") as $t) {
    $q = new WP_Query(["post_type"=>"listings","post_status"=>"publish","posts_per_page"=>1,
      "tax_query"=>[["taxonomy"=>"serie","field"=>"term_id","terms"=>$t->term_id]],"fields"=>"ids"]);
    if ($q->found_posts > $zapas) { $zapas = $q->found_posts; $serie = $t->name; }
  }
  $out[$id] = ["serie"=>$serie, "zapas"=>$zapas, "status"=>$p->post_status,
               "cena"=>(int) get_post_meta($p->ID, "price", true),
               "rezerwacja"=>get_post_meta($p->ID, "_asiaauto_reservation_status", true)];
}
echo json_encode($out);
''' % ','.join(str(int(i)) for i in ids)
    r = subprocess.run(['wp', 'eval', kod], cwd=WP, capture_output=True, text=True)
    try:
        return {str(k): v for k, v in json.loads(r.stdout.strip().splitlines()[-1]).items()}
    except Exception:
        print('   (baza nie odpowiedziała — jadę bez zapasu i ceny)', r.stderr[:120].strip())
        return {}


def wyniki_reklamy(ad_id):
    """Ads Insights dla jednej reklamy — od początku jej życia."""
    pola = 'impressions,reach,clicks,ctr,spend,actions'
    d, e = api.get(f'{ad_id}/insights?fields={pola}&date_preset=maximum')
    if e or not (d or {}).get('data'):
        return {}
    w = d['data'][0]
    akcje = {a['action_type']: a['value'] for a in w.get('actions', [])}
    return {'wysw': w.get('impressions', '0'), 'zasieg': w.get('reach', '0'),
            'kliki': w.get('clicks', '0'), 'ctr': w.get('ctr', '0'),
            'koszt': w.get('spend', '0'),
            'kontakt': akcje.get('offsite_conversion.fb_pixel_custom') or akcje.get('onsite_conversion.messaging_conversation_started_7d') or '0',
            'zaang': akcje.get('post_engagement', '0')}


def sekcja_aktywne(stan):
    aktywne = stan.get('aktywne', [])
    print(f'\n=== CO DZIŚ PRACUJE ({len(aktywne)}/{SLOTY} slotów) ===')
    if not aktywne:
        print('nic — kampania [POST] jeszcze nie ruszyła')
        return
    print(f"{'od dni':>6}  {'reklama':>16}  {'wyśw.':>8} {'zasięg':>8} {'kliki':>6} {'CTR':>6} {'koszt':>8} {'zaang.':>7}  post")
    for a in aktywne:
        dni = (datetime.now(timezone.utc) - datetime.fromisoformat(a['od'])).days
        w = wyniki_reklamy(a['ad_id']) if a.get('ad_id') else {}
        print(f"{dni:>6}  {a.get('ad_id','?'):>16}  {w.get('wysw','–'):>8} {w.get('zasieg','–'):>8} "
              f"{w.get('kliki','–'):>6} {w.get('ctr','–')[:5]:>6} {w.get('koszt','–'):>8} "
              f"{w.get('zaang','–'):>7}  {a['post_id']}")
        if a.get('oferta'):
            print(f"        ↳ {a['oferta']}")
    print('\n  wyjęcie:  --wyjmij <reklama>')


def sekcja_swieze(stan):
    dane = posty()
    oferty = oferty_z_bazy([p['oferta_id'] for p in dane])
    historia = stan.get('historia', {})
    aktywne_id = {a['post_id'] for a in stan.get('aktywne', [])}

    print(f'\n=== ŚWIEŻY MATERIAŁ — ostatnie {len(dane)} postów ===')
    print(f"{'data':>10}  {'typ':>12}  {'seria':>18} {'zapas':>5} {'cena':>9} {'land':>5}  uwagi")
    for p in dane:
        att = (p.get('attachments', {}).get('data') or [{}])[0].get('type', '?')
        d = oferty.get(str(p['oferta_id']), {}) if p['oferta_id'] else {}
        kod = api.landing_zyje(p['oferta_url']) if p['oferta_url'] else '–'
        uwagi = []
        if p['id'] in aktywne_id:
            uwagi.append('PRACUJE TERAZ')
        elif p['id'] in historia:
            uwagi.append('był promowany')
        if not p.get('is_eligible_for_promotion'):
            uwagi.append('Meta nie dopuszcza')
        if not p['oferta_url']:
            uwagi.append('bez linku — tylko zaangażowanie')
        if kod not in (200, '–'):
            uwagi.append(f'landing {kod} — sprzedane albo zły slug')
        if d.get('rezerwacja'):
            uwagi.append(f"rezerwacja {d['rezerwacja']}")
        print(f"{p['created_time'][:10]:>10}  {att:>12}  {(d.get('serie') or '–')[:18]:>18} "
              f"{str(d.get('zapas','–')):>5} {str(d.get('cena') or '–'):>9} {str(kod):>5}  {'; '.join(uwagi)}")
        tytul = (p.get('message') or '').strip().split('\n')[0][:70] or '(bez treści)'
        print(f"            {tytul}")
        print(f"            {p['id']}")
    print('\n  wstawienie:  --wstaw <post>')
    print('  zasięg organiczny niedostępny — System User bez scope\'u read_insights')


def infrastruktura():
    """Czego brakuje, żeby reklama w ogóle miała gdzie stanąć."""
    braki, kampania, zestaw = [], None, None
    d, _ = api.get(f'{api.ACT}/campaigns?fields=id,name,objective,status&limit=50')
    for c in (d or {}).get('data', []):
        if c['name'].startswith('[POST]'):
            kampania = c
    if kampania:
        d, _ = api.get(f"{kampania['id']}/adsets?fields=id,name,status&limit=20")
        zestaw = ((d or {}).get('data') or [None])[0]
        if not zestaw:
            braki.append('zestaw reklam w [POST] nie istnieje')
    else:
        braki.append('kampania [POST] (OUTCOME_TRAFFIC) nie istnieje')
    _, e = api.post(f'{api.ACT}/adsets', {
        'name': 'SONDA', 'campaign_id': (kampania or {}).get('id', '120248809387930243'),
        'status': 'PAUSED', 'daily_budget': '1500', 'billing_event': 'IMPRESSIONS',
        'bid_strategy': 'LOWEST_COST_WITHOUT_CAP', 'optimization_goal': 'LINK_CLICKS',
        'destination_type': 'WEBSITE', 'dsa_beneficiary': api.DSA_PODMIOT, 'dsa_payor': api.DSA_PODMIOT,
        'targeting': json.dumps({'geo_locations': {'countries': ['PL']}})})
    if e and '3858196' in e:
        braki.append('weryfikacja DSA — żadna reklama do PL nie powstanie')
    return kampania, zestaw, braki


def wstaw(post_id, stan):
    kampania, zestaw, braki = infrastruktura()
    if braki:
        print('WSTRZYMANE — nic nie wysłano:')
        for b in braki:
            print('  ·', b)
        return
    if len(stan.get('aktywne', [])) >= SLOTY:
        print(f'wszystkie {SLOTY} sloty zajęte — najpierw --wyjmij')
        return
    r, e = api.post(f'{api.ACT}/adcreatives', {'name': f'[POST] {post_id[-8:]}',
                                               'object_story_id': post_id}, waliduj=False)
    if e:
        return print('BŁĄD kreacji:', e)
    kre = r['id']
    r, e = api.post(f'{api.ACT}/ads', {'name': f'[POST] {post_id[-8:]}', 'adset_id': zestaw['id'],
                                       'creative': json.dumps({'creative_id': kre}),
                                       'status': 'PAUSED'}, waliduj=False)
    if e:
        return print('BŁĄD reklamy:', e)
    stan.setdefault('aktywne', []).append({
        'post_id': post_id, 'ad_id': r['id'], 'kreacja': kre,
        'od': datetime.now(timezone.utc).isoformat()})
    api.stan_zapisz(STAN, stan)
    print(f"WSTAWIONE {post_id} → reklama {r['id']}, status PAUSED.")
    print('Włączenie z budżetem to osobna decyzja — narzędzie tego nie robi.')


def wyjmij(ad_id, stan):
    aktywne = stan.get('aktywne', [])
    poz = [a for a in aktywne if a.get('ad_id') == ad_id]
    if not poz:
        return print(f'reklamy {ad_id} nie ma wśród aktywnych: '
                     f"{[a.get('ad_id') for a in aktywne]}")
    a = poz[0]
    r, e = api.post(ad_id, {'status': 'PAUSED'}, waliduj=False)
    print('PAUZA', ad_id, '→', r or e)
    stan.setdefault('historia', {})[a['post_id']] = {
        'od': a['od'], 'do': datetime.now(timezone.utc).isoformat(),
        'ad_id': ad_id, 'wynik': wyniki_reklamy(ad_id)}
    aktywne.remove(a)
    stan['aktywne'] = aktywne
    api.stan_zapisz(STAN, stan)
    print('wyjęte, wynik zapisany w historii')


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--wstaw', metavar='POST_ID')
    ap.add_argument('--wyjmij', metavar='AD_ID')
    a = ap.parse_args()
    stan = api.stan_wczytaj(STAN, {'aktywne': [], 'historia': {}})
    if a.wstaw:
        return wstaw(a.wstaw, stan)
    if a.wyjmij:
        return wyjmij(a.wyjmij, stan)
    sekcja_aktywne(stan)
    sekcja_swieze(stan)
    _, _, braki = infrastruktura()
    if braki:
        print('\n=== CZEGO BRAKUJE, ŻEBY COKOLWIEK RUSZYŁO ===')
        for b in braki:
            print('  ·', b)


if __name__ == '__main__':
    main()
