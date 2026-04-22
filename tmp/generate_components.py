#!/usr/bin/env python3
"""
Generator account-level komponentów (sitelinks + callouty) dla Prima-Auto.

Dlaczego account-level: asset dodany na poziomie konta objmie wszystkie 3
kampanie automatycznie, bez potrzeby kopiowania pod każdą z nich.

Structured snippets NIE ma w tym CSV — format 159-kolumnowy nie zawiera
Header/Values. Do dodania ręcznie w Editor (Tools → Structured snippets).

Wynikowy plik: tmp/components_assets.csv
"""

from pathlib import Path

OUT_CSV = Path(__file__).parent / "components_assets.csv"

ACCOUNT_ID = "950-606-8500"
ACCOUNT_NAME = "Prima-Auto Ruslan Prima"
BASE = "https://primaauto.com.pl"

COLUMNS = [
    "Account","Account name","Campaign","Labels","Campaign Type","Networks","Budget","Budget type",
    "EU political ads","Standard conversion goals","Custom conversion goal","Customer acquisition",
    "Languages","Bid Strategy Type","Bid Strategy Name","Enhanced CPC","Target CPA",
    "Maximum CPC bid limit","Desktop Bid Modifier","Mobile Bid Modifier","Tablet Bid Modifier",
    "TV Screen Bid Modifier","Start Date","End Date","Broad match keywords","Ad Schedule",
    "Ad rotation","Content exclusions","Targeting method","Exclusion method","DSA Website",
    "DSA Language","DSA targeting source","DSA page feeds","Audience targeting","Flexible Reach",
    "AI Max","Text customization","Final URL expansion","Ad Group","Max CPC","Max CPM","Max CPV",
    "Target CPV","Percent CPC","Target CPM","Target ROAS","Target CPC",
    "Display Network Custom Bid Type","Optimized targeting","Strict age and gender targeting",
    "Search term matching","Ad Group Type","Channels","Audience name","Age demographic",
    "Gender demographic","Income demographic","Parental status demographic",
    "Remarketing audience segments","Interest categories","Life events","Custom audience segments",
    "Detailed demographics","Remarketing audience exclusions","Tracking template",
    "Final URL suffix","Custom parameters","ID","Location","Reach","Location groups","Radius",
    "Unit","Bid Modifier","Asset name","Folder","Source","Image Size","File size",
    "Account keyword type","Keyword","Criterion Type","First page bid","Top of page bid",
    "First position bid","Quality score","Landing page experience","Expected CTR","Ad relevance",
    "Final URL","Final mobile URL","Upgraded extension","Link source","Business name",
    "Location group","Location group type","Ad type",
] + [f"Headline {i}{suffix}" for i in range(1, 16) for suffix in ("", " position")] + [
    "Description 1","Description 1 position","Description 2","Description 2 position",
    "Description 3","Description 3 position","Description 4","Description 4 position",
    "Path 1","Path 2","Shared set name","Shared set type","Keyword count","Campaigns",
    "Placement count","Link Text","Description Line 1","Description Line 2","Callout text",
    "Phone Number","Country of Phone","Conversion Action","Account settings","Inventory type",
    "Auto tagging","Campaign Status","Ad Group Status","Status","Approval Status","Ad strength",
    "Comment",
]

# -- Sitelinki (max 25 zn link text, max 35 zn description) ---------------
SITELINKS = [
    ("Katalog aut z Chin",  "Cały katalog — 1600+ modeli",   "Rocznik 2024/2025, dostępne teraz",  "/samochody/"),
    ("Samochody BYD",       "Tang DM, Song L, Han DM",        "Hybrydy plug-in i elektryki",        "/samochody/byd/"),
    ("Samochody AITO",      "Aito M9, M8, M7, M5",            "Flagowiec Huawei — stan jak nowy",   "/samochody/aito/"),
    ("Samochody Xiaomi",    "SU7, SU7 Ultra, YU7",            "Elektryki najnowszej generacji",     "/samochody/xiaomi/"),
    ("Samochody XPeng",     "P7+, X9, G6, G9",                "Premium EV z autonomią ADAS",        "/samochody/xpeng/"),
    ("Finansowanie",        "Leasing, kredyt, gotówka",       "Dobierzemy formę finansowania",      "/finansowanie/"),
    ("Gwarancja",           "Pełna dokumentacja i VIN",       "Bezpieczny zakup u importera",       "/gwarancja/"),
    ("O nas — Prima-Auto",  "Bezpośredni importer z Chin",    "Odbiór Rzeszów, dostawa cała PL",    "/o-nas/"),
    ("Kontakt",             "Tel. 783 807 381",               "Odpowiemy w 24h",                     "/kontakt/"),
]

# -- Callouty (max 25 zn każdy) -------------------------------------------
CALLOUTS = [
    "Odbiór w Rzeszowie",
    "Dostawa cała Polska",
    "Bezpośredni importer",
    "Umowa agencyjna",
    "Pełna obsługa celna",
    "Dokumenty i VIN",
    "Rocznik 2024/2025",
    "Wycena w 24h",
    "Bez ukrytych kosztów",
    "Kontakt: 783 807 381",
]

# -- Walidacja długości --------------------------------------------------
for lt, d1, d2, _ in SITELINKS:
    assert len(lt) <= 25, f"sitelink text za długi ({len(lt)}): {lt}"
    assert len(d1) <= 35, f"sitelink D1 za długi ({len(d1)}): {d1}"
    assert len(d2) <= 35, f"sitelink D2 za długi ({len(d2)}): {d2}"
for c in CALLOUTS:
    assert len(c) <= 25, f"callout za długi ({len(c)}): {c}"


# -- Budowa wierszy ------------------------------------------------------
rows = []

def add(**fields):
    row = {c: "" for c in COLUMNS}
    row["Account"] = ACCOUNT_ID
    row["Account name"] = ACCOUNT_NAME
    row.update(fields)
    rows.append(row)


# Sitelinki — account-level (Campaign pusty = account-level asset)
for link_text, d1, d2, path in SITELINKS:
    add(
        Source="Advertiser",
        **{"Final URL": f"{BASE}{path}"},
        **{"Link Text": link_text},
        **{"Description Line 1": d1},
        **{"Description Line 2": d2},
        Status="Enabled",
    )

# Callouty — account-level
for text in CALLOUTS:
    add(
        Source="Advertiser",
        **{"Callout text": text},
        Status="Enabled",
    )


# -- Zapis CSV ----------------------------------------------------------
def to_line(row):
    vals = []
    for c in COLUMNS:
        v = row.get(c, "") or ""
        v = str(v).replace("\t", " ").replace("\r", " ").replace("\n", " ")
        vals.append(v)
    return "\t".join(vals)

header = "\t".join(COLUMNS)
body = [to_line(r) for r in rows]
text = "\r\n".join([header] + body) + "\r\n"

with open(OUT_CSV, "wb") as f:
    f.write(b"\xff\xfe")
    f.write(text.encode("utf-16-le"))

print(f"wrote {OUT_CSV}  ({len(SITELINKS)} sitelinks + {len(CALLOUTS)} callouts = {len(rows)} rows)")
