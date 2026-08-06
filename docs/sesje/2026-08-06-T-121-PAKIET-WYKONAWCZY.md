# T-121 — Płatność PayU za depozyt: pakiet wykonawczy (samowystarczalny)

> Data: 2026-08-06 · Spec bazowy: `docs/roadmapa/T-121-platnosc-online-depozyt.md` (decyzje D1–D5 z 27.07)
> Stan wejściowy: plugin **0.34.22** · Rozmiar: **27–36 h** (Janek ~2 h, AI ~25–34 h) — w tym K7b (BLIK na stronie, +3–4 h)
> Strefy kruche dotknięte: **`class-asiaauto-order.php` — wyłącznie `markDepositPaid()`** (patrz §2)
> Makiety UI: `https://auratest.pl/fe4f58fec53ctmp/primaauto-t121-makiety-payu-2026-08-06.html`
> (w repo: `docs/makiety/2026-08-06-T-121-payu.html`)
>
> Ten dokument zastępuje spec tam, gdzie się z nim rozjeżdża. Rozjazdy są nazwane w §5.

---

## 0. Prompt startowy (do wklejenia w nowym wątku)

```
Wdrażasz T-121 (płatność PayU za depozyt zwrotny) w projekcie primaauto.
Przeczytaj docs/sesje/2026-08-06-T-121-PAKIET-WYKONAWCZY.md i wykonaj §6 w kolejności.
Decyzje z §2 i rozjazdy z §5 są zamknięte — nie otwieraj ich ponownie.
Makiety UI: https://auratest.pl/fe4f58fec53ctmp/primaauto-t121-makiety-payu-2026-08-06.html

Source of truth = serwer (~/domains/primaauto.com.pl/public_html/...), repo jest kontekstowe.
Przed każdą edycją: .bak z datą + php -l. Po wdrożeniu: testy z §7, bump wersji, VERSIONS.md.

NIE RUSZASZ: TRANSITIONS, LISTING_RESERVATION_MAP, calculateDeposit(), generatora umów,
kreatora poza krokiem 3/4, ręcznej akcji "oznacz depozyt jako wpłacony" w panelu.
Testy WYŁĄCZNIE na sandboxie (~/secrets/payu/sandbox.env). Produkcyjne creds
(prod-primaauto.env) wchodzą do gry dopiero po zgodzie Janka, osobnym krokiem.
```

---

## 1. Co robimy

Klient płaci zwrotny depozyt (dziś 6 150 zł) BLIK-iem lub kartą wprost w kreatorze, zamiast
przepisywać numer konta do bankowości. Przelew zostaje jako równoprawna alternatywa.

Pięć elementów:

1. **`AsiaAuto_PayU`** — OAuth, tworzenie zamówienia w bramce, weryfikacja podpisu notyfikacji.
2. **REST `POST /order/{id}/pay`** — tworzy płatność, zwraca `redirectUri` do bramki.
3. **Webhook `POST /payu/notify`** — weryfikacja podpisu → idempotentne `markDepositPaid()`.
4. **UI**: pole BLIK + przycisk PayU w kroku 3 i 4 kreatora + ekran powrotu z bramki (makiety, ekrany 1–3).
5. **Sekcja „Płatności PayU"** w karcie zamówienia w panelu, tylko do odczytu (makieta, ekran 4).

### Dlaczego to jest teraz odblokowane

**Janek ma produkcyjne creds PayU od 2026-08-06** — `~/secrets/payu/prod-primaauto.env`
(`PAYU_ENV`, `PAYU_ENDPOINT`, `PAYU_POS_ID`, `PAYU_SIGNATURE_KEY`, `PAYU_CLIENT_ID`,
`PAYU_CLIENT_SECRET`, `PAYU_SECOND_KEY_MD5`). Wniosek akceptanta jest załatwiony. Cała narracja
o „blokerze zgody PayU" z T-121 §48–58 i o niezłożonym wniosku z T-221 §3.5 jest **nieaktualna**.

---

## 2. Twarde ograniczenia (Janek, 2026-08-06)

