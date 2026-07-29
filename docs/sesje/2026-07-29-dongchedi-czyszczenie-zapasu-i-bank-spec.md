# 2026-07-29 — dongchedi: sync ON, czyszczenie martwego zapasu, bank specyfikacji

Punkt wyjścia: „dostałem info, że feed dongchedi ożył — sprawdź, czy faktycznie".

## Co się okazało

**Feed żyje, blokada była u nas.** Strumień `/changes` rośnie ~29 tys. zdarzeń/dobę (che168 ~24,6 tys.),
a `getOffer` odpowiada. Nie wpadało nic, bo `asiaauto_sync_enabled_dongchedi=0` — wyłączone w lipcu
przez regresję `extra_prep` i zostawione tak przy uruchamianiu che168 22.07.

**Ponad połowa zapasu nie istniała u źródła.** Przemiał 2922 ofert po `inner_id`: żywe 1386 (47,4%),
usunięte 1530, wydmuszki 6 → **martwe 1536 = 52,6%**. Kontrola 404: świeża oferta daje 200, zmyślony
`inner_id` daje ten sam komunikat co „nasze martwe", powtarzalnie. Śmiertelność rośnie z wiekiem
(8–14 dni 16% → ponad 2 mies. 71%). Ceny na żywych prawie nietknięte — mediana 0,0%, min −23,5%.

**Nowe oferty wchodzą ze szczątkową specyfikacją.** `extra_prep`: dongchedi sprzed 01.07 mediana **342**,
che168 **82**, dongchedi od 29.07 — **43**. Zostają dane podstawowe, znika cała lista wyposażenia
(388 pól: GPS, kamery, poduszki, ADAS, komfort). To nie nasz kod ani globalne obcięcie — dla starych
rekordów API **nadal** oddaje komplet (383/331/349/373/326, co do jednego tyle, ile mamy w bazie).
Reklamacji u auto-api świadomie **nie składamy** — nie mamy od nich sygnału, że cokolwiek naprawili
(decyzja Janka).

## Co zrobione

1. **Sync dongchedi włączony**, kursor `4521452` → `4711675` (start od dziś, tydzień zaległości pominięty).
   Harmonogramów WP nie ruszano — `asiaauto_sync_changes` już biegał co 15 min i pętli po źródłach.
   Pierwsze ~2 h: 80 nowych ofert.
2. **Przemiał** (`scripts/przemiel-zapas-dongchedi.php`) — werdykt w meta `_asiaauto_source_check`,
   dzięki czemu gaszenie nie płaci drugi raz za API. Werdykt `blad` celowo nie jest stemplowany.
3. **Bank specyfikacji** (`scripts/zbuduj-bank-specyfikacji.php`) — 933 warianty
   `marka|seria|wersja|rocznik`, **319 sierot** bez żywego nosiciela, 14,1 MB w
   `uploads/asiaauto/spec-bank/`. Odcina los danych od losu ogłoszenia: oferty mogą gasnąć,
   bo wiedza jest już poza nimi.
4. **Gaszenie** (`scripts/gasz-martwe-oferty.php`) — pierwsza porcja 200 wygaszona, cron 04:25 na 200/dobę.
   Zostało 1271, czyli ~7 dni.
5. **Dolewanie z banku** (`scripts/dolej-spec-z-banku.php`) — 64 oferty uzupełnione, średnio +342 pola
   (42 → ~380). Cron 04:45 dla nowo wchodzących. Oryginał w `_asiaauto_extra_prep_orig`.

## Decyzje i pułapki

- **Ochronę zamówień trzeba liczyć jawnie.** `markRemoved()` broni wyłącznie `isReserved()`;
  `listingsWithActiveOrders()` działa dopiero przy trwałym kasowaniu z kosza. Skrypt gaszenia wyklucza
  trzy warstwy sam. Kontrola po biegu: 12 martwych z zamówieniem i 9 z rezerwacją **zostało na publish**.
- **404 nie grozi.** Wygaszona oferta daje 301 na hub modelu (`class-asiaauto-redirects.php` v0.32.52),
  fallback hub marki, dopiero bez celu 410 Gone z noindex. Sprawdzone na produkcji po wygaszeniu.
- **Rocznik siedzi pod meta `ca-year`**, nie `_asiaauto_ca_year` (taki klucz nie istnieje). Pierwsza wersja
  banku przez to scalała roczniki — przebudowana, 933 warianty zamiast 885.
- **Nie oceniaj feedu po samej obecności `extra_prep`.** „40 grup" z `getOffer` zostało w trakcie sesji
  błędnie odczytane jako koniec lipcowej regresji; to jej objaw. Porównuj z poziomem ~342.
- **`/usr/local/php74/bin/php` ma odebrane uprawnienia** — cron z nim padałby co noc bez śladu.
  W cronach używać `/usr/local/bin/php` (8.3).
- Crontab modyfikować wyłącznie przez `~/bin/cron-install` (guard blokuje `crontab <plik>`).

## Backupy

- `~/backups/primaauto/2026-07-29/posts-postmeta-przed-czyszczeniem-dongchedi.sql.gz`
- `~/backups/primaauto/2026-07-29/spec-bank-933-warianty.tar.gz`
- `~/backups/crontab/crontab-2026-07-29-*.bak`

## Następny ruch

Cron gaszenia domknie zapas w ~7 dni. Do obserwacji: czy dongchedi utrzyma napływ (pierwszego dnia
~900/dobę w tempie godzinowym) i czy 84 ubogie oferty bez dawcy w banku (Lynk & Co 03, Volvo EM90,
Zeekr 007 GT) dadzą się uzupełnić z katalogu Autohome przez `scripts/autohome-catalog-merge.php`.
