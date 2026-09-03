# T-250 — słownik `/wiki/` pod wyszukiwarkę zaawansowaną (SEO + linkowanie)

> Prompt wykonawczy do odpalenia w **nowym wątku** w `~/projekty/primaauto` (decyzja Janka
> 2026-09-03: „poboczne działania w osobnym wątku, żeby nie zaśmiecać"). Powstał po rozpoznaniu
> w wątku wyszukiwarki 0.37.9; liczby z 2026-09-03 12:30.
>
> Przeczytaj przed startem: `docs/roadmapa/T-116-kandydaci-pastylek.md` (jakie pastylki są),
> `docs/VERSIONS.md` 0.37.5–0.37.9, memory `project_dzial_wiedzy_watek_kontynuacyjny_2026_07_22`
> (pipeline haseł `scripts/kb/`), `project_wiki_cars_cta_awaits_search`.

## 0. Stan (zweryfikuj na starcie)

Słownik: **89 haseł** publish (`asiaauto_wiki`), **73 z kluczami** `_wiki_term_keys` → linkują
do ofert przez `AsiaAuto_Wiki_Cars` (indeks co 6 h). Skrypt zestawienia pastylka ↔ hasło:
przelot z 03.09 w `tmp/` sesji — odtwórz z `AsiaAuto_Specs_Table::FLAGS` + `_wiki_term_keys`.

| co | stan |
|---|---|
| pastylki wyszukiwarki | 36 |
| z hasłem w słowniku | 13 (masaż, kamera 360°, lidar, wartownik, AR-HUD, pompa ciepła, pneumatyka, V2L, NOA, audio ×5 marek, lodówka, zero gravity, automatyczne parkowanie) |
| **bez hasła** | **23**: dach panoramiczny, tylna oś skrętna, tempomat adaptacyjny, centrowanie na pasie, HUD, ekran pasażera przód, ekran tył, Dolby Atmos, głośniki w fotelach, sterowanie gestami, rozpoznawanie znaków, mirroring telefonu, 5G, ładowanie indukcyjne, podgrzewane fotele, wentylowane fotele, pamięć fotela, podgrzewana kanapa, podgrzewana kierownica, masaż tył, ogrzewanie lusterek, zdalny rozruch, hak holowniczy |
| hasła bez kluczy (nie linkują do ofert) | 16: BYD DM-p, CarPlay/Android Auto, Chery Super Hybrid, CTP/CTB, Frunk, Geely EM-i, God's Eye, HarmonyOS, Huawei ADS, HiCar, kW/KM, NEV, DHT, rekuperacja, rok modelowy, GB/T |
| rozjazd kluczy | hasło „Automatyczne parkowanie" ma klucz `memory_parking`, pastylka `auto_park` ma `auto_park` |
| CTA pod hasłem | `class-asiaauto-wiki-cars.php:~168` → `/samochody/` z samym licznikiem; nie wie o wyszukiwarce |

## 1. Decyzje Janka (2026-09-03)

1. **Klucze do 16 haseł bez kluczy** — robimy (ok. 0,5 h). Klucze z `extra_prep`: CarPlay →
   `mobile_system_1`, HiCar → `mobile_system_8`, Huawei ADS → `driving_assist_system`, HarmonyOS →
   `car_intelligent_system`, DHT → `gearbox_type`/`gearbox_description`, God's Eye → `driving_assist_system`
   (BYD), DM-p / EM-i / CSH → `engine_description` lub `fuel_form` + marka; kW/KM, NEV, rok modelowy,
   rekuperacja, GB/T, frunk — sprawdź, czy klucz istnieje (rekuperacja: `energy_recovery_system`?),
   inaczej zostają bez.
2. **Nowe hasła dla WSZYSTKICH 23 pastylek bez hasła** — „liczba jest spora, ale policzalna
   i skończona". Nie kuratorować, robić do całości. Pipeline `scripts/kb/` (T-214), format i ton jak
   istniejące hasła, każde z `_wiki_term_keys` = klucze pastylki z `AsiaAuto_Specs_Table::FLAGS`
   (dla `screen_copilot` prefiks `vice_screen_`/`copilot_screen`, dla `roof_panorama` `skylight_type`
   z wartościami panoramy). Naprawić przy okazji rozjazd `auto_park`/`memory_parking`.
3. **Linkowanie hasło ↔ wyszukiwarka** — ok co do zasady, **UI nierozstrzygnięte**:
   - CTA pod hasłem: dziś `/samochody/`; docelowo `/wyszukiwarka/?wyposazenie=<flaga>` (licznik = ten
     sam, co pastylka). Prosty krok, jedna linia + mapa hasło → flaga.
   - Link z filtra do hasła: **dymek czy ikona (i) przy nazwie — Janek nie wie**. Rekomendacja do
     pokazania na makiecie, nie na ślepo: ikona (i) 20 px przy etykiecie pastylki/pola otwiera mały
     popover (2 zdania definicji + „Czytaj w słowniku →"); dymek `title` odpada, bo nie działa
     dotykiem. Zmierzyć axe po dołożeniu (przycisk w `<label>` = zagnieżdżony interaktywny → ikona
     musi stać OBOK etykiety, nie w niej).
   - **Autolinki w treści są za słabe** (Janek: „CLTC w ogóle się nie wyróżnia, trudno się dziwić,
     że nikt nie klika"). Fakt z kodu: `.aa-autolink` w `themes/primaauto2026/assets/css/base.css:226`
     = kolor dziedziczony + kropkowane podkreślenie `#A0AEC0`, celowo „subtelny". Na ofercie AITO M9
     jest 8 autolinków (Range Extender, LiDAR, CLTC, NMC, CATL, Keyless, PM2.5, OTA), **wszystkie
     w treści**, bo autolinker działa tylko na `the_content`; tabela specyfikacji
     (`class-asiaauto-single.php:291`, `.aa-spec-table`) nie jest autolinkowana wcale.
     Do zrobienia: (a) mocniejszy styl autolinku (pełne podkreślenie w kolorze granatu albo
     ikonka „?" po słowie) — pokazać dwa warianty na jednej ofercie; (b) autolinkowanie etykiet
     w tabeli specyfikacji (nazwa parametru → hasło), to osobna ścieżka niż `the_content`.

## 2. Pomiar PRZED zmianą UI (obowiązkowy — „decyzje wymagają data check")

Janek: „nikt nie odwiedza stron haseł — co jest do sprawdzenia". Zmierz GA4 (property 534017542,
`scripts/ga4_query.py`, 90 dni): odsłony `/wiki/*` łącznie i per hasło (top 15), udział wejść
z zewnątrz vs z wnętrza serwisu, **ile sesji przechodzi z `/oferta/*` na `/wiki/*`** (to mierzy
autolinki) i ile z `/wiki/*` na `/oferta/*` lub `/samochody/` (to mierzy CTA). Bez tych liczb nie
zmieniaj stylu autolinków — może się okazać, że problem jest w treści haseł, nie w podkreśleniu.
Zapisz wynik w `docs/sesje/` i dopiero potem UI.

## 3. Kolejność i szacunek

1. Pomiar GA4 (0,5 h) → 2. klucze do 16 haseł (0,5 h) → 3. 23 nowe hasła pipeline'em
(4–7 h z przeglądem Janka) → 4. CTA pod hasłem → wyszukiwarka z flagą (0,5 h) → 5. makieta
wariantów autolinku i ikony (i) na JEDNEJ ofercie/pastylce, decyzja Janka, wdrożenie (1–2 h).
Razem 7–10 h.

## 4. Czego nie ruszać

`class-asiaauto-inventory.php`, importer, `renderCard()`. Wyszukiwarka (`class-asiaauto-search.php`)
tylko w punkcie 5 i tylko addytywnie (ikona obok etykiety). Publikacja nowych haseł = zgoda Janka
na partię (memory `feedback_publikacja_zewnetrzna_wymaga_osobnej_zgody`).
