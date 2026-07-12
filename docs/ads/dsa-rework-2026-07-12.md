# DSA rework — 2026-07-12 (WYKONANE)

Kampania: `[DSA] Import modele z Chin` (23896725555) · grupa 197286896339 · konto 9506068500.
Recon: https://auratest.pl/fe4f58fec53ctmp/primaauto-dsa-recon-2026-07-12.html
Symulacja reklam: https://auratest.pl/fe4f58fec53ctmp/primaauto-dsa-symulacja-2026-07-12.html

## Diagnoza (recon 30d: 1297 zł / 2013 kl / 5 konw / IS 9,99% / budget lost 88,8%)

- Feed „huby 2026" z 2026-06-02 przeterminowany: 140/295 hubów sitemap, 8 martwych wpisów,
  28 hubów <3 ofert; brak 44 zasobnych (YU7 55 ofert, Monjaro 52, Sealion 8 DM-i, N9, Zeekry…).
  Skutek przy `useSuppliedUrlsOnly=true`: złe landingi („denza n9"→D9, „zeekr 9x"→Zeekr X).
- Kanibalizacja SKAG ~131 zł/30d (~10% kosztu) — negatywy tylko marka-EXACT, zero model-level.
- Kanibalizacja organiki: marginalna kwotowo → temat otwarty od 06-03 ZAMKNIĘTY.
- 1 reklama EDSA bez assetów (0 sitelinków/calloutów/obrazków).

## Wykonane mutacje (wszystkie zweryfikowane odczytem po apply)

1. **Feed 140 → 163**: −20 (8 martwych/301 + 12 z ≤1 ofertą), +43 zasobne huby
   (bez duplikatu `avatr/avatr-07`; li-auto kanonicznie `i6`).
2. **Negatywy 79 → 125**: +46 EXACT = kopie keywordów 20 ENABLED grup SKAG-1/2
   (model + model cena; dedup „mazda ez-6"≡„ez 6").
3. **CPC grupy: 0,51 → 0,41 → 0,36 zł** (dwie decyzje Janka tego samego dnia).
4. **Reklamy** (stara EDSA usunięta):
   - R1: „Zamów online - bez salonu. Rocznik 2026, minimalny przebieg. Ceny w ogłoszeniach."
     / „Auta z rynku chińskiego na indywidualne zamówienie. Weryfikujemy przed zakupem."
   - R2: „Zarezerwuj teraz - auto weryfikujemy przed zakupem i sprowadzamy do Polski dla Ciebie."
     / „Indywidualne zamówienie, dostawa pod dom, polska homologacja i gwarancja."
   - Intencja Janka (weryfikacja przed zakupem + indywidualne zamówienie + sprowadzamy dla klienta)
     rozbita na obie reklamy (limit opisu 90 zn).
5. **Sitelinki ×4** (kampania): Auta od ręki w Rzeszowie → /w-rzeszowie/ · Auta w drodze do Polski
   → /w-drodze/ · Jak działa rezerwacja → /informacje/proces-zamawiania/ · Faktura VAT i leasing
   → /informacje/finansowanie/. **Callouty ×4**: Zamów online · Dostawa pod dom · Homologacja PL
   · Gwarancja w Polsce.
6. **Budżet 50 → 45 zł/dz** — 5 zł zaparkowane pod przesunięcie do RMKT przy jej optymalizacji.

Backup: `~/backups/primaauto/2026-07-12-dsa/` (before.json, apply-response.json, before-ads.json,
apply-response-ads.json). Skrypty: `tmp/gads_dsa_rework_2026_07_12.py`, `tmp/gads_dsa_ads_2026_07_12.py`
(wzorzec: dump before → validateOnly → --apply).

7. **Obrazki AD_IMAGE ×8** (zestaw A, wybór Janka z galerii
   https://auratest.pl/fe4f58fec53ctmp/primaauto-dsa-kadry-2026-07-12.html):
   4 kadry placowe PRIMA-AUTO × 2 formaty (1.91:1 + 1:1), reuse istniejących assetów
   (355647966218/355733189269, 355810008228/355733195737, 355733195602/355647966467,
   355810008213/355733189617) → ad_group_asset na grupie 197286896339.
   Kadry per-model z SKAG-1 świadomie odrzucone (mismatch obrazek↔zapytanie w DSA).
   Skrypt: `tmp/gads_dsa_images_2026_07_12.py`.

## Otwarte

- Przesunięcie 5 zł/dz do `[RMKT] Dynamic Remarketing` — przy sesji optymalizacji RMKT.
- Recheck D+3/D+7: search terms po negatywach (czy SKAG przejął frazy), landingi po refreshu feedu,
  wpływ CPC 0,36 na IS/rank lost. Decyzja budżetowa (podniesienie) — po czystych danych.
- Sieroty taksonomii zauważone przy feedzie: `avatr/07` vs `avatr/avatr-07` w sitemap (duplikat huba).
