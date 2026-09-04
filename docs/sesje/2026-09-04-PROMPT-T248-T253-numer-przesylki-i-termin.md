# T-248 + T-253 — pakiet wykonawczy (stan na 2026-09-04)

> Dwa małe zadania w karcie zamówienia, wykonywane **jednym wejściem**, bo dotykają tego samego
> pliku (`class-asiaauto-order-admin.php`, 3090 linii) i tej samej sekcji karty. Rozdzielenie ich
> na dwie sesje oznacza dwa razy backup, dwa razy deploy i ryzyko konfliktu edycji.
>
> Wtyczka na wejściu: **0.38.2**. Wyjście: **0.39.0** (dwie funkcje panelu + zmiana w kroku 5 kreatora).

---

## §1. Zakres — co dokładnie powstaje

### T-253 — termin dostawy edytowalny per zamówienie
Pole „Termin dostawy (dni)” w karcie zamówienia, obok VIN-u. Domyślnie pokazuje wartość zapisaną
przy zamówieniu (dziś zawsze 120), zmiana wpływa **wyłącznie na to zamówienie** i wchodzi do umowy
przy następnej generacji. Puste = fallback do konfiguracji, czyli zachowanie dzisiejsze.

### T-248 — numer przesyłki + informacja do klienta (zakres przedefiniowany 04.09)
1. Pole „Numer przesyłki” w karcie zamówienia + link do śledzenia u przewoźnika **dla Ruslana**
   (składany z szablonu URL w konfiguracji zamówień).
2. Przycisk „Wyślij informację o transporcie” — mail do klienta z numerem, **bez linku zewnętrznego**;
   jedyny odnośnik w mailu to przycisk do naszego panelu (magic link).
3. Numer + odnośnik do śledzenia **w panelu klienta**, krok 5 kreatora (sekcja „Realizacja”).

**Automat przewoźnika (odpytywanie Maerska o pozycję, następny port, ETA) NIE wchodzi w ten zakres** —
zostaje jako osobna pozycja (T-254, zaparkowana).

---

## §2. Decyzje zamknięte (nie otwierać ponownie)

| # | Decyzja | Powód |
|---|---|---|
| D-1 | **W mailu do klienta nie ma linku do przewoźnika.** Mail podaje numer jako tekst + przycisk do naszego panelu. | Decyzja Janka 04.09: ruch ma iść do naszego panelu, nie do Maerska. Argument dostarczalności jest wtórny (o niej decyduje SPF/DKIM/DMARC i reputacja, nie pojedynczy link), ale kierunek jest ten sam. |
| D-2 | **Numer widzi też klient** — w kroku 5 kreatora, razem z odnośnikiem do śledzenia. | Krok 5 (`class-asiaauto-order-wizard.php:382`) już działa i renderuje pasek transportu; klient wchodzi magic linkiem, **T-204 nie jest do tego potrzebny**. |
| D-3 | **Link do śledzenia składa się z szablonu w konfiguracji zamówień** (`tracking_url_template`, domyślnie Maersk, placeholder `{tracking_number}`). Pusty szablon = brak odnośnika, sam numer. | Jeden przewoźnik dziś; podmiana szablonu obsługuje kolejnego bez deploya. Gdy Ruslan wpisze w polu pełny adres (`http…`), używamy go wprost. |
| D-4 | **Mail wychodzi wyłącznie po świadomym kliknięciu.** Zapis numeru nic nie wysyła, zmiana statusu nic nie wysyła. | Wzorzec T-220 (aneks VIN): generowanie ≠ wysyłka. |
| D-5 | **Nie dotykamy statusów.** Numer przesyłki i termin dostawy to metadane opisowe, nigdy warunek przejścia. | `class-asiaauto-order.php` = strefa krucha. |

---

## §3. Stan faktyczny w kodzie (zweryfikowany 04.09, produkcja)

### Termin dostawy
- `class-asiaauto-order.php:128` — `META_DELIVERY_ESTIMATE = '_order_delivery_estimate'`.
- `:518` — jedyny zapis w całej wtyczce: kopia `default_delivery_days` przy zakładaniu zamówienia.
  **Nigdzie nie da się tego zmienić** — brakuje wyłącznie pola w UI.
- `class-asiaauto-contract.php:301` — `delivery_days` bierze wartość z zamówienia, config to fallback.
  **Umowa zadziała po samym dołożeniu pola**, bez ruszania szablonów (§4 ust. 1 pośrednictwo `:848`,
  §4 leasing `:1380`).
