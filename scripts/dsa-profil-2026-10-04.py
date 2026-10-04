#!/usr/bin/env python3
"""[DSA] ustawiony pod profil klienta (GA4 wszystkie kanały + zamówienia) — 04.10.2026.

    python3 scripts/dsa-profil-2026-10-04.py            # dry-run: plan + validateOnly w Google Ads
    python3 scripts/dsa-profil-2026-10-04.py --apply    # wykonanie (dopiero po akcepcie Janka)

Źródło decyzji: rozmowa 04.10 — profil kontaktujących (GA4 21.04–03.10, 738 osób, na osobach)
i klientów (65 realnych zamówień, 29 z PESEL). Trzy zmiany:
1. Feed: tylko marki, przy których ludzie się kontaktują albo kupują; do 2 najtańszych ofert
   na model (rocznik 2025/2026) + wszystkie auta na placu (`on_lot`).
2. Wiek: 65+ z powrotem (indeks 131), 45–54 najmocniej, 35–44 w dół, 25–34 dalej wykluczone.
3. Harmonogram z korektami (dni robocze 9–17 w górę, wieczór/rano/niedziela w dół)
   + świętokrzyskie (indeks 163) do podbitych województw.
"""
import sys, json, subprocess, urllib.request, urllib.error, html, collections
from pathlib import Path
sys.path.insert(0, str(Path(__file__).parent))
from gads_client import load, refresh, API_VERSION

APPLY = "--apply" in sys.argv
CID = "9506068500"; CAMP = "23896725555"; AG = "197286896339"; FEED_SET = "9118569940"; LABEL = "dsa2026"
WP = "/home/host476470/domains/primaauto.com.pl/public_html"
BASE = "https://primaauto.com.pl"
MARKI = ["byd", "zeekr", "denza", "mazda", "exeed", "lynk-co", "deepal", "jetour", "xpeng"]
NA_MODEL = 2

WIEK = {  # criterion_id: (nazwa, obecnie, docelowo)  None = wykluczony
    "503003": ("35–44", 1.00, 0.80),
    "503004": ("45–54", 1.15, 1.30),
    "503005": ("55–64", 1.25, 1.10),
    "503006": ("65+", None, 1.15),
}
DNI = ["MONDAY", "TUESDAY", "WEDNESDAY", "THURSDAY", "FRIDAY"]
HARMONOGRAM = [(d, 7, 9, 0.7) for d in DNI] + [(d, 9, 17, 1.2) for d in DNI] + \
              [(d, 17, 19, 1.0) for d in DNI] + [(d, 19, 22, 0.8) for d in DNI] + \
              [("SATURDAY", 7, 22, 0.9), ("SUNDAY", 7, 22, 0.7)]
GEO_NOWE = [("20858", "świętokrzyskie", 1.15)]

o, t, cfg = load(); AT = refresh(o, t)
H = {"Authorization": f"Bearer {AT}", "developer-token": cfg["developer_token"],
     "login-customer-id": cfg["mcc_customer_id"], "Content-Type": "application/json"}
U = f"https://googleads.googleapis.com/{API_VERSION}/customers/{CID}"

def call(ep, ops):
    body = {"operations": ops, "validateOnly": not APPLY}
    req = urllib.request.Request(f"{U}/{ep}", data=json.dumps(body).encode(), headers=H)
    try: return json.load(urllib.request.urlopen(req)), None
    except urllib.error.HTTPError as e: return None, e.read().decode()[:600]

def query(g):
    req = urllib.request.Request(f"{U}/googleAds:search", data=json.dumps({"query": g}).encode(), headers=H)
    return json.load(urllib.request.urlopen(req)).get("results", [])

