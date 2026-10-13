# T-264 „Zamów” → zgłoszenie chęci rezerwacji — spec

> Zgłoszenie: rozmowa telefoniczna, transkrypt wklejony przez Janka 2026-10-13.
> Status: **WDROŻONE 13.10 (v0.45.2, `docs/VERSIONS.md`)** — zestaw A1, B1, C1, D1 zaakceptowany przez Ruslana 13.10. Karty katalogu dopięte tego samego dnia.
> Makiety: https://auratest.pl/fe4f58fec53ctmp/primaauto-makieta-t264-zgloszenie-2026-10-13.html (generator `docs/makiety/gen-t264-zgloszenie.py`, źródła HTML w `tmp/makiety-zrodla/`).
> Wycena: **3 h** (decyzja Janka 13.10; makiety zrobione w tej wycenie). W postępie prac na górze listy.
> Dotyka stref wrażliwych: karta oferty (CTA desktop + pasek mobilny), kreator `/zamow/`, szablony maili w bazie, panel klienta. Zmiany wyłącznie w tekstach — logika zamówień, statusy i rezerwacje nietknięte.

## Problem

Przycisk „Zamów” sugeruje, że kliknięcie i podanie danych składa zamówienie. Faktycznie to zgłoszenie chęci rezerwacji / importu — Ruslan dopiero sprawdza dostępność, a umowa i depozyt przychodzą później. Dwa ryzyka:

1. **Prawne** — „Zamów” + „Zamówienie złożone” może być czytane jako zawarcie umowy na odległość (radcy prawni).
2. **Sprzedażowe** — chcemy, żeby to był lead: klient zostawia e-mail i telefon, dalsze ustalenia idą „za zamkniętymi drzwiami”.

Cel: na całej ścieżce — przycisk → strona `/zamow/` → ekran po wysłaniu → mail — klient widzi „zgłoszenie”, nie „zamówienie”, z jasną informacją, że zgłoszenie nie jest zamówieniem i do niczego nie zobowiązuje.

## Z rozmowy (13.10)

- Przycisk: kandydaci **„Chcę to auto”** (preferowany w rozmowie), „Chcę zarezerwować”, „Zarezerwuj”. Obawa: na telefonie są 3 przyciski obok siebie, mało miejsca.
- Strona `/zamow/`: napisać wprost, że to zgłoszenie chęci rezerwacji / importu, nie zamówienie.
- Po podaniu danych: komunikat w stylu „Otrzymaliśmy Twoje zgłoszenie w sprawie dalszych kroków rezerwacji, skontaktujemy się” — nie „Dziękujemy za zamówienie”.
- Druga strona potwierdzi ustalenia na piśmie — czekamy na tę wiadomość przed wdrożeniem.

## Stan obecny (zweryfikowany 13.10 na produkcji)

