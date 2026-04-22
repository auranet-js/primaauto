# Kolejka zadań — Prima Auto

> Aktualizacja: 2026-04-22 (benchmark west-motors.pl + plan blokowany akceptacją mapowania przez Ruslana)

---

## ZADANIE 12 — Po akceptacji mapowania przez Ruslana: rollout SEO + Google Ads v2 (NOWE, 2026-04-22)

> Status: **WAIT** — czekamy na STATUS w `tmp/brand_model_mapping.csv` od Ruslana. Bez tego wszystkie kroki poniżej są zablokowane.

### Blokada i trigger
- Pre-requisite: Ruslan potwierdza mapowanie marek/modeli v5 (scalenia BYD/Geely, Exeed zostaje, dual-name Omoda 9, fallbacki dla 2–5 listings)
- Po "GO" od Ruslana ruszamy Krok 1 — reszta w kolejności

### Krok 1 — Migracja mapowania (blocker dla wszystkiego)
- [ ] Backup: `mysqldump` tabel `wp7j_posts`, `wp7j_postmeta`, `wp7j_terms`, `wp7j_term_taxonomy`, `wp7j_term_relationships` → `~/backups/primaauto/YYYY-MM-DD-pre-mapping-v5/`
- [ ] Skrypt WP-CLI: rename termów `make` i `serie` wg zatwierdzonego CSV (scalenia Fangchengbao/Yangwang→BYD, Galaxy→Geely, + ewentualne poprawki post-review Ruslana)
- [ ] Aktualizacja importera (`class-asiaauto-importer.php`): mapping CN→EU na wejściu, żeby nowe sync-e używały nowych slugów (inaczej każdy cron przywraca stare)
- [ ] 301 redirects stare→nowe slugi (listingi + huby) — regex w `.htaccess` albo plugin Redirection
- [ ] Smoke test: Dongchedi sync → brak nowych starych termów; archive page marki nadal renderuje

### Krok 2 — SEO huby marek i modeli (po Kroku 1)
**Rozdzielone: (a) kod w WP — (b) content przez n8n.**
- [ ] **(a) Widoki** — dokończenie ZADANIA 11 (backend 0.31.0 już ma rewrite rules, term_link, shortcody `[asiaauto_hub_wiki|_faq|_listings]`, term meta `wiki_body` + `faq_json` — brakują Elementor templates dla `/samochody/`, `/samochody/<mark>/`, `/samochody/<mark>/<serie>/`)
- [ ] **(b) Content pipeline n8n** — ZADANIE 8: workflow generujący `wiki_body` i `faq_json` per term (marki najpierw, modele w drugim przebiegu). Output: insert/update do `wp_termmeta` przez REST lub bezpośrednio n8n→DB.
- [ ] Uwaga: huby to NIE "n8n" same w sobie — n8n tylko produkuje content. Routing, taksonomie, szablony to czysto WP/plugin.
- [ ] Rozważyć v6 mapowania (hybryda west-motors: parent-brand w URL + sub-brand w `serie`) — decyzja po tym jak Ruslan zaakceptuje v5, nie mieszać tematów
- [ ] Vehicle Schema + BreadcrumbList + OfferShippingDetails na karcie `single-listings.php` (gap vs west-motors)
- [ ] Opcjonalnie: `llms.txt` / `llms-full.txt` w root domeny (AEO priorytet 2025 wg globalnego CLAUDE.md)

### Krok 3 — Google Ads v2 (równolegle z Krokiem 2, po podpięciu API)
- [ ] User podpina Google Ads API (developer_token już przyznany — user potwierdził 2026-04-22, konfiguracja jutro)
- [ ] n8n workflow: eksport feedu produktowego (listings → Google Merchant Center? albo dynamic Search Ads?) — decyzja architektoniczna do zrobienia
- [ ] Regeneracja keywordów i reklam pod NOWE slugi marek/modeli (po Kroku 1) — bieżący import w `tmp/Edytor-Google Ads+2026-04-22.csv` jest w starych slugach
- [ ] Negatywne keyword lists już są (`tmp/negative_keywords.csv`) — zaimportować do konta
- [ ] Uwaga: nie regenerujemy CSV-ki póki user ręcznie ją poprawia — czekamy na jego eksport (patrz memory `project_google_ads_campaigns.md`)

