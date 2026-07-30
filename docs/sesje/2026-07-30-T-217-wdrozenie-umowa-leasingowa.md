# T-217 — wdrożenie drugiego wzorca umowy (leasing) · 2026-07-30

> Wykonane wg `docs/sesje/2026-07-30-T-217-PAKIET-WYKONAWCZY.md` §0.
> Wersja pluginu: **0.34.13 → 0.34.14** (LIVE na produkcji).
> Status: **DONE**. Regresja zero, 42/42 testów wariantu leasingowego zaliczonych.

---

## 1. Co jest na produkcji

Generator `class-asiaauto-contract.php` obsługuje dwa wzorce:

| Wzorzec | Kiedy | Dokument | Numeracja |
|---|---|---|---|
| Pośrednictwo (**domyślny**) | brak `_order_contract_type` albo `posrednictwo` | „Umowa zlecenia sprowadzenia samochodu z Chin", §1–§9 + Zał. 1 + Zał. 2 | `AA/2026/NNNN` |
| Leasing | `_order_contract_type = leasing` | „Umowa organizacji importu samochodu", §1–§16 + **tylko** Zał. 1 | `UL/2026/NNNN` |

Wybór na karcie zamówienia → metabox „Dane umowy" → selektor „Wzorzec umowy".
Po wybraniu „Leasing" odsłania się sekcja z polami: rok produkcji, kraj pochodzenia,
stan techniczny, procent i kwota depozytu, Finansujący, opłata wstępna.

Konfiguracja: Ustawienia zamówień → karta **„Umowa leasingowa"** (prefix `UL`,
rachunek `72 2490 0005 0000 4530 0075 1603`, procent depozytu `10`).

Stan liczników po wdrożeniu: **AA = 28** (następny `AA/2026/0029`), **UL = 0**
(pierwsza umowa leasingowa dostanie `UL/2026/0001`).

## 2. Dowody regresji („nie ruszamy działających umów")

- **Umowa pośrednictwa — tekst PDF identyczny co do znaku.** 5 zamówień (#390039, #387788,
  #387071, #362513, #360448) wyrenderowanych read-only (przez Reflection, bez tworzenia
  załączników i bez dotykania meta) **przed** i **po** wdrożeniu → `pdftotext` + `diff`
  bez różnic. Skrypt: `tmp/T-217-regresja-posrednictwo.php`.
- **#390039 nadal drukuje zamaskowany VIN** `HACRA0B3XS1S...` — to nie regresja, to dowód,
  że guard VIN nie wyciekł do gałęzi pośrednictwa (decyzja D4).
- **Ręczne wgrywanie PDF — blok kodu identyczny.** `diff` całego zakresu
  `renderUploadCustomContractToggle()` … `renderCardContract()` wobec kopii sprzed wdrożenia:
  171 = 171 linii, zero różnic. Handler `handleUploadCustomContract()` (linia 499) nie jest
  w żadnym hunku zmian i nie zawiera ani jednego odwołania do typu umowy → zamówienie
  leasingowe z ręcznie wgraną umową zachowuje się dokładnie jak dziś (T-05, T-64).
- **T-03:** 0 zamówień z typem `leasing` w bazie (na 150), sekcja leasingowa renderuje się
  z `display:none`, „Pośrednictwo" jest preselektowane, etykieta „Depozyt zabezpieczający"
  i wszystkie dotychczasowe pola (`deposit_amount`, `contract_commission_net`, `order_vin`,
  `extra_costs_*`) obecne bez zmian — sprawdzone renderem karty dla 5 losowych zamówień.
- Zero sierot w `uploads/contracts`, zero nowych załączników, zero meta leasingowych
  na prawdziwych zamówieniach, zamówienie testowe usunięte, liczniki przywrócone.

**Jedna świadoma zmiana w widoku wszystkich kart:** selektor „Wzorzec umowy" jest nowym,
widocznym elementem na każdym zamówieniu (wymóg kroku 1 pakietu). Nic poza nim się nie zmienia.

