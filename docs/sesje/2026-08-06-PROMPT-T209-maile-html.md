# T-209 — prompt startowy do nowego wątku (stan na 2026-08-06)

> Wątek **planistyczny**, nie wdrożeniowy. Efektem ma być **plan wdrożeniowy do akceptu Janka**,
> poszerzony o trzy rzeczy, których nie ma w dzisiejszym specu: **audyt istniejących podpisów**,
> **mockupy przed/po** i **plan wdrożenia** (etapy, kolejność, rollback, odbiór).
> Stan niżej zmierzony 06.08 na produkcji i w bazie — nie z pamięci.

---

## Prompt do wklejenia

```
Ustawiasz plan wdrożeniowy T-209 w projekcie primaauto — brandowany szablon HTML
dla maili transakcyjnych do klientów.

START:
1. Przeczytaj docs/sesje/2026-08-06-PROMPT-T209-maile-html.md (ten plik) — sekcja
   „Stan faktyczny" jest zweryfikowana 06.08 na serwerze, nie mierz jej od nowa.
2. Przeczytaj docs/roadmapa/T-209-maile-html-deliverability.md — tam jest pierwotny
   zakres i podejście (jeden wrapper, treść zostaje tekstowa). Nie przepisuj tej decyzji.
3. Dopiero wtedy zaproponuj plan wątku. Nie zaczynaj kodować.

EFEKT WĄTKU — trzy artefakty, nie kod:

A. AUDYT ISTNIEJĄCYCH PODPISÓW I STOPEK
   Zinwentaryzuj każdy podpis / stopkę / blok kontaktowy, jaki dziś wychodzi do klienta:
   wszystkie szablony, nadawca (From / From Name), Reply-To, warianty brzmienia podpisu,
   dane firmowe (dziś ich w stopce NIE MA — sprawdź, czy powinny być), linki do regulaminu
   i polityki prywatności, sposób podawania kontaktu (telefon / WhatsApp / adres).
   Wynik = tabela: szablon | podpis dosłownie | odbiorca | rozjazd. Na jej podstawie
   zaproponuj JEDEN kanoniczny blok podpisu + stopki do wrappera.

B. MOCKUPY PRZED I PO
   Jeden plik HTML z ekspozycją porównawczą, wystawiony na auratest — wzorzec:
   tmp/t121-makiety-payu.html. Minimum 4 reprezentatywne maile (powitanie, przyjęcie
   zgłoszenia, umowa gotowa do podpisu z magic linkiem, zmiana statusu), każdy w wersji
   PRZED (dokładnie to, co idzie dziś) i PO. Do tego widok mobilny — 79,6% ruchu to mobile.
   Mockup ma pokazywać realny render, nie opis słowny.

C. PLAN WDROŻENIA
   Etapy z kolejnością i zależnościami, punkt wpięcia w kodzie, sposób wycofania zmiany,
   lista testów (automatyczne + w prawdziwych skrzynkach), kryteria odbioru, podział
   Janek / AI, urealnione godziny wobec 14–16 h ze specu (dochodzą A i B), styk z T-121,
   T-204 i T-188.

ZASADY:
- Source of truth = serwer (~/domains/primaauto.com.pl/public_html/...). Repo jest kontekstowe.
- Treści merytorycznej maili NIE ruszamy — ani zdań, ani placeholderów {order_id} itd.
  Dokładamy wyłącznie warstwę prezentacji. To ustalenie ze specu, nie otwieraj go.
- Magic link to jedyna droga klienta do zamówienia. Każda propozycja musi pokazać,
  że link przeżyje HTML i klienta pocztowego (Outlook w szczególności).
- Dostarczalność (SPF/DKIM/DMARC) jest POZA zakresem — alarm wycofany 14.07 (commit fee89ca),
  diagnoza była błędna. Nie wracaj do niej z własnej inicjatywy.
- Żadnej wysyłki do prawdziwych klientów. Testowe wysyłki wyłącznie na skrzynki Janka
  i tylko po jego zgodzie. Raporty i podsumowania dla Janka idą przez ~/bin/send-to-jan,
  nigdy przez wp_mail() projektu.
- Nie dotykasz statusów zamówienia, generatora umów ani kreatora.
- Nic nie wdrażasz na produkcję w tym wątku. Plan → Janek → dopiero potem robota.
```

---

## Stan faktyczny (zmierzony 2026-08-06)

### 1. Maile są w 100% tekstowe — i to się nie zmieniło od pisania specu

| Element | Stan zmierzony |
|---|---|
| Plik | `includes/class-asiaauto-order-content.php`, **919 linii** |
| HTML w szablonach | **0 trafień** na `<html`, `<table`, `wp_mail_content_type` |
| Transport | `wp_mail()` → PHP `mail()`, **brak wtyczki SMTP** |
| Nadawca | filtr globalny w `asiaauto-sync.php:110-111` — From Name „Zamówienia Prima-Auto", From `china@primaauto.com.pl` |
| Reply-To | ustawiany **tylko na mailach do admina** (`class-asiaauto-order.php:1781, 1807, 1855, 1882`) — klient nie dostaje żadnego |
| Punkty wysyłki | **9 wywołań `wp_mail()`** w 3 plikach: `class-asiaauto-order.php` (×7), `class-asiaauto-order-admin.php` (×2, w tym `:768` z załącznikiem PDF) |
| Logo do wrappera | jest gotowe: attachment **259005**, `uploads/2026/03/primaauto-logo-szerokie.png` (`custom_logo` motywu) |

### 2. Edytor szablonów w adminie JUŻ ISTNIEJE — spec o tym nie mówi

Szablony są edytowalne przez UI: **Narzędzia → Prima-Auto Zamówienia → tab „Treści i szablony"**
(`class-asiaauto-order-admin.php:183` akcja `save_email_templates`, formularz `:3017`), zapis do
opcji `asiaauto_order_email_templates`.

