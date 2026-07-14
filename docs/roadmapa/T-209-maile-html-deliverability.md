# T-209 — Maile do klientów: dostarczalność + szablon HTML

> Status: gotowy do odpalenia · Rozmiar: M
> Godziny realnie: **16–20 h** (Janek ~2 h, AI ~14–18 h) · Rynkowo: 40–48 h
> Podniesione z 14–16 h: **doszedł krok zerowy — audyt dostarczalności.**

## ⚠️ Krok zerowy: czy te maile w ogóle dochodzą (2–4 h)

**To musi pójść PRZED ładnym szablonem. Ładny mail w spamie jest wart zero.**

Stan DNS domeny `primaauto.com.pl` (sprawdzone 14.07):
```
SPF:   v=spf1 a mx include:spf.hostido.pl -all        ✅ jest
DMARC: v=DMARC1; p=quarantine; adkim=s; aspf=s;       ⚠️ kwarantanna + ŚCISŁE dopasowanie
DKIM:  brak odpowiedzi na typowych selektorach        ❌
```

**Dlaczego to groźne:** DMARC ustawiony na **kwarantannę** (czyli „wrzuć do spamu, jeśli nie przejdzie") ze **ścisłym dopasowaniem na obu osiach**, a DKIM nie odpowiada. Cała dostarczalność wisi na samym SPF. Gmail i Outlook od 2024 wymagają **SPF i DKIM**, nie „albo".

**Co to znaczy w praktyce:** mail z magic linkiem — **jedyny sposób, w jaki klient wchodzi w swoje zamówienie** — może lądować w spamie już dziś.

*(Uwaga: DKIM może być podpisywany przez hostido na niestandardowym selektorze, którego `dig` nie zgadnie. Dlatego krok pierwszy to sprawdzenie w panelu hostingu, a nie wyrokowanie z konsoli.)*

**Do zrobienia:**
1. Sprawdzić w panelu Hostido, czy DKIM jest włączony i na jakim selektorze.
2. Jeśli nie ma — włączyć albo przejść na autoryzowany SMTP (wtyczka SMTP + dedykowana skrzynka).
3. **Test dostarczalności** — wysyłka na Gmail/Outlook/WP, sprawdzenie, gdzie ląduje.
4. Dopiero potem: szablon.

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
- **Test dostarczalności:** wysyłka na Gmail, Outlook, WP, Onet → gdzie ląduje (skrzynka / oferty / spam). To jest test, który decyduje o sukcesie tego taska.
- Render w klientach: Gmail (web + mobile), Outlook, Apple Mail. Outlook potrafi zniszczyć każdy layout — testujemy go zawsze.
- Kontrola, że magic link działa po kliknięciu z maila HTML.

## Definicja zrobionego

- Wiadomo, czy DKIM działa; jeśli nie działał — działa.
- Maile docierają do skrzynki odbiorczej Gmaila i Outlooka (nie do spamu) — potwierdzone testem.
- 17 szablonów w brandowanym wrapperze, magic link jako przycisk.
- Wersja tekstowa jako fallback.
- Hasło nie leci już otwartym tekstem.
- Ruslan może podejrzeć każdy szablon w adminie.
