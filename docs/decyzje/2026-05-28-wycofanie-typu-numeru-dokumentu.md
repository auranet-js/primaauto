# Wycofanie pól „Typ dokumentu" i „Numer dokumentu"

**Data:** 2026-05-28
**Status:** Wdrożone w v0.32.56
**Decydent:** Ruslan Prima (klient)
**Wykonanie:** Jan Schenk / Auranet

---

## Kontekst

W modelu danych klienta (`AsiaAuto_Order::CUSTOMER_META`) były pola `billing_id_type` (dropdown dowód osobisty/paszport) i `billing_id_number` (numer dokumentu), oba wymagane. W PDF umowy zlecenia ich wartości pojawiały się w nagłówku Zleceniodawcy w linii `{typ}: {numer}` (np. „dowód osobisty: ABC123456").

Po incydencie z umową AA/2026/0013 (Miron Orłowski, 27.05) i wdrożeniu bramki kompletności danych w v0.32.54-55 Ruslan zwrócił uwagę, że:
- nigdy z tych pól nie korzystał w obsłudze klienta,
- komplikują wypełnianie umowy przez klienta w wizardzie i przez admina w panelu,
- nie są potrzebne w obecnym modelu agencyjnym (klient identyfikuje się PESEL-em lub NIP-em, dane adresowe pokrywają potrzebę).

## Decyzja

Wycofujemy oba pola z UI klienta (wizard frontend), UI admina (formularz „Dane do umowy" w karcie zamówienia) i z generowanego PDF umowy.

**Nie czyścimy bazy:** wpisy w `user_meta` zostają — `getCustomerData()` nadal je zwróci, `CUSTOMER_META` zachowuje wpisy z `required: false`. Decyzję można odwrócić bez migracji danych.

## Zmiany techniczne (v0.32.56)

1. **`class-asiaauto-order.php::CUSTOMER_META`** — `billing_id_type` i `billing_id_number` z `required: true` → `required: false`. `isCustomerDataComplete()` przestaje ich wymagać.
2. **`class-asiaauto-order-wizard.php::getBillingFieldDefs()`** — usunięte 2 wpisy z definicji wizarda.
3. **`class-asiaauto-order-admin.php`**:
   - `renderCardCustomer()` — usunięty blok formularza z typem dokumentu (select) i numerem.
   - `handleUpdateCustomerBilling()` — usunięte 2 keys z whitelist $_POST.
4. **`class-asiaauto-contract.php`**:
   - Mapowanie placeholderów — usunięte `customer_id_type` i `customer_id_num`.
   - Layout PDF (linia ~571) — usunięta linia `{customer_id_type}: {customer_id_num}<br>`.

`case 'billing_id_type'` w `saveCustomerData()` zostawiony jako defensywna walidacja gdyby payload z innej drogi (REST z dziwnym client) zawierał to pole.

## Konsekwencje

**Biznesowe:**
- Klient w wizardzie wypełnia 10 pól zamiast 12 — niższa friction, mniej rezygnacji.
- Ruslan w panelu nie musi się martwić wypełnianiem tych pól ręcznie.
- Umowa PDF nadal ma kompletną identyfikację Zleceniodawcy: imię+nazwisko / nazwa firmy, NIP albo PESEL, adres pocztowy, telefon, email.

**Techniczne:**
- Stare dane w `user_meta` nietknięte — odwracalność bez migracji.
- 5 historycznych umów (AA/2026/0006, 0008, 0011, 0012, 0013) zachowuje aktualne PDF-y; przy ręcznej regeneracji linia „typ: numer" zniknie z dokumentu (bo placeholder już nie istnieje).
- 0 userów stało się „nagle kompletnymi" po obniżeniu wymagań (smoke test) — brak nieoczekiwanych side effectów na istniejących klientach.

**Co się NIE zmienia:**
- Bramka `isCustomerDataComplete()` z v0.32.54 dalej działa — wymaga adresu, PESEL XOR NIP, imienia, nazwiska, emaila, telefonu.
- Auto-regen PDF z v0.32.55 dalej działa.
- 5 starych umów nie jest masowo regenerowanych (forward-only zachowane).

## Powiązane

- ADR: brak (decyzja produktowa klienta, nie architektoniczna)
- VERSIONS: `v0.32.56` — pełen opis zmian
- Memory: `feedback_no_unverified_ui_claims.md`, `project_session_2026_05_28_admin_billing_form.md`
- Backupy plików: `*.bak-2026-05-28-remove-id-doc`
