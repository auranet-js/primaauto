#!/usr/bin/env python3
"""
Generator CSV do importu w Google Ads Editor dla konta Prima-Auto.
Produkuje plik tab-separated UTF-16 LE z BOM, CRLF linie.

Zawartość:
  - Pauza istniejącej kampanii "Campaign #1"
  - 3 nowe kampanie: Brand, Topic "Import z Chin", SKAG Marki-Modele (20 grup)
  - Keywordy exact+phrase, RSA (15H/4D z pinami H1/H2), ad schedule 9-17 pon-pt,
    geo Poland (ID 2616), Manual CPC per kampania.

Wynikowy plik: tmp/primaauto_ads_import.csv
"""

from pathlib import Path

OUTPUT = Path(__file__).parent / "primaauto_ads_import.csv"

ACCOUNT_ID = "950-606-8500"
ACCOUNT_NAME = "Prima-Auto Ruslan Prima"
PHONE = "783 807 381"
HOMEPAGE = "https://primaauto.com.pl/"
CATALOG = "https://primaauto.com.pl/samochody/"
AD_SCHEDULE = (
    "(Monday[09:00-17:00]);(Tuesday[09:00-17:00]);"
    "(Wednesday[09:00-17:00]);(Thursday[09:00-17:00]);"
    "(Friday[09:00-17:00])"
)
POLAND_GEO_ID = "2616"
POLAND_LOCATION = "Poland"
START_DATE = "2026-04-23"

# Kolumny wg eksportu z Google Ads Editor (dokładnie te same, 160 kolumn)
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
assert len(COLUMNS) == 159, f"expected 159 cols, got {len(COLUMNS)}"

# ------------------------------------------------------------------
# Dane kampanii
# ------------------------------------------------------------------

def kw_exact(text):   return (text, "Exact")
def kw_phrase(text):  return (text, "Phrase")

BRAND_KEYWORDS = [
    kw_exact("prima auto"),
    kw_exact("prima-auto"),
    kw_exact("primaauto"),
    kw_exact("primaauto.com.pl"),
    kw_exact("prima auto samochody"),
    kw_exact("prima auto import"),
    kw_exact("prima auto chiny"),
    kw_phrase("prima auto"),
    kw_phrase("prima-auto"),
]

TOPIC_KEYWORDS_IMPORT = [
    kw_exact("import aut z chin"),
    kw_exact("import samochodu z chin"),
    kw_exact("import samochodów z chin"),
    kw_exact("sprowadzanie aut z chin"),
    kw_exact("sprowadzanie samochodów z chin"),
    kw_exact("sprowadzenie auta z chin"),
    kw_exact("auta z chin"),
    kw_exact("auto z chin"),
    kw_exact("samochody z chin"),
    kw_exact("samochód z chin"),
    kw_exact("chiński samochód"),
    kw_exact("chińskie samochody"),
    kw_exact("chińskie auto"),
    kw_exact("import elektryka z chin"),
    kw_exact("import hybrydy z chin"),
    kw_phrase("auto z chin"),
    kw_phrase("samochody z chin"),
    kw_phrase("import z chin"),
    kw_phrase("sprowadzanie z chin"),
    kw_phrase("chińskie auto"),
]

TOPIC_KEYWORDS_EV = [
    kw_exact("chiński elektryk"),
    kw_exact("chińskie ev"),
    kw_exact("chińskie auta elektryczne"),
    kw_exact("chińskie samochody elektryczne"),
    kw_exact("chińska hybryda"),
    kw_exact("chińskie phev"),
    kw_exact("chiński samochód elektryczny"),
    kw_exact("chiński plug-in"),
    kw_phrase("chiński elektryk"),
    kw_phrase("chińskie auto elektryczne"),
    kw_phrase("chińska hybryda plug-in"),
]

