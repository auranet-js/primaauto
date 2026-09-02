# T-116 / etap 3 — wyszukiwarka zaawansowana (osobna strona, bez linkowania)

> Prompt wykonawczy do odpalenia w nowym wątku w `~/projekty/primaauto`.
> Powstał 2026-09-02 po zamknięciu etapu 2 (dane). Liczby zmierzone tego dnia na produkcji,
> **zweryfikuj kluczowe na starcie** (pokrycia mogą się ruszyć po nocnych cronach).
>
> Przeczytaj przed startem, w tej kolejności:
> 1. `docs/roadmapa/T-116-etap2-pola-i-wartosci-wyszukiwarki.md` — 9 reguł normalizacji
>    wartości i pełna lista 114 pól-kandydatów z pokryciem. **To jest spec danych.**
> 2. `docs/decyzje/2026-09-02-katalog-autohome-trzecie-ogniwo-nocne.md` — skąd się wzięły dane
>    i pułapki (`wp_slash`, cache, kształt wartości dongchedi).
> 3. `docs/roadmapa/T-116-wyszukiwarka-zaawansowana.md` — spec z 14.07; sekcje „Warstwa A" i „Plan"
>    nadal aktualne, sekcje o unicode i „37 flagach che168" **nieaktualne** (rozwiązane w etapie 2).

---

## 0. Zasada widoczności (decyzja Janka 2026-09-02)

Wyszukiwarka to **osobna, publiczna strona** pod normalnym slugiem, **bez żadnego linkowania
wewnętrznego do odwołania**. Może być w sitemapie i w indeksie jak każda strona; nie ma `noindex`,
hasha ani hasła. Chodzi tylko o to, żeby nikt nie wchodził celowo w coś, co jeszcze nie działa.

Link **NIE pojawia się** w: menu (`nav.js` / header), stronie głównej (`front-page.php`), stopce,
hubach marek i modeli (`taxonomy-make.php`, `taxonomy-serie.php`), stronie `/samochody/`, CTA
„Wszystkie auta" na `/wiki/` (`project_wiki_cars_cta_awaits_search`), newsach, `llms.txt`.
Podpięcie do nawigacji to **osobny krok po odbiorze Janka** (sekcja 5, krok 7), nie część tego etapu.

Slug: `/wyszukiwarka/` (zmień tylko, jeśli Janek poda inny w poleceniu startowym).

## 1. Po co

Konkurencja filtruje po wyposażeniu, my po 7 taksonomiach i cenie. Po etapie 2 dane są:
flagi wyposażenia na **98,2%** ofert publish, moc na **99,6%**, warstwa liczbowa 80–99%.
Cel etapu: działająca wyszukiwarka z zakresami, enumami i flagami, na własnej stronie, z liczbami
wyników zgodnymi z `/samochody/` dla filtrów podstawowych, z zapytaniem poniżej 200 ms przy
5 aktywnych filtrach.

## 2. Stan wejściowy — co istnieje i czego NIE ruszasz

**Obecna wyszukiwarka** (`includes/class-asiaauto-inventory.php`, 1 815 linii + `assets/js/asiaauto-inventory.js`,
1 024 linie) — shortcode `[asiaauto_inventory]` na stronie `/samochody/` (post 93720), cztery trasy REST
w `asiaauto/v1`: `/listings`, `/models`, `/filter-counts`, `/search-suggestions`. Filtruje po
`$tax_map = ['make','serie','fuel','body','ca-year','exterior-color','drive']` + zakres `price`
(meta `price`), sortuje po dacie i cenie, ma liczniki zależne per taksonomia i autocomplete.
**Zostaje nietknięta.** Nowa wyszukiwarka nie dziedziczy po niej i nie modyfikuje jej tras. Jedyne,
co wolno: dodać w niej **publiczną metodę-wrapper** do renderowania karty oferty, jeśli chcesz tę
samą kartę (dziś `getSmartSpecs()` jest `private static`). Addytywnie, jedna metoda, zero zmian w istniejących.

**Dane** (publish 2 988 / publish+draft 3 367, pomiar 02.09):

| warstwa | gdzie | pokrycie | uwaga |
|---|---|---|---|
| marka, model, paliwo, nadwozie, rocznik, kolor, napęd | taksonomie jak w `$tax_map` | 99–100% | gotowe |
| skrzynia, stan | taksonomie `transmission` (7 termów), `condition` (3) | 99,7% / 100% | gotowe, nieużywane w filtrach |
| cena, przebieg | meta `price`, `mileage` | 100% | numeric |
| moc KM | meta `_asiaauto_horse_power` (+ `_asiaauto_horse_power_source`) | 99,6% | po etapie 2 = moc UKŁADU; istniejących wartości nie obniżamy do mocy silnika |
| na placu / w drodze | meta `_asiaauto_reservation_status` (`on_lot`) | 100% | `stm_car_location` NIE jest wiarygodne |
| wszystko inne | JSON `_asiaauto_extra_prep`, mediana 283 kluczy | patrz spec danych | **nieindeksowalne, wymaga spłaszczenia** |

