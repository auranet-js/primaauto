# Kolejka zadań — Prima Auto

> Aktualizacja: 2026-04-20 (fix ghost-crona `asiaauto_daily_cleanup`, bump 0.30.13)

---

## GRUPA 9.5 — performance ✅ DONE (0.30.10)

- [x] Zidentyfikowano: 3× COUNT na postmeta (~600ms) + JOINy w `renderPricePreview` (~400ms)
- [x] Transient `asiaauto_listing_counts` (10min TTL), invalidate po `ajaxBulkRecalc`
- [x] Trash TTL 30d → 7d (2534 listings w koszu się samooczyści szybciej)

---

## ZADANIE 6 — Filtr miast + aktualizacja marek (NOWE)

> Status: **w planowaniu**. Realizacja w nowym wątku po zamknięciu aktualnej sesji.

### Kontekst biznesowy

Prima Auto ma ludzi na miejscu w południowych Chinach (Guangdong, Fujian, Guangxi, Hainan).
Mogą fizycznie sprawdzać i doglądać auta tylko w wybranych miastach. Import ogłoszeń powinien
być ograniczony do tych lokalizacji — żeby klient widział tylko auta, które faktycznie można
zweryfikować na miejscu.

Jednocześnie klient prześle zaktualizowaną listę marek — obecne orphaned termy (Li Auto, NIO,
Volvo) mogą wrócić lub zostać usunięte.

### Lista miast od klienta

| Region | Miasto (PL) | Miasto (ZH) |
|---|---|---|
| Guangdong | Guangzhou | 广州 |
| Guangdong | Shenzhen | 深圳 |
| Guangdong | Foshan | 佛山 |
| Guangdong | Dongguan | 东莞 |
| Fujian | Xiamen | 厦门 |
| Fujian | Fuzhou | 福州 |
| Guangxi | Beihai | 北海 |
| Guangxi | Nanning | 南宁 |
| Hainan | Haikou | 海口 |
| Hainan | Sanya | 三亚 |

> Uwaga: klient podał też regiony (Fujian, Guangxi, Hainan) — interpretowane jako prowincje,
> z których wybrano konkretne miasta. Filtr dotyczy miast, nie prowincji.

### Podzadania — Krok A: weryfikacja dostępności ogłoszeń ✅ DONE (0.30.10)

- [x] Sprawdzone: API `getFilters()` NIE ma filtra `city` — filtr tylko po stronie PHP
- [x] Scan 80 stron dongchedi (1600 ofert), pole `city` = chińskie znaki (广州, 深圳...)
- [x] Zmapowane wszystkie miasta z 4 prowincji z co najmniej 1 ofertą
- [x] Lista finalna: **31 miast** (15 Guangdong + 6 Fujian + 8 Guangxi + 2 Hainan)
- [x] Nadgorliwość Ruslana skorygowana — dodane m.in. 惠州 (19 ofert), 泉州 (9), 南平 (8), 柳州 (5)

### Podzadania — Krok B: filtr w panelu admina ✅ DONE (0.30.10)

- [x] Opcja `city_filter_enabled` (bool) + `city_filter_cities` (array `[{zh,pl,province}]`) w `asiaauto_import_config[source]`
- [x] UI w zakładce "Filtry": toggle + przycisk "Konfiguruj miasta" otwierający modal
- [x] Modal: 4 sekcje prowincji z checkboxami, per-prowincja "wszystkie/żadne", sekcja "Dodaj miasto" (ZH + PL + prowincja), licznik zaznaczonych
- [x] Filtr w `AsiaAuto_Importer::isAllowedByConfig()` — skip oferty której `city` nie ma na liście `zh`

### Podzadania — Krok C: aktualizacja marek ✅ DONE (0.30.12)

- [x] Klient wybrał 66 marek z panelu — zrzuty ekranu z panelu admina
- [x] Orphaned termy (Li Auto, NIO, Volvo, Lynk & Co) — zostają, część wróci z importem
- [x] Brakujące marki dodane jako terminy taksonomii przez sync

