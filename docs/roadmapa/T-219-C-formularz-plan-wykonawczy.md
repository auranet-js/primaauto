# T-219 C — formularz leadowy: plan wykonawczy

> Utworzony: 2026-07-31
> Status: **plan gotowy, czeka na zielone światło**
> Poprzedza: T-219 A (landing LIVE), T-219 B (blok na 317 hubach LIVE, theme 1.2.0)
> Pod ten komponent wejdą potem: T-189 (kalkulator raty), T-188 (alerty)

---

## 1. Co się zmieniło względem specu

Spec zakładał budowę **od zera** („w serwisie nie ma dziś żadnego komponentu formularza")
i wyceniał to na 5–7 h. Recon 31.07 pokazał, że to nieprawda w istotnym szczególe:
**publiczny submit od niezalogowanych już w pluginie działa i jest przetestowany w boju.**

`class-asiaauto-order-api.php` (922 linie) wystawia `POST asiaauto/v1/order/start` z:

- `permission_callback => '__return_true'` — publiczny, świadomie
- honeypotem `_hp` (+ log ostrzeżenia z IP przy trafieniu)
- rate limitem per IP na transientach (`isRateLimited()` / `bumpRateLimit()`)
- nonce `wp_rest` przekazywanym z frontu (`wp_create_nonce('wp_rest')` w wizardzie)
- sanityzacją `sanitize_text_field` / `is_email` przy każdym polu

Formularz leadowy **kopiuje ten wzorzec**, zamiast wymyślać własny. To zdejmuje z estymaty
najdroższą i najbardziej ryzykowną część (bezpieczeństwo publicznego endpointu przyjmującego
dane osobowe) i zamienia zadanie z „napisz od zera" na „zaadaptuj sprawdzone".

## 2. Etapy

### Etap 1 — backend, nowy plik `includes/class-asiaauto-lead.php` (2–2,5 h)

Klasa `AsiaAuto_Lead`, addytywnie — **zero zmian w istniejących klasach**
([[feedback_additive_not_fragile_zone]]).

| Element | Szczegóły |
|---|---|
| CPT `asiaauto_lead` | `public => false`, `show_ui => true`, menu pod zamówieniami; capability jak `asiaauto_order` |
| Kolumny w adminie | data, imię, telefon, e-mail, model, źródło (`landing` / `hub:{slug}`), status (nowy / obsłużony) |
| REST `POST asiaauto/v1/lead` | 1:1 wzorzec z `order/start`: `__return_true` + nonce `wp_rest` + honeypot `_hp` + rate limit |
| Walidacja | imię i telefon **wymagane**, e-mail wymagany i `is_email`, zgoda RODO wymagana (odrzucenie bez niej), reszta opcjonalna |
| Zapis | `wp_insert_post` + meta `_lead_name`, `_lead_phone`, `_lead_email`, `_lead_company`, `_lead_nip`, `_lead_model`, `_lead_message`, `_lead_source`, `_lead_consent_at`, `_lead_ip_hash` |
| Powiadomienie | `wp_mail` na `admin_notification_email` z configu (**dziś: `china@primaauto.com.pl`**, przełącznik `admin_email_notifications` = true), `Reply-To` = e-mail leada. Wzorzec: `sendBillingCompletedEmail()` |
| Log | `AsiaAuto_Logger::info` przy zapisie, `warning` przy honeypocie — jak w order-api |

**Świadomie POMIJAMY autoresponder do osoby wypełniającej formularz.** Reguła „nigdy maile
bezpośrednio do klientów" obejmuje SMTP w pluginach WP — jeśli Ruslan ma dostawać
potwierdzenie, decyzja musi być jawna, nie efekt uboczny wdrożenia.

### Etap 2 — frontend, shortcode `[asiaauto_lead_form]` (1,5–2 h)

- Pola: imię, telefon, e-mail, firma/NIP (opcjonalnie), interesujący model
  (prefill z `?model=` w URL), wiadomość, checkbox zgody, ukryty honeypot
- Wygląd: reużycie klas kreatora `/zamow/` — **zero nowego CSS**, jeśli się da; jeśli nie,
  minimum reguł i bump `PRIMAAUTO_THEME_VERSION` (CSS busta się przez tę stałą)
- JS: `fetch` na REST, blokada podwójnego wysłania, komunikaty błędów pod polami,
  stan „dziękujemy" bez przeładowania strony
- Dostępność: `<label>` przy każdym polu, `aria-describedby` dla błędów, focus na pierwszym
  błędnym polu — serwis przeszedł audyt WCAG 2.2 AA 31.07, nie psujemy tego nowym formularzem

### Etap 3 — pomiar (0,5 h)

- `dataLayer.push({event: 'generate_lead', form: 'leasing'})` po sukcesie
- Weryfikacja **bezpośrednio w GA4**, nie „na oko" ([[feedback_verify_ga4_conversions_directly]]) —
  `tmp/ga4_query.py` po testowym zgłoszeniu

### Etap 4 — osadzenie i testy (1 h)

- Shortcode na landingu **zamiast** dzisiejszego CTA do `/kontakt/` (czyli do `mailto:`)
- Test e2e: wysyłka → wpis w adminie → mail do Ruslana → event w GA4
- Test bezpieczeństwa: honeypot wypełniony → odrzucenie, przekroczony rate limit → odrzucenie,
  brak zgody RODO → odrzucenie, `<script>` w polu → sanityzacja
- Test na telefonie (79,6% ruchu to mobile)

**Razem: 5–6 h.**

## 3. Decyzje do potwierdzenia przed startem

| # | Pytanie | Rekomendacja |
|---|---|---|
| 1 | Storage leada | **CPT `asiaauto_lead`** — widoczny w adminie, filtrowalny, reużywalny pod T-189/T-188. Alternatywa (meta na czymś istniejącym) oszczędza godzinę i kosztuje brakiem listy w panelu |
| 2 | Kto dostaje powiadomienie | `china@primaauto.com.pl` z configu — ten sam kanał, co zamówienia. Ruslan nie musi niczego nowego pilnować |
| 3 | Autoresponder do leada | **Nie robimy** bez jawnej zgody (patrz Etap 1) |
| 4 | Formularz na hubach | **Nie** — huby linkują na landing, formularz w jednym miejscu. Inaczej mamy 317 kopii formularza do utrzymania |
| 5 | Prefill modelu z hubu | Link z bloku hubowego mógłby nieść `?model={slug}`, żeby formularz wiedział, skąd przyszedł klient. Koszt ~15 min, daje źródło leada per model w statystykach |

## 4. Ryzyka

| Ryzyko | Mitygacja |
|---|---|
| Nowy kod przyjmuje dane osobowe | Wzorzec z `order/start` (przetestowany), zgoda RODO wymagana, IP tylko jako hash, zero danych w logach |
| Spam | Honeypot + rate limit per IP; bez zewnętrznej captchy na start, dołożymy gdy realnie zacznie się sypać |
| Regresja w kreatorze `/zamow/` | Nowa klasa w nowym pliku, zero edycji `class-asiaauto-order*.php` |
| Formularz psuje audyt a11y z 31.07 | Etykiety, `aria-describedby`, test klawiaturą przed uznaniem za zrobione |
| Lead ginie, gdy `wp_mail` zawiedzie | Zapis do CPT **przed** wysyłką maila — mail jest powiadomieniem, nie nośnikiem danych |
