#!/usr/bin/env python3
"""Budowa kampanii wideo na Meta — wszystko w stanie PAUSED, do akceptacji Janka.

Nic się nie wyświetla: kampania, zestaw i reklama powstają wyłącznie jako wstrzymane,
a konto i tak nie ma źródła finansowania. Kreacja jest "dark postem" — nie pojawia się
na Stronie ani na profilu.

    python3 scripts/social/meta_kampania_wideo.py --dry-run
    ZGODA_PUBLIKACJA=<data> python3 scripts/social/meta_kampania_wideo.py --buduj
"""
import argparse, json, os, sys, urllib.error, urllib.parse, urllib.request

TOKEN = '/home/host476470/secrets/meta/tokens/primaauto-portfolio-ruslan.txt'
ACT = 'act_1038563008906171'
PAGE = '1146829831857839'
IG = '17841457773875233'
PIKSEL = '1634147041766916'
DSA_PODMIOT = 'Prima Auto'   # dokładna nazwa zweryfikowanego portfolio (verification_status: verified)
GRAPH = 'https://graph.facebook.com/v21.0/'
STAN = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'state', 'meta-kampanie.json')

KAMPANIA = {
    'nazwa': '[VID] Auta z Chin — nowi odbiorcy',
    'video_id': '1389329476599667',              # BYD Leopard 5 czarny, pion 9:16
    'link': 'https://primaauto.com.pl/samochody/byd/leopard-5/#oferty',
    'budzet_dz_gr': 2500,                        # 25 zł/dzień w groszach
    'naglowek': 'BYD Leopard 5 — terenowy SUV z Chin',
    'tekst': ('Terenowy SUV w hybrydzie plug-in, sprowadzany bezpośrednio z Chin. '
              'Weryfikujemy każde auto przed zakupem, zajmujemy się transportem, cłem, '
              'homologacją i rejestracją w Polsce. 21 egzemplarzy w ofercie.'),
    'opis': 'Prima-Auto — bezpośredni importer aut z Chin',
    'cta': 'LEARN_MORE',
    'kraj': 'PL', 'wiek_od': 25, 'wiek_do': 55,
}


def token():
    return open(TOKEN).read().strip()


def wywolaj(sciezka, dane, tok):
    dane = dict(dane, access_token=tok)
    req = urllib.request.Request(GRAPH + sciezka, data=urllib.parse.urlencode(dane).encode())
    try:
        return json.load(urllib.request.urlopen(req)), None
    except urllib.error.HTTPError as e:
        b = json.load(e).get('error', {})
        return None, (f"{b.get('code')}/{b.get('error_subcode')} · "
                      f"{b.get('error_user_title') or b.get('message', '')} | "
                      f"{(b.get('error_user_msg') or '')[:200]}")