**Import zapisuje `extra_prep`** w `class-asiaauto-importer.php:514` (nowa oferta) i `:948` (aktualizacja),
oba przez `wp_slash`. Po imporcie strzela `do_action('asiaauto_after_set_taxonomies', $post_id)` (`:610`).
Nocne wzbogacanie: bliźniak 04:35 → bank 04:45 → katalog Autohome 04:55 (crontab, `~/bin/cron-install`).

## 3. Architektura — wszystko obok, nic w środku

Nowe pliki, prefiks `asiaauto_` / `AsiaAuto_` jak reszta pluginu:

| plik | rola |
|---|---|
| `includes/class-asiaauto-specs.php` | tabela `wp7j_asiaauto_specs`: schemat, normalizatory, `rebuildRow($post_id)`, `rebuildAll()` |
| `includes/class-asiaauto-search.php` | REST `asiaauto/v1/search` (wyniki + strona) i `asiaauto/v1/search-counts` (liczniki zależne), shortcode `[asiaauto_search]` |
| `assets/js/asiaauto-search.js`, `assets/css/asiaauto-search.css` | UI: panel filtrów, wyniki, stan w URL |
| `scripts/zbuduj-specs.php` | backfill + odświeżanie przyrostowe (`[apply] [since=48h]`), dry-run domyślnie |
| `themes/primaauto2026/page-wyszukiwarka.php` | szablon strony (wzorzec: `page-marki.php`), tylko jeśli `page.php` + shortcode nie wystarczy |

**Tabela `wp7j_asiaauto_specs`** — jeden wiersz na ofertę, kolumny typowane, indeksy na każdej
kolumnie filtrowanej. Pierwsza wersja (nie rozbudowuj bez potrzeby, kolumny dokłada się `ALTER`-em):

```
post_id BIGINT PK, status VARCHAR(12), source VARCHAR(12), updated_at DATETIME,
-- zakresy
price INT, mileage INT, year SMALLINT, power_km SMALLINT, range_cltc SMALLINT,
battery_kwh DECIMAL(5,1), seats TINYINT, rim_in TINYINT,
-- enumy (slug PL, słownik w PHP)
fuel, body, drive, transmission, upholstery, sunroof VARCHAR(24),
-- flagi TINYINT(1): 20 z sekcji 4
seat_heat_f, seat_vent_f, seat_massage_f, seat_memory, seat_heat_r, wheel_heat,
cam_360, lidar, adaptive_cruise, lane_center, auto_park, sentinel,
hud, ar_hud, phone_mirror, net_5g, wireless_charge, heat_pump, air_susp, v2l
```

**Normalizacja** = dokładnie reguły z sekcji 2 spec danych, zaimplementowane jako małe funkcje
czyste w `AsiaAuto_Specs` (testowalne na tablicy `extra_prep`):
- flaga: obecność klucza = 1, chyba że wartość ∈ {`选配`,`选装`,`-`,`0`,`不支持`,`无`} → 0;
  pary `前● / 后●` rozbijane; koalescencja kluczy w ustalonej kolejności (np. tempomat adaptacyjny =
  `cruise_system`=`全速自适应巡航` ∪ `full_speed_adaptive_cruise` ∪ `adaptive_cruise`);
- liczba: `preg_match('~-?\d+(\.\d+)?~')`, pierwsza liczba; felgi z `rear_tire_size` → `R(\d\d)`;
  zasięg = `cltc_recharge_mileage` ∪ `recharge_mileage`; moc **z meta**, nie licz drugi raz;
- enum: słownik kanoniczny per pole w `data/specs-enums.php` (tapicerka: skóra / ekoskóra / mieszana /
  alcantara / tkanina; szyberdach: otwierany panoramiczny / stały panoramiczny / dzielony / szklany dach / brak);
  nieznana wartość → `NULL` + wpis w logu, nie zgadywanie;
- nadwozie/napęd/paliwo/skrzynia/rok **z taksonomii**, nie z `extra_prep` (`car_body_struct` i
  `body_struct` mają zamienione znaczenia między źródłami).

**Zasilanie tabeli:** (1) backfill `zbuduj-specs.php apply` na start; (2) hook na
`asiaauto_after_set_taxonomies` → `rebuildRow()` (nowa i odświeżana oferta); (3) cron `05:05`
(po katalogu 04:55) → `zbuduj-specs.php apply since=48h` dla ofert wzbogaconych nocą (stemple
`_asiaauto_spec_inherited_at`, `_asiaauto_spec_catalog_at`, `_asiaauto_fix_t116_at`); (4) przy zmianie
statusu oferty (`transition_post_status` dla `listings`) → aktualizacja kolumny `status`, żeby draft
i trash wypadały z wyników bez JOIN-a do `wp_posts`.

