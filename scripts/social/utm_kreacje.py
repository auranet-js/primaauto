#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Doklejenie UTM-ów do kreacji Meta — bo bez nich GA4 nie odróżnia reklamy od reklamy.

Stan wyjściowy (04.09.2026): wszystkie żywe reklamy prowadzą na czyste adresy, więc ruch
z Mety wpada do GA4 jako `l.facebook.com / referral` — bez kampanii, bez podziału
[VID] / [FOTO], bez szansy przypisania leada.

ZMIERZONE SONDĄ na nieżywej reklamie `[VID] jetour-t2` (04.09, ad 120248942515210243):
  · `url_tags` NIE DA SIĘ dopisać do istniejącej kreacji — Meta odpowiada 100/1815573
    „Podaj nazwę, status lub powiązane etykiety reklam". Edytowalne są tylko te trzy pola.
  · Nowa kreacja z `url_tags` przechodzi (`{"success": true}` przy validate_only).
  · `object_story_spec` trzeba przepisać BEZ `image_url` — Meta odrzuca komplet
    image_url + image_hash błędem ObjectStorySpecRedundant.
  · Reklamy [POST] (z gotowego posta) TEŻ da się otagować — kreacja z `object_story_id`
    plus `url_tags` przechodzi. Nie trzeba pomiaru po samym pikselu.
  · Konto ma kroczący limit wywołań: seria kreacji pod rząd potrafi dostać
    „Please reduce the amount of data you're asking for" (kod 1). To przejściowe —
    ta sama kreacja przechodzi po ~20 s. Stąd odstęp i ponowienia niżej.
  · Podmiana `creative` w reklamie ZACHOWUJE id reklamy i jej nazwę…
  · …ale wysyła reklamę do PENDING_REVIEW. Na żywej reklamie oznacza to przerwę
    w dostawie na czas przeglądu. Kto tego nie chce, robi nową reklamę obok
    i gasi starą dopiero po zatwierdzeniu.

    python3 scripts/social/utm_kreacje.py --lista        # co jest bez UTM-ów
    python3 scripts/social/utm_kreacje.py --sonda        # validate_only, nic nie zmienia
    python3 scripts/social/utm_kreacje.py --podmien "[VID] z9-gt"
    python3 scripts/social/utm_kreacje.py --podmien wszystkie

