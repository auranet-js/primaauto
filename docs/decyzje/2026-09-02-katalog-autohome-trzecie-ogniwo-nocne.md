# 2026-09-02 — Katalog Autohome jako trzecie ogniwo nocnego wzbogacania specyfikacji (T-116 etap 2)

> **Status:** wdrożone (v0.34.31, backfill 2026-09-02 11:12–13:00, cron od 2026-09-03 04:55)
> **Poprzednik:** `docs/roadmapa/T-116-etap2-katalog-autohome.md` (prompt wykonawczy),
> `docs/roadmapa/T-116-wyszukiwarka-zaawansowana.md` (spec z 14.07, częściowo nieaktualny)
> **Następnik:** `docs/roadmapa/T-116-etap2-pola-i-wartosci-wyszukiwarki.md` (analiza pól i wartości)

## Problem

Wyposażenie w `_asiaauto_extra_prep` miało flagi na 51,6% ofert publish (pomiar 02.09 rano).
Bez tego filtr „masaż” czy „lidar” zwracałby losowy podzbiór. Dwa nocne crony (bliźniak
z dongchedi 04:35, bank wariantów 04:45) działały poprawnie, ale **wyczerpały dawców**:
oba czerpią z tego, co już mamy lokalnie, a dongchedi jest zamrożone i się kurczy, więc
z 42–164 ofert/dobę w sierpniu spadły do 3–12. Ostatniej nocy przed etapem: 7 ofert,
a 925 ubogich ofert bez dawcy.

Katalog Autohome (`car.autohome.com.cn/config/spec/{specid}`) jest jedyną ścieżką niezależną
od stanu lokalnego: 99% ofert che168 ma `_asiaauto_spec_id`, 720 unikalnych specid pokrywa
1 923 oferty. Do 02.09 był uruchamiany wyłącznie ręcznie (5 nierównych partii, ostatnia 20.08).

## Ustalenia, które zmieniły plan

1. **Fetcher gubił jedną trzecią danych.** `scripts/autohome-catalog-fetch.js` czytał tylko
   `valueitems[].value`. Dla `displaytype: 1` to pole jest puste, a dane siedzą
   w `valueitems[].sublist[]` (`subname` = nazwa funkcji, `subvalue` = wartość gdy brak nazwy).
   Puste były dokładnie pola złożone, o które chodzi w tasku: fotele, kamery, mirroring,
   klucze, tryby jazdy. Pomiar: 99 z 303 pozycji pustych na specid 73246, po poprawce 0.
2. **Stary cache był bezwartościowy.** 555 plików w `uploads/asiaauto/autohome-catalog/`
   pochodziło ze starego fetchera. Skrypt backfillu bierze cache bez pytania, więc przed
   biegiem cache został przeniesiony do `~/backups/primaauto/2026-09-02/`. Zasada na
   przyszłość: **zmiana fetchera = unieważnienie cache**.
3. **Merger zapisywał JSON bez `wp_slash`.** `update_post_meta` robi `wp_unslash` i zjada
   escape'y: `"\n"` → `"n"`, `"马"` → `"u9a6c"`. Bliźniak i bank robią to poprawnie.
   Backfill uszkodził 151 wartości w 140 ofertach (`user_custom_pkg`, `battery_charge_time`,
   opisy pakietów). Wykryte weryfikacją z dumpem, naprawione z dumpu, merger poprawiony.
   Ręczne biegi katalogu z lipca i sierpnia (ok. 630 ofert) używały tego samego mergera,
   więc ich wartości z nową linią mogą być uszkodzone od tamtej pory. Skala niezmierzona.
4. **Dongchedi zapisuje flagi jako podnazwę, nie `标配`** (`front_seat_massage_1` = `按摩`,
   `drive_mode_1` = `运动`). Tryb złożony mapy zapisuje więc podnazwę, żeby oba źródła
   miały identyczny kształt wartości.
5. **Trzy pola z listy Janka są nieosiągalne:** kolor wnętrza (cecha egzemplarza, żadne
   źródło nie wysyła, taksonomia 6%), tylne koła skrętne (brak parametru w katalogu),
   Apple CarPlay jako osobny filtr (w Chinach HiCar/Carlink; zmapowane uczciwie jako
   mirroring telefonu z podtypami).

## Decyzja