**REST:** `GET asiaauto/v1/search?price_min=&price_max=&…&flags=cam_360,lidar&sort=&page=` → `{total, items[], page}`;
`GET asiaauto/v1/search-counts?<te same parametry>` → liczniki dla każdej wartości enum i każdej flagi
**względem pozostałych aktywnych filtrów** (jak `/filter-counts` dziś). Jedno zapytanie SQL na
liczniki enumów (`GROUP BY`), jedno `SUM(flaga)` na flagi. Cache transient 10 min kluczowany hashem
parametrów, czyszczony przez `rebuildRow()`.

**UI:** jedna strona, panel filtrów z lewej (mobile: szuflada, 79,6% ruchu to mobile), wyniki
w tej samej karcie co `/samochody/`. Stan filtrów **w parametrach URL** (deep-link do wysłania
Ruslanowi), przycisk „wyczyść", licznik „N ofert" aktualizowany po każdej zmianie, sortowanie:
cena, przebieg, moc, zasięg, data. Bez autocomplete w pierwszej wersji.

## 4. Zestaw pól pierwszej wersji

Z rekomendacji spec danych, po pokryciu i sile dyskryminującej:

- **Zakresy (8):** cena, przebieg, rocznik, moc KM, zasięg CLTC, bateria kWh, liczba miejsc, felgi (cale).
- **Enumy (6):** paliwo, nadwozie, napęd, skrzynia, tapicerka, szyberdach.
- **Flagi (20):** fotele: podgrzewane / wentylowane / masaż (przód), pamięć, podgrzewane tył,
  podgrzewana kierownica; kamery i ADAS: 360°, lidar, tempomat adaptacyjny, centrowanie na pasie,
  automatyczne parkowanie, tryb wartownika; wyświetlacze i multimedia: HUD, AR-HUD, mirroring
  telefonu (HiCar ∪ Carlink ∪ CarPlay), 5G, ładowanie indukcyjne; komfort i EV: pompa ciepła,
  zawieszenie pneumatyczne, V2L.

Pomijasz flagi ≥98% jednorodne (ABS, ESP, LED, keyless…) i pola nieosiągalne (kolor wnętrza,
tylne koła skrętne, CarPlay jako osobny filtr). Drugi rzut (po odbiorze): pozostałe pozycje
z sekcji 3 spec danych, dokładane kolumnami.

## 5. Kroki, bez skrótów

1. **Schemat + normalizatory** (`AsiaAuto_Specs`), `dbDelta` przy aktywacji/wersji. Bramka:
   test jednostkowy normalizatorów na 30 realnych `extra_prep` (10 che168, 10 dongchedi, 10 ręcznych)
   z oczekiwanymi wynikami spisanymi ręcznie — **zanim** zbudujesz tabelę.
2. **Backfill** `zbuduj-specs.php` — dry-run z raportem: ile wierszy, ile `NULL` per kolumna,
   lista nieznanych wartości enumów. **Pokaż Jankowi.** Potem `apply`. Bramka: pokrycie kolumn
   zgodne ze spec danych ±2 pkt (np. `cam_360` ≈ 92%, `lidar` ≈ 46%, `power_km` ≈ 99%).
3. **REST** `search` + `search-counts`. Bramka: dla każdej kombinacji filtrów podstawowych
   (marka, paliwo, nadwozie, cena) liczba wyników **równa** wynikowi `/listings` obecnej wyszukiwarki
   na tych samych parametrach (skrypt porównawczy na 50 losowych kombinacjach, 0 rozjazdów).
   Czas zapytania przy 5 filtrach < 200 ms na produkcji (`EXPLAIN`, indeksy).
4. **Hook importu + cron 05:05** (przez `cron-install`) + `transition_post_status`. Bramka: import
   testowy jednej oferty (istniejąca ścieżka `wp asiaauto` / ręczny import) tworzy wiersz; zmiana
   na draft znika z wyników w tej samej sekundzie.
5. **UI** na `/wyszukiwarka/`, mobile-first, a11y jak reszta motywu (kontrast 4,5:1 przy 320 px,
   `tabindex` na obszarach przewijanych — patrz VERSIONS 0.34.30). Bramka: Lighthouse a11y ≥ 95,
   deep-link z 5 filtrami odtwarza stan po odświeżeniu.
6. **Odbiór**: link do Janka, tydzień obserwacji GA4 (strona istnieje, ale nikt jej nie linkuje).
   Bump wersji, `docs/VERSIONS.md`, ADR w `docs/decyzje/`, memory.
