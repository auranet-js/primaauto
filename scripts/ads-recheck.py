#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Recheck konta Google Ads Prima-Auto + zestawienie z GA4 — jedno wywołanie, gotowa tabela.

Powstało 2026-08-19, żeby nie przekopywać całego konta przy każdym rechecku: mapa ról kampanii
siedzi w docs/ads/mapa-kampanii.md, a ten skrypt dowozi do niej świeże liczby.

    python3 scripts/ads-recheck.py            # tabele + strażnik na stdout
    python3 scripts/ads-recheck.py --md       # w składni Markdown, do wklejenia w mapę
    python3 scripts/ads-recheck.py --json plik.json   # surowy dump
    python3 scripts/ads-recheck.py --bez-straznika    # pomija sprawdzanie landingów (offline)

STRAŻNIK (od 2026-08-31) — dwie rzeczy, które potrafią zabić kampanię po cichu:
  1. reklama z `DISAPPROVED` przestaje się wyświetlać, a kampania dalej wygląda na ENABLED
     (tak [DSA] straciła 22.08 swoją główną reklamę i nikt tego nie zauważył przez 9 dni),
  2. landing reklamy przestaje istnieć — oferty rotują szybko: z 320 ofert, które miały ruch
     1–15.07, po siedmiu tygodniach żyje 22%, 78% przekierowuje na hub modelu, a 0,3% oddaje 410.
     Przekierowanie jest nieszkodliwe, 404/410 nie.

Read-only — żadnych mutacji na koncie.

