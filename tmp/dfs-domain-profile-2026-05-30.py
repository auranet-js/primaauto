#!/usr/bin/env python3
"""DFS Labs ranked_keywords — profil organiczny domeny + bucketing intentu."""
import json, subprocess, sys, re
from pathlib import Path

AUTH = (Path.home()/"secrets/dataforseo/basic-auth-b64.txt").read_text().strip()

def ranked(target, limit=700):
    body=[{"target":target,"location_code":2616,"language_code":"pl",
           "limit":limit,"order_by":["ranked_serp_element.serp_item.etv,desc"],
           "filters":[["ranked_serp_element.serp_item.rank_group","<=",30]]}]
    out=subprocess.run(["curl","-s","-H",f"Authorization: Basic {AUTH}",
        "-H","Content-Type: application/json","-d",json.dumps(body),
        "https://api.dataforseo.com/v3/dataforseo_labs/google/ranked_keywords/live"],
        capture_output=True,text=True).stdout
    return json.loads(out)

def analyze(target):
    d=ranked(target)
    cost=d.get('cost',0)
    res=(d['tasks'][0].get('result') or [{}])[0]
    total=res.get('total_count')
    items=res.get('items') or []
    rows=[]
    for it in items:
        kd=it.get('keyword_data') or {}
        kw=kd.get('keyword','')
        ki=kd.get('keyword_info') or {}
        vol=ki.get('search_volume') or 0
        se=(it.get('ranked_serp_element') or {}).get('serp_item') or {}
        pos=se.get('rank_group'); etv=se.get('etv') or 0; url=se.get('url','')
        rows.append((kw,vol,pos,etv,url))
    tot_etv=sum(r[3] for r in rows)
    # bucket intentu
    buckets={'cena':0,'gdzie kupić':0,'kiedy/dostępność':0,'opinie/test':0,
             'dane tech/wymiary':0,'import':0,'leasing/finanse':0,'inne/info':0}
    bet={k:0.0 for k in buckets}
    for kw,vol,pos,etv,url in rows:
        k=kw.lower()
        if 'cena' in k or 'cennik' in k or 'ile kosztuje' in k or 'koszt' in k: b='cena'
        elif 'gdzie kup' in k or 'kupić' in k or 'salon' in k or 'dealer' in k: b='gdzie kupić'
        elif 'kiedy' in k or 'dostępn' in k or 'w polsce' in k or 'premiera' in k: b='kiedy/dostępność'
        elif 'opinie' in k or 'test' in k or 'recenzj' in k or 'vs' in k or 'porówn' in k: b='opinie/test'
        elif 'dane techn' in k or 'wymiary' in k or 'zasięg' in k or 'spalanie' in k or 'moc' in k or 'bagażnik' in k: b='dane tech/wymiary'
        elif 'import' in k or 'sprowadz' in k or 'z chin' in k: b='import'
        elif 'leasing' in k or 'kredyt' in k or 'rata' in k or 'finans' in k or 'vehis' in k: b='leasing/finanse'
        else: b='inne/info'
        buckets[b]+=1; bet[b]+=etv
    print(f"\n########## {target} ########## (cost ${cost:.4f})")
    print(f"Keywords w top 30: {len(rows)} (DFS total_count: {total}) | suma ETV(top30): {tot_etv:.0f}")
    print("\nBucket intentu       | #kw  | ETV    | %ETV")
    for k in sorted(buckets,key=lambda x:-bet[x]):
        pct=100*bet[k]/tot_etv if tot_etv else 0
        print(f"  {k:<18} | {buckets[k]:>4} | {bet[k]:>6.0f} | {pct:>4.1f}%")
    print("\nTop 25 fraz wg ETV:")
    print(f"  {'fraza':<40}{'vol':>7}{'poz':>5}{'ETV':>7}")
    for kw,vol,pos,etv,url in sorted(rows,key=lambda r:-r[3])[:25]:
        print(f"  {kw[:39]:<40}{vol:>7}{pos:>5}{etv:>7.0f}")
    return rows

if __name__=="__main__":
    target=sys.argv[1] if len(sys.argv)>1 else "chinskisamochod.com"
    rows=analyze(target)
    Path(f"tmp/dfs-profile-{target.replace('.','_')}-2026-05-30.json").write_text(
        json.dumps([{"kw":r[0],"vol":r[1],"pos":r[2],"etv":r[3],"url":r[4]} for r in rows],ensure_ascii=False,indent=1))
