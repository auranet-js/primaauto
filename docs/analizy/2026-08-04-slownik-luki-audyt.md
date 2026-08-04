# Audyt pokrycia słownika `/wiki/` względem danych (2026-08-04)

> Powód: od czasu wdrożenia słownika (74 hasła, 21.07) doszła ścieżka katalogu Autohome — **292 parametry na model zamiast ~100 z auto-api**. Pokrycie nigdy nie było przeliczone na nowych danych.
> Metoda: przelot po **2090 ofertach** z `_asiaauto_extra_prep` (364 klucze), rozkład wartości dla pól słownikowych, zestawienie z listą opublikowanych haseł.

## Wniosek nadrzędny — linkowania nie trzeba budować, trzeba dopisać hasła

`AsiaAuto_Autolink` **działa od 22.07** i jest wpięty m.in. w `tech_specs` oraz `equipment`. Na stronie oferty daje dziś **6 linków do `/wiki/`** — dokładnie tyle, ile wartości ze specyfikacji ma swoje hasło.

Czyli: **każde nowe hasło zamienia się automatycznie w link w setkach lub tysiącach ofert**, bez dotykania kodu renderującego. Rozbudowa słownika jest tańszą i szerzej działającą dźwignią niż budowanie osobnego mechanizmu linkowania parametrów.

Osobnym zadaniem zostaje linkowanie **do hubów i katalogu** (marka → hub marki, model → hub modelu, nadwozie/kolor/paliwo → katalog z filtrem) — tego autolinker nie robi.

## Luki wg liczby aut, których dotyczą

| Obszar | Aut w bazie | Hasła dziś | Czego brakuje (liczność) |
|---|---:|---:|---|
| **Zawieszenie** | 1 985 (95%) | 2 (pneumatyczne, CDC) | wielowahaczowe **1 449**, McPherson **958**, dwuwahaczowe **886**, pięciowahaczowe 345, regulacja twardości/wysokości 579, belka skrętna 68, most sztywny 30 |
| **Hamulce** | 2 033 (97%) | 0 | tarcze wentylowane **2 001**, EPB **2 029**, ceramiczne 18, karbonowo-ceramiczne 7 |
| **Struktura nadwozia** | 2 036 (97%) | 0 | samonośna **1 933**, **ramowa 103** (to są dokładnie terenówki — Tank, Leopard 5/8, BJ40, G700) |
| **Nawigacja / mapy** | 1 083 | 0 | Amap/Gaode **857**, Petal Maps 146, Baidu 80 |
| **Radar mmWave** | 1 061 (51%) | 0 | jedno hasło + „ile radarów znaczy co" (1–9 sztuk) |
| **Skrzynia stałoprzełożeniowa (EV)** | 1 079 | 0 | „dlaczego elektryk nie ma biegów" |
| **Silnik spalinowy** | 939–1 343 | 0 | wtrysk bezpośredni **1 101**, turbo **806**, wolnossący 127, cykl Millera 90, VGT 40, VVT 89 |
| **Producenci baterii** | 1 587 (76%, 72 wartości) | 2 (CATL, FinDreams) | **CALB 125**, SVOLT 49, EVE 26, Sunwoda 24, Gotion 22, + JV typu CATL-Geely 53 |
| **Systemy kokpitu** | ~600 | 2 (HarmonyOS, HiCar) | DiLink **221**, Leapmotor OS 77, Flyme Auto 70, Galaxy OS 56, Lion 51 |
| **Chipy kokpitu** | 532 | 0 | Snapdragon **8295 (287)**, **8155 (245)** |
| **Systemy ADAS** | ~500 | 2 (Huawei ADS, NOA) | DiPilot **190**, Leapmotor Pilot 77, NOP 63, Li AD Max 56 |
| **Technologie baterii** | 550 (26%) | 5 | Huawei Juwhale **40**, Shield/Aegis (Geely) 29, Xiaoyao/Freevoy (CATL) 16, Amber (GAC) 9 |
| **Audio** | 507 (24%) | 5 | Sony **69**, BOSE 33, Infinity 29, Yamaha 10, Pioneer 8, KEF 3, Alpine 2 |
| **Chipy ADAS** | ~350 | 0 | NVIDIA Orin X **146**, Orin N 59, + co znaczy TOPS (84–700) |
| **Układ silników mieszany** | 345 | 2 osobno | asynchroniczny przód + PMSM tył — czemu tak się łączy |
| **Ogniwa mieszane LFP+NMC** | 81 | 0 | dwa typy ogniw w jednym aucie |
| **Materiał foteli** | — | 1 | Alcantara, tkanina, skóra+ekoskóra 56 |
| **Mild hybrid 48V** | 9 | 0 | niski priorytet |

**Razem: ~45–55 nowych haseł, z czego 8 dotyczy ponad 1000 aut każde.**

## Co jest pokryte dobrze (bez zmian)

Typ ogniw (LFP 943 / NMC 553 / LMFP 3) · typ silnika elektrycznego (PMSM 1214, asynchroniczny) · rodzaj paliwa (BEV 704 / PHEV 530 / EREV 393) · skrzynie hybrydowe (E-CVT 282, DCT 246, DHT 200) · Blade 223, Kirin 60, Golden Brick 10, Shenxing · LiDAR 561 · pompa ciepła 409 · V2L · 800V · CLTC vs WLTP · poziomy autonomii.

## Uwagi techniczne do generatora haseł

- **Wartości są w chińskim** i mają warianty zapisu tego samego pojęcia — np. zawieszenie: `双叉臂式独立悬架` (682) i `双叉臂式独立悬挂` (204) to jedno i to samo, różnica w ostatnim znaku. Mapowanie musi normalizować warianty, inaczej hasło złapie połowę aut.
- **Ślady zepsutego unicode** nadal występują pojedynczo (`u78f7u9178...`) — 1–2 rekordy na pole, do pominięcia przy zliczaniu.
- **Klucze dynamiczne** (`car_intelligent_chip_高通骁龙8295`, `driving_assist_chip_NVIDIA DRIVE Orin X*2`) niosą wartość w nazwie klucza, nie w wartości — generator musi je czytać inaczej niż zwykłe pola.
- `battery_brand` ma **72 różne wartości** przy 12 realnych producentach — reszta to spółki JV i zakłady (`宁德时代(江苏时代)`, `江苏时代`, `时代吉利`). Hasło o CATL powinno wchłonąć warianty, a nie mnożyć hasła.
