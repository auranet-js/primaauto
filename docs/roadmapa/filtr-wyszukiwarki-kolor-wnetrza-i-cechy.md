# Filtr zaawansowanej wyszukiwarki — kolor wnętrza, trzy cechy, hak

> **Status: zrobione 2026-09-07** (commit `3918e42`). Ten plik był promptem do wątku; po wykonaniu
> przepisany na zapis stanu. Liczby pochodzą z bazy i z odpowiedzi API, nie z szacunków.
>
> Zakres świadomie ograniczony do **filtrów wyszukiwarki zaawansowanej**. Karta oferty nie była
> przedmiotem tego wątku — patrz „Czego NIE ruszaliśmy".

## Kontekst — gdzie co siedzi

Filtr czyta spłaszczoną tabelę **`wp7j_asiaauto_specs`** (3 422 wiersze, 3 022 opublikowane),
budowaną nocą przez `scripts/zbuduj-specs.php` (cron 05:05) ze źródła prawdy, czyli
`_asiaauto_extra_prep`.

| Plik | Rola |
|---|---|
| `includes/class-asiaauto-specs-table.php` | mapa `kolumna => klucze extra_prep` (`FLAGS`, `RANGES`, `ENUMS`), schemat tabeli, stała `NEGATIVE` |
| `includes/class-asiaauto-search.php` | definicja filtrów w UI (`SEKCJE`, `FLAG_GROUPS`, `RANGE_LABELS`) |
| `scripts/zbuduj-specs.php` | przebudowa wierszy (`apply`, opcjonalnie `since=48h`) |

Wartość cechy staje się flagą `1` tylko wtedy, gdy **nie** znajduje się na liście
`NEGATIVE = ['', '-', '--', '0', '选配', '选装', '不支持', '无', '否', 'null']`.
Czyli `标配` (standard) = tak, `选配`/`选装` (opcja za dopłatą) = nie.

---

## 1. Kolor wnętrza — ZDJĘTY z filtra. Danych nie ma i nie będzie

Filtr istniał (`interior_color`, enum z kropkami), ale był wypełniony dla **209 z 3 422 ofert
(6,1%)**. Rozbicie po źródle pokazało, że to nie luka do załatania, tylko brak danych u źródła:

| Źródło | Ofert | Ma kolor wnętrza |
|---|---|---|
| che168 (żywy strumień) | 2 400 | 85 (3,5%) — **84 z nich to `_asiaauto_manual_import=1`** |
| dongchedi (zamrożony) | 889 | 27 (3,0%) |

Sprawdzone na trzech poziomach, żeby nie wracać do tematu:

1. **Surowa odpowiedź `getOffer('che168', …)` z auto-api** — przeszukany cały JSON (~20 KB/oferta)
   sześciu ofert po `内饰|颜色|深色|浅色`. Jedyne trafienie to zdanie z opisu handlowca
   („内饰整洁" = wnętrze zadbane), nie kolor. Top-level ma `color` (nadwozie) i nic poza tym.
   Bloki `extra.configuration`, `extra.option`, `extra.inspection` — nic.
2. **Katalog Autohome po `specid`** (`scripts/autohome-catalog-fetch.js`) — 23 grupy parametrów,
   dwa trafienia na „色": `中控彩色屏幕` (kolorowy ekran) i `256色` (oświetlenie ambientowe).
   Grupy `内饰颜色` nie ma.
3. **`extra_prep`** — klucz `interior_color` w **7 na 600** najnowszych ofert, wartości CN (`深色`).

Powód jest strukturalny: che168 to giełda aut **używanych** C2C — sprzedawca deklaruje kolor
nadwozia, bo widać go na zdjęciu z zewnątrz, a wnętrza nie; katalog Autohome opisuje **wersję**,
nie egzemplarz, a jedna wersja bywa dostępna w kilku wariantach wnętrza.

**Decyzja Janka:** filtr zdjęty z UI. Kolumna `interior_color` i taksonomia `interior-color`
**zostają** w bazie — karta oferty nadal pokazuje kolor tam, gdzie jest.

**Odrzucona alternatywa:** doczytanie koloru ze zdjęć przez Gemini vision (każda oferta ma ~15
pobranych fotek, w tym wnętrze; szacowany koszt rzędu 0,5–1 USD za całą bazę, Batch API o połowę
taniej). Gdyby temat wrócił — to jedyna działająca ścieżka.

## 2. Grupa „Styl i komfort" — nowy skład

Po zdjęciu koloru wnętrza grupa dostała skład i kolejność wg Janka:

`Kolor nadwozia` · `Rodzaj zawieszenia` · `Materiał tapicerki` · `Marka nagłośnienia`

Marka nagłośnienia **przeniesiona** z sekcji „Wyposażenie i technologie", gdzie stała samotnie
obok pastylek.

## 3. Trzy nowe cechy — LIVE

`SCHEMA_VERSION` 6 → 7, tabela przebudowana w całości (9,3 s, 3 422 wiersze bez ubytku).

| Etykieta PL | Kolumna | Klucz `extra_prep` | Ofert |
|---|---|---|---|
| Lusterko z obrazem z kamery | `mirror_stream` | `stream_media_inside_mirror` | 857 |
| Szyby wygłuszające | `glass_sound` | `multilayer_soundproof_glass` | 2 075 |
| Rejestrator jazdy | `dashcam` | `built_in_tachograph` | 2 810 |

**Szyby świadomie jako flaga, nie enum.** Wartości są stopniowalne (`前排` przód / `后排` tył /
`前排+后排` / `全车` całe auto), ale dominujący zapis `前排 / 后排` to konwencja che168
„standard / z pakietem", a `firstVariant()` bierze pierwszy człon. Po normalizacji rozkład to
252 „przód" na 280 wypełnionych — enum nie różnicowałby niczego.

**Rejestrator: poprzednie rozpoznanie było błędne.** Ogłosiło „danych nie ma — 1 oferta w bazie",
bo szukało w **nazwach kluczy** wzorca `record|dvr|dashcam`, a klucz nazywa się
`built_in_tachograph` (`内置行车记录仪`) i ma pokrycie 82%. **Lekcja na przyszłość: kandydatów
szukaj po wartościach i po etykietach PL z `data/translations-extra-prep.php`, nie po angielskich
nazwach kluczy — nazewnictwo auto-api bywa nieoczywiste.**

Zastrzeżenie do rejestratora: przy 82% pokrycia ten filtr odsiewa co piątą ofertę. Działa, ale
zawęża słabo.

## 4. „Zdalny rozruch" — zdjęty z filtra

Kolumna `remote_start` (klucz `engine_remote_start`), 2 670 ofert. Powód (Janek): *„nie każdy
z tych aut ta funkcja zapracuje w Europie"* — obiecywanie funkcji, która po sprowadzeniu może nie
działać, jest gorsze niż jej brak.

Wycięty z listy pastylek i z etykiet. **Kolumna w tabeli zostaje** — nie szkodzi, a usunięcie
wymagałoby migracji schematu. Parametr API nadal przyjmowany.

## 5. Hak holowniczy — filtr działa, ręcznego ustawiania NIE ma (niezlecone)

Stan: kolumna `tow_hook` ← klucz `drag_hook`, filtr działa, **351 ofert** ma flagę.

`class-asiaauto-listing-editor.php` w ogóle nie dotyka `extra_prep` (zero wystąpień w pliku).
Ręczna edycja i ręczne wystawianie oferty pozwalają zmienić cenę, VIN, przebieg, taksonomie
i zdjęcia — ale żadnej cechy wyposażenia. Hak trzeba dziś ustawiać przez WP-CLI.

**Ten punkt NIE był zlecony i nie został ruszony.** Gdyby kiedyś wrócił, kierunek techniczny:
osobna meta `_asiaauto_extra_prep_override` (mapa `flaga => 0/1`) doklejana przy budowie wiersza
specs, zamiast pisania do `_asiaauto_extra_prep` — `extra_prep` jest nadpisywane przez sync,
a importer to strefa krucha (CLAUDE.md sekcja 3). Override przeżyje sync bez dotykania importera.

---

## Weryfikacja (2026-09-07)

Filtry odpytane przez REST, `GET /wp-json/asiaauto/v1/search?wyposazenie=…`:

| Zapytanie | Wynik |
|---|---|
| brak filtra | 3 022 |
| `dashcam` | 2 471 |
| `glass_sound` | 1 829 |
| `mirror_stream` | 755 |
| `glass_sound,dashcam` | 1 670 |

Strona `/wyszukiwarka/` (HTTP 200): „Kolor wnętrza" 0 wystąpień, „Zdalny rozruch" 0 wystąpień,
„Rejestrator jazdy" / „Szyby wygłuszające" / „Lusterko z obrazem z kamery" / „Marka nagłośnienia"
obecne.

Backupy: `class-asiaauto-{search,specs-table}.php.bak-2026-09-07-filtr` na serwerze,
`~/backups/primaauto/2026-09-07/asiaauto_specs-przed-filtrem.sql`.

## Zapas kandydatów na przyszłe filtry

Zmierzone na próbce 595 ofert z `extra_prep` (pokrycie po odrzuceniu wartości `NEGATIVE`).
Wszystkie nieużywane — gotowa lista, gdyby trzeba było dołożyć filtr:

| Kandydat | Klucz | Pokrycie | Kształt |
|---|---|---|---|
| Pojemność bagażnika | `baggage_volume` | 87% | zakres, litry; `375` albo `729-1677` (pierwsza liczba) |
| Klimatyzacja niezależna z tyłu | `rear_independent_air_conditioning` | 46% | flaga |
| Ukryte klamki | `hidden_door_handle` | 43% | flaga |
| Karaoke | `karaoke` | 42% | flaga |
| Podświetlenie ambientowe | `active_ambient_light_1` | 39% | flaga (`interior_light` 63%, ale wartości to liczba kolorów) |
| Drzwi elektryczne | `electric_door` | 35% | flaga |
| Wentylacja foteli z tyłu | `rear_seat_ventilation` | 34% | flaga; przód i grzanie tyłu już są |
| Typ ekranu centralnego | `center_screen_type` | 32% | enum LCD / AMOLED / OLED |
| Bagażnik przedni (frunk) | `front_baggage_volume` | 25% | zakres, litry |
| Liczba silników | `electric_drive_number` | 90% EV | enum 1 / 2 / 3 / 4 — dzieli 281/233/15/4 |
| Typ baterii | `battery_type` | 90% EV | enum LFP / NMC — dzieli 259/246 |

Cechy o pokryciu >88% (`keyless_start`, `car_networking`, `ota_upgrade`, `rear_air_outlet`,
`active_brake`…) świadomie pominięte: filtr, który zostawia 95% magazynu, niczego nie zawęża.

## Czego NIE ruszaliśmy

- **Karty oferty.** Wszystkie ustalenia dotyczyły wyłącznie filtrów wyszukiwarki zaawansowanej.
  `engine_remote_start` nadal wyświetla się w tabeli wyposażenia na stronie pojedynczej oferty
  (`data/translations-extra-prep.php`, grupa „Zdalne sterowanie") — to osobna decyzja, nie zapadła.
- **`remote_start` w tabeli specs** — usunięte tylko z UI.
- **Edytora ofert** — punkt 5, niezlecony.
- **Nocnej sekwencji cronów** (04:35 bliźniak → 04:45 bank → 04:55 katalog → 05:00 moc → 05:05 specs);
  kolejność jest celowa, opisana w ADR 2026-09-02.
- **Kolumn i kluczy wybranych przez Janka 03.09** (lista 117 kandydatów, numery 15, 16, 27, 62, 76,
  79, 87, 96, 101, 108) — to jego wybór, nie modyfikować bez pytania.