### Podzadania — Krok D: re-import (po finalizacji filtrów) ✅ DONE (0.30.12)

- [x] Filtr miast przetestowany i zatwierdzony przez klienta (31 miast)
- [x] Backup bazy przed purge (`mysqldump` listings + postmeta + options do `~/backups/primaauto/pre-purge-20260417-1524.sql`, 445MB)
- [x] Purge: 2905 listings → trash (marki OR miasta poza listą, rezerwacje chronione)
- [x] Bulk-import przez `diag/bulk-import-by-brand.php`: reverse-order pages, MAX_PAGES=50, probe [50,40,30,20,10,5,2,1], parametr API `mark=X` (nie `brand=`)
- [x] Fix-missing-images: 45/45 (15 OK, 30 TRASH ghost-offers)
- [x] Cron bug fix: `add_action('asiaauto_sync_changes')` + `cron_schedules` filter — sync faktycznie działa (change_id 2868663 → 2870663 po pierwszym handler fire)

### Cena — zmiana filtru

- Klient obniżył `price_from` 120000 → 75000 CNY (2026-04-17 ~16:10); v4 bulk-import wczytał świeży config, zmiana obowiązuje dla cron syncu.

### Zależności i uwagi

- Filtr miast ma być domyślnie **wyłączony** (toggle OFF) — obecne ogłoszenia nie znikają od razu
- Toggle OFF = sync działa jak dotychczas (bez filtra geograficznego)
- Toggle ON = sync pobiera tylko ogłoszenia z wybranych miast
- Krok D dopiero po: Krok A (weryfikacja) + Krok B (filtr gotowy) + Krok C (marki potwierdzone)

---

## ZADANIE 8 — n8n pipeline opisów marek i modeli (NOWE)

> Status: **w planowaniu**. Baza: workflow Victorini (`workflows/VICTORINI PRODUCT DESC TECHNISTONE V4.json`, 33 nody, multi-agent LangChain, V4 = dopracowany).

### Kontekst biznesowy

Generacja długich opisów SEO dla **66 marek** (taksonomia `make`) + **~150 modeli** (taksonomia `serie`). Zastąpienie obecnych krótkich tekstów z B2 SEO (10 marek + 75 modeli) bogatszą treścią z inventory snippets (wstawki aktualnych ofert między paragrafami). **Publikacja od razu** (bez manual review), zabezpieczona deterministycznym fact-check + anti-spam guard. Po stabilizacji — adaptacja wzorca do bloga (ZADANIE 16) i bazy wiedzy (ZADANIE 15).

### Podzadania — Krok A: segmenty EU (prerequisite)

- [ ] Klasyfikacja 66 marek + ~150 modeli do segmentów EU: B, C, D, SUV B, SUV C/D, Premium, Van, Pickup
- [ ] Hybryda: algorytm (długość/szerokość/cena z Dongchedi) → propozycja → ręczna akceptacja (Ruslan/Jan)
- [ ] Mapowanie zapisane w term_meta `_asiaauto_eu_segment` + dublet w Google Sheet
- [ ] **Blokuje dalsze kroki** — brand guidelines odwołują się do segmentu

### Podzadania — Krok B: custom REST endpoints w asiaauto-sync

- [ ] `/wp-json/asiaauto/v1/latest-by-make/{slug}?n=8` — 8 najnowszych listings (ID, tytuł, cena PL, obrazek, permalink)
- [ ] `/wp-json/asiaauto/v1/latest-by-serie/{slug}?n=8` — analogicznie dla modelu
- [ ] `/wp-json/asiaauto/v1/facts-for-make/{slug}` — Fact Block: liczba modeli, zakres cen PL, top 3 modele, segment EU, zakres lat
- [ ] `/wp-json/asiaauto/v1/facts-for-serie/{slug}` — moc, przyspieszenie, zasięg EV (min/max/median), liczba ogłoszeń, zakres cen
- [ ] `/wp-json/asiaauto/v1/desc-queue` — lista termów kwalifikujących się do generacji (reguły invalidation)

