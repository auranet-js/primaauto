#!/usr/bin/env python3
"""Naprawa piksela Meta w GTM (2026-09-15) — dotyka WYŁĄCZNIE tagów Meta 27–31 + jednego nowego wyzwalacza.

Problem (v11, 25.08): tag bazowy na „Inicjalizacji". Na /oferta/ `view_item` stoi w dataLayer przed
domyślną zgodą Complianz → piksel odpala BEZ zgody; na pozostałych stronach inicjalizacja wypada po
domyślnej odmowie → piksel nie ładuje się wcale, także u osób ze zgodą.
Diagnoza: docs/sesje/2026-09-15-audyt-meta-przed-po.md, sekcja „Aktualizacja 15.09".

Zmiana:
  27 Base         → All Pages (gtm.js — po aktualizacji zgody, jak w v10), raz na zdarzenie;
                    init + PageView pod strażnikiem window.__paMetaInit (raz na odsłonę)
  28 ViewContent  → nowy wyzwalacz „PV - oferta” (All Pages + Page Path zaczyna się od /oferta/),
                    tag 27 jako setup tag (fbq musi istnieć przed track)
  29–31 Lead/Contact → te same wyzwalacze, tag 27 jako setup tag (kliknięcie na stronie, na której
                    zgodę dano dopiero teraz, i tak dojdzie)
Wymóg zgody (ad_storage + ad_user_data) i wyzwalacze blokujące 35/36 BEZ ZMIAN.
Nie ruszamy: GA4 Tag, GADS Tag, tagów GA4 zdarzeń, 33 GAds RMKT, wyzwalacza 26 (CE view_item — dzieli go 33).

    python3 scripts/gtm-piksel-meta.py              # podgląd planu, nic nie zapisuje
    python3 scripts/gtm-piksel-meta.py --zbuduj     # zmiany w Default Workspace
    python3 scripts/gtm-piksel-meta.py --publikuj   # wersja + publikacja
"""
import argparse
import importlib.util
import json
import os
import sys

_spec = importlib.util.spec_from_file_location(
    'gtm', os.path.join(os.path.dirname(os.path.abspath(__file__)), 'gtm-fbclid.py'))
gtm = importlib.util.module_from_spec(_spec)
_spec.loader.exec_module(gtm)

