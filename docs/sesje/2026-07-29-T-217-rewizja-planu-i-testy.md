# T-217 — rewizja planu i scenariusze testów (wątek analityczny)

> Data: 2026-07-29 · Zakres: **wyłącznie T-217** (drugi wzorzec umowy w generatorze PDF).
> T-220 (aneks VIN) i T-221 (pakiet prawny) świadomie poza zakresem — decyzja Janka.
> Wątek analityczny: bez zmian na produkcji, bez dotykania stref kruchych.

---

## 1. Co zweryfikowano na produkcji

Źródło: `~/domains/primaauto.com.pl/public_html/` + panel przez Chrome MCP.

| Backlog twierdzi | Stan faktyczny |
|---|---|
| `class-asiaauto-contract.php` — 1075 linii | **1345 linii** (v0.32.56, 28.05) |
| „Generator umie jeden wzorzec, §1–§9" | Prawda, ale produkuje **oba załączniki**: `renderAttachment1()` (specyfikacja + zdjęcie + podpisy) i `renderAttachment2()` (kalkulacja z breakdownu) |
| Załącznik nr 1 — do zbudowania | **Istnieje.** Ma 10 wierszy; do wersji leasingowej brakuje trzech: rok produkcji, kraj pochodzenia, stan techniczny |
| Numeracja umów — brak w tasku | **Istnieje:** `{prefix}/{rok}/{NNNN}`, licznik roczny w opcji. Config: `contract_prefix = AA`, licznik 2026 = 28 → następny `AA/2026/0029` |
| `bank_account_number_leasing` do dołożenia | Zgodne. Dziś jedno pole: `17 2490 1057 0000 9900 2270 3049` |
| Ślady leasingu w kodzie | **Zero.** Jedyne wystąpienie to pasek zaufania w `class-asiaauto-single.php:518` |

**Gotowe do reużycia:** mPDF (A4, marginesy 22/22/20/25), CSS umowy (~83 linie), struktura `§ N.` z ustępami i podpunktami, `numberToWords()`, bloki podpisów, header z logo i numerem, stopka z paginacją, deferred cron, zapis do biblioteki mediów.

## 2. Ustalenia z sesji (Janek, 29.07)

| # | Ustalenie |
|---|---|
| U1 | **Osobna numeracja leasingowa** — prefix `UL`, własny licznik roczny, obok istniejącego `AA` |
| U2 | **Depozyt zabezpieczający = 10% ceny z ogłoszenia**, domyślnie wypełnione, edytowalne na zamówieniu |
| U3 | **Rok produkcji** — nowe pole (brak w danych), domyślnie z rocznika, edytowalne |
| U4 | **Kraj pochodzenia** — input z domyślną wartością „Chiny", bez hardcode |
| U5 | **Stan techniczny** — z przebiegu (>0 → używany), edytowalne |
| U6 | **Sekcja leasingowa na karcie zamówienia**, wewnątrz istniejącej „Dane umowy", odsłaniana po wyborze typu |
| U7 | Maile — proces fizyczny identyczny (zakup w Chinach, transport, odprawa), więc korekta dotyczy tylko końcówki przy wydaniu. **Nie** drugi zestaw szablonów |

## 3. Zmiany wobec T-217

1. **Numeracja `UL`** — nowe pole configu + osobny licznik. Nie było w tasku.
2. **Załącznik nr 1** — adaptacja `renderAttachment1()` (3 wiersze), nie budowa od zera.
3. **Etykieta „Depozyt zabezpieczający" → „Depozyt zwrotny"** przy istniejącym polu 6 150 zł. Panel używa dziś tej nazwy na to, co w nomenklaturze T-217 jest depozytem zwrotnym; bez zmiany Ruslan zobaczy dwa pola o tej samej nazwie. Zmiana etykiety, klucz `_order_deposit_amount` nietknięty.
4. **`leasing_deposit_percent`** jako nowe pole configu — **nie** przemianowanie `deposit_percent`, który liczy `max(cena × %, min)` dla wszystkich zamówień (dziś 0, min 6 150). Ustawienie go na 10 zmieniłoby kwotę na każdym nowym zamówieniu.
5. **`_order_contract_type`** jako meta typu umowy — `_order_type` jest zajęte (`customer`/`stock`, 79/71 zamówień).
6. **Guard VIN** — patrz §4.
7. **Priorytet urealniony** — patrz §5.

## 4. Defekt znaleziony przy makiecie: zamaskowany VIN trafia do umowy

`getVehicleData()` (`class-asiaauto-contract.php:310-317`) bierze fallback `_order_vin` → `vin_number` listingu → `—`. che168 **maskuje VIN w ogłoszeniu**: zamówienie #390039 ma `HACRA0B3XS1S...` (15 znaków, w tym wielokropek). Taka wartość drukuje się dziś w umowie jako numer VIN — dokument twierdzi nieprawdę.

