# Sesja 2026-07-22 — Che168: sync wpięty w automat, faza szkiców (v0.34.2)

> Element T-186 (`docs/roadmapa/T-186-che168-automat.md`). Plan i pomiary wejściowe:
> `docs/superpowers/plans/2026-07-22-che168-sync-wpiecie.md`.
> **Stan końcowy: oba źródła WYŁĄCZONE, che168 na `draft`, nic nie opublikowane.**

## Co zostało zrobione

**Blok A — higiena danych**
- 17 brakujących tłumaczeń miast (15 z 31 miast filtra importu + 2 z danych che168). Backfill: **102 wpisy** `stm_car_location`, zero CJK zostało. Backup: `~/backups/primaauto/2026-07-22/postmeta-location-pre-backfill.sql`.
- Domapowania: `BYD|L EV`→Song L EV, `L DM-i`→Song L DM-i, `海豹06`→Seal 6 DM-i, `海豹06 DM-i旅行版`→Seal 6 DM Wagon, nowy `Leapmotor|零跑B01`→B01.
- **Marki foldowane** — poprawka w `AsiaAuto_Mapping`: indeks `serie_eu` bez marki (`serie_only`) dla przypadków, gdzie marka che168 ≠ `mark_eu` (Yangwang → BYD). Wykluczone nazwy niejednoznaczne (H6 Haval/Hongqi, ET5 Exeed/NIO — 7 z 266). Efekt: `仰望U7` rozróżnia PHEV i EV.
- Świadome skipy zweryfikowane w danych: `Exeed|Zhaofeng` = 星途追风 benzyna 1.5T, `Changan|UNI-K` benzyna 2.0T, `Yangwang U8 L`/`U9` = 1,0–1,5 mln ¥, `HiPhi X` = marka upadła 2024. Zostają orphanami, guard je odrzuca.

**Blok B — kod syncu** (addytywnie; `importListing` i niżej nietknięte)
- `Sync::isEnabledForSource()` / `statusForSource()` — wyłączniki i status importu per źródło (`asiaauto_sync_enabled_{source}`, `asiaauto_sync_status_{source}`); brak wpisu = dziedziczenie po globalnym. Toggle w panelu Status.
- Adapter wpięty w `importWithFullData()` (ścieżki `added` i fallback z `/changes`) oraz w `changed`.
- Guard niezmapowanych + kolejka `asiaauto_che168_unmapped`. **Kolejność ma znaczenie:** filtr konfigu → guard. Odwrotnie kolejka zapełnia się BMW/Mercedesami z całych Chin (złapane w pierwszym biegu, poprawione).
- Filtr wstępny na danych z `/changes` przed `getOffer()` — bez niego każdy bieg spalał ~750 wywołań API na oferty, które i tak odpadają. `Importer::isAllowedByConfig()` private → public (logika bez zmian).
- Cron przechodzi po obu źródłach niezależnie.

