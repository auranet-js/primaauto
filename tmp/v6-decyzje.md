# Mapowanie marek/modeli v6 — decyzje z quizu (2026-04-23)

> Plik roboczy. Stan po quizie z userem 2026-04-23.
> Źródło: `tmp/mapowanie-marek-modeli.xlsx` (klient) + `tmp/mapowanie-marek-modeli.csv` (rozpakowane).
> Kolejny krok: aplikacja decyzji → `tmp/mapowanie-marek-modeli-v6.1.csv` + rebuild taksonomii.

## 16 decyzji

| Q | Decyzja | Efekt w CSV/DB |
|---|---|---|
| 1 | A | BYD Leopard 3/5/7/8 — title z prefiksem `BYD` |
| 2 | A | `BYD Leopard 7 PHEV` — PHEV zostaje w title |
| 3 | B | `BYD Leopard 5 (Denza B5)`, `BYD Leopard 8 (Denza B8)` — nawiasy dual-name |
| 4 | A | `Sealion` (jednym wyrazem) — ujednolicić wszędzie (było: `Sea Lion`, `SeaLion`, `Sealion`) |
| 5 | B | `Exeed Exlantix ET` w title (z „Exeed") — marka EU pozostaje Exlantix |
| 6 | C | Hyptec HT: marka=`GAC`, title=`GAC Hyptec HT`, slug `/samochody/gac/hyptec-ht/` (poprawka literówki `GAC Aion Hypec`) |
| 7 | C | `GWM Cannon Great Wall Pao` — cztery człony jak klient |
| 8 | C | `Haval Big Dog Dargo` — trzy człony jak klient |
| 9 | C | `Haval Xiaolong MAX H6` — trzy człony jak klient |
| 10 | A | Chery iCAR: label filtra `iCAR 03`, `iCAR V27` (slug `/samochody/chery/icar-03/`, `/icar-v27/`) |
| 11 | C | LEVC L380: marka `Geely`, title `Geely LEVC Yizhen L380` |
| 12 | A | Qiyuan A07/Q07: marka=`Nevo` (osobna), title=`Qiyuan Nevo A07`/`Qiyuan Nevo Q07` |
| 13 | A | VW Lavida: serie=`Jetta`, title=`Volkswagen Lavida Jetta` jak klient |
| 14 | A | WEY slug dwucyfrowy: `07`, `03`, `05` (poprawić `7` na `07`) |
| 15a | A | Li Auto i6 #57 (9 listingów) → scalić z #188. Popraw title #188 z obecnego `Li i6` na pełne `Li Auto i6` |
| 15b | A | MINI #190, #191 (4 listingi) — skip w imporcie |
| 16 | A | Nissan #83 (6 listingów) → mapuj do `Nissan N6` #142 |

## Zasady filtrowe (ustalone wcześniej, przed quizem)

- **Slug URL**: czysty EU-name (bez nawiasów, `sanitize_title` zjada).
- **Label filtra (sidebar)**: kolumna F raw (z nawiasami, dual-name gdzie istnieje — pattern `ATTO 3 (Yuan PLUS)`).
- **Title / H1 / `<title>`**: kolumna G — finalna decyzja per wiersz z quizu.
- **DM-i / EV w serie**: zostaje gdy istnieją warianty napędu tego samego modelu. Zdejmowane gdy tylko jedna wersja.

## Nowy wiersz #264 (dopisany przeze mnie, nie w xlsx klienta)

- Exeed Lanyue C-DM → marka=Exeed, serie=`VX (Lanyue)`, title=`Exeed VX`, slug `/samochody/exeed/vx/`. Do akceptacji klienta przy najbliższej okazji.

## Plan wdrożenia (5 etapów, odłożony)

1. **CSV v6.1** — apliku decyzje do `tmp/mapowanie-marek-modeli-v6.1.csv`; diff v6→v6.1; redirect-map (slug old→new).
2. **Backup DB** — `mysqldump wp7j_terms wp7j_term_taxonomy wp7j_termmeta wp7j_term_relationships`.
3. **Rebuild taksonomii** — WP-CLI skrypt: rename termów `make`+`serie`, dodaj nowe termy (GAC→Hyptec HT, Exeed VX), usuń MINI, scal duplikaty (Li Auto i6 #57+#188, Nissan #83→#142), zapisz `_serie_full_title` / `_serie_label_cn` w `wp7j_termmeta`.
4. **Kod importera + renderer** — `class-asiaauto-importer.php` mapuje wg v6.1; `class-asiaauto-single.php` / hub / karta renderują `_serie_full_title` zamiast `term.name`. Bump `ASIAAUTO_VERSION`.
5. **Redirecty 301 + sitemap** — stare URL-e → nowe; rebuild sitemapy.

## Wznowienie

Wejdź w `/home/host476470/projekty/primaauto`, przeczytaj ten plik i powiedz „startuj etap 1".
