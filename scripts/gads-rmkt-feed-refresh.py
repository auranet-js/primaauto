#!/usr/bin/env python3
"""RMKT — odświeżenie feedu dynamic remarketing (AssetSet 9118300013). Wersja cronowa.

Dlaczego cyklicznie: DynamicCustomAsset jest IMMUTABLE, więc zmiana ceny w bazie NIE propaguje się
do feedu. Bez odświeżania feed reklamuje ceny sprzed tygodni. Incydent 2026-07-12: feed z 02.06 miał
252 z 254 wpisów (99%) z błędną ceną — GAC M8 reklamowany za 278 tys. przy realnych 147 tys.
Patrz docs/ads/rmkt-optymalizacja-2026-07-12.md.

Bezpieczniki (cron działa bez nadzoru):
  - MIN_ENTRIES — jeśli build zwróci podejrzanie mało wpisów (padnięta DB, pusty feed importera),
    ABORT bez dotykania konta. Lepiej stary feed niż żaden.
  - MAX_REMOVE_RATIO — jeśli wymiana usunęłaby >40% wpisów, ABORT i zgłoś. Chroni przed wyzerowaniem
    feedu, gdy coś się stanie z taksonomią.
  - transakcja atomowa (partialFailure=false) — albo cały nowy feed, albo nic.
  - dump before przed każdą mutacją.

Użycie:
  python3 scripts/gads-rmkt-feed-refresh.py <feed.json>           # dry-run
  python3 scripts/gads-rmkt-feed-refresh.py <feed.json> --apply
"""
import glob
import json
import os
import sys
import urllib.parse
import urllib.request
import urllib.error
from datetime import datetime

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from gads_client import load, refresh  # noqa: E402

# Wersja API z ~/secrets/google/ads-config.json — hardkod "v21" cicho zabił cron
# na cztery tygodnie (16.08–06.09: push 404, feed stał na stanie z 09.08).
API = json.load(open("/home/host476470/secrets/google/ads-config.json")).get("api_version", "v25")
CID = "9506068500"
FEED_SET = f"customers/{CID}/assetSets/9118300013"
BACKUP_ROOT = os.path.expanduser("~/backups/primaauto/rmkt-feed")

MIN_ENTRIES = 200          # poniżej tego = coś jest nie tak ze źródłem
# Kopie JPG z WebP (Google odrzuca WebP w feedzie) — tworzy je build-gads-hub-feed.php.
JPG_DIR = "/home/host476470/domains/primaauto.com.pl/public_html/feeds/rmkt-img"
MAX_REMOVE_RATIO = 0.40    # nie usuwaj więcej niż 40% feedu bez człowieka

FEED_JSON = next((a for a in sys.argv[1:] if not a.startswith("--")), None)
if not FEED_JSON:
    sys.exit("Podaj <feed.json> (wynik scripts/build-gads-hub-feed.php)")


def hdr():
    o, t, c = load()
    tok = refresh(o, t)
    return {"Authorization": f"Bearer {tok}", "developer-token": c["developer_token"],
            "login-customer-id": CID, "Content-Type": "application/json"}


def gaql(q, h):
    u = f"https://googleads.googleapis.com/{API}/customers/{CID}/googleAds:searchStream"
    d = json.loads(urllib.request.urlopen(urllib.request.Request(
        u, data=json.dumps({"query": q}).encode(), headers=h)).read())
    return [r for b in (d or []) for r in b.get("results", [])]


def prune_jpg(entries):
    """Kasuje kopie JPG, których nie używa feed `entries`. Wołane TYLKO gdy konto = entries
    (po udanym apply albo gdy feed jest aktualny) — inaczej skasowałoby obrazy żywych reklam.
    Nazwy w URL-ach są zakodowane procentowo (CJK w nazwach plików), na dysku leżą zdekodowane."""
    keep = {urllib.parse.unquote(e["dynamicCustomAsset"]["imageUrl"].rsplit("/", 1)[-1])
            for e in entries if "/feeds/rmkt-img/" in e["dynamicCustomAsset"].get("imageUrl", "")}
    usun = [f for f in glob.glob(f"{JPG_DIR}/*.jpg") if os.path.basename(f) not in keep]
    for f in usun:
        os.remove(f)
    print(f"          JPG w rmkt-img: w użyciu {len(keep)} | usunięte stare: {len(usun)}")


def die(msg):
    print(f"ABORT: {msg}", file=sys.stderr)
    sys.exit(1)


new = json.load(open(FEED_JSON))
NEW = {a["dynamicCustomAsset"]["id"]: a for a in new}
if len(NEW) != len(new):
    die("duplikat id w nowym feedzie")
if len(NEW) < MIN_ENTRIES:
    die(f"nowy feed ma tylko {len(NEW)} wpisów (min {MIN_ENTRIES}) — źródło podejrzane, "
        f"NIE dotykam konta")

h = hdr()
before = gaql(
    "SELECT asset_set_asset.resource_name, asset.resource_name, asset.id, asset.final_urls, "
    "asset.dynamic_custom_asset.id, asset.dynamic_custom_asset.item_title, "
    "asset.dynamic_custom_asset.price, asset.dynamic_custom_asset.item_subtitle, "
    "asset.dynamic_custom_asset.item_description, asset.dynamic_custom_asset.image_url "
    f"FROM asset_set_asset WHERE asset_set_asset.asset_set = '{FEED_SET}' "
    "AND asset_set_asset.status = 'ENABLED'", h)

