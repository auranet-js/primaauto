# T-116 po odbiorze — cztery ruchy (log crona, moc układu, linkowanie, drugi rzut pól)

> **NIEAKTUALNY od 2026-09-03.** Zastąpiony przez `T-116-domkniecie-wyszukiwarki.md`:
> ruch 0 (log crona) wykonany, wyszukiwarka doszła do v0.36.1 z układem poziomym i dwoma
> nowymi filtrami, liczby w tym pliku są sprzed tych zmian. Zostawiony dla historii.

> Prompt wykonawczy do odpalenia w nowym wątku w `~/projekty/primaauto`.
> Powstał 2026-09-02 po wdrożeniu etapu 3 (v0.35.0). **Startujesz dopiero po tym, jak Janek
> obejrzy `/wyszukiwarka/` w przeglądarce i potwierdzi, że działa.** Jeśli zgłosi usterki
> z tego przeglądu — one mają pierwszeństwo przed wszystkim poniżej.
>
> Liczby zmierzone 02.09 na produkcji, **zweryfikuj kluczowe na starcie** (rotacja i nocne crony ruszają stan).
>
> Przeczytaj przed startem:
> 1. `docs/decyzje/2026-09-02-wyszukiwarka-zaawansowana-tabela-specs.md` — co i dlaczego stoi.
> 2. `docs/roadmapa/T-116-etap2-pola-i-wartosci-wyszukiwarki.md` — spec danych, sekcja 3 to wsad do ruchu C.
> 3. `docs/VERSIONS.md` wpis 0.35.0 — bramki i trzy usterki złapane w przeglądarce.

---

## 0. Stan wejściowy (02.09, po wdrożeniu 0.35.0)

- `/wyszukiwarka/` (post 459262) — publiczna, w indeksie, **nigdzie nie podlinkowana**.
- `wp7j_asiaauto_specs` — 3 396 wierszy (2 969 publish), 41 kolumn, 1,7 MB.
- Trasy `asiaauto/v1/search` i `/search-counts`; `/samochody/` nietknięte.
- Nocna sekwencja: bliźniak 04:35 → bank 04:45 → katalog Autohome 04:55 → **specs 05:05**.

**Czego NIE ruszasz w żadnym z tych ruchów:** `class-asiaauto-inventory.php` i
`asiaauto-inventory.js` (mtime musi zostać sprzed 02.09), logiki importera, nazw klas/CPT/meta/ról,
zapisu `_asiaauto_extra_prep` (zawsze przez `wp_slash`, ale ten wątek nie powinien go w ogóle zapisywać
poza ruchem A, który dotyka wyłącznie `_asiaauto_horse_power`).

---

## RUCH 0 — log crona 05:05 (5 minut, zrób jako pierwsze)

Jedyna bramka etapu 3, której nie dało się domknąć tego samego dnia (DoD 5).

```bash
tail -40 ~/.claude/zbuduj-specs.log
cd ~/domains/primaauto.com.pl/public_html && wp db query \
  "SELECT COUNT(*) n, MAX(updated_at) ostatnia FROM wp7j_asiaauto_specs;"
```

**Zdane, gdy:** log ma wpis z 05:05 z niezerową liczbą przerobionych ofert, a `MAX(updated_at)`
jest z tej nocy. Jeśli log pusty — sprawdź `crontab -l | grep zbuduj-specs`, uprawnienia
`/usr/local/bin/php` i czy skrypt nie wywalił się na `wp-load.php`.

**Ile ofert to norma:** pierwszy bieg po backfillu katalogu weźmie dużo (02.09 ręcznie: 2 379),
potem powinno spaść do kilkudziesięciu — tyle, ile nocne ogniwa realnie ostemplowały.
Utrzymujące się tysiące = `since=48h` łapie za szeroko, sprawdź `idsToRebuild()`.

---

## RUCH A — moc układu zamiast mocy silnika (rekomendowany pierwszy właściwy ruch)

### Problem

`_asiaauto_horse_power` ma pokrycie 99,6%, ale **101 ofert PHEV/EREV trzyma tam moc silnika
spalinowego**, nie układu. Z tego **66 bez stempla** `_asiaauto_horse_power_source` — to wartości
sprzed etapu 2, którego korekta świadomie uzupełniała wyłącznie braki.

| oferta | jest | powinno być ok. |
|---|---:|---:|
| AITO M9 2024 EREV Ultra | 152 KM | ~490 |
| Leapmotor D19 2026 EREV 500 | **0 KM** | ~960 |
| Leapmotor C16 2026 EREV 280 | 95 KM | ~215 |
| Denza D9 DM-i 4WD Flagship | 156 KM | ~421 |

Suwak mocy w wyszukiwarce odsiewa te auta przy filtrze „moc od…", mimo że próg spełniają.
Tabela `specs` bierze moc z meta wprost — **poprawka musi iść do meta, nie do normalizatora**.

### Co zrobić

