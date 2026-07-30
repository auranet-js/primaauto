# T-217 — pakiet wykonawczy (samowystarczalny)

> Data: 2026-07-30 · Zakres: **wyłącznie T-217** — drugi wzorzec umowy (leasing) w generatorze PDF.
> Makiety **zaakceptowane przez Janka 2026-07-30**.
> Ten plik jest jedynym wymaganym wejściem dla wątku wykonawczego. Rewizja planu z 29.07
> (`2026-07-29-T-217-rewizja-planu-i-testy.md`) jest materiałem źródłowym — gdzie się różni,
> **obowiązuje ten plik** (zmiany wymuszone ograniczeniem z 30.07, patrz §2).
> Baza kodu: plugin `asiaauto-sync` **v0.34.13** na produkcji.

---

## 0. Prompt startowy (do wklejenia w nowym wątku)

```
Wdrażamy T-217 — drugi wzorzec umowy (leasing) w generatorze PDF.

Przeczytaj docs/sesje/2026-07-30-T-217-PAKIET-WYKONAWCZY.md — to jest pełny pakiet:
ograniczenia, stan faktyczny kodu, decyzje, kolejność wdrożenia i scenariusze testów.
Plik jest samowystarczalny, obowiązuje ponad wcześniejszymi dokumentami. Do tego pamięć
projektu i docs/roadmapa/T-217-umowa-leasingowa-szablon.md jako kontekst.

Masz zielone światło na całość i pracujesz bez zatrzymywania się po każdym kroku.
Zatrzymujesz się tylko gdy: rzeczywistość rozjeżdża się z planem na tyle, że trzeba
zmienić decyzję; coś wykracza poza zakres z §2; jest ryzyko utraty danych.

Zaczynasz od regresji (§7, T-01..T-04). Zero regresji na umowie pośrednictwa i na ręcznym
wgrywaniu PDF jest warunkiem, nie życzeniem. Nie deklarujesz „działa" bez uruchomienia testu.

Na koniec: bump wersji + docs/VERSIONS.md, zapis pamięci, dokument sesji w docs/sesje/,
commit i push. Raportuj zwięźle w trakcie, pełne podsumowanie na końcu.
```

---

## 1. Co robimy

Generator `class-asiaauto-contract.php` umie dziś **jeden** wzorzec — pośrednictwo, §1–§9, model agencyjny. Dokładamy **drugi**: „Umowa organizacji importu samochodu" (wariant leasingowy), §1–§16 + Załącznik nr 1.

**Zasada nadrzędna:** treści prawnej nie zmieniamy ani o przecinek. Odtwarzamy dokument Ruslana paragraf po paragrafie, dostosowując wyłącznie skład i szatę oraz podstawiając dane z zamówienia. Uwagi merytoryczne zbieramy osobno (§9), nie wprowadzamy.

