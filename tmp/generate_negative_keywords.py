#!/usr/bin/env python3
"""
Generator negative keywords dla kampanii Prima-Auto.

Produkuje dwa pliki:
  - tmp/negative_keywords.csv  — do importu w Google Ads Editor
    (campaign-level negatywy, każda fraza osobno dla każdej z 3 kampanii
    — prostsze i niezawodne; bez shared sets)
  - tmp/negative_keywords.txt  — czytelna wersja z podziałem na kategorie

Format CSV: te same 159 kolumn co `primaauto_ads_import.csv` (TAB, UTF-16 LE z BOM, CRLF).
Match typ negatywów: Negative Broad dla kategorii "śmieci" (szerokie odcinanie),
Negative Phrase dla frazowych (np. "prime video"), Negative Exact dla brand-zderzeń.
"""

from pathlib import Path

OUT_DIR = Path(__file__).parent
OUT_CSV = OUT_DIR / "negative_keywords.csv"
OUT_TXT = OUT_DIR / "negative_keywords.txt"

ACCOUNT_ID = "950-606-8500"
ACCOUNT_NAME = "Prima-Auto Ruslan Prima"

CAMPAIGNS = [
    "[Brand] Prima-Auto",
    "[Topic] Import z Chin",
    "[SKAG] Marki-Modele",
]

# Kolumny identyczne z głównym importerem
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

# ------------------------------------------------------------------
# Kategorie negatywów
# ------------------------------------------------------------------

# Ogólne śmieci — informacyjne / non-commercial intent
SMIECI_OGOLNE = [
    # informacyjne
    "forum", "opinie forum", "pdf", "instrukcja", "instrukcja pdf",
    "wikipedia", "wiki", "definicja", "co to jest", "jak to działa",
    "tłumacz", "translator", "słownik",
    # darmowe / download
    "za darmo", "darmowy", "darmowe", "free", "free download", "download",
    "do pobrania", "online za darmo",
    # content/media
    "tapeta", "tapety", "wallpaper", "wallpaper 4k", "obraz", "obrazki",
    "ikona", "ikony", "logo png", "clipart", "gif",
    "film", "filmy", "film online", "cały film", "trailer",
    "piosenka", "tekst piosenki", "song lyrics", "lyrics",
    "video", "klip", "music video", "youtube", "tiktok", "shorts",
    "meme", "memy", "śmieszne", "żart", "żarty",
    # hobby / nie-auto
    "zabawka", "zabawki", "rc", "zdalnie sterowany", "zdalnie sterowane",
    "modelarstwo", "model do sklejania", "model kolekcjonerski",
    "lego", "gra", "gry", "gra online", "simulator", "symulator",
    "roblox", "minecraft",
    # edukacja / praca
    "kurs", "szkolenie", "tutorial", "uniwersytet", "studia", "licencjat",
    "praca", "pracodawca", "rekrutacja", "zarobki", "ogłoszenia pracy",
    "kariera", "cv", "oferta pracy",
    # inne portale kupnowe (nie chcemy)
    "allegro", "olx", "otomoto", "sprzedajemy",
    # książki
    "książka", "książki", "podręcznik", "ebook",
]

# Motoryzacyjne śmieci — intent ≠ zakup nowego auta z importu
SMIECI_MOTO = [
    # części i serwis
    "części", "części zamienne", "części używane", "sklep z częściami",
    "naprawa", "warsztat", "warsztaty", "mechanik", "serwis",
    "awaria", "usterka", "diagnostyka",
    "felgi", "opony", "akumulator", "akumulatory", "lakier", "klimatyzacja",
    "hak holowniczy",
    # uszkodzone
    "wypadek", "powypadkowy", "powypadkowe", "po wypadku",
    "stłuczka", "do rozbiórki", "rozbiórka",
    "wrak", "szrot", "szrotownik", "złom", "auto złom",
    "skradzione", "skradziony", "kradzione", "kradziony",
    "licytacja", "aukcja komornicza", "policja", "komornik",
    # komisy / leasing / wynajem
    "komis", "komisy", "komis auto", "komis samochodowy",
    "leasing", "wynajem", "wynajem długoterminowy", "rent a car", "rent",
    "carsharing", "car sharing",
    # DIY / tuning / forumowe
    "tuning", "mapa silnika", "chiptuning", "stage 1", "stage 2",
    "diy", "na warsztat",
    # inne hobby / non-buy
    "gra symulator jazdy", "jak ma się nazywać",
    "historia auta", "historia samochodu", "ciekawostki",
    # import hurtowy
    "alibaba", "aliexpress", "dropshipping", "hurt", "hurtownia",
    "ebay", "tani import", "zabawki z chin", "elektronika z chin",
    "odzież z chin", "obuwie z chin", "meble z chin",
]

