# 2026-09-14 — PayU odmówiło współpracy: bramka wyłączona, szukamy innego operatora

> **Status:** decyzja Janka 14.09.2026 — **PayU odpada definitywnie**, bramka wyłączona na produkcji
> **Dotyka:** opcja `asiaauto_payu_enabled`, moduł `includes/class-asiaauto-payu*.php` (zostaje w kodzie),
> treści: mail `status_potwierdzone`, strona `/depozyt/` (post 390646), `/regulamin-uslugi/` (post 390645)
> **Zastępuje:** decyzję z 25.08.2026 „bramka zostaje włączona, żeby PayU mogło zweryfikować ścieżkę zakupową”
> **Powiązane:** [T-121](../roadmapa/T-121-platnosc-online-depozyt.md), [T-221](../roadmapa/T-221-pakiet-prawny-payu.md),
> memory `project_payu_pos_zablokowany_2026_08_25.md`

## Co się stało

| Data | Zdarzenie |
|---|---|
| 06.08 | PayU wydaje produkcyjne dane POS 4436802 (`~/secrets/payu/prod-primaauto.env`) |
| 11.08 | AML PayU (K. Rogalska) żąda dokumentów beneficjenta i poprawek na stronie (regulamin, polityka prywatności) |
| 18.08 | Ruslan odpisuje, że poprawione. PayU: *„prosimy o opisanie modelu biznesowego na stronie oraz o informację, za co będą odbywać się płatności, ponieważ aktualnie nie widzimy możliwości zakupu usług ani produktów fizycznych”* |
| 20.08 | Bramka włączona w trybie `prod`, mimo że POS jest zablokowany |
| 21.08–31.08 | Trzech klientów odbitych od bramki (zam. 423100, 438216, 453465 — depozyty po 6 150 zł), wszystkie 19 metod `DISABLED`. Ruslan księguje depozyty ręcznie z przelewów |
| 14.09 | **PayU odmawia współpracy** (informacja od Janka). Treści ani uzasadnienia odmowy nie mamy w repo ani w skrzynce `claude@auratest.pl` |

**Najbardziej prawdopodobna przyczyna** (wniosek z maila z 18.08, nie potwierdzony przez PayU):
analityk AML nie widział, za co klient płaci. Blok płatności pojawia się dopiero w kroku 3–4 kreatora,
po założeniu zgłoszenia, a model agencyjny z depozytem zwrotnym nie jest opisany na stronie jako „usługa
z ceną”. Ten sam scenariusz grozi u następnego operatora, jeśli strona się nie zmieni.

## Decyzja

1. **PayU wyłączone na produkcji** 14.09.2026: `asiaauto_payu_enabled` `1 → 0` (`mode` zostaje `prod`, bez znaczenia przy fladze OFF).
   - Kreator: bloki `step3`/`step4` pokazują płatność online jako nieaktywną („uruchamiamy”) plus dane do przelewu — patrz niżej (v0.40.1).
   - REST: trasy `/order/{id}/pay` i `/order/{id}/payment-status` zdjęte. **`/payu/notify` zostaje celowo**
     (rejestruje się zawsze — spóźniona notyfikacja nie trafi w 404; bez poprawnego podpisu nic nie robi).
   - Przed wyłączeniem: 0 prób w stanie `pending` (`_order_payment_state_*`), więc żadna wpłata nie wisi.
2. **Kod modułu PayU zostaje w pluginie, uśpiony.** Nie kasujemy: store prób płatności (idempotencja na meta stanu),
   blok w kreatorze, historia w panelu, mail „depozyt opłacony” i zdarzenie GA4 są niezależne od operatora
   i mają posłużyć jako szkielet pod nowego operatora. Do wymiany jest warstwa API (`class-asiaauto-payu.php`,
   `class-asiaauto-payu-api.php`).
3. **Depozyty do czasu nowego operatora — wyłącznie przelewem**, księgowane ręcznie w panelu (`_order_deposit_paid_source = manual`) — tak jak Ruslan robi to od 28.08.
4. **Nowy operator:** kandydaci **Autopay** i **Tpay** (wybór Janka). Rozpoznanie Autopay — osobny dokument.
   Warunek wstępny przed złożeniem wniosku u kogokolwiek: strona musi pokazywać model płatności (za co, ile, kiedy wraca) bez przechodzenia kreatora.

## Treści i kreator — po zmianach 14.09 (v0.40.1)

Zasada (Janek): **wycinamy nazwę PayU, nie płatność online.** Kolejny operator przy weryfikacji ma widzieć,
że ścieżka zakupu przewiduje płatność online, a nie „tylko przelew”.

| Miejsce | Przed | Po |
|---|---|---|
| Kreator, kroki 3 i 4 | przy fladze OFF blok znikał | blok „Płatność online — BLIK, karta”, przyciski nieaktywne, „uruchamiamy — do tego czasu przelew” (`renderSoon()`) |
| Mail `status_potwierdzone` | „opłacisz bezpiecznie przez PayU (BLIK, karta, bank) albo przelewem” | „opłacisz online (BLIK, karta) albo zwykłym przelewem” |
| `/regulamin-uslugi/` (post 390645) | „operatora płatności PayU S.A. z siedzibą w Poznaniu” | „operatora płatności online (BLIK, karta płatnicza)”; data „obowiązuje od” bez zmian — nowa wersja z nazwą operatora przed zgłoszeniem (Autopay nie pozwala zmieniać zatwierdzonego regulaminu) |
| `/depozyt/` (post 390646) | „BLIK, karta albo przelew” | bez zmian |

Backup przed zmianami: `~/backups/primaauto/2026-09-14-payu-off/` (flagi, szablony maili, obie strony, pliki pluginu).

## Nie ruszamy

- `~/secrets/payu/prod-primaauto.env` — zostaje, nieużywany.
- Zamówienie testowe 410903 (`potwierdzone`) — zostaje do testu nowego operatora.
- Stopka z linkami do dokumentów prawnych (wynik uwag AML PayU) — zostaje, to wymóg każdego operatora.

## Rollback

`wp option update asiaauto_payu_enabled 1` — przywraca blok i trasy bez deploya (nie ma po co, dopóki POS jest zablokowany).