# 20 grup modelowych: (Marka, Model, slug_url_segment, kluczowa_fraza, path2)
MODEL_GROUPS = [
    ("AITO",         "Aito M9",        "aito/aito-m9",         "aito m9",        "aito-m9"),
    ("Xiaomi",       "Xiaomi SU7",     "xiaomi/xiaomi-su7",    "xiaomi su7",     "xiaomi-su7"),
    ("AITO",         "Aito M7",        "aito/aito-m7",         "aito m7",        "aito-m7"),
    ("AITO",         "Aito M8",        "aito/aito-m8",         "aito m8",        "aito-m8"),
    ("Geely",        "Xingyue L",      "geely/xingyue-l",      "geely xingyue l","xingyue-l"),
    ("BYD",          "Tang DM",        "byd/tang-dm",          "byd tang dm",    "tang-dm"),
    ("Geely",        "Xingrui",        "geely/xingrui",        "geely xingrui",  "xingrui"),
    ("Hongqi",       "Hongqi H5",      "hongqi/hongqi-h5",     "hongqi h5",      "hongqi-h5"),
    ("XPeng",        "XPeng P7+",      "xpeng/xpeng-p7",       "xpeng p7",       "xpeng-p7"),
    ("AITO",         "Aito M5",        "aito/aito-m5",         "aito m5",        "aito-m5"),
    ("Chery",        "Arrizo 8",       "chery/arrizo-8",       "chery arrizo 8", "arrizo-8"),
    ("Xiaomi",       "Xiaomi YU7",     "xiaomi/xiaomi-yu7",    "xiaomi yu7",     "xiaomi-yu7"),
    ("Avatr",        "Avatr 06",       "avatr/avatr-06",       "avatr 06",       "avatr-06"),
    ("Denza",        "Denza D9 DM",    "denza/denza-d9-dm",    "denza d9",       "denza-d9"),
    ("Changan",      "Changan UNI-V",  "changan/changan-uni-v","changan uni-v",  "uni-v"),
    ("GAC Trumpchi", "Trumpchi M8",    "gac-trumpchi/trumpchi-m8","trumpchi m8", "trumpchi-m8"),
    ("BYD",          "Song L EV",      "byd/song-l",           "byd song l",     "song-l"),
    ("BYD",          "Han DM",         "byd/han-dm",           "byd han dm",     "han-dm"),
    ("Avatr",        "Avatr 12",       "avatr/avatr-12",       "avatr 12",       "avatr-12"),
    ("XPeng",        "XPeng X9",       "xpeng/xpeng-x9",       "xpeng x9",       "xpeng-x9"),
]

def model_keywords(base):
    """Generator fraz per model. base = np. 'byd song l'."""
    variants = ["", " import", " cena", " polska", " opinie", " używany"]
    exacts = [kw_exact(f"{base}{v}") for v in variants]
    phrases = [kw_phrase(base), kw_phrase(f"{base} cena")]
    return exacts + phrases


def clip(s, n):
    return s if len(s) <= n else s[:n]


def brand_rsa():
    return {
        "H": [
            ("Prima-Auto — Oficjalna Strona", "1"),
            ("Import Aut z Chin",              "2"),
            ("Bezpośredni Importer", ""),
            ("Odbiór w Rzeszowie", ""),
            ("Dostawa w Całej Polsce", ""),
            ("Auta 2024 / 2025 Jak Nowe", ""),
            ("Pełna Obsługa Celna", ""),
            (f"Kontakt: {PHONE}", ""),
            ("Zobacz Ofertę Aut z Chin", ""),
            ("BYD, Xiaomi, AITO, XPeng", ""),
            ("Umowa Agencyjna", ""),
            ("VIN, Dokumenty, Gwarancja", ""),
            ("Wybierz Model z Katalogu", ""),
            ("Bezpieczny Import z Chin", ""),
            ("Prima-Auto Zaufany Partner", ""),
        ],
        "D": [
            "Prima-Auto — bezpośredni importer aut z Chin. Odbiór w Rzeszowie, dostawa cała Polska.",
            "Nowe modele BYD, Xiaomi, AITO, XPeng. Pełna obsługa: VIN, cło, umowa agencyjna.",
            f"Zamów ofertę na dowolny model. Kontakt {PHONE}.",
            "Rocznik 2024/2025, stan jak nowy. Sprawdź katalog aut z Chin już teraz.",
        ],
        "final_url": HOMEPAGE,
        "path1": "import-chin",
        "path2": "prima-auto",
    }


def topic_rsa_import():
    return {
        "H": [
            ("Import Aut z Chin",              "1"),
            ("Bezpośredni Importer",           "2"),
            ("Odbiór w Rzeszowie", ""),
            ("Dostawa w Całej Polsce", ""),
            ("Nowe BYD, AITO, Xiaomi", ""),
            ("Rocznik 2024 / 2025", ""),
            ("Auta Jak Nowe", ""),
            ("Pełna Obsługa Celna", ""),
            ("Umowa Agencyjna", ""),
            ("VIN i Dokumenty", ""),
            (f"Kontakt: {PHONE}", ""),
            ("Szybka Wycena w 24h", ""),
            ("Bez Ukrytych Kosztów", ""),
            ("Katalog Aut z Chin", ""),
            ("Zaufany Partner — Prima-Auto", ""),
        ],
        "D": [
            "Importujemy nowe chińskie samochody bezpośrednio od producenta. Odbiór w Rzeszowie.",
            "BYD, Xiaomi, AITO, XPeng i więcej. Rocznik 2024/2025, stan jak nowy.",
            "Pełna obsługa: cło, dokumenty, VIN, umowa agencyjna. Bez ukrytych kosztów.",
            f"Zapytaj o ofertę: {PHONE} lub formularz kontaktowy na stronie.",
        ],
        "final_url": CATALOG,
        "path1": "auta-z-chin",
        "path2": "import",
    }


