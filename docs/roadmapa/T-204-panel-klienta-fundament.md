# T-204 — Panel klienta (konto zalogowanego) — FUNDAMENT

> Status: **nowa pozycja** (decyzja Janka 2026-07-14) · Rozmiar: M
> Godziny realnie: **18–24 h** (Janek ~3–4 h, AI ~15–20 h) · Rynkowo: 45–55 h
> **Odblokowuje:** T-114 (ulubione), T-188 (alerty), częściowo T-115 (porównywarka)

## Po co

T-114, T-188 i T-115 w kosztorysie były wyceniane tak, jakby konto klienta istniało. **Nie istnieje.** Dziś:

- Konto (`asiaauto_customer`) powstaje **wyłącznie jako produkt uboczny zamówienia** — `findOrCreateCustomer()` przy `POST /order/start`.
- Nie ma rejestracji ani logowania „z ulicy".
- `/klient/` to **atrapa**: `template_redirect` natychmiast przerzuca zalogowanego na jego zamówienie (`class-asiaauto-order.php:186-219`). Kod listy zamówień istnieje, ale jest martwy („Shouldn't normally reach here").
- Hasło idzie do klienta **plaintextem w mailu powitalnym** (`class-asiaauto-order.php:1248-1268`) — do naprawy przy okazji.

Stan bazy: **46 kont klientów, 135 zamówień.** Czyli fundament auth (role, capabilities, magic link, blokada wp-admin) **już jest** — brakuje samego panelu.

Bez tego panelu ulubione żyją w pamięci przeglądarki (znikają przy zmianie urządzenia), a alerty mailowe są niewykonalne. **Sens biznesowy tych funkcji to baza kontaktów pod mailing i remarketing** — bez konta nie ma bazy.

## Stan faktyczny (zweryfikowany)

| Element | Gdzie | Stan |
|---|---|---|
| Rola `asiaauto_customer` | `class-asiaauto-order.php:25, 271-279` | ✅ jest (cap: tylko `read`) |
| Auto-rejestracja przy zamówieniu | `class-asiaauto-order.php:1170-1246` | ✅ działa |
| Magic link (token 48h, single-use) | `class-asiaauto-order.php:1254-1304` | ✅ działa, solidne |
| Logowanie hasłem | `class-asiaauto-order-api.php:766-800` (`POST /auth/login`, rate-limited) | ✅ jest |
| Blokada wp-admin dla klienta | `class-asiaauto-order.php:174-182` | ✅ jest |
| Strona `/klient/` (ID 238607) | `class-asiaauto-shortcodes.php:2433-2488` | ⚠️ atrapa — redirect na zamówienie |
| Rejestracja „z ulicy" | — | ❌ nie ma |
| Widok „moje zamówienia" | `class-asiaauto-shortcodes.php:2458-2475` | ❌ martwy kod |
| Reset hasła / zmiana danych | — | ❌ nie ma |

## Plan (kroki)

1. **Odblokować `/klient/` jako realny panel.** Zdjąć redirect z `redirectLoggedInCustomerFromLoginPage()` (przenieść go pod warunek „wchodzę z maila o zamówieniu"). Panel = zakładki.
2. **Zakładka „Moje zamówienia"** — ożywić istniejący martwy kod: lista zamówień klienta ze statusem, linkiem do kreatora, datą. To najtańsza część (kod jest, tylko nieosiągalny).
3. **Zakładka „Moje dane"** — podgląd + edycja danych kontaktowych/rozliczeniowych (te same pola co `POST /order/{id}/billing`). Zmiana hasła.
4. **Rejestracja i logowanie z frontu** — prosty formularz (email + hasło), bez dublowania flow zamówienia. Kto ma już konto z zamówienia — loguje się tym samym.
5. **Naprawa hasła plaintext w mailu** — zamiast hasła wysyłamy link do ustawienia hasła (jednorazowy, TTL). Reużyć istniejący mechanizm magic link.
6. **Miejsca w UI:** ikona konta w headerze (zalogowany: „Moje konto" + licznik ulubionych; niezalogowany: „Zaloguj").
7. **Zakładki-placeholdery** „Ulubione" i „Alerty" — puste sloty, które wypełnią T-114 i T-188.

## Strefy kruche

- `class-asiaauto-order.php` — **strefa krucha** (statusy, rezerwacje). Ruszamy **wyłącznie** funkcje auth/redirect (`:174-234`, `:1170-1304`). **Nie dotykamy** `TRANSITIONS`, `markDepositPaid()`, `calculateDeposit()`, `LISTING_RESERVATION_MAP`.
- Magic link jest dziś **per-user, nie per-order** (nowy token nadpisuje stary) — przy panelu to zostaje bez zmian, ale trzeba to pamiętać przy T-188 (linki w alertach nie mogą kasować tokenu z maila o zamówieniu). **Rozwiązanie: alerty linkują do panelu bez tokenu (klient loguje się normalnie), token zostaje zarezerwowany dla flow zamówienia.**

## Testy

**Automatyczne**
- Testy uprawnień: klient A nie widzi zamówień klienta B (bezpośrednie wejście na `?order_id=` cudzego zamówienia → 403).
- Klient nie wchodzi do wp-admin (redirect działa dalej).
- Rate limit na logowaniu nadal aktywny.

**Półautomatyczne**
- Migracja: wszystkie 46 istniejących kont muszą się logować i widzieć swoje 135 zamówień. Skrypt: dla każdego usera z rolą `asiaauto_customer` policz zamówienia w panelu vs w bazie.
- Sprawdzić, że flow magic link z maili o zamówieniu **nie został zepsuty** (to jest droga, którą klienci realnie wchodzą).

**MCP (Chrome)**
- Rejestracja nowego konta → logowanie → panel → wylogowanie → ponowne logowanie.
- Wejście z magic linku (mail o zamówieniu) → czy nadal ląduje w kreatorze na właściwym kroku.
- Mobile 375px: panel, zakładki, ikona konta w headerze.

## Definicja zrobionego

- Klient może założyć konto bez składania zamówienia.
- `/klient/` pokazuje realny panel z zamówieniami i danymi, nie przerzuca na kreator.
- Istniejące 46 kont działa bez zmian, flow zamówienia nietknięty.
- Hasła nie latają plaintextem w mailach.
- Są puste sloty na Ulubione i Alerty.
