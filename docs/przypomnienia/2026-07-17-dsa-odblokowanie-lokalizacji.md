# PROMPT (nowy wątek) — DSA: odblokować lokalizację, żeby szybciej złapało

> **✅ ZROBIONE 2026-07-17.** Geo DSA rozszerzone na całą Polskę; **hipoteza „geo = dławik" OBALONA** — przyczyną stallu był feed.
> ADR w commicie 9270c30 · memory `project_dsa_start_na_ofertach_2026_07_17.md`.

**Data utworzenia:** 2026-07-17
**Projekt:** primaauto · **Konto Ads:** 9506068500 · **Kampania DSA:** `23896725555`

## Cel

Rozważyć i (jeśli zasadne) wdrożyć **rozszerzenie geo-targetingu DSA na całą Polskę**. Hipoteza Janka:
DSA stanęła nie tylko przez lag propagacji feedu, ale bo **targeting jest zawężony do 8 lokalizacji**,
co dusi zasięg.

## Stan na 2026-07-17 (recon)

DSA `23896725555` ma **8 kryteriów LOCATION** (żaden ≠ „cała Polska"):

| geoTargetConstant | typ | obszar | bid mod |
|---|---|---|---|
| 20852 | Region | Małopolska | +15% |
| 20856 | Region | Podkarpackie | **+30%** |
| 1011367 | City | Kraków | — |
| 1011419 | City | Warszawa | +15% |
| 1011320 | City | Łódź | — |
| 1011347 | City | Lublin | — |
| 1011475 | City | Gdańsk | — |
| 1011615 | City | Poznań | — |

Kontekst awarii: [[project_dsa_start_na_ofertach_2026_07_17]] — 17.07 tylko 104 impr vs ~1300/dz,
oferty 0/133; diagnoza była „lag propagacji feedu", deadline decyzyjny 18.07 rano. **Geo to druga,
nierozpatrzona wtedy hipoteza.**

## Do zrobienia

1. **Najpierw sprawdź aktualny stan** (mogło się zmienić od 17.07):
   - Impresje DSA ostatnie 1-3 dni (czy feed już złapał): `gads_client.py` → `campaign` metrics.
   - Czy geo nadal 8 lokalizacji.
2. **Decyzja geo:** rozszerzyć na `geoTargetConstants/2616` (Poland)? Argument ZA: Prima-Auto dostarcza
   auto pod dom w całej Polsce (dostawa nie tylko Rzeszów), więc zawężanie do 8 miast/2 województw jest
   nieuzasadnione biznesowo i dusi DSA na starcie. Argument PRZECIW: rozmycie budżetu — ale przy DSA
   z małym zasięgiem priorytetem jest ROZPĘD, nie precyzja.
3. **Bid modifiers:** przy przejściu na Poland zdecyduj, czy zachować podbicia (Podkarpackie +30% ma sens
   — plac Rzeszów; patrz [[project_segmentacja_targeting_2026_07_12]] — Podkarpackie=plac Rzeszów).
   Można target=Poland + location bid modifier +30% na Podkarpackie.
4. **Wdrożenie:** `campaign_criterion:mutate` — `create` location Poland (+ ewentualne bid modifiers),
   rozważ `remove` wąskich miast (albo zostaw jako bid-modifier only). Uwaga: bardziej szczegółowa
   lokalizacja wygrywa dla bidu.
5. **Monitoruj** wzrost impresji 24-48h po zmianie; porównaj z hipotezą feedu (jeśli feed OK a impr rosną
   po geo → geo był głównym dławikiem).

## Definicja „zrobione"

Geo DSA rozszerzone (lub świadoma decyzja NIE), zweryfikowany wzrost zasięgu, wpis do
[[project_dsa_start_na_ofertach_2026_07_17]] / ADR jeśli zmiana strukturalna.

## Narzędzia / kontekst

- `tmp/gads_client.py` (konto 9506068500) — patrz [[reference_google_ads_api_client]]
- START sesji Ads: [[project_session_2026_06_19_ads_dsa_remarketing]] (NIE ruszać bez Janka)
- Gotchy DSA: [[reference_dsa_pagefeed_label_gotcha]], [[reference_dsa_naglowek_z_title_landingu]],
  [[reference_gads_landing_page_view_sitelink_inflation]]
- Gotchy API: [[reference_gads_rda_api_gotchas]] (updateMask na liście, wyjście na stderr)
