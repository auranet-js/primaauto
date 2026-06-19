# T-019 — taksonomia destrukcyjna: 7 merge'ów serie (2026-06-19)

> Poprzedzone pomiarem T-017 (rework hubów) — odgatowało decyzję. Plan źródłowy: `docs/seo/taksonomia-destrukcyjna-plan-2026-06-06.md`.

## Kontekst
Plan z 06-06 wskazał 8 par duplikatów/split w taksonomii `serie`. Pułapka: panel `duplicate-serie-terms` „Napraw" wybiera canonical wg `post_count+parent>0?1000` i kasuje term bez backupu termmeta → dla części par ZŁY canonical (skasowałby term z dobrym `_serie_full_title` / zreworkowany). Dlatego merge ręczny.

## Wykonane (APPLY 2026-06-19)
Backup: `~/backups/primaauto/2026-06-19/taxonomy-pre-T019.sql` (terms+term_taxonomy+term_relationships+termmeta) + `htaccess.bak-T019`.
Skrypt: `tmp/T019-merge-dryrun.php` (dry-run+apply, 0 kolizji we wszystkich parach).

| # | Merge (dup → canonical) | Repoint | Canonical count po |
|---|---|---|---|
| 1 | galaxy-e5 6586 → e5 3397 | 17 | e5 = 25 |
| 2 | galaxy-starship-8-phev 6582 → starship-8-phev 3406 | 24 | = 38 |
| 4 | zeekr-9x 6584 → 9x 4824 | 11 | 9x = 14 |
| 5 | li-i6 6513 + i6-2 6493 → i6 5740 | 19 | i6 = 17 (+ft „Li Auto i6") |
| 6 | voyah-taishan 6591 → taishan 6494 | 7 | = 10 |
| 7 | v23 5517 + icar-super-v23 7001 → super-v23 6588 | 4 | = 27 |
| 8 | galaxy-m9 6593 → m9 6550 | 9 | m9 = 9 (+ft „Geely Galaxy M9") |

9 termów-duplikatów skasowanych. Tytuły regen przez `AsiaAuto_HubTitleGenerator::regenerateForTerm` (4824 zeekr-9x ma `skip flag` — tuning CTR zachowany).

### Redirecty 301
- galaxy-*, zeekr-9x, voyah-taishan: łapie istniejące V62/`redirectHubMakePrefix` (auto).
- Dodane do `V62_SERIE_REDIRECTS` w `class-asiaauto-redirects.php` (backup `.bak-2026-06-19-T019`): `li-auto`(li-i6,i6-2→i6), `icar`(v23,icar-super-v23→super-v23).
- `.htaccess` linia 113 (soft-404 T-161) poprawiona: `icar/icar-super-v23` → `icar/super-v23` (było korzeń marki).

Smoke: 9 dup-slugów → 301 na właściwy canonical; 7 hubów = 200.

## Kontaminacja M8/N7/M6 — naprawiona (2026-06-19, po zgłoszeniach Janka)
Dwie warstwy, oddzielne od merge'ów serie:

**Warstwa 1 — meta `_asiaauto_primary_make_slug` wskazywała obcą markę** (filtr `filterSerieTermLink` buduje URL huba z tej mety → breadcrumb na single prowadził do złego huba mimo poprawnej etykiety). Skan: `serie` z `parent-make-slug ≠ primary_make_slug` (count>0). Naprawione 3 czyste:
- 3381 `m8` „Trumpchi M8": aito → **gac**
- 3949 `n7` „N7": denza → **nissan**
- 3377 `m6` „Trumpchi M6": aito → **gac**

**Warstwa 2 — pojedyncze oferty na złym termie serie** (przeniesione relacje term_relationships + recount):
- 3× Denza N7 (359872/360175/360487) z serie 3949 (Nissan N7) → 4659 (Denza N7). Nissan N7=20, Denza N7=6.
- 1× AITO M8 (361236) z 3381 (GAC M8) → 5302 (AITO M8). GAC M8=28, AITO M8=54.
- 2× AITO M6 (352300/353641) z 3377 (GAC M6) → 6557 (AITO M6, był pusty → 2; ustawione primary_make_slug=aito).

Tytuły dotkniętych hubów regen. Slug-kolizja `m8`/`n7`/`m6` (dwa termy ten sam slug, różny parent) jest OK — dwupoziomowy URL `/samochody/<make>/<slug>/` disambiguuje, O ILE `primary_make_slug` zgodny z parentem.

**Otwarte (sub-brand, NIE tknięte — meta=marka-matka, parent=sub-brand):** 6539 a7-em (geely/galaxy → patrz niżej), 3373 s7 (gac/luxeed → #3 split), 6259 e007 + 5343 nami-06 (dongfeng-yipai/dongfeng), 4778 a06-classic (changan-qiyuan/nevo). Wymagają tej samej decyzji co Galaxy.

## NIEzrobione / otwarte
- **#3 GAC S7 / Luxeed split** — `s7` 3373 (18, parent GAC 6525) skażony Luxeed/GAC; wymaga klasyfikacji per-oferta + termu Luxeed S7 (jest tylko `luxeed/r7` 5675). Osobny krok.
- **Galaxy jako osobna marka/sub-brand (od 2025) — DECYZJA Janka 2026-06-19.** NIE mergować make `galaxy` (6579, 17 ofert) do `geely`. Dziś niespójność: `/samochody/galaxy/` 301→geely (V61), e5/starship pod make `geely`, m9/a7-em pod make `galaxy`. „1 sztuka" na M9 = symptom. Otwarta decyzja sub-brandowa: czy odtworzyć żywy hub `/samochody/galaxy/`, przenieść modele Galaxy pod `galaxy`, zdjąć V61 galaxy→geely. Duża, osobny task.
