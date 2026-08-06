# PayU — prompt startowy do nowego wątku (stan na 2026-08-06)

> Wątek **rozpoznawczy**, nie wdrożeniowy. T-121 (płatność PayU za depozyt) jest zablokowany
> i pierwsza rzecz do rozstrzygnięcia brzmi: czy w ogóle teraz w to wchodzimy, a jeśli tak —
> to od której strony. Poniżej stan zmierzony 06.08 (nie z pamięci — sprawdzony w repo,
> na produkcji i w bazie).

---

## Prompt do wklejenia

```
Badasz wątek PayU w projekcie primaauto — płatność online za depozyt zwrotny.

START:
1. Przeczytaj docs/sesje/2026-08-06-PROMPT-payu-rozpoznanie.md (ten plik) — sekcja
   „Stan faktyczny" jest zweryfikowana 06.08, nie sprawdzaj jej od nowa.
2. Przeczytaj docs/roadmapa/T-121-platnosc-online-depozyt.md i T-221-pakiet-prawny-payu.md
   — tam są decyzje z 27.07 (D1-D5), nie otwieraj ich ponownie.
3. Dopiero wtedy zaproponuj plan wątku. Nie zaczynaj kodować.

TO JEST WĄTEK ROZPOZNAWCZY. Efektem ma być decyzja i przygotowane wejście, nie kod:
- co dokładnie blokuje start i w jakiej kolejności to zdjąć,
- treść wniosku do PayU (do akceptu Janka),
- ocena, ile realnie zostało pracy, na podstawie wzorca z digit-kreator.

ZASADY:
- Source of truth = serwer (~/domains/primaauto.com.pl/public_html/...). Repo jest kontekstowe.
- Nie dotykasz statusów zamówienia, kreatora ani generatora umów. Depozyt ma dziś jedno
  miejsce wpięcia: AsiaAuto_Order::markDepositPaid().
- Kwota depozytu do płatności bierze się WYŁĄCZNIE z _order_deposit_amount zamrożonego
  na zamówieniu, nigdy z bieżącej konfiguracji. Ruslan zmienia config ad hoc.
- Sandbox PayU tylko przez ~/secrets/payu/sandbox.env. Żadnych testów na produkcyjnych
  danych płatniczych, żadnej prawdziwej transakcji bez zgody Janka.
- Nie wysyłasz niczego do Ruslana ani do PayU. Draft → Janek → dalej.
```

---

## Stan faktyczny (zmierzony 2026-08-06)

### 1. Kodu PayU w PrimaAuto NIE MA — zero linii

Grep po `payu|blik|tpay|przelewy24|stripe` w `asiaauto-sync` (bez `vendor/`): **0 trafień**.
`oauth` w pluginie to wyłącznie Google Indexing API. **T-121 to greenfield.**

Depozyt działa dziś tak:

| Element | Gdzie |
|---|---|
| oznaczenie wpłaty | `AsiaAuto_Order::markDepositPaid()` — `class-asiaauto-order.php:983-1002`. **Bez guardu idempotencji, bez `do_action`** — to jest punkt wpięcia webhooka |
| wywołanie | tylko ręcznie z panelu — `class-asiaauto-order-admin.php:238` (akcja `mark_deposit_paid`) |
| kwota | `calculateDeposit()` `:789-805`, zamrażana w `META_DEPOSIT_AMOUNT` przy `create()` `:505`. Realny config: `deposit_percent=0`, `deposit_min=6150` → **6 150 zł**. Fabryczne `configDefaults()` (10% / 30 000) na produkcji nieużywane |
| co widzi klient | krok 4 kreatora, karta przelewu — `class-asiaauto-order-wizard.php:285-314`, dane z REST `class-asiaauto-order-api.php:752-756` |
| stan bazy | 120 zamówień bez depozytu, 15 z opłaconym |

### 2. Warstwa prawna: napisana i opublikowana, ale niedomknięta

Obie strony **żyją na produkcji**: `/regulamin-uslugi/` (post 390645, treść 1:1 z
`docs/legal/regulamin-uslugi-2026-07-27.blocks.html`, §2 ust. 3 wymienia PayU S.A.,
§3 z kotwicą `#zwrot` — 3 dni robocze / 7 dni po potrąceniu) i `/depozyt/` (post 390646).

**Cztery kroki T-221 są niewykonane:**

1. Obie strony mają `rank_math_robots = noindex, nofollow` i **zero linków** z motywu i pluginu
   (grep: brak trafień) — wiszą niepodlinkowane.
2. Polityka prywatności (post 198526) **nie wspomina PayU** — 0 wystąpień.
3. `/finansowanie/` nadal opisuje model 30/70, którego nie ma w żadnej umowie — korekta
   wymaga odpowiedzi Ruslana.
4. **Sam wniosek do PayU nie został złożony.** To on odblokowuje T-121.

Stary `/regulamin/` (post 153866) dalej mówi, że usługi są **nieodpłatne** — przy wniosku
o przyjmowanie depozytów 6 150 zł to jest sprzeczność, którą PayU zobaczy.

### 3. Ruslan nie odpowiedział na punkty, od których to zależy

`docs/biznes/2026-07-27-punkty-do-weryfikacji-ruslan.md`: rozstrzygnięte są tylko punkty 1-3
(z tej samej rozmowy 27.07). **Otwarte: pkt 4** (czy terminy zwrotu 3/7 dni obowiązują też
przy pośrednictwie + sygnał o abuzywności §4 ust. 7 wobec konsumenta), **pkt 6** (`/finansowanie/`
30/70), **pkt 8** (depozyt leasingowy — zwrot czy zaliczenie; wydzielony do T-223).
Przeszukanie wszystkich `docs/sesje/` nowszych niż 27.07 — żadnej odpowiedzi.

