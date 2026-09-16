# 2026-09-16 — Bramka płatności za depozyty: porównanie i wybór operatora

> Kontekst: [ADR 2026-09-14 — PayU odmówiło](../decyzje/2026-09-14-payu-odmowa-wylaczenie-bramki.md) ·
> [Autopay — rozpoznanie 14.09](2026-09-14-autopay-rozpoznanie.md) · [T-121](../roadmapa/T-121-platnosc-online-depozyt.md)
> Źródła: publiczne strony i regulaminy operatorów, odczyt 16.09.2026. Z żadnym operatorem się nie kontaktowaliśmy.
> Stawki to cenniki publiczne, nie oferta — potwierdzić przy rejestracji. Brak źródła = „niezweryfikowane”.

> **Decyzja Janka 16.09.2026: Tpay Business.** Zapasowy: Przelewy24.

## 1. Tabela porównawcza

Założenia: depozyt **6 150 zł** (`asiaauto_order_config.deposit_min`, odczyt 16.09), **7 depozytów/mies.** (z bazy, niżej),
metody **BLIK + szybki przelew** (karty później — decyzja Janka 16.09), konto firmowe zostaje w **Alior Banku** (decyzja Janka 16.09).
Promocje „Polska Bezgotówkowa” pominięte — Prima-Auto prawdopodobnie podpisało umowę z PayU (Janek 16.09).