Gotowy skład jest w `tmp/makieta-umowa-leasingowa.php` (standalone, 10 stron, dane zamówienia #390039). **Przenosisz go do pluginu, nie piszesz od nowa.**

Treść wzorca referencyjnego: Google Drive, „GAC Hyper HL … Agw Moto … leasing.docx", fileId `1ikQ_tuzfdEVrLxIDQ9_ISI_W8Ypze8Ob`. Umowa **#072426-1** z 24.07.2026, wypełniony egzemplarz B2B.

**T-217 nie jest blokerem sprzedaży.** Ruslan obchodzi brak wzorca przez „Wgraj własną (zastąpi)" — umowa #072426-1 siedzi w systemie jako ręczny PDF z badge'em „Indywidualna", przy zamówieniu #390039 z numerem `AA/2026/0028`. Zadanie kasuje robotę w Wordzie i rozjazd numeracji.

---

## 2. TWARDE OGRANICZENIA (Janek, 2026-07-30)

> **Nie dotykamy działających umów ani ręcznego wgrywania PDF.**

To nadrzędne wobec wszystkiego poniżej. Konkretnie:

### 2.1 Umowa pośrednictwa — zero zmian w zachowaniu

- `renderHTML()` (`class-asiaauto-contract.php:366`), `renderAttachment1()` (:957), `renderAttachment2()` (:1042), `renderPDF()` (:1200), `createAttachment()` (:1274), `numberToWords()` (:1300) — **czytasz i reużywasz, nie modyfikujesz zachowania dla pośrednictwa**.
- `collectData()` (:186) i `getVehicleData()` (:304) — rozszerzasz **addytywnie** (nowe klucze w zwracanej tablicy). Istniejące klucze i ich wartości bez zmian.
- `generate()` (:127) — jedno rozgałęzienie na typ umowy na wejściu. Ścieżka pośrednictwa musi zostać **identyczna instrukcja w instrukcję**.
- Numeracja `AA` i jej licznik — nietknięte.
- `TRANSITIONS`, `LEGACY_STATUS_MAP`, `LISTING_RESERVATION_MAP` w `class-asiaauto-order.php` — bez zmian. Typ umowy **nie jest** statusem.
- **Dowód:** T-01 wymaga porównania PDF pośrednictwa bajt w bajt z egzemplarzem sprzed wdrożenia (poza numerem i datą). Nie „wygląda tak samo" — `cmp`/`sha256sum` na porównywalnych plikach albo diff tekstu wyekstrahowanego z PDF.

### 2.2 Ręczne wgrywanie PDF — zero edycji

`class-asiaauto-order-admin.php`, linie **1766–1881**: toggle „Wgraj własną (zastąpi)", handler uploadu, badge „Indywidualna (wgrana ręcznie)", badge „Auto-generowana". **Nie wchodzisz w te linie.** Nie zmieniasz warunków widoczności toggle'a, nie dokładasz do niego gate'u na typ umowy, nie zmieniasz treści badge'a.

Zamówienie leasingowe z ręcznie wgraną umową ma zachowywać się dokładnie jak dziś: badge „Indywidualna", numer i typ na zamówieniu zachowane, bramka kompletu danych nie obowiązuje. To jest **fallback na wypadek zmiany wzorca przez Ruslana** i zostaje nietknięty na stałe. Test T-64.

### 2.3 Co z tego wypadło z planu z 29.07

Dwa punkty wczorajszego planu naruszały to ograniczenie — **są wycięte**:

| Wypadło | Dlaczego | Co w zamian |
|---|---|---|
| **Zmiana etykiety „Depozyt zabezpieczający" → „Depozyt zwrotny"** przy istniejącym polu 6 150 zł (krok 7 wczorajszego planu) | Etykieta występuje w 4 miejscach (`class-asiaauto-order.php:297`, `order-admin.php:644`, `:1611`, `:2396` — w tym strona Ustawień). Zmiana dotyka widoku **wszystkich 95 istniejących zamówień** i konfiguracji operacyjnej Ruslana | **Nowe pole leasingowe nazywamy jednoznacznie: „Depozyt zabezpieczający — leasing (% ceny)"**. Kolizja nazw zniknie bez dotykania istniejącego pola. Zysk ten sam, koszt zerowy |
| **Korekta końcówki maili statusowych** (U7, krok 7) | Modyfikuje działający przepływ maili dla wszystkich zamówień, w tym pośrednictwa | Poza zakresem → osobne zadanie. Proces fizyczny jest identyczny (zakup w Chinach, transport, odprawa), więc maile obecne **nie kłamią** na zamówieniu leasingowym — są tylko nieprecyzyjne przy wydaniu |

Trzecia zmiana wobec 29.07 — **guard VIN zawężony do gałęzi leasingowej**, patrz §5.

---

## 3. Stan faktyczny generatora (zweryfikowany na produkcji)

Ścieżka: `~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/`

| Element | Stan |
|---|---|
| `class-asiaauto-contract.php` | **1345 linii**, nie 1075 jak mówi backlog |
| Załączniki | **Oba istnieją**: `renderAttachment1()` (specyfikacja + zdjęcie + podpisy), `renderAttachment2()` (kalkulacja z breakdownu) |
| Załącznik nr 1 | Ma **10 wierszy**. Do wersji leasingowej brakuje trzech: rok produkcji, kraj pochodzenia, stan techniczny |
| Numeracja umów | **Istnieje**: `{prefix}/{rok}/{NNNN}`, licznik roczny w opcji. Config `contract_prefix = AA`, licznik 2026 = 28 → następny `AA/2026/0029` |
| `isContractNumberInUse()` | Istnieje, blokuje duplikaty |
| Rachunek bankowy | Dziś jedno pole: `17 2490 1057 0000 9900 2270 3049` |
| Ślady leasingu w kodzie | **Zero** (jedyne wystąpienie: pasek zaufania w `class-asiaauto-single.php:518`) |
| Ręczne wgrywanie | `class-asiaauto-order-admin.php:1766-1881` — **osobny plik, osobna ścieżka**, nie przechodzi przez generator |

**Gotowe do reużycia:** mPDF (A4, marginesy 22/22/20/25), CSS umowy (~83 linie), struktura `§ N.` z ustępami i podpunktami, `numberToWords()`, bloki podpisów, header z logo i numerem, stopka z paginacją, deferred cron (`deferredGenerate()`), zapis do biblioteki mediów.

Mapa metod: `generate():127` · `regenerate():152` · `getContractUrl():166` · `collectData():186` · `getVehicleData():304` · `extractRegistrationYear():350` · `renderHTML():366` · `renderAttachment1():957` · `renderAttachment2():1042` · `renderPDF():1200` · `createAttachment():1274` · `numberToWords():1300`

---

## 4. Ustalenia (Janek, 29.07)

| # | Ustalenie |
|---|---|
| U1 | **Osobna numeracja leasingowa** — prefix `UL`, własny licznik roczny, obok istniejącego `AA` |
| U2 | **Depozyt zabezpieczający = 10% ceny z ogłoszenia**, domyślnie wypełnione, edytowalne na zamówieniu |
| U3 | **Rok produkcji** — nowe pole (nie ma go nigdzie w danych), domyślnie podpowiedź z rocznika, edytowalne |
| U4 | **Kraj pochodzenia** — input z domyślną wartością „Chiny", bez hardcode |
| U5 | **Stan techniczny** — z przebiegu (>0 → używany), edytowalne |
| U6 | **Sekcja leasingowa na karcie zamówienia**, wewnątrz istniejącej „Dane umowy", odsłaniana po wyborze typu |
| U7 | Maile — proces fizyczny identyczny, **nie** drugi zestaw szablonów. Korekta końcówki → poza zakresem (§2.3) |

### Nowe pola i klucze

| Co | Klucz / nazwa | Uwaga |
|---|---|---|
| Typ umowy | `_order_contract_type` | **Nie `_order_type`** — zajęte (`customer`/`stock`, 79/71 zamówień). Brak wartości = pośrednictwo |
| Procent depozytu leasingowego | config `leasing_deposit_percent` | **Nie przemianowywać `deposit_percent`** — liczy `max(cena × %, min)` dla wszystkich zamówień (dziś 0, min 6 150). Ustawienie 10 zmieniłoby kwotę na każdym nowym zamówieniu |
| Rachunek leasingowy | config `bank_account_number_leasing` | `72 2490 0005 0000 4530 0075 1603` (Alior, z podpisanej umowy) |
| Prefix leasingowy | config `contract_prefix_leasing` = `UL` + własny licznik roczny | |
| Rok produkcji, kraj pochodzenia, stan techniczny, Finansujący, opłata wstępna, kwota depozytu | meta zamówienia | Odsłaniane przy typie „leasing" |

---

## 5. Decyzje rozstrzygnięte — nie pytaj o nie ponownie

1. **Kwota depozytu zabezpieczającego NIE przelicza się** przy zmianie ceny po wygenerowaniu umowy. Zamrażana na zamówieniu, zmiana wyłącznie ręczną edycją pola. *(Klient ma podpisany dokument na konkretną kwotę; przy ~23 tys. zł automatyczne przeliczenie to spór, nie usprawnienie.)*

2. **Numer umowy raz nadany zostaje** przy przełączeniu typu. Przełączenie zmienia wzorzec, nie tożsamość dokumentu — liczniki `AA` i `UL` nie dostają dziur. *(Inaczej każde przełączenie tam i z powrotem paliłoby po numerze w każdej puli.)*

3. **Pusty Finansujący NIE blokuje generowania.** W §3 zostaje sformułowanie wzorca o podmiocie wskazanym przez Zleceniodawcę. *(Wzorzec nie wiąże umowy z konkretnym leasingodawcą, a przy podpisaniu klient często nie ma decyzji leasingowej.)*

4. **Guard VIN — tylko w gałęzi leasingowej.** `getVehicleData()` bierze dziś fallback `_order_vin` → `vin_number` listingu → `—`. che168 **maskuje VIN w ogłoszeniu**: #390039 ma `HACRA0B3XS1S...` (15 znaków z wielokropkiem) i taka wartość drukuje się dziś w umowie jako numer VIN. Skala: na 267 listingów z VIN-em **14 zamaskowanych**, 1 ma 16 znaków, 252 pełne.

   Guard: `preg_match('/^[A-HJ-NPR-Z0-9]{17}$/i', $vin)` — wartość niepełna traktowana jak brak. W umowie leasingowej drukuje się wtedy klauzula wzorca („zostanie dodany aneksem do umowy po weryfikacji auta").

   **Wobec ograniczenia z §2.1 pośrednictwo zostaje bez zmian** — dalej wydrukuje zamaskowaną wartość. Defekt dotyczy obu wzorców, ale jego naprawa w pośrednictwie zmienia treść działającego dokumentu, więc idzie jako **osobne zgłoszenie do decyzji Janka** (§9), nie mimochodem tutaj. Implementacja: guard jako osobna metoda `isValidVin()` wołana **tylko** ze ścieżki leasingowej — żeby przełączenie było potem jedną linią.

5. **Cena 0** — nie blokujemy generowania, depozyt 0 zł. Ruslan widzi kwotę i sam koryguje; blokada wymuszałaby wartość pozorną.

---

## 6. Kolejność wdrożenia

```
1. Pole typu umowy (_order_contract_type) + przełącznik w sekcji „Dane umowy"
   └─ brak wartości = pośrednictwo → zero zmian dla 95 istniejących zamówień
   └─ NIE dotykasz toggle'a „Wgraj własną" (order-admin.php:1766-1881)
2. Config: prefix UL + licznik, bank_account_number_leasing, leasing_deposit_percent
   └─ nowe pola obok istniejących; deposit_percent i contract_prefix nietknięte
3. Nowe pola danych na zamówieniu: rok produkcji, kraj, stan techniczny, Finansujący,
   opłata wstępna, procent depozytu + wyliczona kwota
   └─ zależy od 1 (odsłaniane przy typie „leasing")
   └─ etykieta: „Depozyt zabezpieczający — leasing (% ceny)" (§2.3)
4. Guard VIN — isValidVin(), wołany wyłącznie ze ścieżki leasingowej
   └─ niezależny, może iść równolegle
5. Szablon §1–§16 + wariant Załącznika nr 1 (3 wiersze więcej), przeniesiony
   z tmp/makieta-umowa-leasingowa.php
   └─ zależy od 2, 3, 4
   └─ Załącznik nr 2 dla leasingu NIE powstaje — dokument ma jeden załącznik
6. Rozgałęzienie w generate() wg typu umowy
   └─ zależy od 5; ścieżka pośrednictwa identyczna instrukcja w instrukcję
7. Bump wersji (0.34.13 → wyżej) + docs/VERSIONS.md + poprawka szacunku w tasku
```

**Szacunek: 8–12 h** (Janek ~2 h, AI ~6–10 h). Wcześniejsze 14–20 h zakładało budowę od zera.

**Higiena wykonania:** `.bak` z datą na serwerze przed każdym nadpisaniem pliku · `mysqldump` przed zmianami w bazie · `php -l` przed każdym wgraniem · po każdym elemencie odpalasz odpowiedni scenariusz z §7 i pokazujesz wynik.

---

## 7. Scenariusze testów

Zamówienie referencyjne: **#390039** (GAC Hyptec HL, Agw Moto, 232 000 zł) — komplet danych, odpowiada podpisanej umowie #072426-1, więc wynik można porównać z dokumentem. Depozyt 10% = 23 200 zł.

### Regresja — warunek wstępny, odpalasz PIERWSZE

| # | Warunki | Kroki | Oczekiwany wynik | Weryfikacja |
|---|---|---|---|---|
| T-01 | Zamówienie bez ustawionego typu umowy | Wygeneruj umowę | Wzorzec pośrednictwa §1–§9, numeracja `AA/2026/NNNN` | **Diff bajt w bajt** z egzemplarzem sprzed wdrożenia poza numerem i datą (`pdftotext` + `diff`) |
| T-02 | Zamówienie z typem „pośrednictwo" | j.w. | Jak T-01 | j.w. |
| T-03 | Istniejące zamówienia (95 w bazie) | Otwórz 5 losowych kart | **Zero zmian w widoku.** Żadne nie ma typu „leasing", etykieta „Depozyt zabezpieczający" bez zmian | Przegląd + `SELECT COUNT(*) WHERE _order_contract_type='leasing'` = 0 |
| T-04 | Licznik `AA` | Wygeneruj umowę pośrednictwa | Licznik `AA` +1, licznik `UL` bez zmian | `wp option get` przed i po |
| T-05 | Ręczne wgrywanie na zamówieniu pośrednictwa | Wgraj PDF | Badge „Indywidualna", zachowanie identyczne jak dziś | Porównaj z zamówieniem #390039 |

### Wzorzec leasingowy

| # | Warunki | Oczekiwany wynik | Weryfikacja |
|---|---|---|---|
| T-10 | Typ „leasing", komplet danych | PDF §1–§16 + Załącznik nr 1, numer `UL/2026/NNNN`, szata Prima-Auto | Porównanie treści z `.docx` paragraf po paragrafie |
| T-11 | j.w., §4 ust. 1 | „10% od wartości samochodu (23 200 zł brutto)" — procent **i** kwota | Zgodność z podpisaną umową |
| T-12 | j.w., rachunek w §4 | `72 2490 0005 0000 4530 0075 1603`, **nie** …3049 | Porównaj z configiem |
| T-13 | `leasing_deposit_percent` = 15 | Kwota = 15% ceny; umowa pośrednictwa i depozyt 6 150 zł **bez zmian** | Wygeneruj też pośrednictwo i sprawdź 6 150 |
| T-14 | Cena zmieniona po wygenerowaniu, regeneracja | Kwota depozytu **zachowana** (D1) | Porównaj przed/po |
| T-15 | Załącznik nr 1 | 9 wierszy: marka i model, paliwo, kolor, rok produkcji, rok rejestracji, VIN, kraj, stan techniczny, przebieg | Porównaj z Załącznikiem podpisanej umowy |
| T-16 | Załącznik nr 2 przy leasingu | **Nie powstaje** — dokument ma 1 załącznik | Liczba stron, brak nagłówka „Załącznik nr 2" |

### VIN

| # | Warunki | Oczekiwany wynik | Weryfikacja |
|---|---|---|---|
| T-20 | Leasing, `_order_vin` pusty, listing ma pełne 17 znaków | VIN z listingu w §2 lit. e i w Załączniku nr 1 | Porównaj z `vin_number` |
| T-21 | Leasing, `_order_vin` pusty, listing ma `HACRA0B3XS1S...` | Guard odrzuca → klauzula „zostanie dodany aneksem…" w obu miejscach | #390039 |
| T-22 | Leasing, `_order_vin` wpisany ręcznie (17 znaków) | Wartość z zamówienia ma pierwszeństwo nad listingiem | Wpisz inny VIN niż na listingu |
| T-23 | Leasing, VIN 16 znaków (1 taki w bazie) | Guard odrzuca | j.w. |
| T-24 | **Pośrednictwo**, listing z zamaskowanym VIN | Zachowanie **niezmienione** — drukuje jak dziś (D4, §2.1) | Porównaj z egzemplarzem sprzed wdrożenia |

### Numeracja

| # | Warunki | Oczekiwany wynik | Weryfikacja |
|---|---|---|---|
| T-30 | Pierwsza umowa leasingowa | `UL/2026/0001` | Panel + PDF + nazwa pliku |
| T-31 | Druga leasingowa | `UL/2026/0002`, licznik `AA` nietknięty | Obie opcje w bazie |
| T-32 | Przełączenie typu po wygenerowaniu | **Numer zachowany** (D2), zmienia się wyłącznie wzorzec | Oba liczniki bez zmian |
| T-33 | Duplikat numeru — wpisz ręcznie istniejący `UL/...` | `isContractNumberInUse()` blokuje, tak jak dla `AA` | Komunikat w panelu |

### Pola danych

| # | Warunki | Oczekiwany wynik | Weryfikacja |
|---|---|---|---|
| T-40 | Nowe zamówienie, typ „leasing" | Rok produkcji podpowiedziany z rocznika, kraj „Chiny", stan z przebiegu | Porównaj z listingiem |
| T-41 | Przebieg = 0 (12 ofert w bazie) | Stan techniczny „Nowy" | Wybierz jedną z 12 |
| T-42 | Nadpisanie podpowiedzi (rok → 2024), zapis, regeneracja | Wartość ręczna w PDF, nie podpowiedź | PDF |
| T-43 | Pusty Finansujący | **Generuje się** (D3), w §3 sformułowanie wzorca | PDF §3 ust. 1 |

### Skład PDF

| # | Warunki | Oczekiwany wynik |
|---|---|---|
| T-50 | Umowa leasingowa | Brak sierot — żaden `§` nie zostaje sam na końcu strony bez treści |
| T-51 | j.w., stopka | „Umowa nr UL/… \| Prima-Auto Ruslan Prima \| str. N/M" na każdej stronie |
| T-52 | Klient firma vs osoba fizyczna | Blok Zleceniodawcy: firma + „repr. przez" albo imię i nazwisko; NIP albo PESEL |
| T-53 | Długa nazwa firmy | Nie rozpycha nagłówka. Agw Moto (58 znaków) jako przypadek |

### Przypadki brzegowe

| # | Warunki | Oczekiwany wynik |
|---|---|---|
| T-60 | Listing usunięty, zamówienie zostało | Ten sam błąd co dziś (`no_listing`), bez fatala |
| T-61 | Brak zdjęcia listingu | Załącznik nr 1 bez miniatury, tabela nietknięta |
| T-62 | Cena 0 | Generuje się, depozyt 0 zł (D5) |
| T-63 | Regeneracja umowy leasingowej | Nowy plik, poprzedni zostaje w bibliotece mediów (jak dziś) |
| T-64 | **Ruslan wgrywa własną na zamówieniu leasingowym** | Badge „Indywidualna", numer i typ zachowane, bramka kompletu danych nie obowiązuje — **identycznie jak dziś** (§2.2) |

---

## 8. Poza zakresem

- **T-220** (aneks VIN) — osobny task.
- **T-221** (pakiet prawny), **T-121** (PayU / depozyt online) — osobne taski.
- **T-113** (ścieżka finansowania w kreatorze) — gated, poza zakresem. T-217 na niego **nie czeka**: partner istnieje (konsorcjum EFL, umowa 99/OSD/2093/26 z 1.06.2026), umowa leasingowa z klientem podpisana.
- **Zmiana etykiety istniejącego pola depozytu** — wycięte, §2.3.
- **Korekta końcówki maili statusowych** — wycięte, §2.3, osobne zadanie.
- **Guard VIN w umowie pośrednictwa** — §5 pkt 4, do decyzji Janka.
- Przebudowa statusów pod bramki leasingowe — świadomie nie tutaj.
- Rename klas / CPT / meta / shortcodów — nigdy mimochodem.

---

## 9. Do zgłoszenia Ruslanowi / Jankowi (nie blokuje wdrożenia)

- **Guard VIN w umowie pośrednictwa** — czy naprawiamy? Dziś zamaskowany VIN z che168 drukuje się w umowie pośrednictwa jako prawdziwy numer (14 listingów z 267). Naprawa = zmiana treści działającego dokumentu, więc czeka na świadomą decyzję. Po wdrożeniu T-217 to jedna linia.
- Czy **10% depozytu** to reguła Prima-Auto, czy ustalenie z tym jednym klientem? (dziś: pole edytowalne z podpowiedzią 10%)
- **Paliwo** w podpisanej umowie: „Hybryda plug-in (PHEV)", w naszych danych „Elektryczny z range extenderem (EREV)". GAC Hyptec HL to EREV — rozjazd wynika z dokumentu, nie z systemu. Pole edytowalne to obchodzi, ale warto, żeby wiedział.
- W **§2 podpisanego egzemplarza** lista parametrów zaczyna się od `e)` — brak punktów a–d. W makiecie zrekonstruowane jako marka i model, paliwo, kolor, rok produkcji (kolejność z Załącznika nr 1). Do potwierdzenia.
