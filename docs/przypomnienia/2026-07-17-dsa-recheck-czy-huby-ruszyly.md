# Recheck DSA — czy 14 odblokowanych hubów faktycznie ruszyło

> **✅ RECHECK #1 (17.07) ZROBIONY** — kampania stanęła: 104 impr vs ~1300/dz, oferty 0/133. Diagnoza: feed lepki + `landing_page_view` zawyżał (fałszywy trop „DSA serwuje /samochody/").
> Memory: `project_dsa_start_na_ofertach_2026_07_17.md`, `reference_gads_landing_page_view_sitelink_inflation.md`.
> **⏳ RECHECK #2 nadal OTWARTY — 2026-07-30** (czy pomogło).

> Utworzone: 2026-07-16. Recheck #1: **2026-07-17** (czy ruszyło), recheck #2: **2026-07-30** (czy pomogło).
> Kampania `[DSA] Import modele z Chin` (`23896725555`), konto `9506068500`, grupa `197286896339`.

## Dlaczego ten recheck w ogóle istnieje

**Rework z 12.07 zgłosił „OK" i nie zadziałał.** Dodał 43 huby do page feedu bez etykiety
`dsa2026`, a grupa celuje wyłącznie `CUSTOM_LABEL EQUALS 'dsa2026'` → 43 huby leżały martwe
**4 dni**, z zerem wyświetleń, i nikt tego nie zauważył, bo nikt nie sprawdził impresji po fakcie.

Skutek uboczny był mylący: martwy hub nie daje „braku reklamy" — Google podstawia **najbliższy
etykietowany** hub. Stąd fałszywe „Google źle dopasowuje": `zeekr 9x` → `xpeng/x9`,
`denza n9` → `denza/d9-dm-i`, `deepal s09` → `deepal/s07`.

**Dlatego dowodem NIE jest stan konfiguracji (ten był PASS 16.07), tylko impresje.**
Patrz memory `reference_dsa_pagefeed_label_gotcha.md`.

## Co zmieniono 16.07 (weryfikacja konfiguracji: cała PASS)

| | Zmiana | Stan |
|---|---|---|
| A | `trackingUrlTemplate` = `{lpurl}#oferty` na kampanii | landing od razu na liście ofert, z pominięciem leadu + tabeli spec (~1900 px = ~2,5 ekranu mobile) |
| B | 3 negatywy PHRASE: `prima auto`, `primaauto`, `prima-auto` | brand wpadał w DSA zamiast do kampanii Brand (6 kl/4 dni) |
| C | 5 hubów bez etykiety → re-add z `dsa2026` | `byd/song-plus-ev`, `deepal/sl03`, `jetour/jetour-shanhai-l7-plus`, `li-auto/i6`, `xpeng/p7-plus` |
| D | 38 martwych wpisów usuniętych | sprzeczne z polityką generatora (SKAG_EXCLUDE + NON_CHINESE) |
| E | 11 brakujących dodanych z `dsa2026` | m.in. `aito/m7` (63 oferty), `deepal/s09`, `nio/et5`, `wey/07` |
| F | 2 huby wycięte (decyzja Janka) | `exeed/es` (3/3 CJK), `geely/a7-em` (21/38 CJK) — patrz „Dług" niżej |

Feed: 163 → **134**, wpisów bez etykiety: 43 → **0**. Wszystkie 136 URL-i sprawdzone
curl-em: 136× HTTP 200 + 136× obecna kotwica `id="oferty"`.

Skrypty: `tmp/gads_dsa_optimize_2026_07_16.py`, `tmp/gads_dsa_fix_labels_2026_07_16.py`,
`tmp/gads_dsa_cut_cjk_2026_07_16.py`. Backup: `~/backups/primaauto/2026-07-16-dsa/snapshot-przed.json`.

## Recheck #1 (17.07) — czy ruszyło