| # | Ustalenie | Skutek |
|---|---|---|
| O1 | **Żadnych cronów.** Ani przypomnień o niedokończonej płatności, ani reconkiliacji wiszących płatności, ani alertów | zakres −5 h; §6 nie zawiera kroku „cron" |
| O2 | **Reconkiliacja niepotrzebna, bo PayU sam maluje Ruslanowi maila** o każdej transakcji, niezależnie od naszego systemu. Nasz mail przy zaksięgowaniu jest drugim kanałem — **brak naszego maila przy obecnym mailu z PayU sam jest sygnałem awarii webhooka** | wymaga tylko tego, żeby `description` w bramce zawierał nr zamówienia i nazwisko (K4) |
| O3 | Przypomnienia „dołożymy w przyszłości" — nie teraz | nie projektuj pod nie schematu danych na zapas |
| O4 | Kwota **wyłącznie** z zamrożonego `_order_deposit_amount`. Nigdy z `getConfig()`, nigdy z `calculateDeposit()` | Ruslan zmienia nastawy ad hoc — patrz memory `reference_order_config_is_operational_knob` |
| O5 | Testy tylko na sandboxie. Produkcyjne creds — osobny krok, za zgodą Janka, po przejściu §7 | |

**Strefa krucha:** `class-asiaauto-order.php` dotykamy **wyłącznie** w `markDepositPaid()`
(`:983-1002`), dokładając `do_action('asiaauto_deposit_paid', $order_id)` i mail do admina.
Reszta pliku — bez zmian.

---

## 3. Stan wejściowy (zmierzony 2026-08-06 na produkcji)

### 3.1 Kodu PayU nie ma — zero linii

Grep `payu|blik|tpay|przelewy24|stripe` w `asiaauto-sync` (bez `vendor/`): 0 trafień. To greenfield.

### 3.2 Jak depozyt działa dziś

| Element | Gdzie |
|---|---|
| oznaczenie wpłaty | `AsiaAuto_Order::markDepositPaid()` — `class-asiaauto-order.php:983-1002`. Bez guardu idempotencji, bez `do_action`, **bez maila** |
| wywołanie | tylko ręcznie z panelu — `class-asiaauto-order-admin.php:238` (akcja `mark_deposit_paid`) |
| kwota | `calculateDeposit()` `:789-805`, zamrażana w `META_DEPOSIT_AMOUNT` przy `create()` `:505`. Realny config: `deposit_percent=0`, `deposit_min=6150` → **6 150 zł** |
| karta przelewu (klient) | `class-asiaauto-order-wizard.php:285-314`, dane z REST `getTransferDetails()` — `class-asiaauto-order-api.php:741-759` |
| stan bazy | 120 zamówień bez depozytu, 15 z opłaconym |

### 3.3 Jak klient w ogóle dociera do zamówienia

Konto `asiaauto_customer` + hasło z maila powitalnego, wejście `/zamow/`. Dodatkowo **magic link
48 h, jednorazowy** (`generateMagicLink()` `:1433`, `verifyMagicLink()` `:1443` kasuje token po
użyciu). Generowany przy każdej zmianie statusu — **nowy mail unieważnia poprzedni link**.
To znaczy: „link uciekł / wpadł do spamu" nie blokuje płatności, bo jest logowanie hasłem.

### 3.4 Infrastruktura, o którą się potkniesz

| Rzecz | Fakt |
|---|---|
| **`~/secrets/` jest poza `open_basedir`** dla web/LSPHP | `is_readable()` = false w kontekście HTTP. PrimaAuto obchodzi to katalogiem `~/domains/primaauto.com.pl/private-google` (patrz `class-asiaauto-indexing.php:29-35` — 1611 cichych porażek przez 3 tygodnie, zanim to wyszło). **Dla PayU zrób tak samo** — K1 |
| Plugin **nie ma żadnych własnych tabel** | grep `dbDelta|CREATE TABLE`: 0 trafień. Nie zakładaj tabeli płatności jak w digicie — patrz K3 |
| REST namespace | `asiaauto/v1` (`class-asiaauto-order-api.php:28`) |
| Gotowy `permission_callback` | `canAccessOrder()` `:137-151` — sprawdza zalogowanie + właściciela + admina. Użyj go, nie pisz nowego |
| JS kreatora | `assets/js/asiaauto-order-wizard.js`, render karty przelewu ~`:635`, przełączanie kroków `showStep()` `:174` |
| Design system kreatora | `#1B2A4A` primary, `#D63031` accent, Inter, radius 6px (`assets/css/asiaauto-order-wizard.css:16-28`) |