1. Dump `postmeta` dla `_asiaauto_horse_power` + `_asiaauto_horse_power_source` do
   `~/backups/primaauto/<data>/`. Bez tego nie ruszasz.
2. Skrypt `scripts/napraw-moc-ukladu.php` (dry-run domyślnie, `apply`), wzorzec argumentów jak
   w `zbuduj-specs.php`. Dla ofert `fuel IN (phev, erev)` policz moc układu przez
   `AsiaAuto_Spec::km_from_power()` (jest `private` — albo dodaj obok **czystą metodę publiczną**,
   albo przenieś logikę do skryptu; nie przerabiaj istniejącej).
3. **Tylko podnosisz.** Jeśli policzona moc jest niższa albo równa obecnej — zostaw i zalicz do
   raportu jako „bez zmian". Prompt etapu 3 mówił wprost: istniejących wartości nie obniżamy.
4. Stempluj `_asiaauto_horse_power_source` wartością użytego klucza — inaczej za miesiąc
   znowu nie odróżnisz naprawionych od zastanych.
5. Po `apply`: `php scripts/zbuduj-specs.php apply` (pełny bieg, 5 s) — tabela nie przelicza się sama.

### Bramki

- Dry-run pokazuje listę: ID, tytuł, moc przed, moc po, użyty klucz. **Pokaż Jankowi przed `apply`.**
- Po biegu: 0 ofert z mocą 0 KM wśród publish; AITO M9 i Leapmotor D19 mają moc układu.
- `SELECT COUNT(*) FROM wp7j_asiaauto_specs WHERE status='publish' AND power_km < 200`
  na PHEV/EREV spada z 101 do liczby, którą umiesz uzasadnić (są realnie słabe PHEV-y, np. 1.5 TD).
- Huby modeli i karty ofert pokazują nową moc — sprawdź trzy adresy w przeglądarce.

### Pułapki

- **`engine_max_horsepower` to moc SILNIKA**, `system_max_power` / `electric_total_horsepower`
  to układ. Nie mieszaj ich w jednej kolumnie.
- 2 oferty mają stempel `engine_max_horsepower` — tam wybór był świadomy. Zapytaj Janka,
  czy je ruszamy, zamiast zakładać.
- Moc idzie do **tytułu oferty i do treści hubów**. Sprawdź, czy zmiana nie wymusza regeneracji
  tytułów — i pamiętaj o [[reference_skip_title_regen_zamraza_ceny]]: flaga `skip_title_regen`
  potrafi zamrozić to, co miało się odświeżyć.

---

## RUCH B — linkowanie (WYMAGA wyraźnej zgody Janka, osobno na każdy punkt)

Krok 7 promptu etapu 3. **Nie robisz tego z własnej inicjatywy ani „przy okazji".**
Zgoda na jeden punkt nie jest zgodą na pozostałe.

### B1. CTA „Wszystkie auta →" na hasłach `/wiki/`

`class-asiaauto-wiki-cars.php:168` — dziś prowadzi do `/samochody/` bez filtra, celowo
(memory `project_wiki_cars_cta_awaits_search`, decyzja Janka 22.07: podmiana dopiero, gdy
powstanie wyszukiwarka z filtrami po wyposażeniu). **Ten warunek jest już spełniony.**

Pomiar 02.09: hasła mają `_wiki_term_keys` (60 haseł z kluczem). Z tego **16 da się podpiąć dziś**:

```
lidar, v2l, pompa-ciepla, kamera-360, tryb-wartownika, masaz-foteli, ar-hud,
zawieszenie-pneumatyczne, cltc-wltp, materialy-tapicerki, erev, hybryda-plug-in-phev,
samochod-elektryczny-ev, dct-mokra, e-cvt, przekladnia-jednobiegowa-ev
```

Pozostałe **44 czekają na ruch C** — ich klucze (`battery_type`, `battery_brand`, `sound_brand`,
`driver_form`, `automatic_drive_level`, `high_voltage_fast_charging_platform_800`,
`navigation_assisted_driving_1`, `active_dms_fatigue_detection`…) nie mają jeszcze kolumny w `specs`.

**Sensowna kolejność: ruch C przed B1**, żeby nie robić CTA dwa razy. Jeśli Janek chce B1 od razu,
podepnij te 16 i zostaw resztę na `/samochody/` — ale wtedy dwa różne CTA na jednym typie strony
wymagają jego świadomej zgody, bo to niespójność widoczna dla użytkownika.

Deep-link ma postać `/wyszukiwarka/?wyposazenie=lidar` albo `/wyszukiwarka/?paliwo=erev`.
Mapowanie hasło → parametr trzymaj **w jednym miejscu**, nie rozsypane po szablonach.

### B2. Menu i stopka

`themes/primaauto2026/header.php` (menu) i `footer.php`. Uwaga: menu jest krótkie i pełne
— dołożenie pozycji wypycha inne. Zapytaj, **co ma ustąpić**, zamiast dokładać na siłę.

