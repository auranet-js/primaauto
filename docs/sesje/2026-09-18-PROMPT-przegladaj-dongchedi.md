# PROMPT — „Przeglądaj Dongchedi" (przeglądarka ofert z filtrami)

> Zadanie do wykonania w osobnej sesji, w repo `~/projekty/primaauto`.
> Stan wejściowy ustalony pomiarami 18.09.2026 — liczby niżej są zweryfikowane, nie szacowane.

## Cel

Zbudować stronę **„Przeglądaj Dongchedi"** — przeglądarkę ofert giełdy z filtrami
(marka / model / rocznik / cena / miasta), siatką wyników i importem jednym kliknięciem.
Ma działać i wyglądać jak istniejąca przeglądarka Che168:

`https://primaauto.com.pl/wp-admin/edit.php?post_type=listings&page=asiaauto-che168-browse`

## Stan zastany — co JEST, a czego brakuje

| | che168 | dongchedi |
|---|---|---|
| import pojedynczej oferty (wklej URL / inner_id) | „Dodaj z Che168" — `class-asiaauto-admin-che168-import.php` | **JEST** — „Dodaj z Dongchedi", `class-asiaauto-admin-manual-import.php` (slug `asiaauto-manual-import`) |
| przeglądarka z filtrami | „Przeglądaj Che168" — `class-asiaauto-admin-che168-browse.php` (377 linii) | **BRAK — to jest do zrobienia** |
| słownik marka→modele | `AsiaAuto_Che168_Dictionary` (z `getFilters`, transient 7 dni) | **BRAK — do zrobienia** |

Uwaga na nazewnictwo: „Dodaj z Dongchedi" **już istnieje** i działa (wklej URL → podgląd
z breakdownem ceny → import). Brakującym elementem jest przeglądarka, stąd nazwa
**„Przeglądaj Dongchedi"**, spójna z „Przeglądaj Che168".

## Zweryfikowane fakty API (pomiar 18.09, nie zakładaj inaczej)

```
getFilters("dongchedi")                              → OK, 272 marki   (che168: 284)
getOffers("dongchedi", [mark=>BYD, year_from=>2024]) → 20 ofert, meta.next_page = 2
```

API zachowuje się identycznie dla obu źródeł — ta sama paginacja, te same parametry
(`page`, `mark`, `model`, `year_from`, `year_to`). **Jeden model per zapytanie**, dlatego
wzorzec ogranicza liczbę chipów modeli (`MAX_MODELS = 5`) — zachowaj ten limit.

## Wzorzec do naśladowania

`wp-content/plugins/asiaauto-sync/includes/class-asiaauto-admin-che168-browse.php`

Kluczowe fragmenty:
- `addMenuPage()` — submenu pod `edit.php?post_type=listings`, cap `AsiaAuto_Security::IMPORT_CAP`
- `ajaxBrowse()` — nonce `check_ajax_referer`, sanityzacja, `wp_send_json_*`
- `browseOffers(array $a)` — **publiczna**, testowalna z `wp eval` bez kontekstu AJAX (zachowaj tę własność)
- `renderPage()` — formularz + JS + siatka kart

Klasa jest **czystym konsumentem**: `AsiaAuto_API` + słownik + istniejące AJAX-y importu.
Nie duplikuje logiki importu ani pipeline'u cenowego.

## Różnice dongchedi, których nie wolno przeoczyć

1. **Słownik trzeba dopisać.** `AsiaAuto_Che168_Dictionary` jest zahardkodowany pod jedno źródło
   (nazwy transientów `asiaauto_che168_filters_dict` / `_dict_canon`). Dwie drogi: sparametryzować
   istniejącą klasę źródłem albo dorobić bliźniaczą. **Preferowana: parametryzacja** — logika jest
   identyczna, różni się wyłącznie kluczem cache i argumentem `getFilters($source)`.
   Jeśli parametryzujesz, zachowaj wsteczną zgodność wywołań `AsiaAuto_Che168_Dictionary::get()`.

2. **`canonicalKeyForSource(..., 'che168')` → `'dongchedi'`.** Wywołanie w `browseOffers()`
   przyjmuje źródło jako czwarty argument. Adapter nazw jest per źródło — podmiana stałej to
   nie kosmetyka, bez niej mapowanie marka/model trafi w próżnię.

3. **Config czytany z właściwej gałęzi.** Wzorzec czyta `$config['che168']['city_filter_cities']`
   i `['model_blacklist']`. Dla nowej strony: `$config['dongchedi'][...]`.
   Stan na 18.09: obie gałęzie są identyczne (68 marek, blacklista 3 marki / 46 modeli,
   rocznik ≥2024, km ≤40 000, cena ≥85 000 ¥, 31 miast) — ale czytaj swoją gałąź, nie cudzą.