1. Poprawka fetchera (`sublist`), bramka: 0 pustych wartości na 3 losowych specid.
2. Mapa `data/autohome-catalog-map.php` 218 → 314 wpisów. Trzy nowe tryby obok istniejących:
   - `'nazwa' => 'klucz=wartość'` — wartość stała (np. `远程启动功能`=1 → `标配`),
   - `'nazwa' => ['podnazwa' => 'klucz', …]` — złożone: obecność podnazwy w wartości zapala klucz,
   - `'nazwa@grupa' => …` — gdy ta sama nazwa CN żyje w dwóch grupach z inną semantyką
     (`最大功率(kW)` w 基本参数 to moc układu, w 发动机 moc silnika; `车身结构` podobnie).
   Klucze docelowe = klucze dongchedi. Nowe (39) tylko tam, gdzie dongchedi nie ma
   odpowiednika, dopisane do `translations-extra-prep.php` z etykietą, kategorią i tłumaczeniem
   wartości (bez kategorii translator ukrywa wiersz; z CJK w wartości też).
3. Backfill jednorazowy: wszystkie oferty che168 ze `spec_id`, w tym ręczne, z pominięciem
   stempli z poprzednich biegów (`manual force apply`).
4. Cron `55 4 * * *` jako trzecie ogniwo po bliźniaku i banku, bez `force` (tylko oferty bez
   stempla `_asiaauto_spec_catalog_at`), limit 100/dobę, przez `~/bin/cron-install`.
   Kolejność celowa: bliźniak i bank są darmowe, katalog kosztuje request. Cache po specid
   sprawia, że nowa oferta w znanej wersji nie pobiera nic.

## Pomiar zamykający

Backfill: 2 179 ofert (publish + draft), 737 pobrań, 1 442 z cache, 0 błędów, +290 230 pól,
średnio +133 na ofertę, 1 oferta bez zmian (Lotus Eletre, specid 71755). Tożsamość wersji
zweryfikowana na 50 ofertach: 11 pól z auto-api (rozstaw, wymiary, miejsca, masa, bateria,
moc, cena katalogowa, premiera) zgodnych z katalogiem w 100%, nazwa wersji che168 zawarta
w nazwie katalogowej we wszystkich 38 ofertach, które ją mają.

| pole | przed (3 002 publish) | po (2 988 publish) |
|---|---:|---:|
| `front_seat_massage_1` | 20,0% | 57,3% |
| `laser_radar` | 17,6% | 45,7% |
| `ar_hud` | 11,4% | 25,0% |
| `panoramic_camera` | 46,1% | 92,5% |
| `speaker` | 35,5% | 95,8% |
| `sound_brand` | 12,5% | 33,4% |
| którakolwiek z 7 flag wyposażenia | 51,6% | **98,2%** |

Che168 publish: 1 906 z 1 921 z flagami (99,2%). Mediana pól `extra_prep`: 283.
Nienaruszalność: na wszystkich 2 179 ofertach każdy klucz obecny przed biegiem ma po biegu
tę samą wartość (po naprawie `\n`), licznik dołożonych pól zgodny z logiem.

## Konsekwencje

- Pokrycie przestaje być blokerem wyszukiwarki zaawansowanej. Blokerem są **wartości**:
  9 typów rozjazdu (styl `标配` vs nazwa funkcji, opcja vs standard, pary przód/tył, liczby
  z jednostką, pary „N个 / M个”, synonimy CN, ten sam klucz z inną semantyką per źródło,
  ten sam sens w kilku kluczach, resztki uszkodzonego unicode). Reguły i pełna lista
  pól-kandydatów: `docs/roadmapa/T-116-etap2-pola-i-wartosci-wyszukiwarki.md`.
- Każdy zapis `_asiaauto_extra_prep` z `update_post_meta` **musi** iść przez `wp_slash`.
- `air_supply` jest dwuznaczne (che168: doładowanie, dongchedi: rozrząd); moje mapowanie
  `配气机构 → air_supply` z 02.09 to utrwala. Doładowanie brać z `gas_form`.
- Oferty dongchedi bez `spec_id` (ok. 140 ofert <100 pól) są poza tym mechanizmem; istnieje
  ścieżka ręczna nazwa CN → specid w 3 requestach, automat wymagałby dopasowywania nazw.

## Pliki

- repo: `scripts/autohome-catalog-fetch.js`, `scripts/autohome-catalog-merge.php`
  (tryby mapy, `wp_slash`, `verbose`), `scripts/backfill-spec-autohome.php` (bez zmian,
  orkiestracja), `docs/roadmapa/T-116-etap2-*.md`
- serwer: `data/autohome-catalog-map.php`, `data/translations-extra-prep.php`,
  `asiaauto-sync.php` (0.34.31)
- backupy: `~/backups/primaauto/2026-09-02/` (dump postmeta `extra_prep` sprzed biegu, stary
  cache, mapa i słownik sprzed zmian, snapshot 140 ofert sprzed naprawy `\n`)
- logi: `~/.claude/backfill-spec-autohome-2026-09-02.log` (bieg), `~/.claude/backfill-spec-autohome.log` (cron)