# --- 1. feed ---
SQL = f"""
SELECT mk.slug, ts.slug, ts.name, p.post_name, CAST(pp.meta_value AS UNSIGNED) price,
       IFNULL(rs.meta_value,'') rs
FROM wp7j_posts p
JOIN wp7j_term_relationships tr ON tr.object_id=p.ID
JOIN wp7j_term_taxonomy tt ON tt.term_taxonomy_id=tr.term_taxonomy_id AND tt.taxonomy='serie'
JOIN wp7j_terms ts ON ts.term_id=tt.term_id
JOIN wp7j_term_taxonomy ttm ON ttm.term_id=tt.parent JOIN wp7j_terms mk ON mk.term_id=ttm.term_id
JOIN wp7j_postmeta pp ON pp.post_id=p.ID AND pp.meta_key='price' AND pp.meta_value REGEXP '^[0-9]+$'
JOIN wp7j_postmeta py ON py.post_id=p.ID AND py.meta_key='ca-year' AND py.meta_value IN ('2025','2026')
LEFT JOIN wp7j_postmeta rs ON rs.post_id=p.ID AND rs.meta_key='_asiaauto_reservation_status'
WHERE p.post_type='listings' AND p.post_status='publish' AND mk.slug IN ({','.join(repr(m) for m in MARKI)})
ORDER BY mk.slug, ts.slug, price"""
r = subprocess.run(["wp", "db", "query", SQL, "--skip-column-names"], cwd=WP, capture_output=True, text=True)
if r.returncode: sys.exit(r.stderr)
modele = collections.defaultdict(list)
pominiete = [l for l in r.stdout.splitlines() if l.count("\t") != 5]
if pominiete: print(f"UWAGA: {len(pominiete)} linii o złym formacie, np. {pominiete[0][:80]!r}")
for line in r.stdout.splitlines():
    if line.count("\t") != 5: continue
    mk, sl, nm, slug, price, rs = line.split("\t")
    modele[(mk, sl, nm)].append((slug, int(price), rs == "on_lot"))
nowy = {}
for (mk, sl, nm), oferty in modele.items():
    wybrane = oferty[:NA_MODEL] + [x for x in oferty[NA_MODEL:] if x[2]]
    for slug, price, lot in wybrane:
        nowy[slug] = (mk, nm, price, lot)

cur = {}
for x in query(f"SELECT asset.page_feed_asset.page_url, asset_set_asset.resource_name FROM asset_set_asset "
               f"WHERE asset_set.id={FEED_SET} AND asset_set_asset.status!='REMOVED'"):
    u = x["asset"]["pageFeedAsset"]["pageUrl"]
    if "/oferta/" in u: cur[u.rstrip("/").split("/oferta/")[-1]] = x["assetSetAsset"]["resourceName"]
usun = sorted(set(cur) - set(nowy)); dodaj = sorted(set(nowy) - set(cur)); zostaje = sorted(set(cur) & set(nowy))

wyniki = []
BEZ_FEEDU = "--bez-feedu" in sys.argv   # feed robi cron dsa-offer-feed-refresh.py (ta sama reguła od 04.10)
if BEZ_FEEDU: usun, dodaj = [], []
res, err = call("assetSetAssets:mutate", [{"remove": cur[s]} for s in usun]) if usun else ({}, None)
wyniki.append(("feed: usunięcie", len(usun), err))
ops = [{"create": {"pageFeedAsset": {"pageUrl": f"{BASE}/oferta/{s}/", "labels": [LABEL]}}} for s in dodaj]
res, err = call("assets:mutate", ops) if ops else ({}, None)
wyniki.append(("feed: nowe assety", len(ops), err))
if APPLY and ops and not err:
    names = [x["resourceName"] for x in res["results"]]
    res, err = call("assetSetAssets:mutate", [{"create": {"assetSet": f"customers/{CID}/assetSets/{FEED_SET}", "asset": n}} for n in names])
    wyniki.append(("feed: podpięcie", len(names), err))

