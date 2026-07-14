# T-209 — Ładne maile HTML do klientów

> Status: gotowy do odpalenia · Rozmiar: M
> Godziny realnie: **14–16 h** (Janek ~2 h, AI ~12–14 h) · Rynkowo: 30–36 h
> Estymata z kosztorysu **potwierdzona** — bez zmian.

## Po co

Maile transakcyjne (potwierdzenia, statusy zamówienia, umowa) w brandowanym szablonie z logo — profesjonalny wizerunek w skrzynce klienta. Dziś to czysty tekst bez żadnej oprawy.

## Stan faktyczny maili

| Element | Stan |
|---|---|
| Liczba szablonów | **17** (`class-asiaauto-order-content.php`, 908 linii) |
| Format | **100% czysty tekst.** Zero HTML w całym pliku |
| Branding / logo / wrapper | ❌ nie ma. Jedyny „branding" to podpis „Zespół Prima-Auto" |
| Transport | `wp_mail()` → PHP `mail()`. **Brak wtyczki SMTP** |
| Nadawca | „Zamówienia Prima-Auto" `<china@primaauto.com.pl>` |
| Ile maili dostaje klient | **~6–9** przez pełną ścieżkę zamówienia |
| Linki | gołe URL-e (bez anchorów) |

**Treść merytoryczna jest dobra** — problemem jest wyłącznie prezentacja i dostarczalność, nie copy. To dobra wiadomość: nie przepisujemy treści.

## 🔴 Znalezisko przy okazji: hasło idzie plaintextem

Mail powitalny **wysyła hasło klienta otwartym tekstem** (`class-asiaauto-order.php:1247`). Do naprawy: zamiast hasła — jednorazowy link do jego ustawienia (mechanizm magic link już istnieje).
*(To samo jest w planie T-204 — jeśli T-204 pójdzie pierwszy, tutaj odpada.)*

## Plan szablonu (12–14 h)

**Podejście tanie i skuteczne:** nie przepisujemy 17 szablonów na HTML. Zamiast tego:

1. **Jeden wrapper** `wrapHtml($body)` — nagłówek z logo, stopka, dane kontaktowe, stopka prawna, styl inline (klienty pocztowe nie znoszą zewnętrznego CSS).
2. **Treść zostaje tekstowa**, konwertowana automatycznie: zamiana znaków nowej linii, auto-linkowanie URL-i, wyróżnienie przycisku CTA (magic link jako przycisk, nie goły URL).
3. `wp_mail_content_type` → `text/html` + wersja tekstowa jako fallback (multipart — część klientów i filtrów antyspamowych tego wymaga).
4. **Podgląd szablonów w adminie** — żeby Ruslan mógł zobaczyć, co dostaje klient, bez składania testowego zamówienia.

To tnie robotę drastycznie względem przepisywania każdego szablonu z osobna.

## Strefy kruche

- `class-asiaauto-order-content.php` — dokładamy warstwę prezentacji, **nie ruszamy treści ani placeholderów**.
- ⚠️ **Nie zepsuć magic linku** — to jedyna droga klienta do zamówienia. Po zmianie na HTML link musi działać w każdym kliencie pocztowym.

## Testy

**Automatyczne**
- Każdy z 17 szablonów renderuje się do poprawnego HTML (bez rozjechanych placeholderów).
- Wersja tekstowa (fallback) zawiera działający URL magic linku.

**Półautomatyczne**
- Render w prawdziwych skrzynkach (patrz niżej) — po zmianie na HTML sprawdzić, czy maile nadal docierają normalnie.
- Render w klientach: Gmail (web + mobile), Outlook, Apple Mail. Outlook potrafi zniszczyć każdy layout — testujemy go zawsze.
- Kontrola, że magic link działa po kliknięciu z maila HTML.

## Definicja zrobionego

- Maile docierają tak jak dotąd (zmiana na HTML niczego nie zepsuła).
- 17 szablonów w brandowanym wrapperze, magic link jako przycisk.
- Wersja tekstowa jako fallback.
- Hasło nie leci już otwartym tekstem.
- Ruslan może podejrzeć każdy szablon w adminie.