---

## 4. Wzorzec: `digit-kreator` — co bierzemy, a co robimy inaczej

Źródło: `~/projekty/digit/src/plugins/digit-kreator/`. Integracja realna, przetestowana
w sandboxie (w bazie trzy prawdziwe `payu_order_id`).

**Bierzemy 1:1 (po przepisaniu na konwencję `AsiaAuto_*`, bez namespace'ów):**

| Plik | Co |
|---|---|
| `includes/integrations/class-payu.php` (137 l.) | OAuth z transientem `:60-82`, `create_order()` `:84-122` — w tym łapanie `redirectUri` **z JSON albo z nagłówka `Location`** (`'redirection' => 0`), `verify_notify_signature()` `:124-136` (parsowanie `OpenPayU-Signature`, `hash_equals`) |
| `includes/modules/class-payments.php` | filtr `allowed_redirect_hosts` `:21-31` — **bez niego `wp_safe_redirect()` zablokuje bramkę**; kształt payloadu `create_order` `:94-112` |

**Trzy rzeczy robimy INACZEJ — to są defekty digicie, nie wzorzec:**

1. **Idempotencja.** `handle_notify()` przy powtórzonym `COMPLETED` księguje drugi raz
   i wysyła drugiego maila (`class-payments.php:155-176`). PayU ponawia notyfikacje rutynowo.
   U nas: atomowy przeskok stanu płatności (`WHERE status = 'pending'`), księgowanie **tylko**
   gdy przeskok faktycznie nastąpił.
2. **Podpis weryfikowany bezwarunkowo.** W digicie `if ($sig_header && !verify…)` — brak nagłówka
   przechodzi bez weryfikacji. Przy 6 150 zł to dziura. U nas: brak nagłówka = 403.
3. **Kwota z zamówienia, nie z opcji.** Digit liczy z `digit_kreator_settings` w locie.
   U nas — `_order_deposit_amount` (O4).

**Dwie pułapki już rozwiązane w digicie** (`~/projekty/digit/docs/strona/raport-testow-2026-05-15.md:89-108`):
`wp_safe_redirect()` vs host bramki (jak wyżej) oraz „stub trasy REST wygrywa z realnym
handlerem" — WP trzyma **pierwszą** rejestrację danej trasy, więc nie zostawiaj zaślepki.

---

## 5. Rozjazdy wobec dokumentów — co jest nieaktualne

| Gdzie | Co mówi | Jak jest |
|---|---|---|
| `T-121:48-58` „Bloker #1 — zgoda PayU" | „krok zero: pytamy PayU o schemat", „bez pisemnego potwierdzenia nie startujemy" | **Creds produkcyjne są od 06.08.** Bloker nie istnieje |
| `T-121:80-82` Etap 0 — spike | zapytanie do PayU + test sandbox przed wyceną | zapytanie odpada; test sandbox wchodzi w K7 |
| `T-121:87, :102` idempotencja | opisana jako wymóg, bez mechanizmu | mechanizm: K3 + K5 |
| `T-121:41` zamrożenie kwoty | „zamrażana w momencie generowania umowy" | kod zamraża w `create()` `:505`, czyli **w kroku 1**, przed weryfikacją. Nie zmieniamy tego — tylko odnotowujemy, że kwota jest znana wcześniej, niż spec sugeruje |
| `T-121:88` UI | „przycisk w kroku 4" | **D1 wygrywa: krok 3**, widoczny też w kroku 4 |
| `T-221:88-90, :102` wniosek do PayU | „wysyła Janek, odblokowuje T-121" | zrobione |
| `T-221:3` status | „gotowe do startu, blokuje T-121" | nie blokuje niczego; zostają trzy zadania higieniczne (§9) |
| `QUEUE.md:23, :30` | T-221 „12–18 h", T-121 „32–42 h" | roadmapa: 4–6 h i 24–32 h. Popraw przy okazji |

---

## 6. Kroki wykonawcze (w tej kolejności)

**K1 — dostęp do sekretów spod weba (0,5 h).**
`mkdir ~/domains/primaauto.com.pl/private-payu` (poza `public_html`, więc 404 po HTTP),
skopiuj tam `sandbox.env`. Wzorzec czytania: `class-asiaauto-indexing.php:29-35`.
**Sprawdź `is_readable()` z kontekstu HTTP, nie z CLI** — to jest cała pułapka.
Produkcyjny `.env` kopiujesz tam dopiero w K9.

**K2 — `class-asiaauto-payu.php` (3–4 h).**
Port `digit-kreator/includes/integrations/class-payu.php` na `AsiaAuto_PayU`:
`getAccessToken()` (transient `asiaauto_payu_token`, TTL = `expires_in − 60`), `createOrder()`,
`verifyNotifySignature()`, `isConfigured()`. Endpoint z `PAYU_ENV`. `require_once` w
`asiaauto-sync.php` obok pozostałych (`:54-58`).

**K3 — magazyn płatności: meta, nie tabela (1 h).**
Plugin nie ma warstwy tabel (§3.4) — nie zakładaj jej pod 1–2 wiersze na zamówienie.
`_order_payments` = JSON na zamówieniu, tablica wpisów:
`{attempt, ext_order_id, payu_order_id, amount, status, created_at, updated_at}`.
Idempotencja: odczyt → modyfikacja → zapis **pod `wp_cache`/transient lockiem na `order_id`**
(wzorzec locka jest już w importerze — transient 10 min, `class-asiaauto-importer.php`).
Księgowanie tylko gdy wpis faktycznie przeszedł `pending → completed`.

**K4 — REST `POST /order/{id}/pay` (2–3 h).**
Rejestracja obok pozostałych (`class-asiaauto-order-api.php:45-120`),
`permission_callback` → `canAccessOrder` (gotowe, `:137`).
Guardy: status w `['potwierdzone','umowa_gotowa','podpisane']`, `_order_deposit_paid === '0'`,
listing wciąż dostępny, brak otwartej płatności młodszej niż 15 min.
Payload: kwota z `_order_deposit_amount` × 100, `extOrderId = "PA-{order_id}-{attempt}"`,
`continueUrl` = krok 4 kreatora z `?payu=return`, `notifyUrl` = `rest_url('asiaauto/v1/payu/notify')`,
**`description` = „Depozyt zwrotny, zam. #{id}, {imię nazwisko}"** (to jest warunek O2 —
bez tego mail od PayU do Ruslana jest bezużyteczny).
Filtr `allowed_redirect_hosts` dla `*.payu.com`.

