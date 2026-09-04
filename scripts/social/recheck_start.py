#!/usr/bin/env python3
"""Recheck po starcie — czy reklamy wyszły z weryfikacji, czy schodzi budżet, czy coś stanęło.

Dwa wywołania do Mety zamiast dziesięciu: konto ma kroczący limit (17/2446079) i seria
osobnych żądań na zestaw i reklamę potrafi go przebić w środku pracy.

    python3 scripts/social/recheck_start.py
"""
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import meta_api as api

# Statusy, które znaczą kłopot, a nie „jeszcze się nie rozkręciło".
ALARM = {'DISAPPROVED', 'WITH_ISSUES', 'CAMPAIGN_PAUSED', 'ADSET_PAUSED', 'PENDING_BILLING_INFO'}


def main():
    pola = ('id,name,effective_status,'
            'insights{impressions,reach,clicks,spend,cpc,ctr,actions},'
            'adset{name,status,daily_budget}')
    d, e = api.get(f'{api.ACT}/ads?fields={pola}&effective_status=["ACTIVE","PENDING_REVIEW",'
                   f'"DISAPPROVED","WITH_ISSUES","PREAPPROVED","PENDING_BILLING_INFO"]&limit=50')
    if e:
        sys.exit(f'nie mogę odczytać reklam: {e}')

    print('REKLAMY WŁĄCZONE\n')
    alarmy = []
    for a in d.get('data', []):
        ins = (a.get('insights') or {}).get('data', [{}])[0]
        st = a['effective_status']
        if st in ALARM:
            alarmy.append(f"{a['name']} → {st}")
        kontakt = sum(int(x['value']) for x in ins.get('actions', [])
                      if x['action_type'] in ('contact_website', 'onsite_conversion.lead_grouped',
                                              'lead', 'landing_page_view'))
        print(f"  {a['name'][:42]:42} {st:16} "
              f"wyśw. {ins.get('impressions', '0'):>6} · kliki {ins.get('clicks', '0'):>4} · "
              f"CTR {float(ins.get('ctr', 0) or 0):.2f}% · {float(ins.get('spend', 0) or 0):.2f} zł"
              f" · zdarzeń {kontakt}")

    k, e = api.get(f'{api.ACT}?fields=amount_spent,spend_cap,balance')
    if k:
        wyd, cap = int(k['amount_spent']), int(k['spend_cap'])
        print(f"\nKONTO  wydane {wyd/100:.2f} zł z limitu {cap/100:.0f} zł "
              f"({(cap - wyd)/100:.0f} zł zostało)")

    if alarmy:
        print('\nDO SPRAWDZENIA:')
        for x in alarmy:
            print('  ·', x)
    else:
        print('\nBez alarmów — nic nie zostało odrzucone ani wstrzymane.')


if __name__ == '__main__':
    main()
