# 2026-09-14 — Autopay jako następca PayU: rozpoznanie

> Kontekst: [ADR 2026-09-14 — PayU odmówiło](../decyzje/2026-09-14-payu-odmowa-wylaczenie-bramki.md).
> Źródło: research ze stron autopay.pl / developers.autopay.pl (14.09.2026), z Autopay się nie kontaktowaliśmy.
> Stawki i warunki trzeba potwierdzić przy rejestracji — to stan z publicznych stron, nie oferta.

## Werdykt

**Da się przestawić.** Lista branż zakazanych Autopay dla BLIK/przelewu/karty nie obejmuje pojazdów,
importu, zaliczek ani depozytów. Technicznie to wymiana warstwy API w naszym module, nie przebudowa flow.

**Ryzyko odmowy jest realne z tej samej przyczyny co w PayU** — plus jeden nowy punkt: na liście zakazanych
jest **„dropshipping”**, a oferty z chińskich portali, kupowane dopiero po zamówieniu, mogą tak zostać
odczytane. Strona musi jednoznacznie mówić: działamy jako **agent klienta na podstawie umowy**, depozyt
jest opłatą za **usługę**.

## Branże zakazane — co nas dotyczy

Lista: https://developers.autopay.pl/online/wykaz-produktow-i-branz-zakazanych

| Punkt listy | Ryzyko dla nas |
|---|---|
| „dropshipping” | **wysokie** — model „auto z portalu, kupujemy po zamówieniu” |
| „produkty i usługi finansowe, w tym pośrednictwo” | średnie — `/finansowanie/` i umowa leasingowa nie mogą wyglądać na pośrednictwo w leasingu |
| „pozyskiwanie potencjalnych klientów” | niskie |
| Karty: dostawa > 1 rok od zamówienia | brak (import trwa krócej) |
| BNPL: zaliczki/kaucje, MCC 5521 | nie dotyczy — BNPL nie bierzemy |

Case studies z dealerami/importerami: brak.

## Wymogi wobec strony (weryfikacja)

- strona publiczna; **min. 5 produktów/usług z opisem, ceną i zdjęciem** (oferty mamy),
- regulamin z nazwą, adresem, NIP; polityka prywatności; zasady dostaw, zwrotów, reklamacji,
- karty dodatkowo: czas realizacji, **checkbox akceptacji regulaminu w koszyku**, logotypy kart, kontakt,
- **zatwierdzonego regulaminu nie wolno potem zmieniać** → regulamin finalizujemy (z nazwą Autopay)
  PRZED zgłoszeniem, nie w trakcie,
- kreator jest za logowaniem → trzeba dać analitykowi konto testowe / gotową ścieżkę.

Źródła: https://developers.autopay.pl/online/faq/podstawowe-informacje ·
https://developers.autopay.pl/online/wymogi-karty

## Onboarding

Rejestracja online (portal.autopay.eu) → weryfikacja reprezentanta (fotoweryfikacja / mObywatel) →
przelew weryfikacyjny 49 zł z konta firmowego z białej listy VAT → weryfikacja zwykle do 24 h, karty
osobno do 14 dni. Sandbox (`testpay.autopay.eu`) dopiero po założeniu konta, na prośbę z ServiceID.
Źródło: https://developers.autopay.pl/online/wdrozenie-krok-po-kroku

## Koszty i limity

- Standard: **1,19% + 0,34 zł** od transakcji, bez abonamentu → depozyt 6 150 zł ≈ **73,53 zł** prowizji.
- Starter: 29,99 zł/mies., 0% do 5 500 zł obrotu, powyżej 1,1% → przy naszych kwotach bez sensu.
- Aktywacja 49 zł, zwroty 0 zł. Plan indywidualny do negocjacji.
- Limit transakcji: BLIK 50 000 zł, pay-by-link/karta 100 000 zł (plus limity banku klienta) — depozyt mieści się.
- Środki na koncie najpóźniej D+1, prowizja potrącana z wpłaty.

Źródło: https://autopay.pl/oferta/platnosci-online#pricing

## Technika — mapowanie na nasz moduł

| PayU (dziś) | Autopay |
|---|---|
| OAuth + JSON create order | przedtransakcja: POST z hashem SHA256 (pola łączone `|` + klucz), odpowiedź XML |
| BLIK na stronie (`BLIK_AUTHORIZATION_CODE`) | `GatewayID=509` + `AuthorizationCode` |
| notify (200 OK) | ITN: XML w base64, **odpowiedź XML CONFIRMED z hashem**, ponawiane do potwierdzenia |
| retrieve | `webapi/transactionStatus` |
| WAITING_FOR_CONFIRMATION / capture | brak — gałąź znika |
| (zwrotów przez API nie mamy) | `transactionRefund`: wymaga salda w Autopay, BLIK max 6 mies. wstecz |

SDK PHP Autopay jest zarchiwizowane (2025) → własna implementacja, co i tak pasuje do modułu.

**Do przepisania:** `class-asiaauto-payu.php` (klient API) i obsługa notyfikacji w `class-asiaauto-payu-api.php`.
**Zostaje:** store prób (idempotencja), kreator, panel, maile, GA4. Szacunek 2–4 dni robocze z testami na
sandboxie — **niezweryfikowany**, do potwierdzenia po wglądzie w sandbox.

## Tpay — dla kontrastu

Publiczna lista wyłączeń Tpay (13 punktów) nie zawiera pojazdów, zaliczek ani dropshippingu — na papierze
łagodniejsza. https://tpay.com/user/assets/files_for_download/zalacznik-nr-1-lista-towarow-i-uslug-wylaczonych.pdf
Pełnego rozpoznania Tpay nie robiliśmy.

## Co przygotować na stronie PRZED wnioskiem (u któregokolwiek operatora)

1. Publiczny opis modelu: usługa importu na zlecenie (umowa agencyjna), za co jest depozyt 6 150 zł, kiedy
   płacisz, na co się zalicza, kiedy i jak wraca, faktura.
2. Proces krok po kroku z czasem realizacji: katalog → kreator → umowa → depozyt → import → odbiór.
3. Jasne zdanie „nie trzymamy aut na stanie — kupujemy wskazane auto w imieniu klienta na podstawie umowy”
   (odpowiedź na „dropshipping”).
4. Informacja o płatności widoczna na stronie oferty, przed kreatorem.
5. Regulamin z nazwą operatora + checkbox akceptacji w kreatorze; finalny przed zgłoszeniem.
6. Konto testowe dla analityka ze ścieżką do kroku płatności (zamówienie 410903).
7. `/finansowanie/`: nie może wyglądać na pośrednictwo w leasingu.