- Pomiar bazy: **194 zamówienia, wszystkie z wartością 120** — brak zamówień bez meta, brak wartości
  odstających.
- 🔴 **Rozjazd fallbacku do naprawy w tym samym ruchu:** 120 w `class-asiaauto-order.php:371,518`
  i `class-asiaauto-contract.php:301`, ale **60** w `class-asiaauto-order-wizard.php:465,559,612,641`
  i `class-asiaauto-order-api.php:194` — klient w kreatorze widzi inną liczbę niż ta, która wchodzi
  do umowy.

### Numer przesyłki
- **Nie istnieje** — grep po `tracking|kontener|przesyłk` w `includes/` nie zwraca nic w obszarze zamówień.
- Statusów `w_drodze` jest dziś **27** (plus 23 `na_placu`) — funkcja ma na czym pracować od razu.

### Wzorzec do skopiowania (T-220, aneks VIN)
- Akcja: `class-asiaauto-order-admin.php:170` (`case 'set_annex_vin_state'`) → `:697` handler →
  `:735` `sendAnnexVinEmail()`.
- Bramka bezpieczeństwa stoi **przed** switchem (`:120` nonce `aa_order_action`, `:124`
  `current_user_can(AsiaAuto_Security::ORDER_CAP)`) — nowy `case` dziedziczy ją za darmo.
- Mail: `AsiaAuto_Order_Content::getTemplate('annex_vin_sent')` + `buildVariables()` + `wp_mail()`,
  z warunkiem `customer_email_notifications` i wpisem do `AsiaAuto_Logger`.
- Znacznik wysyłki: `META_ANNEX_VIN_SENT_GMT` (`class-asiaauto-order.php:165`).

### Maile — stan faktyczny
- Flaga `asiaauto_order_email_html` = **1** (wrapper T-209 aktywny).
- W bazie leży **16 gotowych szablonów HTML** (`asiaauto_order_email_templates`) — nowy szablon musi
  być pełnym HTML-em w tej samej strukturze, inaczej odstaje wyglądem od reszty korespondencji.
- Droga budowy szablonu jest gotowa: `scripts/t209_build_tresci.py` (wpis do `MAILS` → makieta do
  akceptu) → `scripts/t209_export_templates.py` → JSON → wgranie w opcję. **Nie pisz HTML-a ręcznie.**