| # | Miejsce | Co widzi klient dziś | Plik / źródło | Ocena |
|---|---|---|---|---|
| 1 | Karta oferty — CTA desktop | **„Zamów”** | `class-asiaauto-single.php:491` | do zmiany |
| 2 | Karta oferty — pasek mobilny (3 przyciski) | ikona koszyka + **„Zamów”** | `class-asiaauto-single.php:682` | do zmiany; ikona koszyka też sugeruje zakup |
| 3 | Shortcode CTA (stary) | **„Zamów”** | `class-asiaauto-shortcodes.php:2321` | do zmiany lub potwierdzić, że nieużywany |
| 4 | Shortcode `[asiaauto_order_cta]` | **„Zamów ten samochód”** (domyślny `text`) | `class-asiaauto-shortcodes.php:2602` | do zmiany; sprawdzić, gdzie użyty |
| 5 | Strona `/zamow/` — tytuł / H1 / `<title>` | **„Zamów”** / „Zamów - Prima-Auto - Import Samochodów z Chin” (noindex) | strona #174547 | do zmiany (slug `/zamow/` zostaje — linki w mailach, kampaniach, GA4) |
| 6 | Kreator krok 1 | „Poproś o rezerwację” + „sprawdzimy dostępność… odezwiemy się w ciągu 24h”, przycisk „Wyślij zgłoszenie” | `class-asiaauto-order-wizard.php:171–216` | **już dobrze**; brakuje zdania „to nie jest zamówienie” |
| 7 | Kreator krok 2 (ekran po wysłaniu) | „Weryfikujemy zgłoszenie”, „Numer zgłoszenia #…” | `class-asiaauto-order-wizard.php:231–234` | **już dobrze**; dodać zdanie o braku zobowiązania |
| 8 | Odpowiedź API po wysłaniu | `message: „Zamówienie złożone. Weryfikujemy dostępność pojazdu.”` | `class-asiaauto-order-api.php:314` | JS jej **nie wyświetla** (przechodzi na krok 2) — ale poprawić, bo to tekst w odpowiedzi publicznego API |
| 9 | Mail do klienta po wysłaniu (`order_started_customer`, wersja z bazy) | „przyjęliśmy Twoje zgłoszenie #…”, **„Zgłoszenie nic nie kosztuje i do niczego Cię nie zobowiązuje.”**, przycisk „Śledź status zgłoszenia” | opcja `asiaauto_order_email_templates` | **już dobrze** — to dokładnie to, o co chodziło w rozmowie |
| 10 | Mail statusu `weryfikacja` | temat „Prima-Auto — Weryfikacja (**zamówienie** #…)”, treść „Status Twojego **zamówienia** zmienił się na…”, przycisk „Podgląd **zamówienia**” | opcja `asiaauto_order_email_templates` | do zmiany — to drugi mail, który klient dostaje |
| 11 | Mail statusu `nowe` | jak wyżej („zamówienie”) | j.w. | do zmiany; najpewniej nie wysyłany przy utworzeniu (maile statusu idą przy zmianie) — potwierdzić w logu |
| 12 | Nadawca wszystkich maili | **„Zamówienia Prima-Auto”** | `asiaauto-sync.php:127` (`wp_mail_from_name`) | do decyzji |
| 13 | Panel klienta `/klient/` | zakładka „Moje zamówienia”, „Zamówienie #…”, „złożysz zamówienie” | `class-asiaauto-account.php:30, 435, 482` | do decyzji — klient ma panel dopiero po zgłoszeniu; po umowie to już jest zamówienie |
| 14 | Etykieta pierwszego kroku w kreatorze i statusy | „Dane kontaktowe”, „Weryfikacja”; status `nowe` = „Nowe” | `class-asiaauto-order-wizard.php:732`, `class-asiaauto-order.php:33` | OK, bez zmian |
| 15 | Strona „Proces zamawiania” | 5× „zamów*”, 2× „rezerwac*” | strona #153862 | przejrzeć opis pierwszego kroku |
| 16 | Regulamin | w treści strony 0 trafień — treść renderowana z innego źródła | strona #153866 | **niezweryfikowane** — sprawdzić, czy regulamin opisuje „zamówienie przez formularz” |

Wniosek: kreator i pierwszy mail są już napisane językiem zgłoszenia. Rozjazd jest **przed** kreatorem (przyciski, tytuł strony) i **po** nim (maile statusowe, nadawca, panel).

## Zakres prac

1. **Makiety** (`docs/makiety/`): pasek CTA na karcie oferty — stan obecny + 2–3 warianty etykiety, desktop i telefon 360 px; nagłówek `/zamow/` z adnotacją; ekran po wysłaniu.
2. **Wdrożenie tekstów** po wyborze wariantu: punkty 1–5, 6–7 (jedno zdanie), 8, 10–11; 12–13 wg decyzji.
3. **Maile w bazie**: backup opcji `asiaauto_order_email_templates` przed zmianą (wzorzec `…_backup_2026_08_10`), edycja tylko tematów i fraz „zamówienie” w `status_nowe` / `status_weryfikacja`.
4. **Smoke test**: karta oferty desktop + telefon, `/zamow/?listing_id=…` krok 1–2, podgląd maili (bez zakładania zamówienia w bazie — zgoda Janka wymagana).

## Decyzje 13.10 (quiz z Jankiem)

- **A1** — przycisk „Chcę to auto” (telefon bez ikony, koszyk znika), na desktopie podpis „Bezpłatne zgłoszenie — nie jest zamówieniem”.
- **B1** — nagłówek strony `/zamow/` „Zgłoszenie rezerwacji”, tytuł formularza „Zgłoś chęć rezerwacji”, adnotacja w zielonej ramce nad polami.
- **C1** — ekran po wysłaniu „Otrzymaliśmy Twoje zgłoszenie” + „w sprawie dalszych kroków rezerwacji” + adnotacja.
- **D1** — maile statusu `nowe` i `weryfikacja` językiem zgłoszenia; nadawca wszystkich maili **„Prima-Auto”** (zamiast „Zamówienia Prima-Auto”).
- Panel klienta: zakładka **„Moje zamówienia” zostaje**, zmienia się tylko pusty stan („złożysz zamówienie” → „wyślesz zgłoszenie”).
- Adnotacja w **pełnym brzmieniu** (z makiety).
- Makieta zestawu: https://auratest.pl/fe4f58fec53ctmp/primaauto-makieta-t264-wybrane-2026-10-13.html

Ruslan zaakceptował makietę zestawu 13.10 — wdrożone tego samego dnia (v0.45.2).

## Do decyzji (stan sprzed quizu, rozstrzygnięte wyżej)

- Etykieta przycisku: „Chcę to auto” / „Chcę zarezerwować” / „Zarezerwuj” — na telefonie liczy się szerokość.
- Ikona koszyka na pasku mobilnym — zamienić (np. dokument/ptaszek) czy usunąć.
- Tytuł strony `/zamow/`: „Zgłoszenie rezerwacji” / „Zgłoś chęć rezerwacji” / inne.
- Nadawca maili „Zamówienia Prima-Auto” → „Prima-Auto”?
- Panel klienta: „Moje zamówienia” → „Moje zgłoszenia” czy zostawić (po umowie to jest zamówienie)?
- Brzmienie adnotacji „zgłoszenie nie jest zamówieniem” — jedno zdanie wspólne dla `/zamow/`, ekranu po wysłaniu i maila.

## Makiety (13.10)

| Scena | Warianty |
|---|---|
| A — przyciski na karcie oferty | A0 stan · A1 „Chcę to auto” (telefon bez ikony) · A2 „Zarezerwuj” z kluczykiem · A3 „Chcę zarezerwować · bez zobowiązań” w dwóch rzędach |
| B — formularz `/zamow/` | B0 stan · B1 nagłówek „Zgłoszenie rezerwacji” + zielona ramka z adnotacją · B2 nagłówek „Chcę to auto” + trzy kroki + adnotacja nad przyciskiem |
| C — ekran po wysłaniu | C0 stan · C1 „Otrzymaliśmy Twoje zgłoszenie” · C2 „Dziękujemy za przesłanie zgłoszenia” |
| D — mail statusu „Weryfikacja” | D0 stan · D1 język zgłoszenia + nadawca „Prima-Auto” |

Zmierzone na zrzutach z telefonu (390 px): „Chcę to auto” i „Zarezerwuj” z ikoną mieszczą się w 1/3 paska; „Chcę to auto” z ikoną już nie (dlatego A1 bez ikony). Na desktopie A1–A3 mają pod przyciskiem podpis „Bezpłatne zgłoszenie — nie jest zamówieniem”.

## Zauważone obok, poza zakresem

- Szablon tytułu SEO oferty nr 9: „Zamów Online {auto} z Chin w 2026” (~10% ofert, `class-asiaauto-single.php:753`) i nr 2 „{auto} zamów z Chin” — ten sam problem językowy w wynikach Google, ale zmiana tytułów = osobna decyzja SEO (T-203).
- Nazewnictwo zdarzeń GA4/Meta przy wysłaniu formularza — bez zmian.
- `/zamow/` na telefonie: formularz jest **pod** podsumowaniem auta, ceną i ramką „Informacje” (~1400 px przewijania po kliknięciu przycisku). Kolejność bloków to osobna zmiana układu.
- Maile w przeglądarce 390 px rozjeżdżają się do 749 px przez pasek postępu z `white-space:nowrap` — dotyczy wszystkich szablonów statusowych, także dziś. Zachowanie w Gmailu/Outlooku mobilnym niezweryfikowane.

## Po wdrożeniu (13.10)

- **Karty katalogu `/samochody/`** (`class-asiaauto-inventory.php:1097, 1110`) miały przycisk „Zamów” — pominięte w tabeli 16 miejsc (weryfikacja szła po plikach karty oferty, nie po wszystkich szablonach z linkiem do `/zamow/`). Zmienione na „Chcę to auto” 13.10, po zgłoszeniu Janka.
- Mail do obsługi `order_started_admin` („[Prima-Auto] Nowe zgłoszenie #…”) mówi o zgłoszeniu od co najmniej 30.05 — bez zmian.