Bez `--podmien` skrypt niczego nie zmienia. Podmiana na ŻYWEJ reklamie to decyzja Janka,
nie skutek uboczny uruchomienia narzędzia.
"""
import argparse
import json
import os
import sys
import time

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import meta_api as api

# {{campaign.name}} i {{ad.name}} to makra Mety — podstawia je przy kliknięciu,
# dzięki czemu jeden ciąg działa dla każdej kampanii i każdej reklamy.
TAGI = ('utm_source=facebook&utm_medium=paid_social'
        '&utm_campaign={{campaign.name}}&utm_content={{ad.name}}')

POLA = ('name,effective_status,status,'
        'creative{id,name,url_tags,object_story_spec,degrees_of_freedom_spec,'
        'object_story_id,effective_object_story_id}')


def reklamy(statusy):
    d, e = api.get(f'{api.ACT}/ads?fields={POLA}&limit=100&'
                   f'effective_status={json.dumps(statusy)}')
    if e:
        sys.exit(f'nie mogę odczytać reklam: {e}')
    return d.get('data', [])


def specyfikacja(kreacja):
    """object_story_spec gotowy do wysłania — bez pól, których Meta nie przyjmie z powrotem."""
    oss = json.loads(json.dumps(kreacja.get('object_story_spec') or {}))
    for blok in ('video_data', 'link_data'):
        if blok in oss:
            # image_url razem z image_hash = ObjectStorySpecRedundant; hash jest trwalszy.
            if oss[blok].get('image_hash'):
                oss[blok].pop('image_url', None)
    return oss


# Konto puszcza JEDEN zapis na 30 s (kod 613) i potrafi odbić serię kodem 1
# („reduce the amount of data"). Oba są przejściowe — czeka się i ponawia.
PRZEJSCIOWE = ('613', 'reduce the amount of data')


def ponow(sciezka, dane, waliduj, prob=5):
    for i in range(prob):
        d, e = api.post(sciezka, dane, waliduj=waliduj)
        if not e or not any(x in e for x in PRZEJSCIOWE):
            return d, e
        time.sleep(35)
    return None, e


def podmien(a, naprawde):
    c = a.get('creative') or {}
    oss = specyfikacja(c)
    dane = {'name': c['name'] + ' +utm', 'url_tags': TAGI}
    if oss:
        dane['object_story_spec'] = json.dumps(oss)
    elif c.get('object_story_id') or c.get('effective_object_story_id'):
        # Reklama z gotowego posta: własnej treści nie ma, jest wskaźnik na post Andrzeja.
        dane['object_story_id'] = c.get('object_story_id') or c['effective_object_story_id']
    else:
        return f"{a['name']}: kreacja bez treści i bez posta — pomijam"
    if c.get('degrees_of_freedom_spec'):
        dane['degrees_of_freedom_spec'] = json.dumps(c['degrees_of_freedom_spec'])

    d, e = None, None
    for proba in range(3):                       # kod 1 „reduce the amount of data" = throttling
        d, e = api.post(f'{api.ACT}/adcreatives', dane, waliduj=not naprawde)
        if not e or 'reduce the amount of data' not in e:
            break
        time.sleep(20 * (proba + 1))
    if e:
        return f"{a['name']}: kreacja odrzucona — {e}"
    if not naprawde:
        return f"{a['name']}: przejdzie ({d})"

    d, e = api.post(a['id'], {'creative': json.dumps({'creative_id': d['id']})}, waliduj=False)
    if e:
        return f"{a['name']}: kreacja utworzona, ale podmiana odrzucona — {e}"
    return f"{a['name']}: podmienione, reklama idzie do weryfikacji (id reklamy bez zmian)"


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--lista', action='store_true', help='pokaż reklamy bez url_tags')
    ap.add_argument('--sonda', action='store_true', help='validate_only — nic nie zmienia')
    ap.add_argument('--podmien', metavar='NAZWA', help='nazwa reklamy albo „wszystkie"')
    ap.add_argument('--ze-wstrzymanymi', action='store_true',
                    help='obejmij też reklamy PAUSED (domyślnie tylko ACTIVE)')
    a = ap.parse_args()

    statusy = ['ACTIVE'] + (['PAUSED', 'ADSET_PAUSED', 'CAMPAIGN_PAUSED']
                            if a.ze_wstrzymanymi else [])
    wszystkie = reklamy(statusy)
    bez = [r for r in wszystkie if not (r.get('creative') or {}).get('url_tags')]

    if a.lista or not (a.sonda or a.podmien):
        print(f'{len(bez)} z {len(wszystkie)} reklam bez UTM-ów\n')
        for r in wszystkie:
            c = r.get('creative') or {}
            znak = '—' if not c.get('url_tags') else 'UTM'
            zrodlo = 'post' if c.get('effective_object_story_id') and not c.get('object_story_spec') \
                else 'własna kreacja'
            print(f"  {znak:>3}  {r['name'][:40]:42} {r['effective_status']:15} {zrodlo}")
        return

    cele = bez if a.podmien == 'wszystkie' or a.sonda else [r for r in bez if r['name'] == a.podmien]
    if not cele:
        sys.exit('nie znalazłem takiej reklamy wśród tych bez UTM-ów')

    for i, r in enumerate(cele):
        if i:
            time.sleep(35)                       # jeden zapis na 30 s — limit konta
        print(' ·', podmien(r, naprawde=bool(a.podmien)))


if __name__ == '__main__':
    main()
