# T-188 — Zapisane wyszukiwania + alerty mailowe

> Status: **gated na T-204** (panel) i T-114 (ulubione, dla alertu o rezerwacji) · Rozmiar: L
> Godziny realnie: **33–40 h** (Janek ~4–5 h, AI ~28–35 h) · Rynkowo: 85–100 h
> Zakres wybrany przez Janka 2026-07-14 (quiz): nowe auto + tryb zbiorczy + alert o rezerwacji. **Bez** alertu o spadku ceny.

## Po co

Klient zapisuje kryteria („czarny Leopard 5 do 150 tys.") i dostaje maila, gdy pasujące auto wpadnie z importu. To jest **newsletter oparty na intencji** — powracający ruch i gotowa baza kontaktów, w miejsce wysyłania w ciemno.

## Zakres (decyzja Janka)

| Wyzwalacz | Wchodzi? | Koszt |
|---|---|---|
| Nowe auto pasujące do kryteriów | ✅ rdzeń | ~25 h |
| Tryb zbiorczy „raz w tygodniu" | ✅ | +4–6 h |
| Auto z ulubionych zarezerwowane / znika (FOMO) | ✅ | +4–6 h |
| Spadek ceny na obserwowanym aucie | ❌ odrzucone | — |

**Uwaga projektowa:** alert natychmiastowy i newsletter tygodniowy to **ten sam mechanizm**, nie dwa systemy. Robimy z tego **wybór klienta przy zapisie**: „powiadom od razu" / „raz w tygodniu zbiorczo". Taniej i mniej nachalne.

## Stan faktyczny

- Wyszukiwarka ma już **pełne faceted search** z REST (`class-asiaauto-inventory.php`, `parseParams()` :505-535) — kryteria da się zserializować z tych samych parametrów URL (`marka`, `model`, `paliwo`, `nadwozie`, `rocznik`, `kolor`, `naped`, `cena_min/max`). **Nie wymyślamy formatu kryteriów — bierzemy istniejący.**
- Import wpina nowe auta przez `AsiaAuto_Importer::importListing()`; rezerwacje przez `_asiaauto_reservation_status` (`on_lot`/`in_transit`/`reserved`).
- Maile: **NIE przez `wp_mail()` do Janka**, ale do klientów wysyłka idzie istniejącym kanałem zamówień (`class-asiaauto-order-content.php`) — szablony HTML tam są.
- Zero infrastruktury alertowej. Zielone pole.

## Plan (kroki)

1. **Model danych:** CPT `asiaauto_alert` (albo tabela) — `user_id`, `criteria_json` (kształt = parametry wyszukiwarki), `tryb` (`instant`/`weekly`), `active`, `last_notified_at`, `last_seen_listing_id`.
2. **UI zapisu w wyszukiwarce:** przycisk „Zapisz to wyszukiwanie i powiadom mnie" nad wynikami (widoczny, gdy jakikolwiek filtr aktywny). Niezalogowany → modal rejestracji (ta sama ścieżka co T-114).
3. **Zakładka „Alerty" w panelu** (slot z T-204): lista zapisanych, edycja kryteriów, przełącznik trybu, pauza, usunięcie. Każdy alert pokazuje „ile aut pasuje dziś" (reuse `filter-counts`).
4. **Silnik dopasowania (cron):** po każdym imporcie (hook na `importListing`) kolejkuj nowe post_id; cron co godzinę bierze kolejkę, dla każdego alertu odpala WP_Query z zapisanymi kryteriami zawężony do nowych ID. **Nie odwrotnie** (nie skanujemy całej bazy per alert) — to jest różnica między zapytaniem w sekundę a w minutę.
5. **Wysyłka `instant`:** mail „Nowe auto pasujące do Twojego wyszukiwania" z kartą auta (zdjęcie, cena, link). Throttle: max 1 mail / alert / dobę (zbiorczo, jeśli w oknie wpadło kilka aut).
6. **Wysyłka `weekly`:** cron tygodniowy, zbiorczy mail z listą (max 8 aut + „zobacz wszystkie").
7. **Alert FOMO (wymaga T-114):** hook na zmianę `_asiaauto_reservation_status` → jeśli auto jest w czyichś ulubionych → mail „Auto, które obserwujesz, zostało zarezerwowane".
8. **Wypis (unsubscribe)** — link w każdym mailu, wymóg prawny. Pauza alertu bez logowania (token w linku).

## Strefy kruche

- **Hook w imporcie** — `importListing()` jest w strefie kruchej. Wpinamy się **wyłącznie** przez `do_action` na końcu udanego importu (dodanie akcji, zero zmian w logice). Kolejkowanie asynchroniczne — **import nie może czekać na maile**.
- **Magic link:** alerty linkują do panelu **bez tokenu** (klient loguje się normalnie). Token magic link zostaje zarezerwowany dla flow zamówienia — inaczej mail alertowy skasowałby token z maila o zamówieniu (są per-user, nadpisują się).

## Testy

**Automatyczne**
- Dopasowanie kryteriów: zestaw 10 alertów × 10 sztucznych aut → macierz oczekiwanych trafień. To jest serce funkcji, musi mieć testy jednostkowe.
- Throttle: 5 aut w jednym oknie → dokładnie 1 mail, nie 5.
- Unsubscribe: token deaktywuje alert i nie da się nim ruszyć cudzego.

**Półautomatyczne**
- Dry-run na produkcji: włącz alerty w trybie „loguj, nie wysyłaj", nakarm cronem import z ostatnich 7 dni → sprawdź, ile maili **by** poszło. **Gate przed uruchomieniem: jeśli wychodzi >2 maile/klienta/tydzień, throttle jest za słaby.**
- Wydajność: 100 alertów × 200 nowych aut → czas przebiegu crona (cel: <30 s).

**MCP (Chrome)**
- Ustaw filtry → „Zapisz wyszukiwanie" → panel pokazuje alert z poprawnymi kryteriami.
- Niezalogowany klika „Zapisz" → rejestracja → alert zapisany (intencja nie ginie).
- Mail testowy (do `js@auranet.com.pl`, NIE do klientów) — render HTML na desktopie i mobile.

## Definicja zrobionego

- Klient zapisuje kryteria z wyszukiwarki jednym kliknięciem, wybiera tryb powiadomień.
- Nowe auto z importu wyzwala maila do pasujących klientów, z throttlingiem.
- Auto z ulubionych zarezerwowane → mail FOMO.
- Każdy mail ma działający wypis.
- Dry-run potwierdził, że nie zasypujemy klientów.
