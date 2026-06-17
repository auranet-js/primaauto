#!/usr/bin/env python3
"""Term-po-termie: dlaczego dry-run oznaczył 'sierota'. Czy fallbackowe make/serie ISTNIEJĄ."""
import json, glob, os

DRY = os.path.expanduser('~/domains/primaauto.com.pl/public_html/wp-content/uploads/asiaauto/che168-dryrun')
TARGETS = {
 '57688498':'BYD Leopard 5','55603575':'AITO M9','57345012':'AITO M8','56072168':'Li Auto L9',
 '58317978':'IM LS9','58645565':'IM LS8','57762274':'Tank 300 Hi4-T','57161580':'AITO M7',
 '56265713':'Voyah Dream PHEV','57474626':'WEY 07','55401343':'Tank 300','56477466':'Haval H9',
 '56256657':'NIO ET5 Touring','56367131':'NIO ES6','56898733':'XPeng P7+','56308602':'BYD Han DM-i',
 '55753694':'XPeng P7','57452042':'Dongfeng eπ008','56907198':'Zeekr X','56958722':'Mazda CX-5',
}

def latest(num):
    fs = sorted(glob.glob(f'{DRY}/{num}-*.json'))
    return fs[-1] if fs else None

print(f"{'model':<20}{'mapped':<8}{'make term (exists)':<34}{'serie term (exists)':<30}WERDYKT")
real_orphans, false_alarms = [], []
for num,label in TARGETS.items():
    f=latest(num)
    if not f: continue
    d=json.load(open(f))
    mapped=d.get('mapped')
    terms=d.get('plan',{}).get('terms') or []
    make=next((t for t in terms if t['taxonomy']=='make'),None)
    serie=next((t for t in terms if t['taxonomy']=='serie'),None)
    me = make['exists'] if make else None
    se = serie['exists'] if serie else None
    mstr=f"{(make['value'] if make else '—')} ({'OK' if me else 'BRAK'})"
    sstr=f"{(serie['value'] if serie else '—')} ({'OK' if se else 'BRAK'})"
    # realny orphan tylko gdy make LUB serie nie istnieje
    if me and se:
        verdict='fałszywy alarm (oba huby istnieją)'; false_alarms.append(label)
    else:
        verdict='REALNA sierota (tworzy nowy term)'; real_orphans.append(label)
    print(f"{label:<20}{str(mapped):<8}{mstr:<34}{sstr:<30}{verdict}")

print(f"\nPODSUMOWANIE: realnych sierot {len(real_orphans)} / {len(TARGETS)}")
print(f"  REALNE (make lub serie nie istnieje): {real_orphans}")
print(f"  fałszywy alarm (oba istnieją, tylko brak override w model-map): {false_alarms}")