### B3. Podmiana `/samochody/`

Najcięższy ruch i osobna decyzja strategiczna: `/samochody/` to **strona konwersyjna**
(memory `reference_mobile_share_and_offers_are_conversion_pages`) z ruchem organicznym i płatnym,
landing dla kampanii Ads i DSA. Podmiana bez planu przekierowań i sprawdzenia landingów
w kampaniach = utrata ruchu. Jeśli w ogóle, to jako osobny task z własnym promptem.

---

## RUCH C — drugi rzut pól (nowy zakres, wyceń przed startem)

Sekcja 9 promptu etapu 3 wyklucza to z etapu 3. To nowy task, nie kontynuacja.

### Wsad

`docs/roadmapa/T-116-etap2-pola-i-wartosci-wyszukiwarki.md` sekcja 3 — 22 zakresy, 22 enumy,
~70 flag, z czego pierwsza wersja wzięła 8 + 6 + 20. Kandydaci drugiego rzutu, ułożeni według
tego, co realnie odblokowują:

| pole | typ | pokrycie | odblokowuje |
|---|---|---|---|
| `battery_type` (LFP/NMC/LMFP) | E | 80% | 4 hasła wiki |
| `battery_brand` (CATL, FinDreams…) | E | 79,7% | 2 hasła |
| `automatic_drive_level` (L2/L2+) | E | 93% | 1 hasło |
| `driver_form` (układ napędowy, liczba silników) | E | 99,1% | 2 hasła |
| `high_voltage_fast_charging_platform_800` | F | 29% | 1 hasło |
| `sound_brand` | E | 33,4% | 3 hasła |
| `navigation_assisted_driving_1/2` | F | 44 / 57% | 1 hasło |
| `active_dms_fatigue_detection` | F | 74% | 1 hasło |
| `memory_parking` (bez `选配`) | F | 25% | 1 hasło |
| `jb` (klasa pojazdu) | E | 99,1% | filtr segmentu, mocno różnicujący |
| `car_refrigerator`, `electric_back_door`, `hidden_door_handle` | F | 36 / 77 / 55% | filtry „życiowe" |

### Jak to zrobić bez rozwalania tego, co stoi

1. Kolumny dokładasz **`ALTER`-em** (`AsiaAuto_Specs_Table::install()` + `dbDelta`; sprawdź
   `SHOW COLUMNS` po instalacji — 02.09 `dbDelta` dodało kolumny dopiero za drugim wywołaniem).
2. Dla każdego nowego enumu **słownik kanoniczny** i raport nieznanych wartości — bramka
   z etapu 3 brzmiała „0 nieznanych" i ma tak zostać.
3. Po dołożeniu: pełny `zbuduj-specs.php apply` + ponowny bieg `porownaj-search.php`
   (nowe kolumny nie mogą zmienić wyników filtrów podstawowych — musi zostać 0 rozjazdów).
4. UI: nowe grupy w panelu, ale **panel już teraz jest długi** — przy 40+ filtrach rozważ
   sekcję „więcej filtrów" albo wyszukiwarkę po nazwie filtra. To decyzja projektowa, nie techniczna.
5. Powtórz test przeglądarkowy (`scripts/test-ui-wyszukiwarka.mjs`) — axe musi zostać na zerze.

### Trzy reguły normalizacji, które musisz przenieść na nowe pola

- Pusta wartość klucza = **NIE**, nie „brak danych" (dowód w ADR).
- `选配` / `选装` = opcja cennikowa wersji, **nie liczy się** do flagi.
- Wartości parowe „A / B" (che168) — bierzesz **pierwszy człon** jako standard; rozbijasz
  tylko po ` / ` ze spacjami, nigdy po gołym `/` (`皮/翻毛皮`, `245/45 R19`).

---

## Kolejność, którą rekomenduję

**RUCH 0** (5 min) → **RUCH A** (2–3 h, natychmiastowy zysk dla filtra mocy) → decyzja Janka
o **C** (8–12 h) → dopiero potem **B1** na pełnym zestawie haseł. **B2/B3** osobno, kiedy Janek zdecyduje.

Robienie B1 przed C oznacza dotknięcie CTA dwa razy i przejściową niespójność (16 haseł
linkuje do wyszukiwarki, 44 do `/samochody/`).

## Definicja zrobionego dla tego wątku

1. Log crona 05:05 potwierdzony wpisem z nocy (domyka DoD 5 etapu 3).
2. Ruch A: dry-run pokazany Jankowi, `apply` po jego zgodzie, 0 ofert publish z mocą 0 KM,
   tabela przebudowana, trzy adresy sprawdzone w przeglądarce.
3. Każdy ruch z osobna: bump wersji, `docs/VERSIONS.md`, memory. ADR tylko dla C
   (A i B to wykonanie decyzji, które już zapadły).
4. `/samochody/` nadal bez regresji — `class-asiaauto-inventory.php` z mtime sprzed 02.09.