Skala: na 267 listingów z VIN-em **14 jest zamaskowanych**, jeden ma 16 znaków, 252 są pełne.

**Dotyczy obu wzorców**, nie tylko leasingowego.

Rozwiązanie: guard `preg_match('/^[A-HJ-NPR-Z0-9]{17}$/i', $vin)` — wartość niepełna traktowana jak brak. We wzorcu leasingowym drukuje się wtedy klauzula wzorca („zostanie dodany aneksem do umowy po weryfikacji auta"). Dla umowy pośrednictwa: do decyzji, czy zmieniać zachowanie istniejącego generatora (dziś `—`).

## 5. Priorytet — urealnienie

T-217 **nie jest blokerem sprzedaży**. Panel ma „Wgraj własną (zastąpi)" i Ruslan już z tego korzysta: umowa #072426-1 (Agw Moto, 24.07) jest w systemie jako PDF z badge'em **„Indywidualna (wgrana ręcznie)"**, przy zamówieniu #390039 z nadanym numerem `AA/2026/0028`. Zadanie eliminuje ręczną robotę w Wordzie i rozjazd numeracji, a nie odblokowuje ścieżki.

Konsekwencja: „Wgraj własną" zostaje jako fallback na wypadek zmiany wzorca przez Ruslana.

T-217 **nie czeka na T-113** mimo brzmienia nagłówka („Powiązane: T-113"). T-113 jest gated na partnera finansującego, a partner istnieje (konsorcjum EFL, umowa 99/OSD/2093/26 z 1.06.2026) i umowa leasingowa z klientem została podpisana.

## 6. Zrewidowana kolejność wdrożenia

```
1. Pole typu umowy (_order_contract_type) + przełącznik w sekcji „Dane umowy"
   └─ nic od niego nie zależy poza resztą; domyślnie „pośrednictwo" = zero zmian dla istniejących
2. Config: prefix UL + licznik, bank_account_number_leasing, leasing_deposit_percent
   └─ zależy od 1 (pola widoczne w Ustawieniach niezależnie, ale sens mają z typem)
3. Nowe pola danych na zamówieniu: rok produkcji, kraj, stan techniczny, Finansujący,
   opłata wstępna, procent depozytu + wyliczona kwota
   └─ zależy od 1 (odsłaniane przy typie „leasing")
4. Guard VIN
   └─ niezależny, może iść równolegle
5. Szablon §1–§16 + wariant Załącznika nr 1
   └─ zależy od 2, 3, 4 (czyta pola i config)
6. Rozgałęzienie w generate() wg typu umowy
   └─ zależy od 5
7. Etykieta „Depozyt zwrotny" + korekta końcówki maili
   └─ niezależne, na końcu
```

**Szacunek: 8–12 h.** Plik `docs/roadmapa/T-217-umowa-leasingowa-szablon.md` mówi 14–20 h — zawyżone, bo nie uwzględnia, że szata, załączniki, numeracja i kwoty słownie już istnieją. Do poprawienia.

## 7. Scenariusze testów

Numeracja `T-nn`. Zamówienie referencyjne: **#390039** (GAC Hyptec HL, Agw Moto, 232 000 zł) — ma komplet danych i odpowiada podpisanej umowie #072426-1, więc wynik można porównać z dokumentem.

### Regresja umowy pośrednictwa (najważniejsze — zero regresji to warunek)

| # | Warunki | Kroki | Oczekiwany wynik | Weryfikacja |
|---|---|---|---|---|
| T-01 | Zamówienie bez ustawionego typu umowy | Wygeneruj umowę | Wzorzec **pośrednictwa** §1–§9, numeracja `AA/2026/NNNN` | Porównaj PDF z egzemplarzem sprzed wdrożenia — bajt w bajt poza numerem i datą |
| T-02 | Zamówienie z typem „pośrednictwo" | j.w. | Jak T-01 | j.w. |
| T-03 | Istniejące zamówienia (95 w bazie) | Otwórz 5 losowych kart | Brak zmian w widoku poza etykietą „Depozyt zwrotny"; żadne nie ma typu „leasing" | Przegląd wizualny + `SELECT COUNT(*) WHERE _order_contract_type='leasing'` = 0 |
| T-04 | Licznik `AA` | Wygeneruj umowę pośrednictwa | Licznik `AA` +1, licznik `UL` bez zmian | `wp option get asiaauto_contract_counter_2026` przed i po |

### Wzorzec leasingowy

| # | Warunki | Kroki | Oczekiwany wynik | Weryfikacja |
|---|---|---|---|---|
| T-10 | Typ „leasing", komplet danych | Wygeneruj umowę | PDF §1–§16 + Załącznik nr 1, numer `UL/2026/NNNN`, szata Prima-Auto | Porównanie treści z `GAC Hyper HL … leasing.docx` paragraf po paragrafie |
| T-11 | j.w. | Sprawdź §4 ust. 1 | „10% od wartości samochodu (23 200 zł brutto)" — procent **i** kwota | Zgodność z podpisaną umową |
| T-12 | j.w. | Sprawdź rachunek w §4 | `72 2490 0005 0000 4530 0075 1603` (leasingowy), **nie** …3049 | Porównaj z configiem |
| T-13 | Zmiana `leasing_deposit_percent` na 15 | Wygeneruj nową umowę | Kwota = 15% ceny; **istniejące** zamówienia i depozyt zwrotny 6 150 zł bez zmian | Wygeneruj też umowę pośrednictwa i sprawdź, że depozyt = 6 150 |
| T-14 | Cena zamówienia zmieniona po wygenerowaniu | Regeneruj umowę | Kwota depozytu przeliczona od nowej ceny (albo zachowana — **do rozstrzygnięcia przed wdrożeniem**) | Świadoma decyzja, nie przypadek |
| T-15 | Załącznik nr 1 | Sprawdź tabelę | 9 wierszy: marka i model, paliwo, kolor, rok produkcji, rok rejestracji, VIN, kraj, stan techniczny, przebieg | Porównaj z Załącznikiem nr 1 podpisanej umowy |
| T-16 | Załącznik nr 2 | Wygeneruj umowę leasingową | **Nie powstaje** (D5) — dokument ma 1 załącznik | Liczba stron PDF, brak nagłówka „Załącznik nr 2" |

### VIN

| # | Warunki | Kroki | Oczekiwany wynik | Weryfikacja |
|---|---|---|---|---|
| T-20 | `_order_vin` pusty, listing ma pełne 17 znaków | Wygeneruj | VIN z listingu w §2 lit. e i w Załączniku nr 1 | Porównaj z `vin_number` |
| T-21 | `_order_vin` pusty, listing ma `HACRA0B3XS1S...` | Wygeneruj | **Guard odrzuca** → klauzula „zostanie dodany aneksem do umowy po weryfikacji auta" w obu miejscach | Zamówienie #390039 |
| T-22 | `_order_vin` wpisany ręcznie (17 znaków) | Wygeneruj | Wartość z zamówienia ma pierwszeństwo nad listingiem | Wpisz inny VIN niż na listingu |
| T-23 | VIN 16 znaków (1 taki w bazie) | Wygeneruj | Guard odrzuca | j.w. |

### Numeracja

| # | Warunki | Kroki | Oczekiwany wynik | Weryfikacja |
|---|---|---|---|---|
| T-30 | Pierwsza umowa leasingowa | Wygeneruj | `UL/2026/0001` | Panel + PDF + nazwa pliku |
| T-31 | Druga leasingowa | Wygeneruj | `UL/2026/0002`, licznik `AA` nietknięty | Obie opcje w bazie |
| T-32 | Przełączenie typu po wygenerowaniu | Zmień typ i regeneruj | Do rozstrzygnięcia: numer zachowany czy nadany z drugiej puli — **decyzja przed wdrożeniem**, nie przypadek |
| T-33 | Duplikat numeru | Wpisz ręcznie istniejący `UL/...` | `isContractNumberInUse()` blokuje, tak jak dla `AA` | Komunikat w panelu |

### Pola danych

| # | Warunki | Kroki | Oczekiwany wynik | Weryfikacja |
|---|---|---|---|---|
| T-40 | Nowe zamówienie, typ „leasing" | Otwórz kartę | Rok produkcji podpowiedziany z rocznika, kraj „Chiny", stan z przebiegu | Porównaj z listingiem |
| T-41 | Przebieg = 0 (12 ofert w bazie) | j.w. | Stan techniczny „Nowy" | Wybierz jedną z 12 |
| T-42 | Nadpisanie podpowiedzi | Zmień rok produkcji na 2024, zapisz, regeneruj | Wartość ręczna w PDF, nie podpowiedź | PDF |
| T-43 | Pusty Finansujący | Wygeneruj | Do rozstrzygnięcia: blokada czy pusty placeholder w §3 — **decyzja przed wdrożeniem** |

### Skład PDF

| # | Warunki | Kroki | Oczekiwany wynik | Weryfikacja |
|---|---|---|---|---|
| T-50 | Umowa leasingowa | Otwórz PDF | Brak sierot: żaden `§` nie zostaje sam na końcu strony bez treści | Przegląd wszystkich stron |
| T-51 | j.w. | Stopka | „Umowa nr UL/… \| Prima-Auto Ruslan Prima \| str. N/M" na każdej stronie | j.w. |
| T-52 | Klient firma vs osoba fizyczna | Wygeneruj oba | Blok Zleceniodawcy: firma + „repr. przez" albo samo imię i nazwisko; identyfikator NIP albo PESEL | Dwa zamówienia |
| T-53 | Długa nazwa firmy | j.w. | Nie rozpycha layoutu nagłówka | Agw Moto (58 znaków) jako przypadek |

### Przypadki brzegowe

| # | Warunki | Oczekiwany wynik |
|---|---|---|
| T-60 | Listing usunięty, zamówienie zostało | Ten sam błąd co dziś (`no_listing`), bez fatala |
| T-61 | Brak zdjęcia listingu | Załącznik nr 1 bez miniatury, tabela nietknięta |
| T-62 | Cena 0 | Depozyt 0 zł — do rozstrzygnięcia, czy blokować generowanie |
| T-63 | Regeneracja umowy leasingowej | Nowy plik, poprzedni zostaje w bibliotece mediów (jak dziś) |
| T-64 | Ruslan wgrywa własną na zamówieniu leasingowym | Badge „Indywidualna", numer i typ zachowane |

**Trzy rzeczy do rozstrzygnięcia przed wdrożeniem, nie w trakcie:** T-14 (przeliczanie depozytu przy regeneracji), T-32 (numer przy zmianie typu), T-43 (pusty Finansujący).

## 8. Artefakty

- `tmp/makieta-umowa-leasingowa.php` — generator makiety, standalone, **nie dotyka pluginu**. Uruchomienie: `php makieta-umowa-leasingowa.php [order_id]`
- `tmp/makieta-karta-zamowienia.html` — makieta sekcji „Dane umowy"
- Makieta PDF (10 stron) i HTML wystawione na auratest

## 9. Prompt startowy dla wątku wykonawczego

```
Wdrażamy T-217 — drugi wzorzec umowy (leasing) w generatorze PDF. WYŁĄCZNIE T-217;
nie ruszasz T-220 (aneks VIN), T-221 (pakiet prawny) ani T-113.

Przeczytaj najpierw: docs/sesje/2026-07-29-T-217-rewizja-planu-i-testy.md (rewizja planu,
ustalenia U1-U7, kolejność z §6, scenariusze z §7), docs/roadmapa/T-217-umowa-leasingowa-szablon.md
oraz pamięć projektu. Treść wzorca: Google Drive, „GAC Hyper HL … Agw Moto … leasing.docx"
(fileId 1ikQ_tuzfdEVrLxIDQ9_ISI_W8Ypze8Ob) — treść prawna 1:1, zmieniamy wyłącznie skład i szatę.
Gotowy skład jest w tmp/makieta-umowa-leasingowa.php — przenosisz go do pluginu, nie piszesz od nowa.

Masz zielone światło na całość. Zatrzymujesz się tylko gdy: rzeczywistość rozjeżdża się z planem
na tyle, że trzeba zmienić decyzję; coś wykracza poza uzgodniony zakres; jest ryzyko utraty danych.

Zasady:
- Dokładasz OBOK. class-asiaauto-contract.php i class-asiaauto-order.php to strefy kruche:
  nowe metody obok istniejących, generate() pośrednictwa nietknięty poza rozgałęzieniem na typ,
  TRANSITIONS i LISTING_RESERVATION_MAP bez zmian.
- Backup przed każdą zmianą (.bak z datą na serwerze, mysqldump przy zmianach w bazie).
  php -l przed każdym wgraniem.
- Po każdym elemencie odpal odpowiedni scenariusz z §7 i pokaż wynik. Najpierw regresja
  (T-01..T-04) — zero regresji na umowie pośrednictwa jest warunkiem, nie życzeniem.
- Trzy decyzje z końca §7 (T-14, T-32, T-43) rozstrzygnij z Jankiem ZANIM zaczniesz kodować
  ten fragment — jednym pytaniem, nie trzema osobnymi.
- Bump wersji pluginu + docs/VERSIONS.md. Popraw szacunek w T-217 z 14-20 h na 8-12 h.
- Na koniec: zapis pamięci, dokument sesji, commit i push.

Raportuj zwięźle w trakcie, pełne podsumowanie na końcu: co wdrożone, jakie testy przeszły,
co zostało otwarte, czego nie zrobiłeś i dlaczego.
```

## 10. Otwarte wobec Ruslana (nie blokuje startu)

- Czy 10% to reguła Prima-Auto, czy ustalenie z tym klientem? (dziś: pole edytowalne z podpowiedzią 10%)
- Czy przy zmianie ceny po podpisaniu depozyt ma się przeliczać? (T-14)
- Paliwo w podpisanej umowie: „Hybryda plug-in (PHEV)", w naszych danych „Elektryczny z range extenderem (EREV)". GAC Hyptec HL to EREV — rozjazd wynika z dokumentu, nie z systemu. Pole edytowalne rozwiązuje, ale warto mu to powiedzieć.
