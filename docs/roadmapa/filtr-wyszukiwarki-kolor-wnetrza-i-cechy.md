# Filtr zaawansowanej wyszukiwarki — kolor wnętrza, trzy cechy, hak

> Prompt do nowego wątku. Zlecenie Janka 2026-09-07. Rozpoznanie już zrobione — liczby poniżej
> pochodzą z bazy, nie z szacunków.

## Kontekst — gdzie co siedzi

Filtr czyta spłaszczoną tabelę **`wp7j_asiaauto_specs`** (3 422 wiersze), budowaną nocą
przez `scripts/zbuduj-specs.php` (cron 05:05) ze źródła prawdy, czyli `_asiaauto_extra_prep`.

| Plik | Rola |
|---|---|
| `includes/class-asiaauto-specs-table.php` | mapa `kolumna => klucze extra_prep` (`FLAGS`, `RANGES`, `ENUMS`), schemat tabeli, stała `NEGATIVE` |
| `includes/class-asiaauto-search.php` | definicja filtrów w UI (ok. linia 107 enumy, 120 grupa cech, 918 etykiety PL) |
| `scripts/zbuduj-specs.php` | przebudowa wierszy (`apply since=48h`) |

Wartość cechy staje się flagą `1` tylko wtedy, gdy **nie** znajduje się na liście
`NEGATIVE = ['', '-', '--', '0', '选配', '选装', '不支持', '无', '否', 'null']`.
Czyli `标配` (standard) = tak, `选配`/`选装` (opcja za dopłatą) = nie.

## Zadanie 1 — kolor wnętrza (główny powód tego wątku)

Filtr **już istnieje** (`class-asiaauto-search.php:107`, enum z kropkami, kolumna `interior_color`),
ale jest wypełniony dla **209 z 3 422 ofert, czyli 6,1%**. Przy takim pokryciu filtr odsiewa
94% magazynu i praktycznie nie da się go użyć.

Wartości, które już są (znormalizowane, angielskie):
`white` 44 · `orange` 41 · `black` 41 · `brown` 38 · `dark-gray` 12 · `beige` 11 · `red` 8 ·
`purple` 7 · `blue` 3 · `grey` 2 · `green` 1 · `other` 1

Do ustalenia w wątku:
1. Skąd bierze się `interior_color` — w `class-asiaauto-specs-table.php:294` idzie z **taksonomii**
   `interior-color`, a nie wprost z `extra_prep`. Sprawdzić, kto tę taksonomię wypełnia przy imporcie
   i dlaczego dla 94% ofert zostaje pusta.
2. W samym `extra_prep` klucz `interior_color` też występuje, ale rzadko (8 na 600 w próbce) i
   z wartościami CN (`深色` = ciemne). Zbadać, czy da się dołożyć drugie źródło — np. `upholstery`
   (materiał tapicerki) jest wypełniony dla **3 083 ofert**, więc dane o wnętrzu są, tylko o innym
   wymiarze.
3. Rozstrzygnąć z Jankiem: czy filtr ma zostać „kolorem wnętrza" przy niskim pokryciu, czy
   przejść na coś gęściej wypełnionego.

## Zadanie 2 — dodać dwie cechy do filtra

Obie mają dane i sensowne pokrycie (próbka 600 najnowszych ofert):

| Cecha (etykieta PL) | Klucz `extra_prep` | Pokrycie | Wartości |
|---|---|---|---|
| Lusterko wsteczne z obrazem z kamery | `stream_media_inside_mirror` | 155/600 ≈ 26% | `流媒体` |
| Szyby wygłuszające (wielowarstwowe) | `multilayer_soundproof_glass` | 291/600 ≈ 48% | `前排` (przód), `后排` (tył), `前排+后排`, `全车` (całe auto) |

Kroki: dopisać kolumny do schematu i do `FLAGS` w `class-asiaauto-specs-table.php`, dodać do listy
cech i etykiet w `class-asiaauto-search.php`, przebudować tabelę
(`wp eval-file scripts/zbuduj-specs.php apply` — bez `since`, bo to nowe kolumny dla całej bazy).

Uwaga przy szybach: wartości są **stopniowalne** (przód / tył / całe auto), więc flaga 0-1 gubi
informację. Do decyzji, czy zostaje flagą („ma wygłuszane"), czy enumem.

## Zadanie 3 — usunąć „Zdalny rozruch"

Kolumna `remote_start` (klucz `engine_remote_start`), wypełniona dla **3 033 ofert**.
Powód usunięcia (Janek): *„nie każdy z tych aut ta funkcja zapracuje w Europie"* — obiecywanie
funkcji, która po sprowadzeniu może nie działać, jest gorsze niż jej brak.

Wyciąć z listy cech w `class-asiaauto-search.php:120` i z etykiet (`:918`). Kolumnę w tabeli
zostawić — nie szkodzi, a usunięcie wymaga migracji schematu.

## Zadanie 4 — rejestrator: NIE dodawać, danych nie ma

Janek prosił o dodanie, ale sprawdzenie mówi jasno: w całej bazie jest **1 oferta** z 行车记录仪,
a w próbce 800 ofert **żaden** klucz `extra_prep` nie pasuje do wzorca `record|dvr|dashcam`.
Filtr nie miałby czego filtrować.

Jeśli cecha jest ważna, najpierw trzeba ustalić, czy katalog Autohome ją w ogóle oddaje
(`scripts/autohome-catalog-fetch.js <specid>` i szukanie grupy 多媒体配置) — dopiero potem filtr.

## Zadanie 5 — hak holowniczy: jest w filtrze, ale nie da się go ustawić ręcznie

Stan: kolumna `tow_hook` ← klucz `drag_hook`, filtr działa, **398 ofert** ma flagę.

Problem: **`class-asiaauto-listing-editor.php` w ogóle nie dotyka `extra_prep`** (zero wystąpień
w pliku). Ręczna edycja i ręczne wystawianie oferty pozwalają zmienić cenę, VIN, przebieg,
taksonomie i zdjęcia — ale żadnej cechy wyposażenia. Hak trzeba dziś ustawiać przez WP-CLI
(instrukcja w `docs/produkcja/`), co dla Ruslana jest nieużywalne.

Do zrobienia: dołożyć do metaboksu edytora sekcję z najważniejszymi cechami (hak na pewno,
reszta do ustalenia), zapisującą do `extra_prep` i odświeżającą wiersz w `wp7j_asiaauto_specs`.
Uwaga: edycja musi przeżyć sync — patrz `_asiaauto_manual_import` / `isManuallyManaged()`.

## Czego NIE ruszać

- `remote_start` w tabeli — usuwamy tylko z UI.
- Nocnej sekwencji cronów (04:35 bliźniak → 04:45 bank → 04:55 katalog → 05:00 moc → 05:05 specs);
  kolejność jest celowa, opisana w ADR 2026-09-02.
- Kolumn i kluczy wybranych przez Janka 03.09 (lista 117 kandydatów, numery 15, 16, 27, 62, 76,
  79, 87, 96, 101, 108) — to jego wybór, nie modyfikować bez pytania.