### Podzadania — Krok C: klon workflow Victorini

- [ ] Kopia → `workflows/primaauto-brand-desc-v1.json`
- [ ] Podmiana endpointów WC Products → `/wp-json/wp/v2/make/{id}` + `/wp-json/wp/v2/serie/{id}` (term `description` jako target, nie post_content)
- [ ] Dual-LLM (GPT-4o + Gemini) → **Claude Sonnet 4.6** (treść) + **Claude Haiku 4.5** (meta description)
- [ ] Prompt caching na system prompt + Brand Guidelines (oszczędność 2–3× przy 216 generacjach)
- [ ] Node „Fetch Inventory Snippet" — wołanie endpointów z Kroku B, sklejanie HTML bloku wstawianego między paragrafami w Final Assembly

### Podzadania — Krok D: fact-check walidator (deterministyczny)

- [ ] JavaScript node „Fact Block Validator" po każdym agencie generującym treść
- [ ] Regex po liczbach w outpucie → porównanie z Fact Block → tolerancja ±1%
- [ ] Named entity check: tekst marki nie zawiera innych marek poza sekcją porównania (whitelist z segment EU)
- [ ] FAIL → retry max 2× z dopiskiem „Użyłeś liczb spoza DANE, popraw"
- [ ] Po 2 nieudanych próbach → `status=failed` + error message

### Podzadania — Krok E: anti-spam lint (Google E-E-A-T)

- [ ] Lista „AI tells" w regex (kopia Victorini + rozszerzenia: „Warto wspomnieć", „Bez wątpienia", „W dzisiejszym dynamicznym świecie", „Niezrównana jakość")
- [ ] Wykrycie → regeneracja (1 retry)
- [ ] Meta `_asiaauto_desc_author = "Zespół Prima Auto"` + `_asiaauto_desc_updated_ui` renderowane widocznie na archive page
- [ ] Uniqueness seed (hash marki) → różnicowanie kolejności sekcji i punktu startu narracji
- [ ] Obowiązkowe wstawki E-E-A-T w prompcie („Z naszego doświadczenia z importem X…", „Klienci Prima Auto najczęściej pytają o…")

### Podzadania — Krok F: status tracking + admin UI

- [ ] Meta fields na termie:
  - `_asiaauto_desc_status` (pending | generating | published | failed | skipped)
  - `_asiaauto_desc_generated_at`, `_asiaauto_desc_prompt_version`
  - `_asiaauto_desc_inventory_hash`, `_asiaauto_desc_facts_hash`
  - `_asiaauto_desc_backup`, `_asiaauto_desc_error`
- [ ] Meta box na edit term screen (make/serie): status badge, ostatnia generacja, przyciski Wygeneruj/Regeneruj/Przywróć backup/Zablokuj/Odblokuj
- [ ] Kolumna „Opis AI" w taxonomy list + bulk actions (zakolejkuj / zablokuj / odblokuj)
- [ ] Dashboard widget „Pipeline opisów" z licznikami per status
- [ ] Globalna opcja `asiaauto_desc_daily_quota` (domyślnie 20) — ochrona przed runaway

### Podzadania — Krok G: Brand Guidelines

- [ ] Google Sheet „PrimaAuto Brand Guidelines" — kolumny: make_slug, tone, USP, target, zakazane_zwroty, segment_eu, referencyjny_eu_model, flagship_models
- [ ] Wypełnienie 66 marek (research + decyzje Ruslan)

### Podzadania — Krok H: iteracja + batch

- [ ] Dry-run 10 sztuk (5 marek + 5 modeli) → publikacja
- [ ] Spot-check Jan/Ruslan w 24h, backup w `_desc_backup` umożliwia rollback
- [ ] Kalibracja promptów na bazie obserwacji
- [ ] Pełny batch marek (~1h) + batch modeli (~2h)
- [ ] Cron `asiaauto_desc_pipeline_hourly` — quota 20/dzień, invalidation: prompt_version bump, inventory_hash change (7d cooldown), facts_hash change, segment_eu change, kwartalny pełny rerun