## 3. Wynik testów wariantu leasingowego

`tmp/T-217-testy-leasing.php` — **42 zaliczone, 0 niezaliczonych**. Pracuje na zamówieniu
testowym zakładanym i usuwanym przez skrypt (nie przez `AsiaAuto_Order::create()`, żeby
nie dotknąć rezerwacji listingu).

| Zakres | Wynik |
|---|---|
| T-10..T-12 | §1–§16 obecne, 11 stron, numer w nagłówku i stopce, depozyt 10% = 23 200 zł z 232 000, rachunek leasingowy w §4, **brak** rachunku pośrednictwa |
| T-15, T-16 | Załącznik nr 1 = 9 wierszy; **brak Załącznika nr 2** |
| T-20..T-23 | pełny VIN z listingu w §2 i załączniku; VIN z zamówienia ma pierwszeństwo; guard odrzuca 16-znakowy i zamaskowany → klauzula o aneksie w obu miejscach |
| T-13, T-14, T-62 | procent z configu (15% → 34 800 zł) przy jednoczesnym depozycie pośrednictwa **6 150 zł bez zmian**; kwota zamrożona po zmianie ceny 232 → 300 tys.; cena 0 → generuje się, depozyt 0 zł |
| T-30..T-33, T-04 | `UL/2026/0001`, `0002`, licznik AA nietknięty; pośrednictwo dalej bierze z AA (+1); przełączenie typu zachowuje numer i nie rusza liczników; duplikat blokowany; ręczny numer `UL` podbija licznik UL, nie AA |
| T-40..T-43 | podpowiedzi (rocznik, „Chiny", stan z przebiegu), przebieg 0 → „Nowy", ręczne wartości wygrywają, pusty Finansujący nie blokuje |
| T-50..T-53 | brak sierot `§`, stopka na każdej z 11 stron, firma + „repr. przez" + NIP, 52-znakowa nazwa nie rozwala dwukolumnowego nagłówka |
| T-60, T-61 | brak listingu → `no_listing` bez fatala; nieistniejący listing bez fatala; brak zdjęcia → załącznik bez miniatury, tabela cała |

**Uwaga metodologiczna:** pierwsze biegi pokazały 4 „niezaliczone", które okazały się
artefaktami ekstrakcji tekstu, nie defektami: `pdftotext` zwraca ligatury (`weryﬁkacji`),
CSS uppercase'uje nagłówki (`ZAŁĄCZNIK NR 1`), długie zdania i komórki tabel łamią się
na wiersze, a w trybie `-layout` kolumny się przeplatają („VIN" wchodzi w środek klauzuli).
Dodatkowo negatywna asercja „nie ma słowa Używany" łapała się wewnątrz `używanym`
z treści prawnej §2. Poprawione zostały **asercje**, nie kod — treść PDF była poprawna
od pierwszego biegu.

## 4. Decyzje zrealizowane bez odstępstw

D1 (kwota depozytu zamrażana, nie przelicza się), D2 (numer zostaje przy przełączeniu typu),
D3 (pusty Finansujący nie blokuje, §3 zachowuje sformułowanie wzorca), D4 (guard VIN tylko
w gałęzi leasingowej), D5 (cena 0 nie blokuje).

## 5. Rozstrzygnięcia podjęte w trakcie (do wiadomości)

1. **§2 — numeracja ustępów poprawiona z 1 na 2.** W makiecie ustępy §2 startowały od 1,
   przez co odesłanie „Okoliczności, o których mowa w **ust. 2**" wskazywało samo siebie
   (ust. 1 to lista parametrów a–f). Poprawka numeracji, nie treści. Sprawdzone: pozostałe
   odesłania (§10 ust. 4 → ust. 2 i 3, §12 ust. 2 → §8 ust. 4) trafiają prawidłowo.
2. **Finansujący i opłata wstępna NIE są drukowane w umowie.** Wzorzec nie ma dla nich miejsca:
   §3 ust. 1 mówi o „podmiocie wskazanym przez Zleceniodawcę", §5 ust. 3 odsyła po kwoty
   do dokumentów leasingowych, a Załącznik nr 1 ma sztywne 9 wierszy (T-15). Wpisanie tam
   danych wymagałoby dopisania zdań prawnych, czego zakazuje zasada nadrzędna („nie zmieniamy
   ani o przecinek"). Pola zostały jako **notatki operacyjne** na karcie zamówienia,
   z opisem wprost w UI. **Do decyzji Janka:** czy Ruslan chce je widzieć w dokumencie —
   jeśli tak, potrzebne jest brzmienie od prawnika, nie nasza improwizacja.
3. **Klasy `class-asiaauto-{contract,order,order-admin}.php` nie są śledzone w repo**
   (repo trzyma selektywne lustro 18 plików, source of truth = serwer). Nie dodawałem ich
   przy okazji — to zmiana polityki mirroringu repo, nie część T-217. Do decyzji, czy warto.
4. **Ręczne wgrywanie na zamówieniu leasingowym bez numeru podpowie numer z puli `AA`** —
   formularz uploadu (nietykalny wg §2.2) używa `previewNextContractNumber()`. Ruslan może
   wpisać `UL/...` ręcznie i licznik UL podbije się prawidłowo (rozszerzone
   `bumpContractCounterFromManual()`). Zmiana podpowiedzi wymagałaby wejścia w zakazany blok.

## 6. Do zgłoszenia Ruslanowi / Jankowi (nie blokuje)

- **Guard VIN w umowie pośrednictwa** — dziś zamaskowany VIN z che168 drukuje się jako
  prawdziwy numer (14 listingów z 267). Po T-217 naprawa to jedna linia: podmiana `vin`
  na `vin_verified` w `renderHTML()` i `renderAttachment1()`. Czeka na świadomą decyzję,
  bo zmienia treść działającego dokumentu.
- Czy **10% depozytu** to reguła Prima-Auto, czy ustalenie z jednym klientem?
- **Paliwo:** podpisana umowa mówi „Hybryda plug-in (PHEV)", nasze dane „Elektryczny
  z range extenderem (EREV)" — GAC Hyptec HL to EREV, rozjazd wynika z dokumentu.
  Pole jest edytowalne, ale warto, żeby wiedział.
- **§2 lit. a–d** zrekonstruowane (w podpisanym egzemplarzu lista zaczyna się od `e)`) —
  do potwierdzenia u Ruslana.
- Czy **Finansujący / opłata wstępna** mają trafić do treści umowy (punkt 5.2 wyżej).

## 7. Higiena i artefakty

- Backup bazy: `~/backups/primaauto/2026-07-30-T-217/wp521-przed-T-217.sql` (postmeta + options).
- `.bak-2026-07-30-T-217` na serwerze dla wszystkich 4 zmienionych plików.
- `php -l` przed każdym wgraniem; smoke test: wtyczka `active 0.34.14`,
  home / `/samochody/` / oferta = HTTP 200.
- Skrypty testowe w `tmp/` (gitignored): `T-217-regresja-posrednictwo.php`,
  `T-217-testy-leasing.php`. Oba są **wielokrotnego użytku** — warto je uruchomić przy
  następnej zmianie w generatorze umów.
- Ostrzeżenie mPDF `unlink(_tempImgPNG…)` przy wielu renderach w jednym procesie PHP:
  artefakt wspólnego katalogu tymczasowego w skrypcie testowym, nie występuje w produkcji
  (jeden render na request). Nie dotyczy treści PDF.

## 8. Poza zakresem (świadomie, wg §2.3 i §8 pakietu)

Zmiana etykiety istniejącego pola depozytu · korekta końcówki maili statusowych ·
guard VIN w pośrednictwie · T-220 (aneks VIN) · T-221 (pakiet prawny) · T-121 (PayU) ·
T-113 (ścieżka finansowania w kreatorze) · przebudowa statusów · rename klas/CPT/meta.