### 4. Gotowy wzorzec do skopiowania: `digit-kreator`

`~/projekty/digit/src/plugins/digit-kreator/` — integracja **realna i przetestowana w sandboxie**
(kod w repo identyczny z produkcją, w bazie trzy prawdziwe `payu_order_id` z sandboxa).

| Plik | Co bierzemy |
|---|---|
| `includes/integrations/class-payu.php` (137 l.) | OAuth + transient na token `:60-82`, `create_order()` `:84-122` (redirectUri z JSON **albo** z nagłówka `Location`), `verify_notify_signature()` `:124-136` |
| `includes/modules/class-payments.php` (182 l.) | przepływ: `admin_post` → create → redirect → REST `/payu/notify`; filtr `allowed_redirect_hosts` `:21-31` |
| `includes/data/class-payments.php`, `class-activator.php:114-124` | tabela płatności + indeksy |
| `includes/admin/class-admin-settings.php` | creds w `wp_options` (pola write-only) |

**Trzy rzeczy trzeba zrobić INACZEJ niż w digicie:**

1. **Idempotencja.** `handle_notify()` przy powtórzonym `COMPLETED` woła `mark_paid` drugi raz
   **i wysyła mail ponownie**. PayU retry'uje webhooki — u nas to musi być guard, nie życzenie.
2. **Podpis weryfikowany warunkowo** — `if ($sig_header && !verify…)`: brak nagłówka = przejście
   bez weryfikacji. Przy 6 150 zł to dziura, nie niedopatrzenie.
3. **Kwota liczona z opcji w locie** — u nas z `_order_deposit_amount` zamrożonego na zamówieniu.

**Dwie pułapki już rozwiązane w digicie** (raport `docs/strona/raport-testow-2026-05-15.md:89-108`),
warto wziąć od razu: `wp_safe_redirect()` blokuje host bramki (potrzebny filtr
`allowed_redirect_hosts`) i stub trasy REST wygrywa z realnym handlerem (WP trzyma pierwszą
rejestrację). Do tego `open_basedir` blokuje czytanie `~/secrets/` z poziomu WP — digit ma
fallback przez `wp_options`, PrimaAuto obchodzi to przy Indexing API
(`class-asiaauto-indexing.php:29-31`).

Sandbox: `~/secrets/payu/sandbox.env` (`PAYU_ENV`, `PAYU_ENDPOINT`, `PAYU_POS_ID`,
`PAYU_SECOND_KEY_MD5`, `PAYU_CLIENT_ID`, `PAYU_CLIENT_SECRET`), karta testowa i checklist
produkcyjny w memory cross-project `reference_payu_sandbox.md`. Uwaga: `prod-wns.env`
w tym samym katalogu to **inny projekt** (Wydruki na sztuki), nie PrimaAuto.
`~/projekty/damianchen` już nie istnieje — jako źródło odpada.

### 5. Rozjazdy do posprzątania przy okazji

- `docs/QUEUE.md:23` podaje T-221 jako „12–18 h, gotowe do startu"; roadmapa mówi **4–6 h
  pozostałych** (treść rozliczona 27.07).
- `docs/QUEUE.md:30` podaje T-121 jako 32–42 h; roadmapa **24–32 h**.
- `T-121-platnosc-online-depozyt.md:60-62` twierdzi „bloker regulaminowy ZDJĘTY 27.07",
  a nagłówek `:3` i QUEUE trzymają task jako gated — jedno i drugie jest po części prawdą
  (regulamin napisany, wniosek niezłożony), ale zapis wprowadza w błąd.
- Luka funkcjonalna znaleziona przy specu (`T-121:96`): przejście `podpisane → zarezerwowane`
  **nie jest dziś blokowane brakiem depozytu**. Niezależne od PayU, warte osobnego taska.

---

## Pytanie, od którego zacząć rozmowę z Jankiem

Ścieżka jest sekwencyjna i najdłuższy element nie zależy od nas: **wniosek do PayU** (akceptacja
akceptanta trwa i może wrócić z uwagami do regulaminu). Do wysłania wniosku brakuje trzech
drobnych rzeczy (polityka prywatności, zdjęcie noindex + podlinkowanie, korekta `/finansowanie/`)
— z czego jedna czeka na Ruslana.

Sensowna kolejność: **domknąć T-221 do stanu „wniosek gotowy" → wysłać wniosek → w czasie
oczekiwania zrobić spike sandbox na wzorcu z digicie (3-4 h) → dopiero po zgodzie PayU wdrażać.**

Do rozstrzygnięcia przez Janka na starcie wątku:

1. Czy ruszamy T-221 teraz, mimo braku odpowiedzi Ruslana na pkt 4 i 6? (Pkt 6 blokuje tylko
   korektę `/finansowanie/`, pkt 4 dotyka treści regulaminu, który już jest opublikowany.)
2. Czy spike sandbox robimy **przed** zgodą PayU (ryzyko: praca do kosza, jeśli PayU odmówi
   schematu depozytu zwrotnego), czy po.
3. Zwrot depozytu: automatyczny przez refund API (+6-8 h) czy ręczny przelew jak dziś —
   pytanie otwarte od 27.07 (`T-121:90`).
