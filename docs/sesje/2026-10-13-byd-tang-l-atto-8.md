# BYD Tang L → „Tang L (Atto 8)" — plan, czeka na decyzje

**Źródło:** Ruslan, WhatsApp 13.10.2026 13:35: „BYD Tang L (Atto 8) — tak napisz, bo wchodzi do EU pod tą nazwą".
**Status:** nic nie zmienione na produkcji. Czeka na decyzje Janka (niżej).

**Sprzeczność z wcześniejszą decyzją:** 14.05.2026 Ruslan potwierdził „Tang L = Sealion 8" (memory
`reference_byd_tang_aliases.md`). Nowsza decyzja wygrywa: Tang L = Atto 8, „Sealion 8" znika z nazw.

## Stan w bazie (13.10)

| term_id | nazwa | slug | ofert | `_serie_full_title` |
|---|---|---|---|---|
| 3746 | Sealion 8 DM-I (Tang L) | `sealion-8-dm-i` | 14 | BYD Sealion 8 |
| 3703 | Sealion 8 (Tang L) EV | `sealion-8-ev` | 6 | BYD Sealion 8 EV |
| 3700 | Tang DM-i | `tang-dm-i` | 31 | BYD Tang — **nie ruszamy** (stary Tang, nie Tang L) |

Część ofert che168 ma już tytuł „BYD Tang L 2025 …" (niespójność). Mapowanie: `data/brand-mapping-v6.1.php`,
`data/che168-model-map.php`, `includes/class-asiaauto-redirects.php`.

## Docelowo (wzorzec „Tiggo 9 (Jaecoo 8)", 14.09)

| Miejsce | Teraz | Po zmianie |
|---|---|---|
| Tytuł oferty | BYD Sealion 8 DM-I (Tang L) 2025 DM-p 175KM 4WD Edition | BYD Tang L DM-i (Atto 8) 2025 DM-p 175KM 4WD Edition |
| Tytuł oferty EV | BYD Sealion 8 (Tang L) EV 2025 600KM 4WD … | BYD Tang L EV (Atto 8) 2025 600KM 4WD … |
| H1 huba (`_serie_full_title`) | BYD Sealion 8 / BYD Sealion 8 EV | BYD Tang L (Atto 8) / BYD Tang L EV (Atto 8) |
| Nazwa termu = etykieta filtra | Sealion 8 DM-I (Tang L) | Tang L DM-i / Tang L EV (bez nawiasu) |

## Kroki

1. Backup: mysqldump termów + tytułów ofert obu serii, `.bak` 3 plików mapowania.
2. Rename termów 3746 / 3703.
3. `_serie_full_title` obu termów.
4. `serie_eu` w obu mapowaniach (nowe importy dongchedi + che168).
5. Tytuły 20 istniejących ofert skryptem `$wpdb` (dry-run → lista do wglądu). NIE `odswiez-tytuly-ofert.php` — zdejmuje alias.
6. Treść obu hubów: Sealion 8 → Tang L (Atto 8); „Sealion 8" ewentualnie jako wzmianka w treści (frazy wyszukiwania).
7. Memory: poprawka `reference_byd_tang_aliases.md`.

## Decyzje do podjęcia

- **Slugi URL** — rekomendacja: zostają (`sealion-8-dm-i`, `sealion-8-ev`); alternatywa `tang-l-*` + 301.
- **Dwie serie (DM-i / EV) czy jedna** — rekomendacja: zostają dwie.
- **„Sealion 8" w treści huba** — rekomendacja: tak, po sprawdzeniu w GSC, czy fraza zbiera ruch.
- **Reklamy** — sprawdzić, czy Ads/Meta celują w „Sealion 8" (tylko odczyt, zmiany osobno).