**Opcja nie istnieje w bazie** (`wp option get` → błąd) — czyli **nikt nigdy nic nie nadpisał**,
a `defaultEmailTemplates()` z kodu to dosłownie to, co dostaje klient. Dwie konsekwencje dla planu:

- „podgląd szablonów w adminie" z definicji zrobionego to **dołożenie renderu do istniejącego taba**, nie budowa nowego ekranu — mniej roboty, niż zakłada spec;
- wrapper musi działać także dla treści, którą Ruslan kiedyś nadpisze przez UI (wrapper na zewnątrz, nie wklejony w treść szablonu).

### 3. Liczba szablonów: spec mówi 17, kod generuje więcej

`getTemplateKeyLabels()` (`:898`) składa listę z **7 kluczy stałych** (`welcome`,
`order_started_admin`, `order_started_customer`, `status_changed_admin`, `contract_regenerated`,
`customer_billing_completed`, `annex_vin_sent` — ten ostatni doszedł z T-220) **plus jeden per
status** z `AsiaAuto_Order::STATUSES`, których jest **13**. To daje **20 pozycji** w UI, nie 17.
Ile z nich ma faktyczną treść w `defaultEmailTemplates()`, a ile leci fallbackiem — **do policzenia
w wątku**, bo to wprost skaluje robotę i mockupy.

### 4. Podpisy są rozjechane — i to jest gotowy materiał na audyt (A)

W samych domyślnych szablonach:

| Wariant | Ile razy |
|---|---|
| `Zespół Prima Auto` (bez myślnika) | **12** |
| `Zespół Prima-Auto` (z myślnikiem) | **3** |

Subjecty są konsekwentne — wszędzie `Prima-Auto`. Rozjeżdżają się **wyłącznie podpisy w treści**.
Decyzja brandowa jest zamknięta (memory `feedback_brand_prima_auto_spacing_settled`: myślnik =
separator), więc **12 z 15 podpisów jest dziś niezgodnych z własnym brandem**.

Poza tym w stopce **nie ma nic**: żadnych danych rejestrowych, żadnego telefonu, żadnego linku do
regulaminu ani polityki prywatności. Jedyny „branding" to sam podpis. Audyt A ma rozstrzygnąć,
co musi się tam znaleźć — i to jest pytanie częściowo prawne, nie tylko wizualne.

### 5. Hasło klienta nadal leci otwartym tekstem

Potwierdzone: `class-asiaauto-order.php:1370` generuje hasło, `:1399` przekazuje je do
`sendWelcomeEmail()`, `:1404-1421` wstawia w treść i wysyła. To samo zadanie siedzi w **T-204**
(panel klienta) — w planie trzeba jawnie rozstrzygnąć, który task to bierze, żeby nie zrobić
tego dwa razy albo zero razy.

### 6. Dostarczalność — zamknięte, nie otwierać

Commit `fee89ca` (14.07) wycofał alarm o DKIM/DMARC: diagnoza opierała się na nagłówkach maila
dostarczonego **lokalnie** (Exim „with local"), który z definicji nie ma podpisu DKIM. To nie
mówiło nic o wysyłce zewnętrznej. Poczta i formularze działają — 45 zamówień/30 dni.
Zakres wrócił z 16–20 h na 14–16 h. Zostaje wyłącznie **test regresyjny**: po przejściu na HTML
maile mają docierać tak jak dotąd.

---

## Co dokładnie znaczy każde z trzech rozszerzeń

**A. Audyt podpisów** — nie jest kosmetyką. Trzy rzeczy do rozstrzygnięcia przy okazji:
czy w stopce mają być dane rejestrowe (spółka, NIP, adres), czy klient ma dostawać `Reply-To`
na adres obsługi (dziś nie dostaje żadnego, więc odpowiedź idzie na `china@`), i czy pod maile
transakcyjne podpina się linki do `/regulamin-uslugi/` i polityki prywatności — obie strony
mają dziś `noindex` i zero linków (ustalenie z wątku PayU, `2026-08-06-PROMPT-payu-rozpoznanie.md`).

**B. Mockupy przed/po** — kryterium jest praktyczne: Ruslan ma zobaczyć różnicę bez zakładania
testowego zamówienia, a Janek ma zaakceptować wygląd, zanim powstanie kod. Stąd render, nie opis.
Wystawiane na `https://auratest.pl/fe4f58fec53ctmp/`.

**C. Plan wdrożenia** — musi odpowiedzieć na pytanie kolejności wobec innych tasków:
**T-121 (PayU)** dołoży maile o płatności depozytu — taniej mieć wrapper przed nimi niż po;
**T-204** przejmuje sprawę hasła; **T-188** (alerty/newsletter) będzie chciał tego samego wrappera
dla maili marketingowych, które mają inne wymogi prawne (opt-out, zgody) — plan ma powiedzieć,
czy wrapper od razu to przewiduje, czy świadomie nie.

---

## Do rozstrzygnięcia przez Janka na starcie wątku

1. **Zakres A:** czy audyt podpisów kończy się rekomendacją, czy od razu ujednoliceniem
   12 rozjechanych podpisów (to zmiana treści — dziś spec tego zabrania, więc wymaga Twojej zgody).
2. **Stopka prawna:** dane rejestrowe Prima-Auto w każdym mailu — tak czy nie. Jeśli tak,
   potrzebne dane od Ruslana (spółka, NIP, adres) — to jest zewnętrzna zależność.
3. **Kolejność wobec T-121:** wrapper przed PayU (maile o płatności od razu ładne) czy po
   (PayU szybciej, maile o płatności trafiają do przeróbki w drugim podejściu).