# Zderzenia brandowe — żeby kampania Brand nie pokazywała się na Primark/Prime Video itp.
# (Negative Phrase wystarczy — odcina od kontekstów)
BRAND_COLLISIONS = [
    "primark",
    "prime video",
    "amazon prime",
    "primavera",
    "primator",
    "primabar",
    "primacom",
    "primabiotic",
    "prima sport",
    "prima bank",
    "auto świat",  # czasopismo "auto" sam w sobie jest ryzykiem dla phrase "prima auto"
]


# ------------------------------------------------------------------
# Budowa wierszy CSV
# ------------------------------------------------------------------

rows = []

def add(**fields):
    row = {c: "" for c in COLUMNS}
    row["Account"] = ACCOUNT_ID
    row["Account name"] = ACCOUNT_NAME
    row.update(fields)
    rows.append(row)


def add_neg(campaign, keyword, match_type):
    """match_type: 'Negative Broad' | 'Negative Phrase' | 'Negative Exact'"""
    add(
        Campaign=campaign,
        Keyword=keyword,
        **{"Criterion Type": match_type},
        Status="Enabled",
    )


# Ogólne śmieci + moto śmieci → wszystkie 3 kampanie, Negative Broad
for camp in CAMPAIGNS:
    for kw in SMIECI_OGOLNE:
        add_neg(camp, kw, "Negative Broad")
    for kw in SMIECI_MOTO:
        add_neg(camp, kw, "Negative Broad")

# Zderzenia brandowe → tylko kampania Brand, Negative Phrase
for kw in BRAND_COLLISIONS:
    add_neg("[Brand] Prima-Auto", kw, "Negative Phrase")


# ------------------------------------------------------------------
# Zapis CSV (UTF-16 LE z BOM, CRLF, TAB)
# ------------------------------------------------------------------

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

# ------------------------------------------------------------------
# Zapis TXT — czytelna wersja do ewentualnego ręcznego wklejenia
# ------------------------------------------------------------------

def txt_section(title, items, match_type):
    lines = [f"### {title}  ({match_type}, {len(items)} fraz)", ""]
    lines.extend(f"  {x}" for x in items)
    lines.append("")
    return "\n".join(lines)

txt = []
txt.append("# Negative keywords — Prima-Auto")
txt.append("")
txt.append("Do ręcznego wklejenia w Google Ads Editor → Tools → Shared library →")
txt.append("Negative keyword lists, albo jako campaign-level negatives.")
txt.append("")
txt.append("Zalecana konfiguracja (jeśli tworzysz Shared lists ręcznie):")
txt.append("  1. Lista 'prima_smieci_ogolne' (Negative Broad)  → zastosuj do 3 kampanii")
txt.append("  2. Lista 'prima_smieci_moto'    (Negative Broad)  → zastosuj do 3 kampanii")
txt.append("  3. Lista 'prima_brand_collision' (Negative Phrase) → zastosuj TYLKO do [Brand] Prima-Auto")
txt.append("")
txt.append("Albo: po prostu zaimportuj `negative_keywords.csv` — campaign-level bez shared lists.")
txt.append("")
txt.append(txt_section("ŚMIECI OGÓLNE  (informacyjne / non-commercial)",
                       SMIECI_OGOLNE, "Negative Broad"))
txt.append(txt_section("ŚMIECI MOTORYZACYJNE  (części, warsztat, wrak, hurt)",
                       SMIECI_MOTO, "Negative Broad"))
txt.append(txt_section("ZDERZENIA BRANDOWE  (tylko kampania [Brand] Prima-Auto)",
                       BRAND_COLLISIONS, "Negative Phrase"))

OUT_TXT.write_text("\n".join(txt), encoding="utf-8")

print(f"wrote {OUT_CSV}  ({len(rows)} negative-keyword rows)")
print(f"wrote {OUT_TXT}  ({len(SMIECI_OGOLNE)} ogólne + {len(SMIECI_MOTO)} moto + {len(BRAND_COLLISIONS)} brand)")