# --- 2. wiek ---
agc = lambda cid: f"customers/{CID}/adGroupCriteria/{AG}~{cid}"
ops_rm = [{"remove": agc("503006")}]
ops_wiek = [
    {"create": {"adGroup": f"customers/{CID}/adGroups/{AG}", "ageRange": {"type": "AGE_RANGE_65_UP"}, "bidModifier": WIEK["503006"][2]}},
    {"create": {"adGroup": f"customers/{CID}/adGroups/{AG}", "ageRange": {"type": "AGE_RANGE_35_44"}, "bidModifier": WIEK["503003"][2]}},
    {"update": {"resourceName": agc("503004"), "bidModifier": WIEK["503004"][2]}, "updateMask": "bid_modifier"},
    {"update": {"resourceName": agc("503005"), "bidModifier": WIEK["503005"][2]}, "updateMask": "bid_modifier"},
]
if APPLY:
    _, err = call("adGroupCriteria:mutate", ops_rm); wyniki.append(("wiek: zdjęcie wykluczenia 65+", 1, err))
    _, err = call("adGroupCriteria:mutate", ops_wiek); wyniki.append(("wiek: korekty", len(ops_wiek), err))
else:
    _, err = call("adGroupCriteria:mutate", ops_rm); wyniki.append(("wiek: zdjęcie wykluczenia 65+", 1, err))
    _, err = call("adGroupCriteria:mutate", ops_wiek[1:]); wyniki.append(("wiek: 35–44 / 45–54 / 55–64", 3, err))
    wyniki.append(("wiek: 65+ ×1,15 (walidacja możliwa dopiero po zdjęciu wykluczenia)", 1, None))

# --- 3. harmonogram + geo ---
stary = [x["campaignCriterion"]["resourceName"] for x in query(
    f"SELECT campaign_criterion.resource_name FROM campaign_criterion WHERE campaign.id={CAMP} AND campaign_criterion.type='AD_SCHEDULE'")]
ops = [{"remove": rn} for rn in stary] + [
    {"create": {"campaign": f"customers/{CID}/campaigns/{CAMP}", "bidModifier": m,
                "adSchedule": {"dayOfWeek": d, "startHour": a, "endHour": b, "startMinute": "ZERO", "endMinute": "ZERO"}}}
    for d, a, b, m in HARMONOGRAM]
_, err = call("campaignCriteria:mutate", ops); wyniki.append((f"harmonogram: {len(stary)} usuniętych → {len(HARMONOGRAM)} z korektami", len(ops), err))
ops = [{"create": {"campaign": f"customers/{CID}/campaigns/{CAMP}", "bidModifier": m,
                   "location": {"geoTargetConstant": f"geoTargetConstants/{g}"}}} for g, _, m in GEO_NOWE]
_, err = call("campaignCriteria:mutate", ops); wyniki.append(("region: świętokrzyskie ×1,15", 1, err))