### Zależności i uwagi

- **Prerequisite Krok A:** bez segmentacji EU brand guidelines są kalekie
- **Koszt LLM:** ~$5–15 pełny batch (Sonnet 4.6 + prompt caching), reruny tańsze dzięki cache
- **Safety valve:** `_desc_backup` pozwala rollback każdego termu
- **Replacement B2 SEO:** obecne krótkie teksty zostają jako lead paragraph (pierwsze 2–3 zdania), długi AI-content jako rozwinięcie
- **Synergia z ZADANIEM 10:** GSC invalidation trigger — spadek pozycji >20%/tydz → flag `_desc_status=pending`

---

## ZADANIE 10 — API integracje: GSC + GA4 + Google Ads + Meta (NOWE)

> Status: **w planowaniu**. Setup OAuth i tokenów na **osobnym wątku** (Jan ma dostęp do Google Cloud Console + Meta Business Manager).

### Kontekst biznesowy

Podłączenie 4 zewnętrznych API: **GSC** (monitoring organic), **GA4** (attribution + funnel), **Google Ads** (performance + conversion upload), **Meta Marketing** (FB/IG kampanie). Cel read-first: mierzenie → input do iteracji (ZADANIE 8 invalidation trigger, SEO roadmap ZADANIE 18). Write operations na dalszych etapach (Q2+ po 3 miesiącach iteracji).

### Priorytet TERAZ — aplikacje o tokeny (timer 2–4 tyg biegnie równolegle)

- [ ] **Google Ads Developer Token** — aplikacja w Google Cloud Console (1–4 tyg akceptacji)
- [ ] **Meta App Review** (Business Manager → Advanced Access) — developers.facebook.com (1–2 tyg akceptacji)

### Podzadania — Krok A: GSC (najszybszy ROI)

- [ ] OAuth 2.0 credentials w GCP, scope `webmasters.readonly`
- [ ] Tokeny w `~/.bash_profile`: `GSC_CLIENT_ID`, `GSC_CLIENT_SECRET`, `GSC_REFRESH_TOKEN`
- [ ] n8n credentials + workflow „GSC weekly report" — top 20 queries/pages, CTR, pozycja, Δ tydzień do tygodnia
- [ ] Output → Google Sheet + alert Slack/mail

### Podzadania — Krok B: GA4 readonly

- [ ] Service Account w GCP z dostępem do GA4 property, scope `analytics.readonly`
- [ ] Token w `~/.bash_profile`: `GA4_SERVICE_ACCOUNT_JSON` (ścieżka), `GA4_PROPERTY_ID`
- [ ] n8n workflow „GA4 weekly KPI" — sesje per źródło, konwersje, funnel rezerwacje→umowa

### Podzadania — Krok C: integracja z ZADANIEM 8

- [ ] Job „GSC → desc-queue" (n8n, raz/tydz) — detekcja stron ze spadkiem pozycji >20% → flag `_asiaauto_desc_status=pending` → regeneracja w następnym cyklu pipeline
- [ ] Detekcja nowych queries do top 20 → wpis do brand guidelines (nowy keyword do uwzględnienia w prompcie)

### Podzadania — Krok D: Google Ads API (po tokenie)

- [ ] OAuth + Developer Token + Manager Account (MCC) setup
- [ ] Tokeny: `GADS_DEVELOPER_TOKEN`, `GADS_REFRESH_TOKEN`, `GADS_MCC_ID`, `GADS_CUSTOMER_ID`
- [ ] Raporty readonly: kampanie, keywords, CPL, CPC, CTR per kampania
- [ ] **Offline conversion upload:** rezerwacja auta → webhook → Google Ads conversion (label `reservation`) — bidder optymalizuje pod realne rezerwacje, nie clicks

### Podzadania — Krok E: Meta Marketing API (po App Review)