def miniatura(video_id, tok):
    """Meta wymaga miniatury dla kreacji wideo — bierzemy tę, którą sama wygenerowała."""
    u = f"{GRAPH}{video_id}/thumbnails?access_token={urllib.parse.quote(tok)}"
    try:
        d = json.load(urllib.request.urlopen(u)).get('data', [])
        pref = [t for t in d if t.get('is_preferred')] or d
        return pref[0]['uri'] if pref else None
    except Exception:
        return None


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--dry-run', action='store_true')
    ap.add_argument('--buduj', action='store_true')
    a = ap.parse_args()
    if not (a.dry_run or a.buduj):
        ap.error('podaj --dry-run albo --buduj')

    k = KAMPANIA
    tok = token()
    mini = miniatura(k['video_id'], tok)

    print(f"KAMPANIA   {k['nazwa']}")
    print(f"CEL        konwersje — zdarzenie Contact (piksel {PIKSEL})")
    print(f"WIDEO      {k['video_id']}")
    print(f"MINIATURA  {'jest' if mini else 'BRAK — Meta odrzuci kreację'}")
    print(f"LINK       {k['link']}")
    print(f"BUDŻET     {k['budzet_dz_gr']/100:.0f} zł/dzień")
    print(f"TARGET     {k['kraj']}, {k['wiek_od']}–{k['wiek_do']} lat")
    print(f"NAGŁÓWEK   {k['naglowek']}")
    print(f"TEKST      {k['tekst']}")
    print(f"STATUS     PAUSED na każdym poziomie")
    if a.dry_run:
        print("\n[dry-run] nic nie wysłano")
        return

    zrobione = {}

    print("\n--- 1. KAMPANIA ---")
    r, e = wywolaj(f'{ACT}/campaigns', {
        'name': k['nazwa'], 'objective': 'OUTCOME_LEADS', 'status': 'PAUSED',
        'is_adset_budget_sharing_enabled': 'false', 'special_ad_categories': '[]'}, tok)
    if e:
        print('   BŁĄD:', e); return
    camp = r['id']; zrobione['kampania'] = camp
    print('   OK', camp)

    print("--- 2. ZESTAW REKLAM ---")
    r, e = wywolaj(f'{ACT}/adsets', {
        'name': 'Leopard 5 — PL 25-55', 'campaign_id': camp, 'status': 'PAUSED',
        'daily_budget': str(k['budzet_dz_gr']),
        'billing_event': 'IMPRESSIONS', 'optimization_goal': 'OFFSITE_CONVERSIONS',
        'bid_strategy': 'LOWEST_COST_WITHOUT_CAP', 'destination_type': 'WEBSITE',
        'promoted_object': json.dumps({'pixel_id': PIKSEL, 'custom_event_type': 'CONTACT'}),
        # DSA (unijny akt o usługach cyfrowych) — od 2024 Meta wymaga w UE wskazania,
        # kto na reklamie korzysta i kto za nią płaci. Bez tych dwóch pól zestaw reklam
        # jest odrzucany: 100/3858081 „Nie wskazano reklamodawcy".
        'dsa_beneficiary': DSA_PODMIOT,
        'dsa_payor': DSA_PODMIOT,
        'targeting': json.dumps({
            'geo_locations': {'countries': [k['kraj']]},
            'age_min': k['wiek_od'], 'age_max': k['wiek_do'],
            'publisher_platforms': ['facebook', 'instagram'],
        })}, tok)
    if e:
        print('   BŁĄD:', e)
        print('\nSTAN:', json.dumps(zrobione)); return
    adset = r['id']; zrobione['zestaw'] = adset
    print('   OK', adset)

    print("--- 3. KREACJA (dark post — nie trafia na Stronę) ---")
    spec = {'page_id': PAGE, 'instagram_user_id': IG, 'video_data': {
        'video_id': k['video_id'], 'message': k['tekst'], 'title': k['naglowek'],
        'link_description': k['opis'], 'image_url': mini,
        'call_to_action': {'type': k['cta'], 'value': {'link': k['link']}}}}
    r, e = wywolaj(f'{ACT}/adcreatives', {
        'name': 'Leopard 5 — wideo 9:16', 'object_story_spec': json.dumps(spec)}, tok)
    if e:
        print('   BŁĄD:', e)
        print('\nSTAN:', json.dumps(zrobione)); return
    kre = r['id']; zrobione['kreacja'] = kre
    print('   OK', kre)

    print("--- 4. REKLAMA ---")
    r, e = wywolaj(f'{ACT}/ads', {
        'name': 'Leopard 5 — wideo 9:16', 'adset_id': adset,
        'creative': json.dumps({'creative_id': kre}), 'status': 'PAUSED'}, tok)
    if e:
        print('   BŁĄD:', e)
        print('\nSTAN:', json.dumps(zrobione)); return
    zrobione['reklama'] = r['id']
    print('   OK', r['id'])

    os.makedirs(os.path.dirname(STAN), exist_ok=True)
    json.dump(zrobione, open(STAN, 'w'), ensure_ascii=False, indent=2)
    print('\nGOTOWE — wszystko PAUSED. Zapisane w', STAN)


if __name__ == '__main__':
    main()
