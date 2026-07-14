# T-114 — Ulubione auta (schowek klienta / „parking")

> Status: **gated na T-204** (panel klienta) · Rozmiar: S
> Godziny realnie: **8–10 h** (Janek ~1 h, AI ~7–9 h) · Rynkowo: 20–25 h
> Wzorzec: „parking" z mobile.de (życzenie Janka)

## Po co

Klient zapisuje auta na własną listę i wraca do nich. Efekt uboczny (właściwy cel): **wymusza założenie konta → baza kontaktów pod mailing i remarketing**, i wydłuża czas na stronie.

## Stan faktyczny

**Zielone pole — zero kodu.** Grep `favorite|ulubion|schowek|wishlist|parking` po pluginie i motywie: brak implementacji (jedyne trafienia to `memory_parking` jako *funkcja wyposażenia auta* i kategoria filtra „Parkowanie i kamery" — false positive).

Zero `localStorage` w `assets/js/`.

## Zależność

**Nie startuje przed T-204.** Decyzja Janka 2026-07-14: ulubione mają żyć na koncie, nie w pamięci przeglądarki — inaczej znikają przy zmianie urządzenia i nie budują bazy kontaktów.

## Plan (kroki)

1. **Storage:** user meta `_asiaauto_favorites` (tablica post_id). Prosto, bez własnej tabeli — przy skali 46+ kont i kilkunastu autach na konto to wystarczy.
2. **REST:** `POST/DELETE /asiaauto/v1/favorites/{listing_id}` + `GET /asiaauto/v1/favorites`. Nonce, `permission_callback` = zalogowany.
3. **Serce (ikona) na karcie oferty** — w helperze karty z T-187 (jedno miejsce → działa wszędzie: katalog, huby, homepage, blok „inne egzemplarze"). Stan wypełnione/puste.
4. **Serce na stronie oferty** — w sticky headerze i przy cenie.
5. **Niezalogowany klika serce** → modal „Zaloguj się, żeby zapisać" + zapamiętanie intencji (po zalogowaniu auto ląduje w ulubionych). To jest **moment konwersji na konto** — nie może być tarcia.
6. **Zakładka „Ulubione" w panelu** (slot z T-204) — siatka kart + licznik w headerze.
7. **Obsługa auta, które zniknęło/zostało zarezerwowane** — karta zostaje, ale wyszarzona z etykietą „Zarezerwowane" / „Niedostępne". To jest paliwo dla T-188 (FOMO-alert).

## Strefy kruche

Brak. Wszystko addytywne. Jedyne dotknięcie istniejącego kodu: helper karty (wspólny z T-187) — dlatego **T-187 warto zrobić pierwszy**, żeby serce dokładać już do jednego komponentu, a nie do trzech kopii.

## Testy

**Automatyczne**
- REST: dodanie/usunięcie ulubionego, próba dodania cudzym nonce → 403, próba jako niezalogowany → 401.
- Ulubione przeżywają wylogowanie i ponowne logowanie (user meta, nie sesja).

**Półautomatyczne**
- Dodać 200 ofert do ulubionych jednego konta → sprawdzić czas renderu zakładki (czy user meta nie puchnie problematycznie).
- Usunięcie oferty z bazy (rotacja) → czy panel się nie wywala na martwym post_id.

**MCP (Chrome)**
- Niezalogowany: klik serca → modal → rejestracja → czy auto trafiło do ulubionych (intencja zapamiętana).
- Zalogowany: klik serca w katalogu → licznik w headerze rośnie → panel pokazuje auto.
- Mobile 375px: serce klikalne (min. 44×44 px), nie koliduje z klikiem w kartę.

## Definicja zrobionego

- Serce na każdej karcie oferty i na stronie oferty, w jednym komponencie.
- Ulubione przypisane do konta, przeżywają zmianę urządzenia.
- Klik serca przez niezalogowanego = ścieżka do rejestracji bez utraty intencji.
- Zakładka „Ulubione" w panelu + licznik w headerze.
