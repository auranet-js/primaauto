# T-121 — Płatność online (BLIK/karta) za depozyt rezerwacyjny

> Status: **GATED na T-221 (regulamin usługi) → potem wniosek do PayU** · Rozmiar: L
> Godziny realnie: **24–32 h** (Janek ~5–6 h, AI ~19–26 h) · Rynkowo: 60–80 h

## ⬆️ Decyzje 2026-07-27 (Janek) — nadpisują części opisu niżej

| # | Decyzja | Wpływ |
|---|---|---|
| D1 | **Przycisk PayU pojawia się już przy statusie `potwierdzone`** (krok 3 kreatora, „Dane do umowy") i zostaje widoczny w kroku 4, dopóki depozyt nie jest opłacony. Przelew zostaje jako alternatywa | zmienia plan §6 („UI w kroku 4") — płatność wchodzi **przed** umową |
| D2 | Pod przyciskiem komunikat: **„Zgłoszenia z opłaconym depozytem zwrotnym realizujemy w pierwszej kolejności"** + link „Poczytaj więcej" → nowa strona `/depozyt/` (T-221) | nowy element UI + zależność treściowa |
| D3 | **Dotyczy wyłącznie depozytu ZWROTNEGO** — stała kwota z configu (dziś 6 150 zł), niezależna od ceny auta, zamrażana na zamówieniu (`class-asiaauto-order.php:452`), czyli **przed** krokiem 3 — spójne z D1. ⚠️ **Nie mylić z depozytem zabezpieczającym** z umowy leasingowej (% wartości auta, w Agw Moto 10% = 23 200 zł) — ten żyje w treści umowy, nie w PayU. Przy realizacji umowy depozyt zwrotny zostaje odliczony od zabezpieczającego. Rozpiska: [T-217](T-217-umowa-leasingowa-szablon.md) | wymóg 🔒 bez zmian; PayU pobiera **tylko** depozyt zwrotny |
| D4 | **Blocker regulaminowy potwierdzony pomiarem, nie przypuszczeniem** — dzisiejszy `/regulamin/` mówi, że usługi są *nieodpłatne*; nie ma dokumentu regulującego usługę, depozyt ani zwrot. → **T-221 idzie pierwsze** | „Bloker #2 — regulamin" niżej awansuje na #1 |
| D5 | Zwrot depozytu = **model kosztowy** (§4 ust. 4–5 umowy: 3 dni bez kosztów / 7 dni po potrąceniu), **dla sytuacji, w której nie dojdzie do rezerwacji auta** — czyli dokładnie dla momentu, w którym klient płaci online (krok 3, przed rezerwacją). Etapy po rezerwacji reguluje umowa, regulamin ich nie dubluje | określa zakres procedury refundu we wniosku do PayU |

Szczegóły: [T-221](T-221-pakiet-prawny-payu.md), [punkty dla Ruslana](../biznes/2026-07-27-punkty-do-weryfikacji-ruslan.md).

## Po co

Klient wpłaca depozyt od razu w kreatorze, zamiast przepisywać numer konta do bankowości. Szybsze domknięcie rezerwacji, mniej porzuceń na ostatnim kroku.

## Kwota depozytu — ZMIENNA, nie hardkodować

Depozyt liczy `calculateDeposit()` z **konfiguracji w panelu** (`asiaauto_order_config`: `deposit_percent`, `deposit_min`, `deposit_rounding`).

**To są nastawy operacyjne, które Ruslan zmienia sam — także ad hoc, pod konkretną umowę, i potem cofa.** To samo dotyczy prowizji (`contract_commission_percent` / `_min`). Odczyt tych opcji w danej chwili to **migawka, nie parametr biznesowy projektu**.

Stan na 2026-07-14 (dla orientacji, nie do wyceny): `deposit_percent = 0`, `deposit_min = 6150` → efektywnie 6 150 zł; na 135 zamówień 134 ma tę kwotę.

⚠️ **Dwie pułapki, w które NIE wchodzimy:**
1. `configDefaults()` (`class-asiaauto-order.php:296-310`) deklaruje *10% / min 30 000 zł* — to wartości fabryczne, **produkcja ich nie używa**. Nigdy nie wyceniaj z kodu.
2. Odczytana dziś kwota **nie jest stała** — jutro może być inna, bo Ruslan tak ustawi.