### Zależności i kolejność
```
Ruslan OK → Krok 1 (migracja DB+importer) → Krok 2a (templates) ─┐
                                          → Krok 2b (n8n content)┤→ uruchomienie huby
                                          → Krok 3 (Ads v2)      ┘ równolegle z 2
```

### Ryzyka
- **Bez Kroku 1 Krok 3 jest bez sensu** — reklamy prowadzące na stare slugi po migracji zgubią ruch
- **Krok 2a bez Kroku 1** — huby renderują stare termy, po migracji trzeba przerobić drugi raz
- **Google Ads API developer_token** — jeśli konto jest test, limity są niskie; do zweryfikowania przy podpinaniu

---

## ZADANIE 11 — Strony frontowe marek + widoki hubów (NOWE)

> Status: **następne**. Backend gotowy w 0.31.0 (rewrite rules, term_link filter, shortcody `[asiaauto_hub_wiki]`, `[asiaauto_hub_faq]`, `[asiaauto_hub_listings]`, term meta `wiki_body` + `faq_json`). Brakuje widoków.

### Krok A: landing `/samochody/` — lista wszystkich marek
- [ ] Grid z logo marek + licznikiem listingów per make
- [ ] Link do `/samochody/<mark-slug>/` (archive make)
- [ ] Sortowanie: najpopularniejsze (największa liczba listingów) na górze
- [ ] Filtry w bocznej kolumnie (paliwo, cena, stan) — reuse z `[asiaauto_inventory]`

### Krok B: archive marki `/samochody/<mark>/`
- [ ] Elementor Theme Builder template dla taxonomy `make`
- [ ] `[asiaauto_hub_wiki]` na górze (intro + wiki_body z n8n)
- [ ] Lista modeli tej marki (grid tile z licznikiem per model)
- [ ] `[asiaauto_hub_listings]` — lista ogłoszeń filtrowana po make
- [ ] `[asiaauto_hub_faq]` — FAQ z term_meta

### Krok C: archive modelu `/samochody/<mark>/<serie>/`
- [ ] Template reuse albo osobny — podobny layout do marki
- [ ] Listings filtered by make + serie (tax_query AND już działa w `filterHubQuery`)
- [ ] FAQ bardziej szczegółowe (koszt importu tego modelu, zasięg, homologacja)

### Zależności
- Backend 0.31.0 gotowy — tylko widoki + content
- Content wiki/FAQ wypełni ZADANIE 8 (n8n pipeline, marki najpierw, modele po spot-check)

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

### Kolejność wdrożenia (2026-04-21)

1. **Faza 1 — marki** (66 termów `make`): Krok A → B → C → D → E → F → G → H dry-run 5 marek → spot-check → batch 66
2. **Faza 2 — modele** (~150 termów `serie`): po stabilizacji marek. Adaptacja workflow (mniej paragrafów, węższy fact block), batch ~150

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

- [ ] **Plan A — fix race condition w sync** (reaktywne, trigger: gdy znów pojawią się duplikaty). Zastąpić transient lock w `class-asiaauto-sync.php:52-58` przez MySQL `GET_LOCK('asiaauto_sync_dongchedi', 0)`. Bez TTL, auto-release na disconnect. ADR: `docs/decyzje/2026-04-22-dedup-i-optymalizacja-bazy.md`.
- [ ] **Plan D — prewencja bloatu bazy**:
  - [ ] `class-asiaauto-media.php`: ustawiać `post_parent = $listing_id` przy `wp_insert_attachment` (bez tego każdy trashowany listing zostawia 10-15 sierot)
  - [ ] `asiaauto_daily_cleanup` cron: kasować na stałe trashed listings starsze niż 30 dni (teraz tylko drafts→trash, nigdy nie kasuje)
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
- [x] Dedup 36 par listingów + optymalizacja DB 640 MB → 141 MB (−78%): revisions, trashed listings, orphan attachments; `WP_POST_REVISIONS=3` (2026-04-22) — ADR: `docs/decyzje/2026-04-22-dedup-i-optymalizacja-bazy.md`
