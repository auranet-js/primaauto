# Decyzja: mapowanie marek/modeli v6.1 (taksonomia)

> Data: 2026-04-23
> Wdrożone w: `f423728` ([fix] v0.31.6 — importer używa mapping v6.1 do post_title/slug)
> Dotyczy: `class-asiaauto-importer.php`, taksonomie `make` + `serie`, slugi URL, redirect-mapy
> Aktualne mapowanie: `docs/architektura/mapowanie-marek-modeli-v6.1.csv`

## Tło

Klient dostarczył `tmp/mapowanie-marek-modeli.xlsx` z mapowaniem 263 wierszy CN→EU brand/model. Po analizie (90 zmian względem v6) i quizie konsultacyjnym z klientem zapadły poniższe **16 decyzji projektowych** sprowadzających mapowanie do v6.1.

## Zasady systemowe

- **Slug URL**: czysty EU-name (bez nawiasów — `sanitize_title` zjada)
- **Label filtra (sidebar)**: kolumna F raw — z nawiasami, dual-name w pattern `ATTO 3 (Yuan PLUS)`
- **Title / H1 / `<title>`**: kolumna G — finalna decyzja per wiersz
- **DM-i / EV w serie**: zostaje gdy istnieją warianty napędu tego samego modelu, zdejmowane gdy tylko jedna wersja

## 16 decyzji quizu z klientem

| # | Decyzja | Efekt |
|---|---|---|
| Q1 | A | BYD Leopard 3/5/7/8 — title z prefiksem `BYD` |
| Q2 | A | `BYD Leopard 7 PHEV` — PHEV zostaje w title |
| Q3 | B | `BYD Leopard 5 (Denza B5)`, `BYD Leopard 8 (Denza B8)` — nawiasy dual-name |
| Q4 | A | `Sealion` (jedno słowo) — uniformity wszędzie (było: `Sea Lion`, `SeaLion`, `Sealion` — pomieszane) |
| Q5 | B | `Exeed Exlantix ET` w title (z „Exeed") — marka EU pozostaje Exlantix |
| Q6 | C | Hyptec HT: marka=`GAC`, title=`GAC Hyptec HT`, slug `/samochody/gac/hyptec-ht/` (poprawka literówki `GAC Aion Hypec`) |
| Q7 | C | `GWM Cannon Great Wall Pao` — cztery człony jak klient |
| Q8 | C | `Haval Big Dog Dargo` — trzy człony jak klient |
| Q9 | C | `Haval Xiaolong MAX H6` — trzy człony jak klient |
| Q10 | A | Chery iCAR: label filtra `iCAR 03`, `iCAR V27` (slug `/samochody/chery/icar-03/`) |
| Q11 | C | LEVC L380: marka `Geely`, title `Geely LEVC Yizhen L380` |
| Q12 | A | Qiyuan A07/Q07: marka=`Nevo` (osobna), title=`Qiyuan Nevo A07`/`Qiyuan Nevo Q07` |
| Q13 | A | VW Lavida: serie=`Jetta`, title=`Volkswagen Lavida Jetta` jak klient |
| Q14 | A | WEY slug dwucyfrowy: `07`, `03`, `05` (poprawić `7` na `07`) |
| Q15a | A | Li Auto i6 #57 (9 listingów) → scalić z #188; popraw title #188 z `Li i6` na `Li Auto i6` |
| Q15b | A | MINI #190, #191 (4 listingi) — SKIP w imporcie (MINI×GWM JV, niechińska) |
| Q16 | A | Nissan #83 (6 listingów) → mapuj do `Nissan N6` #142 |

Plus dopisany przeze mnie wiersz #267: `Exeed Lanyue C-DM` → marka=Exeed, serie=`VX (Lanyue)`, slug `/samochody/exeed/vx/` (zatwierdzone post-hoc).

## Wdrożenie (5 etapów — zrealizowane)

1. ✅ CSV v6.1 (90 zmian, redirect-map old→new slug)
2. ✅ Backup DB (`mysqldump wp7j_terms wp7j_term_taxonomy wp7j_termmeta wp7j_term_relationships`)
3. ✅ Rebuild taksonomii (WP-CLI: rename termów, dodanie nowych, usunięcie MINI, scalanie duplikatów Li Auto i6 #57+#188 i Nissan #83→#142, zapis `_serie_full_title` / `_serie_label_cn` w `wp7j_termmeta`)
4. ✅ Kod importera + renderer (`class-asiaauto-importer.php` mapuje wg v6.1, single/hub/karta używają `_serie_full_title`)
5. ✅ Redirecty 301 + sitemap

## Artefakty robocze (USUNIĘTE z tmp/ po wdrożeniu)

- `v6-decyzje.md`, `v6.1-diff.md`, `v6.1-taxonomy-plan.md`, `v6.1-taxonomy-plan.json`, `v6.1-apply.sql`, `v6.1-apply.log`, `v6.1-title-diff.log`
- `analyze-taxonomy.php`, `apply-taxonomy.php`, `generate-v61.php`, `migrate-make-per-serie.php`, `migrate-make-relationships.php`, `update-listing-titles.php`
- `mapowanie-marek-modeli.csv` (v6, źródło), `mapowanie-marek-modeli.xlsx` (Excel klienta)

Pełny diff dostępny w `git show f423728` (commit z wdrożeniem). Aktualne mapowanie zawsze w `docs/architektura/mapowanie-marek-modeli-v6.1.csv`.