def topic_rsa_ev():
    return {
        "H": [
            ("Chińskie EV i Hybrydy PHEV",     "1"),
            ("Import z Chin — Prima-Auto",     "2"),
            ("BYD, XPeng, NIO, AITO", ""),
            ("Zasięg 500+ km", ""),
            ("Najnowsza Technologia", ""),
            ("Odbiór w Rzeszowie", ""),
            ("Dostawa Cała Polska", ""),
            ("Rocznik 2024 / 2025", ""),
            ("Auta Jak Nowe", ""),
            (f"Kontakt: {PHONE}", ""),
            ("Umowa Agencyjna", ""),
            ("Pełne Dokumenty VIN", ""),
            ("Bezpośredni Importer", ""),
            ("Sprawdź Katalog Elektryków", ""),
            ("Wycena w 24h", ""),
        ],
        "D": [
            "Chińskie elektryki i hybrydy plug-in. Bezpośredni import do Polski, odbiór Rzeszów.",
            "BYD, Xiaomi, XPeng, NIO, AITO — najnowsze modele 2024/2025 w cenach od importera.",
            "Pełna obsługa: cło, VIN, dokumenty, umowa. Bez ukrytych kosztów.",
            f"Zadzwoń {PHONE} — dobierzemy model pod Twoje potrzeby i budżet.",
        ],
        "final_url": CATALOG,
        "path1": "elektryki",
        "path2": "z-chin",
    }


def model_rsa(marka, model, model_slug_last, final_url, path2):
    """RSA dla grupy modelowej. Max 30 znaków/H, 90/D."""
    # H1/H2 mogą się powtarzać brandem marki — nie dublujemy jeśli model już zawiera markę
    if marka.lower() in model.lower():
        h1 = clip(f"{model} z Chin", 30)
    else:
        h1 = clip(f"{marka} {model} z Chin", 30)
    h2 = "Prima-Auto — Import"
    tail = [
        ("Rocznik 2024 / 2025", ""),
        ("Odbiór w Rzeszowie", ""),
        ("Dostawa w Całej Polsce", ""),
        ("Bezpośredni Importer", ""),
        ("Pełna Obsługa Celna", ""),
        ("Umowa Agencyjna", ""),
        ("VIN, Dokumenty", ""),
        (f"Kontakt: {PHONE}", ""),
        ("Wycena w 24h", ""),
        ("Stan Jak Nowy", ""),
        (clip(f"Wszystkie {model}", 30), ""),
        (clip(f"{marka} Prosto z Chin", 30), ""),
        (clip(f"Zamów {model} Teraz", 30), ""),
    ]
    H = [(h1, "1"), (h2, "2")] + tail
    H = H[:15]
    full = f"{marka} {model}" if marka.lower() not in model.lower() else model
    D = [
        clip(f"{full} sprowadzony z Chin. Rocznik 2024/2025, stan jak nowy. Pełna dokumentacja.", 90),
        clip(f"Bezpośredni importer Prima-Auto. Odbiór Rzeszów lub dostawa cała Polska.", 90),
        clip(f"Pełna obsługa: VIN, cło, dokumenty, umowa agencyjna. Bez ukrytych kosztów.", 90),
        clip(f"Zadzwoń {PHONE}. Sprawdź wszystkie egzemplarze {model} w katalogu.", 90),
    ]
    return {
        "H": H,
        "D": D,
        "final_url": final_url,
        "path1": "auta-z-chin",
        "path2": clip(path2, 15),
    }


# ------------------------------------------------------------------
# Budowa wierszy
# ------------------------------------------------------------------

rows = []

def add(**fields):
    row = {c: "" for c in COLUMNS}
    row["Account"] = ACCOUNT_ID
    row["Account name"] = ACCOUNT_NAME
    row.update(fields)
    rows.append(row)


def add_campaign(name, budget, max_cpc):
    add(
        Campaign=name,
        **{"Campaign Type": "Search"},
        Networks="Google search",
        Budget=f"{budget:.2f}",
        **{"Budget type": "Daily"},
        Languages="pl",
        **{"Bid Strategy Type": "Manual CPC"},
        **{"Enhanced CPC": "Disabled"},
        **{"Maximum CPC bid limit": f"{max_cpc:.2f}"},
        **{"Start Date": START_DATE},
        **{"Ad Schedule": AD_SCHEDULE},
        **{"Ad rotation": "Optimize for clicks"},
        **{"Targeting method": "Presence: People in or regularly in your targeted locations"},
        **{"Exclusion method": "Presence: People in your excluded locations"},
        **{"Campaign Status": "Enabled"},
    )
    # Geo row
    add(
        Campaign=name,
        ID=POLAND_GEO_ID,
        Location=POLAND_LOCATION,
        Status="Enabled",
    )