ALL_PAGES = '2147479553'
BASE, VC, META_ZDARZENIA = '27', '28', ('29', '30', '31')
NAZWA_WYZW = 'PV - oferta'


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--zbuduj', action='store_true')
    ap.add_argument('--publikuj', action='store_true')
    a = ap.parse_args()
    tok = gtm.token()
    ws = gtm.default_workspace(tok)
    wsp = f"{gtm.SCIEZKA}/workspaces/{ws['workspaceId']}"

    tagi, e = gtm.wolaj(tok, f'{wsp}/tags')
    if e:
        sys.exit(e)
    tagi = {t['tagId']: t for t in tagi['tag']}
    for tid in (BASE, VC) + META_ZDARZENIA:
        if not tagi[tid]['name'].startswith('Meta Pixel'):
            sys.exit(f'tag {tid} to nie Meta ({tagi[tid]["name"]}) — przerywam')
    nazwa_base = tagi[BASE]['name']

    if a.publikuj:
        v, e = gtm.wolaj(tok, f'{wsp}:create_version', {
            'name': 'Meta Pixel — Base raz na zdarzenie + strażnik init (akceptacja i klik na jednej stronie)',
            'notes': 'Base z Inicjalizacji na All Pages; ViewContent na PV - oferta; Base jako setup tag '
                     'dla ViewContent/Lead/Contact. Piksel przestaje strzelać bez zgody na /oferta/ i działa '
                     'na wszystkich stronach u osób ze zgodą. Diagnoza: docs/sesje/2026-09-15-audyt-meta-przed-po.md'})
        if e:
            sys.exit(f'create_version: {e}')
        wid = v['containerVersion']['containerVersionId']
        p, e = gtm.wolaj(tok, f"{gtm.SCIEZKA}/versions/{wid}:publish", {})
        print(f'wersja {wid} →', 'OPUBLIKOWANA' if p else e)
        return

    wyzw, _ = gtm.wolaj(tok, f'{wsp}/triggers')
    istn = [t for t in wyzw.get('trigger', []) if t['name'] == NAZWA_WYZW]
    plan = [f'27 {nazwa_base}: {tagi[BASE]["firingTriggerId"]} → [{ALL_PAGES}] All Pages, raz na odsłonę',
            f'28 {tagi[VC]["name"]}: {tagi[VC]["firingTriggerId"]} → [{NAZWA_WYZW}], setup: {nazwa_base}']
    plan += [f'{t} {tagi[t]["name"]}: wyzwalacze bez zmian {tagi[t]["firingTriggerId"]}, setup: {nazwa_base}'
             for t in META_ZDARZENIA]
    print('PLAN\n  ' + '\n  '.join(plan) + f'\n  nowy wyzwalacz „{NAZWA_WYZW}”: '
          + ('już istnieje' if istn else 'pageview, Page Path zaczyna się od /oferta/'))
    if not a.zbuduj:
        print('\n(podgląd — nic nie zapisano; --zbuduj wprowadza zmiany w workspace)')
        return

    if istn:
        wid_tr = istn[0]['triggerId']
    else:
        tr, e = gtm.wolaj(tok, f'{wsp}/triggers', {
            'name': NAZWA_WYZW, 'type': 'pageview',
            'filter': [{'type': 'startsWith', 'parameter': [
                {'type': 'template', 'key': 'arg0', 'value': '{{Page Path}}'},
                {'type': 'template', 'key': 'arg1', 'value': '/oferta/'}]}],
            'notes': 'ViewContent Meta — All Pages tylko na ofertach (2026-09-15).'})
        if e:
            sys.exit(f'wyzwalacz: {e}')
        wid_tr = tr['triggerId']
    print(f'  wyzwalacz {NAZWA_WYZW}: {wid_tr}')

    def zapisz(tid, zmiany):
        t = dict(tagi[tid], **zmiany)
        r, e = gtm.wolaj(tok, t['path'], t, 'PUT')
        print(f'  tag {tid} {t["name"]}: ', 'OK' if r else e)
        if e:
            sys.exit(1)

    # stopOnSetupFailure=False: przy Base „raz na odsłonę” drugie wywołanie jako setup jest pomijane,
    # a pominięcie nie może zablokować Contact — tagi Meta i tak same sprawdzają typeof fbq.
    setup = [{'tagName': nazwa_base, 'stopOnSetupFailure': False}]
    # v15: „raz na odsłonę” nie działa — próba zablokowana zgodą na gtm.js zużywa limit i Base
    # nie wraca jako setup po akceptacji na tej samej stronie (zmierzone 15.09, scenariusz E).
    # Dlatego „raz na zdarzenie” + strażnik w kodzie: init i PageView tylko raz na odsłonę.
    stary = "fbq('init','1634147041766916');\nfbq('track','PageView');"
    nowy = ("if (!window.__paMetaInit) { window.__paMetaInit = 1;\n"
            "  fbq('init','1634147041766916');\n  fbq('track','PageView');\n}")
    par = [dict(p) for p in tagi[BASE]['parameter']]
    for p in par:
        if p['key'] == 'html' and stary in p['value']:
            p['value'] = p['value'].replace(stary, nowy)
    zapisz(BASE, {'firingTriggerId': [ALL_PAGES], 'tagFiringOption': 'oncePerEvent', 'parameter': par})
    zapisz(VC, {'firingTriggerId': [wid_tr], 'setupTag': setup})
    for tid in META_ZDARZENIA:
        zapisz(tid, {'setupTag': setup})
    print('\nZmiany w workspace. Publikacja: --publikuj')


if __name__ == '__main__':
    main()
