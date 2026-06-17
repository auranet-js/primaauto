#!/usr/bin/env python3
"""Agregacja 20 snapshotów dry-run che168 -> obraz kalibracji (Etap 2)."""
import json, glob, re, os, collections

DRY = os.path.expanduser('~/domains/primaauto.com.pl/public_html/wp-content/uploads/asiaauto/che168-dryrun')
MAP = os.path.expanduser('~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/data/che168-param-map.php')

TARGETS = {
 '57688498':'BYD Leopard 5','55603575':'AITO M9','57345012':'AITO M8','56072168':'Li Auto L9',
 '58317978':'IM LS9','58645565':'IM LS8','57762274':'Tank 300 Hi4-T','57161580':'AITO M7',
 '56265713':'Voyah Dream PHEV','57474626':'WEY 07','55401343':'Tank 300','56477466':'Haval H9',
 '56256657':'NIO ET5 Touring','56367131':'NIO ES6','56898733':'XPeng P7+','56308602':'BYD Han DM-i',
 '55753694':'XPeng P7','57452042':'Dongfeng eπ008','56907198':'Zeekr X','56958722':'Mazda CX-5',
}

# znane id z param-map
known = set(int(m) for m in re.findall(r'^\s*(\d+)\s*=>', open(MAP).read(), re.M))

def latest(num):
    fs = sorted(glob.glob(f'{DRY}/{num}-*.json'))
    return fs[-1] if fs else None

# zbiory zbiorcze
unknown_ids = collections.Counter()           # param_NN -> ile modeli
unknown_id_name = {}                            # param_NN -> CN name (z extra.configuration)
orphan_terms = collections.defaultdict(collections.Counter)  # taxonomy -> value -> count
sierota_models = []
rows = []

for num, label in TARGETS.items():
    f = latest(num)
    if not f: rows.append((label,num,'BRAK PLIKU','','')); continue
    d = json.load(open(f))
    rd = d.get('raw_data',{})
    ep = rd.get('extra_prep',{}) or {}
    # nieznane param_NN
    unk = [k for k in ep if re.match(r'^param_\d+$', k)]
    for k in unk:
        unknown_ids[k]+=1
    # nazwy CN z extra.configuration
    extra = rd.get('extra',{}) or {}
    conf = extra.get('configuration',{}) or {}
    for grp in (conf.get('paramtypeitems') or []):
        for it in (grp.get('paramitems') or []):
            pid = it.get('id'); nm = it.get('name')
            if pid is not None and int(pid) not in known:
                unknown_id_name[f'param_{pid}'] = nm
    # sieroty taksonomii
    terms = d.get('plan',{}).get('terms') or []
    for t in terms:
        if not t.get('exists'):
            orphan_terms[t['taxonomy']][t.get('api_value') or t.get('value')]+=1
    # sierota modelu
    mapped = d.get('mapped')
    if not mapped:
        sierota_models.append((label, rd.get('mark'), rd.get('model'), d.get('plan',{}).get('title')))
    rows.append((label,num,'mapped' if mapped else 'SIEROTA',len(unk),d.get('plan',{}).get('title','')[:40]))

print("=== PER MODEL ===")
print(f"{'model':<22}{'numer':<11}{'hub':<9}{'#unk':<5}title")
for r in rows:
    print(f"{r[0]:<22}{r[1]:<11}{str(r[2]):<9}{str(r[3]):<5}{r[4]}")

print(f"\n=== SIEROTY MODELI (mapped=false): {len(sierota_models)} ===")
for s in sierota_models:
    print(f"  {s[0]:<22} che168={s[1]}/{s[2]}  title='{s[3]}'")

print(f"\n=== SIEROTY TAKSONOMII (exists=false) — enumy do zmapowania ===")
for tax, vals in sorted(orphan_terms.items()):
    print(f"  [{tax}]")
    for v,c in vals.most_common():
        print(f"     {c:>2}x  '{v}'")

print(f"\n=== NIEZNANE param_ (unia, {len(unknown_ids)} unikatowych id) ===")
for k,c in unknown_ids.most_common():
    nm = unknown_id_name.get(k,'?')
    print(f"  {c:>2}x  {k:<12} CN='{nm}'")
