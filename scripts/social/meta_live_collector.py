#!/usr/bin/env python3
"""Kolektor statystyk Meta — JSON dla dashboardu, który Janek otwiera bez konta na FB.

Powód: dostęp do Menedżera Reklam mają Ruslan i Andrzej, nie my — jedyny sposób, żeby
Janek widział, co robią kampanie, to własna strona pod zahaszowanym adresem. Token
System Usera zostaje po stronie serwera, w JSON-ie lądują wyłącznie liczby.

Cron co 30 minut. Pięć wywołań Graph API na przebieg (~240/dobę) — konto ma kroczący
limit (17/2446079), więc częściej nie schodzimy; przy odbiciu skrypt ponawia z odczekaniem.

    python3 scripts/social/meta_live_collector.py            # zapis do DEST
    python3 scripts/social/meta_live_collector.py --stdout   # podgląd JSON-a
"""
import json
import os
import sys
import time
from datetime import datetime

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import meta_api as api

DEST = '/home/host476470/domains/auratest.pl/public_html/pa-live-8aaf08d6ece0db176603/meta.json'
POLA = ('impressions,reach,frequency,clicks,inline_link_clicks,ctr,cpc,spend,actions,'
        'campaign_name,adset_name,ad_name')

# action_type z Mety → nasza nazwa. „ViewContent" to cel optymalizacji [VID] i [FOTO],
# kontakt (lead) liczy piksel na kliknięciu w telefon/WhatsApp — to jedyne, co realnie
# oznacza pieniądze; reszta jest miarą dostawy, nie wyniku.
AKCJE = {
    'offsite_conversion.fb_pixel_view_content': 'oferty',
    'link_click': 'klik_link',
    'video_view': 'obejrzenia',
    'landing_page_view': 'wejscia',
    'post_engagement': 'reakcje',
    'offsite_conversion.fb_pixel_lead': 'kontakty',
}


def pobierz(sciezka, prob=5):
    for i in range(prob):
        d, e = api.get(sciezka)
        if d is not None:
            return d
        if '2446079' not in (e or ''):
            print(f'{sciezka}: {e}', file=sys.stderr)
            return {}
        time.sleep(30 * (i + 1))
    print(f'{sciezka}: limit API nie puścił', file=sys.stderr)
    return {}


def liczby(r):
    """Jeden wiersz insights → same liczby, bez pól tekstowych."""
    akcje = {AKCJE[a['action_type']]: int(float(a['value']))
             for a in r.get('actions', []) if a['action_type'] in AKCJE}
    return dict({
        'wyswietlenia': int(r.get('impressions', 0)),
        'zasieg': int(r.get('reach', 0)),
        'czestotliwosc': round(float(r.get('frequency', 0)), 2),
        'kliki': int(r.get('clicks', 0)),
        'kliki_link': int(r.get('inline_link_clicks', 0)),
        'ctr': round(float(r.get('ctr', 0)), 2),
        'cpc': round(float(r.get('cpc', 0)), 2),
        'koszt': round(float(r.get('spend', 0)), 2),
    }, **akcje)


def suma(wiersze):
    """Agregat po reklamach. Zasięgu NIE sumujemy — ta sama osoba widzi kilka reklam,
    więc suma zasięgów to nie jest zasięg; pokazujemy tylko wyświetlenia i pieniądze."""
    out = {'wyswietlenia': 0, 'kliki': 0, 'kliki_link': 0, 'koszt': 0.0}
    for k in AKCJE.values():
        out[k] = 0
    for w in wiersze:
        for k in out:
            out[k] += w.get(k, 0)
    out['koszt'] = round(out['koszt'], 2)
    out['ctr'] = round(100 * out['kliki'] / out['wyswietlenia'], 2) if out['wyswietlenia'] else 0
    out['cpc'] = round(out['koszt'] / out['kliki'], 2) if out['kliki'] else 0
    return out


def insighty(okno):
    d = pobierz(f'{api.ACT}/insights?level=ad&fields={POLA}&date_preset={okno}&limit=200')
    return {r['ad_name']: dict(liczby(r), kampania=r.get('campaign_name', ''),
                               zestaw=r.get('adset_name', ''))
            for r in d.get('data', [])}