# --- raport ---
tryb = "WYKONANE" if APPLY else "DRY-RUN (validateOnly — na koncie nic nie zmienione)"
print(tryb)
for n, k, e in wyniki: print(f"  {n}: {k} op. — {'OK' if not e else 'BŁĄD ' + e}")
print(f"feed: obecnie {len(cur)}, nowy {len(nowy)} | zostaje {len(zostaje)}, usuwane {len(usun)}, dodawane {len(dodaj)}")
per_mk = collections.Counter(v[0] for v in nowy.values())
print("nowy feed per marka:", dict(per_mk.most_common()))
out = Path(sys.argv[sys.argv.index("--html") + 1]) if "--html" in sys.argv else None
if out:
    e = html.escape
    wiersze = "".join(
        f"<tr><td>{e(v[0])}</td><td>{e(v[1])}</td><td><a href='{BASE}/oferta/{s}/'>{e(s)}</a></td>"
        f"<td style='text-align:right'>{v[2]:,} zł</td><td>{'na placu' if v[3] else ''}</td>"
        f"<td>{'nowa' if s in dodaj else 'zostaje'}</td></tr>".replace(",", " ")
        for s, v in sorted(nowy.items(), key=lambda x: (x[1][0], x[1][1], x[1][2])))
    stare_mk = collections.Counter(s.split("-")[0] for s in cur)
    usuwane = ", ".join(e(s) for s in usun)
    wiek = "".join(f"<tr><td>{n}</td><td>{'wykluczony' if a is None else f'×{a:.2f}'}</td><td>×{b:.2f}</td></tr>" for n, a, b in WIEK.values())
    wiek += "<tr><td>25–34</td><td>wykluczony</td><td>wykluczony (bez zmian)</td></tr><tr><td>18–24</td><td>×1,00</td><td>×1,00 (bez zmian)</td></tr>"
    harm = "".join(f"<tr><td>{d}</td><td>{a}:00–{b}:00</td><td>×{m:.2f}</td></tr>" for d, a, b, m in HARMONOGRAM if d in ("MONDAY", "SATURDAY", "SUNDAY"))
    walid = "".join(f"<li>{e(n)} — {k} op. — <b>{'OK' if not er else 'BŁĄD'}</b>{'' if not er else ' ' + e(er)}</li>" for n, k, er in wyniki)
    out.write_text(f"""<!doctype html><html lang="pl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>DSA pod profil klienta</title><style>
body{{font:15px/1.5 -apple-system,Segoe UI,Roboto,sans-serif;background:#f6f5f3;color:#1a1a1a;margin:0}}
.w{{max-width:1100px;margin:0 auto;padding:28px 16px 60px}} section{{background:#fff;border:1px solid #e2e0dc;border-radius:10px;padding:18px;margin:0 0 18px;overflow-x:auto}}
table{{border-collapse:collapse;width:100%;font-size:13.5px}} td,th{{border-bottom:1px solid #eee;padding:5px 8px;text-align:left}} th{{background:#f3f2ef}}
h1{{font-size:24px}} h2{{font-size:18px;margin-top:0}} .m{{color:#6b6b6b}}
</style></head><body><div class="w">
<h1>[DSA] pod profil klienta — {tryb}</h1>
<p class="m">Profil: GA4 wszystkie kanały 21.04–03.10 (738 osób z kontaktem) + 65 realnych zamówień (29 z PESEL). Kampania na ręcznym CPC — korekty działają wprost. Budżet 15 zł/dz bez zmian.</p>
<section><h2>Walidacja Google Ads</h2><ul>{walid}</ul></section>
<section><h2>Wiek (grupa „Import — modele z Chin”)</h2><table><tr><th>wiek</th><th>dziś</th><th>po zmianie</th></tr>{wiek}</table></section>
<section><h2>Harmonogram (dziś: 7–22 codziennie, bez korekt)</h2><table><tr><th>dzień</th><th>godziny</th><th>korekta</th></tr>{harm}</table>
<p class="m">Wtorek–piątek tak samo jak poniedziałek. Region: świętokrzyskie ×1,15 obok istniejących podkarpackie ×1,30, małopolskie ×1,15.</p></section>
<section><h2>Feed: {len(cur)} → {len(nowy)} ofert</h2>
<p class="m">Marki: {', '.join(MARKI)}. Do {NA_MODEL} najtańszych ofert na model (rocznik 2025/2026) + każde auto na placu. Zostaje {len(zostaje)}, dodawanych {len(dodaj)}, usuwanych {len(usun)}.<br>
Dziś w feedzie per marka: {e(str(dict(stare_mk.most_common())))}<br>Po zmianie: {e(str(dict(per_mk.most_common())))}</p>
<table><tr><th>marka</th><th>model</th><th>oferta</th><th>cena</th><th></th><th></th></tr>{wiersze}</table>
<p class="m">Usuwane: {usuwane}</p></section></div></body></html>""")
    print("raport:", out)