### 🔒 Wymóg: zamrożenie kwoty na zamówieniu (zabezpieczenie przed rozjazdem)

Ta sama kwota pojawia się dziś w **czterech** miejscach: panel → umowa PDF (§1 ust. 3) → mail do klienta (`status_podpisane`) → karta przelewu w kreatorze. Płatność online będzie **piątym**.

**Ryzyko:** Ruslan zmienia nastawę w panelu, gdy zamówienie jest w połowie procesu → klient ma umowę na jedną kwotę, a link do zapłaty na inną. Przy zwrotnym depozycie to spór, nie drobiazg.

**Rozwiązanie (wymóg, nie opcja):**
- Kwota jest **zamrażana w momencie generowania umowy** i zapisywana na zamówieniu (`META_DEPOSIT_AMOUNT` — **już istnieje**, `class-asiaauto-order.php:113`, ustawiana przy `create()`).
- **Płatność PayU czyta kwotę z zamówienia (`_order_deposit_amount`), NIE z bieżącej konfiguracji panelu.**
- `calculateDeposit()` woła się tylko raz — przy powstaniu/aktualizacji zamówienia przed umową.
- Zmiana nastawy w panelu dotyczy **wyłącznie nowych** zamówień.

**Zero kwot w kodzie, zero kwot w konfiguracji PayU.**

## ⚠️ Bloker #1 — zgoda PayU (ryzyko obniżone)

**To ryzyko regulaminowe, nie kwotowe.** Rząd wielkości depozytu (kilka–kilkanaście tys.) mieści się w normalnych limitach BLIK-a i karty.

Problemem jest **charakter** płatności:
- **Depozyt jest zwrotny** — w razie niepowodzenia zakupu wraca do klienta.
- **Model to pośrednictwo**, nie sprzedaż towaru (`docs/legal/`, ADR o modelu agencyjnym).

PayU takie schematy akceptuje, ale wymaga opisania ich w umowie akceptanta, a zwroty muszą mieć jasną procedurę. **Ryzyko: „trzeba dobrze opisać", nie „mogą odmówić".**

**Krok zero — pytamy PayU o SCHEMAT I WIDEŁKI, nie o kwotę:** depozyt zwrotny, model pośrednictwa, transakcje rzędu kilku–kilkunastu tysięcy, kwota ustalana per zamówienie z panelu. ~1–2 h Janka + czekanie.

## Bloker #2 — regulamin ✅ ZDJĘTY 2026-07-27 (T-221)

Regulamin usługi i strona o depozycie **napisane i opublikowane** (`/regulamin-uslugi/`, `/depozyt/` — na razie `noindex`, bez linków). Procedura zwrotu z terminami 3/7 dni siedzi pod kotwicą `primaauto.com.pl/regulamin-uslugi/#zwrot` — ten adres podajemy we wniosku. Bez prawnika, minimum funkcjonalne (decyzja Janka). Zostaje: zdjęcie noindex + podlinkowanie, PayU w polityce prywatności — patrz T-221.

## Stan faktyczny

| Element | Stan |
|---|---|
| **WooCommerce** | ❌ **NIE MA.** Zero e-commerce frameworka. Cały lifecycle zamówienia jest autorski (CPT `asiaauto_order`) |
| Jakikolwiek gateway w kodzie | ❌ zero (grep: payu, przelewy24, stripe, tpay, blik → brak) |
| Creds PayU | ✅ `~/secrets/payu/sandbox.env` + `prod-wns.env` (z innego projektu) |
| Jak klient płaci dziś | Ręczny przelew — statyczna karta z numerem konta w kroku 4 kreatora (`class-asiaauto-order-wizard.php:286-313`) + upload potwierdzenia. Konto: Alior Bank, Prima-Auto Ruslan Prima |
| **Miejsce wpięcia webhooka** | ✅ **`markDepositPaid()`** (`class-asiaauto-order.php:927-946`) — gotowa funkcja, dziś wołana tylko z admina |
| Status „czeka na depozyt" | `podpisane` + meta `_order_deposit_paid = '0'` (nie ma osobnego statusu) |
| Stan bazy | 120 zamówień bez depozytu, 15 z opłaconym; 134 × 6 150 zł |