**Blok C — pierwsze biegi**
- Kursor `asiaauto_last_change_id_che168` ustawiony świadomie na start doby (nie mielimy 20 tys. zdarzeń wstecz).
- 8 cykli × 100 batchy ≈ 16 tys. zdarzeń → **45 ofert w szkicach**. Wszystkie trafiły w istniejące huby; kolejka domapowań: 4 pozycje (`Zeekr|MIX`, `Galaxy|银河A7 EM`, `Galaxy|银河M9`, 1 pusty rekord).
- Zdjęcia 15/15, opisy tłumaczone, ceny policzone (Denza N8L: 253 800 ¥ → 261 000 PLN), **VIN obecny** — czego dongchedi nie dostarcza.
- Naprawione w locie: pełnoszerokościowa interpunkcja z `param_93` („… LiDAR ）" w tytułach AITO) — fix w adapterze + poprawka 2 zaimportowanych ofert.

**Domapowanie parametrów** (v0.34.2, commit `d7320bb`)
- 45 różnych nieznanych parametrów / 795 wystąpień → zmapowane 29 id: **16 na istniejące klucze dongchedi**, 13 nowych z etykietami PL, kategoriami i jednostkami.
- Dwa błędy semantyczne złapane przy weryfikacji: `id 88` (最大功率 z grupy 基本参数) to moc **całego układu**, nie silnika — nadpisywał `engine_max_power` (AITO M9: 118 → 365 kW); `id 84/105` to ładowanie **szybkie**, a `battery_charge_time` było zajęte przez wolne (`id 111`). Po poprawkach: zero kolizji, zero znikających kluczy na 15 ofertach.
- Bez mapowania świadomie: `id 92` (长*宽*高 — duplikuje `length/width/height`), `id 93` (车型名称 — źródło tytułu i wersji).
- Efekt: nieznane **795 → 188** (z czego 90 to celowe 92/93), średnio **60,9 wiersza** specyfikacji na ofertę.

## Pomiary (wszystkie read-only, na produkcji)

| Co | Wynik |
|---|---|
| Kanał `/changes` che168 | 20–31 tys. zdarzeń/dobę (17–22.07) |
| Z tego `added` | 37,5% |
| Przechodzi filtr Prima Auto | 0,9% `added` → **60–100 ofert/dobę** |
| Dongchedi w tym samym pomiarze | 8 981 zdarzeń/dobę, `added` ≈ 2 |
| Filtr przed wpięciem adaptera | **0 / 730** (surowy rekord nie ma `city`) |
| Filtr po wpięciu | **89 / 730 (12%)** |
| Trafialność w huby | 70% próbki |
| Filtr miast | ucina **81%** dobrych ofert (388 z 477) |

## Ustalenie, które zmienia założenia projektu

**Auto-api nie przekazuje wyposażenia dla che168.** `/offer` zwraca `extra.configuration.paramtypeitems` z 6–7 grupami technicznymi; pola `extra_prep` i `equipment` (które dongchedi ma na najwyższym poziomie, 340–370 atrybutów) **nie istnieją**. Odpowiedź niesie `extra.configuration.specid`, a publiczny katalog Autohome ma dla tego samego id 11–12 grup — z fotelami, bezpieczeństwem czynnym i biernym, klimatyzacją i pakietami opcji.

Zweryfikowane w Chrome na trzech ofertach: `73545` 6→11, `66867` 7→12, `66792` 6→11 grup.

To **nie jest problem mapowania**: `che168-option-map.php` trafia w 100% (26 pozycji, zero nieznanych), a param-map po dzisiejszym rozszerzeniu zbił nieznane do 188. Luka to brak danych w odpowiedzi API.

Pomiar luki na 45 draftach (kategorie z zerem u che168): Fotele 0/820, Zdalne sterowanie 0/303, Tryby jazdy 0/202, Lusterka 0/191. Szczątkowo: Komfort 148/989, Multimedia 138/988, ADAS 98/813, Bezpieczeństwo 56/602.

**Wysłane zapytanie do auto-api** (22.07 21:22, w wątku „API inquiry Dongchedi + Che168", UDW na `claude@auratest.pl`, mail [147]): czy pełna konfiguracja jest dostępna — innym endpointem, parametrem, w wyższym planie, czy za dopłatą. Plus dopisek o regresji `extra_prep` dongchedi (363/369/349 pól w starych ofertach vs **43** w `24415754` i `24341400`, próg ~24,34 mln).

## Decyzja odłożona do odpowiedzi dostawcy

Dziedziczenie specyfikacji z bliźniaczych ofert (`scripts/merge-spec-from-twin.php`) zmierzone w trzech wariantach na 45 draftach:

| Wariant | Pola | Wyposażenie / oferta |
|---|---|---|
| exact (marka+seria+**wersja**+rocznik) — obecny | 1374 → 1923 | trafia w 2 z 14 |
| luźne (bez wersji) | 1374 → 4224 | ryzyko cudzych wartości |
| **luźne + konsensus** | 1374 → 3472 | **14,5 → 67,7 pozycji** |

Konsensus dolewa wyłącznie pola, w których wszystkie sztuki modelu podają tę samą wartość; rozbieżne (liczba głośników, materiał foteli, typ szyberdachu) zostawia puste. **Nie wdrożone** — jeśli auto-api odda dane, dziedziczenie jest zbędne.

## Artefakty

- Karta oferty w trzech wariantach: `https://auratest.pl/fe4f58fec53ctmp/primaauto-che168-karta-oferty-2026-07-22.html`
- Drafty z biegu: `https://auratest.pl/fe4f58fec53ctmp/primaauto-che168-drafty-2026-07-22.html`
- Treść zapytania do auto-api: `https://auratest.pl/fe4f58fec53ctmp/primaauto-autoapi-che168-zapytanie-2026-07-22.md`
- Skrypty: `scripts/che168-dryrun-filtr.php`, `che168-pomiar-strumienia.php`, `che168-symulacja-karty-oferty.php`, `che168-przelicz-extra-prep.php`
- Backupy serwerowe: `*.bak-2026-07-22-che168` / `-sync`

## Następny ruch

1. **Czekamy na odpowiedź auto-api** — decyduje o wariancie specyfikacji.
2. Przegląd 45 draftów przez Janka → publikacja albo poprawki.
3. Po decyzji: włączenie `asiaauto_sync_enabled_che168`, zmiana statusu na `publish`, obserwacja dobowa.
4. Dedup po VIN (che168 ma VIN w 100%) — jeszcze niezrobiony, zaplanowany w planie sesji.
5. Otwarte pytanie do Ruslana: filtr 31 miast kosztuje 81% podaży che168; największe wolumeny poza listą to Szanghaj, Wuhan, Chongqing, Chengdu.
