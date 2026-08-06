# T-209 — pakiet wykonawczy (stan na 2026-08-06)

> Wątek planistyczny **zamknięty**. Makiety wyglądu i treści zaakceptowane przez Janka 06.08.
> Ten plik jest wejściem do wątku wdrożeniowego. Wszystkie liczby i lokalizacje zmierzone
> na produkcji 06.08 — nie mierz ich od nowa.

## Artefakty do wglądu

- **Treści, 16 maili przed/po:** `tmp/t209-tresci-maili.html`
  → https://auratest.pl/fe4f58fec53ctmp/primaauto-t209-tresci-maili-2026-08-06.html
- **Wygląd, wrapper + mobile:** `tmp/t209-makiety-maile.html`
  → https://auratest.pl/fe4f58fec53ctmp/primaauto-t209-makiety-maile-2026-08-06.html
- **Generator makiet treści:** `scripts/t209_build_tresci.py` (`--deploy` wystawia na auratest).
  Trzyma gotowe wzorce HTML: wrapper, stopka D, `progress()`, `badge()`, `cta()`, `head()`, `nextstep()`.
- **Stopka, 4 warianty (źródło wariantu D):** `primaauto-stopki-mail-2026-07-31.html` na auratest

---

## 1. Decyzje zamknięte

**Wrapper i marka**
1. Stopka = **wariant D** z zestawu z 31.07 (granatowy pasek), **bez linii osobowej** „Ruslan Prima / właściciel" — mail leci automatem, podpis jest instytucjonalny.
2. Pasek w stopce: „Auta z rynku chińskiego dostępne na indywidualne zamówienie. Każdy samochód przed zakupem jest przez nas weryfikowany, a następnie sprowadzany do Polski dla klienta."
3. **Telefon pod podpisem**, nie w bloku danych: „Pozdrawiamy, / Zespół Prima-Auto / +48 721 730 507" — czerwień `#D63031`, klikalny `tel:`.
4. Dane firmowe z `asiaauto_order_config` (nazwa, adres, NIP, REGON, telefon) — nic na sztywno. Adres = **plac** `ul. Przemysłowa 13A, 35-105 Rzeszów` (adres rejestrowy nie ma u nas kodu pocztowego).
5. Logo: `https://primaauto.com.pl/primaauto-logo-mail.png` (64×64 w stopce), leży na produkcji od 31.07.
6. **Przyciski w niebieskim LinkedIn** `#0A66C2`. Czerwień zostaje akcentem (numery kroków, telefon), granat `#1B2A4A` obsługuje stopkę, nagłówki i pasek postępu. Rozważone i odrzucone: granat na CTA (zlewał się ze stopką), błękit `#2563EB` (za jaskrawy, „appowy"), zieleń `#38A169` (pracuje już jako sygnał statusu — przycisk w tym kolorze rozmywałby jej znaczenie), czerwień marki (w skrzynce czyta się jako alert).
7. Magic link zawsze jako **przycisk + goły URL pod spodem** — Outlook bywa bez stylów, a to jedyna droga klienta do zamówienia.

**Treść**
8. Powitanie **„Dzień dobry {customer_name},"** — nigdy „Cześć". (memory `feedback_powitanie_dzien_dobry_nie_czesc`)
9. **Nigdy słowo „homologacja"** w treści user-facing — zawsze „przygotowanie do rejestracji". URL strony `/informacje/homologacja-i-rejestracja/` zostaje, zmienia się wyłącznie anchor. (memory `feedback_nigdy_slowo_homologacja`)
10. Nagłówek każdego maila: `<nazwa oferty> (#numer) — STATUS` — status kolorem tekstu, **bez tła**. Zielony `#38A169` dla kamieni milowych (DOSTĘPNY, UMOWA GOTOWA, UMOWA PODPISANA, ZAREZERWOWANY, ZAKUPIONY, NA PLACU, ODEBRANY), granatowy dla informacyjnych (W DRODZE, W DOSTAWIE, ANEKS), czerwony dla NIEDOSTĘPNY, szary dla ANULOWANE.
11. **Pasek postępu** w jednej linii, sześć etapów: `Zgłoszenie › Potwierdzenie › Umowa › Rezerwacja i zakup › Transport › Odbiór`. Aktualny pogrubiony granatem, przebyte z ptaszkiem, przyszłe szare. Tabela HTML (Outlook-safe).
12. Wzorzec sekcji „Co teraz": **pierwszy krok → przycisk → kolejne kroki pod spodem**, następny etap wyszarzony.
13. Bez zamykających formułek („Pytania? Dzwoń lub pisz…") — kontakt jest w podpisie i stopce.
14. Bez liczb bez pokrycia — wyleciało „ok. 10% ofert z Chin znika w ciągu 24 godzin".

**Proces (zmiany wykraczające poza treść)**
15. **Mail `welcome` wyłączony** ze ścieżki zamówienia. Konto powstaje po cichu, hasło nie leci mailem. Klient wchodzi magic linkiem; stały dostęp ustawia sobie sam przez „Nie pamiętasz hasła?" na `/klient/`.
16. **Depozyt przeniesiony na etap „Potwierdzone"** — płatny razem z uzupełnieniem danych, pod jednym przyciskiem „Opłać depozyt i uzupełnij dane". Konsekwencja: blok płatności musi przejść z kroku 4 wizarda do kroku 3.
17. **Nie dokładamy żadnych blokad w kodzie.** Umowa już to zabezpiecza: „Przystąpienie do procedury zakupu pojazdu jest możliwe po uiszczeniu depozytu" (`class-asiaauto-contract.php:750`) i analogicznie w wariancie leasingowym (`:1510`). Ruslan nadzoruje ręcznie, flagę `deposit_paid` ma w panelu.
18. **PDF umowy dołączany do maila** `status_umowa_gotowa` — mechanizm istnieje (aneks VIN, `class-asiaauto-order-admin.php:768`).
19. Kwoty w mailu „Potwierdzone": **brutto duże, netto obok mniejsze**, pod spodem co zawiera + zdanie „Prowizja Prima-Auto." **bez kwoty prowizji**.

---

## 2. Stan faktyczny (zmierzone 06.08, nie mierz ponownie)

| Element | Stan |
|---|---|
| Szablony w `defaultEmailTemplates()` | **18** (`class-asiaauto-order-content.php`) |
| + fallback poza plikiem szablonów | `class-asiaauto-order.php:1893-1911` — dla statusów bez własnej treści (`nowe`, `weryfikacja`) |
| Do klienta / do admina | **15 / 3** (`order_started_admin`, `status_changed_admin`, `customer_billing_completed` — ta mimo nazwy leci na `admin_email`, `:1783`) |
| Pozycji w UI edytora | 20 (7 stałych + 13 statusów), opcja `asiaauto_order_email_templates` **nie istnieje w bazie** — nikt nigdy nic nie nadpisał |
| Podpisy | 12 × „Zespół Prima Auto" (bez myślnika), 3 × „Prima-Auto"; telefon w 9 w bloku podpisu, w 6 w treści |
| Wywołania `wp_mail()` | 9, w tym **5 do klienta**: `order.php:1421` (welcome), `:1820` (order started), `:1917` (wszystkie statusy), `order-admin.php:664` (umowa przegenerowana), `:768` (aneks VIN + PDF) |
| Nagłówki | żadne z 5 wywołań do klienta nie przekazuje `$headers` → **jeden filtr obsłuży komplet** |
| Nadawca | filtr globalny `asiaauto-sync.php:110-111`, „Zamówienia Prima-Auto" `<china@primaauto.com.pl>` |
| Reply-To dla klienta | **brak** (ustawiany tylko na mailach do admina) |
| Magic link | `MAGIC_TOKEN_TTL = 48 h`, jednorazowy, **generowany świeżo przy każdej wysyłce** (4 miejsca) |
| Depozyt | `deposit_min = 6150`, procent i zaokrąglenie wyzerowane → zawsze **6 150 zł** |
| Prowizja umowna | `contract_commission_min = 5000` → **5 000 zł netto** = 6 150 brutto (ta sama kwota co depozyt) |
| Wizard | 5 kroków; krok 3 = dane do umowy, krok 4 = umowa + depozyt (blok `4c`, przelew + numer konta) |
| Regulamin | linkowany z wizarda (`:188` checkbox akceptacji, `:448` boczna lista), `/informacje/regulamin/` → 200 |

---

## 3. Zakres wdrożenia — etapy

### Etap 1 — wrapper (fundament)
- `wrapHtml($body, $vars)` w nowej klasie **obok** istniejących (zasada: addytyw, nie refaktor stref kruchych).
- Wpięcie: filtr na `wp_mail` + `wp_mail_content_type` na `text/html`, z **rozpoznaniem odbiorcy** — maile na `admin_email` idą bez wrappera.
- Multipart: wersja tekstowa jako fallback, z działającym URL-em magic linku.
- **Feature flag** w opcji (np. `asiaauto_order_email_html`) — wyłączenie przywraca dzisiejsze zachowanie bez deploya.
- Elementy: stopka D, podpis + telefon, `progress()`, `badge` statusu, `cta()`. Wzorce HTML gotowe w `scripts/t209_build_tresci.py` — **przenieś stamtąd, nie pisz od nowa**.

### Etap 2 — treści (16 pozycji)
- Przepisz szablony wg makiet, klucz po kluczu. Podpisy **wycinamy z treści** (wrapper je dokleja).
- Nie pomiń **fallbacku w `order.php:1893`** — jest poza plikiem szablonów.
- Zachowaj wszystkie placeholdery. Dostępne: `{price_final}`, `{deposit_amount}`, `{magic_link}`, `{listing_title}`, `{order_id}`, `{customer_name}`, `{company_phone}` i pozostałe z `PLACEHOLDERS`.

### Etap 3 — proces
- Wyłącz `sendWelcomeEmail()` z `createCustomer()` (`order.php:1399`).
- Przenieś blok płatności depozytu z kroku 4 wizarda do **kroku 3**, obok formularza danych; jeden przycisk.
- Mail do klienta po opłaceniu depozytu i uzupełnieniu danych (wariant kliencki obok dzisiejszego `customer_billing_completed`, który zostaje adminowy). Bez nowego statusu — zamówienie zostaje na „Potwierdzone".
- Załącznik PDF umowy przy `status_umowa_gotowa`.

### Etap 4 — testy i odbiór
- Render wszystkich 16 szablonów bez rozjechanych placeholderów.
- Wersja tekstowa zawiera działający magic link.
- Skrzynki: Gmail web + mobile, Outlook, Apple Mail. **Outlook obowiązkowo** — cały HTML jest tabelkowy właśnie pod niego.
- Klik w magic link z maila HTML → panel otwiera się zalogowany.
- Regresja dostarczalności: maile docierają jak dotąd (45 zamówień / 30 dni to baza porównawcza).
- Wysyłki testowe **wyłącznie na skrzynki Janka**, po jego zgodzie. Raporty przez `~/bin/send-to-jan`, nigdy przez `wp_mail()` projektu.

---

## 4. Zależności

- **T-121 (PayU)** — mail „Potwierdzone" mówi „bezpiecznie przez PayU (BLIK, karta, bank)". Do czasu wdrożenia T-121 albo ta wzmianka wypada, albo T-209 czeka. **Do decyzji Janka na starcie wątku.** Oba taski dotykają tego samego ekranu (krok 3 wizarda) — warto robić razem.
- **T-204 (panel klienta)** — problem hasła plaintextem znika sam z wyłączeniem `welcome`. Sprawdź, czy T-204 nie zakłada jego istnienia.
- **T-188 (alerty/newsletter)** — będzie chciał tego samego wrappera, ale maile marketingowe mają inne wymogi (opt-out, zgody). Wrapper ma być na to przygotowany strukturalnie, ale bez implementowania opt-outu teraz.

---

## 5. Poza zakresem (nie ruszamy)

- Dostarczalność SPF/DKIM/DMARC — alarm wycofany 14.07 (commit `fee89ca`), diagnoza była błędna.
- Nazwy klas, CPT, meta, shortcodów, ról (`asiaauto-*`).
- Statusy zamówienia — **nie dokładamy czternastego**.
- Generator umów, kreator, logika rezerwacji.
- Rozjazd „cena końcowa" (homepage, schema FAQ) vs „kwoty szacunkowe" (załącznik nr 2 do umowy, `class-asiaauto-contract.php:1285`) — **osobna pozycja do kolejki**, wykryta przy okazji.
- Token magic linku jest per użytkownik, nie per zamówienie (`_asiaauto_magic_token` w user_meta) — nowy kasuje poprzedni, więc klient z dwoma zamówieniami traci dostęp do pierwszego. **Osobny defekt do kolejki.**

---

## 6. Godziny

Spec mówił 14–16 h przy założeniu „treści nie ruszamy". Zakres urósł: audyt podpisów, makiety wyglądu i treści (zrobione w tym wątku, ~4 h), przepisanie 16 szablonów, wyłączenie `welcome`, przeniesienie depozytu w wizardzie, nowy mail po wpłacie, załącznik PDF.

**Realnie do wdrożenia: 18–24 h** (Janek ~3 h odbiór i testy skrzynkowe, AI ~15–21 h).

---

## Prompt do wklejenia w nowy wątek

```
Wdrażasz T-209 w projekcie primaauto — brandowany wrapper HTML dla maili
transakcyjnych plus przepisane treści. Wątek WYKONAWCZY, plan jest zamknięty.

START:
1. Przeczytaj docs/sesje/2026-08-06-T209-PAKIET-WDROZENIOWY.md (ten plik).
   Sekcje „Decyzje zamknięte" i „Stan faktyczny" są zweryfikowane 06.08 na
   produkcji — nie mierz ich od nowa i nie otwieraj zamkniętych decyzji.
2. Otwórz makiety — to jest specyfikacja wyglądu i treści, wiążąca:
   https://auratest.pl/fe4f58fec53ctmp/primaauto-t209-tresci-maili-2026-08-06.html
   https://auratest.pl/fe4f58fec53ctmp/primaauto-t209-makiety-maile-2026-08-06.html
3. Gotowe wzorce HTML wrappera, stopki, paska postępu i przycisków są
   w scripts/t209_build_tresci.py — przenieś je stamtąd, nie pisz od nowa.
4. Zapytaj Janka o jedno: kolejność wobec T-121 (PayU). Od tego zależy, czy
   mail „Potwierdzone" wspomina o PayU od razu, czy w drugim podejściu.
5. Potem etapami z sekcji 3, po kolei, z zatrzymaniem po każdym.

ZASADY:
- Source of truth = serwer (~/domains/primaauto.com.pl/public_html/...).
- Wrapper ADDYTYWNIE — nowe metody obok istniejących, nie refaktor
  class-asiaauto-order-content.php ani class-asiaauto-order.php.
- Feature flag od pierwszego dnia: wyłączenie flagi = dzisiejsze zachowanie.
- Nigdy słowo „homologacja" w treści — „przygotowanie do rejestracji".
- Powitanie „Dzień dobry", nie „Cześć".
- Maile do admina BEZ wrappera.
- Nie dokładaj statusów ani blokad płatności — umowa to zabezpiecza.
- Żadnej wysyłki do prawdziwych klientów. Testy wyłącznie na skrzynki Janka
  i tylko po jego zgodzie. Raporty przez ~/bin/send-to-jan.
- php -l przed każdym wgraniem, .bak z datą przy większych modach.
```
