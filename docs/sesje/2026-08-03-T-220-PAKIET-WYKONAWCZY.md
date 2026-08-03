# T-220 — Aneks VIN: pakiet wykonawczy (samowystarczalny)

> Data: 2026-08-03 · Spec bazowy: `docs/roadmapa/T-220-aneks-vin.md` (decyzje z 27.07)
> Stan wejściowy: plugin **0.34.16**, T-217 (drugi wzorzec umowy) LIVE od 30.07
> Rozmiar: ~4–6 h AI · Strefy kruche dotknięte: **żadna z twardych** (patrz §2)
>
> Ten dokument zastępuje spec tam, gdzie się z nim rozjeżdża. Rozjazdy są nazwane w §5.

---

## 0. Prompt startowy (do wklejenia w nowym wątku)

```
Wdrażasz T-220 (aneks VIN do umowy) w projekcie primaauto.
Przeczytaj docs/sesje/2026-08-03-T-220-PAKIET-WYKONAWCZY.md i wykonaj §6 w kolejności.
Decyzje z §5 są zamknięte — nie otwieraj ich ponownie.
Source of truth = serwer (~/domains/primaauto.com.pl/public_html/...), repo jest kontekstowe.
Przed każdą edycją: .bak z datą + php -l. Po wdrożeniu: testy z §7, bump wersji, VERSIONS.md.
Nie ruszasz: TRANSITIONS, LISTING_RESERVATION_MAP, generate(), kreatora klienta,
bloku ręcznego wgrywania umowy.
```

---

## 1. Co robimy

Umowa bywa podpisywana, zanim znany jest numer VIN. Aneks uzupełnia ten jeden numer, odnosząc się
do konkretnej, już istniejącej umowy.

Trzy elementy:

1. **Generator aneksu** — osobna metoda obok generatora umowy, PDF w tej samej szacie.
2. **Przycisk „Generuj aneks VIN"** w karcie zamówienia, za guardem (jest umowa + jest pełny VIN).
3. **Znacznik „Aneks VIN"** (`brak / wygenerowany / wysłany`) przełączany ręcznie; przejście na
   „wysłany" wysyła klientowi maila z aneksem w załączniku.

**Dlaczego to nie jest funkcja marginalna — pomiar 2026-08-03:**

| Źródło ofert | Ofert publish | Bez VIN | Pełny VIN (17 zn.) | Ucięty |
|---|---|---|---|---|
| dongchedi | 1489 | **1477 (99,2 %)** | 11 | 1 |
| che168 | 585 | 98 | **476 (81 %)** | 11 |
| bez źródła (ręczne/duplikaty) | 104 | 79 | 25 | 0 |

Oferty z dongchedi praktycznie nigdy nie mają VIN-u w ogłoszeniu — Ruslan dopisuje go ręcznie po
weryfikacji auta. To jest ścieżka aneksu. Che168 zwykle podaje VIN w ofercie i tam aneks nie będzie
potrzebny.

Stan zamówień (155 sztuk, bez kosza): **31 z wpisanym VIN-em**, **30 z wygenerowaną umową**
(w tym **6 wgranych ręcznie**), **0 z ustawionym `_order_contract_type`** — wzorzec leasingowy
nie był jeszcze użyty produkcyjnie.

---

## 2. Twarde ograniczenia (Janek, 2026-08-03)

### 2.1 Generowanie aneksu NIE wysyła żadnych maili

Kliknięcie „Generuj aneks VIN" tworzy wyłącznie dokument. Regeneracja — również cicha.
Mail wychodzi **tylko** przy świadomym przełączeniu znacznika na „wysłany", z potwierdzeniem
w oknie dialogowym. To jest wymóg nadrzędny — jeśli implementacja go łamie, jest błędna.

### 2.2 Statusy zamówienia nietknięte

