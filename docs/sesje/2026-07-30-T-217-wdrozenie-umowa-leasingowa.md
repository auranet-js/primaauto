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

## 3a. E2E + test w przeglądarce (dopisane tego samego dnia)

**E2E przez prawdziwe API — 22/22 zaliczonych** (`tmp/T-217-e2e-status-i-zalacznik.php`).
Uzupełnia bateria z §3, która testowała sam render:

| Zakres | Wynik |
|---|---|
| E2E-1 | `changeStatus('umowa_gotowa')` przechodzi → `UL/2026/0001`, licznik AA nietknięty, cron zaplanowany, `deferredGenerate()` dowozi PDF, załącznik `UL-2026-0001-27a24546.pdf` (183 KB, 11 stron), tytuł „Umowa UL/2026/0001", parent = zamówienie, mime `application/pdf`, depozyt 23 200 zł zamrożony w meta |
| E2E-2 | zamówienie stockowe + wzorzec leasingowy — bez konfliktu, oba wymiary niezależne |
| E2E-3 | zamówienie **bez** typu → `AA/2026/0029`, §1–§9 + Załącznik nr 2, depozyt 6 150 zł, brak §16 |
| MAIL | **4 maile zablokowane** filtrem `pre_wp_mail`, w tym `agwswiebodzin@gmail.com` „umowa gotowa do podpisu" |

Wyciszenie poczty było kluczowe: klikanie tej ścieżki w panelu wysłałoby prawdziwemu klientowi
maila o umowie, której nie zamawiał. Dlatego E2E poszło skryptem, nie kliknięciami.

**Test w przeglądarce (Chrome MCP), read-only na produkcji.** Karta zamówienia #390039 i strona
Ustawień. Trzy rzeczy, których harness nie mógł pokazać:
1. Selektor **nie zapisuje się sam** — po reloadzie wraca „Pośrednictwo". Ruslan może bezpiecznie
   klikać i oglądać.
2. Podpowiedzi liczą się na żywo z prawdziwych danych (rok 2025, „Używany" z 3 800 km, 23 200 zł).
3. **Defekt VIN widać też w panelu:** pole pokazuje `HACRA0B3XS1S...` z podpowiedzią „zapisz, żeby
   utrwalić". Guard chroni treść umowy leasingowej, ale nie chroni pola — jedno kliknięcie
   „Zapisz" utrwala zamaskowaną wartość w meta zamówienia.

Poprawiona jedna wada kosmetyczna: podpowiedź przy kwocie depozytu wychodziła z komórki tabeli
(`<small>` inline → `<p class="description">` pod polem). Zweryfikowane wzrokowo po poprawce.

**Wpadka:** sprzątanie po E2E wywaliło się w połowie na prywatnej `clearListingReservation()` —
zostały 3 zamówienia testowe, 2 osierocone załączniki i podbite liczniki. Doczyszczone ręcznie,
produkcja zweryfikowana w każdym wymiarze (0 zamówień testowych, 0 osieroconych plików,
AA=28/UL=0, 0 meta leasingowych na prawdziwych zamówieniach, rezerwacje nietknięte, ręczny PDF
na #390039 na miejscu). Skrypt poprawiony — zamiast czyścić rezerwację na oślep sprawdza
read-only, czy w ogóle powstała (przy `umowa_gotowa` `syncListingReservation` nic nie zapisuje).

## 3b. Paliwo — świadomie nieedytowalne (i dlaczego to NIE jest luka)

Paliwo idzie wprost z taksonomii `fuel` ogłoszenia; nie ma pola override na zamówieniu i **nie ma
go dorabiać**. Powód: ten sam term **napędza akcyzę** w pipeline cenowym
(`class-asiaauto-order.php:827-835` — benzyna/diesel 3,1%, hybryda/MHEV 1,55%, PHEV/EV/EREV 0%).
Nadpisanie tekstu na zamówieniu rozjechałoby dokument z wyliczeniem, które ustaliło cenę; w umowie
pośrednictwa byłoby to widoczne w jednym dokumencie, bo Załącznik nr 2 drukuje slug paliwa obok
akcyzy. Błędne paliwo poprawia się na **ogłoszeniu** — tam poprawia się jednocześnie treść umowy
i cena.

Wystąpienia paliwa w umowie leasingowej: **§2 lit. b)** (`:1451`) i wiersz „Paliwo" w **Załączniku
nr 1** (`:1646`). Proweniencja: wiersz w załączniku pochodzi z podpisanego egzemplarza, linia w §2
jest **naszą rekonstrukcją** (w podpisanym egzemplarzu lista zaczyna się od `e)`), a sama wartość
to nasz term taksonomii. W §2 lit. a–d mamy więc wolną rękę co do zestawu i brzmienia parametrów —
to jedyne takie miejsce w tym dokumencie.

**Sprostowanie wobec pakietu §9 i wcześniejszego zapisu tej sesji:** notka o „rozjeździe paliwa"
(podpisany egzemplarz mówi PHEV, my EREV) była nadinterpretacją. GAC Hyptec HL jest EREV, więc
nasza wartość jest poprawna, a PHEV to **literówka Ruslana w jego własnym wordowym egzemplarzu** —
dane, nie treść prawna, więc nigdy nie weszły do wzorca. Nie ma tu ani rozjazdu w systemie, ani
sprawy do decyzji. Punkt wykreślony z listy otwartych.

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
- ~~Paliwo PHEV vs EREV~~ — **wykreślone, patrz §3b.** GAC Hyptec HL jest EREV, więc nasza
  wartość jest poprawna, a PHEV to literówka w wordowym egzemplarzu Ruslana (dane, nie treść
  prawna — nie weszły do wzorca). Nie wymaga niczego. Paliwo nie jest i nie ma być edytowalne,
  bo term taksonomii napędza akcyzę.
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