def main():
    dzis, wczoraj, calosc = insighty('today'), insighty('yesterday'), insighty('maximum')

    struktura = pobierz(f'{api.ACT}/ads?fields=id,name,status,effective_status,'
                        f'campaign{{name,status}},adset{{name,status,daily_budget}}&limit=200')
    konto = pobierz(f'{api.ACT}?fields=amount_spent,spend_cap,balance,account_status')

    reklamy, ostrzezenia = [], []
    for a in struktura.get('data', []):
        if a['effective_status'] in ('ARCHIVED', 'DELETED'):
            continue
        nazwa = a['name']
        reklamy.append({
            'nazwa': nazwa,
            'kampania': a['campaign']['name'],
            'zestaw': a['adset']['name'],
            'status': a['effective_status'],
            'dzis': dzis.get(nazwa, {}),
            'razem': calosc.get(nazwa, {}),
        })
        if a['effective_status'] in ('DISAPPROVED', 'WITH_ISSUES', 'PENDING_BILLING_INFO'):
            ostrzezenia.append(f'{nazwa}: {a["effective_status"]} — reklama się nie wyświetla')

    kampanie = {}
    for a in struktura.get('data', []):
        k = a['campaign']['name']
        if a['effective_status'] in ('ARCHIVED', 'DELETED'):
            continue
        w = kampanie.setdefault(k, {'nazwa': k, 'status': a['campaign']['status'],
                                    'budzet': 0, 'chodzi': 0, 'reklam': 0, 'zestawy': set()})
        w['reklam'] += 1
        w['chodzi'] += a['effective_status'] == 'ACTIVE'
        if a['adset']['name'] not in w['zestawy'] and a['adset']['status'] == 'ACTIVE':
            w['zestawy'].add(a['adset']['name'])
            w['budzet'] += int(a['adset'].get('daily_budget', 0)) / 100
    for k, w in kampanie.items():
        w.pop('zestawy')
        w['dzis'] = suma([r for r in dzis.values() if r['kampania'] == k])
        w['razem'] = suma([r for r in calosc.values() if r['kampania'] == k])
        w['wczoraj'] = suma([r for r in wczoraj.values() if r['kampania'] == k])

    wydane = float(konto.get('amount_spent', 0)) / 100
    limit = float(konto.get('spend_cap', 0)) / 100
    budzet_dzienny = sum(w['budzet'] for w in kampanie.values())
    if limit and budzet_dzienny:
        dni = (limit - wydane) / budzet_dzienny
        if dni < 7:
            ostrzezenia.append(f'limit konta wystarczy na {dni:.0f} dni przy {budzet_dzienny:.0f} zł/dobę')
    if konto.get('account_status') != 1:
        ostrzezenia.append(f'konto reklamowe w stanie {konto.get("account_status")} — nie wyda budżetu')

    dane = {
        'aktualizacja': datetime.now().strftime('%Y-%m-%d %H:%M'),
        'konto': {'wydane': round(wydane, 2), 'limit': round(limit, 2),
                  'budzet_dzienny': budzet_dzienny,
                  'saldo': round(float(konto.get('balance', 0)) / 100, 2)},
        'dzis': suma(list(dzis.values())),
        'wczoraj': suma(list(wczoraj.values())),
        'razem': suma(list(calosc.values())),
        'kampanie': sorted(kampanie.values(), key=lambda w: -w['razem']['koszt']),
        'reklamy': sorted(reklamy, key=lambda r: (r['status'] != 'ACTIVE',
                                                  -(r['razem'].get('koszt', 0)))),
        'ostrzezenia': ostrzezenia,
    }
    tresc = json.dumps(dane, ensure_ascii=False, indent=1)
    if '--stdout' in sys.argv:
        print(tresc)
        return
    # zapis przez plik tymczasowy — przeglądarka nigdy nie trafi na połowę JSON-a
    tmp = DEST + '.tmp'
    open(tmp, 'w', encoding='utf-8').write(tresc)
    os.replace(tmp, DEST)


if __name__ == '__main__':
    main()
