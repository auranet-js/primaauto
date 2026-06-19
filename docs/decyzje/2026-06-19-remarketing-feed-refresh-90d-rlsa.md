# ADR 2026-06-19 — Remarketing: refresh feedu, lista 90 dni, RLSA obserwacja

**Konto:** Google Ads Prima-Auto `9506068500`. Kampania `[RMKT] Dynamic Remarketing — Model-huby` (`23897599362`).
**Kontekst:** analiza kampanii remarketingowej (czy nie brakuje komponentów). Baza → [`2026-06-02-...`](../../.claude) sesja faza 2.

## Stan zastany (analiza)
- Struktura kompletna: DISPLAY, Manual CPC, budżet **10→15 zł**, audiencja `All visitors` (840, 30d), feed `9118300013` (309), wykluczenia (parked + 2 app cat), RDA APPROVED, **Ad Strength GOOD**, optimized targeting **OFF**.
- Wyniki 30d: 5 konw., **CPA 6,2 zł** (2. po Brandzie), CTR 4,2%, CPC 0,18. Działa.
- **Braki:** feed nierotowany od 02.06 (30×301 slug drift, 5 martwych serie-id, ~brakujące modele); audiencja wąska (tylko 30d, brak 90d); RLSA nieużyte; RDA: opisy 3/5, brak logo 4:1, brak wideo.

## Decyzje + wdrożenie (live)
1. **Feed odświeżony: 309 → 312 hubów.** +10 nowych modeli, −7 martwych/nieaktualnych serie-id, **30 finalUrl skanonikalizowanych** (301→200, pełny łańcuch redirectów; np. galaxy→geely, /model/→/samochody/). Wszystkie 312 = HTTP 200. Generator: `tmp/build-gads-hub-feed-2026-06-02.php` (NON_CHINESE zsync). Delta-push: `tmp/gads_rmkt_feed_update_2026_06_19.py` (ADD/REMOVE/UPDATE, dry-run/`--apply`).
2. **Lista 90-dniowa utworzona:** `userLists/9414602400` „Wszyscy odwiedzający — 90 dni" (RULE_BASED, url CONTAINS primaauto.com.pl, prepopulation REQUESTED). Podpięta **obok** 840 (additive, bez przerwy). Napełnia się w czasie; serwuje po progu 100. Skrypt: `tmp/gads_create_rmkt_list_90d_2026_06_19.py`.
3. **RLSA obserwacja:** lista 90d dodana do **5 kampanii Search** (Brand/Topic/SKAG-1/SKAG-2/DSA) jako **OBSERWACJA**. Na SKAG-1/2/DSA wymuszono `targetingSetting AUDIENCE bidOnly:true` (potwierdzone na wszystkich 5 — **zero zawężenia zasięgu**). Skrypt: `tmp/gads_rlsa_observation_2026_06_19.py`. Pozwala mierzyć wracających i ew. podbić bid; teraz nic nie zmienia w serwowaniu.
4. **RDA — Janek ręcznie w UI** (immutability: edycja w UI tworzy nową wersję bez przerwy). Do dodania: 2 opisy (`Sprawdź auto, które oglądałeś — wycena i dostępność bez zobowiązań.` / `Elektryczne i hybrydowe SUV-y i sedany z Chin. Bezpieczny import.`) + logo poziome 4:1 (brak pliku 4:1 na koncie — wymaga grafiki). Wideo YouTube = osobny temat materiału.

## Backup
`~/backups/primaauto/2026-06-19-ads-dsa/` (rmkt-feed-assets-before.json + rmkt-assetset-links-before.json).

## Monitoring
- Lista 90d: sprawdzić rozmiar za ~3–7 dni (czy >100, czy serwuje); gdy dojrzeje — rozważyć przełączenie z 840 na 90d jako główną.
- RLSA: po ~14d zobaczyć konwersje segmentu „wracający" na Search → ew. positive bid modifier.
- Feed: dorobić **cron cotygodniowej rotacji** (memory: miała być, nie ma) — generator + delta-push gotowe do zaplanowania.