**K5 — webhook `POST /payu/notify` (3–4 h).**
`permission_callback => '__return_true'`, podpis weryfikowany **bezwarunkowo** (brak nagłówka = 403).
Kolejność: parsuj `extOrderId` → znajdź zamówienie i wpis płatności → **porównaj kwotę
z `_order_deposit_amount`** (rozjazd = nie księguj, zaloguj, 200) → przy `COMPLETED` atomowy
przeskok stanu → dopiero wtedy `markDepositPaid()`.
Zawsze 200 dla poprawnie podpisanej notyfikacji, którą świadomie zignorowaliśmy — inaczej PayU
będzie ponawiać w kółko.

**K6 — `markDepositPaid()` + mail (1 h).**
Dołóż `do_action('asiaauto_deposit_paid', $order_id, $source)` na końcu metody
(`class-asiaauto-order.php:1000`). Na hooku: mail do admina („Depozyt 6 150 zł zaksięgowany,
zam. #1247, PayU") i mail do klienta. Szablony przez `AsiaAuto_Order_Content::getTemplate()`,
jak reszta. **Metody nie przepisujesz** — dokładasz linię.

**K7 — UI kreatora (4–6 h).** Makiety, ekrany 1–3.
Krok 3: blok „Wpłać depozyt zwrotny" po zapisaniu danych — przycisk, komunikat D2, link do
`/depozyt/`, przelew zwinięty jako alternatywa, w stopce bloku linki do `/regulamin-uslugi/`
i polityki prywatności. Krok 4: ten sam blok, przelew rozwinięty.
Ekran powrotu (`?payu=return`): domyślnie „przetwarzamy", odpytuje `GET /order/{id}` co 3 s
przez ~30 s, potem „potwierdzimy mailem". **Stan „opłacone" wyłącznie z `deposit_paid`
z serwera — nigdy z faktu powrotu z bramki.**

**K7b — BLIK z kodem na naszej stronie (3–4 h).** Decyzja Janka 06.08: najszybsza ścieżka dla
klienta, przy 79,6 % ruchu mobilnego kluczowa.

Układ: **pole na 6-cyfrowy kod BLIK wprost w kreatorze**, pod nim „Inne metody płatności" →
przycisk PayU z K7 (redirect na bramkę: karty, Google Pay, Apple Pay, przelew online).

API: to samo `POST /api/v2_1/orders`, rozszerzone o
`payMethods.payMethod = { type: 'BLIK_AUTHORIZATION_CODE', value: '<6 cyfr>' }`.
Wystarczy OAuth `client_credentials` — ten sam token co w K2, **bez** `trusted_merchant`
(ten tryb jest potrzebny dopiero dla BLIK one-click z zapamiętanym tokenem — nie robimy).
Wariant redirectowy z K7 to w API `payMethod = { type: 'PBL', value: 'blik' }`.

Przepływ: klient wpisuje kod → `POST /order/{id}/pay` z kodem → odpowiedź `WARNING_CONTINUE_3DS`
/ `SUCCESS` → ekran „Potwierdź płatność w aplikacji banku" z odliczaniem → odpytywanie
`GET /order/{id}` co 3 s → wynik z **webhooka**, nie z odpowiedzi na `pay` (O2 bez zmian:
prawdę o zaksięgowaniu ustala wyłącznie notyfikacja).

Obsłuż osobno: kod odrzucony przez bank, kod wygasły (klient wpisał stary), brak potwierdzenia
w czasie, klient wpisał kod z innej aplikacji. Każdy z tych stanów wraca do pola z komunikatem
i nie tworzy nowego wpisu płatności, tylko zamyka poprzedni.

⚠️ **Zależność po stronie PayU:** BLIK z kodem autoryzacyjnym musi być włączony na POS-ie.
Do sprawdzenia w panelu akceptanta przed startem K7b — jeśli nie ma, to zgłoszenie do PayU,
nie kod. Testowe kody autoryzacyjne: dokumentacja „Testowanie BLIK" (np. `200201` — autoryzacja
pozytywna z rejestracją tokenu).