**Konsekwencja braku WooCommerce: nie ma gotowej wtyczki. Piszemy integrację w całości sami.** To jest źródło tych 24–32 h.

## Plan (kroki)

**Etap 0 — spike (3–4 h, PRZED wyceną wdrożenia)**
1. Zapytanie do PayU o dopuszczalność (depozyt zwrotny, 30 tys., pośrednictwo).
2. Test sandbox: utworzenie zamówienia PayU + odbiór notyfikacji na testowym endpoincie. Potwierdzenie, że BLIK i karta działają w tym schemacie.

**Etap 1 — wdrożenie (24–32 h)**
3. Klasa `AsiaAuto_PayU` — OAuth, tworzenie zamówienia (`POST /api/v2_1/orders`), weryfikacja podpisu notyfikacji.
4. REST: `POST /order/{id}/pay` → tworzy płatność, zwraca `redirectUri`. Guard: tylko właściciel zamówienia, tylko status `podpisane`, tylko gdy `_order_deposit_paid = 0`.
5. **Webhook notyfikacji** → weryfikacja podpisu → `markDepositPaid()`. **Idempotentnie** (PayU potrafi wysłać notyfikację kilka razy — podwójne zaksięgowanie = katastrofa).
6. UI w kroku 4 kreatora: przycisk „Zapłać BLIK/kartą" **obok** danych do przelewu (nie zamiast — zostawiamy wybór; przelew przy 30 tys. bywa tańszy dla klienta).
7. Statusy płatności: oczekująca / opłacona / odrzucona / zwrócona. Ekran powrotu z PayU.
8. **Obsługa zwrotu depozytu** — refund przez API albo świadoma decyzja „zwroty ręcznie przelewem" (do rozstrzygnięcia z Ruslanem; refund automatyczny to +6–8 h).
9. Log każdej operacji + panel w adminie (podgląd płatności per zamówienie).

## Strefy kruche

- **`class-asiaauto-order.php` — strefa krucha** (statusy, rezerwacje). Dotykamy **wyłącznie** `markDepositPaid()`, dokładając `do_action('asiaauto_deposit_paid', ...)`. **Nie zmieniamy** `TRANSITIONS`, `LISTING_RESERVATION_MAP`, `calculateDeposit()`.
- ⚠️ **Znaleziona luka (do zaadresowania przy okazji):** przejście `podpisane → zarezerwowane` **nie jest dziś blokowane brakiem depozytu** — admin może zarezerwować auto bez wpłaty. Przy płatności online warto dodać guard (albo świadomie zostawić — decyzja Janka).

## Testy

**Automatyczne**
- Weryfikacja podpisu notyfikacji: poprawny → księguje; zmanipulowany → odrzuca (to jest krytyczne, bez tego ktoś oznaczy sobie depozyt jako wpłacony).
- **Idempotencja:** ta sama notyfikacja 3× → `markDepositPaid()` wykonuje się raz.
- Guard: próba zapłaty za cudze zamówienie → 403.
- **🔒 Test spójności kwoty (kluczowy):** wygeneruj umowę → **zmień nastawę depozytu w panelu** → otwórz płatność. Kwota w PayU musi być **ta z umowy**, nie nowa z panelu. Sprawdź komplet: umowa PDF = mail = karta przelewu = płatność online.

**Półautomatyczne**
- Pełny przebieg w sandboxie: BLIK (kod testowy), karta (test 3DS), płatność odrzucona, timeout.
- Test kwoty granicznej: 30 000 zł (minimum) i ~100 000 zł (10% z drogiego auta) — czy PayU nie odbija.

**MCP (Chrome)**
- Kreator krok 4 → „Zapłać BLIK" → redirect do PayU (sandbox) → powrót → status zamówienia zaktualizowany, w adminie widać płatność.
- Mobile: czy redirect i powrót nie gubią sesji.

## Definicja zrobionego

- PayU **pisemnie potwierdził** dopuszczalność schematu (bez tego nie startujemy).
- Regulamin zaktualizowany.
- Klient płaci BLIK/kartą w kreatorze; przelew nadal dostępny jako alternatywa.
- Webhook idempotentny, podpis weryfikowany.
- Depozyt księguje się automatycznie, admin widzi historię płatności.
