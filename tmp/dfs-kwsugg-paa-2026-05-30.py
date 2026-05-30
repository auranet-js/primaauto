#!/usr/bin/env python3
"""DFS Labs keyword_suggestions (volume) dla seedów modeli + seedów rankingowych pod blog."""
import json, subprocess
from pathlib import Path
AUTH=(Path.home()/"secrets/dataforseo/basic-auth-b64.txt").read_text().strip()

MODEL_SEEDS=["denza z9 gt","aito m9","xiaomi su7","geely monjaro","byd seal 6",
             "jaecoo 8","omoda 5","jetour t2","xpeng mona","changan uni-v"]
BLOG_SEEDS=["ranking chińskich samochodów","najlepszy chiński suv","chiński samochód 7 osobowy",
            "chińskie marki samochodów","chiński samochód elektryczny","chiński samochód hybrydowy"]

def suggest(seed,limit=40):
    body=[{"keyword":seed,"location_code":2616,"language_code":"pl","limit":limit,
           "order_by":["keyword_info.search_volume,desc"]}]
    out=subprocess.run(["curl","-s","-H",f"Authorization: Basic {AUTH}",
        "-H","Content-Type: application/json","-d",json.dumps(body),
        "https://api.dataforseo.com/v3/dataforseo_labs/google/keyword_suggestions/live"],
        capture_output=True,text=True).stdout
    return json.loads(out)

total_cost=0; allrows={}
for label,seeds in [("MODELE",MODEL_SEEDS),("BLOG/RANKINGI",BLOG_SEEDS)]:
    print(f"\n################# {label} #################")
    for seed in seeds:
        d=suggest(seed); total_cost+=d.get('cost',0)
        res=(d['tasks'][0].get('result') or [{}])[0]
        items=res.get('items') or []
        rows=[]
        for it in items:
            kw=it.get('keyword','')
            ki=it.get('keyword_info') or {}
            vol=ki.get('search_volume') or 0
            cpc=ki.get('cpc') or 0
            comp=ki.get('competition_level') or ''
            rows.append((kw,vol,cpc,comp))
        allrows[seed]=rows
        tot_vol=sum(r[1] for r in rows)
        print(f"\n=== '{seed}' — {len(rows)} fraz, suma vol {tot_vol}/mc ===")
        for kw,vol,cpc,comp in rows[:12]:
            print(f"  {kw[:46]:<48}{vol:>6}/mc  cpc {cpc:>5.2f}  {comp}")
print(f"\n===== TOTAL COST: ${total_cost:.4f} =====")
Path("tmp/dfs-kwsugg-2026-05-30.json").write_text(json.dumps(
    {s:[{"kw":r[0],"vol":r[1],"cpc":r[2],"comp":r[3]} for r in rs] for s,rs in allrows.items()},
    ensure_ascii=False,indent=1))
