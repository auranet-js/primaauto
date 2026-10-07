# T-261 — Flaga [A] „rozliczone z Auranetem” na liście zamówień

> Status: **spec — do budowy** (zgłoszenie Janka 07.10, propozycja ze spotkania z Ruslanem 07.10 §5)
> Godziny realnie: **1,5–2 h** (kod ~1 h + uzupełnienie wsteczne z rejestru ~0,5–1 h)
> Powiązane: `docs/rozliczenia/ruslan.md` (rejestr — zostaje source of truth do czasu pełnego uzupełnienia flag),
> memory `reference_rejestr_rozliczen_ruslan`, `feedback_ruslan_rozliczenie_zawsze_tabelka`.

## Cel

Obok pinów **D** (depozyt) i **C** (CIF) na liście zamówień w panelu pojawia się **A** — zamówienie
rozliczone z Auranetem. Ruslan widzi od razu, co już poszło na fakturę, bez pytania nas.
Flagę ustawiamy **raz w miesiącu z konsoli** (półautomat, jak dziś partie z rejestru) — w panelu
nie ma przycisku, nikt jej nie przełącza ręcznie.

## Stan dziś (zmierzone 07.10)

- Baza **nie ma** żadnego znacznika rozliczenia — jedyny sygnał to `_order_deposit_paid` + `_order_deposit_paid_at`
  (rejestr, sekcja „Jak wyznaczyć kolejną partię”). Istniejącej flagi do wykorzystania nie ma.
- Piny renderuje `class-asiaauto-order-admin.php` w **dwóch** miejscach: tabela desktop (l. ~1308–1317)
  i karty mobilne (l. ~1359–1362). CSS `.aa-pin.is-ok / .is-no` w `assets/css/asiaauto-order-admin.css:168–172`.
- Lista czyta dane przez `AsiaAuto_Order::getOrderData()` — **ta sama funkcja** zasila panel klienta
  (`class-asiaauto-account.php`), maile, umowę PDF, GA4 i bramkę płatności.

## Rozwiązanie

**Dwie nowe meta, tylko dla zamówień:**

| meta | wartość | przykład |
|---|---|---|
| `_order_auranet_settled_at` | data `Y-m-d` | `2026-09-02` |
| `_order_auranet_invoice` | numer FV Auranet | `FS/1/09/2026` |

**Render (jedyna zmiana w kodzie, addytywnie, 2 miejsca):** po pinie C trzeci pin `A`, czytany
`get_post_meta()` **wprost w szablonie listy** — NIE przez `getOrderData()`.
- rozliczone → `is-ok` (zielony), title „Auranet: rozliczone DD.MM.RRRR, FV …”
- brak → pin neutralny (szary, jak C „nie dotyczy”), title „Auranet: nierozliczone” — **bez czerwieni**:
  testowe i anulowane zamówienia nie wchodzą do rozliczeń i świeciłyby na czerwono w nieskończoność.

**Ustawianie:** skrypt `scripts/rozliczenia-oznacz.php` przez `wp eval-file`:
`ids=… data=RRRR-MM-DD fv=…`, domyślnie **dry-run** (wypisuje tabelę: ID, auto, klient, depozyt, stan flagi),
zapis dopiero z `apply=1`. Odmawia, gdy zamówienie nie jest `asiaauto_order`, ma już flagę z innym numerem FV
albo jest z konta testowego (lista z rejestru: user 10, 12, 13, 157, zamówienie 410894).

## Miejsca krytyczne — czego NIE ruszamy

1. **`getOrderData()` bez zmian** — dodanie pola tam wypuściłoby informację o rozliczeniu z Auranetem
   do panelu klienta, maili i umowy (klient nie ma wiedzieć o relacji Auranet ↔ Prima-Auto).
2. **Statusy, przejścia, rezerwacje, `markDepositPaid()`, maile, umowa PDF** — nietknięte. Flaga nie jest
   warunkiem niczego, niczego nie wyzwala.
3. **Hooki na meta:** `updated_post_meta` słuchają tylko `homepage` (klucz `_asiaauto_reservation_status`)
   i `specs-table` (`post_type = listings`) — nowe klucze na `asiaauto_order` ich nie dotkną (sprawdzone w kodzie 07.10).
4. **Zamówienia stockowe i auta z placu bez zamówienia** — query depozytów ich nie widzi (pułapka z rejestru).
   Auta z placu dostaną flagę dopiero, gdy Ruslan założy przy sprzedaży zamówienie wewnętrzne (ustalenie 07.10 §6).
   Do tego czasu takie pozycje żyją tylko w rejestrze.
5. Deploy wg checklisty: `.bak-<data>-t261`, `php -l`, smoke test listy (desktop + telefon) jako `js` i jako rola `primaauto`.

## Uzupełnienie wsteczne

Z rejestru `docs/rozliczenia/ruslan.md`: wszystkie partie z numerem FV (m.in. FS/1/09/2026 — partie 5+6 + Leopard 7 #869).
Pozycje bez zamówienia (eksport, plac) — pomijamy i wypisujemy osobno. Partia 7 (lista Ruslana za 09.2026) —
flaga dopiero po wystawieniu FV.

## Rytm miesięczny

Przypomnienie w kalendarzu „Auranet Claude” — pierwszy dzień roboczy miesiąca, 09:00:
`docs/przypomnienia/2026-11-02-rozliczenie-miesieczne-ruslan.md`.
