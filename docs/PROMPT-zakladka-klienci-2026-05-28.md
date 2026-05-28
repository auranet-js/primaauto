# PROMPT do nowego wątku — zakładka „Klienci" w panelu admina

**Data:** 2026-05-28
**Projekt:** `~/projekty/primaauto/` (Prima-Auto)
**Wersja startowa:** 0.32.55

---

## Kontekst (przeczytaj na starcie)

Przeczytaj:
1. `CLAUDE.md` w korzeniu repo
2. `docs/VERSIONS.md` wpisy **v0.32.54** i **v0.32.55** — bramka kompletności + UI billing
3. Memory:
   - `project_session_2026_05_28_admin_billing_form.md` — sesja w której dorobiliśmy UI billing w karcie zamówienia
   - `feedback_no_unverified_ui_claims.md` — krytyczny feedback: backend ≠ UI, grep przed obietnicą klientowi
   - `project_workflow_rework.md` — model agencyjny, flow statusów (orientacja w typach zamówień)

**Stan klientów dziś (28.05.2026):**
- Rola WP: `asiaauto_customer` (slug zostaje, patrz CLAUDE.md sekcja 1)
- W systemie ~12+ klientów którzy złożyli zamówienie (post_type `asiaauto_order`, `_order_customer_id` > 0)
- Dane przechowywane w `user_meta` jako `billing_first_name`, `billing_last_name`, `billing_email`, `billing_phone`, `billing_pesel`, `billing_nip`, `billing_id_type`, `billing_id_number`, `billing_address_street`, `billing_address_postcode`, `billing_address_city`, `billing_company_name`
- Funkcja `AsiaAuto_Order::getCustomerData($user_id)` zwraca tablicę 12 pól
- Funkcja `AsiaAuto_Order::isCustomerDataComplete($user_id)` → bool (required + XOR PESEL/NIP)
- Funkcja `AsiaAuto_Order::saveCustomerData($user_id, $params)` — z walidacją
- Formularz edycji billing w panelu zamówienia: nowy `renderCardCustomer` w `class-asiaauto-order-admin.php` (v0.32.55)

**5 historycznych klientów z brakami billing** (zamówienia AA/2026/0006, 0008, 0011, 0012, 0013) — do uzupełnienia per case przez Ruslana w formularzu z karty zamówienia. Nowa zakładka „Klienci" powinna ułatwić mu objęcie tego z lotu ptaka.

---

## Cel zadania

Zbudować **zakładkę „Klienci"** w panelu admina (obok istniejącej „Zamówienia") z widokiem listy wszystkich klientów (rola `asiaauto_customer`) plus widokiem szczegółu pojedynczego klienta.

## Wymagania funkcjonalne (do uzgodnienia z user'em na starcie)

**Lista klientów (`view=list`):**
- Tabela: nazwisko+imię / email / telefon / liczba zamówień / **status kompletności danych** (badge Komplet/Niekompletne) / data ostatniego zamówienia
- Filtry: status kompletności (komplet / niekomplet), tylko klienci z aktywnym zamówieniem, szukajka po nazwisku/email/telefonie
- Sortowanie: po dacie ostatniego zamówienia, po nazwisku
- Paginacja jak w „Zamówieniach"
- Link „Edytuj" → szczegół klienta (`view=detail`)

**Szczegół klienta (`view=detail&customer_id=X`):**
- Dane podstawowe (read-only: ID, login, data rejestracji, rola)
- Sekcja „Dane do umowy" — ten sam formularz co w karcie zamówienia (v0.32.55), ale samodzielny widok per klient (bez kontekstu konkretnego zamówienia). Zapis → `saveCustomerData()`. **Bez auto-regen PDF** w tym widoku (nie wiemy które zamówienie regenerować — albo: regen *wszystkich* zamówień klienta w statusie `umowa_gotowa+`, do decyzji).
- Sekcja „Zamówienia tego klienta" — tabela z linkami do zamówień (numer kontraktu, listing, status, data)

**Pytania do user'a (zadać NA STARCIE wątku):**
1. Czy edycja billing na widoku klienta ma regenerować PDF dla *wszystkich* zamówień klienta w `umowa_gotowa+`? Czy tylko aktywnego/najnowszego? Czy nie regenerować i pokazać przycisk „Regeneruj wybrane zamówienie"?
2. Czy pokazywać klientów którzy nie mają jeszcze zamówienia (zarejestrowani ale niczego nie zamówili)? Czy tylko tych z ≥1 zamówieniem?
3. Czy dodać akcję „Wyślij magic link" (klient dostaje email z linkiem do panelu zamówienia) — przyspieszenie kontaktu gdy Ruslan dzwoni do klienta z brakami?
4. Czy zakładka „Klienci" w menu admin — pod „Listings" czy osobny top-level? Lokalizacja menu w `class-asiaauto-order-admin.php::addMenuPages()`.

## Architektura (sugerowana, do akceptacji)

Nowa klasa `class-asiaauto-customer-admin.php` (wzorowana na `class-asiaauto-order-admin.php`):
- `addMenuPages()` — dodać submenu „Klienci" pod tym samym parent co Zamówienia
- `renderPage()` — switch `view=list|detail`
- `renderCustomerList()` — query po `wp_users` z `meta_query` na role, JOIN do `wp_posts` post_type=`asiaauto_order` dla count zamówień
- `renderCustomerDetail()` — wyciąga `getCustomerData($customer_id)` + zamówienia klienta
- `handleActions()` — case `update_customer_billing_standalone` (bez kontekstu zamówienia)
- Reuse styles z `aa-billing-form` (CSS już istnieje w `renderCardCustomer`)

## Zasady operacyjne dla nowego wątku

1. **NIE pisz nic do klienta o funkcjonalności której nie zweryfikowałeś grep'em.** Patrz `feedback_no_unverified_ui_claims.md`. Backend ≠ UI.
2. **Recon przed mutacją:** najpierw `wp user list --role=asiaauto_customer` i grep po klasach istniejących (`class-asiaauto-order-admin.php` jako szablon stylistyczny + struktura menu).
3. **Slug zostaje `asiaauto-*`** — nie zmieniać nazw klas, slugów, meta keys (CLAUDE.md sekcja 1).
4. **Backup przed edycją** każdego pliku produkcyjnego (`.bak-YYYY-MM-DD-<scope>`).
5. **`php -l` + smoke test** (`wp eval` z ReflectionClass) przed uznaniem za gotowe.
6. **Bump wersji v0.32.56** w `asiaauto-sync.php` header + define + `ASIAAUTO_VERSION`.
7. **Aktualizacja `docs/VERSIONS.md`** + memory + commit + push w jednym podejściu na końcu.
8. **Pytaj user'a o decyzje produktowe** zanim wpiszesz w kod (4 pytania powyżej).

## Definition of Done

- Zakładka „Klienci" widoczna w panelu admin
- Lista wszystkich klientów z badge'em Komplet/Niekompletne
- Filtry + sortowanie + paginacja działają
- Widok szczegółu klienta z formularzem edycji billing
- Zapis billing przez `saveCustomerData()` (reuse istniejącej walidacji)
- Tabela zamówień klienta z linkami do widoku zamówienia
- v0.32.56 wdrożona na produkcję + zacommitowana + push do GitHub
- Memory zaktualizowane (1 wpis project_*, ewentualnie nowy feedback)
- Smoke test przeszedł na min. 3 klientach (komplet, niekomplet, klient bez zamówień jeśli zdecydujemy ich pokazywać)