```bash
cd ~/projekty/primaauto && python3 - <<'EOF'
import sys, json, urllib.request
sys.path.insert(0,'tmp'); from gads_client import load, refresh
oauth, tokens, cfg = load(); at = refresh(oauth, tokens)
CID='9506068500'
H={"Authorization":f"Bearer {at}","developer-token":cfg['developer_token'],
   "login-customer-id":cfg['mcc_customer_id'],"Content-Type":"application/json"}
r=urllib.request.Request(f'https://googleads.googleapis.com/v21/customers/{CID}/googleAds:search',
  data=json.dumps({'query':"""SELECT dynamic_search_ads_search_term_view.search_term,
   dynamic_search_ads_search_term_view.landing_page, metrics.impressions, metrics.clicks
   FROM dynamic_search_ads_search_term_view WHERE campaign.id = 23896725555
   AND segments.date DURING TODAY"""}).encode(), headers=H)
rows=json.load(urllib.request.urlopen(r)).get('results',[])
NOWE={"aito/m7","deepal/s09","nio/et5","voyah/zhiyin","wey/07","gac/hyper-ht","ford/ford-bronco",
      "haval/haval-menglong-ice","hongqi/h5-phev","byd/song-plus-ev","deepal/sl03",
      "jetour/jetour-shanhai-l7-plus","li-auto/i6","xpeng/p7-plus"}
agg={}
for x in rows:
    v=x['dynamicSearchAdsSearchTermView']; p=v.get('landingPage','').rstrip('/').split('/samochody/')[-1]
    a=agg.setdefault(p,[0,0]); a[0]+=int(x['metrics'].get('impressions',0)); a[1]+=int(x['metrics'].get('clicks',0))
zywe=[p for p in NOWE if agg.get(p,[0])[0]>0]
print(f'14 odblokowanych — z impresjami: {len(zywe)}/14')
for p in sorted(NOWE): print(f"   {'ŻYJE ' if agg.get(p,[0])[0]>0 else 'cisza'} {p:<34} imp={agg.get(p,[0,0])[0]:>4} kl={agg.get(p,[0,0])[1]}")
EOF
```

**Zrobione =** przynajmniej kilka z 14 ma impresje > 0. Szczególnie `aito/m7` (63 oferty)
i `deepal/s09` — jeśli one milczą po 24 h, mechanizm etykiet **nadal** nie działa i trzeba
kopać dalej (nie zakładać, że „potrzeba więcej czasu").

**Sprawdź też, czy mismatche zniknęły** — `zeekr 9x` powinno teraz iść na `zeekr/9x`
(a nie `xpeng/x9`), `deepal s09` na `deepal/s09` (a nie `deepal/s07`).

## Recheck #2 (30.07) — czy pomogło

- **CTR i konwersje po `#oferty`** — porównaj okno 17–30.07 z 16.06–15.07 (przed).
  Punkt wyjścia: 1755 kl / 1064 zł / 30 dni, konwersje ~**0** (w top 20 najdroższych hubów
  jedyne 2 konwersje miał `baic/bj40-erev` przy 28 zł).
- **Czy brand zniknął z DSA** — `prima auto` / `primaauto` nie powinny się już pojawiać
  w `dynamic_search_ads_search_term_view`.
- **GA4:** `page_location` hubów rozbije się na wiersze z `#oferty` i bez — to darmowy
  znacznik ruchu z DSA, ale raporty hubów trzeba agregować po ścieżce bez fragmentu.

## Dług / otwarte — NIE gubić

1. **CJK w tytułach ofert: 40 sztuk / 15 modeli** (`Galaxy 银河A7 EM 2025 150km Starship`).
   Dług z importów 10.05–30.06, nie regres (feed Dongchedi zamrożony od 01.07). Blokuje powrót
   `geely/a7-em` (38 ofert!) i `exeed/es` do DSA. W feedzie zostaje 5 hubów z 1-2 ofertami CJK:
   `geely/coolray`, `byd/seal-06-gt`, `dongfeng/e-008`, `voyah/taishan`, `geely/atlas-pro`.
   → osobny task: reguła czyszczenia CJK z nazw wersji + backfill.
2. **20 hubów serwuje mimo że nie przechodzą gate** rocznika 2025/2026 ≥3 auta
   (`jetour/t2` 69 kl/30d, `hongqi/hs5` 30 kl, `deepal/s05`, `geely/coolray`). Ślepy sync
   do `scripts/build-dsa-pagefeed.php` by je wyciął = utrata realnego ruchu. Decyzja Janka.
3. **`$NON_CHINESE` w generatorze jest niekompletna** — zawiera Mazdę, MG, Nissana, ale
   **nie Forda**, więc `ford/ford-bronco` przeszedł do feedu (Bronco jest produkowane
   w Chinach przez JMC, więc zostawiony świadomie — ale lista wymaga przeglądu).
4. **Konwersje ≈ 0 to problem lejka, nie linkowania.** `#oferty` skraca drogę do ofert,
   ale jeśli `/oferta/` nie domyka, DSA dalej będzie kandydatem do wyłączenia — i to jest
   właściwy powód, dla którego Janek rozważa rezygnację z DSA.