| Operator | 1 depozyt | /mies. przy 7 (10 · 15) | Zwrot | Ryzyko odmowy | Onboarding w imieniu klienta | Źródło |
|---|---|---|---|---|---|---|
| **Tpay Business** (99 zł/mies. + BLIK 0,99% + 0,02 zł, przelew 0,99%) | **60,91 zł** | **525 zł** (708 · 1 013) | 0 zł; prowizja nie wraca; wymaga salda | **niskie** — lista 14 zakazów bez aut, importu, zaliczek, dropshippingu, pośrednictwa | Janek wypełnia; Ruslan: dowód + opłata aktywacyjna 1 zł z Aliora | [cennik](https://tpay.com/oferta) · [lista](https://tpay.com/user/assets/files_for_download/zalacznik-nr-1-do-owu-lista-towarow-i-uslug-zabronionych.pdf) · [OWU](https://tpay.com/user/assets/files_for_download/owu.pdf) |
| **Przelewy24** (1,29% + 0,30 zł, bez abonamentu) | 79,64 zł | 557 zł (796 · 1 195) | 0 zł; czy prowizja wraca — niezweryfikowane; wymaga salda | **niskie** — lista 18 przykładów bez aut, zaliczek, pośrednictwa | Janek wypełnia; Ruslan: skan dowodu + przelew aktywacyjny 59 zł (zwracany po 10 transakcjach w 2 mies.) | [cennik](https://www.przelewy24.pl/oferta/tabela-prowizji-i-oplat) · [OWU](https://www.przelewy24.pl/storage/app/media/dokumenty/wzor_owu_wrzesien_2024.pdf) |
| Autopay Starter (0% do 5 500 zł obrotu, powyżej 1,1%; opłata 29,99–49,99 zł/mies.) | ok. 67,65 zł (krańcowo) | **413–463 zł** (616–666 · 954–1 004) | 0 zł; prowizja nie wraca; BLIK do 6 mies. | **średnie** — „dropshipping” i „pośrednictwo w usługach finansowych” na liście | Janek wypełnia; Ruslan: fotoweryfikacja / mObywatel + przelew 49 zł | [landing](https://lp.autopay.pl/oferta-abonamentowa/) · [lista EN](https://developers.autopay.pl/en/online/list-of-prohibited-products-and-industries) · [zwroty](https://developers.autopay.pl/online/faq/rozliczenia) |
| Autopay Standard (1,19% + 0,34 zł) | 73,53 zł | 515 zł (735 · 1 103) | jw. | średnie — jw. | jw. | [cennik](https://autopay.pl/oferta/platnosci-online) |
| Paynow (mBank, 0,95%) | 58,43 zł | 409 zł (584 · 876) | 0 zł; prowizja nie wraca; 180 dni | niskie — lista 26 pkt bez aut | **wymaga konta firmowego w mBanku** → odpada (decyzja: zostajemy przy Aliorze) | [cennik](https://www.paynow.pl/cennik) · [lista](https://www.mbank.pl/pdf/firmy/inne/towary-i-uslugi-zakazane-paynow.pdf) |
| ING Pay (d. imoje; BLIK 1,59%, przelew 1,40%) | 86,10–97,79 zł | 603–685 zł | prowizja nie wraca; regulamin kartowy: zwrot tylko przy „zwrocie Towaru lub Usługi” | średnie — regulamin pisany pod „umowę sprzedaży” | **wymaga konta firmowego w ING** → odpada | [centrum pomocy](https://www.ing.pl/bramka-platnicza-ing-pay/centrum-pomocy) · [regulamin](https://www.ing.pl/_fileserver/item/e1zjamv/v3) |
| Stripe (BLIK 1,6% + 1 zł) | 99,40 zł | 696 zł | 0 zł; prowizja nie wraca | **wysokie** — P24 w Stripe wyklucza „Automotive sales”, MCC 5511 restricted; wypłaty D+3 (pierwsza po 7 dniach) | reprezentantem musi być Ruslan, agencja tylko jako członek zespołu | [cennik](https://stripe.com/pl/pricing/local-payment-methods) · [P24](https://docs.stripe.com/payments/p24) |
| Tpay Starter (BLIK 1,59% + 0,41 zł) | 98,20 zł | 687 zł | 1 zł | niskie | jak Tpay Business, aktywacja 99 zł | [cennik](https://tpay.com/oferta) |
| CashBill | niezweryfikowane (stawki niepubliczne, „Zapytaj o prowizję”) | — | opłata z niepublicznego załącznika | niezweryfikowane — lista zakazów niepubliczna | — | [regulamin](https://www.cashbill.pl/download/regulaminy/Regulamin_Serwisu.pdf) |
| Espago | tylko karty, stawka indywidualna | — | — | — (poza zakresem: bez BLIK) | — | [espago](https://espago.com/pl/espagopay) |
| Alior Bank (bank klienta) | **brak bramki online** — tylko terminal stacjonarny przez Polskie ePłatności | — | — | — | — | [usługi dla firm](https://www.aliorbank.pl/wlasna-dzialalnosc/uslugi.html) |

**Uwagi do liczb:**
- Autopay Starter: landing podaje „Minimalna opłata miesięczna 29,99 PLN” (płatność roczna), kod strony cennika — 49,99 zł przy płatności miesięcznej. Nie wiadomo, czy to abonament doliczany do prowizji (463 zł), czy minimum pokrywane prowizją (413 zł). Do potwierdzenia przy rejestracji.
- Tpay: PDF pakietów z 01.2026 podaje BLIK w Business „0,99%” bez 0,02 zł — liczone według strony (droższy wariant). Pakiet 99 zł pobierany tylko w miesiącach z transakcjami (OWU). Netto/brutto — niezweryfikowane.
- Tpay Business wychodzi taniej niż Przelewy24 od **6 depozytów w miesiącu** (próg 5,3). Przy 5 i mniej tańsze jest P24.
- „TpayGO” (0,99% + 0,39 zł) z cennika Tpay to terminal w telefonie do płatności na miejscu — nie dotyczy płatności online.
- Limity kwot: żaden operator nie publikuje limitu, który blokowałby 6 150 zł; praktycznym limitem jest **limit BLIK w banku płacącego** (Paynow w FAQ: standardowo 1–3 tys. zł). Dlatego szybki przelew musi być w bramce obok BLIK.
- Zwrot prowizji: u **żadnego** operatora ze źródłem prowizja przy zwrocie nie wraca. Przy naszym modelu to margines (niżej).

## 2. Dane z bazy (tylko SELECT, 16.09.2026)

Zamówienia `asiaauto_order` typu `customer` (zamówienia `stock` — 103 szt. — to zakupy Ruslana na plac, bez depozytu klienta).
Liczone po dacie wpłaty (`_order_deposit_paid_at`).

| Miesiąc | Opłacone depozyty | W tym anulowane po wpłacie | Źródło |
|---|---|---|---|
| czerwiec | 7 | 2 | brak meta (sprzed wprowadzenia `_source`) |
| lipiec | 6 | 0 | brak meta |
| sierpień | 7 (+1 testowy za 1 zł) | 1 | 5× `manual` |
| wrzesień (do 16.09) | 3 | 0 | 3× `manual` |

- **Średnio ~7 depozytów/mies. = ok. 43 tys. zł obrotu przez bramkę**, jeśli wszyscy zapłacą online. Realnie część klientów zostanie przy przelewie, więc koszt w tabeli to górna granica.
- Kwoty: 214 × 6 150 zł, 1 × 7 000 zł, 2 × 1 zł (testy). Rozkładu praktycznie nie ma.
- Online: 0 (PayU nigdy nie przyjęło płatności — POS zablokowany).
- **Zwroty:** meta zwrotu w bazie nie istnieje. Janek (16.09): depozyty wpłacone przelewem przy anulowanych zamówieniach **raczej nie były zwracane**; wyjątek to zamówienia testowe (Andrzej, Wiktor). → zwroty traktujemy jako margines, koszt utraconej prowizji pomijalny.
- **Flaga depozytu jest niepełna:** zamówienia w statusach `w_drodze` / `na_placu` / `zakonczone` bez `_order_deposit_paid = 1` to w większości zamówienia `stock`; u klientów flaga zgadza się ze statusem (odchyłka 1 zamówienie we wrześniu).
- **Sygnał porzuceń:** 14 zamówień klientów od czerwca stoi w `potwierdzone` / `umowa_gotowa` / `podpisane` bez depozytu (czerwiec 3, lipiec 4, sierpień 2, wrzesień 5 — wrześniowe mogą być jeszcze w toku). Nie rozróżnimy porzucenia od zwykłego czekania.

## 3. Rekomendacja

**Główny: Tpay Business.** Najtańszy z operatorów, u których ryzyko odmowy jest niskie. Lista zakazów nie ma punktu, pod który da się podciągnąć import aut czy depozyt, a „dropshipping”, który grozi nam w Autopay, tu nie występuje. Technicznie najbliżej PayU: REST/JSON z tokenem OAuth, BLIK Level 0 na naszej stronie, API zwrotów, **sandbox bez konta produkcyjnego** — przepięcie warstwy API możemy przetestować, zanim Ruslan cokolwiek podpisze.

**Zapasowy: Przelewy24.** Równie czysta lista zakazów, bez abonamentu (przy ≤5 depozytach w miesiącu wręcz tańszy), REST/JSON, BLIK Level 0 przez `chargeByCode`. Minusy: sandbox dopiero po założeniu konta produkcyjnego, o ~32 zł/mies. drożej przy 7 depozytach.

**Dlaczego nie Autopay, choć na papierze jest o 60–110 zł/mies. tańszy:** „dropshipping” na liście zakazanych trafia dokładnie w pytanie, na którym poległo PayU („za co klient płaci, skoro auta nie ma”). Do tego zatwierdzonego regulaminu nie wolno potem zmieniać, API jest starsze (hash SHA256, odpowiedzi XML), a sandbox dostajemy dopiero po założeniu konta. Powtórka odmowy kosztowała miesiąc i trzech odbitych klientów — to więcej niż roczna różnica w prowizji (~700–1 300 zł).

**Paynow** byłby najtańszy (409 zł/mies.), ale wymaga konta w mBanku — odrzucone decyzją Janka.

## 4. Alternatywa bez operatora (dla kontrastu)

| Wariant | Koszt | Wygoda klienta | Księgowanie | Uwagi |
|---|---|---|---|---|
| Dziś: przelew zwykły na Alior | 0 zł | przepisuje numer, dane w kreatorze | ręczne (Ruslan) | działa od 28.08 |
| + kod QR 2D (standard ZBP) w kreatorze | 0 zł | skan w aplikacji banku (Alior Mobile potwierdzone, inne banki niezweryfikowane) | ręczne | tylko wygoda, kod do dopisania w kreatorze |
| + rachunki wirtualne Alior MPT (numer per zamówienie) | Light: 500 zł wdrożenie + ok. 60 zł abonamentu + 10 zł generator + 0,80 zł/wpłata (+ raport 30 zł) | jak przelew | automatyczne dopasowanie po numerze konta, raport MT940/XML | [MPT](https://www.aliorbank.pl/przedsiebiorstwa/rachunki/uslugi-do-rachunkow/mpt.html) · [tabela](https://www.aliorbank.pl/dam/jcr:51e6a9e2-c34c-4f67-91d0-df74f6490d76/tabela-oplat-i-prowizji.pdf); stawka zależy od typu rachunku Prima-Auto (niezweryfikowane) |
| + odczyt konta przez agregator PSD2 (Enable Banking, „restricted mode”) | 0 zł (niezweryfikowane, czy dozwolone komercyjnie) | jak przelew | automatyczne | wymaga integracji, której nie znamy — **nie rekomendujemy** |
| BLIK na telefon | — | — | — | Alior: tylko konta osobiste, nie firmowe; mieszanie rachunków — **odradzamy** |

Wniosek: bez operatora nie da się dać klientowi płatności „jednym kliknięciem” — a to był cel T-121 (mniej porzuceń na ostatnim kroku). Rachunki wirtualne rozwiązują tylko ręczne księgowanie, nie wygodę.

## 5. Paczka do rejestracji — Tpay Business

**Formularz:** https://tpay.com/oferta → „Załóż konto” (pakiet Business). Sandbox do testów integracji wcześniej i osobno: https://register.sandbox.tpay.com/

**Dane klienta (z `asiaauto_order_config`):**
- Prima-Auto Ruslan Prima, JDG · NIP 8133898576 · REGON 525442846
- ul. Jerzego Pleśniarowicza 2A lok. 38, 35-117 Rzeszów
- Reprezentant: Ruslan Prima
- Rachunek do wypłat: Alior Bank, `17 2490 1057 0000 9900 2270 3049` (ten sam, na który klienci robią dziś przelewy; musi być na białej liście VAT)
- **E-mail konta w Tpay (login, powiadomienia, kontakt): china@primaauto.com.pl** (Janek 16.09)
- Telefon: +48 721 730 507
- Sklep: https://primaauto.com.pl

**Dokumenty** (OWU §3 + [FAQ Tpay](https://support.tpay.com/faq/jakie-dokumenty-sa-potrzebne-do-weryfikacji-konta-w-serwisie-tpay-com)):
dowód osobisty lub paszport Ruslana · wydruk CEIDG · oświadczenie o beneficjencie rzeczywistym · dokument uprawnienia do domeny primaauto.com.pl · (jeśli dotyczy) dokument potwierdzający prawo do reprezentacji.

**Kroki:**
1. Janek zakłada konto i wypełnia dane firmy, opis działalności, adres strony.
2. **Ruslan:** przesyła skan dowodu i robi przelew aktywacyjny **1 zł z konta w Aliorze** (weryfikuje rachunek do wypłat). Formę weryfikacji tożsamości (fotoweryfikacja / mObywatel / skan) i to, czy Janek może działać na pełnomocnictwo — **niezweryfikowane**, wyjdzie w formularzu.
3. Wybór metod: **BLIK + przelewy online**. Karty — osobny wniosek później.
4. Oczekiwanie na weryfikację — Tpay deklaruje „nawet do 24 godzin” (blog Tpay, nie regulamin).

**Opis działalności do wniosku (propozycja):**
> Prima-Auto sprowadza samochody z Chin na zlecenie klientów indywidualnych i firm. Klient wybiera konkretny egzemplarz w katalogu na primaauto.com.pl, zgłasza go w kreatorze i podpisuje umowę zlecenia. Działamy jako agent klienta: nie sprzedajemy aut z cudzych ofert, tylko kupujemy wskazane auto w imieniu klienta, na podstawie umowy, i organizujemy transport, odprawę, homologację i rejestrację. Przez bramkę klient wpłaca wyłącznie **zwrotny depozyt** (dziś 6 150 zł), który rozpoczyna naszą usługę: weryfikację egzemplarza i rezerwację u sprzedawcy. Na depozyt wystawiamy fakturę VAT; jeśli do zakupu nie dojdzie nie z winy klienta, depozyt wraca w 3 dni robocze (lub w 7 dni po potrąceniu udokumentowanych kosztów). Dalsze płatności za auto idą przelewem według harmonogramu z umowy, poza bramką. Część modeli mamy również na placu w Rzeszowie — te sprzedajemy bezpośrednio, bez depozytu przez bramkę.

Ostatnie zdanie jest celowe: strona główna mówi o autach „od ręki na placu”, więc zdanie „nie trzymamy aut na stanie” analityk sprawdzi w 10 sekund i uzna za nieprawdę.

**Strony do wskazania analitykowi:**
- model i depozyt: https://primaauto.com.pl/depozyt/
- regulamin usługi z procedurą zwrotu: https://primaauto.com.pl/regulamin-uslugi/#zwrot
- proces: https://primaauto.com.pl/informacje/proces-zamawiania/
- przykładowe oferty (≥5 z ceną i zdjęciem): https://primaauto.com.pl/samochody/
- polityka prywatności: https://primaauto.com.pl/polityka-prywatnosci/
- kontakt: https://primaauto.com.pl/kontakt/
- ścieżka do kroku płatności: zamówienie testowe 410903 (`potwierdzone`), dziś na koncie `auranet` — dla analityka założyć osobne konto testowe

## 6. Warunki na stronie przed wnioskiem — stan 16.09

Lista z [rozpoznania Autopay](2026-09-14-autopay-rozpoznanie.md#co-przygotować-na-stronie-przed-wnioskiem-u-któregokolwiek-operatora), sprawdzona na produkcji (curl, 16.09).

| # | Warunek | Stan | Dowód |
|---|---|---|---|
| 1 | Publiczny opis modelu i depozytu (za co, ile, kiedy wraca, faktura) | ⚠️ **treść jest, ale ukryta** — `/depozyt/` opisuje zwrot 3/7 dni, fakturę VAT, zwrot tym samym kanałem; strona ma `noindex, nofollow` i **0 linków** ze strony głównej, oferty, procesu, finansowania | https://primaauto.com.pl/depozyt/ |
| 2 | Proces krok po kroku z czasem realizacji | ⚠️ **sprzeczny** — strona procesu podaje 80–120 dni, ale mówi o „**30% zaliczki**”, nie o depozycie 6 150 zł; to samo `/informacje/finansowanie/` („30% zaliczki przy podpisaniu umowy”) | https://primaauto.com.pl/informacje/proces-zamawiania/ |
| 3 | Zdanie o modelu agencyjnym (odpowiedź na „dropshipping”) | ⚠️ częściowo — strona główna: „klient wpłaca depozyt, a auto sprowadzamy na jego rzecz”; brak jasnego „kupujemy wskazane auto w imieniu klienta na podstawie umowy”. Jednocześnie: „Część modeli mamy dostępnych od ręki na placu” | https://primaauto.com.pl/ |
| 4 | Informacja o płatności na stronie oferty, przed kreatorem | ❌ **brak** — na ofercie jest tylko „Umowa agencyjna”, bez depozytu i metod płatności | np. https://primaauto.com.pl/oferta/nio-et5-touring-2024-481958/ |
| 5 | Regulamin usługi z operatorem + checkbox akceptacji w kreatorze | ⚠️ **checkbox linkuje do złego regulaminu** — kreator: „Akceptuję regulamin serwisu” → `/informacje/regulamin/`, który mówi, że usługi są „nieodpłatne”; regulamin usługi z depozytem (`/regulamin-uslugi/`, noindex) nie jest akceptowany w kreatorze. Nazwa operatora w regulaminie: ogólna („operator płatności online”) — do uzupełnienia po wyborze | `class-asiaauto-order-wizard.php:210` |
| 6 | Konto testowe dla analityka ze ścieżką do płatności | ⚠️ zamówienie 410903 istnieje (`potwierdzone`), ale na koncie `auranet` — brak osobnego konta dla operatora | baza |
| 7 | `/finansowanie/` nie wygląda na pośrednictwo w leasingu | ✅ brak sformułowań o pośrednictwie/leasingodawcach (grep) — ale patrz pkt 2 („30% zaliczki”) | https://primaauto.com.pl/informacje/finansowanie/ |
| — | Regulamin usługi: „Maksymalny czas realizacji zamówienia: 20 dni roboczych” vs proces 80–120 dni | ⚠️ do ujednolicenia lub doprecyzowania (co liczy się jako „zamówienie”) | https://primaauto.com.pl/regulamin-uslugi/ |

Zmiany na stronie to osobna decyzja — tu tylko lista. Kolejność sensowna przed wnioskiem: 5 (checkbox) → 2 (zaliczka vs depozyt) → 1 (zdjąć noindex i podlinkować `/depozyt/`) → 4 (depozyt na ofercie) → nazwa operatora w regulaminie.

## 7. Technika — szacunek (niezweryfikowany do sandboxa)

| PayU (dziś w kodzie) | Tpay |
|---|---|
| OAuth → Bearer | OAuth → Bearer ([docs](https://docs-api.tpay.com/pl/)) |
| `POST /api/v2_1/orders` JSON | `POST /transactions` JSON |
| BLIK `BLIK_AUTHORIZATION_CODE` | BLIK Level 0 — kod na naszej stronie ([docs](https://docs-api.tpay.com/pl/payment-methods/blik/)) |
| notify JSON + podpis w nagłówku | notyfikacja POST form-urlencoded, **podpis JWS** — do przepisania weryfikacja |
| retrieve order | `GET /transactions/{id}` |
| — | zwroty `POST /transactions/{id}/refunds` ([docs](https://docs-api.tpay.com/pl/refunds/)) |

Do wymiany jak w ADR 14.09: `class-asiaauto-payu.php` + obsługa notyfikacji w `class-asiaauto-payu-api.php`. Store prób, kreator, panel, maile, GA4 — zostają. Szacunek: bliżej dolnej granicy 2–4 dni niż Autopay (JSON zamiast XML/hash) — **niezweryfikowane**.

## Do Ruslana

Po quizie z Jankiem (16.09) zostały tylko kroki wykonawcze, bez pytań otwartych: skan dowodu + przelew 1 zł z Aliora przy rejestracji. Sprawdzić w formularzu Tpay: czy wymagają fotoweryfikacji i czy Janek może działać jako pełnomocnik.