Źródła: `developers.payu.com/europe/docs/payment-solutions/blik/integration/`,
`.../blik/testing/`, `developers.payu.com/europe/api/`.
Widget `payu-widget.js` **odrzucony** — żyje pod starą dokumentacją (`/pl/payu_express.html`),
poza bieżącą strukturą docs.

**K8 — panel: sekcja „Płatności PayU" (1–2 h).** Makieta, ekran 4.
Tabela read-only w karcie zamówienia (`class-asiaauto-order-admin.php`): data, kwota, status,
`payu_order_id`, numer próby + linia „Depozyt oznaczony automatycznie {data} (notyfikacja PayU)".
Bez przycisków akcji. Ręczne „oznacz depozyt jako wpłacony" zostaje nietknięte.

**K9 — sandbox → produkcja (1–2 h, za zgodą Janka).**
Pełne §7 na sandboxie. Dopiero potem `prod-primaauto.env` do `private-payu`, `PAYU_ENV=prod`,
jedna realna transakcja na małą kwotę i zwrot. Bump wersji, `docs/VERSIONS.md`, popraw `QUEUE.md`.

---

## 7. Testy

**Automatyczne (`tmp/` skrypty, sandbox)**
- Podpis: poprawny → księguje; zmanipulowany → 403; **brak nagłówka → 403** (to jest ten
  przypadek, w którym digit przepuszcza).
- Idempotencja: ta sama notyfikacja `COMPLETED` ×3 → `markDepositPaid()` raz, mail raz.
- Kwota: notyfikacja z kwotą ≠ `_order_deposit_amount` → nie księguje, log, 200.
- Guard: `POST /order/{cudze}/pay` → 403. Niezalogowany → 401.
- Guard stanu: zamówienie z `deposit_paid = 1` → odmowa utworzenia płatności.