- 🔴 **Pułapka:** `AsiaAuto_Order_Mail::wrapHtml()` i `renderHead()` czytają `self::$context`,
  ustawiany wyłącznie przez hooki statusowe. Przy wysyłce z panelu kontekst jest pusty → nagłówek
  „oferta (#numer) — STATUS” i pasek postępu **nie wchodzą**. Przed `wp_mail()` wywołaj
  `AsiaAuto_Order_Mail::setContext($order_id)`.
  📌 *Zauważone obok, nie ruszam bez decyzji:* `sendAnnexVinEmail()` (T-220) ma dokładnie ten brak.

### Panel klienta (krok 5)
- Markup: `class-asiaauto-order-wizard.php:382-397` — pasek transportu (`.aa-wiz__tracking`) +
  ramka `.aa-wiz__info-box` z „Szacowany czas dostawy” i telefonem. **To jest miejsce na numer.**
- Render: `assets/js/asiaauto-order-wizard.js:686` `renderTracking()`, wołany z `:789`.
- Payload: `class-asiaauto-order-api.php:361` blok `price` (tam siedzi już `delivery_days`).
- Autoryzacja: `canAccessOrder()` (`:137`) — zalogowany właściciel zamówienia albo admin. Numer
  nie wycieknie poza zamówienie.

---

## §4. Kroki wykonawcze — T-253 (rób pierwsze, jest krótsze)

1. **Pole w karcie.** `class-asiaauto-order-admin.php`, sekcja „Pojazd” (tabela `aa-order-meta`,
   okolice `:1780`, wiersz VIN) — dołóż wiersz „Termin dostawy”: `input type=number`, `min=1`,
   `max=999`, wartość = `get_post_meta($order_id, META_DELIVERY_ESTIMATE)`, placeholder = wartość
   z konfiguracji. Pod polem `description`: co pole robi i że puste = wartość z ustawień.
2. **Podgląd daty.** Obok pola pokaż wyliczoną datę (`data umowy + N dni`, `AsiaAuto_Contract::resolveContractDate()`),
   żeby Ruslan widział, co zobaczy klient. Wyłącznie prezentacja, nic nie zapisujemy.
3. **Zapis.** `handleUpdateMeta()` (`:257`), obok bloku `order_vin` (`:277`):
   `if (isset($_POST['order_delivery_days']))` → `(int)`, zakres 1–999, wartość `0`/puste →
   `delete_post_meta()` (wtedy działa fallback z `contract.php:301`).
4. **Rozjazd 60/120.** Ujednolić na 120 w `class-asiaauto-order-wizard.php:465,559,612,641`
   i `class-asiaauto-order-api.php:194`. To pięć literałów fallbacku, żadnej logiki.

---

## §5. Kroki wykonawcze — T-248

5. **Meta i konfiguracja.** `class-asiaauto-order.php`: `META_TRACKING_NUMBER = '_order_tracking_number'`,
   `META_TRACKING_SENT_GMT = '_order_tracking_sent_gmt'` (obok istniejących stałych, nic nie przenosimy).
   `handleSaveConfig()` (`:812`) + ekran konfiguracji (`:2936` okolice): `tracking_url_template`,
   domyślnie `https://www.maersk.com/tracking/{tracking_number}`.
6. **Pole w karcie.** Nowa sekcja „Transport” pod „Pojazd”: input numeru + pod nim gotowy odnośnik
   („Sprawdź u przewoźnika ↗”, `target="_blank" rel="noopener"`), gdy numer i szablon są wypełnione.
   Sanityzacja przy zapisie: `strtoupper`, dozwolone `A-Z0-9-/`, max 40 znaków. Gdy wpisany pełny
   adres (`http…`) — zapisujemy jak jest i używamy zamiast szablonu (`esc_url` przy renderze).
7. **Akcja wysyłki.** `case 'send_tracking_info'` w switchu (`:132`) → `handleSendTrackingInfo()`:
   guard „brak numeru — nie ma czego wysłać”, guard `customer_email_notifications`, guard adresu
   klienta, `AsiaAuto_Order_Mail::setContext($order_id)`, `wp_mail()`, zapis `META_TRACKING_SENT_GMT`,
   `AsiaAuto_Logger::info()`, komunikat `add_settings_error` z **adresem odbiorcy w treści**.
   Przycisk: `onsubmit="return confirm(...)"` z adresem klienta, a obok — „ostatnio wysłano: …”,
   gdy znacznik istnieje.
8. **Szablon maila** `tracking_sent`: wpis w `defaultEmailTemplates()` + `getTemplateKeyLabels()`
   (`class-asiaauto-order-content.php:932`). Nowe placeholdery w `PLACEHOLDERS` (`:26`)
   i `buildVariables()` (`:709`): `{tracking_number}`, `{tracking_url}` (drugi istnieje dla Ruslana,
   **domyślna treść go nie używa** — D-1). Treść buduj przez `scripts/t209_build_tresci.py`,
   makietę pokaż Jankowi do akceptu **przed** wgraniem opcji.
9. **Panel klienta.** `class-asiaauto-order-api.php` — dołóż do payloadu (blok `price` albo nowy
   `shipping`): `tracking_number`, `tracking_url`. Markup: wiersz w `.aa-wiz__info-box` kroku 5
   (`class-asiaauto-order-wizard.php:393`). JS: uzupełnienie w `renderTracking()`
   (`asiaauto-order-wizard.js:686`) — wiersz pokazuje się **tylko gdy numer istnieje**, przez
   `escHtml`/`escAttr` (funkcje są na dole pliku).

---

## §6. Zabezpieczenia i czego nie ruszać

**Bramki, które dostajesz za darmo — nie obchodź ich:** nonce `aa_order_action` + `ORDER_CAP`
przed switchem panelu; `canAccessOrder()` w REST. Nowa akcja i nowe pole payloadu są nimi objęte,
o ile trzymasz się istniejących ścieżek.

**Escaping:** numer trafia w trzy miejsca — karta admina (`esc_attr`/`esc_html`), mail
(placeholder w szablonie, wartość przez `buildVariables()`), krok 5 (`escHtml` w JS). Link zawsze
`esc_url`, a numer wstawiany do szablonu URL przez `rawurlencode()`.

**NIE ruszasz:** `STATUSES`, `TRANSITIONS`, `LISTING_RESERVATION_MAP`, `changeStatus()`,
`AsiaAuto_Contract::generate()`/`regenerate()`, szablonów §-ów umowy, kroków 1–4 kreatora,
16 istniejących szablonów maili.

**Ryzyka:**
1. 🔴 **Mail do prawdziwego klienta w testach.** 194 zamówienia to żywi ludzie. E2E wyłącznie na
   zamówieniu zakładanym skryptem (`wp_insert_post`, wzorzec T-217) albo z filtrem `pre_wp_mail`.
   Przy T-217 poszły tak 4 maile do klientów.
2. 🔴 **Regeneracja umowy przy statusie „podpisane”** (`class-asiaauto-order-admin.php:437`) kasuje
   podpisane pliki i wysyła maila. Zmiana terminu **niczego nie regeneruje samoczynnie** — Ruslan
   klika regenerację świadomie. Nie dokładaj automatu.
3. 🟡 **Kolizja plikowa.** T-249, T-218 i T-209 czekają na ten sam `class-asiaauto-order-admin.php`.
   Po wdrożeniu zapisz w QUEUE, że plik jest wolny.
4. 🟡 **Wrapper maila.** Bez `setContext()` mail wyjdzie bez nagłówka i paska postępu — wygląda jak
   awaria, a jest brakiem jednej linii.

---

## §7. Testy przed ogłoszeniem „zrobione”

| # | Test | Dowód |
|---|---|---|
| 1 | `php -l` na każdym edytowanym pliku | wynik komendy |
| 2 | Zapis terminu 90 na zamówieniu testowym → PDF pośrednictwa | §4 ust. 1 mówi „90 dni” |
| 3 | To samo dla wzorca leasingowego | §4 umowy UL |
| 4 | Puste pole terminu → PDF | wraca 120 (fallback), bez błędu |
| 5 | Kreator klienta krok 5 | „Szacowany czas dostawy” = ta sama liczba co w umowie |
| 6 | Zapis numeru przesyłki → odnośnik w karcie | otwiera stronę przewoźnika z numerem |
| 7 | Wysyłka maila na `js@auranet.com.pl` (zamówienie testowe) | mail ma numer, nagłówek, pasek postępu i **zero linków zewnętrznych** |
| 8 | Ten sam mail w kliencie bez HTML | AltBody czytelny |
| 9 | Krok 5 z numerem i bez numeru | wiersz pojawia się i znika, brak pustej etykiety |
| 10 | Ponowne kliknięcie wysyłki | potwierdzenie + widoczna data ostatniej wysyłki |

Na koniec: bump do **0.39.0**, `docs/VERSIONS.md`, wpis w `docs/QUEUE.md`, aktualizacja
`docs/kosztorys/dane/postep.json` (obie pozycje z „do zrobienia” do changelogu) + `build_postep.py --deploy`,
commit.

---

## §8. Prompt do wklejenia w nowym wątku

```
Wdrażasz T-253 (termin dostawy per zamówienie) i T-248 (numer przesyłki + informacja do klienta)
w projekcie primaauto. Oba w jednym wejściu — dotykają tego samego pliku.

START:
1. Przeczytaj docs/sesje/2026-09-04-PROMPT-T248-T253-numer-przesylki-i-termin.md w całości.
   §2 to decyzje ZAMKNIĘTE — nie otwierasz ich ponownie. §3 to zweryfikowany stan kodu z numerami
   linii; sprawdź, czy linie się nie przesunęły, ale nie planuj od nowa tego, co jest rozpisane.
2. Kolejność: §4 (T-253, krótsze) → §5 (T-248). Po §4 zrób test 2 i 3 z §7, zanim ruszysz dalej.

ZASADY:
- Source of truth = serwer (~/domains/primaauto.com.pl/public_html/...). Repo jest kontekstowe.
- Przed każdą edycją kopia .bak z datą, po edycji php -l — plik produkcyjny edytujesz in-place.
- Metoda addytywna: nowe pola i nowa metoda OBOK istniejących, zero refaktoru
  class-asiaauto-order.php i class-asiaauto-order-admin.php.
- Termin dostawy i numer przesyłki to metadane opisowe — NIGDY warunek przejścia statusu.
- Mail wychodzi WYŁĄCZNIE po kliknięciu przycisku. Zapis numeru i zmiana statusu nie wysyłają nic.
- W mailu do klienta nie ma linków zewnętrznych — jedyny odnośnik to przycisk do naszego panelu.
- W testach wyciszaj pocztę filtrem pre_wp_mail albo testuj na zamówieniu zakładanym skryptem.
  194 zamówienia w bazie to prawdziwi klienci.
- Treść maila buduj przez scripts/t209_build_tresci.py i pokaż makietę do akceptu PRZED wgraniem
  opcji asiaauto_order_email_templates.

NA KONIEC: testy z §7 (wszystkie dziesięć, z dowodem), bump 0.39.0, VERSIONS.md, QUEUE.md,
postep.json + build_postep.py --deploy, commit.
```
