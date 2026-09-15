#!/usr/bin/env python3
"""Przebudowa kampanii Meta 15.09.2026 wg quizu Janka (poza [KAT], który buduje buduj_kat.py).

  [FOTO]  NOWY zestaw „Karuzele PL M 30-60 — Contact” (15 zł): M 30–60 twardo (bez Advantage+), tylko
          aktualności FB + IG, cel Contact; te same kreacje kadr 1 + kadr 2; wykluczenia i geo jak w starym.
          Stary zestaw 120248942223140243 (ViewContent) na pauzę — celu nie da się zmienić (3260011).
  [RMKT]  „Oglądane — 30 dni” 120248991023860243: 15 zł (z 7), tylko aktualności FB + IG.
          „Na placu w Polsce” 120248991026870243: pauza.
  [VID] 120248809387930243 i [POST] 120248941749890243: pauza kampanii.

    python3 scripts/social/przebudowa_2026_09_15.py             # sonda validate_only
    python3 scripts/social/przebudowa_2026_09_15.py --wykonaj   # na żywo
"""
import argparse, json, os, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import meta_api as api

FOTO, RMKT_OGL, RMKT_PLAC = '120248942223140243', '120248991023860243', '120248991026870243'
FOTO_KAMP = '120248942206780243'
STAN = 'meta-przebudowa-2026-09-15.json'
FOTO_KREACJE = []  # uzupełniane niżej z żywych reklam kadr 1 / kadr 2
FOTO_ADY = {'Cała oferta — kadr 1': '120248942700600243', 'Cała oferta — kadr 2': '120248942701580243'}
VID, POST = '120248809387930243', '120248941749890243'


def targeting(sid):
    d, e = api.get(f'{sid}?fields=targeting')
    if e:
        sys.exit(f'{sid}: {e}')
    t = d['targeting']
    for x in t.get('excluded_custom_audiences', []) + t.get('custom_audiences', []):
        x.pop('name', None)
    return t


def main():
    ap = argparse.ArgumentParser(); ap.add_argument('--wykonaj', action='store_true')
    waliduj = not ap.parse_args().wykonaj
    print('SONDA — nic się nie zmieni\n' if waliduj else 'NA ŻYWO\n')

    for n, ad in FOTO_ADY.items():
        d, e = api.get(f'{ad}?fields=creative')
        FOTO_KREACJE.append((n, d['creative']['id']))
    tf = targeting(FOTO)
    tf.update({'age_min': 30, 'age_max': 60, 'genders': [1], 'facebook_positions': ['feed'],
               'instagram_positions': ['stream'], 'targeting_automation': {'advantage_audience': 0}})
    to = targeting(RMKT_OGL)
    to.update({'facebook_positions': ['feed'], 'instagram_positions': ['stream']})

    # [FOTO]: zdarzenia konwersji nie da się zmienić w opublikowanym zestawie (3260011) —
    # nowy zestaw z celem Contact, te same kreacje kadr 1 i kadr 2, stary zestaw na pauzę.
    stan = api.stan_wczytaj(STAN)
    if not stan.get('foto_zestaw'):
        r, e = api.post(f'{api.ACT}/adsets', {
            'name': 'Karuzele PL M 30-60 — Contact', 'campaign_id': FOTO_KAMP, 'status': 'ACTIVE',
            'daily_budget': 1500, 'billing_event': 'IMPRESSIONS', 'optimization_goal': 'OFFSITE_CONVERSIONS',
            'bid_strategy': 'LOWEST_COST_WITHOUT_CAP', 'destination_type': 'WEBSITE',
            'attribution_spec': json.dumps([{'event_type': 'CLICK_THROUGH', 'window_days': 7}]),  # jak stary zestaw
            'dsa_beneficiary': api.DSA_PODMIOT, 'dsa_payor': api.DSA_PODMIOT,
            'promoted_object': json.dumps({'pixel_id': api.PIKSEL, 'custom_event_type': 'CONTACT'}),
            'targeting': json.dumps(tf)}, waliduj=waliduj)
        print(f"  {'[FOTO] nowy zestaw M 30–60 Contact':44} → {json.dumps(r) if r else e}")
        if e:
            sys.exit('BŁĄD — przerywam, kolejne kroki NIE wykonane')
        if not waliduj:
            stan['foto_zestaw'] = r['id']; api.stan_zapisz(STAN, stan)
    zestaw = stan.get('foto_zestaw') or FOTO  # w sondzie reklamy walidujemy pod starym zestawem
    for nazwa, kreacja in FOTO_KREACJE:
        if stan.get(f'foto_ad_{nazwa}'):
            continue
        r, e = api.post(f'{api.ACT}/ads', {'name': f'[FOTO] {nazwa}', 'adset_id': zestaw, 'status': 'ACTIVE',
                                           'creative': json.dumps({'creative_id': kreacja})}, waliduj=waliduj)
        print(f"  {'[FOTO] reklama ' + nazwa:44} → {json.dumps(r) if r else e}")
        if e:
            sys.exit('BŁĄD — przerywam, kolejne kroki NIE wykonane')
        if not waliduj:
            stan[f'foto_ad_{nazwa}'] = r['id']; api.stan_zapisz(STAN, stan)

    kroki = [
        ('[FOTO] stary zestaw (ViewContent): pauza', FOTO, {'status': 'PAUSED'}),
        ('[RMKT] Oglądane: 15 zł, aktualności', RMKT_OGL, {'daily_budget': 1500, 'targeting': json.dumps(to)}),
        ('[RMKT] Na placu: pauza', RMKT_PLAC, {'status': 'PAUSED'}),
        ('[VID] kampania: pauza', VID, {'status': 'PAUSED'}),
        ('[POST] kampania: pauza', POST, {'status': 'PAUSED'}),
    ]
    for opis, ident, dane in kroki:
        r, e = api.post(ident, dane, waliduj=waliduj)
        print(f'  {opis:44} → {json.dumps(r) if r else e}')
        if e and not waliduj:
            sys.exit('BŁĄD na żywo — przerywam, kolejne kroki NIE wykonane')


if __name__ == '__main__':
    main()