7. **Osobna decyzja, poza tym etapem:** podpięcie do nawigacji, CTA na `/wiki/`, ewentualna
   podmiana `/samochody/`.

Szacunek: 1–2 → 10–14 h, 3 → 8–10 h, 4 → 4–6 h, 5 → 14–18 h, 6 → 3–4 h. Razem **40–52 h**.
Warstwa A z T-116 (10–14 h) jest w tym zawarta, nie wdrażaj jej osobno w starej wyszukiwarce.

## 6. Strefy kruche — czego nie wolno

- `class-asiaauto-inventory.php` i `asiaauto-inventory.js`: **żadnych zmian** poza jednym
  publicznym wrapperem do karty (jeśli w ogóle). `/samochody/` ma działać identycznie po etapie.
- Importer: nie dotykasz `findByInnerId`, slugów, rezerwacji, obrazów, zapisu `extra_prep`.
  Hook `asiaauto_after_set_taxonomies` jest do **podpięcia**, nie do edycji.
- Nazwy klas, CPT, meta, ról, shortcodów: prefiks `asiaauto_*` zostaje.
- Motyw aktywny to `primaauto2026`; `themes/asiaauto/` jest martwy, nie edytuj (`wp theme list --status=active`).
- Zapis `_asiaauto_extra_prep` **zawsze** przez `wp_slash` (ten etap nie powinien go w ogóle zapisywać).
- Cron tylko przez `~/bin/cron-install`. Backup `mysqldump` przed `CREATE TABLE` i przed backfillem.
- Zero linkowania wewnętrznego (sekcja 0). Zero `noindex` (też sekcja 0 — strona jest publiczna).
- Homepage, `taxonomy-*.php`, `single-listings.php` — bez zmian bez osobnego OK.

## 7. Gotchy, na które natrafisz

- Ta sama nazwa CN w dwóch kluczach z różną semantyką (`car_body_struct` / `body_struct`) —
  dlatego nadwozie z taksonomii. `air_supply` po 02.09 = rozrząd, doładowanie w `gas_form`.
- `选配` znaczy „opcja w cenniku producenta", nie „to auto ma". Do flag liczysz tylko standard.
- Dongchedi zapisuje flagi jako podnazwę (`按摩`), che168 z katalogu tak samo po etapie 2, ale
  starsze oferty che168 z ręcznych biegów mogą mieć `标配` — normalizator musi przyjąć oba.
- `panoramic_camera` ma trzy zapisy (`360°全景影像`, `标配`, dawniej `360u00b0…` — naprawione 02.09);
  po unicode nie szukaj, ale normalizator i tak ma być odporny.
- `_asiaauto_horse_power` = moc układu dla PHEV/EREV; `engine_max_horsepower` w `extra_prep` to
  moc silnika. Nie mieszaj ich w jednej kolumnie.
- Liczniki zależne: `search-counts` bez cache przy 20 flagach to 20 `SUM` w jednym zapytaniu —
  jedno `SELECT SUM(a),SUM(b),…`, nie 20 zapytań.
- Oferty bez `extra_prep` (kilkanaście) i bez `spec_id` dongchedi (ok. 140 <100 pól) dostają wiersz
  z `NULL`-ami: filtr je pomija, ale zakres ceny/przebiegu/rocznika je widzi.

## 8. Definicja zrobionego

1. Tabela zbudowana, pokrycie kolumn zgodne ze spec danych, raport `NULL`-i i nieznanych enumów w ADR.
2. `/wyszukiwarka/` działa na produkcji, publiczna, **nigdzie nie podlinkowana** (grep po motywie
   i treściach = 0 wystąpień slugu poza samą stroną i sitemapą).
3. 50 losowych kombinacji filtrów podstawowych: liczby wyników = obecna wyszukiwarka, 0 rozjazdów.
4. Czas `search` przy 5 filtrach < 200 ms, `search-counts` < 300 ms (pomiar na produkcji, 10 prób).
5. Nowa oferta z importu i oferta wzbogacona nocą trafiają do tabeli bez ręcznej interwencji
   (dowód: log crona 05:05 z następnej nocy).
6. Lighthouse a11y ≥ 95 mobile, deep-link odtwarza stan.
7. `/samochody/` bez regresji: te same 4 trasy REST odpowiadają identycznie (snapshot przed/po na 20 zapytaniach).
8. Bump wersji, `docs/VERSIONS.md`, ADR, memory.

## 9. Czego ten etap świadomie NIE dowozi

Linkowania i promocji strony, podmiany `/samochody/`, drugiego rzutu pól (94 pozostałe
z listy kandydatów), pokazywania wyposażenia opcjonalnego (`选配`) jako „opcja", autocomplete,
obsługi ofert dongchedi bez `spec_id` (parking: ścieżka ręczna nazwa CN → specid).