cur = {r["asset"]["dynamicCustomAsset"]["id"]: r["assetSetAsset"]["resourceName"] for r in before}
cur_price = {r["asset"]["dynamicCustomAsset"]["id"]: r["asset"]["dynamicCustomAsset"].get("price")
             for r in before}
cur_img = {r["asset"]["dynamicCustomAsset"]["id"]: r["asset"]["dynamicCustomAsset"].get("imageUrl")
           for r in before}

removed = [k for k in cur if k not in NEW]
added = [k for k in NEW if k not in cur]
repriced = [k for k in NEW if k in cur and NEW[k]["dynamicCustomAsset"].get("price") != cur_price.get(k)]
# zmiana obrazu też jest zmianą — 10.09.2026 sama podmiana WebP→JPG (ceny bez zmian) musiała wejść na konto
reimaged = [k for k in NEW if k in cur and NEW[k]["dynamicCustomAsset"].get("imageUrl") != cur_img.get(k)]
# i zmiana tekstów (tytuł/podtytuł/opis) — inaczej poprawka tytułu pod policy nigdy nie wejdzie
cur_txt = {r["asset"]["dynamicCustomAsset"]["id"]: tuple(r["asset"]["dynamicCustomAsset"].get(f) for f in
           ("itemTitle", "itemSubtitle", "itemDescription")) for r in before}
retexted = [k for k in NEW if k in cur and tuple(NEW[k]["dynamicCustomAsset"].get(f) for f in
            ("itemTitle", "itemSubtitle", "itemDescription")) != cur_txt.get(k)]
webp = [k for k in NEW if (NEW[k]["dynamicCustomAsset"].get("imageUrl") or "").lower().split("?")[0].endswith(".webp")]

stamp = datetime.now().strftime("%Y-%m-%d")
print(f"[{stamp}] feed na koncie: {len(cur)} | nowy build: {len(NEW)}")
print(f"          usuwane: {len(removed)} | dodawane: {len(added)} | zmiana ceny: {len(repriced)} | zmiana obrazu: {len(reimaged)} | zmiana tekstu: {len(retexted)}")
if webp:
    die(f"{len(webp)} wpisów z obrazem WebP — Google je odrzuci (DYNAMIC_DISPLAY_ADS_FEED_IMAGE_FORMAT); "
        f"builder ma konwertować do JPG: {webp[:5]}")

if cur and len(removed) / len(cur) > MAX_REMOVE_RATIO:
    die(f"usunięcie {len(removed)}/{len(cur)} wpisów ({len(removed)/len(cur)*100:.0f}%) "
        f"przekracza próg {MAX_REMOVE_RATIO*100:.0f}% — wymaga decyzji człowieka")

if not (removed or added or repriced or reimaged or retexted):
    print("          bez zmian — feed aktualny, nic nie robię.")
    if "--apply" in sys.argv:
        prune_jpg(new)
    sys.exit(0)

# sanity nowych wpisów
bad = [k for k in NEW if not NEW[k]["dynamicCustomAsset"].get("imageUrl")
       or not (NEW[k].get("finalUrls") or [None])[0]
       or len(NEW[k]["dynamicCustomAsset"].get("itemTitle", "")) > 25]
if bad:
    die(f"{len(bad)} wpisów bez zdjęcia/URL lub z tytułem >25 zn.: {bad[:5]}")

ops = [{"assetSetAssetOperation": {"remove": rn}} for rn in cur.values()]
for i, k in enumerate(NEW):
    tmp = f"customers/{CID}/assets/{-100 - i}"
    ops.append({"assetOperation": {"create": {
        "resourceName": tmp, "finalUrls": NEW[k]["finalUrls"],
        "dynamicCustomAsset": NEW[k]["dynamicCustomAsset"]}}})
    ops.append({"assetSetAssetOperation": {"create": {"assetSet": FEED_SET, "asset": tmp}}})

validate = "--apply" not in sys.argv
if not validate:
    d = f"{BACKUP_ROOT}/{stamp}"
    os.makedirs(d, exist_ok=True)
    json.dump(before, open(f"{d}/feed-before.json", "w"), ensure_ascii=False, indent=2)
    print(f"          dump before -> {d}/feed-before.json")

print(f"          {'DRY-RUN' if validate else 'APPLY'}: {len(ops)} operacji")
u = f"https://googleads.googleapis.com/{API}/customers/{CID}/googleAds:mutate"
body = json.dumps({"mutateOperations": ops, "partialFailure": False,
                   "validateOnly": validate}, ensure_ascii=False).encode()
try:
    resp = json.loads(urllib.request.urlopen(urllib.request.Request(u, data=body, headers=h)).read())
    n = len(resp.get("mutateOperationResponses", []))
    print(f"          {'validate OK' if validate else f'zastosowano {n} operacji'} "
          f"-> feed {len(NEW)} wpisów")
    if not validate:
        prune_jpg(new)
except urllib.error.HTTPError as e:
    die(f"HTTP {e.code}: {e.read().decode()[:1500]}")