4. **Dongchedi nie ma `spec_id`** (0 z 903 ofert; che168 ma 1288/1306). Katalog Autohome jest
   po nim kluczowany, więc dla ofert z dongchedi **nie zadziała**. Świeżo zaimportowana oferta
   wchodzi z ~40 polami `extra_prep` (regresja źródła, potwierdzona 18.09: API oddaje 42–43 pola
   wobec ~342 sprzed lipca). Uzupełnia ją nocny cykl przez bliźniaka i bank specyfikacji.
   **W UI warto to pokazać** — kolumna/znacznik „specyfikacja: z bliźniaka / brak dawcy",
   żeby operator wiedział, że importuje sztukę, która może zostać chuda.

5. **Gate dostępu.** Przeglądarka Che168 siedzi za `ASIAAUTO_CHE168_PREVIEW` (lista loginów
   w `wp-config.php`) — relikt fazy testów, Ruslan jej nie widzi. „Dodaj z Dongchedi" gate'a
   nie ma. Decyzja do podjęcia z Jankiem: czy nowa strona ma być od razu dostępna dla Ruslana
   (wtedy sam cap `IMPORT_CAP`), czy najpierw za gate'em.

6. **Import per klik.** Wzorzec Che168 woła istniejące AJAX-y `asiaauto_che168_preview` /
   `asiaauto_che168_import`. Dla dongchedi odpowiednikami są `asiaauto_preview_offer` /
   `asiaauto_manual_import` (`class-asiaauto-admin-manual-import.php:23-24`).
   **Użyj ich, nie pisz nowych** — te ustawiają `_asiaauto_manual_import` + `_by` + `_at`,
   od których zależy widok „Ręczny import (X)" (`class-asiaauto-admin-listings-views.php`).

## Czego NIE dotykać

- `class-asiaauto-price.php`, `class-asiaauto-importer.php`, `class-asiaauto-sync.php`,
  `class-asiaauto-media.php`, `class-asiaauto-order.php` — strefy kruche (`CLAUDE.md` §3).
- Trybów synca i filtrów w `asiaauto_import_config` — przeglądarka jest **tylko do odczytu**
  konfiguracji; import ręczny świadomie omija filtry, i tak ma zostać.
- `class-asiaauto-admin-che168-browse.php` — jeśli parametryzujesz słownik, zmiana ma być
  addytywna i nie może zepsuć istniejącej strony Che168.

## Kryteria „zrobione"

1. Strona widoczna pod `edit.php?post_type=listings&page=asiaauto-dongchedi-browse`.
2. Dropdown marek wypełniony ze słownika (≈272 pozycje), chipy modeli po wyborze marki.
3. Filtry rocznik / cena minimalna / „tylko nasze miasta" działają, paginacja („Pokaż więcej”).
4. Karta oferty: miniatura, marka+model, rocznik, cena CNY, przebieg, paliwo, miasto po polsku,
   znaczniki 🆕 (model bez huba) i ⛔ (na blackliście), informacja „już w bazie" dla znanych `inner_id`.
5. Klik „Importuj" tworzy ogłoszenie tą samą ścieżką co „Dodaj z Dongchedi"
   (sprawdź: nowa oferta ma `_asiaauto_manual_import = 1` i pojawia się w widoku „Ręczny import").
6. `php -l` czysty, `browseOffers()` wywoływalne z `wp eval` bez AJAX.
7. Strona Che168 działa bez zmian po całej operacji.

## Pułapki z historii projektu

- **Nie kopiuj list marek między źródłami.** Nazwy marek są w konwencji źródła; przy podmianie
  30.07 skasowano BAW, Lynk & Co i Volvo, a 16.08 wycofano sumę list tego samego dnia.
- **Guard mapowania działa na obu kanałach** od 18.08 (`class-asiaauto-sync.php:388-401`) —
  ale dotyczy **synca**, nie importu ręcznego. Import ręczny świadomie wchodzi mimo braku huba,
  dlatego w UI ostrzegaj znacznikiem 🆕 zamiast blokować.
- **Log pluginu i baza chodzą w UTC** (`gmt_offset = 0`), a serwer w CEST. Przy każdym pomiarze
  czasowym przelicz, zanim wyciągniesz wniosek.

## Kontekst decyzyjny (dlaczego to w ogóle powstaje)

Dongchedi wrócił 18.09 na tryb „tylko aktualizacja" po pomiarze: przy pełnym imporcie
**92% ofert to modele i roczniki, które już mamy z che168** (178 z 193 w oknie 19–24.08),
a unikalnych modeli było 6% (12 sztuk: Tank 400 Hi4-T, Luxeed R7, Chery Tiggo 8 Pro,
Chery Fulwin X3 PLUS i in.). Zamiast wpuszczać cały strumień, te pojedyncze sztuki mają
być brane ręcznie — i do tego właśnie potrzebna jest wygodna przeglądarka.
