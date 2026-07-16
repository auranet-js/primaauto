#!/usr/bin/env python3
"""URL Inspection dla hubow, ktore maja tresc i oferty, a nie rankuja na wlasna nazwe."""
import json, urllib.parse, urllib.request

SECRETS = "/home/host476470/secrets/google"
SITE = "https://primaauto.com.pl/"

URLS = [
    ("tank 700",          "https://primaauto.com.pl/samochody/tank/700-hi4-t/"),
    ("geely atlas pro",   "https://primaauto.com.pl/samochody/geely/atlas-pro/"),
    ("geely preface",     "https://primaauto.com.pl/samochody/geely/preface/"),
    ("byd tang dm-i",     "https://primaauto.com.pl/samochody/byd/tang-dm-i/"),
    ("changan cs75 plus", "https://primaauto.com.pl/samochody/changan/cs75-plus/"),
    ("tank 300",          "https://primaauto.com.pl/samochody/tank/300/"),
    ("mg 7",              "https://primaauto.com.pl/samochody/mg/7/"),
    ("byd qin l dm-i",    "https://primaauto.com.pl/samochody/byd/qin-l-dm-i/"),
]


def token():
    o = json.load(open(f"{SECRETS}/oauth-desktop-client.json"))["installed"]
    t = json.load(open(f"{SECRETS}/tokens.json"))
    data = urllib.parse.urlencode({
        "client_id": o["client_id"], "client_secret": o["client_secret"],
        "refresh_token": t["refresh_token"], "grant_type": "refresh_token"}).encode()
    return json.load(urllib.request.urlopen(
        urllib.request.Request("https://oauth2.googleapis.com/token", data=data)))["access_token"]


tok = token()
print(f"{'fraza':<20}{'verdict':<10}{'coverage':<38}{'lastCrawl':<12}{'canonical OK'}")
print("-" * 96)
for label, url in URLS:
    body = {"inspectionUrl": url, "siteUrl": SITE, "languageCode": "pl"}
    req = urllib.request.Request(
        "https://searchconsole.googleapis.com/v1/urlInspection/index:inspect",
        data=json.dumps(body).encode(),
        headers={"Authorization": f"Bearer {tok}", "Content-Type": "application/json"})
    try:
        r = json.load(urllib.request.urlopen(req))["inspectionResult"]["indexStatusResult"]
    except Exception as e:
        print(f"{label:<20}ERR {e}")
        continue
    crawl = (r.get("lastCrawlTime") or "—")[:10]
    goog = r.get("googleCanonical", "")
    user = r.get("userCanonical", "")
    same = "tak" if goog == user else f"NIE: {goog[:44]}"
    print(f"{label:<20}{r.get('verdict','?'):<10}{r.get('coverageState','?')[:37]:<38}{crawl:<12}{same}")
