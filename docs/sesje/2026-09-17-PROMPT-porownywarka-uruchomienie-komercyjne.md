# PROMPT — T-115 porównywarka: uruchomienie komercyjne (nawigacja + przycisk „Porównaj”)

> Wklej jako pierwszą wiadomość nowego wątku w `~/projekty/primaauto`.
> Stan wyjściowy: wtyczka **0.42.2** (17.09.2026), porównywarka działa na `/porownywarka/`, `noindex`, **nigdzie niepodlinkowana**.

## Kontekst — przeczytaj najpierw

- `docs/roadmapa/T-115-porownywarka.md` — wszystkie decyzje (wersje zamiast ofert, wzór cpubenchmark, max 3, noindex, nagłówki od „Porównanie”, historia porównań, plan indeksowania).
- `docs/VERSIONS.md` — wpisy **0.42.0, 0.42.1, 0.42.2** (co jest wdrożone i jak testowane).
- Memory: `project_t115_porownywarka_decyzje_2026_09_16.md`.
- Kod: `includes/class-asiaauto-compare.php` (strona, REST `asiaauto/v1/versions`, historia), `includes/class-asiaauto-versions-table.php` (tabela wersji, cron 05:15), `assets/js/asiaauto-compare.js` (schowek w localStorage pod kluczem **`aa_porownanie`**), `assets/css/asiaauto-compare.css`, `templates/page-porownywarka.php`.

## Cel wątku

Porównywarka przestaje być ukryta: wchodzi do nawigacji, na stronę główną i do kart ofert.
**Kolejność pracy: najpierw rozkmina działania przycisku „Porównaj” (quiz do Janka), potem makiety przed/po, dopiero potem wdrożenie.**
Nagłówek, menu i strona główna to szablony widoczne na całym serwisie — **zmiany dopiero po OK Janka na makiecie** (memory: `feedback_no_edit_homepage_without_ok.md`, `feedback_mockupy_przed_po.md`).

## Zakres (decyzje Janka z 17.09)

### 1. Pasek „Nowość” nad nagłówkiem
- Dziś: `themes/primaauto2026/header.php` ~l.27–45 — „Nowość · Sprawdź **Wyszukiwanie zaawansowane**”, ukryty na `is_page(459262)`, zamknięcie pamiętane 30 dni pod kluczem `paNews-2026-09-wyszukiwarka`.
- Zmiana: treść na **nowość: porównywarka samochodów z Chin** (dokładne brzmienie zaproponuj w makiecie, np. „Nowość · Porównaj auta z Chin — **Porównywarka samochodów**”), link `/porownywarka/`.
- **Nowy klucz localStorage** (np. `paNews-2026-09-porownywarka`) — inaczej osoby, które zamknęły poprzedni pasek, nie zobaczą nowego.
- Warunek ukrycia: strona porównywarki (ID **481993**, także podstrony `/porownywarka/a-vs-b/`), a nie wyszukiwarki.

### 2. Strona główna — link pod wyszukiwarką
- Dziś: `includes/class-asiaauto-homepage.php` ~l.344 — `Szukaj po wyposażeniu →` do `/wyszukiwarka/`.
- Zmiana: **„Wyszukiwanie zaawansowane | Porównywarka”** — dwa linki (`/wyszukiwarka/`, `/porownywarka/`).

### 3. Menu główne (desktop i telefon)
- Menu WP „Header” (term 6033, lokalizacja `menu-1`). Pozycje: Samochody z Chin · Dostępne od ręki · W drodze · **Marki (db_id 265786)** · Klienci · Informacje ▾ · Wiedza ▾ (Aktualności, Słownik, Rankingi) · Kontakt.
- **„Marki” przenieść na górę działu „Wiedza”** (dziecko 389095, pierwsza pozycja przed Aktualnościami) — na desktopie i telefonie (to samo menu WP).
- **Desktop:** po prawej stronie nagłówka przyklejone nowe pozycje **„Wyszukiwarka”** (→ `/wyszukiwarka/`) i **„Porównywarka”** (→ `/porownywarka/`); reszta menu przesuwa się w lewo. Nagłówek: `header.php` ~l.65–80 (`pa-header__nav` + `pa-header__contact` z pigułką telefon/WhatsApp). Sprawdź, czy pozycje zmieszczą się przy 1200–1366 px — dziś menu już się łamie w dwie linie przy ~1040 px (zrzut z 17.09). Propozycja w makiecie: ikonka + krótka etykieta, licznik aut w porównaniu przy „Porównywarka” (jak „VS 2” / badge na cpubenchmark).
- **Telefon:** menu mobilne `header.php` ~l.135–150 dokleja już „Wyszukiwarka zaawansowana” przez `items_wrap` — dołożyć **„Porównywarka”** obok.

### 4. Link z wyszukiwarki zaawansowanej do porównywarki
Zatwierdzony razem z uruchomieniem (wcześniej wstrzymany): przy wynikach `/wyszukiwarka/` np. „Porównaj wersje modeli →”. Porównywarka ma już link w drugą stronę.