**🔒 Test spójności kwoty (kluczowy).** Utwórz zamówienie → **zmień `deposit_min` w panelu** →
otwórz płatność. W bramce musi być kwota z zamówienia, nie nowa z panelu. Sprawdź komplet:
umowa PDF = mail = karta przelewu = bramka.

**Półautomatyczne (sandbox)**
- **BLIK na stronie (K7b):** kod poprawny → potwierdzenie w aplikacji → zaksięgowanie;
  kod odrzucony; kod wygasły; brak potwierdzenia w czasie. Po każdym z tych stanów pole
  przyjmuje kolejny kod i nie mnoży wpisów płatności.
- Karta testowa 3DS, płatność odrzucona, porzucenie na bramce (ścieżka redirect z K7).
- Ponowna próba po odrzuconej → nowy `extOrderId` z sufiksem, stary wpis na `expired`.
- Powrót z bramki przy jeszcze nieodebranej notyfikacji → ekran „przetwarzamy", nie „opłacone".

**Chrome MCP**
- Krok 3 → „Zapłać" → bramka sandbox → powrót → status w kreatorze i w panelu.
- Mobile (79,6 % ruchu): czy redirect i powrót nie gubią sesji.

---

## 8. Definicja zrobionego

- Klient płaci BLIK-iem/kartą w kroku 3 i 4; przelew nadal dostępny.
- Webhook idempotentny, podpis weryfikowany bezwarunkowo, kwota z zamówienia.
- Depozyt księguje się sam; Ruslan dostaje mail z PayU **i** nasz.
- W panelu widać historię płatności; ręczna ścieżka nietknięta.
- `/regulamin-uslugi/` i polityka prywatności podlinkowane spod przycisku płatności.
- Testy §7 przeszły na sandboxie; produkcja włączona osobnym krokiem.

---

## 8a. Do rewizji przy K7 (Janek, 06.08) — nie bloker

**Numer i status zamówienia przy kwocie.** W kreatorze jedno i drugie jest, ale wyłącznie
w prawej kolumnie: `aa-wiz__status-badge` (`class-asiaauto-order-wizard.php:433`, widoczny
od kroku 2) i `aa-wiz__order-num` (JS `:716`). Przy płatności to za mało — numer zamówienia
powinien stać **obok kwoty w bloku płatności**, bo to ten sam numer, który idzie w tytule
przelewu i którym klient posługuje się przy reklamacji. Do przemyślenia przy budowie K7:
czy wystarczy dopisanie wiersza do bloku, czy nagłówek kreatora ma na stałe nieść
„#1247 · Potwierdzone". Makiety tego nie pokazują — nie mają narysowanej prawej kolumny.

## 9. Poza zakresem — świadomie

- **Crony**: przypomnienia o niedokończonej płatności, reconkiliacja, alerty (O1, O3).
- **BLIK one-click / token** (`grant_type=trusted_merchant`) — dla jednorazowego depozytu bez sensu.
- **Widget `payu-widget.js`** — schyłkowy, patrz K7b.
- **Refund przez API** — zwroty robi Ruslan przelewem, tak jak dziś. `T-121:90` zostaje otwarte
  (+6–8 h, osobna decyzja).
- **Guard „nie rezerwuj auta bez depozytu"** — luka realna (`T-121:96`: przejście
  `podpisane → zarezerwowane` nie sprawdza depozytu), ale niezależna od PayU. Osobny task.
- **Higiena T-221** (~1,5 h, do zrobienia obok, nie blokuje): PayU jako odbiorca danych
  w polityce prywatności (post 198526 — dziś 0 wystąpień, **wymóg RODO od chwili pobierania
  płatności**), zdjęcie `noindex` z `/regulamin-uslugi/` i `/depozyt/`, sprzeczność starego
  `/regulamin/` (post 153866, §4 ust. 3 „usługi nieodpłatne"). Korekta `/finansowanie/` (30/70)
  nadal czeka na Ruslana — pkt 6 z `docs/biznes/2026-07-27-punkty-do-weryfikacji-ruslan.md`.