- [ ] OAuth + Advanced Access permissions
- [ ] Token w `~/.bash_profile`: `META_ACCESS_TOKEN`, `META_AD_ACCOUNT_ID`
- [ ] Raporty readonly: kampanie, audiences, creative performance
- [ ] Custom audience „odwiedzili parking (ZADANIE 12) ale nie zalogowani" → remarketing

### Podzadania — Krok F: skonsolidowany dashboard

- [ ] Workflow n8n łączący GSC + GA4 + Ads + Meta
- [ ] Export do Google Sheet / Looker Studio
- [ ] KPI: CPL per source, ROAS, funnel conversion rate, organic traffic growth MoM

### Zależności i uwagi

- **Prerequisite:** osobny wątek na setup OAuth + tokeny (Jan ma Google Cloud Console)
- **Tokeny:** wszystkie w `~/.bash_profile` na Elarze, NIC w repo (wzorzec identyczny jak MCP_TOKEN)
- **Consent Mode v2** — wymagany w EU od 2024; Prima Auto musi mieć poprawnie skonfigurowany cookie banner, inaczej dane GA4 niepełne
- **Rate limits:** GSC 50k/dzień OK, GA4 1250 tokens/h/property OK, Ads 15k ops/dzień (basic access), Meta 200 calls/h
- **NIE robimy na starcie:** automated bidding, auto-kreacje, bulk-edycja kampanii — dopiero po 3 mies stabilnego readonly trackingu
- **Status Prima Auto aktywności kampanii:** do potwierdzenia — GA4 już działa w GTM? Ads/Meta aktywne z budżetem czy planowane? (zmienia priorytety)

---

## Backlog (niski priorytet)

- [ ] SKILL/CLAUDE.md: zasada „MCP tylko gdy lokalnie nie da się" — MCP http round-trip marnuje tokeny, jeśli uruchamiamy Claude Code na Elarze mającej bezpośredni dostęp do plików i `wp` CLI. Dotyczy: `read_file`, `list_dir`, `query_db` (zastąpić `wp db query`), `options` (zastąpić `wp option get`), `stats` (własny `wp eval`). MCP sens zachowuje tylko dla zewnętrznych projektów bez local shell (Claude.ai web).
- [ ] Krok 4 manual editor — metabox extra_prep (18 zakładek)
- [ ] Email HTML templates (maile są plain text)
- [ ] Homepage + Contact CSS → pliki zewnętrzne (z inline)
- [ ] Archive/taxonomy pages dla marek (B5 — duże zadanie, osobny projekt)
- [ ] Orphaned terms (Li Auto, Volvo, NIO, Lynk &amp; Co — count=0, czekają na nową listę marek od klienta)

---

## Zrealizowane (archiwum)

- [x] Pending 0: bump wersji 0.29.0-wip → 0.30.7 (2026-04-16)
- [x] Pending 1: pipeline USD-centric — `calculateFromCifUsd()`, `BREAKDOWN_VERSION=2` (2026-04-16)
- [x] ZADANIE 2: załączniki PDF do umowy (renderAttachment1/2), token bezpieczeństwa, nr umowy w tytule przelewu (0.30.8, 2026-04-17)
- [x] ZADANIE 3: maile statusów, etykiety "depozyt zabezpieczający" (2026-04-16)
- [x] ZADANIE 4: smoke test E2E — flow zamówień, PDF, maile, statusy (2026-04-17)
- [x] ZADANIE 5: rework workflow zamówień — model agencyjny, nowe statusy, wizard, panel admina (0.30.7, 2026-04-16)
- [x] B2 SEO: meta/OG/title dla single i inventory, Schema.org, term meta opisów, 10 marek + 75 modeli, llms.txt (0.30.9, 2026-04-17)
- [x] Panel klienta `/klient/` — shortcode, logout, auto-redirect (2026-04-16)
- [x] Fix ghost-crona `asiaauto_daily_cleanup` — handler w bootstrapie + jednorazowy cleanup (trash 5470 → 3559, delete 2077, drafts→trash 166) (0.30.13, 2026-04-20)