### 5. Przycisk „Porównaj” — listing (karty) i karta produktu — **NAJPIERW ROZKMINA**
Karta w katalogu, wyszukiwarce i shortcode'ach to jeden komponent: `AsiaAuto_Inventory::renderCard()` (`includes/class-asiaauto-inventory.php` ~l.973, akcje ~l.1086). Karta produktu: `includes/class-asiaauto-single.php` (CTA desktop ~l.487, dolny pasek mobilny `aa-mcta` ~l.654).

**Propozycja do przegadania (Claude, 17.09) — każdy punkt potwierdź quizem, pytania pojedynczo:**

1. **Co dodaje przycisk:** wersję oferty (`_asiaauto_spec_id` → wiersz `wp7j_asiaauto_versions`), nie egzemplarz — do **tego samego schowka `aa_porownanie`**, z którego korzysta porównywarka. Jeden schowek w całym serwisie.
2. **Wygląd:** ikona wagi/szalki (⚖, intuicyjna, używana przez porównywarki) + tekst „Porównaj” na desktopie, sama ikona z `aria-label` na telefonie; po dodaniu stan **„W porównaniu ✓”** (klik ponownie = usuń). Na karcie produktu: obok ceny/wersji, **nie** w dolnym pasku mobilnym (Zadzwoń / WhatsApp / Zamów — tam walczymy o kontakt).
3. **Informacja zwrotna:** po pierwszym dodaniu przyklejony **pasek schowka** na dole ekranu: „Porównanie: 2/3 — [Porównaj →] [wyczyść]” (jak „Compare List” na cpubenchmark) + licznik przy „Porównywarka” w nagłówku.
4. **Pełny schowek (3) i klik „Porównaj”:** zamiast cichej odmowy — okienko „Porównanie ma już 3 auta. Zamień jedno:” z trzema przyciskami „zamień A / B / C” (albo „anuluj”).
5. **Dwie oferty tej samej wersji:** obie pokazują „W porównaniu ✓” (ta sama wersja), podpowiedź „Ta wersja jest już w porównaniu”.
6. **Oferty bez `specid` (~23%, 703 z 3 051):** **brak przycisku** — nie pokazujemy martwego przycisku (memory `feedback_nie_wdrazaj_polowy_funkcji.md`).
7. **Nowa oferta nowej wersji:** tabela wersji buduje się nocą (05:15), więc dziś dodana oferta nie ma wiersza → do rozważenia dobudowa wiersza przy imporcie (hook `asiaauto_after_set_taxonomies`, jak `AsiaAuto_Specs_Table`), inaczej przycisk pojawi się dopiero następnego dnia.
8. **Otwarte porównanie w innej karcie:** schowek jest w localStorage, więc zdarzenie `storage` aktualizuje przyciski i pasek we wszystkich kartach na żywo. Strona porównania pokazuje auta **z adresu**; jeśli schowek różni się od adresu, pokazać link „Masz w schowku inne auta — porównaj je →”.
9. **Niezalogowani:** tylko localStorage (per urządzenie), bez logowania — jak dziś.
10. **Zalogowani (później, po T-114/T-204):** synchronizacja schowka do `user_meta` przez REST przy każdej zmianie + scalenie przy logowaniu (suma, max 3, najnowsze pierwsze) → ten sam schowek na telefonie i komputerze. Do decyzji: w tym wątku czy osobno.
11. **Pomiar:** historia porównań rozpoznaje dziś źródło z Referer (`start/dodaj/usun/lista/serwis/…`) — dodać **`oferta`** (wejście z karty produktu) i **`listing`** (katalog, wyszukiwarka). Klik „Porównaj” nie zmienia adresu, więc historia go nie widzi — do decyzji, czy dodać zdarzenie GA4 `porownanie_dodaj` (17.09 ustalono: bez nowych zdarzeń GA4; wrócić tylko, jeśli będzie potrzebne).

## Zasady wykonania

- Makiety przed/po (desktop 1366 i telefon 390) na auratest przed każdą zmianą szablonu; wdrożenie po OK.
- Backup `.bak-YYYY-MM-DD` przed edycją, `php -l` przed zapisem, podbicie wersji Pythonem (nie `sed`), `docs/VERSIONS.md`, commit + push.
- Porównywarka **zostaje `noindex`** — linki z nawigacji tego nie zmieniają (decyzja o indeksie: po analizie historii ~1000 porównań, T-115 pkt 6).
- **Testy w przeglądarce:** desktop i ramka 390 px. Pułapki z 17.09: strona ma `scroll-behavior: smooth` — w testach `scrollTo({behavior:'instant'})`, inaczej `elementFromPoint` zwraca null; **zalogowany redaktor nie trafia do historii porównań** — do sprawdzania historii/listy ostatnich używać curla ze zwykłym UA albo incognito, a testowe wpisy usuwać.
- Scenariusz odbioru: z karty w katalogu dodaj 2 wersje → pasek schowka → Porównaj; z karty produktu dodaj 3. → 4. przy pełnym schowku (zamiana); usuń z paska; to samo na telefonie; dwie karty przeglądarki jednocześnie (zdarzenie `storage`); oferta bez `specid` bez przycisku.