Gotchy:
- wersja API z ~/secrets/google/ads-config.json (`api_version`), NIGDY hardkod — v21 zwraca 404,
- `campaign.start_date` NIE ISTNIEJE w v25 (400 INVALID_ARGUMENT); daty startu szukaj w docs/ads/,
- `login-customer-id` = 9506068500 (konto direct, nie pod MCC),
- konwersje Ads i zdarzenia GA4 NIE SĄ tą samą liczbą — patrz sekcja rozjazdu w mapie.
"""
import json, sys, urllib.request, urllib.error
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
sys.path.insert(0, str(Path(__file__).parent.parent / "tmp"))
from gads_client import load, refresh          # noqa: E402
from ga4_query import token as ga4_token, run as ga4_run, rows as ga4_rows  # noqa: E402

CID = "9506068500"
EVENTS = ["click_phone", "click_whatsapp", "generate_lead", "form_submit"]


def ads_query(gaql, api, hdr):
    out, tok = [], None
    while True:
        body = {"query": gaql}
        if tok:
            body["pageToken"] = tok
        req = urllib.request.Request(
            f"https://googleads.googleapis.com/{api}/customers/{CID}/googleAds:search",
            data=json.dumps(body).encode(), headers=hdr)
        d = json.load(urllib.request.urlopen(req))
        out += d.get("results", [])
        tok = d.get("nextPageToken")
        if not tok:
            return out


def collect():
    oauth, tokens, cfg = load()
    api = cfg.get("api_version", "v25")
    hdr = {"Authorization": f"Bearer {refresh(oauth, tokens)}",
           "developer-token": cfg["developer_token"],
           "login-customer-id": CID, "Content-Type": "application/json"}

    camps = ads_query("""SELECT campaign.id, campaign.name, campaign.status,
        campaign.advertising_channel_type, campaign.bidding_strategy_type,
        campaign_budget.amount_micros FROM campaign WHERE campaign.status != 'REMOVED'""", api, hdr)

    met = {}
    for label, during in (("7d", "LAST_7_DAYS"), ("30d", "LAST_30_DAYS")):
        met[label] = {r["campaign"]["id"]: r["metrics"] for r in ads_query(
            f"""SELECT campaign.id, metrics.impressions, metrics.clicks, metrics.cost_micros,
                metrics.conversions, metrics.all_conversions FROM campaign
                WHERE segments.date DURING {during} AND campaign.status != 'REMOVED'""", api, hdr)}

    ga4 = {}
    tok = ga4_token()
    d = ga4_run(tok, {"dateRanges": [{"startDate": "30daysAgo", "endDate": "yesterday"}],
                      "dimensions": [{"name": "sessionCampaignName"}, {"name": "eventName"}],
                      "metrics": [{"name": "eventCount"}],
                      "dimensionFilter": {"filter": {"fieldName": "eventName",
                                                     "inListFilter": {"values": EVENTS}}},
                      "limit": 200})
    for dims, m in ga4_rows(d)[2]:
        ga4.setdefault(dims[0], {})[dims[1]] = int(float(m[0]))

    sess = {}
    d = ga4_run(tok, {"dateRanges": [{"startDate": "30daysAgo", "endDate": "yesterday"}],
                      "dimensions": [{"name": "sessionCampaignName"}],
                      "metrics": [{"name": "sessions"}], "limit": 100})
    for dims, m in ga4_rows(d)[2]:
        sess[dims[0]] = int(float(m[0]))

    karty = [] if "--bez-straznika" in sys.argv else ads_query(
        """SELECT asset.id, asset.name, asset.final_urls FROM asset
           WHERE asset.type = 'DEMAND_GEN_CAROUSEL_CARD'""", api, hdr)

    reklamy = [] if "--bez-straznika" in sys.argv else ads_query(
        """SELECT campaign.name, campaign.status, ad_group.name, ad_group.status,
            ad_group_ad.ad.id, ad_group_ad.status, ad_group_ad.ad.final_urls,
            ad_group_ad.policy_summary.approval_status
           FROM ad_group_ad WHERE ad_group_ad.status != 'REMOVED'
            AND campaign.status != 'REMOVED'""", api, hdr)

    return {"api": api, "campaigns": camps, "metrics": met, "ga4_events": ga4,
            "ga4_sessions": sess, "reklamy": reklamy, "karty": karty}


def kod(url):
    """Kod HTTP landingu. Przekierowanie zwracamy jako '301 → cel', nie jako błąd."""
    try:
        r = urllib.request.urlopen(urllib.request.Request(
            url, headers={"User-Agent": "Mozilla/5.0 (ads-recheck landing guard)"}), timeout=25)
        cel = r.geturl()
        return (301, cel) if cel.rstrip("/") != url.rstrip("/") else (200, None)
    except urllib.error.HTTPError as e:
        return (e.code, None)
    except Exception as e:                       # DNS, timeout, TLS — też warto zobaczyć
        return (0, type(e).__name__)


def straznik(data):
    """Dwa ciche zabójcy: reklamy DISAPPROVED i landingi, które przestały istnieć."""
    reklamy = data.get("reklamy")
    if reklamy is None:
        return
    print("\n" + "=" * 78 + "\nSTRAŻNIK — sprawdź to, zanim ruszysz cokolwiek\n" + "=" * 78)

    odrzucone = [r for r in reklamy
                 if r["adGroupAd"].get("policySummary", {}).get("approvalStatus") == "DISAPPROVED"]
    if odrzucone:
        print(f"\n[!] reklamy DISAPPROVED — NIE wyświetlają się ({len(odrzucone)}):")
        for r in odrzucone:
            print(f'    {r["campaign"]["name"][:30]:30} / {r["adGroup"]["name"][:26]:26} '
                  f'ad={r["adGroupAd"]["ad"]["id"]} ({r["adGroupAd"]["status"]})')
    else:
        print("\n[ok] żadna reklama nie jest DISAPPROVED")
    ograniczone = sum(1 for r in reklamy
                      if r["adGroupAd"].get("policySummary", {}).get("approvalStatus") == "APPROVED_LIMITED")
    if ograniczone:
        print(f"[--] APPROVED_LIMITED: {ograniczone} — NIE przepisuj tekstów, patrz mapa sekcja 7")

    urle = {}
    for r in reklamy:
        for u in r["adGroupAd"]["ad"].get("finalUrls", []):
            urle.setdefault(u, []).append(
                f'{r["campaign"]["name"][:22]}/{r["adGroupAd"]["ad"]["id"]}')
    # karty karuzeli mają WŁASNE final_urls, których nie ma na poziomie reklamy —
    # bez tego strażnik miał ślepą plamkę (znalezione 31.08 przy pierwszym statusie)
    for k in data.get("karty", []):
        for u in k["asset"].get("finalUrls", []):
            urle.setdefault(u, []).append(f'karta/{k["asset"]["id"]}')
    if not urle:
        return
    with ThreadPoolExecutor(max_workers=8) as pool:
        wyniki = dict(zip(urle, pool.map(kod, urle)))
    zle = {u: w for u, w in wyniki.items() if w[0] not in (200, 301)}
    przek = {u: w for u, w in wyniki.items() if w[0] == 301}
    print(f"\nlandingi reklam: {len(urle)} sprawdzonych, "
          f"{len(urle) - len(zle) - len(przek)} × 200, {len(przek)} × przekierowanie, {len(zle)} × problem")
    for u, w in przek.items():
        print(f'    [--] {w[1]}\n         ← {u}  ({", ".join(urle[u])})')
    for u, w in zle.items():
        print(f'    [!!] {w[0] or w[1]}  {u}  ({", ".join(urle[u])})')
    if zle:
        print("\n    Landing, który nie odpowiada 200/301, to martwa reklama — podmień URL")
        print("    albo wyłącz reklamę. Oferty rotują: 78% znika w 7 tygodni (pomiar 31.08).")


def report(data, md=False):
    sep, pipe = ("| ", " |") if md else ("", "")
    camps = sorted(data["campaigns"], key=lambda c: c["campaign"]["name"])
    if md:
        print("| kampania | stan | budżet/dz | 7d koszt | 7d konw | 30d koszt | 30d kliki | 30d konw | CPA 30d | GA4 sesje | GA4 phone+wa |")
        print("|---|---|---|---|---|---|---|---|---|---|---|")
    else:
        print(f'{"kampania":44} {"stan":<8} {"bud":>6} {"7d zł":>8} {"7d k":>5} {"30d zł":>9} {"kliki":>6} {"konw":>6} {"CPA":>7} {"GA4 ses":>8} {"GA4 ev":>7}')
    tot = {"c7": 0, "c30": 0, "k7": 0, "k30": 0, "ga4": 0}
    for c in camps:
        cm = c["campaign"]
        cid, nm = cm["id"], cm["name"]
        bud = int(c.get("campaignBudget", {}).get("amountMicros", 0)) / 1e6
        a, b = data["metrics"]["7d"].get(cid, {}), data["metrics"]["30d"].get(cid, {})
        c7, k7 = int(a.get("costMicros", 0)) / 1e6, float(a.get("conversions", 0))
        c30, k30 = int(b.get("costMicros", 0)) / 1e6, float(b.get("conversions", 0))
        clicks = int(float(b.get("clicks", 0)))
        ev = data["ga4_events"].get(nm, {})
        ga4ev = ev.get("click_phone", 0) + ev.get("click_whatsapp", 0)
        ses = data["ga4_sessions"].get(nm, 0)
        cpa = f"{c30 / k30:.0f}" if k30 else "—"
        for key, val in (("c7", c7), ("c30", c30), ("k7", k7), ("k30", k30), ("ga4", ga4ev)):
            tot[key] += val
        if md:
            print(f"| {nm} | {cm['status']} | {bud:.0f} zł | {c7:.0f} zł | {k7:.1f} | {c30:.0f} zł | {clicks} | {k30:.1f} | {cpa} zł | {ses} | {ga4ev} |")
        else:
            print(f'{nm[:44]:44} {cm["status"]:<8} {bud:>6.0f} {c7:>8.2f} {k7:>5.1f} {c30:>9.2f} {clicks:>6} {k30:>6.1f} {cpa:>7} {ses:>8} {ga4ev:>7}')
    line = (f"| **RAZEM** | | | {tot['c7']:.0f} zł | {tot['k7']:.1f} | {tot['c30']:.0f} zł | | "
            f"{tot['k30']:.1f} | {tot['c30'] / tot['k30'] if tot['k30'] else 0:.0f} zł | | {tot['ga4']} |") if md else \
           (f'{"RAZEM":44} {"":<8} {"":>6} {tot["c7"]:>8.2f} {tot["k7"]:>5.1f} {tot["c30"]:>9.2f} '
            f'{"":>6} {tot["k30"]:>6.1f} {tot["c30"] / tot["k30"] if tot["k30"] else 0:>7.0f} {"":>8} {tot["ga4"]:>7}')
    print(line)
    if not md:
        print(f'\nRozjazd Ads vs GA4 (30 d): Ads {tot["k30"]:.1f} konwersji, GA4 {tot["ga4"]} zdarzeń '
              f'click_phone+click_whatsapp — Ads liczy o {(tot["k30"] / tot["ga4"] - 1) * 100 if tot["ga4"] else 0:.0f}% więcej.')


if __name__ == "__main__":
    data = collect()
    if "--json" in sys.argv:
        out = sys.argv[sys.argv.index("--json") + 1]
        json.dump(data, open(out, "w"), ensure_ascii=False, indent=1)
        print(f"zapisano {out}")
    report(data, md="--md" in sys.argv)
    straznik(data)