def add_adgroup(campaign, group, max_cpc):
    add(
        Campaign=campaign,
        **{"Ad Group": group},
        **{"Max CPC": f"{max_cpc:.2f}"},
        **{"Ad Group Type": "Standard"},
        **{"Ad Group Status": "Enabled"},
    )


def add_keyword(campaign, group, text, ctype):
    add(
        Campaign=campaign,
        **{"Ad Group": group},
        Keyword=text,
        **{"Criterion Type": ctype},
        Status="Enabled",
    )


def add_rsa(campaign, group, rsa):
    fields = {
        "Campaign": campaign,
        "Ad Group": group,
        "Ad type": "Responsive search ad",
        "Final URL": rsa["final_url"],
        "Path 1": rsa.get("path1", ""),
        "Path 2": rsa.get("path2", ""),
        "Status": "Enabled",
    }
    for i, (text, pos) in enumerate(rsa["H"], 1):
        fields[f"Headline {i}"] = text
        fields[f"Headline {i} position"] = pos
    for i, text in enumerate(rsa["D"], 1):
        fields[f"Description {i}"] = text
    add(**fields)


# -- Pauza istniejącej kampanii --
add(Campaign="Campaign #1", **{"Campaign Status": "Paused"})

# -- 1. BRAND --
CAMP_BRAND = "[Brand] Prima-Auto"
add_campaign(CAMP_BRAND, budget=10.00, max_cpc=0.50)
GRP_BRAND = "Brand"
add_adgroup(CAMP_BRAND, GRP_BRAND, max_cpc=0.50)
for (t, ct) in BRAND_KEYWORDS:
    add_keyword(CAMP_BRAND, GRP_BRAND, t, ct)
add_rsa(CAMP_BRAND, GRP_BRAND, brand_rsa())

# -- 2. TOPIC Import z Chin --
CAMP_TOPIC = "[Topic] Import z Chin"
add_campaign(CAMP_TOPIC, budget=30.00, max_cpc=1.00)
GRP_TOPIC_IMPORT = "Import aut z Chin"
add_adgroup(CAMP_TOPIC, GRP_TOPIC_IMPORT, max_cpc=1.00)
for (t, ct) in TOPIC_KEYWORDS_IMPORT:
    add_keyword(CAMP_TOPIC, GRP_TOPIC_IMPORT, t, ct)
add_rsa(CAMP_TOPIC, GRP_TOPIC_IMPORT, topic_rsa_import())

GRP_TOPIC_EV = "Chińskie EV i PHEV"
add_adgroup(CAMP_TOPIC, GRP_TOPIC_EV, max_cpc=1.00)
for (t, ct) in TOPIC_KEYWORDS_EV:
    add_keyword(CAMP_TOPIC, GRP_TOPIC_EV, t, ct)
add_rsa(CAMP_TOPIC, GRP_TOPIC_EV, topic_rsa_ev())

# -- 3. SKAG Marki-Modele --
CAMP_MODEL = "[SKAG] Marki-Modele"
add_campaign(CAMP_MODEL, budget=60.00, max_cpc=0.80)
for (marka, model, url_seg, base, path2) in MODEL_GROUPS:
    group_name = model if marka.lower() in model.lower() else f"{marka} {model}"
    final_url = f"https://primaauto.com.pl/samochody/{url_seg}/"
    add_adgroup(CAMP_MODEL, group_name, max_cpc=0.80)
    for (t, ct) in model_keywords(base):
        add_keyword(CAMP_MODEL, group_name, t, ct)
    add_rsa(CAMP_MODEL, group_name, model_rsa(marka, model, base, final_url, path2))


# ------------------------------------------------------------------
# Zapis TSV → UTF-16 LE z BOM, CRLF
# ------------------------------------------------------------------

def to_line(row):
    vals = []
    for c in COLUMNS:
        v = row.get(c, "")
        if v is None: v = ""
        v = str(v).replace("\t", " ").replace("\r", " ").replace("\n", " ")
        vals.append(v)
    return "\t".join(vals)

header = "\t".join(COLUMNS)
body = [to_line(r) for r in rows]
text = "\r\n".join([header] + body) + "\r\n"

with open(OUTPUT, "wb") as f:
    f.write(b"\xff\xfe")  # UTF-16 LE BOM
    f.write(text.encode("utf-16-le"))

print(f"wrote {OUTPUT}  ({len(rows)} rows, {len(COLUMNS)} cols)")
