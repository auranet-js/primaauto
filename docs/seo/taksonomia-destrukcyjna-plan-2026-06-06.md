# Plan taksonomii destrukcyjnej — duplikaty/split hubów (2026-06-06)

> **STATUS: PLAN. NIE WYKONANE.** Wymaga wyraźnej zgody Janka per pozycja (decyzja 2026-06-06: „nic teraz — tylko zgłoś plan").
> Wszystkie pozycje mają tę samą pułapkę: panel `duplicate-serie-terms` „Napraw" wybiera canonical wg liczby ofert (`post_count + parent>0?1000`) i **kasuje term bez backupu termmeta** — dla części par wybrałby ZŁY canonical (skasowałby term z dobrym `_serie_full_title` albo już zreworkowany). **NIE klikać panelu — merge/split ręczny.**
> Wzorzec skryptu: `tmp/contam-split-batch.php`, `tmp/porzadki-exec.php` (DRY+APPLY). Backup ZAWSZE przed: `mysqldump ... wp7j_term_relationships` + `wp7j_termmeta` do `~/backups/primaauto/<data>/`.

## Wspólny wzorzec błędu: prefiks marki w slugu → kolizja po stripie
Redirect `redirectHubMakePrefix` strzela prefiks nazwy marki ze slugu. Termy `galaxy-*` (make znormalizowany geely) lądują po stripie na bare-slug pod `/geely/<model>/` i kolidują z osobnym (często pustym) termem o tym slugu. Dotyczy: galaxy-e5, galaxy-m9 (potwierdzone tej sesji). To samo mechanicznie co `9x`/`taishan` (slug bez prefiksu = canonical).

## Pary (canonical = TEN co zostaje)

| # | Duplikat (skasować) | Canonical (zostaje) | Akcja | Wynik |
|---|---|---|---|---|
| 1 | `galaxy-e5` 6586 (16, ft NULL) | `e5` 3397 (8, ft „Geely Galaxy E5", geely) | merge 16→3397; del 6586; 301 galaxy-e5→e5 | Geely Galaxy E5 = 24 |
| 2 | `galaxy-starship-8-phev` 6582 (20, ft NULL) | `starship-8-phev` 3406 (14, ft „Geely Galaxy Starship 8 PHEV") | merge 20→3406; del 6582; 301 | Starship 8 PHEV = 34 |
| 3 | — (split, nie merge) | `s7` 3373 (16) skażony **Luxeed 12 / GAC 2** | split 12 Luxeed S7 → term Luxeed S7 (sprawdzić/utworzyć; jest 5675 luxeed/r7 ale S7 osobny); zostaw 2 GAC Trumpchi S7 w 3373; fix titles+rank_math | 3373 GAC S7=2, Luxeed S7=12 |
| 4 | `zeekr-9x` 6584 (10, ft NULL) | `9x` 4824 (zreworkowany Tier B, ma content) | merge 10→4824; del 6584; 301 zeekr-9x→9x | Zeekr 9X (treść Tier B zachowana) |
| 5 | `li-i6` 6513 (11) + `i6-2` 6493 (5) | `i6` 5740 (0, slug czysty, geely?→li-auto) | merge 11+5→5740; set ft „Li Auto i6"; del 6513+6493; 301 obu→i6 | Li Auto i6 = 16 → potem content |
| 6 | `voyah-taishan` 6591 (7, ft NULL) | `taishan` 6494 (3, ft „Voyah Taishan", parent 5073) | merge 7→6494; del 6591; 301 | Voyah Taishan = 10 → potem content |
| 7 | `v23` 5517 (4, ft „iCAR Super V23") + `icar-super-v23` 7001 (0) | `super-v23` 6588 (21, zreworkowany) | merge 4→6588; del 5517+7001; 301 v23→super-v23 | iCAR Super V23 (treść zachowana, +4) |
| 8 | `galaxy-m9` 6593 (9, ft NULL) | `m9` 6550 (0, slug m9, geely) | merge 9→6550; set ft „Geely Galaxy M9"; del 6593; 301 galaxy-m9→m9 | Geely Galaxy M9 = 9 → potem content |

**Uwaga #5/#8:** canonical to obecnie PUSTE termy (5740, 6550) z czystym slugiem — wybrane bo slug bez prefiksu = URL docelowy po stripie. Alternatywa: zostawić term z ofertami i naprawić redirect/slug — ale to grzebanie w warstwie redirectów, ryzykowniejsze. Rekomendacja: merge do czystego slugu.

## Procedura per para (po zgodzie)
1. `mysqldump` term_relationships (WHERE object_id w listingach obu termów) + termmeta obu termów → backup.
2. Skrypt DRY: pokaż które `term_relationships` przepną się (`tt_id` duplikatu → `tt_id` canonical), sprawdź kolizje (oferta już w canonical).
3. APPLY: `UPDATE wp7j_term_relationships SET term_taxonomy_id=<canon_tt> WHERE term_taxonomy_id=<dup_tt>` (z dedup); przelicz `tt.count` obu (`wp term recount` lub ręcznie); `wp term delete <dup_term_id> serie`.
4. Dodaj 301 w warstwie redirectów (V61/V62/V63 map w `class-asiaauto-seo.php` lub mechanizm `_MAKE_SERIE_REDIRECTS`) dup-slug→canon-slug.
5. Ustaw/popraw `_serie_full_title` na canonical (#5,#8) + `AsiaAuto_HubTitleGenerator::regenerateForTerm`.
6. Dopiero potem content-rework canonicalu (metoda v1).

## Po zgodzie — kolejność rekomendowana
Najpierw bezpieczne same-brand/same-model merge (#1,2,4,5,6,7,8) → potem split #3 (GAC S7/Luxeed, najbardziej złożony, wymaga utworzenia/znalezienia termu Luxeed S7).
