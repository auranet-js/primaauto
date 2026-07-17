# ADR 2026-07-17 — DSA: rozszerzenie geo-targetingu na całą Polskę

**Kampania:** DSA `23896725555` (konto Ads 9506068500)
**Status:** wdrożone 2026-07-17

## Kontekst

Wątek [[project_dsa_start_na_ofertach_2026_07_17]]: po swapie feedu DSA z hubów modelowych na
oferty (16.07 21:45) kampania stanęła — 17.07 tylko ~120 impr vs baseline 1241–1453/dobę.
Hipoteza Janka: przyczyną może być zawężenie geo-targetingu do 8 lokalizacji (2 województwa +
6 miast), co dusi zasięg.

## Ustalenie (dane)

**Hipoteza „geo dusi zasięg" — obalona.** Te same 8 lokalizacji obsługiwało 1241–1453 impr/dobę
jeszcze 14–16.07. Geo było statyczne; spadek do ~120 (17.07) zbiega się co do godziny ze swapem
feedu, nie ze zmianą geo. Stall = lag propagacji page feedu (arbiter
`dynamic_search_ads_search_term_view` 17.07: DSA nadal serwuje głównie stare huby
`/samochody/{marka}/{model}/`, z nowego feedu weszła 1 oferta) — **nie geo**.

## Decyzja

Rozszerzyć geo na całą Polskę jako **osobną optymalizację zasięgu** (nie jako fix stallu):
Prima-Auto dostarcza auto pod dom w całej Polsce, więc ograniczenie do 2 województw + 6 miast
(wycinało Śląsk, Wrocław, Szczecin, cały zachód) było nieuzasadnionym sufitem zasięgu.

**Wykonanie:** `campaignCriteria:mutate` → create LOCATION `geoTargetConstants/2616` (Poland),
`negative=false`. Kryterium ENABLED. 8 → 9 kryteriów LOCATION.

**Zachowane modyfikatory stawek** (bardziej szczegółowa lokalizacja wygrywa dla bidu):
- Podkarpackie (20856) +30% — plac Rzeszów
- Małopolska (20852) +15%
- Warszawa (1011419) +15%
- Kraków/Łódź/Lublin/Gdańsk/Poznań — bez modyfikatora (efektywnie neutralne po dodaniu Polski)

Skrypt: `tmp/dsa-add-poland-geo.py` (validateOnly dry-run → `--live`).

## Konsekwencje

- Pula adresowalnych zapytań rośnie z 2 woj./6 miast do całej PL; budżet (~47 zł/dobę) rozkłada się
  szerzej, ale przy DSA priorytetem jest rozpęd, nie precyzja.
- **Pomiar wpływu geo dopiero 19–20.07** — po odetkaniu feedu (okno do 18.07 wieczór), żeby nie
  mieszać dwóch zmiennych (feed + geo).
- **Odwracalne:** `remove` kryterium `customers/9506068500/campaignCriteria/23896725555~2616`.