`STATUSES`, `TRANSITIONS`, `LISTING_RESERVATION_MAP` — zero zmian. Aneks żyje w osobnym polu obok
statusu. Powód: zamówienie ma zachować swój etap (auto „w drodze" nie może zniknąć z filtra),
a kreator klienta ma liczyć krok jak dotąd.

### 2.3 `generate()` i ręczne wgrywanie umowy nietknięte

Generator aneksu to metoda **obok**. Blok `renderUploadCustomContractToggle()` …
`handleUploadCustomContract()` — nie dotykamy ani linii (wymóg z T-217 §2.2 obowiązuje dalej).

### 2.4 Kreator klienta nietknięty

Aneks dociera do klienta mailem, z PDF-em w załączniku. Do kreatora nie wchodzimy.
Powód techniczny: `resolveStep()` mapuje `zarezerwowane|zakupione|w_drodze|na_placu` na **krok 5**,
a panel kroku 4 (gdzie siedzi „Pobierz umowę") dostaje wtedy `display:none`. Aneks powstaje właśnie
na tych statusach, więc kafel w panelu 4 byłby niewidoczny. Dokładanie sekcji dokumentów do panelu 5
to osobna robota — patrz §9.

---

## 3. Stan faktyczny (zweryfikowany na produkcji 2026-08-03)

### 3.1 Gdzie w umowach siedzi VIN

| Wzorzec | Jednostka redakcyjna | Plik:linia | Co drukuje przy pustym VIN |
|---|---|---|---|
| Pośrednictwo | `§1 ust. 1 pkt 1 lit. c` | `class-asiaauto-contract.php:688` | **`—`** |
| Pośrednictwo | wiersz „VIN" w Zał. nr 1 | `:1065` | **`—`** |
| Leasing | `§2 lit. e` (lista a–f: marka, paliwo, kolor, rok, **VIN**, stan) | `:1442-1454` | „zostanie dodany aneksem do umowy po weryfikacji auta" |
| Leasing | wiersz „VIN" w Zał. nr 1 | `:1650-1652` | j.w. |

Klauzula o aneksie istnieje **wyłącznie we wzorcu leasingowym** — dołożył ją T-217, bo tak było
w podpisanym egzemplarzu #072426-1. Pośrednictwo drukuje myślnik.

### 3.2 Fallback VIN

`getVehicleData()` (`:345-355`): `_order_vin` → `vin_number` listingu → `'—'`.
`isValidVin()` (`:429`) — 17 znaków bez I/O/Q, wołane dziś tylko ze ścieżki leasingowej
(klucz `vin_verified`).

### 3.3 Czego NIE MA w bazie

**Daty umowy.** `collectData()` ustawia `'date' => date('d.m.Y')` w momencie renderu i nigdzie jej
nie zapisuje. Nie istnieje meta z datą umowy. Jedyny ślad to `post_date` załącznika PDF — dla umów
auto-generowanych równy dacie z dokumentu, dla 6 umów wgranych ręcznie równy dacie wgrania
(prawdziwa data umowy jest wtedy systemowi nieznana).

Aneks musi się odnieść do „umowy z dnia…", więc datę trzeba skądś wziąć — patrz §4 i §6 krok 2.

### 3.4 Zanieczyszczone VIN-y

Che168 podaje w 11 ofertach numer ucięty z wielokropkiem (`LC0EJ5...`, `L6T7...`, `LVYPDH5D...`).
Jeden taki numer **już wszedł do zamówienia**: #387790 ma w `_order_vin` wartość `L1NB`.
Dodatkowo #392721 ma `LNNBDDEH5SD108389` (17 znaków, poprawny). Stąd guard z §5 D3.

### 3.5 Wzorzec aneksu (Google Drive, `Aneks do umowy.docx`, Piskorz 04.11.2024)

Jedna strona, treść odtworzona co do znaku:

```
04.11.2024, Rzeszów

ANEKS
DO UMOWY ZLECENIA SPROWADZENIA SAMOCHODU Z CHIN

Strony, zgodnie postanawiają dokonać zmian w treści umowy z dnia 25.10.2024
(zwanej dalej Umową), zawartej pomiędzy

PRIMA-AUTO Ruslan Prima
35-117 Rzeszów, ul. Pleśniarowicza 2a/38  NIP: 8133898576, REGON: 525442846  tel. 783 807 381
zwany dalej „Zleceniobiorcą”, a:

imię i nazwisko/nazwa firmy: Piotr Piskorz
adres: 05-230 Kobyłka, ul. Załuskiego, 86
PESEL: 63030703611
zwany dalej „Zleceniodawcą”.

                                      §1

Niniejszym aneksem Strony zmieniają dane opisujące pojazd określony w §1, ust.1, pkt.1
Umowy podpunkt c na następujące:

c). VIN: LC0CD4C49RO572663

Zleceniodawca ……………………          Zleceniobiorca (PRIMA-AUTO Ruslan Prima) ……………………
```

Uwagi: wzorzec odwołuje się do umowy **przez datę, nie przez numer**; dane stron w układzie
identycznym jak w umowie; jeden paragraf; dwa podpisy.

### 3.6 Infrastruktura, którą reużywamy

| Element | Gdzie | Uwaga |
|---|---|---|
| Render PDF | `renderLeasingPDF()` `:1709` | wzorzec do skopiowania: mPDF A4, marginesy 22/22/20/25, stopka z numerem i `{PAGENO}/{nbpg}` |
| Załącznik WP | `createAttachment()` `:1857` | `wp_insert_attachment` z `parent = order_id`, katalog `uploads/contracts` |
| Router akcji | `handleActions()` `class-asiaauto-order-admin.php:115` | `_aa_order_action` + nonce `aa_order_action` + `AsiaAuto_Security::ORDER_CAP` |
| Karta dokumentów | `renderCardContract()` `:2026` | tu wchodzi nowy wiersz |
| Szablony maili | `AsiaAuto_Order_Content::defaultEmailTemplates()` | edytowalne w Ustawieniach → Szablony |
| Magic link | `AsiaAuto_Order::generateMagicLink()` | używany przez maile statusowe |
| Wyciszenie poczty w testach | filtr `pre_wp_mail` | wzorzec z T-217 §3a — **obowiązkowy w E2E** |

---

## 4. Nowe pola i klucze

| Klucz | Gdzie | Zawartość |
|---|---|---|
| `_order_annex_vin_id` | zamówienie | ID załącznika z aneksem |
| `_order_annex_vin_generated_gmt` | zamówienie | data wygenerowania |
| `_order_annex_vin_state` | zamówienie | `'' / brak` \| `wygenerowany` \| `wyslany` |
| `_order_annex_vin_sent_gmt` | zamówienie | data wysyłki maila |
| `_order_contract_date` | zamówienie | data umowy (`Y-m-d`), uzupełniana przy generowaniu aneksu |

Stałe w `AsiaAuto_Order` (obok istniejących `META_*`), nazewnictwo bez zmian konwencji.

**Mapa definicji pól aneksu** — w `AsiaAuto_Contract`, to jest oś rozszerzalności z §1 specu:

```php
private const ANNEX_FIELDS = [
    'vin' => [
        'label'  => 'VIN',
        'meta'   => AsiaAuto_Order::META_VIN,
        'unit'   => [
            AsiaAuto_Order::CONTRACT_TYPE_AGENCY  => ['ref' => '§1, ust.1, pkt.1 Umowy podpunkt c', 'letter' => 'c'],
            AsiaAuto_Order::CONTRACT_TYPE_LEASING => ['ref' => '§2 Umowy lit. e oraz Załącznik nr 1 do Umowy', 'letter' => 'e'],
        ],
    ],
];
```

Szablon iteruje po przekazanej liście pozycji i drukuje tyle punktów, ile dostał. Dziś wywołanie
zawsze podaje `['vin']`, a UI wystawia jeden przycisk. Dołożenie ceny czy terminu = wpis w tej mapie.

---

## 5. Decyzje rozstrzygnięte — nie otwieraj ich ponownie

| # | Decyzja | Źródło |
|---|---|---|
| D1 | **Jeden szablon aneksu dla obu wzorców.** Różni się wyłącznie tytułem („…UMOWY ZLECENIA SPROWADZENIA SAMOCHODU Z CHIN" vs „…UMOWY ORGANIZACJI IMPORTU SAMOCHODU") i odwołaniem do jednostki redakcyjnej | spec D1 |
| D2 | Przycisk **„Generuj aneks VIN"** w karcie „Umowa i dokumenty", pod wierszem „Umowa PDF" | spec D2 |
| D3 | **Guard: umowa główna istnieje ORAZ `isValidVin(_order_vin)` = prawda.** Nie „VIN niepusty" — w bazie leżą ucięte numery i jeden już trafił do zamówienia (§3.4). Brak spełnienia → przycisk nieaktywny + jedno zdanie dlaczego | Janek 03.08 |
| D4 | **Generowanie nie wysyła maili i nie zmienia statusu zamówienia** | Janek 03.08, §2.1 |
| D5 | **Znacznik jako osobne pole**, nie status. Select obok statusu, `wygenerowany` ustawiany automatycznie, `wysłany` ręcznie i tylko on wysyła maila | spec D4 |
| D6 | **Umowa bez VIN-u zapowiada aneks — także w pośrednictwie.** Zamiast `—` drukuje się „zostanie uzupełniony w formie aneksu do niniejszej Umowy" w obu miejscach (`§1 ust.1 pkt 1 lit. c` i Zał. nr 1). Uzasadnienie: 99,2 % ofert dongchedi nie ma VIN-u, więc myślnik jest regułą, nie wyjątkiem. Umowy z wpisanym VIN-em renderują się **bajt w bajt jak dziś** | Janek 03.08 (korekta wobec mojej wcześniejszej propozycji odłożenia) |
| D7 | **Aneks trafia do klienta mailem z PDF-em w załączniku.** Kreator nietknięty (§2.4) | Janek 03.08 |
| D8 | **Aneks nie dostaje własnej puli numerów.** Identyfikuje się przez umowę: „Aneks do umowy nr AA/2026/0029 z dnia 30.07.2026". Ponowne generowanie nadpisuje meta; poprzedni plik zostaje w bibliotece mediów | spec 4.1 |

---

## 6. Kolejność wdrożenia

Każdy krok kończy się `php -l` i `.bak-2026-08-03-T-220` przed nadpisaniem.

### Krok 1 — meta i stałe (`class-asiaauto-order.php`)

Dopisz 5 stałych `META_*` z §4 obok istniejących. Nic więcej w tym pliku — bez dotykania
`STATUSES`, `TRANSITIONS`, `LISTING_RESERVATION_MAP`, `changeStatus()`.

Dopisz też do `getOrderData()` (`:1626` okolica) klucze `annex_vin_id`, `annex_vin_state`,
`contract_date` — karta zamówienia i mail będą z nich czytać.

### Krok 2 — data umowy

Funkcja pomocnicza `resolveContractDate(int $order_id): string`:

1. `_order_contract_date` jeśli ustawione,
2. `post_date` załącznika `_order_contract_attachment_id`,
3. dzisiejsza data.

Wynik jest **prefillem pola tekstowego** przy przycisku aneksu — Ruslan może poprawić przed
wygenerowaniem (potrzebne dla 6 umów wgranych ręcznie). Wartość zapisuje się w `_order_contract_date`,
więc druga generacja podpowiada już to samo.

**Nie stemplujemy daty w `generate()`** — to strefa objęta zakazem z §2.3.

### Krok 3 — generator (`class-asiaauto-contract.php`)

Nowa metoda `generateAnnex(int $order_id, array $fields = ['vin']): int|\WP_Error` **na końcu klasy**,
obok istniejących. Wewnątrz:

- walidacja: umowa główna istnieje, `isValidVin()` przechodzi, `collectData()` nie zwraca błędu;
- `renderAnnexHTML($data, $items)` — treść wg §3.5, tytuł i odwołanie z mapy `ANNEX_FIELDS`
  wg `contract_type`;
- `renderAnnexPDF()` — klon `renderLeasingPDF()` bez załączników, stopka
  „Aneks do umowy nr {nr} | {firma} | str. {PAGENO}/{nbpg}”;
- `createAttachment()` z tytułem „Aneks do umowy {nr}” (metoda istniejąca, przyjmuje dowolny tytuł —
  jeśli ma zaszyty prefiks „Umowa ”, dorób wariant obok, **nie zmieniaj** istniejącej);
- zapis `_order_annex_vin_id`, `_order_annex_vin_generated_gmt`, `_order_annex_vin_state = wygenerowany`.

Nazwa pliku: `aneks-{numer-umowy-slug}-{token}.pdf` w `uploads/contracts`.

**Zero wywołań `wp_mail()` w tej metodzie.**

### Krok 4 — D6: klauzula w umowie pośrednictwa

Dwie linie: `:688` (lit. c) i `:1065` (wiersz Zał. nr 1). Wzorzec — jak w gałęzi leasingowej:
wartość `vin_verified` pusta → klauzula, niepusta → numer.

**Warunek regresji:** dla zamówień z poprawnym VIN-em PDF ma być identyczny co do znaku
z dzisiejszym. Sprawdza to skrypt z T-217 (`tmp/T-217-regresja-posrednictwo.php`, 5 zamówień,
`pdftotext` + `diff`) — odpal go **przed** i **po** tej zmianie.

Brzmienie klauzuli: **„zostanie uzupełniony w formie aneksu do niniejszej Umowy"**.
(We wzorcu leasingowym jest „zostanie dodany aneksem do umowy po weryfikacji auta" — tam zostaje
bez zmian, bo pochodzi z podpisanego egzemplarza.)

### Krok 5 — UI panelu (`class-asiaauto-order-admin.php`)

1. `handleActions()` — dwa nowe `case`: `generate_annex_vin`, `set_annex_vin_state`.
2. `handleGenerateAnnexVin()` — woła generator, komunikat sukcesu/błędu przez `add_settings_error`.
   **Bez maila.**
3. `handleSetAnnexVinState()` — zapisuje znacznik; gdy nowa wartość to `wyslany` **i** poprzednia
   nie była `wyslany` → wysyła mail (krok 6) i stempluje `_order_annex_vin_sent_gmt`.
4. `renderCardContract()` — nowy wiersz **„Aneks VIN"** pod „Umowa PDF”:
   - gdy aneks istnieje: `📄 Pobierz aneks ↗` + `🔄 Generuj ponownie` + select znacznika
     + `Zapisz` (z `confirm()` przy „wysłany”: „Klient dostanie maila z aneksem. Kontynuować?”);
   - gdy nie istnieje i guard przechodzi: pole „Data umowy” (prefill z kroku 2) + `📄 Generuj aneks VIN`;
   - gdy guard nie przechodzi: przycisk nieaktywny + `<small>` z powodem
     („brak wygenerowanej umowy” / „VIN musi mieć 17 znaków — obecnie: `L1NB`”).

### Krok 6 — mail (`class-asiaauto-order-content.php` + wysyłka)

Nowy szablon `annex_vin_sent` w `defaultEmailTemplates()`, w konwencji pozostałych
(zmienne `{customer_name}`, `{order_id}`, `{listing_title}`, `{contract_number}`, `{magic_link}`,
`{company_phone}`). Propozycja treści:

```
Temat: Prima-Auto — aneks do umowy (zamówienie #{order_id})

Cześć {customer_name},

do umowy nr {contract_number} przygotowaliśmy aneks uzupełniający numer VIN
sprowadzanego pojazdu ({listing_title}).

Aneks znajdziesz w załączniku. Wydrukuj, podpisz i odeślij tak samo jak umowę.

Panel zamówienia: {magic_link}

Pozdrawiamy,
Zespół Prima-Auto
{company_phone}
```

Wysyłka: `wp_mail($email, $subject, $body, [], [$sciezka_do_pdf])` — piąty argument to załącznik.
Respektuj istniejący gate `$config['customer_email_notifications']` (jak
`sendContractRegeneratedEmail()`).

### Krok 7 — domknięcie

Bump `ASIAAUTO_VERSION` 0.34.16 → **0.34.17**, wpis w `docs/VERSIONS.md`, log sesji
w `docs/sesje/`, aktualizacja `docs/QUEUE.md` (T-220 → DONE), commit.

---

## 7. Scenariusze testów

### Regresja — warunek wstępny, odpalasz PIERWSZE

| # | Scenariusz | Oczekiwane |
|---|---|---|
| R-1 | `tmp/T-217-regresja-posrednictwo.php` przed i po zmianach, 5 zamówień z VIN-em | `pdftotext` + `diff` bez różnic |
| R-2 | `tmp/T-217-testy-leasing.php` | 42/42 jak 30.07 |
| R-3 | Zmiana statusu na zamówieniu testowym przez pełne API | przejścia działają, `_asiaauto_reservation` bez zmian |
| R-4 | Ustawienie znacznika aneksu | `_order_status` i rezerwacja listingu nietknięte |

### Generator

| # | Scenariusz | Oczekiwane |
|---|---|---|
| G-1 | Aneks dla pośrednictwa | tytuł „…UMOWY ZLECENIA SPROWADZENIA SAMOCHODU Z CHIN”, odwołanie `§1, ust.1, pkt.1 Umowy podpunkt c`, punkt `c). VIN: …` |
| G-2 | Aneks dla leasingu | tytuł „…UMOWY ORGANIZACJI IMPORTU SAMOCHODU”, odwołanie `§2 Umowy lit. e oraz Załącznik nr 1`, punkt `e). VIN: …` |
| G-3 | Dane stron | zgodne z umową główną (firma z configu, klient z `billing_*`, PESEL albo NIP wg tego, co wypełnione) |
| G-4 | Odwołanie do umowy | numer + data z `resolveContractDate()`; ręczna korekta daty wygrywa |
| G-5 | Jedna strona, dwa podpisy, stopka Prima-Auto | wizualnie i w `pdftotext` |
| G-6 | Ponowna generacja | nowy plik, meta wskazuje nowy, stary załącznik zostaje w mediach |
| G-7 | Załącznik | `post_parent` = zamówienie, mime `application/pdf`, tytuł „Aneks do umowy {nr}” |

### Guard

| # | Scenariusz | Oczekiwane |
|---|---|---|
| GD-1 | VIN pusty | przycisk nieaktywny, generatora nie da się wywołać |
| GD-2 | VIN `L1NB` (4 zn.) | nieaktywny + powód z podaną wartością |
| GD-3 | VIN ucięty `LC0EJ5...` | nieaktywny |
| GD-4 | Brak umowy głównej, VIN poprawny | nieaktywny |
| GD-5 | Wywołanie akcji POST-em z pominięciem UI | `WP_Error`, brak pliku, brak meta |

### Maile — najważniejsze

| # | Scenariusz | Oczekiwane |
|---|---|---|
| M-1 | Generowanie aneksu | **zero wywołań `wp_mail()`** (licznik na filtrze `pre_wp_mail`) |
| M-2 | Regeneracja aneksu | **zero wywołań** |
| M-3 | Znacznik → `wygenerowany` | zero wywołań |
| M-4 | Znacznik → `wyslany` | jeden mail, do klienta, z załącznikiem PDF |
| M-5 | Ponowne zapisanie `wyslany` na `wyslany` | brak drugiego maila |
| M-6 | `customer_email_notifications` wyłączone | brak maila, znacznik i tak się zapisuje |

**Cała bateria E2E biegnie z filtrem `pre_wp_mail` przechwytującym pocztę** — wzorzec z T-217 §3a.
Klikanie tej ścieżki w panelu na prawdziwym zamówieniu wyśle prawdziwemu klientowi maila.

### D6 — klauzula w pośrednictwie

| # | Scenariusz | Oczekiwane |
|---|---|---|
| D-1 | Umowa pośrednictwa, VIN pusty | klauzula w `§1 ust.1 pkt 1 lit. c` **i** w Zał. nr 1 |
| D-2 | Umowa pośrednictwa, VIN poprawny | numer w obu miejscach, dokument identyczny z dzisiejszym (R-1) |
| D-3 | Umowa pośrednictwa, VIN ucięty na listingu, pusty na zamówieniu | klauzula (nie ogryzek numeru) |

### MCP (Chrome), read-only na produkcji

Karta zamówienia: wiersz aneksu w trzech stanach (guard nieaktywny / gotowy do generowania /
wygenerowany), select znacznika, `confirm()` przy „wysłany”. Zamówienie testowe usuwane po teście —
**z weryfikacją sprzątania** (wpadka z T-217 §3a: sprzątanie wywaliło się w połowie i zostawiło
sieroty; sprawdź na końcu: 0 zamówień testowych, 0 osieroconych plików w `uploads/contracts`,
liczniki umów bez zmian).

---

## 8. Poza zakresem

- Aneksy do innych pól niż VIN (konstrukcja gotowa, UI nie).
- Sekcja dokumentów w kroku 5 kreatora — dziś klient po podpisaniu umowy nie może pobrać ani jej,
  ani aneksu z panelu. Osobny task, patrz §9.
- Guard zamaskowanego VIN-u w **treści** umowy pośrednictwa poza przypadkiem pustego pola
  (T-217 §6 — nadal otwarte).
- Znacznik aneksu na liście zamówień (T-218).
- Zmiany w `TRANSITIONS`, numeracji umów, ręcznym wgrywaniu PDF.

---

## 9. Do zgłoszenia Ruslanowi / Jankowi (nie blokuje wdrożenia)

1. **Brzmienie odwołania w aneksie leasingowym.** Wzorzec Piskorza dotyczy umowy pośrednictwa
   i wskazuje jedną jednostkę. W leasingu VIN stoi w dwóch miejscach (`§2 lit. e` i Załącznik nr 1),
   więc odwołanie brzmi „…określony w §2 Umowy lit. e oraz w Załączniku nr 1 do Umowy”.
   To nasza konstrukcja, nie cytat — warto pokazać Ruslanowi przy pierwszym aneksie leasingowym.
2. **Punkt 7 listy weryfikacyjnej** (`docs/biznes/2026-07-27-punkty-do-weryfikacji-ruslan.md`) —
   czy przewiduje aneksy do innych pól — nadal bez odpowiedzi. Nie blokuje, bo konstrukcja i tak
   jest listowa.
3. **Klient traci dostęp do dokumentów po kroku 4 kreatora.** Nie wynika z T-220, tylko z mapowania
   statusów na kroki — ale ujawnia się przy aneksie. Kandydat na osobny, mały task.
4. **6 umów wgranych ręcznie nie ma znanej daty.** Przy pierwszym aneksie do takiej umowy Ruslan
   musi datę wpisać — pole jest, ale warto go uprzedzić.
