---
name: asiaauto
description: >
  Skill for building and maintaining AsiaAuto — a Chinese car listings platform 
  (Dongchedi + Che168) on WordPress with self-contained CPT + Elementor Pro. 
  Use this skill whenever the user mentions: AsiaAuto, 
  asiaauto.pl, car listings import, auto-api.com integration, Dongchedi, Che168, 
  Chinese car data sync, vehicle listing synchronization, car import cron, 
  Elementor listing templates, or any task related 
  to the AsiaAuto automotive listing project. Also trigger when working on: 
  WP-CLI import scripts, auto-api PHP SDK, vehicle taxonomy mapping, 
  Chinese-to-Polish translation of car data, or listing meta fields. This skill 
  covers the full stack: API integration, data sync, WordPress backend, 
  frontend display (Elementor Pro), and translation pipeline.
---

# AsiaAuto — Chinese Car Listings Platform

> ## 🚀 PENDING UPDATE — v0.30.6 czeka na wgranie
>
> **Status: gotowe w sandboxie, czeka na FTP/SSH upload.** Trzy pliki, wszystkie po `php -l` clean.
> Pliki w `/mnt/user-data/outputs/asiaauto-v0.30.6/`. Cel: dwa fixy odporności override CIF USD na sync
> + redesign kolumny CIF w widget preview + profiler diagnostyczny do tropienia wolnego ładowania panelu.
>
> ### Pliki do nadpisania (`wp-content/plugins/asiaauto-sync/includes/`)
>
> | Plik | Patch | Co robi |
> |------|-------|---------|
> | `class-asiaauto-admin.php`     | A | Kolumna „CIF PLN" → „CIF" dwuwierszowa (USD bold na górze, PLN szare/małe pod spodem) — spójność z metaboksem ręcznych ogłoszeń. Profiler `?aa_profile=1` (HTML comment + visible bar, 14 punktów pomiaru). |
> | `class-asiaauto-importer.php`  | B | `updateListing()` zawsze odświeża `_asiaauto_original_price` z API PRZED `applyToListing()` — meta CNY przestaje dryfować od ceny aukcyjnej, gdy aktywny jest override CIF USD. |
> | `class-asiaauto-price.php`     | C | Audit log overlap CIF USD + CNY: `warning(...)` → `info(...)` (intentional override). Koniec spamu w syncu co 15 min dla każdego listingu z ręczną ceną. |
>
> ### Protokół deployu
>
> 1. Backup obecnych 3 plików z `wp-content/plugins/asiaauto-sync/includes/` (przed nadpisaniem)
> 2. Wgraj 3 nowe pliki przez FTP/SSH (ścieżki bez zmian)
> 3. Otwórz `https://asiaauto.pl/wp-admin/tools.php?page=asiaauto-import-config` (Ctrl+F5)
> 4. **Test #1 — kolumna CIF.** Scroll do tabeli „Podgląd kalkulacji". Kolumna „CIF" powinna być dwuwierszowa: USD na górze pogrubione, PLN pod spodem szare i mniejsze. Wcześniej była tylko „CIF PLN" jednowierszowo.
> 5. **Test #2 — profiler.** Dodaj `&aa_profile=1` do URL → na dole strony pojawi się żółty banner „⏱ Profiler aktywny" z sumarycznym czasem. Klik „Pokaż tabelę inline" → dump per sekcja. Sekcje >100ms na czerwono, >30ms na pomarańczowo. **Output stąd posłuży do zaplanowania optymalizacji bottlenecka** (najpewniej 3× COUNT na postmeta — kandydat na transient cache).
> 6. **Test #3 — sync na listingu z override.** Wybierz listing który ma wpisaną wartość w metaboksie „CIF (USD) — OVERRIDE" i którego cena CNY zmieniła się ostatnio na Dongchedi. Trigger sync (cron co 15 min lub ręcznie `wp asiaauto sync`). Po sync sprawdź:
>    - `_asiaauto_original_price` powinno mieć **nową** wartość CNY (wcześniej zamrożone). MCP: `query_db SELECT meta_value FROM {prefix}postmeta WHERE post_id=X AND meta_key='_asiaauto_original_price'`
>    - `price` (PLN klienta) i `_asiaauto_price_breakdown` — **bez zmian** (override wygrywa, breakdown trzyma się CIF USD)
>    - Log sync (`logs sync`): wpis `INFO ... CIF wins (intentional override)` zamiast `WARNING ... CIF wins`
>
> ### Po pomyślnych testach
>
> - Podbij `ASIAAUTO_VERSION` w `asiaauto-sync.php`: `0.29.0-wip` → `0.30.6`
> - Zaktualizuj wpis pluginu w sekcji „Active Plugins" tego skilla (kolumna Version)
> - **Usuń ten cały blok PENDING UPDATE** (od `> ## 🚀 PENDING UPDATE` do tej linii) — wpisy historyczne idą do sekcji `### Krok 9 — Override resilience + widget redesign + profiler v0.30.6` na końcu pliku
>
> ### Co dalej (kolejka GRUPA 9)
>
> Po deployu v0.30.6 i analizie outputu profilera → optymalizacja bottlenecka (transient cache na 3× COUNT).
> Następne zadania kolejki: ZADANIE 2 (Załączniki do umowy PDF — Załącznik nr 2 z breakdownem v2 etapów,
> Załącznik nr 1 z snapshotem aukcji), ZADANIE 3 (Krok 5 umowy — maile statusów + display labels pod
> narrację depozytu zabezpieczającego zamiast zaliczki), ZADANIE 4 (smoke test E2E na zamówieniu #222262).

## Project Overview

AsiaAuto is a car listings platform aggregating vehicles from the Chinese market 
(Dongchedi + Che168) via auto-api.com, displayed on a WordPress site.
Frontend design reference: mobile.de (clean, functional UI).
Production: `asiaauto.pl`.

## Architecture Decision: Motors → Self-contained (2026-03-21)

Originally built on Motors theme by StylemixThemes. Migrated away due to:
- Chaotic plugin dependencies (8+ plugins for basic functionality)
- Poor Elementor integration (shortcodes instead of native widgets)
- Opaque settings system (wpcfto framework)
- Inflexible frontend templates

**New stack:** Self-contained CPT in `asiaauto-sync` plugin + Hello Elementor + 
Elementor Pro. Zero dependency on Motors.

## WordPress Environment (verified 2026-03-22)

| Setting              | Value                                                          |
|----------------------|----------------------------------------------------------------|
| WP root              | `/home/host476470/domains/asiaauto.pl/public_html`             |
| WordPress            | 6.9.4                                                         |
| PHP                  | 8.3 (GD + Imagick)                                            |
| Database             | MariaDB 10.11                                                  |
| DB prefix            | `wp7j_`                                                        |
| Server               | Shared hosting (elara, host476470)                             |
| Theme parent         | `hello-elementor` (Hello Elementor v3.4.7)                     |
| Theme child          | `asiaauto` (AsiaAuto by Auranet, v1.0.0)                      |
| Child theme dir      | `wp-content/themes/asiaauto/`                                  |
| Frontend builder     | Elementor Pro 3.35.1                                           |
| Listing CPT          | `listings` — registered by `asiaauto-sync` plugin              |
| Order CPT            | `asiaauto_order` — registered by `asiaauto-sync` plugin (v0.13) |
| Currency             | PLN                                                            |
| Logo                 | `asia-auto-logo.png` (red "AsiaAuto.pl" + amber dot)          |
| Favicon              | `Asia-auto-logo-favico.png` (amber "o." on black)             |

### Active Plugins (2026-04-05)

| Plugin                     | Version | Purpose                              |
|----------------------------|---------|--------------------------------------|
| AsiaAuto Sync              | 0.29.0-wip | CPT, taxonomies, API sync, import, price (v0.28.0 pipeline: Chiny+rejestracja → agencja celna → cło → transport z portu → akcyza → homologacja → prowizja → VAT 23% na końcu; v0.29.0 dual-mode CNY+CIF PLN z wspólnym helperem `runPipelineFromStep3()`), orders (customer+stock types, 11 statuses, manage_asiaauto_orders cap), order wizard (multi-upload signed contract, confirm flow), contract PDF v2 with admin revert, extra_prep (resilient JSON parser), Gemini translate, Inventory + subpages (W drodze / W Rzeszowie) with stock/customer logic, complectation, diag, manual import from Dongchedi, **manual listing editor v0.29.0-wip** (task #8 Kroki 2+3: metabox "Dane pojazdu" w głównej kolumnie edytora — strict `<select>` z istniejących termów (bez auto-create), CIF PLN input, AJAX HTML-table breakdown preview, row action "Duplikuj" bez galerii/thumb/VIN/cen, Gutenberg off for listings CPT, default taxonomy metaboxy usunięte z sidebara, inject button "Wygeneruj tytuł" pod #titlewrap; **metabox galerii** — drag&drop upload, auto WebP conversion przez `AsiaAuto_Media::storeLocalUpload()` (additive, reuse helperów SEO), 4 AJAX endpoints, raw meta ops dla `_thumbnail_id` (bypass `set_post_thumbnail()` internal DELETE path), self-healing gallery[0]→`_thumbnail_id` w renderze), editable email templates & status display, sync toggle, purge-outdated, model translation pipeline, utility shortcodes (aa_phone, aa_whatsapp, aa_back, aa_breadcrumb), security (login hiding via .htaccess + primaauto seller role), branded /biuro/ login (self-contained CSS, text logo, Inter, navy/red/amber palette), homepage shortcode [asiaauto_homepage] + contact shortcode [asiaauto_contact] with LocalBusiness schema |
| Elementor                  | 3.35.7  | Page builder (free base)             |
| Elementor Pro              | 3.35.1  | Theme Builder, Loop, Forms, Popups   |

### Removed (was Motors stack + unused)

Motors theme, Motors Pro, Motors Elementor Widgets, Motors Listing Types, 
STM Motors Extends, WooCommerce, Subscriptio, RevSlider, WPBakery, 
Add to Any, Breadcrumb NavXT, MailChimp, Envato Market, STM Importer, 
Custom Icons, Contact Form 7, JetSmartFilters — ALL REMOVED.

## MCP Server — Remote Access (configured 2026-04-02, upgraded to v2.0 on 2026-04-13)

AsiaAuto Elara MCP server allows Claude to access the server remotely
from claude.ai chat interface — read files anywhere in public_html, query
database (read+write), inspect orders/listings, check logs.

| Setting           | Value                                              |
|-------------------|----------------------------------------------------|
| MCP URL           | `https://asiaauto.pl/mcp-test/mcp.php`             |
| Connector name    | `AsiaAuto Elara` (custom connector in claude.ai)   |
| Transport         | Streamable HTTP (JSON-RPC 2.0 over POST)           |
| Auth              | Authless (URL-based security)                      |
| Protocol version  | 2025-03-26                                         |
| MCP version       | 2.0.0 (upgraded 2026-04-13, was 1.0.0)             |
| PHP file          | `{WP_ROOT}/mcp-test/mcp.php`                       |

### Available MCP Tools (v2.0, 13 tools)

| Tool              | Description                                              |
|-------------------|----------------------------------------------------------|
| `status`          | Server status: PHP, WP version, plugin info, MCP version |
| `stats`           | Listings by status, orders by status, **orders_vin_missing**, last sync |
| `read_file`       | Read file from anywhere in public_html (max 512 KB). Blocked: wp-config.php, .env, root .htaccess |
| `list_dir`        | List directory contents with sizes and dates (anywhere in public_html) |
| `logs`            | Last N lines (10-500) of `sync` (asiaauto-sync.log) or `debug` (wp-content/debug.log) |
| `query_db`        | SELECT/SHOW/DESCRIBE/EXPLAIN on WP database. Use `{prefix}` for `wp7j_`. Truncated to 100 rows |
| `query_db_write`  | INSERT/UPDATE/DELETE/CREATE/ALTER/DROP/RENAME/TRUNCATE/REPLACE. Protected tables: `wp7j_users`, `wp7j_usermeta` |
| `options`         | Read `wp_options` by name list or LIKE pattern. Auto-unserialize. Default includes `asiaauto_order_config`, `asiaauto_import_config` |
| `plugins_list`    | All installed plugins with active/inactive status and version |
| `order_details`   | Full order dump by ID: meta (JSON-decoded for status_log & breakdown), listing title, customer email |
| `listing_details` | Full listing dump by ID: meta + taxonomies (make/serie/body/fuel/etc.) + reservation status + thumbnail |
| `cron`            | AsiaAuto cron schedule (sync + cleanup hooks)            |
| `diag_list`       | List available diagnostic scripts in diag/               |

### MCP Workflow

1. **Read from server** — always use MCP tools to check current state of code,
   data, and logs. Never assume file contents from skill or memory.
2. **Generate changes** — produce **complete files** as downloadable artifacts.
   Always read current version via `read_file` first, then generate the full
   replacement file (not fragments or patches). User swaps the file on server.
3. **User deploys** — user uploads files via FTP/SSH. No direct write access to FS,
   but DB writes possible via `query_db_write` for migrations/fixes.
4. **Verify** — use MCP to confirm changes are live (`read_file`, `query_db`, or
   `order_details` / `listing_details` for specific entities).

### MCP Limitations (v2.0)

- No direct FS write — file changes require FTP/SSH deploy
- No shell/exec — shared hosting disables proc_open for web PHP
- DB write protected: `wp7j_users` and `wp7j_usermeta` cannot be modified (passwords, tokens)
- File access blocked: `wp-config.php`, `.env*` (anywhere), `.htaccess` (root only; subdirs OK)
- `query_db` max 100 rows (truncated)
- `read_file` max 512 KB per file
- Diagnostic scripts: list only, execution requires SSH CLI
- Cloudflare: currently disabled (gray clouds, DNS via Hostido nameservers)

### MCP v2.0 deployment notes

The MCP upgrade from v1.0 to v2.0 happened during Krok 2 of Contract Rework (2026-04-13).
Key changes in `mcp-test/mcp.php`:
- `ALLOWED_DIRS` widened from plugin+theme to full `public_html`
- Added 5 new tools: `query_db_write`, `options`, `plugins_list`, `order_details`, `listing_details`
- `stats` extended with `orders_by_status` and `orders_vin_missing` metrics
- Blacklist added: `wp-config.php`, `.env*`, root `.htaccess` (subdir `.htaccess` OK for debugging)
- `query_db` whitelist extended: `SHOW`/`DESCRIBE`/`EXPLAIN` now allowed

After editing `mcp.php`, the user MUST reconnect the connector in claude.ai settings
(or restart the app) — Claude's tool list is cached per conversation.

## File & Code Policy (established 2026-04-02)

### Single source of truth: SERVER (via MCP)

| What              | Where                           | Role                         |
|-------------------|---------------------------------|------------------------------|
| Source code       | Server (Elara) via MCP          | Source of truth               |
| Skill (SKILL.md)  | claude.ai project knowledge    | Architecture, decisions, conventions, design system |
| Project files     | claude.ai project               | **EMPTY** — no code files    |
| Artifacts (chat)  | Generated per-session           | Deliverables for user to deploy |

### Rules

- **Never assume** file contents from skill or past conversations — always `read_file` first
- **Skill contains** architecture decisions, design system, conventions, changelog, MCP config — NOT code
- **File structure** in skill is reference only — use `list_dir` for live state
- **Version info** — verify with `status` / `read_file` on plugin header
- **No code duplication** — code lives on server only, skill documents structure and decisions

## Design System (configured in Elementor Site Settings, 2026-03-22)

Design reference: mobile.de — clean, functional, data-dense automotive UI.
Font: Inter (Google Fonts) — geometric sans-serif, excellent Polish diacritics.

### Global Colors (Elementor → Site Settings → Global Colors)

| Name             | Hex       | CSS variable context                    |
|------------------|-----------|-----------------------------------------|
| Primary          | `#1B2A4A` | Header, footer, nav, primary buttons    |
| Drugorzędny      | `#718096` | Secondary text, labels, meta info       |
| Text primary     | `#2D3748` | Body text, headings H3-H4              |
| Akcent           | `#D63031` | Prices, CTA buttons, sale badges        |
| Background       | `#F5F6F8` | Page background, section fills          |
| Card / Surface   | `#FFFFFF` | Listing cards, forms, modals            |
| Highlight amber  | `#E8AC07` | "Nowe" badge, stars, featured listings  |
| Success green    | `#38A169` | EV badge, "Dostępny", eco indicators    |

Additional derived colors (not in Elementor globals, use in custom CSS/code):
- Hover red: `#B52828` (darker Akcent for button hover)
- Border gray: `#E1E4E8` (form field borders, card borders)
- EV badge bg: `#E6F7EE` with text `#276749`
- Type badge bg: `#EBF0F7` with text `#1B2A4A`
- Owner badge bg: `#FFF5E5` with text `#8B6914`

### Global Fonts (Elementor → Site Settings → Global Fonts)

| Slot         | Font  | Weight | Usage                    |
|--------------|-------|--------|--------------------------|
| Główny       | Inter | 700    | H1, H2 headings         |
| Drugorzędny  | Inter | 600    | H3, H4, subtitles       |
| Tekst        | Inter | 400    | Body, descriptions       |
| Akcent       | Inter | 700    | Prices, CTA labels       |

### Typography Scale (Elementor → Site Settings → Typography)

| Element  | Size  | Weight | Color                    |
|----------|-------|--------|--------------------------|
| Body     | 15px  | 400    | Text primary (#2D3748)   |
| Links    | —     | —      | Primary (#1B2A4A), hover: Akcent (#D63031) |
| H1       | 32px  | 700    | Primary (#1B2A4A)        |
| H2       | 24px  | 600    | Primary (#1B2A4A)        |
| H3       | 18px  | 600    | Text primary (#2D3748)   |
| H4       | 15px  | 600    | Text primary (#2D3748)   |
| H5       | 13px  | 600    | Drugorzędny (#718096)    |
| Price    | 22px  | 700    | Akcent (#D63031)         |
| Meta     | 13px  | 400    | Drugorzędny (#718096)    |

### Buttons (Elementor → Site Settings → Buttons)

| State    | Text color | Background     | Border radius | Padding          |
|----------|-----------|----------------|---------------|------------------|
| Normal   | `#FFFFFF` | Akcent (#D63031) | 6px          | 12px 24px        |
| Hover    | `#FFFFFF` | `#B52828`      | 6px           | 12px 24px        |

No shadows, no text shadows — flat design.

### Form Fields (Elementor → Site Settings → Form Fields)

| Property        | Value                              |
|-----------------|------------------------------------|
| Label color     | Drugorzędny (#718096), 13px/500    |
| Field font      | Inter, 15px/400                    |
| Text color      | Text primary (#2D3748)             |
| Background      | Card / Surface (#FFFFFF)           |
| Border          | Solid, `#E1E4E8`, 6px radius      |
| Padding         | 10px 14px                          |
| Focus border    | Primary (#1B2A4A)                  |

### Layout (Elementor → Site Settings → Layout)

| Setting           | Value    |
|-------------------|----------|
| Container width   | 1280px   |
| Widget gap        | 20px     |
| Page background   | #F5F6F8  |
| Images radius     | 6px      |

### Logo & Branding

- **Logo**: `asia-auto-logo.png` — red "AsiaAuto" text + amber dot, dark bg
- **Favicon**: `Asia-auto-logo-favico.png` — amber "o." circle on black
- **Brand colors**: Red (#D63031) from logo = Akcent, Amber (#E8AC07) = Highlight
- **Tagline**: "Samochody z Chin — import, sprzedaż, gwarancja"

## CPT & Taxonomies (self-contained, v0.9.1)

Registered by `class-asiaauto-cpt.php` in asiaauto-sync plugin.
No external theme/plugin dependency. Falls back gracefully if Motors 
ever re-activated (checks `post_type_exists()` / `taxonomy_exists()`).

**CPT:** `listings`
- Archive slug: `/oferta/`
- Supports: title, editor, thumbnail, excerpt, custom-fields, REST API
- Admin menu icon: `dashicons-car`

### Taxonomy slugs (registered by plugin)

| Field           | Taxonomy key     | Frontend slug      | Hierarchical |
|-----------------|------------------|--------------------|--------------|
| Marka           | `make`           | `/marka/`          | yes          |
| Model           | `serie`          | `/model/`          | yes          |
| Nadwozie        | `body`           | `/nadwozie/`       | yes          |
| Paliwo          | `fuel`           | `/paliwo/`         | yes          |
| Skrzynia biegów | `transmission`   | `/skrzynia-biegow/`| yes          |
| Napęd           | `drive`          | `/naped/`          | yes          |
| Kolor zew.      | `exterior-color` | `/kolor-nadwozia/` | yes          |
| Kolor wew.      | `interior-color` | `/kolor-wnetrza/`  | yes          |
| Rok             | `ca-year`        | `/rocznik/`        | yes          |
| Stan            | `condition`      | `/stan/`           | yes          |

### Meta keys (registered for REST API visibility)

| Field                | Meta key                    | Type    | Notes                          |
|----------------------|-----------------------------|---------|--------------------------------|
| Cena (PLN)           | `price`                     | number  | Final PLN (via AsiaAuto_Price) |
| Cena oryginalna (CNY)| `_asiaauto_original_price`  | number  | Raw price from API             |
| Breakdown cenowy     | `_asiaauto_price_breakdown` | string  | JSON with full cost pipeline   |
| Data przeliczenia    | `_asiaauto_price_calculated_at` | string | ISO datetime              |
| URL oferty           | `_asiaauto_url`             | string  | Link to source platform        |
| Cena promocyjna      | `sale_price`                | number  | Strikethrough original         |
| Przebieg (km)        | `mileage`                   | integer | Kilometers                     |
| Silnik (pojemność)   | `engine`                    | string  | e.g. "1500"                    |
| Zużycie paliwa       | `fuel-consumption`          | string  | e.g. "5.1"                     |
| Lokalizacja          | `stm_car_location`          | string  | Polish city name               |
| VIN                  | `vin_number`                | string  |                                |
| Data rejestracji     | `registration_date`         | string  | Format: d/m/Y                  |
| Moc (KM)             | `_asiaauto_horse_power`     | integer | Horsepower                     |
| Właściciele          | `_asiaauto_owners_count`    | integer | Number of previous owners      |
| Galeria              | `gallery`                   | serial  | Serialized array of attach IDs |
| Featured image       | `_thumbnail_id`             | int     | WP attachment ID               |
| Extra features       | `additional_features`       | string  | Comma-separated list           |
| Complectation        | `_asiaauto_complectation`   | string  | Translated (ZH→EN/PL)         |
| Complectation orig.  | `_asiaauto_complectation_original` | string | Raw Chinese (before translation) |

### Dual storage pattern (kept for compatibility)

Taxonomy values stored in TWO places (Importer does both):
1. **Taxonomy term** assigned to post (`wp_set_object_terms`)
2. **Post meta** with taxonomy slug as key (`update_post_meta`)

```php
wp_set_object_terms($post_id, 'BYD', 'make');     // taxonomy
update_post_meta($post_id, 'make', 'byd');          // meta (slug)
```

Both stored — frontend can query via taxonomy OR meta (flexible for 
Elementor Pro loop or custom queries).

### Slug generation in importer (v0.11.0)

**Critical:** For translated taxonomies (fuel, drive, transmission, color, body, condition),
importer passes original API value as `$api_value` to `setTaxonomyAndMeta()`:

```php
// Correct: slug from API value "electric" → matches sync-filters term
$this->setTaxonomyAndMeta($post_id, $this->tax_fuel, $fuel_pl, $data['engine_type']);

// Wrong (pre-v0.11.0): slug from Polish name "Elektryczny (EV)" → "elektryczny-ev" → duplicate
$this->setTaxonomyAndMeta($post_id, $this->tax_fuel, $fuel_pl);
```

For non-translated taxonomies (make, model, year): `slugify()` from display name.
`slugify()` strips non-ASCII (Chinese) and handles '+' → '-plus'.

## Architecture

```
auto-api.com (Dongchedi + Che168)
        │
        ▼
  PHP SDK (autoapi/client)
        │
        ▼
  WP-CLI Sync Scripts
  ├── sync-filters    (taxonomy sync via /filters)
  ├── import          (full import via /offers + image download + Gemini + price pipeline)
  ├── sync            (ongoing sync via /changes + transient lock + inner_id injection)
  ├── cleanup         (rotation: sold→trash→delete, skips reserved listings)
  ├── purge-all       (delete all listings + images, for testing)
  ├── purge-outdated  (delete listings not matching current import filters, protects orders)
  ├── recalculate-prices  (bulk price recalc after config change)
  ├── price-check     (preview calculation or listing breakdown)
  ├── download-missing-images  (retry failed image downloads)
  └── status/check    (diagnostics + disk usage + price config)
        │
        ▼
  Price Module (v0.28.0 — class-asiaauto-price.php)
  ├── Pipeline (9 kroków, kolejność klienta): bazowa → +Chiny/rej./transport+ubezp → +agencja celna/wyładunek → +cło% → +transport z portu → +akcyza%(fuel) → +homologacja/detailing → +prowizja → +VAT 23% → zaokr
  ├── Config: asiaauto_price_config (12 params w wp_options)
  ├── New keys (v0.28.0): chiny_rejestracja_transport (18000), agencja_celna_wyladunek (2600), transport_z_portu (3400), homologacja_detailing (1500)
  ├── Lazy migration: stare klucze (transport_ubezpieczenie/transport_krajowy/rejestracja_homologacja) mapowane w getConfig() jako fallback
  ├── Admin UI: Narzędzia → AsiaAuto Import → tab "Kalkulator cen" — 9 kroków, horyzontalna tabela akcyz (paliwa w kolumnach), preview 14 kolumn
  ├── Akcyza: per fuel type slug (petrol 3.1%, hybrid 1.55%, EV/PHEV/EREV 0%), edytowalne z admina
  ├── VAT: configurable (default 23%), liczony NA KOŃCU od pełnej sumy (1..8), włącznie z prowizją
  ├── Per-listing: _asiaauto_price_breakdown (JSON z fuel_type), _asiaauto_original_price (CNY)
  ├── Bulk: wp asiaauto recalculate-prices (batch fuel_type lookup, after config change)
  ├── Check: wp asiaauto price-check --cny=80000
  └── Prowizja: max(podstawa_prowizji × %, min kwota) — domyślnie 10% / min 10000 zł
        │
        ▼
  Translation Pipeline
  ├── Static maps: colors, body types, transmissions, engines, drives, cities
  ├── Complectation: segment-based ZH→EN/PL (~55 segments, incl. brand strip + LiDAR/Excellence)
  │   ├── Static segment map: data/translations-complectations.php
  │   ├── Translated at import + stored in _asiaauto_complectation
  │   ├── Original preserved in _asiaauto_complectation_original
  │   └── CLI: wp asiaauto fix-titles — bulk retranslate + rebuild titles
  ├── Extra prep: ~200 vehicle spec keys → 19 categories (ZH→PL, 830 labels)
  │   ├── Static value map: ~200 Chinese→Polish translations
  │   ├── Pattern-based: 马力→KM, 万→¥, 年或万公里→warranty, 色→colors
  │   └── 选配 (optional) values skipped — meaningless for used cars
  ├── Descriptions: Gemini Flash 2.5 (primary) + DeepL (fallback) ZH→PL
  ├── Original stored in _asiaauto_description_original (backup)
  └── Status tracked: _asiaauto_description_status (ok/failed/empty)
        │
        ▼
  Image Pipeline
  ├── Download at import: API URL → wp-content/uploads/asiaauto/YYYY/MM/
  ├── SEO naming: {mark}-{model}-{year}-{city_pl}-{inner_id}-{n}.webp
  ├── WP attachments: alt text, title, parent post
  ├── Custom sizes: asiaauto-card (350x250), asiaauto-thumb (190x132), asiaauto-gallery (800x600)
  └── Featured image (_thumbnail_id) + gallery meta (serialized attach IDs)
        │
        ▼
  Order Module (v0.22.0 — multi-upload signed contract, confirm flow, admin revert, 2026-04-03)
  ├── CPT: asiaauto_order (not public, custom admin UI)
  ├── Order types: customer (listing blocked) | stock (listing available for clients) — _order_type meta
  ├── Role: asiaauto_customer (read only, wp-admin blocked → /zamow/)
  ├── Role: primaauto (seller — listings, pages, orders, taxonomy, media; no plugins/settings/users) (v0.24.0)
  ├── Config: asiaauto_order_config (18 params incl. company data)
  ├── Content: AsiaAuto_Order_Content (editable email templates + status display)
  │   ├── asiaauto_order_email_templates (15 templates in wp_options)
  │   ├── asiaauto_order_status_display (11 statuses: label+color+description in wp_options)
  │   ├── Placeholders: {customer_name}, {order_id}, {listing_title}, {magic_link}, {customer_phone}, etc.
  │   └── Fallback: hardcoded defaults if nothing saved in DB
  ├── Statuses: 11-step lifecycle with flexible validated transitions
  ├── SHIPPING_STATUSES: zakupione, w_drodze
  ├── Legacy status support: LEGACY_STATUS_MAP for historical data from v0.20 (5 old → 2 new)
  ├── Admin: top-level "Zamówienia" menu (lista+karta+config+dane firmy), order type badges
  ├── REST API: 7 endpoints (reserve, start, state, billing, upload, transfer, login)
  ├── Frontend wizard: [asiaauto_order_wizard] — 5-step (register→verify→billing→contract+upload→tracking)
  ├── Contract PDF: mPDF auto-generate at umowa_gotowa (deferred via WP cron in REST, immediate in CLI), §1-§9 template with full data, admin regenerate
  ├── File uploads: signed contract + payment proof (multipart, 10MB, PDF/JPG/PNG/XML)
  ├── Auth: magic link (48h token) + frontend login + welcome email with password
  ├── Email notifications: admin + customer on status change (magic links in every mail), stock orders skip customer mail
  ├── Shortcodes: [asiaauto_order_cta] (Elementor button), [asiaauto_order_url] (bare URL)
  ├── Listing protection: _asiaauto_reservation_status + _asiaauto_reservation_type (stock vs customer)
  │   ├── Both types protect from rotation/sync
  │   ├── Only customer type blocks new orders (listingIsBlockedForOrders)
  │   └── Stock→customer conversion: auto-closes stock order when customer orders same listing
  ├── Deposit: % + minimum + rounding (configurable)
  ├── Commission override: auto-recalculates price from breakdown
  ├── Sync guard: class-asiaauto-sync.php skips updateListing() for reserved listings
  └── CLI: order-create, order-status, order-list
        │
        ▼
  Frontend
  ├── Theme: Hello Elementor + child theme 'asiaauto'
  ├── Builder: Elementor Pro 3.35.1
  ├── Shortcodes: gallery, key_specs, badges, price, specs, tech_specs, equipment, cta, 404, order_cta, order_url, aa_phone, aa_whatsapp, aa_back, aa_breadcrumb
  ├── Design system: Inter font, navy/red/amber palette (configured)
  ├── Inventory page: [asiaauto_inventory] — DONE (v0.10.0)
  ├── Order wizard: [asiaauto_order_wizard] — DONE (v0.13.0), page /zamow/
  ├── Single listing: [asiaauto_single] — gallery + specs + order CTA, Schema.org Car, resilient JSON parser (v0.25.0)
  ├── Homepage: [asiaauto_homepage] — DONE (v0.25.0), hero search + stats + latest + makes + body types + fuels + USP + CTA, Schema.org AutoDealer + WebSite
  ├── Contact: [asiaauto_contact] — DONE (v0.26.0), hero H1 + dane kontaktowe + Google Maps iframe + dane firmy + bank + CTA, dane z asiaauto_order_config, Schema.org LocalBusiness + OpeningHoursSpecification
  └── SEO: Schema.org on homepage (AutoDealer, WebSite+SearchAction), single (Car), contact (LocalBusiness). No SEO plugin. Sitemap: WP built-in (/wp-sitemap.xml).
```

### Manual Import (v0.15.0)

```
Admin → Ogłoszenia → Dodaj z Dongchedi
        │
        ▼
  Paste URL/inner_id → AJAX preview
        │
        ├── getOffer(dongchedi, inner_id) → API data
        ├── Filter analysis (mark, year, km, price vs config)
        ├── Price breakdown (AsiaAuto_Price::calculate)
        ├── Image thumbnails (from API URLs)
        └── Translations preview (engine_type, body_type, etc.)
        │
        ▼
  "Zaimportuj" button → importListing($data, $source, force: true)
        │
        ├── Bypasses isAllowedByConfig()
        ├── Full import pipeline (title, slug, meta, taxonomies, price, images, description)
        └── _asiaauto_manual_import = 1 (marker meta)
```

## Order Module (v0.22.0 — multi-upload signed contract, confirm flow, admin revert, 2026-04-03)

> ⚠️ **PLANNED REWORK (v0.30+) — Umowa pod model agency Prima-Auto.**
> Obecny moduł zakłada model „pod klucz" — jedna cena PLN brutto, zaliczka jako procent ceny, prowizja ukryta w breakdownie.
> Klient (Ruslan Prima) używa modelu **agencyjnego**: depozyt zabezpieczający + wynagrodzenie zleceniobiorcy jako dwa osobne byty w umowie, niezależne od pipeline'u cenowego.
> **Pełny runbook przebudowy — patrz sekcja „Planned: Contract Rework — Agency Model (Prima-Auto)" na końcu pliku.**
> Dokumentacja poniżej opisuje AKTUALNY stan modułu, który zostanie zmodyfikowany w zakresie: treść PDF umowy (§1–§9), nowe meta `_order_contract_commission_net`, nowe pola configu `contract_commission_*`, semantyka pola depozytu. Pipeline cenowy, breakdown, `_order_commission_override`, wizard strukturalnie — nietknięte.

Implemented 2026-03-24 (v0.8.0), extended 2026-04-01 (v0.13.0), polished 2026-04-02 (v0.14.0),
content management 2026-04-03 (v0.17.0), status redesign 2026-04-03 (v0.21.0),
signed contract flow 2026-04-03 (v0.22.0).
Full order system: backend + admin + REST API + frontend wizard + contract PDF + auth + email templates.

### CPT: `asiaauto_order`

Not public, no default WP UI. Managed via custom admin pages + REST API.
Registered by `class-asiaauto-order.php`.

### Order types (v0.21.0)

| Type | `_order_type` | Listing blocked? | Use case |
|------|---------------|------------------|----------|
| Customer | `customer` (default) | YES — no one else can order | Client ordered specific car |
| Stock | `stock` | NO — clients can order via wizard | Seller bought car for lot/resale |

Stock→customer conversion: when client orders a stock listing, old stock order auto-closes
(`zakonczone` with note), new customer order takes ownership of reservation.

### Order meta keys

| Meta key                         | Type     | Description                              |
|----------------------------------|----------|------------------------------------------|
| `_order_listing_id`              | int      | Post ID of linked listing                |
| `_order_customer_id`             | int      | WP user ID (0 = internal order)          |
| `_order_status`                  | string   | Current lifecycle status                 |
| `_order_type`                    | string   | `customer` or `stock` (v0.21.0)          |
| `_order_price_cny`               | float    | CNY price copied from listing at creation|
| `_order_price_breakdown`         | JSON     | Full breakdown from AsiaAuto_Price       |
| `_order_price_final`             | int      | Final PLN price (admin editable)         |
| `_order_commission_override`     | int/''   | Override commission (auto-recalcs price) |
| `_order_deposit_amount`          | int      | Deposit amount PLN (admin editable)      |
| `_order_deposit_paid`            | '0'/'1'  | Whether deposit is paid                  |
| `_order_deposit_paid_at`         | datetime | ISO timestamp of deposit confirmation    |
| `_order_source_url`              | string   | Dongchedi URL copied from listing        |
| `_order_contract_attachment_id`  | int      | WP attachment ID of generated contract PDF |
| `_order_signed_attachment_id`    | JSON/int | WP attachment IDs of signed contract (v0.22.0: JSON array, backward compat with int) |
| `_order_payment_proof_id`        | int      | WP attachment ID of payment proof upload |
| `_order_signature_method`        | string   | profil_zaufany / reczny / kwalifikowany  |
| `_order_signed_at`               | datetime | ISO timestamp of signature               |
| `_order_admin_notes`             | text     | Free-form admin notes                    |
| `_order_status_log`              | JSON     | Array of {status, timestamp, user_id, note} (JSON_UNESCAPED_UNICODE) |
| `_order_cancel_reason`           | string   | Reason for cancellation                  |
| `_order_delivery_estimate`       | int      | Estimated delivery time in days          |
| `_order_contract_number`         | string   | Auto-generated: AA/2026/0001             |

### Order statuses (v0.21.0 — 11 statuses, down from 16)

Removed: `transport_morski`, `odprawa_celna`, `transport_krajowy`, `formalnosci`, `dostarczone`.
Added: `w_drodze` (replaces 4 shipping sub-statuses), `na_placu` (replaces `dostarczone`).

```
CUSTOMER PATH:
NOWE → WERYFIKACJA → POTWIERDZONE → DANE_KLIENTA → UMOWA_GOTOWA → PODPISANE → ZAREZERWOWANE
                  └→ ODRZUCONE

FULFILLMENT (both types):
ZAREZERWOWANE → ZAKUPIONE → W_DRODZE → NA_PLACU → ZAKOŃCZONE
                    ↘─────────↗    ↗     (flexible — can skip steps)
                    ↘────────────────↗

STOCK PATH (direct entry):
createInternal() → ZAKUPIONE | W_DRODZE | NA_PLACU → ZAKOŃCZONE

TERMINAL: ZAKOŃCZONE (clears reservation), ANULOWANE (clears reservation), ODRZUCONE
```

### Flexible transitions (v0.21.0)

Admin can skip intermediate fulfillment steps:

| From | Allowed next statuses |
|------|----------------------|
| zarezerwowane | zakupione, w_drodze, na_placu |
| zakupione | w_drodze, na_placu |
| w_drodze | na_placu |
| na_placu | zakonczone |

`anulowane` reachable from any non-terminal status.

### Listing reservation meta (v0.21.0)

Two meta keys on listing (both set by `syncListingReservation()`):

| Meta key | Values | Purpose |
|----------|--------|---------|
| `_asiaauto_reservation_status` | `reserved`, `in_transit`, `on_lot` | Physical status — controls page visibility |
| `_asiaauto_reservation_type` | `customer`, `stock` | Order type — controls blocking + button |
| `_asiaauto_reservation_order_id` | int | Which order owns this listing |

### LISTING_RESERVATION_MAP (v0.21.0)

| Order status | `_asiaauto_reservation_status` | Shown on page |
|---|---|---|
| potwierdzone → zarezerwowane | `reserved` | Main inventory (badge) |
| zakupione | `in_transit` | `/w-drodze/` |
| w_drodze | `in_transit` | `/w-drodze/` |
| na_placu | `on_lot` | `/w-rzeszowie/` |
| zakonczone | **CLEARS all reservation meta** | Disappears |
| anulowane | **CLEARS all reservation meta** | Disappears |

### Listing blocking logic (v0.21.0)

| Method | What it checks | Used for |
|--------|---------------|----------|
| `listingHasReservation()` | Any `_asiaauto_reservation_status` set | Rotation/sync protection |
| `listingIsBlockedForOrders()` | Has reservation AND `_asiaauto_reservation_type != stock` | Wizard + API blocking |
| `isProtectedFromRotation()` | Same as `listingHasReservation()` | Sync guard |

### Subpage card behavior (v0.21.0)

On `/w-drodze/` and `/w-rzeszowie/`:
- Stock listing → active "Zamów" button (customer can order, triggers stock→customer conversion)
- Customer listing → disabled "Zarezerwowane" badge

### Legacy status support (v0.21.0)

`LEGACY_STATUS_MAP` in `AsiaAuto_Order`:
```php
'transport_morski'  => 'w_drodze',
'odprawa_celna'     => 'w_drodze',
'transport_krajowy' => 'w_drodze',
'formalnosci'       => 'w_drodze',
'dostarczone'       => 'na_placu',
```

Used in: `resolveStep()`, `resolveWizardStep()`, `getStatusLabel()`, `getStatusColor()`, `statusBadgeHtml()`.
Historical status log entries display with original labels but mapped colors.

### Wizard step mapping (v0.21.0)

| Step | Statuses                          | User action                           |
|------|-----------------------------------|---------------------------------------|
| 1    | nowe                              | Dane kontaktowe + RODO + regulamin    |
| 2    | weryfikacja                       | Oczekiwanie na admina                 |
| 3    | potwierdzone, dane_klienta        | Uzupełnienie danych billing (12 pól)  |
| 4    | umowa_gotowa, podpisane           | Pobierz PDF → upload podpisanej → przelew → upload potwierdzenia |
| 5    | zarezerwowane → zakonczone        | Tracking realizacji                   |
| -1   | odrzucone, anulowane              | Końcowe (błąd/anulacja)              |

**Auto-transitions:**
- Step 1 submit → `nowe → weryfikacja` (auto)
- Step 3 billing complete → `potwierdzone → dane_klienta → umowa_gotowa` (auto, triggers PDF)
- Step 4 signed contract upload + confirm button → `umowa_gotowa → podpisane` (v0.22.0: manual confirm)
- Admin confirms deposit → `podpisane → zarezerwowane` (manual)

### Order config (wp_options: `asiaauto_order_config`, 18 params)

| Parametr                     | Typ    | Domyślnie | Opis                                    |
|------------------------------|--------|-----------|----------------------------------------|
| `deposit_percent`            | float  | 10        | % ceny końcowej                        |
| `deposit_min`                | int    | 30000     | Minimalna zaliczka PLN                 |
| `deposit_rounding`           | int    | 1000      | Zaokrąglenie w górę (0=brak)          |
| `bank_account_number`        | string | ''        | Nr konta do przelewu                   |
| `bank_account_name`          | string | ''        | Nazwa odbiorcy                         |
| `transfer_title_template`    | string | 'Zaliczka AsiaAuto zamówienie #{order_id}' | Szablon |
| `reservation_expiry_hours`   | int    | 48        | Czas na weryfikację                    |
| `admin_email_notifications`  | bool   | true      | Maile do admina                        |
| `customer_email_notifications`| bool  | true      | Maile do klienta                       |
| `admin_notification_email`   | string | ''        | Email admina ('' = WP default)         |
| `company_phone`              | string | ''        | Telefon kontaktowy                     |
| `company_name`               | string | ''        | Nazwa firmy (do umów)                  |
| `company_address`            | string | ''        | Adres firmy                            |
| `company_nip`                | string | ''        | NIP firmy                              |
| `company_regon`              | string | ''        | REGON                                  |
| `company_representative`     | string | ''        | Osoba reprezentująca (do umów)         |
| `default_delivery_days`      | int    | 60        | Szacowany czas dostawy                 |
| `contract_prefix`            | string | 'AA'      | Prefix numeru umowy: AA/2026/0001      |

### Deposit calculation (v0.13.0)

```php
$calculated = ceil($price_pln * $percent / 100);
$deposit = max($calculated, $deposit_min);
$deposit = ceil($deposit / $rounding) * $rounding;  // e.g. 31200 → 32000
```

### REST API (AsiaAuto_Order_API, v0.13.0)

Namespace: `asiaauto/v1`.

| Method | Endpoint                         | Auth          | Description                              |
|--------|----------------------------------|---------------|------------------------------------------|
| GET    | `/listing/{id}/reserve`          | public        | Listing availability + price + deposit   |
| POST   | `/order/start`                   | public*       | Register customer + create order         |
| GET    | `/order/{id}`                    | customer/admin| Full wizard state (step, uploads, contract) |
| POST   | `/order/{id}/billing`            | customer/admin| Submit billing data + auto umowa_gotowa  |
| POST   | `/order/{id}/upload`             | customer/admin| Upload signed contract / payment proof   |
| POST   | `/order/{id}/confirm-signed`     | customer/admin| Confirm signed contract → podpisane (v0.22.0) |
| POST   | `/order/{id}/delete-upload`      | customer/admin| Delete specific signed file before confirm (v0.22.0) |
| GET    | `/order/{id}/transfer`           | customer/admin| Bank transfer details for deposit        |
| POST   | `/auth/login`                    | public*       | Frontend login (email+password)          |

*Honeypot + IP rate limit (10/hour).
Upload: multipart form, `type` = `signed_contract` | `payment_proof`, max 10MB, PDF/JPG/PNG/XML.
v0.22.0: signed_contract supports multi-file (appends to JSON array), blocked after confirm.

### Frontend wizard `[asiaauto_order_wizard]` (v0.13.0)

Page: `/zamow/` — Elementor page with shortcode.
URL params: `?listing_id=X` (new) | `?order_id=X` (resume) | `?magic_token=X&order_id=Y` (auto-login).

**Step 4 flow (umowa i zaliczka) — 4 numbered blocks:**
1. Pobierz umowę (PDF auto-generated at umowa_gotowa)
2. Prześlij podpisaną umowę (v0.22.0: multi-file upload + file list + ✕ delete + "Potwierdzam przesłanie umowy" button → podpisane)
3. Wpłać zaliczkę (dane przelewu z config)
4. Prześlij potwierdzenie wpłaty (opcjonalne, upload)

Sidebar: listing card + cena + zaliczka (visible from step 3) + status badge + `[elementor-template id="174645"]`

### Contract PDF (AsiaAuto_Contract, v0.14.0)

- Library: mPDF v8.3 (`composer require mpdf/mpdf`)
- Auto-generated on `asiaauto_order_status_changed` → `umowa_gotowa`
- **Deferred in REST/web:** `wp_schedule_single_event` (5s delay) — avoids memory/timeout on shared hosting
- **Immediate in CLI:** direct `generate()` call (WP-CLI has plenty of resources)
- **Admin regenerate:** `AsiaAuto_Contract::regenerate($order_id)` — deletes old attachment, generates fresh
- Template: "Umowa Zlecenia Importu Samochodu Osobowego" §1-§9
- Data: config (company), customer (billing), listing (vehicle), order (price/breakdown/deposit)
- Polish number-to-words (kwota słownie)
- Stored as WP attachment → `_order_contract_attachment_id`
- Upload dir: `wp-content/uploads/asiaauto/contracts/`, tempDir: `/tmp/mpdf` (auto-created)

### Auth system (v0.13.0)

- **Role:** `asiaauto_customer` — `read` only, wp-admin blocked → redirect `/zamow/`
- **Account creation:** `findOrCreateCustomer()` → WP user + welcome email with password
- **Magic link:** `generateMagicLink($user_id, $order_id)` → 48h token, single-use, hashed in user meta
- **Frontend login:** REST `POST /auth/login` → `wp_signon()` + rate limit
- **wp-admin block:** `admin_init` redirects `asiaauto_customer` to `/zamow/`

### Email notifications (v0.17.0 — template-based)

Hooked to `asiaauto_order_started` + `asiaauto_order_status_changed`.
All emails rendered via `AsiaAuto_Order_Content` templates with placeholders.

| Event                  | Admin mail              | Customer mail                        |
|------------------------|-------------------------|--------------------------------------|
| New order (frontend)   | ✓ (client data + link)  | ✓ Przyjęcie zgłoszenia + magic link  |
| New account created    | —                       | ✓ Welcome email with password        |
| Status change (admin)  | — (skip self-notify)    | ✓ Status-specific message + magic link |
| Status change (client) | ✓                       | ✓                                    |

All customer mails contain magic link. Plain text format.
Templates editable via: Narzędzia → AsiaAuto Zamówienia → tab "Treści maili".

**15+1 email templates (v0.22.0 — 15 status + 1 system):**
- `welcome` — nowe konto klienta
- `order_started_admin` / `order_started_customer` — nowe zamówienie
- `status_changed_admin` — ogólny zmiana statusu → admin
- `contract_regenerated` — umowa zregenerowana → klient (v0.22.0)
- `status_{każdy_z_11_statusów}` — per-status → klient (stock orders skip customer emails)

**Placeholders:** `{customer_name}`, `{order_id}`, `{listing_title}`, `{price_final}`,
`{deposit_amount}`, `{status_label}`, `{old_status_label}`, `{magic_link}`,
`{company_phone}`, `{company_name}`, `{admin_link}`, `{customer_email}`,
`{customer_phone}` (v0.22.0), `{password}` (welcome only), `{login_url}`

**wp_options:** `asiaauto_order_email_templates` (JSON), `asiaauto_order_status_display` (JSON)

### Shortcodes (order-related)

- `[asiaauto_order_cta]` — Elementor-native button, "Zamów ten samochód" or disabled badge
- `[asiaauto_order_url]` — bare URL `/zamow/?listing_id=X` for Dynamic Tag
- `[asiaauto_order_wizard]` — full 5-step wizard (page `/zamow/`)

### Admin UI (v0.21.0)

**Menu:** Top-level "Zamówienia" (dashicons-clipboard, position 27).
**List view:** Typ column (Stock/Klient badge), stat boxes: Wszystkie, Nowe, Weryfikacja, Zarezerwowane, W realizacji, Na placu.
**Order card:** Order type badge (Stock/Klient) in header + customer section.
**Internal order form:** Dropdown with optgroups: Stock (zakupione/w drodze/na placu) + Klient (zarezerwowane).
**Config:** Narzędzia → AsiaAuto Zamówienia — 3 taby:
  - **Ustawienia** — Zaliczka, Dane firmy, Dane przelewu, Powiadomienia, Inne
  - **Treści maili** — 15 szablonów (subject + body), placeholdery, reset per-template i globalny
  - **Statusy** — 11 statusów: label (text), kolor (color picker), opis kliencki (text), podgląd badge
**Commission override:** auto-recalculates `price_final` from breakdown when saved.
**Contract card:** numer umowy, PDF download/regenerate, signed contract + payment proof attachments with filenames.

### Order module TODO (Etap 5+)

- ✅ PDF auto-generation: deferred via WP cron in REST context, immediate in CLI (v0.14.0)
- ✅ Inventory card: disabled badge "Zarezerwowane"/"W drodze"/"Na placu" + link "Zamów" → /zamow/ (v0.14.0)
- ✅ Admin karta: wyświetlanie załączników klienta (signed contract, payment proof, contract PDF) (v0.14.0)
- ✅ Admin karta: numer umowy + przycisk Wygeneruj/Regeneruj PDF (v0.14.0)
- ✅ Treści maili edytowalne z admin — 17 szablonów z placeholderami (v0.17.0)
- ✅ Zarządzanie statusami i treściami z admin panelu — label, kolor, opis (v0.17.0)
- ✅ Inventory subpages: "W drodze" + "W Rzeszowie" with reservation_status filter (v0.20.0)
- ✅ Order types: customer vs stock — stock listings available for clients on subpages (v0.21.0)
- ✅ Simplified statuses: 16 → 11, flexible transitions, zakonczone clears reservation (v0.21.0)
- ✅ Stock→customer conversion: auto-close stock order when client orders same listing (v0.21.0)
- ✅ Multi-upload signed contract: file list + delete + manual confirm button (v0.22.0)
- ✅ Admin contract regenerate with revert: podpisane → umowa_gotowa, delete signed files, custom email (v0.22.0)
- ✅ Mail sender: wp_mail_from_name "Zamówienia AsiaAuto.pl", wp_mail_from zamowienia@asiaauto.pl (v0.22.0)
- 🔲 Panel klienta /moje-zamowienia/ (lista zamówień, statusy)
- 🔲 Zmiana hasła klienta z frontend
- 🔲 Email HTML templates (obecnie plain text z placeholderami)

## Image Pipeline

### How it works

During `wp asiaauto import`, for each listing:
1. Parse `images` field from API (comma-separated string of URLs)
2. Download each image via `download_url()` (WP HTTP API)
3. Save to `wp-content/uploads/asiaauto/YYYY/MM/{seo-name}.webp`
4. Create WP attachment with SEO meta (alt, title)
5. Generate custom thumbnail sizes (filtered in asiaauto-sync.php)
6. Set `_thumbnail_id` (featured) and `gallery` meta
7. Store original API URLs in `_asiaauto_images` meta (backup)

### SEO naming convention

Filename: `{mark}-{model}-{year}-{city_pl}-{inner_id}-{n}.webp`
Alt text: `BYD Destroyer 05 2024 Dongguan — zdjęcie 1 z 9`

### Thumbnail sizes (custom, no Motors dependency)

Registered by asiaauto-sync.php:
- `thumbnail` (150x150) — WP core
- `asiaauto-card` (350x250) — listing grid card
- `asiaauto-thumb` (190x132) — small thumbnail
- `asiaauto-gallery` (800x600) — single listing gallery

Result: 4 files per image (original + 3 custom sizes).

### Image URL expiry

Dongchedi URLs expire after 6 days (`x-expires` parameter).
Images downloaded at import time — no expiry issue for successfully downloaded files.

## Price Module (v0.29.0)

Centralized price calculator in `class-asiaauto-price.php`. Pełen pipeline kosztów składowych
wg wytycznych klienta (2026-04-10) — kolejność narastającego subtotalu, VAT 23% liczony
na końcu od pełnej sumy łącznie z prowizją.

Od v0.29.0 kalkulator obsługuje **dwa tryby wejścia** (CNY z importera, CIF PLN z ręcznych
ogłoszeń) dzielące ten sam pipeline kroków 3-9. Szczegóły w sekcji "Dual-mode price calculation"
poniżej.

### Pipeline (9 kroków, v0.28.0)

```
1. cena_cny × kurs_cny_pln                                   = cena_bazowa_pln
2. + chiny_rejestracja_transport         (stała, domyślnie 18 000 zł)
3. + agencja_celna_wyladunek              (stała, domyślnie 2 600 zł)
4. + cło = (1+2+3) × clo_procent          (domyślnie 10%)
5. + transport_z_portu                    (stała, domyślnie 3 400 zł)
6. + akcyza = (1+2+3+cło+5) × stawka_paliwa
7. + homologacja_detailing                (stała, domyślnie 1 500 zł)
8. + prowizja = max((1..7) × prowizja_procent, prowizja_min)  (domyślnie 10%, min 10 000 zł)
9. + VAT = (1..8) × vat_procent           (domyślnie 23%)
   → zaokrąglenie w górę do wielokrotności zaokraglenie (domyślnie 1 000 zł)
```

**Kluczowa różnica vs v0.18.0:** VAT przeniesiony z kroku 5 (po akcyzie) na koniec (krok 9),
liczony od pełnego subtotalu razem z prowizją, transportem krajowym i homologacją.
Dodano nowy krok "Agencja celna / wyładunek" jako osobną pozycję między pkt 2 a 4.
Transport "z portu" przesunięty przed akcyzę (krok 5).

### Akcyza — stawki per typ paliwa

Podstawa: `cena_bazowa + chiny + agencja + cło + transport_z_portu` (krok 6 pipeline'u).
Stawki edytowalne z admin panelu, domyślne wg polskiego prawa (silniki do 2.0L):

| Slug taksonomii `fuel` | Typ | Stawka domyślna |
|---|---|---|
| `petrol` | Benzyna | 3.1% |
| `diesel` | Diesel | 3.1% |
| `hybrid` | Hybryda (HEV) | 1.55% |
| `mhev` | Mild hybrid (MHEV) | 1.55% |
| `phev` | Hybryda plug-in (PHEV) | 0% (zwolnienie do 2029) |
| `electric` | Elektryczny (EV) | 0% (zwolnienie do 2029) |
| `erev` | EREV | 0% (zwolnienie do 2029) |
| `cng` | CNG | 3.1% |
| `bi-fuel` | Bi-fuel | 3.1% |

### VAT (krok 9)

Liczony od `(1..8)` — czyli od pełnej sumy łącznie z prowizją, homologacją i transportem.
Domyślnie 23%, edytowalny z admin. **To NIE jest klasyczny VAT importowy** (wartość celna +
cło + akcyza) — to VAT od ceny "pod klucz" zgodnie z modelem biznesowym klienta.

### Config (wp_options key: `asiaauto_price_config`)

| Parametr                       | Typ   | Domyślnie | Opis                                              |
|--------------------------------|-------|-----------|---------------------------------------------------|
| `kurs_cny_pln`                 | float | 0.00      | PLN za 1 CNY (0 = brak przeliczenia)              |
| `chiny_rejestracja_transport`  | int   | 18000     | Chiny + rejestracja + transport morski + ubezp.   |
| `agencja_celna_wyladunek`      | int   | 2600      | Agencja celna + wyładunek kontenera               |
| `clo_procent`                  | float | 10.0      | % cła od (1+2+3)                                  |
| `transport_z_portu`            | int   | 3400      | Transport lawetą z portu UE do Rzeszowa           |
| `akcyza_stawki`                | array | (per fuel)| Mapowanie fuel slug → % akcyzy                    |
| `homologacja_detailing`        | int   | 1500      | Homologacja + detailing + rejestracja PL + OC     |
| `prowizja_procent`             | float | 10.0      | % prowizji od podstawy_prowizji (1..7)            |
| `prowizja_min`                 | int   | 10000     | Minimalna prowizja w PLN                          |
| `vat_procent`                  | float | 23.0      | % VAT od pełnej sumy (1..8)                       |
| `zaokraglenie`                 | int   | 1000      | Zaokrąglenie w górę                               |

### Legacy key mapping (v0.28.0 lazy migration)

`getConfig()` automatycznie mapuje stare klucze na nowe, jeśli nowe jeszcze nie istnieją w bazie.
Pozwala to przejść z v0.27.0 → v0.28.0 bez skryptu migracyjnego — pierwszy zapis konfiguracji
z admin panelu nadpisuje stare wartości nowymi kluczami:

```php
private const LEGACY_KEY_MAP = [
    'transport_ubezpieczenie' => 'chiny_rejestracja_transport',
    'transport_krajowy'       => 'transport_z_portu',
    'rejestracja_homologacja' => 'homologacja_detailing',
];
```

Stare breakdowny w meta `_asiaauto_price_breakdown` zostają w bazie aż do pierwszego
`bulkRecalculate()` — wtedy są nadpisywane nową strukturą.

### Key methods (`AsiaAuto_Price`)

- `::calculate($cny, ?$config, $fuel_type)` — MODE A (CNY). Czysta kalkulacja, zwraca breakdown array z `input_mode='cny'`
- `::calculateFromCif($cif_pln, ?$config, $fuel_type)` — MODE B (CIF PLN, v0.29.0). Pipeline od kroku 3, zwraca breakdown z `input_mode='cif'` i wyzerowanymi polami CNY/chiny. Edge case: `$cif_pln <= 0` → struktura `przeliczone=false`
- `::applyToListing($post_id, ?$cny, ?$fuel_type)` — auto-detect trybu po meta (`_asiaauto_cif_price_pln > 0` → MODE B, inaczej MODE A). Zapisuje cenę + breakdown do meta
- `::getBreakdown($post_id)` — odczytuje zapisany breakdown
- `::bulkRecalculate($args, $dry_run)` — masowe przeliczenie, v0.29.0 obsługuje oba tryby w jednej pętli (2 osobne SQL queries, overlap handling gdy listing ma obie meta — CIF wygrywa). Stats: `total, recalculated, skipped, errors, cny_count, cif_count`
- `::formatBreakdown($b)` — czytelny tekst dla CLI/logów, rozgałęzia nagłówek po `input_mode` (v0.29.0)
- `::getAkcyzaRate($fuel_type, ?$config)` — stawka akcyzy dla danego paliwa
- `::getFuelType($post_id)` — fuel slug z taksonomii listingu
- `::batchGetFuelTypes($post_ids)` — batch query: [post_id => fuel_slug] (1 SQL zamiast N)
- `::runPipelineFromStep3(...)` — **private** (v0.29.0). Shared logic kroków 3-9, używane przez `calculate()` i `calculateFromCif()`. DRY, gwarancja spójności między trybami

### Per-listing storage

- `_asiaauto_original_price` — cena CNY z API (MODE A, nigdy nie modyfikowana; importer)
- `_asiaauto_cif_price_pln` — **cena CIF w PLN** (MODE B, v0.29.0; ręczne ogłoszenia primaauto)
- `_asiaauto_price_breakdown` — JSON z pełnym breakdown (wszystkie 9 kroków + podstawy + paliwo + `input_mode`)
- `_asiaauto_price_calculated_at` — ISO datetime przeliczenia
- `price` — cena końcowa PLN (używana przez frontend/filtry)
- `_asiaauto_manual_entry` — `'1'` dla ręcznych ogłoszeń (v0.29.0, audit flag)
- `_asiaauto_manual_entry_by` — user_id sprzedawcy który dodał (v0.29.0)
- `_asiaauto_manual_entry_at` — ISO timestamp utworzenia ręcznego ogłoszenia (v0.29.0)

**Rozłączność trybów:** importer ustawia tylko `_asiaauto_original_price`, metabox sprzedawcy
ustawia tylko `_asiaauto_cif_price_pln`. Dzięki temu sync po `inner_id` nigdy nie rusza ręcznych
ogłoszeń (brak `_asiaauto_source`/`_asiaauto_inner_id`). W razie anomalii (obie meta na jednym
listingu) `applyToListing()` i `bulkRecalculate()` dają priorytet CIF + logują warning.

### Breakdown JSON structure (v0.29.0)

Pełen zestaw kluczy zwracany przez `calculate()` / `calculateFromCif()`:

```
input_mode, cena_cny, kurs_cny_pln, cif_pln, cena_bazowa_pln,
chiny_rejestracja_transport, agencja_celna_wyladunek,
podstawa_cla, clo_procent, clo_kwota,
transport_z_portu,
podstawa_akcyzy, akcyza_procent, akcyza_kwota, fuel_type,
homologacja_detailing,
podstawa_prowizji, prowizja_procent, prowizja_wyliczona, prowizja_min, prowizja_kwota,
podstawa_vat, vat_procent, vat_kwota,
subtotal, suma_przed_zaokr, zaokraglenie, cena_koncowa,
waluta, przeliczone
```

Nowe pola v0.29.0: `input_mode` (`'cny'`|`'cif'`), `cif_pln` (tylko MODE B, w MODE A = 0).
W MODE B pola `cena_cny=0`, `kurs_cny_pln=0`, `chiny_rejestracja_transport=0`,
`cena_bazowa_pln=cif_pln`.

### Admin UI

Narzędzia → AsiaAuto Import → tab "Kalkulator cen":
- 9 numerowanych pól konfiguracyjnych w nowej kolejności + zaokrąglenie
- **Horyzontalna tabela akcyzy** — 9 paliw w kolumnach (Benzyna, Diesel, HEV, MHEV, PHEV, EV, EREV, CNG, Bi-fuel),
  pod każdym input + licznik ogłoszeń. Zwarta forma zamiast wcześniejszych 9 wierszy.
- Tabela podglądu (14 kolumn): 10 ostatnich ogłoszeń z pełną kalkulacją per krok pipeline'u
- Czerwone "(min)" oznaczenie gdy zadziałała prowizja minimalna
- "⚠" gdy cena w bazie różni się od bieżącej kalkulacji (po zmianie configa, przed bulk recalc)

### Integration points

- **Importer** (`importListing`) → `AsiaAuto_Price::applyToListing()` po utworzeniu posta (MODE A — ustawia `_asiaauto_original_price`)
- **Sync** (`updateListing`) → `AsiaAuto_Price::applyToListing()` przy zmianie ceny (MODE A)
- **Manual Listing Editor** (v0.29.0, `class-asiaauto-listing-editor.php`) → `AsiaAuto_Price::calculateFromCif()` w AJAX preview + `applyToListing()` po zapisie metaboksu (MODE B — ustawia `_asiaauto_cif_price_pln`)
- **CLI** → `recalculate-prices` do masowego przeliczenia (oba tryby), `price-check` do podglądu
- **Order module** → odczytuje `_asiaauto_price_breakdown` + `_asiaauto_url` przy tworzeniu zamówienia
- **Contract PDF** (`AsiaAuto_Contract`) → używa TYLKO `price_final` z order meta, nie breakdownu
  (od v0.16.0 kontrakt nie pokazuje rozbicia kosztów klientowi)

### Dual-mode price calculation (v0.29.0)

`AsiaAuto_Price` obsługuje dwa tryby wejścia, spójne 9-stopniowe wyjście:

| Tryb | Wejście | Meta key | Metoda | Źródło |
|------|---------|----------|--------|--------|
| CNY (A) | Cena w juanach z API | `_asiaauto_original_price` | `calculate()` | Importer Dongchedi / Che168 |
| CIF PLN (B) | Cena CIF w PLN (zakup w Chinach + transport do granicy UE) | `_asiaauto_cif_price_pln` | `calculateFromCif()` | Ręczne ogłoszenia (primaauto + admin) |

**Pipeline CIF startuje od kroku 3** (agencja celna). Kroki 1-2 (cena bazowa z CNY × kursu
+ chiny/rejestracja/transport 18 000 zł) są już zawarte w wartości CIF, więc w breakdownie
wyzerowane (`cena_cny=0`, `kurs_cny_pln=0`, `chiny_rejestracja_transport=0`,
`cena_bazowa_pln=$cif_pln`). Od kroku 3 pipeline leci identycznie jak dla CNY — dzięki
wspólnemu prywatnemu helperowi `runPipelineFromStep3()` obie ścieżki są matematycznie
gwarantowanie spójne.

**Rozłączność:** importer nigdy nie ustawia `_asiaauto_cif_price_pln`. Metabox sprzedawcy
nigdy nie ustawia `_asiaauto_original_price`. Sync po `inner_id` nigdy nie rusza ręcznych
(brak `_asiaauto_source`/`_asiaauto_inner_id`).

**Auto-detect w `applyToListing()`:** jeśli listing ma `_asiaauto_cif_price_pln > 0` → MODE B,
w przeciwnym razie MODE A. Jeśli obie meta ustawione (anomalia, nie powinno się zdarzyć)
→ CIF wygrywa, warning w logu (`class-asiaauto-logger.php`).

**`bulkRecalculate()` w dual-mode:** dwa osobne zapytania SQL (po `_asiaauto_original_price`
i po `_asiaauto_cif_price_pln`), scalane w jedną listę post_ids z overlap handling.
Stats rozszerzone o `cny_count` i `cif_count`. Jeden przebieg `wp asiaauto recalculate-prices`
(albo "Zaktualizuj wszystkie ceny teraz" w adminie) odświeża oba typy po zmianie configu.

**Rationale — dlaczego wspólne API zamiast twardej separacji:** alternatywa była taka, żeby
importer miał swoje `applyToListing` / `bulkRecalculate` a metabox sprzedawcy własne funkcje,
zero przecięć. Odrzucone, bo: (a) po zmianie stawki akcyzy/cła w adminie i kliknięciu "przelicz
wszystko", przy twardej separacji ręczne ogłoszenia cicho zostają ze starą ceną aż sprzedawca
wejdzie w każde i kliknie "Zapisz" — błąd ludzki = strata marży; (b) wspólne API nie tworzy
ryzyka że importer "nadpisze" ręczne ogłoszenie, bo listingi są rozłączne po meta.

**Format breakdownu:** `formatBreakdown()` rozgałęzia nagłówek po `input_mode`:
- `'cny'` → pokazuje "Cena CNY: X ¥" + "Kurs CNY→PLN: Y" + "1. Cena bazowa PLN"
- `'cif'` → pokazuje "Tryb: CIF PLN (ręczne ogłoszenie)" + "Cena CIF: X zł" + "1. Cena bazowa PLN (=CIF)" + "2. Chiny/rej./transp.: wliczone w CIF (0 zł)"

Kroki 3-9 renderują się identycznie w obu trybach.

### Stałe klasy

```php
private const OPTION_KEY     = 'asiaauto_price_config';
private const META_BREAKDOWN = '_asiaauto_price_breakdown';
private const META_ORIGINAL  = '_asiaauto_original_price';   // MODE A — CNY
private const META_CIF       = '_asiaauto_cif_price_pln';    // MODE B — CIF PLN (v0.29.0)
```

## Data Flow

### 1. Initial Setup (one-time)

1. Call `/filters` for each source (dongchedi, che168)
2. Parse the nested mark → model tree
3. Map to registered taxonomies (`make`, `serie`)
4. Populate translation table for enum fields

### 2. Initial Import (one-time)

1. Call `/offers?page=1`, iterate all pages
2. For each listing: translate description ZH→PL via DeepL
3. Create `listings` post with Polish description
4. Calculate PLN price via `AsiaAuto_Price::applyToListing()` (full cost pipeline)
5. Map API fields to meta fields
6. Download images → create WP attachments → set featured + gallery
7. Store `source`, `inner_id`, original CNY price, breakdown JSON, original description

### 3. Ongoing Sync (cron, every 15 min)

1. **Transient lock** — `set_transient('asiaauto_sync_lock_{source}', time(), 600)` prevents
   parallel execution. Auto-expires after 10 min (crash safety). Released at end of run.
2. Read last `change_id` from wp_options
3. Call `/changes?change_id={last_id}` for each source
4. Process changes:
   - `added` → call `getOffer(source, inner_id)` for **complete data** (incl. extra_prep,
     images, description) → then `importListing()` with full data. `/changes` alone does NOT
     return extra_prep — `getOffer()` is required for complete import.
   - `changed` → recalculate price via AsiaAuto_Price, update meta, download missing images
   - `removed` → check reservation protection, then draft or flag as API-removed
5. Save new `change_id` to wp_options
6. **Release lock** — `delete_transient('asiaauto_sync_lock_{source}')`

### 4. Cleanup Cron (daily, 3:00 AM)

1. Posts with `_asiaauto_removed_at` older than 48h → `wp_trash_post()` (skips reserved)
2. Trashed posts older than 30 days → `wp_delete_post(force: true)` (skips reserved)
3. Orphaned image attachments → delete

## Key Conventions

### Source Tracking
Every imported listing MUST have these meta fields:
- `_asiaauto_source` — `dongchedi` or `che168`
- `_asiaauto_inner_id` — original listing ID from the platform
- `_asiaauto_url` — original URL on the source platform
- `_asiaauto_last_sync` — ISO datetime of last sync
- `_asiaauto_original_price` — raw CNY price from API
- `_asiaauto_images` — JSON array of original image URLs
- `_asiaauto_description_original` — Chinese description from API
- `_asiaauto_description_status` — `ok`, `failed`, or `empty`
- `_asiaauto_reservation_status` — `reserved`, `in_transit`, `on_lot`, or empty
- `_asiaauto_api_removed` — `true` if listing disappeared from API but protected by reservation
- `_asiaauto_reservation_order_id` — linked order ID (set by Order module)

### Naming
- Plugin prefix: `asiaauto_`
- WP-CLI namespace: `wp asiaauto`
- Option keys: `asiaauto_last_change_id_dongchedi`, `asiaauto_last_change_id_che168`, `asiaauto_import_config`, `asiaauto_price_config`, `asiaauto_order_config`

### Error Handling
- Wrap all API calls in try/catch
- Log to `wp-content/plugins/asiaauto-sync/logs/asiaauto-sync.log`
- On API failure: retry 3x with exponential backoff
- Never halt full sync on single listing error

## WP-CLI Commands

```bash
wp asiaauto sync-filters --source=dongchedi    # Sync brands/models/enums from /filters
wp asiaauto import --source=dongchedi --limit=5 # Import listings (images + DeepL/Gemini + price)
wp asiaauto sync --source=dongchedi             # Ongoing sync via /changes
wp asiaauto cleanup [--dry-run]                 # Rotation: draft→trash→delete (skips reserved)
wp asiaauto status                              # Show stats + disk + price config
wp asiaauto check --inner-id=X --source=Y       # Quick listing check (WP-only, no API)
wp asiaauto inspect --inner-id=X --source=Y     # Deep inspect: API fetch + WP compare + gaps
wp asiaauto inspect --offer-url=<url>           # Inspect by Dongchedi URL (--url reserved by WP-CLI)
wp asiaauto inspect --inner-id=X --raw          # Inspect with full raw API JSON dump
wp asiaauto restore --inner-id=X                # Restore removed listing
wp asiaauto recalculate-prices [--dry-run]      # Bulk recalculate all prices (after config change)
wp asiaauto price-check --cny=80000             # Preview price calculation for CNY amount
wp asiaauto price-check --inner-id=X            # Show stored + current breakdown for listing
wp asiaauto purge-all --yes                     # Delete ALL listings + images (testing)
wp asiaauto purge-outdated --dry-run             # Preview: delete listings not matching import filters
wp asiaauto purge-outdated --yes                 # Execute purge (protects listings with active orders)
wp asiaauto download-missing-images [--limit=N]  # Download images for listings with empty gallery
wp asiaauto fix-titles [--dry-run] [--limit=N]   # Rebuild titles: "Mark Model Year Complectation"
wp asiaauto order-create --listing=X [--customer=Y] [--type=reserved|purchased|in_transit|on_lot]  # Create order
wp asiaauto order-status <order_id> --status=<status> [--note="..."]  # Change order status
wp asiaauto order-list [--status=<status>] [--limit=N]               # List orders with filters
```

## Initial Import Results (2026-03-26, COMPLETED)

Full docelowy import from Dongchedi completed after ~24h (2 sessions with restart).
Post-cleanup (2026-03-27): removed 78 duplicate posts + 891 orphaned attachments.

| Metric              | Value       |
|---------------------|-------------|
| Listings imported   | 3,518       |
| After cleanup       | ~3,696 (publish, after sync added more + dupes removed) |
| Images downloaded   | 188,076     |
| Disk usage (images) | 4 GB        |
| Pages scanned       | ~10,234     |
| Total API offers    | ~204,680    |
| Match rate (filters)| ~1%         |
| Descriptions OK     | ~2,543      |
| Descriptions failed | ~975 (being re-translated via Gemini) |

### Import config used

```json
{
  "dongchedi": {
    "enabled": true,
    "marks": ["AITO","BYD","Changan","Chery","Denza","Dongfeng","Exeed","Geely",
              "Hongqi","Jetour","Leapmotor","Li Auto","NIO","Volvo","Voyah",
              "Yangwang","Zeekr"],
    "year_from": 2023, "year_to": null,
    "km_from": 1000, "km_to": 50000,
    "price_from": 150000, "price_to": null
  }
}
```

### Post-import fixes applied

1. **Title dedup** — API returns `model` with `mark` prefix (e.g. mark=`Volvo`, model=`Volvo S90`).
   Importer now strips mark from model. Bulk SQL fixed 3,173 existing titles.
2. **Fuel taxonomy** — renamed slugs to Polish: `erev` → `Elektryczny z range extenderem (EREV)`, etc.
3. **Description re-translate** — DeepL quota exhausted (500k/500k). Switched to Gemini Flash 2.5.
   Bulk re-translation of ~1,081 failed descriptions via Gemini (in progress).
4. **Taxonomy duplicates (v0.11.0)** — importer generował polskie slugi, sync-filters angielskie.
   Root cause fixed: `$api_value` parameter in `setTaxonomyAndMeta()`.
   Cleanup: 1099 postów, 30 termów, 4 taksonomie. Skrypt: `diag/fix-taxonomy-duplicates.php`.
5. **Chinese model names** — API zwraca chińskie nazwy modeli (~22 przypadków w mapie).
   Fix: `diag/fix-chinese-models.php` (22 renames, all applied 2026-04-02). 0 Chinese terms remain in `serie`.

## Translation Engine (v0.9.1)

Primary: **Google Gemini Flash 2.5** (pay-as-you-go, no quota limits).
Fallback: **DeepL** (500k chars/month free plan, resets 1st of each month).

Config in `wp-config.php`:
```php
define('ASIAAUTO_GEMINI_API_KEY', 'your-gemini-key');
define('ASIAAUTO_DEEPL_API_KEY', 'your-deepl-key');  // optional fallback
```

Flow: `translateDescription()` → tries Gemini first → on failure falls back to DeepL.
Gemini model: `gemini-2.5-flash`, temperature 0.1, prompt instructs ZH→PL with HTML preservation.

## Extra Prep Module (v0.9.0 — vehicle specifications)

The `extra_prep` field from auto-api.com contains ~200 keys of detailed vehicle
specification data in Chinese. These are **catalogue specs for the complectation**,
not inspection data for the specific car.

### What this means for accuracy

- **CERTAIN** (hardware specs): dimensions, engine, gearbox, suspension, brakes — identical
  for every car of the same complectation. Verified: 33 of 38 complectation groups had
  0 differences in extra_prep across multiple listings.
- **CERTAIN** (标配 = standard): ABS, airbags, central lock — standard equipment is guaranteed.
- **SKIPPED** (选配 = optional): air suspension, auto-park, etc. — unknown if this specific
  car has the option. Translator now skips these values (displays nothing instead of "Optional").
- Frontend should display: "Specyfikacja fabryczna konfiguracji: [complectation]"

### Display categories (19 groups)

basic, dimensions, engine, fuel, gearbox, suspension, wheels, safety, lights,
comfort, seats, mirrors, multimedia, parking, body, ev, adas, drive_modes, remote

### Translation stats (3,518 listings)

- ~95% of Chinese values translated to Polish
- ~830 key→label mappings, ~200 value translations
- Pattern-based: 马力→KM, 万→¥, 年或万公里→warranty, 色→colors, charging times
- Broken unicode fix in translator: WP strips backslashes from `\uXXXX` → `uXXXX`,
  translator fixes on read via `preg_replace_callback`

## auto-api.com API Reference

SDK: `autoapi/client` (PHP 8.1+, Guzzle 7)
GitHub: https://github.com/autoapicom/auto-api-php
Packagist: `composer require autoapi/client`

Config in wp-config.php:
```php
define('ASIAAUTO_API_KEY', 'ZHxJiftgezpRndYjr4QE');
define('ASIAAUTO_API_BASE_URL', 'https://api1.auto-api.com');
```

Wrapper: `class-asiaauto-api.php` adds retry logic (3x, exponential backoff).

### Client constructor

```php
new Client(string $apiKey, string $baseUrl = 'https://api1.auto-api.com', string $apiVersion = 'v2')
```

- Default API version: `v2`
- Timeout: 30s (Guzzle)
- All methods return associative arrays (decoded JSON), no DTO classes

### Authentication

| Request type | Method |
|---|---|
| GET endpoints (wszystkie poza getOfferByUrl) | `api_key` query parameter |
| POST `getOfferByUrl` | `x-api-key` header |

### Endpoints (verified 2026-03-28)

| Metoda PHP | HTTP | Endpoint | Status |
|---|---|---|---|
| `getFilters($source)` | GET | `api/v2/{source}/filters` | ✅ Works |
| `getOffers($source, $params)` | GET | `api/v2/{source}/offers` | ✅ Works |
| `getOffer($source, $innerId)` | GET | `api/v2/{source}/offer?inner_id=X` | ✅ Works |
| `getChangeId($source, $date)` | GET | `api/v2/{source}/change_id?date=X` | ✅ Works |
| `getChanges($source, $changeId)` | GET | `api/v2/{source}/changes?change_id=X` | ✅ Works |
| `getOfferByUrl($url)` | POST | `api/v1/offer/info` | ⚠️ 401 u nas |

### getFilters — dostępne filtry

```php
$filters = $client->getFilters('dongchedi');
// Zwraca: {mark: {BYD: [...models], NIO: [...]}, transmission_type: [...], body_type: [...], ...}
```

### getOffers — paginowany skan ofert

```php
$response = $api->getOffers('dongchedi', ['page' => 1]);
// $response['result'] = array of {id, inner_id, change_type, created_at, data: {...}}
// $response['meta']   = {page, next_page}
```

Dostępne params filtrowania:

| Param | Typ | Opis |
|---|---|---|
| `page` | int | Numer strony (wymagany) |
| `brand` | string | Marka |
| `model` | string | Model |
| `configuration` | string | Konfiguracja |
| `complectation` | string | Wykończenie |
| `transmission` | string | Skrzynia biegów |
| `color` | string | Kolor |
| `body_type` | string | Typ nadwozia |
| `engine_type` | string | Typ silnika |
| `year_from` | int | Rok od |
| `year_to` | int | Rok do |
| `mileage_from` | int | Przebieg od (km) |
| `mileage_to` | int | Przebieg do (km) |
| `price_from` | int | Cena od |
| `price_to` | int | Cena do |

**Uwaga:** Dla Dongchedi filtry API-side nie testowane — filtrujemy po stronie klienta przez `isAllowedByConfig()`. Wyniki NIE są posortowane — losowa kolejność. Pełny skan = ~10k stron.

### getOffer — pojedyncza oferta (CRITICAL dla sync)

```php
$offer = $api->getOffer('dongchedi', '23212341');
// Zwraca: {id, inner_id, url, mark, model, ..., extra_prep, images, description}
// Struktura: $offer['data']['url'], $offer['data']['mark'] itd.
// PEŁNE dane — zawiera extra_prep i equipment (w przeciwieństwie do /changes added)
```

**Kluczowe:** gdy `/changes` zwraca `added`, wywołaj `getOffer(inner_id)` zanim zimportujesz — `/changes` nie zawiera `extra_prep` ani `equipment`.

### getChangeId — ID zmiany dla daty

```php
$changeId = $api->getChangeId('dongchedi', '2026-03-27');
// Zwraca: int (bezpośrednio, wyciągnięty z {change_id: 12345})
```

### getChanges — delta sync

```php
$response = $api->getChanges('dongchedi', $changeId);
// $response['result'] = array of {change_type, inner_id, data: {...}}
// $response['meta']   = {next_change_id}

// Iteracja przez kolejne batche:
$next = $response['meta']['next_change_id'];
$more = $api->getChanges('dongchedi', $next);
```

Typy zmian:

| `change_type` | `data` zawiera | Akcja |
|---|---|---|
| `added` | Częściowe pola — **BEZ extra_prep, BEZ equipment** | Wywołaj `getOffer(inner_id)` dla pełnych danych |
| `changed` | Zazwyczaj tylko `new_price` | Zaktualizuj cenę + download brakujących zdjęć |
| `removed` | Puste — tylko `inner_id` | Sprawdź rezerwację, potem draft lub flaga |

**Ważne:** `inner_id` jest na poziomie zmiany (`$change['inner_id']`), NIE wewnątrz `$change['data']`. Sync wstrzykuje `$data['inner_id'] = $change['inner_id']` przed przekazaniem do importera.

### getOfferByUrl — oferta po URL

```php
$info = $client->getOfferByUrl('https://www.encar.com/dc/dc_cardetailview.do?carid=40427050');
// POST api/v1/offer/info, auth: x-api-key header (różny od reszty!)
// U nas zwraca 401 — inna wersja API, inny mechanizm auth
```

### Struktura danych oferty (32 pola z getOffers/getOffer)

```
id, inner_id, url, mark, model, complectation, year, color, price, km_age,
body_type, engine_type, transmission_type, address, is_dealer, displacement,
city, title, owners_count, drive_type, equipment, horse_power, reg_date,
section, seller, seller_type, salon_id, region, description, created_at,
images, extra_prep, interior_color
```

Kluczowe pola:
- `extra_prep` — obiekt z ~200 kluczami specyfikacji pojazdu (wymiary, silnik, bezpieczeństwo…)
- `images` — string CSV z URL-ami (Dongchedi: webp z byteimg.com, wygasają po 6 dniach)
- `description` — chiński tekst HTML
- `equipment` — array stringów wyposażenia
- `section` — `new` lub `used` → mapowane na taxonomię `condition`
- `new_price` — pojawia się tylko w `/changes` type=`changed`
- `interior_color` — jeśli dostępny (mapowany na taxonomię `interior-color`)

### Mapowanie pól API → WP meta

| Pole API | WP meta key | Uwagi |
|---|---|---|
| `price` | `_asiaauto_original_price` | CNY z API, niemodyfikowane |
| `price` (przeliczone) | `price` | PLN via AsiaAuto_Price pipeline |
| `km_age` | `mileage` | km |
| `displacement` | `engine` | pojemność silnika |
| `fuel_consumption` | `fuel-consumption` | |
| `city` (PL) | `stm_car_location` | przetłumaczone na PL |
| `vin` | `vin_number` | |
| `reg_date` | `registration_date` | format d/m/Y |
| `horse_power` | `_asiaauto_horse_power` | KM |
| `owners_count` | `_asiaauto_owners_count` | liczba właścicieli |
| `complectation` | `_asiaauto_complectation` | Translated ZH→EN/PL, original in `_asiaauto_complectation_original` |
| `is_dealer` | `_asiaauto_is_dealer` | bool |
| `seller` | `_asiaauto_seller` | |
| `salon_id` | `_asiaauto_salon_id` | |
| `region` | `_asiaauto_region` | |
| `city` (surowe ZH) | `_asiaauto_city` | oryginał chiński |
| `address` | `_asiaauto_address` | |
| `extra_prep` | `_asiaauto_extra_prep` | JSON |
| `url` | `_asiaauto_url` | link do źródła |

### Error handling (wyjątki SDK)

```php
use AutoApi\Exception\AuthException;  // extends ApiException
use AutoApi\Exception\ApiException;

try {
    $offers = $client->getOffers('dongchedi', ['page' => 1]);
} catch (AuthException $e) {
    // 401 lub 403 — nieprawidłowy klucz API
    $e->getStatusCode();   // int
    $e->getMessage();      // string
} catch (ApiException $e) {
    // Pozostałe błędy HTTP + invalid JSON
    $e->getStatusCode();   // int
    $e->getResponseBody(); // array|null — zdekodowane JSON z odpowiedzi
}
```

- `AuthException` extends `ApiException` — możesz catchować oba jako `ApiException`
- Invalid JSON response → rzuca `ApiException('Invalid JSON response: ...')`
- Błędy sieciowe bez response → `ApiException` z `statusCode=0`

### Nasza warstwa retry (class-asiaauto-api.php)

- 3 próby, exponential backoff (500ms → 1000ms → 2000ms)
- `AuthException` (401/403) — nie retry (błąd klucza, bez sensu ponawiać)
- `ApiException` — retry z backoff
- Inne wyjątki (`\Throwable`) — log error, return null (bez retry)
- Po 3 nieudanych próbach: log error, return null

### Dane niedostępne w API

Raporty inspekcji pojazdu, historia wypadków/zalania/pożaru, stan lakieru,
szczegóły licencji dealera — niedostępne przez auto-api.com.

### Wspierane źródła

| Source | Platforma | Region | Używamy |
|---|---|---|---|
| `dongchedi` | dongchedi.com | Chiny | ✅ tak |
| `che168` | che168.com | Chiny | 🔲 planowane |
| `encar` | encar.com | Korea Płd. | nie |
| `mobilede` | mobile.de | Niemcy | nie |
| `autoscout24` | autoscout24.com | Europa | nie |
| `guazi` | guazi.com | Chiny | nie |
| `dubicars` | dubicars.com | UAE | nie |
| `dubizzle` | dubizzle.com | UAE | nie |


## Elementor Tips (discovered 2026-03-26)

- **Post Terms dropdown empty**: In Elementor Theme Builder Single template, set
  Preview Settings → "Preview Dynamic Content as: listings" → pick a specific post.
  Without this, taxonomy dropdowns are empty.
- **Fuel display**: Use "Post Terms" dynamic tag (taxonomy: fuel), NOT "Post Custom Field"
  (meta `fuel` stores slug like `erev`, taxonomy term has Polish name).

## TODO (not yet implemented)

### Frontend build order
1. ✅ Header (Elementor Theme Builder — sticky, logo+slogan, menu, phone+CTA)
2. ✅ Footer (Elementor Theme Builder — 3 kolumny, navy bg, copyright)
3. ✅ Inventory page (class-asiaauto-inventory.php — własny shortcode, REST API, karty, filtry)
4. ✅ Single listing template ([asiaauto_single] — gallery + specs + CTA + Schema.org Car)
5. ✅ Homepage ([asiaauto_homepage] — search + stats + latest + marki + nadwozia + napęd + USP + CTA + Schema.org)
6. 🔲 Archive/taxonomy pages (brand pages, etc.)
7. ✅ Responsive design (mobile CSS fixes v0.25.0 — safe-area-inset, admin order table)
8. ✅ Frontend wizard `[asiaauto_order_wizard]` — DONE (v0.13.0, page /zamow/)
9. ✅ Email notifications — DONE (v0.13.0, plain text, magic links)
10. ✅ Contract PDF — DONE (v0.13.0, mPDF, auto-generated at umowa_gotowa)
11. 🔲 Panel klienta /moje-zamowienia/ (lista zamówień, statusy, dane)
12. ✅ Badge "Zarezerwowane" na listingu — DONE (inventory + single)
13. ✅ Podstrona "W drodze" (`/w-drodze/`) — DONE (v0.20.0)
14. ✅ Podstrona "W Rzeszowie" (`/w-rzeszowie/`) — DONE (v0.20.0)

### v0.9.3 — Slug, data aktualizacji, 404 obsługa (planned)

**A. Slug z post_id** ✅ DONE — format `{marka}-{model}-{rok}-{post_id}`, fix-slugs CLI, ~3700 postów zmigrowanych.

**B. Shortcode [asiaauto_updated]** ✅ DONE — zwraca datę ostatniej sync (`_asiaauto_last_sync` via `strtotime`), fallback na `post_date`. Format: `d.m.Y`. Brak stylów — prefix/suffix w Elementorze.

**C. Custom 404 dla usuniętych ogłoszeń**

Gdy ktoś wchodzi na URL ogłoszenia które jest w trash/draft/usunięte:
- Zamiast standardowego WP 404: "To ogłoszenie nie jest już dostępne"
- Pod spodem: listing grid filtrowany po tej samej marce i modelu
- Przycisk: "Zobacz wszystkie [Marka] [Model]" → inventory z prefiltrem

Implementacja: hook `template_redirect` w child theme `functions.php`.
Wyciąga markę/model ze slug-a, szuka pasujących ogłoszeń.
Ewentualnie: sprawdza `_asiaauto_inner_id` z trashed postów.

**D. Weryfikacja rotation/cleanup**

Cleanup cron (daily 3:00): draft → trash (48h) → delete (30d).
Sprawdzić: czy cleanup faktycznie przetwarza stare drafty.
Jeśli drafty się piętrzą > 48h — bug w cleanup lub cron nie odpala.

### Homepage blueprint (confirmed 2026-03-22)

Design reference: mobile.de homepage. 6 sekcji:

**Sekcja 1 — Hero + Wyszukiwarka + Filtry**
- Tło: navy (#1B2A4A), pełna szerokość
- H1: "Znajdź auto z Chin" (32px, biały)
- Podtytuł: "Tysiące sprawdzonych ofert w najlepszych cenach" (15px, #B0BEC5)
- Search bar: białe pole, placeholder "BYD Han EV do 80 000 zł...", przycisk Szukaj
- Panel filtrów (biała karta): Marka ▾, Model ▾, Rok od ▾, Cena do ▾, Paliwo ▾
- Pod filtrami: "Więcej filtrów" link + "Szukaj X ofert →" przycisk Akcent

**Sekcja 2 — Najnowsze oferty**
- H2: "Najnowsze oferty"
- Elementor Loop Grid, 4 kolumny, query: listings, orderby date DESC, limit 4-8
- Przycisk: "Zobacz wszystkie oferty →" → /oferta/

**Sekcja 3 — Popularne kategorie**
- 5 okrągłych ikon: Elektryczne, SUV, Sedany, Hybrydy, Premium
- Każda → link do Inventory z prefiltrem

**Sekcja 4 — Popularne marki**
- Grid 6x2: BYD, Geely, Changan, NIO, Li Auto, Xpeng, Chery, Haval, GAC, JAC, Hongqi, Zeekr
- Każda → /marka/{slug}/

**Sekcja 5 — Dlaczego AsiaAuto?**
- 3 kolumny z ikonami:
  - Sprawdzone oferty — "Weryfikujemy każde auto przed publikacją"
  - Najlepsze ceny — "Import bez pośredników, oszczędzasz do 40%"
  - Pełna obsługa — "Transport, homologacja, rejestracja w PL"

**Sekcja 6 — CTA końcowe**
- "Nie znalazłeś? Skontaktuj się z nami" + przycisk kontakt / telefon

### Bulk price recalculation
- ✅ DONE (v0.7.0→v0.18.0) — `wp asiaauto recalculate-prices`, full cost pipeline with 9 steps (incl. VAT + akcyza per fuel type)
- ✅ Admin UI with preview table (Narzędzia → AsiaAuto Import → Kalkulator cen) — 9 fields + akcyza table + VAT
- ✅ Per-listing breakdown stored in `_asiaauto_price_breakdown` (JSON with fuel_type, akcyza, VAT)
- ✅ Batch fuel_type lookup in bulkRecalculate() — 1 SQL instead of N

### CSV image refresh
- `active_offer.csv` from autobase-perez.auto-api.com has daily refreshed image URLs
- Host: autobase-perez.auto-api.com, auth: Basic (pineda:ZHxJiftgezpRndYjr4QE)

### Bulk translate retry
- Command `wp asiaauto translate` to retry failed descriptions
- Use batch DeepL API (up to 50 per call)

### Reservation / Order Module

**Etap 1 — Backend: ✅ DONE (v0.8.0)**
**Etap 2 — REST API + admin extensions: ✅ DONE (v0.12.0)**
**Etap 3 — Frontend wizard + contract + auth + email: ✅ DONE (v0.13.0)**
**Etap 4 — Polish: ✅ DONE (v0.14.0)** — PDF deferred cron, inventory CTA badge, admin attachments view, PDF regenerate
**Etap 5 — PARTIAL (v0.20.0):** ✅ "W drodze"/"W Rzeszowie" podstrony | 🔲 panel klienta, email HTML templates

### Cron safety
- ✅ DONE (v0.9.2) — Transient lock prevents parallel sync runs
- ✅ Cron enabled: `asiaauto_sync_changes` every 15 min, `asiaauto_daily_cleanup` daily 03:00
- ✅ `findByInnerId()` searches all statuses except trash (prevents dupes)
- ✅ `inner_id` injected from change level into data before import
- ✅ `require_once file.php` + `image.php` in cron context (media download fix)

## Inventory Module (v0.10.0, subpages v0.20.0 — DONE)

Self-contained listing page. Custom shortcode + REST API, no external filter plugin dependency.

### Wdrożenie

Main inventory: `[asiaauto_inventory]` na stronie `/samochody/` (pełne filtry + sidebar + search).
Subpages (v0.20.0):
- `/w-drodze/` → `[asiaauto_inventory reservation_status="in_transit"]`
- `/w-rzeszowie/` → `[asiaauto_inventory reservation_status="on_lot"]`

Subpage layout: bez sidebara, bez searchbara, bez mobile drawer. Tylko toolbar (count+sort) + grid + pagination.
CSS klasa `aa-inv--no-sidebar` → max-width 960px, toolbar widoczny na mobile.
Karty na subpages: bez reservation badge, bez przycisku Zamów — tylko "Szczegóły".
Empty state: dedykowane komunikaty + CTA "Zobacz dostępne samochody →" → `/samochody/`.
Shortcode ładuje CSS + JS tylko na tej stronie (conditional enqueue).

### REST API

| Endpoint | Metoda | Opis |
|---|---|---|
| `/wp-json/asiaauto/v1/listings` | GET | AJAX grid — zwraca HTML kart + total + pages |
| `/wp-json/asiaauto/v1/models` | GET | Dynamiczne modele dla wybranych marek (`?make=byd,nio`) |
| `/wp-json/asiaauto/v1/filter-counts` | GET | Faceted counts — countery termów per taksonomia z uwzgl. aktywnych filtrów (v0.10.3) |
| `/wp-json/asiaauto/v1/search-suggestions` | GET | Autocomplete — `?q=xxx`, min 2 znaki, max 8 wyników z title/price/thumb/url (v0.10.3) |

Parametry `/listings` (query string):

| Param URL | Typ | Opis |
|---|---|---|
| `marka` | string (CSV) | Slug(i) taksonomii `make` |
| `model` | string (CSV) | Slug(i) taksonomii `serie` |
| `paliwo` | string (CSV) | Slug(i) taksonomii `fuel` |
| `nadwozie` | string (CSV) | Slug(i) taksonomii `body` |
| `rocznik` | string (CSV) | Slug(i) taksonomii `ca-year` |
| `kolor` | string (CSV) | Slug(i) taksonomii `exterior-color` |
| `cena_min` | int | Minimalna cena PLN (meta `price`) |
| `cena_max` | int | Maksymalna cena PLN (meta `price`) |
| `sort` | string | `date_desc` / `price_asc` / `price_desc` |
| `strona` | int | Numer strony (domyślnie 1) |

### Filtry sidebar

- **Marka** — multi-select dropdown (checkbox)
- **Model** — multi-select, zablokowany dopóki nie wybrano marki; po wyborze marki ładuje modele via `/models` AJAX
- **Rocznik** — multi-select, malejąco
- **Rodzaj paliwa** — multi-select
- **Rodzaj nadwozia** — multi-select
- **Cena** — dwa inputy (min/max PLN), krok 5000 zł, debounce 600ms
- **Kolor** — koła kolorów (SVG circles z hex z `$colorHex`), multi-select
- **Dependent counts (v0.10.3):** po każdej zmianie filtra, countery `(N)` przy opcjach aktualizują się via `/filter-counts`. Opcje z 0 wyników dimowane (opacity 0.4), ale nadal klikalne.

### Wyszukiwarka (v0.10.3)

- `.aa-search` w `.aa-inv__main` (prawa kolumna, nad toolbarem — jak mobile.de)
- Debounce 300ms, min 2 znaki, dropdown z max 8 wynikami
- Każdy wynik: thumbnail 48×36 + tytuł + cena PLN
- Klik lub Enter → nawigacja do `/oferta/{slug}/`
- Keyboard: ↑↓ nawigacja, Enter potwierdza, Esc zamyka

### Karty listingów (SSR + AJAX)

`renderCard($post)` — publiczna statyczna metoda, reużywalna (np. homepage).

Elementy karty:
- Miniaturka `asiaauto-card` (350×250) z linkiem do `/oferta/{slug}/`
- Badge "Nowe" (amber #E8AC07, próg 24h) — priorytet nad reservation badge
- Reservation badges: zarezerwowane (red), w drodze (amber), na placu (green)
- Tytuł (marka + model) + "Dodano X temu" (prawy górny róg, `Europe/Warsaw`)
- Specs line: rok | przebieg km | pojemność (ukryta dla EV) | moc KM | 0-100s | paliwo short
- Highlighty (max 4): `getHighlights()` — automat z `extra_prep` + taksonomii
- Cena PLN (format z separatorem)
- Lokalizacja (meta `stm_car_location`)

### Highlights (algorytm, 15 kandydatów)

Priorytety z `extra_prep` + taksonomii, max 4 wyniki:

| Priorytet | Label | Źródło | Pokrycie |
|---|---|---|---|
| 1 | `Zasięg: X km` | `extra_prep.pure_electric_range` | EV only |
| 2 | `Ładowanie: Xmin` | `extra_prep.dc_charging_time` | EV |
| 3 | `Autopilot` | `extra_prep.intelligent_driving_assist` | 14% |
| 4 | `Panorama otwierana` | `extra_prep.skylight_type` = `可开启全景` | 35% |
| 5 | `Panorama dach` | `extra_prep.skylight_type` = `全景` | 50% |
| 6 | `Skóra` | `extra_prep.seat_material` = `真皮` | 58% |
| 7 | `0-100: X,X s` | `extra_prep.acceleration_time` < 4.0 | 8% |
| 8 | `Tylny napęd` | taxonomy `drive` = `rwd` | 10% |
| 9 | `1 właściciel` | meta `_asiaauto_owners_count` = 1 | 74% |
| 10 | `4x4` | taxonomy `drive` = `awd` | 80% |
| 11 | `Elektr. klapa` | `extra_prep.electric_back_door` | 84% |
| 12 | `Wykrywanie zmęczenia` | `extra_prep.fatigue_driving_warning` | 85% |
| 13 | `Asyst. pasa ruchu` | `extra_prep.lane_keeping_assist` | 87% |
| 14 | `AEB` | `extra_prep.active_brake` | 87% |
| 15 | `Auto parkowanie` | `extra_prep.auto_park` | 88% |

### JS (asiaauto-inventory.js)

Vanilla JS, zero zależności. Globalna zmienna `aaInventory` (wp_localize_script):
```js
{
  restUrl: '/wp-json/asiaauto/v1/',
  nonce: '...',
  phone: '+48 ...',
  colorHex: { white: '#FFFFFF', ... }
}
```

Moduły JS:
- `readUrlState()` / `pushUrlState()` — synchronizacja URL (`?marka=byd&sort=price_asc`) przez `pushState`
- `initDropdowns()` — multi-select z checkboxami, inline search (>8 opcji), klik poza = zamknięcie
- `initColorFilter()` — kliknięcie kola koloru toggle `is-active`
- `initPriceRange()` — debounce 600ms, formatowanie z separatorem tysięcy
- `initSort()` — `<select>` zmiana → fetchListings()
- `initPagination()` — delegacja na `.aa-pagination__btn[data-page]`
- `initMobileDrawer()` — hamburger button + overlay + `aa-inv__sidebar--open`
- `fetchListings()` — AbortController (cancel in-flight), loading overlay, inject HTML + pagination
- `fetchModels(makeSlugs)` — po zmianie marki, odbudowuje dropdown modeli

### CSS (asiaauto-inventory.css)

Scoped do `.aa-inv`. Zawiera **Elementor Nuclear Reset** — `!important` override dla button, input, select, a, h2, h3 — bez tego Elementor Pro nadpisuje style formularzy.

Breakpoint mobilny `@media (max-width: 768px)`:
- sidebar ukryty → drawer (slide-in z lewej)
- toolbar zamieniony na mobile bar z przyciskiem "Filtry" + badge z liczbą aktywnych filtrów
- siatka kart → 1 kolumna

### Pagination

Algorytm `renderPagination()`: zawsze pokazuje stronę 1 i ostatnią, okno ±2 wokół aktualnej, separator `…` przy lukach.

## Changes (v0.14.0, 2026-04-02) — Etap 4: PDF deferred, inventory CTA, admin attachments

- ✅ Contract PDF deferred generation: `wp_schedule_single_event` in REST/web context to avoid
  memory/timeout on shared hosting during double transition (dane_klienta → umowa_gotowa).
  CLI context generates immediately. Fallback: if cron scheduling fails, tries inline.
- ✅ `AsiaAuto_Contract::regenerate($order_id)` — deletes old attachment, generates fresh PDF.
  Used by admin "Regeneruj PDF" button.
- ✅ WP cron action: `asiaauto_generate_contract_pdf` — double-checks status and existing PDF.
- ✅ Inventory card CTA: reserved listings show disabled grey badge ("Zarezerwowane"/"W drodze"/"Na placu"),
  available listings show "Zamów" button → `/zamow/?listing_id={id}`. Old dead link `permalink#rezerwacja` removed.
- ✅ CSS: `.aa-card__btn--disabled` style (grey bg, no pointer events, opacity).
- ✅ Admin order card "Umowa i dokumenty" section:
  - Numer umowy displayed at top
  - Contract PDF: "Pobierz" + "Regeneruj" buttons (or "Wygeneruj PDF" if missing)
  - Signed contract: filename + MIME type + download link
  - Payment proof: filename + MIME type + download link (new row, previously missing)
- ✅ Admin action handlers: `generate_contract`, `regenerate_contract` with success/error feedback.
- ✅ Admin CSS extracted to `assets/css/asiaauto-order-admin.css` (was inline) — responsive breakpoints for tablet/mobile/small phone.

### Chinese characters fix (v0.14.0, 2026-04-02)

Full audit and fix of Chinese characters across all taxonomies, titles, and extra_prep values.

**Titles (51 → 0 Chinese):**
- `translations-complectations.php`: +18 terms (16 complectation + `激光` LiDAR + `卓越` Excellence)
- `fix-chinese-models.php`: +4 model renames (`享界S9T`→S9T, `钛7 PHEV`→Tai 7 PHEV, `领克10 EM-P`→10 EM-P, `风云T11`→T11)
- `wp asiaauto fix-titles`: 48+9 titles rebuilt
- `fix-chinese-models.php APPLY=1`: 21 terms renamed, 45 titles fixed

**Extra prep (frontend visible):**
- `translations-extra-prep.php`: +15 battery brands (CALB, CATL Jiangsu, CATL-Geely, Welion, Sunwoda, Gotion, EVE Power, Farasis, Welion Tech, Zenergy, CATL Fuding, CATL Jiaocheng, Yaoneng, Zhipeng, Jike Energy)
- `translations-extra-prep.php`: +3 electric layout (`后置`→Tył, `前置`→Przód, `中置`→Środek)
- `translations-extra-prep.php`: +2 body struct (`5门5座旅行车`→kombi, `5门4座旅行车`→kombi)
- `class-asiaauto-translator.php`: fix standalone `快充X小时` pattern (was only combined 快充+慢充)

## Changes (v0.12.0, 2026-04-01) — Order module: statuses, REST API, customer, contract

- ✅ 16 order statuses (was 12): added zakupione, transport_morski, odprawa_celna, transport_krajowy, formalnosci
- ✅ SHIPPING_STATUSES constant for admin aggregate tile "W realizacji"
- ✅ REST API: 6 endpoints under `asiaauto/v1` for frontend order wizard
  - `GET /listing/{id}/reserve` — availability + price + deposit info
  - `POST /order/start` — register customer + create order (honeypot + rate limit)
  - `GET /order/{id}` — full wizard state with step mapping
  - `POST /order/{id}/billing` — partial update of customer billing data
  - `POST /order/{id}/terms` — 5 required checkbox acceptance
  - `GET /order/{id}/transfer` — bank transfer details
- ✅ Customer management: findOrCreateCustomer, saveCustomerData (partial update, validation), isCustomerDataComplete
- ✅ 12 billing fields with validation (PESEL 11 digits, NIP 10, postcode XX-XXX, phone, email, document type)
- ✅ Contract number auto-generation: AA/YYYY/NNNN at `umowa_gotowa` transition (atomic counter in wp_options)
- ✅ Delivery estimate: `_order_delivery_estimate` meta, `default_delivery_days` config
- ✅ Sync guard: `class-asiaauto-sync.php` skips `updateListing()` for reserved listings
- ✅ Admin: kafelek "W realizacji" (aggregate), filtr `_shipping`, typ rezerwacji "Zakupione"
- ✅ CLI: `order-create`, `order-status`, `order-list` commands
- ✅ `getOrders()` accepts array of statuses for multi-filter
- ✅ Hook: `asiaauto_order_started` (fires after frontend order creation)
- ✅ Full end-to-end flow tested via CLI and REST API

## Changes (v0.11.0, 2026-04-01) — Taxonomy dedup, importer fix, diag tools

### Taxonomy duplicate fix (root cause + cleanup)

**Root cause:** `setTaxonomyAndMeta()` w importerze generował slug z polskiej nazwy
(np. `Benzyna` → `benzyna`), podczas gdy `sync-filters` tworzył termy z angielskim
slugiem z API (np. `petrol`). Skutek: duplikaty w fuel, drive, transmission, exterior-color.

**Fix — importer (`class-asiaauto-importer.php`):**
- `setTaxonomyAndMeta()` — nowy parametr `$api_value` (opcjonalny)
- Gdy podany: slug z `sanitize_title($api_value)` (kanoniczny, jak sync-filters)
- Gdy brak (make/model/year): nadal `slugify()` z display name
- `setTaxonomies()` — wszystkie tłumaczone taksonomie przekazują `$data['engine_type']`,
  `$data['color']`, `$data['drive_type']` itd. jako `$api_value`

**Cleanup (jednorazowy, wykonany 2026-04-01):**
- 1099 postów przeniesionych ze złych termów na kanoniczne
- 1119 post meta zaktualizowanych
- 30 duplikatów termów usunięte
- Taksonomie: fuel (4 merges), drive (3), transmission (4 + 2 chińskie), exterior-color (12), body (1)
- Skrypt: `diag/fix-taxonomy-duplicates.php`

### Li Auto i6 slug collision fix

**Problem:** API zwraca `model=理想i6`. `slugify()` stripuje chińskie znaki → slug `i6`.
Istniejący term `荣威i6经典` (Roewe) ma ten sam slug → kolizja.

**Fix (jednorazowy):** 4 posty przeniesione z `i6` → `li-auto-i6`, tytuły poprawione.

**TODO:** `translations-models.php` — mapa chińskich nazw modeli na angielskie.
Importer sprawdza mapę PRZED slugify. Zapobiega nowym kolizjom.
Znanych 12 przypadków (Li Auto i6, Voyah Taishan, Exeed Lanyue C-DM, Volvo XC70,
BYD Sea Lion 07DM, Hongqi HS6 PHEV, Hongqi Jinkuihua Guoya/Guoyue, Denza N8/N8L DM,
Yangwang U8L). Lista w `diag/fix-chinese-models.php`.

### Extra prep labels & value translations

- ✅ 33 brakujących labeli dodanych do `translations-extra-prep.php`
  - Skrzynia: `gear_shift_mode_2`, `gear_shift_mode_6`, `engine_layout_form`
  - Komfort: `car_fragrance_device`, `car_fridge_feature_1/2/3`, `multilayer_soundproof_glass`
  - Fotele: `zero_gravity_seat`, `second_row_seat_*` (electrical, back_and_forth, memory),
    `vertical_move_second_row`, `second_independent_seat`, `third_row_seat_count/heating`,
    `third_row_electric_adjustment`, `layout_seat_6`, `seat_cork_style/1`,
    `main/vice_drive_window_sunshade_mirror_1`
  - Lusterka: `external_mirror_auto_flip/fold`, `exter_mirror_functional`
  - Multimedia: `lcd_dashboard_size_12.3`, `mobile_system_1`, `rear_lcd_screen`,
    `rear_entertainment_screen_resolution`, `copilot_screen_resolution`,
    `vice_screen_size_15.4`, `seat_speakers`, `hud_size_29`
- ✅ 15+ nowych tłumaczeń wartości:
  - Skrzynie: `混合动力专用变速箱(DHT)`, `1挡DHT`
  - Silniki: `前感应/异步后永磁/同步`
  - Baterie: `弗迪` (FinDreams), `比亚迪`, `欣旺达/宁德时代`
  - 4WD: `全时四驱`, `适时四驱`, `多片离合器式中央差速器`
  - Inne: `米勒循环`, `可穿戴钥匙`, `前排4个`, `5门6座MPV` etc.

### Diagnostic & maintenance tools (`diag/` directory)

Nowy katalog `diag/` w pluginie z uporządkowanymi skryptami.
Zastępuje 30 luźnych skryptów w `/public_html/diag/` (do usunięcia z serwera).

**Diagnostyczne (read-only):**
- `check-translations.php` — brakujące labele + niekategoryzowane klucze + nieprzetłumaczone wartości
- `check-chinese-models.php` — chińskie nazwy w taksonomii + duplikaty termów
- `check-data-quality.php` — slugi, EV power, extra_prep, opisy, spójność meta↔taksonomia

**Maintenance (env `APPLY=1`):**
- `fix-taxonomy-duplicates.php` — scalanie duplikatów (mapa wrong→correct slugs)
- `fix-chinese-models.php` — rename chińskich modeli + merge + fix tytułów
- `retranslate-descriptions.php` — ponowne tłumaczenie failed opisów (Gemini)
- `backfill-extra-prep.php` — uzupełnianie extra_prep z API
- `cleanup-duplicates.php` — usuwanie duplikatów inner_id

**Workflow po problemach:**
```bash
wp eval-file diag/check-chinese-models.php        # 1. diagnoza
wp eval-file diag/check-translations.php           # 2. tłumaczenia
APPLY=1 wp eval-file diag/fix-chinese-models.php   # 3. fix (jeśli potrzebny)
wp eval-file diag/check-data-quality.php           # 4. weryfikacja
```

## Changes (v0.10.3, 2026-03-31) — Inventory v2

### Dependent filter counts (faceted search)
- ✅ REST `GET /wp-json/asiaauto/v1/filter-counts` — zwraca countery per taksonomia z uwzględnieniem aktywnych filtrów
- ✅ `getFilteredTermCounts()` — SQL z dynamicznymi JOINami, pomija self-taxonomy (faceted pattern)
- ✅ Bind order: `$joinBind` (slugi) → `[$taxonomy]` → `$whereBind` (ceny) — prawidłowa kolejność dla `$wpdb->prepare()`
- ✅ JS `fetchFilterCounts()` + `updateFilterCounts()` — aktualizacja `(count)` obok checkboxów po każdym AJAX fetch
- ✅ CSS: `.aa-filter__option--empty` (opacity 0.4), `.aa-color--empty` (opacity 0.3) — dimowane zerowe opcje

### Search autocomplete
- ✅ REST `GET /wp-json/asiaauto/v1/search-suggestions?q=xxx` — WP_Query z `'s'`, max 8 wyników
- ✅ Zwraca: title, price (formatted PLN), thumbnail URL, permalink
- ✅ HTML: `.aa-search` w `.aa-inv__main` (prawa kolumna, nad toolbarem) — jak mobile.de
- ✅ JS `initSearch()` — debounce 300ms, dropdown, keyboard nav (↑↓ Enter Esc), clear button
- ✅ CSS: dropdown z shadow, hover/active state, thumb 48x36px

### Badge "Nowe" + relative time
- ✅ `isNewListing()` — próg `NEW_THRESHOLD_HOURS = 24` (const)
- ✅ `getRelativeTime()` — "Dodano X temu", timezone `Europe/Warsaw`, polska odmiana (minuty/godziny/dni/miesiące)
- ✅ `renderCard()` — badge `aa-card__badge--new` (amber #E8AC07, star SVG), priorytet nad reservation badge
- ✅ `aa-card__header` — flex row: title (left) + "Dodano X temu" (right, desktop)
- ✅ Mobile: header stacks vertical, time 11px

### Sticky layout
- ✅ Desktop sidebar: `position: sticky; top: 78px` (70px header + 8px gap), `max-height: calc(100vh - 86px)`
- ✅ Mobile bar: `position: sticky; top: 70px; z-index: 100` — pod headerem Elementor
- ✅ Mobile bar: przebudowany layout — row z Filtry + counter wyników + sort
- ✅ `data-header-h="70"` na root `.aa-inv` element

### CSS variables added
- `--aa-success: #38A169` (highlight checkmarks)
- `--aa-amber: #E8AC07` (badge "Nowe")

## Changes (v0.10.2, 2026-03-29)

### Complectation translation & title rebuild

- ✅ `data/translations-complectations.php` — ~35 Chinese segments → EN/PL
  - Segment-based: 增程→EREV, 四驱→4WD, 六座→6-osobowy, 旗舰→Flagship, etc.
  - Space-padded replacement + whitespace cleanup
  - 100% coverage of ~280 unique complectation values from Dongchedi
- ✅ `AsiaAuto_Translator::translateComplectation()` — new method
  - ASCII-only strings pass through unchanged (no-op)
  - Chinese segments replaced via `str_replace()` loop with space padding
  - Parens spacing cleanup, multiple space collapse
- ✅ Importer title format changed: `"Mark Model Year"` → `"Mark Model Year Complectation"`
  - e.g. `"Li Auto L9 2024"` → `"Li Auto L9 2024 Pro Edition"`
  - e.g. `"BYD Xia 2025"` → `"BYD Xia 2025 DM-i 180KM Version"`
- ✅ Importer stores translated complectation in `_asiaauto_complectation`
  - Original Chinese preserved in `_asiaauto_complectation_original` (if different)
- ✅ CLI: `wp asiaauto fix-titles [--dry-run] [--limit=N] [--source=<source>]`
  - Rebuilds all existing titles from taxonomy terms + translated complectation
  - Also retranslates Chinese complectation values in meta

### extra_prep Power Fields (documented 2026-03-29)

Analysis of 3 vehicles (PHEV, EREV, 48V/ICE) revealed 461 unique extra_prep keys.
Key discovery for MOC (power) display:

| Field | Key | BYD Xia PHEV | Li Auto L9 EREV | Hongqi H9 48V |
|---|---|---|---|---|
| Moc silnika spalinowego (KM) | `engine_max_horsepower` | 156 | 154 | 252 |
| Moc silnika spalinowego (kW) | `engine_max_power` | 115 | 113 | 185 |
| Łączna moc elektryczna (kW) | `total_electric_power` | 200 | 330 | — |
| Łączna moc elektryczna (KM) | `front_electric_max_horsepower` | 272 | 449 | — |
| Opis układu elektrycznego | `electric_description` | "PHEV 272 KM" | "EREV 449 KM" | — |
| Moc przedniego silnika el. (kW) | `front_electric_max_power` | 200 | 130 | — |
| Moc tylnego silnika el. (kW) | `rear_electric_max_power` | — | 200 | — |
| Łączny moment obrotowy el. (Nm) | `total_electric_torque` | 315 | 620 | — |
| 0-100 km/h (s) | `acceleration_time` | 8.5 | 5.3 | 7.8 |

**Kluczowe ustalenie:** Pole `horse_power` z top-level API = moc silnika spalinowego.
Dla PHEV/EREV/EV na frontendzie trzeba pokazywać `total_electric_power` / `front_electric_max_horsepower`
jako łączną moc układu. Hongqi H9 (48V) nie ma pól elektrycznych — `engine_max_horsepower` to właściwa moc.

### Single listing page (2026-03-29 → 2026-03-31)

**Obecny stan:** Elementor Theme Builder Single Post template z shortcodes.
Struktura: `main` → `left col` (gallery + key-spec×3) + `right col` (title, price, info, CTA).

**Znane problemy:**
- Moc (key-spec) — wyświetla `horse_power` (silnik spalinowy) zamiast łącznej mocy dla PHEV/EV
- Paliwo (key-spec) — "Hybryda plug-in (PHEV)" za długie na kafelek
- Brak complectation w tytule/podtytule ← FIXED w v0.10.2
- Przyciski CTA — brak logiki (Zadzwoń, WhatsApp z kontekstem URL, Zarezerwuj)

**Decyzja (2026-03-31):** Self-contained shortcode `[asiaauto_single]` — analogicznie jak Inventory.
Cała strona renderowana z PHP, zero zależności od Elementor Template. Elementor Single Post
template zastępowany prostą stroną z jednym shortcodem. Łatwiejsze utrzymanie, testowanie, iteracja.

**Planowana struktura (mobile.de reference):**
- Sekcja 1: Galeria (slider/lightbox) + key specs (6 kafelków: przebieg, moc, paliwo, skrzynia, rok, właściciele)
- Sekcja 2: Dane techniczne (zwijane, logiczna kolejność jak mobile.de Technische Daten)
- Sekcja 3: Wyposażenie (checkmarks, zwijane jak mobile.de Ausstattung)
- Sekcja 4: Opis pojazdu (przetłumaczony z ZH)
- Sidebar (sticky): tytuł + complectation, cena, info box, CTA (zarezerwuj/zadzwoń/whatsapp)
- WhatsApp: automatyczny tekst z URL aktualnego ogłoszenia
- Numer telefonu z `asiaauto_order_config`
- Mobile: sticky CTA bar na dole (Zadzwoń + WhatsApp / Zarezerwuj)
- Pliki: `class-asiaauto-single.php`, `asiaauto-single.css`, `asiaauto-single.js`

## Changes (v0.10.0/v0.10.1, 2026-03-28)

- ✅ `AsiaAuto_Inventory` — nowy plik `includes/class-asiaauto-inventory.php`
  - Shortcode `[asiaauto_inventory]` — pełna strona listingów z sidebar filtrami
  - REST `GET /wp-json/asiaauto/v1/listings` — AJAX grid (HTML + total + pages)
  - REST `GET /wp-json/asiaauto/v1/models?make=X,Y` — dynamiczne modele per marka
  - SSR: pierwsze renderowanie po stronie serwera (SEO + bookmarks)
  - `renderCard()` — publiczna statyczna metoda reużywalna
  - `renderHighlights()` — 15 kandydatów, max 4 per karta z `extra_prep` + taksonomii
- ✅ `assets/css/asiaauto-inventory.css` — layout, filtry, karty, mobile drawer, Elementor nuclear reset
- ✅ `assets/js/asiaauto-inventory.js` — AJAX, URL pushState, make→model dependency, mobile drawer
- ✅ `data/translations-drive-types.php` — napęd FWD/RWD/AWD → Polish (używane przez taxonomy + Inventory)



- ✅ Custom 404 dla usuniętych/sprzedanych ogłoszeń CPT `listings`
- ✅ `AsiaAuto_Redirects` — nowy plik `includes/class-asiaauto-redirects.php`
  - Hook `template_redirect` (priority 1) wykrywa 404 pod `/oferta/{slug}`
  - Regex `^(.+)-(20\d{2})-(\d+)$` wyciąga `post_id` ze sluga
  - `resolveMake(post_id)` — pobiera term `make` z posta (działa dla draft i trash)
  - Statyczne akcesory: `AsiaAuto_Redirects::getCurrentMake()`, `::isListing404()`
  - Nie renderuje nic — kontekst przekazywany do shortcode
- ✅ Shortcode `[asiaauto_404_listing]` dodany do `class-asiaauto-shortcodes.php`
  - Renderuje: ikona + komunikat + kafelki marek (hide_empty=true, sort count DESC) + CTA
  - Kafelki linkują do `/samochody/?marka={slug}`
  - Aktywna marka (rozpoznana z post_id) podświetlona na czerwono (`aa-404__make--active`)
  - Przycisk "Najnowsze oferty" → `/samochody/` (bez filtrów)
  - Style inline via `wp_add_inline_style()` — raz per request
  - Wstawiany w Elementor Theme Builder → 404 Page template

**Slug parsing — kluczowe ustalenia (2026-03-28):**
- 100% slugów (3947/3947 publish+draft) pasuje do wzorca `{prefix}-{20YY}-{post_id}` — zero wyjątków
- `serie` terms zawierają prefiks marki: `li-auto-l7`, `aito-m9`, `volvo-xc60`
- Chińskie znaki w slugach termów `make` (count=0) nie trafiają do listingów — `sanitize_title()` je usuwa
- Trash był pusty w momencie wdrożenia

## Changes (v0.9.3, 2026-03-28)

- ✅ Canonical listing slugs: `{marka}-{model}-{rok}-{post_id}` (e.g. `li-auto-l7-2023-151068`)
- ✅ `AsiaAuto_Importer::buildListingSlug()` — public static helper
- ✅ `importListing()` sets `post_name` via `wp_update_post()` after insert
- ✅ CLI: `wp asiaauto fix-slugs [--dry-run] [--source=<source>]` — migrated ~3700 existing posts
- ✅ Shortcode `[asiaauto_updated]` — data ostatniej sync (`_asiaauto_last_sync` ISO 8601 → `strtotime`), fallback na `post_date`, format `d.m.Y`

## Bugfixes & Hardening (v0.9.2, 2026-03-27)

### Duplicate listings fix

**Root cause:** Two overlapping issues caused 78 duplicate posts (65 unique inner_ids):
1. **Race condition** — API `/changes` and `/offers` return same `inner_id` on multiple pages
   (random order, no sorting). Cron or parallel CLI runs could create duplicates before
   first `wp_insert_post()` committed.
2. **`findByInnerId()` too narrow** — only searched `publish` and `draft` status.
   If a listing was in any other status, importer created a new one.

**Fix applied:**
- `findByInnerId()`: changed `IN ('publish','draft')` → `NOT IN ('trash','auto-draft')`
- **Transient lock** in `AsiaAuto_Sync::run()` — prevents parallel cron execution
- **Cleanup script** (`cleanup-duplicates.php`) — removed 78 dupes + 891 orphaned attachments

### Missing inner_id in sync

**Root cause:** API `/changes` endpoint returns `inner_id` at change level (`$change['inner_id']`),
not inside `$change['data']`. Sync passed only `$data` to importer, which logged
"Listing has no inner_id, skipping" for every change that had no inner_id in data.

**Fix:** Sync now injects `$data['inner_id'] = $change['inner_id']` before passing to importer.

### Cron image download fix

**Root cause:** `download_url()` and `wp_generate_attachment_metadata()` require
`wp-admin/includes/file.php` and `wp-admin/includes/image.php` which are NOT loaded
in cron context (only admin/CLI). Image downloads silently failed during cron sync.

**Fix:** Added `require_once` guards in `AsiaAuto_Media::downloadToTemp()` and
`downloadSingleImage()` for both files.

### Admin columns (CPT listings)

Added to `class-asiaauto-cpt.php`:
- **Inner ID** column — `_asiaauto_inner_id` (diagnostic)
- **Źródło** column — clickable link to `_asiaauto_url` (opens in new tab)
- **Ostatnia sync** column — `_asiaauto_last_sync` date + "X temu", sortable

### downloadMissingImages()

New method in `AsiaAuto_Importer` — called in `updateListing()` when gallery is empty.
Handles: failed initial downloads, images added later by seller.
Also available via CLI: `wp asiaauto download-missing-images`

## Slug Schema (v0.9.3, 2026-03-28)

### Format: `{marka}-{model}-{rok}-{post_id}`

Examples: `li-auto-l7-2023-151068`, `byd-han-2022-150034`, `xpeng-g9-2024-151200`

**Rules:**
- Uses WP `post_id` — NOT `inner_id` from Dongchedi/Che168 (source not exposed in URL)
- `sanitize_title()` handles lowercase, spacje→myślniki, diacritics, special chars
- `post_id` suffix guarantees uniqueness — WP never appends `-2`/`-16` again
- CPT rewrite base: `/oferta/` → full URL: `asiaauto.pl/oferta/li-auto-l7-2023-151068/`

**Implementation:**
- `AsiaAuto_Importer::buildListingSlug(mark, model, year, post_id)` — public static, callable from CLI
- Called in `importListing()` via `wp_update_post()` immediately after `wp_insert_post()` (ID needed first)
- Migration: `wp asiaauto fix-slugs [--dry-run] [--source=<source>]` — idempotent, skips already-correct slugs

## File Structure

```
wp-content/themes/asiaauto/              (child theme — "AsiaAuto by Auranet" v1.0.0)
├── style.css                            (Theme Name, Template: hello-elementor)
└── functions.php                        (parent style enqueue)

.htaccess                                        (AsiaAuto login hide: /biuro/ rewrite, wp-login.php + wp-admin block for non-auth)

wp-content/plugins/asiaauto-sync/        (v0.29.0-wip)
├── asiaauto-sync.php                    (plugin bootstrap + image sizes + cron)
├── composer.json                        (autoapi/client + mpdf/mpdf dependencies)
├── vendor/                              (composer autoload)
├── includes/
│   ├── class-asiaauto-cpt.php          (CPT + taxonomies + meta registration + admin columns)
│   ├── class-asiaauto-api.php           (API client wrapper with retry)
│   ├── class-asiaauto-price.php        (price calculator v0.28.0 — 9-step pipeline: Chiny+rejestracja → agencja celna → cło → transport z portu → akcyza per fuel → homologacja → prowizja → VAT 23% na końcu, lazy migration starych kluczy)
│   ├── class-asiaauto-importer.php      (listing import + images + translate + price + $api_value slug fix + $force bypass)
│   ├── class-asiaauto-sync.php          (changes sync logic + transient lock + inner_id injection + reservation guard)
│   ├── class-asiaauto-rotation.php      (lifecycle: sold→trash→delete + reservation protection)
│   ├── class-asiaauto-taxonomy.php      (filters → taxonomies sync)
│   ├── class-asiaauto-translator.php    (static translations + Gemini/DeepL API + extra_prep + complectation)
│   ├── class-asiaauto-media.php         (image download, SEO, WP attachments, cron-safe requires)
│   ├── class-asiaauto-admin.php         (import config + price config v0.28.0 z horyzontalną tabelą akcyz + sync toggle + image stats admin UI)
│   ├── class-asiaauto-admin-manual-import.php (manual import from Dongchedi: admin page + AJAX preview/import)
│   ├── class-asiaauto-listing-editor.php  (v0.29.0 task #8 Krok 2: metabox "Dane pojazdu" w głównej kolumnie edytora listings + row action "Duplikuj" + AJAX CIF breakdown preview; Gutenberg off dla listings; default taxonomy metaboxes removed from sidebar)
│   ├── class-asiaauto-gallery-metabox.php (v0.29.0 task #8 Krok 3: metabox galerii — drag&drop upload, auto WebP conversion, SEO naming spójne z importerem, reorder drag&drop, featured toggle, delete; 4 AJAX endpoints z raw meta ops dla _thumbnail_id; self-healing w renderze jeśli gallery[0] != _thumbnail_id)
│   ├── class-asiaauto-logger.php        (logging to file)
│   ├── class-asiaauto-order.php         (order CPT + meta + 11 statuses + CRUD + config + customer + auth + email via Content templates + suppressNextStatusEmail v0.22.0)
│   ├── class-asiaauto-order-content.php (editable email templates + status display: labels, colors, descriptions — wp_options based)
│   ├── class-asiaauto-order-admin.php   (order admin: list + card + config page 3 tabs: ustawienia/treści maili/statusy)
│   ├── class-asiaauto-order-api.php     (REST API: 9 endpoints — wizard + multi-upload + confirm-signed + delete-upload + login + honeypot + rate limit)
│   ├── class-asiaauto-order-wizard.php  (frontend wizard shortcode + magic link + login form + SSR)
│   ├── class-asiaauto-contract.php      (contract PDF v2 — mPDF, professional layout, no breakdown, deferred cron, admin regenerate)
│   ├── class-asiaauto-redirects.php     (custom 404 detection for /oferta/ — sets make context)
│   ├── class-asiaauto-shortcodes.php    (gallery, key specs, badges, price, specs, tech_specs, equipment, CTA, 404, order_cta, order_url)
│   ├── class-asiaauto-inventory.php     (inventory page v2: shortcode + REST + SSR + faceted counts + search + reserve/order CTA)
│   ├── class-asiaauto-single.php        (single listing page shortcode — tech specs + equipment rendering)
│   ├── class-asiaauto-security.php    (login URL filters + primaauto seller role + admin menu cleanup, v0.24.0)
│   ├── class-asiaauto-login.php       (branded /biuro/ login: text logo "AsiaAuto.pl" + amber dot, Inter, navy/red palette, self-contained CSS, hides language switcher + backtoblog + WP h1, generic error message, v0.27.0)
│   ├── class-asiaauto-homepage.php    (homepage shortcode [asiaauto_homepage] — hero search/stats/latest/makes/bodies/fuels/USP/CTA + AutoDealer + WebSite schema, v0.25.0)
│   └── class-asiaauto-contact.php     (contact shortcode [asiaauto_contact] — hero/dane kontaktowe/mapa Google iframe/dane firmy/bank/CTA + LocalBusiness schema, dane z asiaauto_order_config, v0.26.0)
├── cli/
│   └── class-asiaauto-cli.php           (WP-CLI commands incl. inspect + fix-slugs + fix-titles + purge-outdated + order-create/status/list)
├── data/
│   ├── translations-colors.php
│   ├── translations-body-types.php
│   ├── translations-transmissions.php
│   ├── translations-engine-types.php    (fuel types with Polish names + symbols)
│   ├── translations-drive-types.php     (FWD/RWD/AWD → Polish)
│   ├── translations-seller-types.php
│   ├── translations-cities.php
│   ├── translations-extra-prep.php      (200+ vehicle spec keys → 19 categories, ~900 labels+values, 15 battery brands)
│   ├── translations-extra-prep-values.php (extra_prep value translations for Single shortcode)
│   └── translations-complectations.php  (~55 ZH segments → EN/PL, incl. brand strip + LiDAR/Excellence)
├── diag/
│   ├── README.md                        (documentation + workflow)
│   ├── check-translations.php           (audit: missing labels, uncategorized keys, untranslated values)
│   ├── check-chinese-models.php         (detect: Chinese terms + taxonomy duplicates)
│   ├── check-data-quality.php           (audit: slugs, power, extra_prep, descriptions, meta consistency)
│   ├── fix-taxonomy-duplicates.php      (merge: Polish-slug → English-slug canonical, env APPLY=1)
│   ├── fix-chinese-models.php           (rename: Chinese model names → English, env APPLY=1)
│   ├── retranslate-descriptions.php     (retry: failed ZH→PL descriptions, env APPLY=1)
│   ├── backfill-extra-prep.php          (fill: missing extra_prep from API, env APPLY=1)
│   └── cleanup-duplicates.php           (dedup: same inner_id, keep best, env APPLY=1)
├── assets/
│   ├── css/
│   │   ├── asiaauto-inventory.css       (inventory v2 styles)
│   │   ├── asiaauto-single.css          (single listing page styles)
│   │   ├── asiaauto-order-wizard.css    (wizard: steps, uploads, login, responsive)
│   │   └── asiaauto-order-admin.css     (admin order pages: list, card, config — responsive)
│   └── js/
│       ├── asiaauto-inventory.js        (inventory JS: AJAX, faceted counts, search, URL pushState)
│       ├── asiaauto-single.js           (single: accordion, mobile sticky CTA)
│       └── asiaauto-order-wizard.js     (wizard JS: steps, multi-upload signed, file list, delete, confirm, login, tracking)
└── logs/
    └── .gitkeep
```

## Changes (v0.18.0, 2026-04-03) — VAT + Akcyza in Price Pipeline

Extended price calculator from 7 to 9 steps with import VAT and excise duty (akcyza).

### Pipeline changes

Old (v0.7.0–v0.17.0): CNY → bazowa → +transport → +cło → +transport krajowy → +rej+homol → +prowizja → zaokr
New (v0.18.0): CNY → bazowa → +transport → +cło → **+akcyza** → **+VAT** → +transport krajowy → +rej+homol → +prowizja → zaokr

### Akcyza (excise duty)

- Basis: wartość celna + cło (art. 104 ustawy o podatku akcyzowym)
- Rate depends on fuel type taxonomy slug
- Default rates: petrol/diesel 3.1%, HEV/MHEV 1.55%, PHEV/EV/EREV 0%
- Editable per fuel type from admin panel (table with listing counts)

### VAT (import VAT)

- Basis: wartość celna + cło + akcyza
- Default: 23%, configurable from admin
- Applied after cło + akcyza, before transport krajowy

### New config keys in `asiaauto_price_config`

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `akcyza_stawki` | array | {petrol: 3.1, ...} | Fuel slug → excise rate % |
| `vat_procent` | float | 23.0 | VAT rate % |

### New breakdown JSON fields

`podstawa_akcyzy`, `akcyza_procent`, `akcyza_kwota`, `fuel_type`, `podstawa_vat`, `vat_procent`, `vat_kwota`

### New methods in `AsiaAuto_Price`

- `getAkcyzaRate($fuel_type, ?$config)` — lookup rate for fuel slug
- `getFuelType($post_id)` — resolve fuel taxonomy slug for listing
- `batchGetFuelTypes($post_ids)` — single SQL query for multiple listings (used by bulkRecalculate)

### Modified method signatures

- `calculate($cny, ?$config, $fuel_type = '')` — new 3rd param
- `applyToListing($post_id, ?$cny, ?$fuel_type = null)` — new 3rd param (null = auto-resolve from taxonomy)

### Admin UI changes (`class-asiaauto-admin.php`)

- Steps renumbered 1–9 (was 1–7)
- New section "4. Akcyza" — table of rates per fuel type with listing count column
- New section "5. VAT" — percentage field with description
- Preview table: 2 new columns (+Akcyza with rate, +VAT), new "Paliwo" column, horizontal scroll wrapper

### Backward compatibility

- `applyToListing()` without fuel_type → auto-resolves from taxonomy (zero changes needed in importer/sync)
- `calculate()` without fuel_type → 0% akcyza (safe fallback for CLI `price-check --cny=X`)
- Old breakdown JSON fields unchanged — new fields added alongside existing ones
- Existing `_asiaauto_price_breakdown` values still readable (missing new fields handled gracefully)

### TODO (follow-up, non-critical)

- CLI `price_check --inner-id=X`: pass fuel_type to fresh calculation (currently uses fallback 0%)
- CLI `recalculate-prices`: extend config log with VAT + akcyza info
- CLI `status`: show VAT + akcyza in config summary

## Changes (v0.17.0, 2026-04-03) — Order Content Management

Editable email templates and status display configuration from admin panel.

### New class: `AsiaAuto_Order_Content`

File: `includes/class-asiaauto-order-content.php`
Manages editable content for order module — email templates + status labels/colors/descriptions.
All settings in `wp_options` with hardcoded defaults as fallback.

### Email templates (17 templates)

| Key | When sent | Recipient |
|-----|-----------|-----------|
| `welcome` | New account | Customer |
| `order_started_admin` | New order | Admin |
| `order_started_customer` | New order | Customer |
| `status_changed_admin` | Any status change | Admin |
| `status_{16_statuses}` | Per-status change | Customer |

Each template: `subject` + `body` with placeholders (`{customer_name}`, `{order_id}`, etc.).
Stored in `wp_options` key `asiaauto_order_email_templates` (JSON).

### Status display (16 statuses)

Editable per-status: `label` (name shown in UI), `color` (hex for badge), `description` (client-facing, for wizard).
Stored in `wp_options` key `asiaauto_order_status_display` (JSON).
`AsiaAuto_Order::STATUSES` and `STATUS_COLORS` constants remain as fallback defaults.

### Admin UI — config page tabs

Narzędzia → AsiaAuto Zamówienia now has 3 tabs:
1. **Ustawienia** — deposit, company data, bank, notifications (unchanged)
2. **Treści maili** — edit subject + body per template, placeholder reference, reset per-template / all
3. **Statusy** — edit label + color (picker) + description per status, live badge preview, reset all

### Modified files

| File | Changes |
|------|---------|
| `asiaauto-sync.php` | Version 0.18.0, added `require` for order-content (before order.php) |
| `class-asiaauto-order.php` | `sendWelcomeEmail()`, `sendOrderStartedEmails()`, `sendStatusChangedEmails()` → use Content templates; `statusBadgeHtml()` → delegates to Content; `getOrderData()` → editable labels/colors; `getAllowedTransitions()` → editable labels; error messages use editable labels |
| `class-asiaauto-order-admin.php` | `renderConfigPage()` → tabbed (config/templates/statuses); new handlers: `save_email_templates`, `save_status_display`, `reset_template`, `reset_all_templates`, `reset_status_display`; stat boxes use Content labels/colors |

### Backward compatibility

- `AsiaAuto_Order::STATUSES` / `STATUS_COLORS` constants → unchanged (fallback)
- `AsiaAuto_Order::TRANSITIONS` → unchanged (not editable from admin — security)
- If no templates saved in DB → original hardcoded content used (zero visual change)
- Existing emails continue to work identically until admin edits templates

## Changes (v0.16.0, 2026-04-02) — Contract PDF Redesign

Complete visual redesign of `class-asiaauto-contract.php` — professional layout
inspired by PKO Leasing document structure.

### Design decisions

| Element | Old (v0.13) | New (v0.16) |
|---------|-------------|-------------|
| Header | Centered title only | Logo left (~190px) + contract nr/date right, 2pt navy line |
| Parties | Sequential blocks | Two-column table layout, gray `#F5F6F8` background |
| §2 Scope | Single paragraph | Itemized list a)–g) with detailed descriptions |
| §3 Price | Full breakdown table (cena bazowa, transport, cło, prowizja…) | **Only final price** — no breakdown visible to client |
| §3 Description | None | One sentence explaining what the price covers |
| §4 Bank details | Simple table | Same table style as vehicle data |
| Signatures | Basic lines | Navy lines, uppercase labels, names underneath |
| Footer | Inline text | mPDF SetHTMLFooter — contract nr + page/total on every page |
| Brand color | `#1B2A4A` + `#D63031` price | `#1B2A4A` only (no red price — more professional) |

### Key code changes

- `renderHTML()` — completely rewritten template, mPDF-compatible (tables instead of CSS Grid/Flexbox)
- `collectData()` — removed `$breakdown` field (no longer needed), added `logo_src` with local→URL fallback
- `resolveLogoPath()` — new method, tries WP uploads local path before falling back to URL
- `LOGO_URL` constant — `https://asiaauto.pl/wp-content/uploads/2026/03/asia-auto-logo.png`
- `COLOR_NAVY` constant — `#1B2A4A`
- mPDF footer with `{PAGENO}/{nbpg}` page numbering on all pages
- Customer company name handling (shows company above person name if `billing_company_name` set)
- VIN displayed in monospace font
- All HTML entities properly escaped via `htmlspecialchars()`

### §2 Scope of services (a–g)

The contract now explicitly lists all services included in the "pod klucz" price:
a) weryfikacja dostępności i stanu technicznego
b) zakup w imieniu klienta
c) transport morski z Chin + ubezpieczenie ładunku
d) odprawa celna + należności (cło, akcyza, VAT)
e) transport krajowy do Rzeszowa
f) badanie techniczne, homologacja, rejestracja
g) przekazanie dokumentów i wydanie pojazdu

### Testing workflow

No new orders needed. Use existing order + admin "Regeneruj" button:
1. Upload `class-asiaauto-contract.php` via FTP
2. WP Admin → Zamówienia → any order with customer data
3. Scroll to "Umowa i dokumenty" → click 🔄 Regeneruj
4. Download PDF and verify

### Price breakdown — still in admin

The breakdown was removed only from the **client-facing PDF**. Admin order card
(`class-asiaauto-order-admin.php` → `renderCardPrice()`) still shows full breakdown
table with all cost components. This is intentional — internal visibility, client simplicity.

## Manual Import Module (v0.15.0 — admin UI, 2026-04-02)

Admin page for importing individual Dongchedi offers that don't match import filters.
Accessible via: WP Admin → Ogłoszenia → Dodaj z Dongchedi.

### Flow

1. User pastes Dongchedi URL or inner_id
2. AJAX fetches offer from `getOffer()` API
3. Preview shows: vehicle data, filter analysis (green/red), price breakdown, image thumbnails
4. "Zaimportuj" button → AJAX import with `$force=true` (bypasses `isAllowedByConfig()`)
5. After import, listing participates normally in sync, rotation, reservation

### Implementation

| Component | Details |
|-----------|---------|
| Class | `AsiaAuto_Admin_Manual_Import` |
| File | `includes/class-asiaauto-admin-manual-import.php` |
| Menu | Submenu under `edit.php?post_type=listings` |
| AJAX: preview | `wp_ajax_asiaauto_preview_offer` → API fetch + filter analysis + price calc |
| AJAX: import | `wp_ajax_asiaauto_manual_import` → `importListing($data, $source, true)` |
| Script dep | `wp-util` (enqueued via `admin_enqueue_scripts` on page hook) |

### Importer change (v0.15.0)

```php
// Added $force parameter — when true, skips isAllowedByConfig()
public function importListing(array $data, string $source, bool $force = false): ?int
```

### Meta keys (manual import)

| Meta key | Type | Description |
|----------|------|-------------|
| `_asiaauto_manual_import` | '1' | Marker: listing was manually imported |
| `_asiaauto_manual_import_by` | int | WP user ID who imported |
| `_asiaauto_manual_import_at` | string | ISO datetime of manual import |

### After manual import

- Listing gets full standard import: title, slug, taxonomies, meta, price pipeline, images, description translation
- Sync updates work normally (found by `inner_id` + `source`)
- Rotation/cleanup follows standard rules (reservation protection if applicable)
- No special treatment vs auto-imported listings (except the `_manual_import` meta marker)

### BYD sub-brands note

API returns BYD sub-brands as separate marks: `Fangchengbao` (方程豹, off-road: Leopard 5/8),
`Denza` (腾势), `Yangwang` (仰望, ultra-luxury), `Avatr` (阿维塔). This is correct per Chinese
market structure. European users may search "BYD Leopard 8" — consider brand mapping or display
adjustments if needed.

## Changes (v0.20.0, 2026-04-03) — Inventory Subpages: "W drodze" / "W Rzeszowie"

### Subpage support for inventory shortcode

Shortcode `[asiaauto_inventory]` extended with `reservation_status` attribute.
Two new Elementor pages display listings filtered by `_asiaauto_reservation_status` meta.

| Page | URL | Shortcode | Filters by |
|------|-----|-----------|------------|
| W drodze | `/w-drodze/` | `[asiaauto_inventory reservation_status="in_transit"]` | `transport_morski`, `odprawa_celna`, `transport_krajowy`, `formalnosci` |
| W Rzeszowie | `/w-rzeszowie/` | `[asiaauto_inventory reservation_status="on_lot"]` | `dostarczone` |

### Subpage layout (no sidebar)

- CSS class `aa-inv--no-sidebar` on container
- No sidebar (filters), no search bar, no mobile drawer
- Toolbar (count + sort) always visible (incl. mobile)
- `max-width: 960px` on main content
- Cards: stock → active "Zamów" button; customer → "Zarezerwowane" badge (v0.21.0)
- Empty state: dedicated messages + CTA → `/samochody/`

### Modified files

| File | Changes |
|------|---------|
| `class-asiaauto-inventory.php` | `reservation_status` shortcode attr + static context; `parseParams()` + `buildQueryArgs()` + `executeQuery()` support; `renderCard($post, $options)` with `hide_reservation_badge`; `renderEmptyState()` per-subpage; `getPriceRange()` scoped; all REST endpoints accept `reservation_status`; `getFilteredTermCounts()` with reservation join; `sanitizeReservationStatus()` validator |
| `asiaauto-inventory.js` | `RESERVATION_STATUS` const from `aaInventory.reservationStatus`; `appendReservationStatus()` helper; all fetch calls pass param; `fetchFilterCounts()` skipped on subpages |
| `asiaauto-inventory.css` | `.aa-inv--no-sidebar` layout (max-width 960px); `.aa-inv__empty-cta` button style; mobile toolbar override for no-sidebar |

### Reservation status mapping (v0.21.0 — updated from v0.20.0)

| `_asiaauto_reservation_status` | Triggered by order statuses | `_asiaauto_reservation_type` | Shown on page |
|---|---|---|---|
| `reserved` | potwierdzone → zarezerwowane | `customer` or `stock` | Main inventory (badge) |
| `in_transit` | zakupione, w_drodze | `customer` or `stock` | `/w-drodze/` |
| `on_lot` | na_placu | `customer` or `stock` | `/w-rzeszowie/` |

### Subpage card behavior (v0.21.0)

On `/w-drodze/` and `/w-rzeszowie/`:
- `_asiaauto_reservation_type = stock` → active "Zamów" button (leads to wizard, auto stock→customer conversion)
- `_asiaauto_reservation_type = customer` → disabled "Zarezerwowane" badge
- No image badge for stock listings (page context sufficient)

### Backward compatibility

- Main inventory (`/samochody/`) unchanged — no `reservation_status` attr = full layout with sidebar
- `renderCard()` second parameter `$options` is optional — existing external calls unaffected
- REST endpoints: `reservation_status` param optional, empty = no filter (same as before)
- CSS: new rules use `.aa-inv--no-sidebar` scope — zero impact on existing `.aa-inv`

## Changes (v0.21.0, 2026-04-03) — Order Types, Simplified Statuses, Stock Support

### Status redesign: 16 → 11 statuses

Removed 5 granular shipping sub-statuses that required 4-5 clicks to advance:
`transport_morski`, `odprawa_celna`, `transport_krajowy`, `formalnosci`, `dostarczone`.

Added 2 clear statuses: `w_drodze` (all shipping in one), `na_placu` (car physically in Rzeszów).

Flexible transitions: admin can skip steps (e.g. `zarezerwowane → na_placu` directly).

### Order types: customer vs stock

New `_order_type` meta: `customer` (default) or `stock`.
- **Customer**: listing blocked — no one else can order
- **Stock**: listing available — clients can order via wizard, triggers auto stock→customer conversion

### New listing meta: `_asiaauto_reservation_type`

Set alongside `_asiaauto_reservation_status`. Controls whether listing shows "Zamów" (stock) or "Zarezerwowane" (customer) on subpages.

### Key method changes

| Method | Change |
|--------|--------|
| `listingIsBlockedForOrders()` | NEW — checks reservation AND type (stock doesn't block) |
| `listingHasReservation()` | Unchanged — any reservation = protected from rotation |
| `create()` | New `$order_type` param; handles stock→customer conversion |
| `createInternal()` | New reservation types: `stock_purchased`, `stock_in_transit`, `stock_on_lot`, `reserved` |
| `changeStatus()` | `zakonczone` now clears reservation (was bug — only `anulowane` cleared) |
| `syncListingReservation()` | Sets `_asiaauto_reservation_type`; doesn't downgrade physical location |
| `clearListingReservation()` | Checks order ownership before clearing (prevents cross-order conflicts) |
| `closeStockOrder()` | NEW — auto-closes stock order with note |

### Admin UI changes

- Order list: new "Typ" column with Stock/Klient badge
- Stat boxes: `dostarczone` → `na_placu`
- Order card: type badge in header + customer section
- Internal form: new dropdown with optgroups (Stock 3 options + Klient 1 option)

### Modified files

| File | Changes |
|------|---------|
| `class-asiaauto-order.php` | STATUSES (11), TRANSITIONS (flexible), LISTING_RESERVATION_MAP, META_ORDER_TYPE, LISTING_META_RESERVATION_TYPE, LEGACY_STATUS_MAP, TYPE_CUSTOMER/TYPE_STOCK, create() with $order_type, createInternal() new types, closeStockOrder(), changeStatus() clears on zakonczone, syncListingReservation() sets type, clearListingReservation() checks ownership, listingIsBlockedForOrders(), getOrderTypeLabel(), getOrderData() includes order_type |
| `class-asiaauto-order-content.php` | defaultStatusDisplay() 11 statuses, defaultEmailTemplates() 15 templates (removed 5 old shipping, added w_drodze + na_placu), getStatusLabel/Color/Description() with legacy fallback, getTemplate() with legacy key mapping |
| `class-asiaauto-order-admin.php` | renderOrderList() type column + na_placu stat, renderOrderCard() type badge, renderCardCustomer() type info, renderCardContract() updated gen_statuses, renderNewInternalForm() new dropdown with stock/customer optgroups, CSS type badges |
| `class-asiaauto-order-wizard.php` | resolveStep() new statuses + LEGACY_STATUS_MAP, resolveInitialState() uses listingIsBlockedForOrders() |
| `class-asiaauto-order-api.php` | resolveWizardStep() new statuses + legacy, getListingReserveInfo() uses listingIsBlockedForOrders(), startOrder() uses listingIsBlockedForOrders() |
| `class-asiaauto-inventory.php` | executeQuery() passes is_subpage, renderCard() checks _asiaauto_reservation_type for stock vs customer button/badge on subpages |

### Migration (run once after deploy)

`migrate-v21.php`: backfills `_order_type=customer` on existing orders, `_asiaauto_reservation_type=customer` on reserved listings, cleans stale reservations, resets status display options.

### Bugs fixed

- `zakonczone` status didn't clear listing reservation (listing stuck on `/w-rzeszowie/` forever)
- `zakupione` mapped to `reserved` instead of `in_transit` (car in limbo — not on any subpage)
- No way to skip shipping sub-statuses (4 mandatory clicks to move car from "w drodze" to "na placu")

## Changes (v0.19.0, 2026-04-03) — Sync Toggle, Purge Outdated, Filter Cleanup

### Sync toggle in admin panel

Admin panel (Narzędzia → AsiaAuto Import) Status section now has sync on/off widget.

| Element | Details |
|---------|---------|
| Widget | Green "● AKTYWNA" / Red "○ WYŁĄCZONA" box |
| Button | "▶ Włącz sync" / "⏸ Zatrzymaj sync" (confirm dialog) |
| Option | `asiaauto_sync_enabled` (wp_options, '0'/'1') |
| Cron mgmt | Enable → `wp_schedule_event('asiaauto_15min')`, Disable → `wp_clear_scheduled_hook()` |
| Safety | Cron hook checks `AsiaAuto_Sync::isEnabled()` as belt+suspenders |

### Image stats in admin Status

"Zdjęcia na dysku" row: file count + human-readable size.
Cached via transient `asiaauto_disk_usage` (1h TTL). Cleared by purge-outdated.

### CLI: purge-outdated

```
wp asiaauto purge-outdated [--source=dongchedi] [--dry-run] [--yes]
```

Reads `asiaauto_import_config`, finds listings violating filters (year, brand, price, km).
Protects listings with active orders + reservations. Handles `Lynk & Co` / `Lynk &amp; Co` variants.
Deletes posts + gallery + featured images + orphaned attachments. Clears disk cache transient.

### Filter update workflow (established 2026-04-03)

```
1. Admin → "⏸ Zatrzymaj sync"
2. Admin → Filtry importu → update (year, price, marks)
3. SSH: wp asiaauto purge-outdated --dry-run
4. SSH: wp asiaauto purge-outdated --yes
5. SSH: nohup wp asiaauto import --source=dongchedi >> logs/import-oneshot.log 2>&1 &
6. Admin → "▶ Włącz sync"
```

`nohup` required for step 5 — full import takes hours on shared hosting.

### First purge (2026-04-03)

| Metric | Value |
|--------|-------|
| Before | 4 220 publish |
| Purged (year 2023) | ~588 |
| Purged (brand not in filter) | ~2 847 |
| Protected (active orders) | 4 listings |
| After purge | ~1 850 publish |

### Import filter config (2026-04-03)

| Param | Value |
|-------|-------|
| Marks (16) | Avatr, BYD, Chery, Chery Fengyun, Chery New Energy, Deepal, Denza, Exeed, Fangchengbao, Geely, Hongqi, Jetour, Mazda, Xiaomi, XPeng, Zeekr |
| year_from | 2024 |
| km_from / km_to | 1 000 / 50 000 |
| price_from | 120 000 ¥ |

Changes vs previous: added **Hongqi**, removed **Lynk & Co**.

### Installer pattern (new)

For inserting methods into large existing files (1000+ lines) without transcription risk:
`install-purge-outdated.php` — reads CLI file, finds marker, inserts method, creates `.bak`.
Run once via SSH, then delete.

## Changes (v0.22.0, 2026-04-03) — Multi-upload signed contract, confirm flow, admin revert

### Frontend wizard: signed contract flow reworked

| Before (v0.21) | After (v0.22) |
|----------------|---------------|
| Single file upload | Multi-file upload (JPG pages, PDF, XML) |
| Auto status change on upload (umowa_gotowa → podpisane) | Manual confirmation required |
| No way to delete uploaded file | ✕ button to remove before confirming |
| No feedback after confirmation | "✓ Umowa podpisana" disabled button |

### New REST API endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/order/{id}/confirm-signed` | POST | Customer confirms signed contract → status podpisane |
| `/order/{id}/delete-upload` | POST | Delete specific attachment before confirmation |

### Modified endpoints

| Endpoint | Change |
|----------|--------|
| `POST /order/{id}/upload` | signed_contract: appends to JSON array, no auto-status change. Blocked after confirmation. |
| `GET /order/{id}` | `uploads.signed_files` returns array `[{id, url, filename}]`. New field `signed_contract_confirmed`. |

### Admin: regenerate contract with revert

When admin clicks "Regeneruj" on order with status `podpisane`:
- Confirm dialog warns about revert + file deletion
- Deletes all signed contract attachments (supports int + JSON array)
- Reverts status `podpisane → umowa_gotowa`
- Suppresses normal status email (`$suppressNextStatusEmail` flag)
- Sends custom `contract_regenerated` email to customer
- Regenerates PDF

### TRANSITIONS change

`'podpisane' => ['zarezerwowane', 'umowa_gotowa']` — added umowa_gotowa for admin revert.

### Meta format change

`_order_signed_attachment_id` — changed from single `int` to `JSON array` string (e.g. `[401, 402]`).
Helper `getSignedAttachmentIds()` in API class handles both formats (backward compat).

### New email template

`contract_regenerated` — sent when admin regenerates contract on podpisane order.
Editable in admin: Narzędzia → AsiaAuto Zamówienia → Treści maili.

### Mail sender

- `wp_mail_from_name` = "Zamówienia AsiaAuto.pl"
- `wp_mail_from` = `zamowienia@asiaauto.pl`
- Hosting: Hostido shared (Elara), local delivery works with existing mailbox

### Bug fixes

- `{company_phone}` → `{customer_phone}` in `order_started_admin` template
- `{customer_phone}` added to PLACEHOLDERS and `buildVariables()`
- Tracking (Step 5) updated to v0.21 statuses (w_drodze, na_placu)

### Modified files

| File | Changes |
|------|---------|
| `asiaauto-sync.php` | Version 0.22.0, wp_mail_from_name + wp_mail_from filters |
| `class-asiaauto-order.php` | TRANSITIONS['podpisane'] += umowa_gotowa, $suppressNextStatusEmail flag + guard |
| `class-asiaauto-order-api.php` | Complete rewrite: multi-upload, confirm-signed, delete-upload, getSignedFiles() |
| `class-asiaauto-order-admin.php` | Complete rewrite: handleGenerateContract() with revert, sendContractRegeneratedEmail() |
| `class-asiaauto-order-content.php` | contract_regenerated template + label, {customer_phone} fix |
| `asiaauto-order-wizard.js` | Complete rewrite Step 4: multi-file list, delete, confirm button, confirmed badge |
| `asiaauto-order-wizard.css` | .aa-wiz__signed-file*, .aa-wiz__confirm-signed, .aa-wiz__btn--confirmed |

## v0.23.0 — Utility shortcodes + Model translation pipeline (2026-04-04)

### New shortcodes (GRUPA 1 — all 4)

Registered in `class-asiaauto-shortcodes.php`, all prefixed `aa_`:

| Shortcode | Output | Usage |
|-----------|--------|-------|
| `[aa_phone]` | `tel:+48783807381` (default) | Elementor button href. Formats: `tel` (default), `raw` (with spaces), `clean` (no prefix) |
| `[aa_whatsapp]` | `https://wa.me/48783807381?text=...` | Elementor button href. Auto-includes listing title + URL. Attribute `text` for custom message with `{url}`/`{title}` placeholders |
| `[aa_back]` | `<button>` with `history.back()` | Styled per design system. Attribute `text` for custom label |
| `[aa_breadcrumb]` | `Oferta › Marka › Model` | `<nav>` with links to taxonomy archives. Only on single listing pages |

Phone source: `get_option('asiaauto_order_config')['company_phone']`

### Model translation pipeline (GRUPA 3 #6)

Chinese model names from API (海狮07DM, 深蓝L06, etc.) now translated before
title build and taxonomy assignment. Prevents recurring Chinese in titles and model terms.

| Component | File | Change |
|-----------|------|--------|
| Model map | `data/translations-models.php` | NEW: 35 ZH→EN model translations (brand-stripped) |
| Complectation map | `data/translations-complectations.php` | +24 segments (暗夜骑士, 科技, 魔方, 智航, etc.) |
| Translator | `class-asiaauto-translator.php` | NEW: `translateModel()` — ASCII passthrough, translates only Chinese |
| Importer | `class-asiaauto-importer.php` | Calls `translateModel()` in `importListing()` + `setTaxonomies()` |
| Fix script | `diag/fix-chinese-v23.php` | Retrofix: 12 model terms + 113 titles (Part 1: models, Part 2: complectation) |

Model names are brand-stripped (consistent with existing taxonomy: "Seal" not "BYD Seal"):
- `海狮07DM` → `Sea Lion 07DM` (BYD)
- `深蓝L06` → `L06` (Deepal)
- `腾势N8L DM` → `N8L DM` (Denza)
- `马自达EZ-60` → `EZ-60` (Mazda)

Rollback: restore `.bak-v22` copies of translator + importer. Data files are passive.

### Bug fix

`renderCTA()` in shortcodes — was using `$config['phone']` (non-existent key),
fixed to `$config['company_phone']` (actual key in `asiaauto_order_config`).

### Modified files

| File | Changes |
|------|---------|
| `class-asiaauto-shortcodes.php` | +4 shortcodes (aa_phone, aa_whatsapp, aa_back, aa_breadcrumb), renderCTA bugfix |
| `class-asiaauto-translator.php` | +translateModel(), +model map in loadMaps() |
| `class-asiaauto-importer.php` | translateModel() call in importListing() + setTaxonomies() |
| `data/translations-models.php` | NEW: 35 model translations |
| `data/translations-complectations.php` | +24 segments |
| `diag/fix-chinese-v23.php` | NEW: combined model + title fixer |

## v0.24.0 — Security: Login hiding + Seller role (2026-04-04)

### Login URL anonymization (#10)

Approach: .htaccess (Apache-level) + minimal PHP filters. No cookies, no PHP redirect chains.

**.htaccess rules (before WordPress block):**

| Rule | Purpose |
|------|---------|
| `RewriteRule ^wp-admin/(load-styles\|load-scripts\|admin-ajax\|admin-post)\.php$ - [L]` | **Whitelist (must be first)** — short-circuits the wp-admin block below so `wp-login.php` (served from `/biuro/`) can fetch its CSS/JS. Added v0.27.0 — without this the login page renders unstyled because `load-styles.php` hits the wp-admin guest block. |
| `RewriteRule ^biuro/?$ wp-login.php [QSA,L]` | Internal rewrite — browser sees `/biuro/`, Apache serves `wp-login.php` |
| `THE_REQUEST \s/wp-login\.php` + conditions | Block direct GET to `wp-login.php` for non-authenticated users → 404 |
| `REQUEST_URI ^/wp-admin/` + no `wordpress_logged_in_` cookie | Block `wp-admin` for non-authenticated users → 404 (simplified from `THE_REQUEST` chain in v0.27.0 — assets already passed via whitelist rule above) |

Key technique: `%{THE_REQUEST}` contains original browser request line — unaffected by internal rewrites.
`/biuro/` → THE_REQUEST says `/biuro/` → not blocked. Direct `/wp-login.php` → blocked.

Conditions allow through: POST (form submit), `wordpress_logged_in_` cookie, safe actions (logout, lostpassword, resetpass, rp, postpass, confirmaction), admin-ajax.php, admin-post.php.

**PHP filters (`class-asiaauto-security.php`):**
- `login_url` → `/biuro/` (all WP-generated login links)
- `site_url` for `wp-login.php` → `/biuro/` (form action, password reset links)
- Login slug: `const LOGIN_SLUG = 'biuro'` (must match .htaccess RewriteRule)
- **To change slug:** edit both `.htaccess` RewriteRule AND `LOGIN_SLUG` constant

### Seller role `primaauto` (#9)

Role: `primaauto` ("Sprzedawca PrimaAuto") — 32 capabilities.

| Scope | Capabilities |
|-------|-------------|
| Listings/Posts | edit, edit_others, edit_published, publish, delete (full set) |
| Pages | Same full set |
| Taxonomy | manage_categories |
| Orders | manage_asiaauto_orders (custom cap) |
| Media | upload_files |
| WP Levels | level_0 through level_7 |

Blocked: plugins, themes, users, settings, tools, core updates.
Admin menu hidden + direct URL access blocked (`blockRestrictedAdmin`).

### Custom order capability

`manage_asiaauto_orders` — new cap replacing `manage_options` in order admin.
Granted to: `administrator` (activation hook) and `primaauto` role.

**VERIFIED (v0.25.0):** `class-asiaauto-order-admin.php` — main menu uses `ORDER_CAP` ✅,
config submenu uses `manage_options` (intentional — admin-only settings) ✅,
`handleActions()` uses `ORDER_CAP` ✅. Primaauto role has full order management access.

### Stale role cleanup

On activation: removes Motors/WooCommerce roles with 0 users:
`stm_dealer`, `listing_manager`, `shop_manager`, `customer`.

### Modified files

| File | Changes |
|------|---------|
| `.htaccess` | NEW: AsiaAuto Login Hide block (3 rewrite rules before WordPress block) |
| `class-asiaauto-security.php` | NEW: login URL filters (filterLoginUrl, filterSiteUrl) + primaauto role + admin menu cleanup |
| `asiaauto-sync.php` | +require security, +new AsiaAuto_Security(), +activation: grantAdminOrderCap + cleanStaleRoles |

## v0.25.0 — Homepage, broken JSON fix, mobile CSS (2026-04-05)

### #7 — Fix broken extra_prep JSON (93 listings)

Root cause: `wp_json_encode()` + `update_post_meta()` + `wp_unslash()` strips `\` from `\"` →
bare `"` in JSON → `json_decode()` returns null → tech specs don't render.
Affected: 93 listings (Denza N9 DM, Fangchengbao Tai 7/Tai 3, Volvo EM90) — models with DJI drone
system specs containing literal quotes in Chinese descriptions.

| File | Change |
|------|--------|
| `class-asiaauto-importer.php` | `wp_slash(wp_json_encode($ep))` — prevents future breakage |
| `class-asiaauto-single.php` | `fixBrokenFlatJson()` state machine parser — recovers broken JSON on-the-fly |
| `diag/fix-broken-json-v25.php` | Migration: parsed & re-saved all 93 broken listings (0 failures) |

Additional: `$cfg['phone']` → `$cfg['company_phone']` fix in single page sidebar.

### #12 — Mobile CSS fixes

Admin order list:
- `assets/css/asiaauto-order-admin.css` — now loaded via `wp_enqueue_style` (was inline without media queries)
- Stat boxes: CSS grid `repeat(3, 1fr)` always 3 per row
- Table: `nth-child()` hides 5 columns on mobile, `table-layout:fixed` with explicit widths

Frontend safe-area-inset (iPhone home indicator):
- `asiaauto-inventory.css` — sidebar `100dvh` + `env(safe-area-inset-bottom)`
- `asiaauto-single.css` — mobile CTA bottom padding with safe area

### #11 — Homepage `[asiaauto_homepage]`

New: `includes/class-asiaauto-homepage.php` — 8 sections, all data dynamic from DB.
Hero search (make/model AJAX + CTA), stats bar (3 counters), latest listings (6 cards scroll),
popular makes (3×3 grid), body types (4 tiles, custom SVG icons), fuel types (pills),
USP (3 items), contact CTA (phone + WhatsApp).

SEO: H1 "Samochody elektryczne i hybrydowe z Chin" (jedyny), H2 per section with keywords,
H3 for USP, `<section>` elements, Schema.org AutoDealer + WebSite with SearchAction.

### Modified files

| File | Changes |
|------|---------|
| `class-asiaauto-homepage.php` | NEW: homepage shortcode |
| `class-asiaauto-single.php` | +fixBrokenFlatJson(), +company_phone fix |
| `class-asiaauto-importer.php` | +wp_slash() for extra_prep |
| `class-asiaauto-order-admin.php` | enqueueStyles → wp_enqueue_style |
| `assets/css/asiaauto-order-admin.css` | Full rewrite with responsive media queries |
| `asiaauto-sync.php` | +require homepage, +new AsiaAuto_Homepage() |
| `diag/fix-broken-json-v25.php` | NEW: migration for 93 broken JSON listings |

## v0.26.0 — Contact page shortcode (2026-04-07)

New: `includes/class-asiaauto-contact.php` — `[asiaauto_contact]` shortcode for `/kontakt/` page.
Header/footer rendered by Elementor Theme Builder; shortcode renders content sections only.

### Sections

1. **Hero** — H1 SEO "Kontakt — AsiaAuto.pl | Import samochodów z Chin", podtytuł z lokalizacją, navy bg
2. **Dane kontaktowe** + **Mapa** (2-col desktop, stacked mobile) — telefon (tel:), WhatsApp (wa.me), email (mailto:), adres placu + Google Maps iframe
3. **Godziny pracy** — pon-pt 9-17, sob 10-14, nd zamknięte (hardcoded const HOURS)
4. **Dane firmy** + **Rachunek bankowy** (2-col desktop)
5. **Napisz do nas** — sekcja z mailto button (formularz: opcjonalnie Elementor Pro Forms wstawiony w treści strony powyżej/poniżej shortcode)
6. **CTA** — Zadzwoń + WhatsApp (jak na homepage)

### Data sources

| Element | Źródło | Klucz |
|---------|--------|-------|
| Telefon | `asiaauto_order_config` | `company_phone` |
| Email | `asiaauto_order_config` | `admin_notification_email` |
| Nazwa firmy | `asiaauto_order_config` | `company_name` |
| Adres rejestrowy | `asiaauto_order_config` | `company_address` |
| NIP | `asiaauto_order_config` | `company_nip` |
| REGON | `asiaauto_order_config` | `company_regon` |
| Numer konta | `asiaauto_order_config` | `bank_account_number` |
| Nazwa odbiorcy | `asiaauto_order_config` | `bank_account_name` |
| Plac samochodowy | hardcoded const | `ul. Przemysłowa 13A, 35-105 Rzeszów` |
| Godziny pracy | hardcoded const HOURS | pon-pt 9-17, sob 10-14, nd zamknięte |
| GeoCoordinates | hardcoded const | 50.025, 22.018 |

**Ważne:** plac samochodowy ≠ adres firmy. Adres firmy (Pleśniarowicza, z configa) wyświetlany w sekcji "Dane firmy" jako adres rejestrowy. Adres placu (Przemysłowa, hardcoded) używany jako fizyczna lokalizacja na mapie + w schema.org. Telefon, email i wszystkie pozostałe dane firmy/bankowe pobierane wyłącznie z configa — zero hardcoded danych kontaktowych.

### Schema.org LocalBusiness

- `@type: LocalBusiness`, `@id: {site}/kontakt/#business`
- `name: AsiaAuto.pl`, `legalName` z configa (`company_name`)
- `address` (PostalAddress) — adres PLACU (Przemysłowa 13A, 35-105 Rzeszów, PL)
- `geo` (GeoCoordinates) — 50.025, 22.018
- `telephone`, `email`, `taxID` (NIP) — z configa
- `openingHoursSpecification` — array OpeningHoursSpecification (Mon-Fri 09:00-17:00, Sat 10:00-14:00, niedziela pominięta)
- `areaServed: PL`, `image` (logo URL), `priceRange: $$`
- `description`, `url` — homepage

### Map embed

Google Maps iframe bez API key:
```
https://www.google.com/maps?q={street}, {postal} {city}&output=embed
```
Plus link "Otwórz w Google Maps →" do `/maps/search/...`. Aspect ratio 16/10 mobile, fill-height desktop.

### CSS conventions

- BEM: `.aa-contact__hero`, `.aa-contact__main`, `.aa-contact__info`, `.aa-contact__map-col`, `.aa-contact__bottom`, `.aa-contact__company`, `.aa-contact__bank`, `.aa-contact__write`, `.aa-contact__cta`
- Inline w shortcode (do ekstrakcji w v0.27+, task #20)
- Mobile-first → desktop grid `1fr 1fr` przy 768px+
- `!important` na kolorach hero + CTA (Elementor Site Settings nadpisuje)
- Typography Inter, design tokens: navy `#1B2A4A`, accent `#D63031`, surface `#FFFFFF`, border `#E1E4E8`

### REST/API

Brak — strona w pełni statyczna z danych w wp_options. Bez AJAX, bez cache.

### Modified files

| File | Changes |
|------|---------|
| `class-asiaauto-contact.php` | NEW: [asiaauto_contact] shortcode (full file) |
| `asiaauto-sync.php` | +require contact, +new AsiaAuto_Contact(), version bump 0.25.0 → 0.26.0 |

## v0.27.0 — Branded login screen + .htaccess assets fix (2026-04-07)

Fix dla problemu zgłoszonego po v0.24.0: strona `/biuro/` renderowała się bez stylów,
bo blok `.htaccess` zabraniał gościom dostępu do całego `/wp-admin/` — w tym
`load-styles.php` i `load-scripts.php`, z których `wp-login.php` ciągnie CSS/JS.

### .htaccess fix

Dodana **pierwsza** reguła rewrite (przed blokami login/admin), która natychmiast
przepuszcza assety i ajax z flagą `[L]`:

```apache
RewriteRule ^wp-admin/(load-styles|load-scripts|admin-ajax|admin-post)\.php$ - [L]
```

Wcześniejsze podejście opierało się na łańcuchu `RewriteCond %{THE_REQUEST} !/wp-admin/load-...`,
co działało zawodnie zależnie od kolejności reguł i interakcji z blokiem WordPress poniżej.
Nowa wersja jest jednoznaczna: jeśli URL pasuje do whitelisty, request kończy mielenie reguł
i Apache po prostu serwuje plik. Blok blokujący `/wp-admin/` poniżej został uproszczony do
`%{REQUEST_URI}` (bez negacji `THE_REQUEST` na poszczególne pliki — niepotrzebne, bo whitelist
już je zwolnił).

**Test po wgraniu (curl, bez logowania):**

```bash
curl -sI "https://asiaauto.pl/biuro/"                         # 200
curl -sI "https://asiaauto.pl/wp-admin/load-styles.php?..."   # 200 ← był 404
curl -sI "https://asiaauto.pl/wp-admin/load-scripts.php?..."  # 200 ← był 404
curl -sI "https://asiaauto.pl/wp-admin/"                      # 404 ✓
curl -sI "https://asiaauto.pl/wp-login.php"                   # 404 ✓
```

### Branded login screen

Nowa klasa `AsiaAuto_Login` (`includes/class-asiaauto-login.php`) — pełne brandowanie
ekranu logowania pod stylistykę homepage (`class-asiaauto-homepage.php`).

| Element | Wartość |
|---------|---------|
| Logo | **Tekstowe** — `AsiaAut<span>o</span>.pl` (navy + amber kropka), Inter 32px/800. Zero pliku graficznego — `themes/asiaauto/` zawiera tylko `functions.php` + `style.css`. |
| Tagline | "Auta z Chin w najlepszych cenach" (drugorzędny szary, 12px) |
| Karta formularza | Białe tło, border `#E1E4E8`, radius 8px, subtle shadow `rgba(27,42,74,.08)` |
| Submit | Czerwony `#D63031` (akcent z homepage CTA), hover `#B52828`, full width, radius 6px |
| Pola | Border 1px `#E1E4E8`, focus border navy + 3px ring `rgba(27,42,74,.10)` |
| Font | Inter (Google Fonts `@import` w inline CSS) |
| Tło strony | `#F5F6F8` (background z homepage), centrowanie flex |

**Hooki użyte:**

| Hook | Cel |
|------|-----|
| `login_enqueue_scripts` (prio 100) | `wp_register_style` + `wp_add_inline_style` z całym brandowanym CSS |
| `login_headerurl` | Link logo → `home_url('/')` |
| `login_headertext` | "AsiaAuto.pl" (text dla screen readerów; widoczne `h1` ukrywane przez CSS) |
| `login_display_language_dropdown` | `__return_false` — usunięcie przełącznika języka |
| `login_errors` | Generic "nieprawidłowe dane logowania" zamiast wycieku informacji o istnieniu loginu |
| `login_head` (prio 1) | `<meta robots="noindex,nofollow">` |
| `login_header` | Wstrzyknięcie własnego brandu (text logo + tagline) nad formularzem |

**Strategia self-contained CSS:** wszystkie style mają `!important` i nie polegają na
domyślnych regułach z `wp-admin/css/login.min.css`. Powód — gdyby przeglądarka miała
zacacheowaną starą odpowiedź 404 z `load-styles.php` (sprzed fixu .htaccess), strona i tak
wyrenderuje się poprawnie. Dodatkowo CSS jest enqueueowany z `?ver=ASIAAUTO_VERSION`,
więc bump wersji pluginu zbija cache po stronie klienta.

**Ukrywane elementy:**

| Element | Metoda |
|---------|--------|
| `#login h1 a` ("Oparte na WordPressie") | `display: none !important` (zastąpione własnym brandem) |
| `#backtoblog` ("← Przejdź do AsiaAuto" ze strzałką) | `display: none !important` |
| `.login .language-switcher` / `#language-switcher` | `display: none !important` + filter PHP |

### Modified files

| File | Changes |
|------|---------|
| `.htaccess` | Dodana reguła whitelist `^wp-admin/(load-styles\|load-scripts\|admin-ajax\|admin-post)\.php$ - [L]` jako pierwsza w bloku AsiaAuto Login Hide; uproszczony blok blokujący `/wp-admin/` do `REQUEST_URI` (negacje na poszczególne pliki usunięte) |
| `class-asiaauto-login.php` | NEW: brandowany ekran logowania, self-contained CSS, hooki login_*, text logo z amber kropką, generic error message |
| `asiaauto-sync.php` | +require login, +new AsiaAuto_Login(), version bump 0.26.0 → 0.27.0 |

### Rationale — text logo zamiast pliku

Skill historycznie wspominał `asia-auto-logo.png` w `themes/asiaauto/`, ale weryfikacja
przez MCP `list_dir` pokazała, że katalog zawiera **tylko** `functions.php` (411B) i
`style.css` (258B). Plik logo nie istnieje w theme dir — jest to logo tekstowe renderowane
inline w `class-asiaauto-homepage.php` (CSS: navy "AsiaAut" + amber "o" + navy ".pl").
Login powiela ten sam pattern, dzięki czemu zero zależności od plików graficznych i
spójność z headerem strony.

## v0.29.0-wip — Manual Listing Editor: task #8 (2026-04-10)

Dodawanie moduł ręcznego dodawania i edycji ogłoszeń przez sprzedawcę (rola `primaauto`)
i admina w panelu WordPress. Task #8 z backlogu. Moduł wdrażany iteracyjnie w kilku krokach,
aktualny stan poniżej.

### Plan modułu (zrewidowany 2026-04-10)

| Krok | Zakres | Status |
|------|--------|--------|
| 1 | `class-asiaauto-price.php` — dual input mode (CNY + CIF PLN), refaktor pipeline'u | ✅ DONE |
| 2 | `class-asiaauto-listing-editor.php` — metabox "Dane pojazdu" + cena CIF + walidacja + duplikacja | ✅ DONE |
| 3 | Metabox galerii (drag&drop, WebP+SEO naming, reuse `AsiaAuto_Media`) | TODO |
| 4 | Metabox extra_prep (18 zakładek z `translations-extra-prep.php`) | TODO |

**Zmiana planu vs pierwotny (2026-04-10):** oryginalnie plan miał 5 kroków — osobne metaboxy
dane/cena/galeria/**import z innego ogłoszenia**/extra_prep + helper `setTaxonomyAndMeta`
wyciągnięty z importera. Po rozmowie z klientem:
- (a) Dane pojazdu + cena CIF **scalone w jeden metabox** — sprzedawca ma wszystko w jednym widoku
- (b) "Import z innego ogłoszenia" **wyparowuje** — zastąpiony row action "Duplikuj" na liście
  listingów (bo naturalnym workflow sprzedawcy jest "znajdź podobne auto, skopiuj, popraw
  co indywidualne", więc prostsza duplikacja WP jest lepsza niż AJAX select2 + fill-fields handler)
- (c) Helper taksonomii **nie jest wyciągany z importera** — metabox ma własną, prostszą wersję
  bez logiki `$api_value` dla sync-filters (sprzedawca wybiera polskie termy z datalist, nie
  potrzebuje canonical slugów z API)

Wynik: zamiast 5 kroków robimy 4, drugi krok jest grubszy (metabox + duplikacja + AJAX preview
ceny) ale za to nie mamy osobnego importu który by się rozlewał na dodatkowe AJAX endpointy.

### Krok 1 — Dual input mode w `class-asiaauto-price.php` ✅ DONE

**Nowa metoda `calculateFromCif(float $cif_pln, ?array $config, string $fuel_type): array`**
dla MODE B (CIF PLN, ręczne ogłoszenia). Pipeline startuje od kroku 3 (agencja celna), kroki 1-2
wyzerowane bo już zawarte w wartości CIF. Edge case `$cif_pln <= 0` → struktura `przeliczone=false`
z pełnym kształtem breakdownu (AJAX preview w metaboxie pokazuje komunikat "wpisz cenę CIF",
save handler blokuje zapis walidacją wymaganych pól).

**Refaktor: `runPipelineFromStep3()`** — prywatny helper wyciągnięty z `calculate()`. Zawiera
kroki 3-9 (agencja celna → cło → transport portu → akcyza → homologacja → prowizja → VAT →
zaokrąglenie). Używany przez `calculate()` (MODE A) i `calculateFromCif()` (MODE B). DRY,
gwarancja matematycznej spójności między trybami — jakakolwiek zmiana kroków 3-9 automatycznie
aplikuje się do obu ścieżek.

**Nowa stała `META_CIF = '_asiaauto_cif_price_pln'`** — meta key dla ręcznych ogłoszeń.

**`calculate()` i `calculateFromCif()` zwracają nowe pole `input_mode`** (`'cny'` / `'cif'`)
w każdej strukturze. Templaty admina i frontend mogą rozpoznać tryb po tym polu.

**`applyToListing()` rozszerzony o auto-detect trybu:**
- Jeśli `_asiaauto_cif_price_pln > 0` → MODE B (wywołanie `calculateFromCif`)
- Inaczej MODE A (wywołanie `calculate`, jak w v0.28.0)
- Jeśli obie meta ustawione (anomalia) → CIF wygrywa, warning w logu

**`bulkRecalculate()` rozszerzony o MODE B:**
- Dwa osobne zapytania SQL (`_asiaauto_original_price` i `_asiaauto_cif_price_pln`)
- Overlap handling: listingi z obiema metami są usuwane z listy CNY (CIF ma priorytet)
- Stats rozszerzone o `cny_count` i `cif_count` obok istniejących `total`/`recalculated`/`skipped`/`errors`
- Filtr `source` działa dla obu zapytań (join po `_asiaauto_source` meta)
- Jeden przebieg odświeża oba typy po zmianie config.

**`formatBreakdown()` rozgałęzia nagłówek po `input_mode`:**
- `'cny'` → standardowe "Cena CNY: X ¥" + "Kurs CNY→PLN" + "1. Cena bazowa PLN"
- `'cif'` → "Tryb: CIF PLN (ręczne ogłoszenie)" + "Cena CIF: X zł" + "1. Cena bazowa PLN (=CIF)" + "2. Chiny/rej./transp.: wliczone w CIF (0 zł)"
- Kroki 3-9 identyczne w obu trybach

**Breakdown JSON** — nowe pola: `input_mode` (zawsze), `cif_pln` (tylko MODE B, w MODE A `=0`).

**Defensywne `class_exists('AsiaAuto_Logger')`** we wszystkich miejscach logowania — metoda
może być wywołana z kontekstu WP-CLI / diag script gdzie logger jeszcze nie jest załadowany.

### Rationale — dual-mode vs twarda separacja

Alternatywą dla wspólnego API (`applyToListing`/`bulkRecalculate` dla obu trybów) było:
importer ma swoje funkcje, metabox sprzedawcy własne, zero przecięć. Odrzucone, bo:

1. **Błąd ludzki po zmianie configa cen.** Po zmianie stawki akcyzy 3.1% → 5% klient klika
   "przelicz wszystkie ceny". Przy twardej separacji: 2897 importerowych ma nową cenę,
   ale 15 ręcznych ogłoszeń sprzedawcy dalej ma starą (cło/akcyza z dnia dodania). Żeby je
   odświeżyć sprzedawca musi wejść w każde z 15 i kliknąć "Zapisz" — gwarantowany błąd
   zapomnienia, gwarantowana strata marży.
2. **Brak ryzyka nadpisania.** Wspólne API nie tworzy ryzyka że importer "nadpisze" ręczne
   ogłoszenie, bo listingi są rozłączne po meta (importer pisze tylko `_asiaauto_original_price`,
   metabox tylko `_asiaauto_cif_price_pln`; sync po `inner_id` nigdy nie trafi w ręczne bo
   one nie mają `_asiaauto_inner_id`/`_asiaauto_source`).

### Modified files (Krok 1)

| File | Changes |
|------|---------|
| `includes/class-asiaauto-price.php` | Nowa stała `META_CIF`. Nowa publiczna metoda `calculateFromCif()`. Nowa prywatna metoda `runPipelineFromStep3()` — shared logic kroków 3-9. Refaktor `calculate()` — używa `runPipelineFromStep3()`, dodaje `input_mode='cny'` do zwracanej struktury. Rozszerzenie `applyToListing()` — auto-detect trybu po meta, CIF wygrywa nad CNY, warning w logu przy obu metach. Rozszerzenie `bulkRecalculate()` — 2 SQL queries, overlap handling, stats `cny_count`/`cif_count`. Rozszerzenie `formatBreakdown()` — rozgałęzienie nagłówka po `input_mode`. Defensywne `class_exists('AsiaAuto_Logger')`. Docblock klasy zaktualizowany o sekcję "TWO INPUT MODES", `@since 0.29.0` |

### Deployment workflow used (Krok 1)

1. MCP `read_file includes/class-asiaauto-price.php` — weryfikacja stanu v0.28.0 przed patchem
2. Generacja pełnego pliku v0.29.0 jako artefakt w chacie
3. Sanity check: `php -l` + symulacja pipeline'u w PHP CLI dla CIF 200k (petrol/hybrid/electric) + CIF 150k (petrol) — wyniki 318 000 / 313 000 / 309 000 / 241 000 zgodne z ręcznym wyliczeniem
4. Klient wgrywa plik FTP-em na serwer
5. MCP `read_file` — weryfikacja że bajt-w-bajt zgadza się z wygenerowanym
6. MCP `logs 50` — sprawdzenie że sync Dongchedi leci dalej bez fatal errorów
7. W logu o 14:35:25: `Bulk price recalculation: 2897/2897 updated` → zero regresji MODE A

### Krok 2 — `class-asiaauto-listing-editor.php` ✅ DONE (2026-04-10)

Metabox "Dane pojazdu — AsiaAuto" w głównej kolumnie edytora CPT `listings`
(context=`normal`, priority=`high`), rozwiązuje task #8 "pola w sidebar zamiast
w głównym oknie". Scalony z ceną CIF PLN w jeden widok zamiast pierwotnie planowanych
osobnych metaboxów. Row action "Duplikuj" na liście listingów zastąpił pierwotny
osobny metabox "Import z innego ogłoszenia" (workflow sprzedawcy naturalnie pasuje
do "znajdź podobne, skopiuj, popraw indywidualne" — prostsza duplikacja WP wygrywa
z AJAX select2 + fill-fields handlerem).

**Zarys modułu** (~820 linii):

| Hook | Metoda | Cel |
|------|--------|-----|
| `use_block_editor_for_post_type` | `disableBlockEditor()` | Force classic editor dla `listings` — w Gutenbergu custom metaboxy są chowane w zwijanej sekcji "Metaboksy" na dole strony, nieużywalne dla sprzedawcy wypełniającego 15 pól |
| `add_meta_boxes_listings` (prio 10) | `registerMetabox()` | Rejestracja "Dane pojazdu — AsiaAuto" |
| `add_meta_boxes_listings` (prio 20) | `removeDefaultTaxonomyMetaboxes()` | `remove_meta_box('{tax}div', …)` dla wszystkich 10 taksonomii — znikają domyślne panele "Marki"/"Modele"/etc. z sidebara, sprzedawca nie ma dwóch miejsc do edycji marki |
| `save_post_listings` (prio 20) | `handleSave()` | Walidacja + taksonomie + meta + slug + `applyToListing()` |
| `post_row_actions` | `addDuplicateRowAction()` | Link "Duplikuj" z nonce na liście listingów |
| `admin_action_asiaauto_duplicate_listing` | `handleDuplicate()` | Kopia posta do drafta |
| `admin_notices` | `renderAdminNotices()` | Walidacja (warning z listą braków) + duplikacja success (`?asiaauto_dup=1`) |
| `wp_ajax_asiaauto_preview_cif_breakdown` | `handleAjaxPreview()` | AJAX podgląd breakdownu z `calculateFromCif()` → HTML tabela |

**Pola formularza** (3-kolumnowy CSS grid, mobile → 1 kolumna):

| Kategoria | Pola | Widget |
|-----------|------|--------|
| Taksonomie | make, serie, ca-year, body, transmission, drive, condition, exterior-color, interior-color | **`<select>` strict ze slugiem w `value`, nazwą w tekście opcji** — brak auto-create, link "Zarządzaj: X ↗" pod spodem do `edit-tags.php` w nowej karcie |
| Fuel | fuel | `<select>` ze slugiem (potrzebny dla stawki akcyzy) |
| Meta wymagane | mileage, CIF PLN | `<input type="number">` |
| Meta opcjonalne | vin, stm_car_location, registration_date (DD/MM/YYYY), _asiaauto_owners_count, _asiaauto_horse_power, engine (cm³), _asiaauto_complectation | `<input type="text"/number>` |

**Wymagane pola** (walidacja miękka): Marka, Model, Rok, Paliwo, Przebieg, Cena CIF (PLN).
Przy brakach: force status `draft` + transient (`asiaauto_listing_editor_notice_{user}_{post_id}`,
TTL 2 min) → admin_notice warning z listą braków na następnym załadowaniu strony.

**Generowanie tytułu** — przycisk `🪄 Wygeneruj tytuł z danych pojazdu` wstrzykiwany
JS-em jako `div` pod `#titlewrap` (natychmiast widoczny przy polu Tytuł u góry strony,
bez scrolla do metaboksa). Klik czyta `option.text` z selektów make/serie/ca-year
+ input `_asiaauto_complectation`, składa w string "Marka Model Rok Wersja" i wpisuje
do `#title` z dispatchem `input`/`change` (classic editor odświeża nagłówek strony).

**Podgląd breakdownu ceny** — przycisk "Podgląd breakdownu ceny" → `fetch()` POST do
`admin-ajax.php?action=asiaauto_preview_cif_breakdown` z `cif` + `fuel` + nonce.
Backend: `AsiaAuto_Price::calculateFromCif($cif, null, $fuel)` → prywatna metoda
`renderBreakdownHtml($result)` renderująca tabelę HTML server-side (wzorowana na
`aa-mi-table` z `class-asiaauto-admin-manual-import.php`, zaktualizowana dla v0.28.0
pipeline + rozgałęzienie CNY/CIF). Wiersze tabeli: cena bazowa → agencja celna →
podstawa cła → cło → transport z portu → podstawa akcyzy → akcyza (z badge paliwa) →
homologacja → podstawa prowizji → prowizja (czerwone "(zast. min.)" jeśli zadziałało
minimum) → podstawa VAT → VAT → suma → zaokrąglenie → **CENA KOŃCOWA** (czerwony
pasek `#dc2626`, 18px bold uppercase).

**Zapis — kolejność operacji w `handleSave()`:**

1. Guards: nonce (`asiaauto_listing_editor_save`), `wp_is_post_revision`, `wp_is_post_autosave`,
   `DOING_AUTOSAVE`, `post_type === 'listings'`, `current_user_can('edit_post', $post_id)`
2. `remove_action('save_post_listings', 'handleSave', 20)` — anti-recursion guard dla `wp_update_post()` poniżej
3. Taksonomie: pętla po `TAXONOMIES` + fuel — `setTermBySlug($post_id, $tax, $slug)` **strict**
   (slug musi istnieć w DB, inaczej clear + warning w logu). Zero auto-create — sprzedawca
   nie może stworzyć nowej Marki/Modelu wpisaniem w formularzu. Zwraca nazwę termu
   (dla `buildListingSlug()`). Dual storage: `wp_set_object_terms()` + `update_post_meta($tax, $slug)`.
4. Meta: parsowanie z `wp_unslash($_POST['asiaauto_editor'])`, sanityzacja, `update_post_meta()`.
   `_asiaauto_cif_price_pln` ustawiane tylko gdy `$cif > 0` (inaczej `delete_post_meta`).
5. Audit przy pierwszym zapisie (`_asiaauto_manual_entry != '1'`):
   `_asiaauto_manual_entry = '1'` + `_by` = user_id + `_at` = `current_time('mysql')`
6. Walidacja miękka → transient + force draft
7. Slug przez `AsiaAuto_Importer::buildListingSlug(mark, model, year, post_id)` **tylko
   przy pierwszym zapisie** (edycja marki/modelu nie zepsuje SEO po zmianie nazwy)
8. `AsiaAuto_Price::applyToListing($post_id)` (auto-detect MODE B po `_asiaauto_cif_price_pln > 0`)
9. `add_action('save_post_listings', 'handleSave', 20, 3)` — re-attach

**Duplikacja** (`handleDuplicate()`):
- `check_admin_referer(DUP_ACTION . '_' . $post_id)` + cap `edit_posts`
- `wp_insert_post()` z `post_status=draft`, `post_title='[Kopia] ' . $orig->post_title`, author=current user
- Kopia wszystkich meta z `get_post_meta($post_id)` **poza blocklistą** `DUP_BLOCKED_META`
- Kopia wszystkich termów z `get_object_taxonomies('listings')` → `wp_set_object_terms($new_id, $ids, $tax)`
- **BEZ galerii i featured image** — `gallery` + `_thumbnail_id` w blockliście,
  `set_post_thumbnail()` nie wywoływane. Powód: oszczędność miejsca i brak ryzyka
  cascading delete (usunięcie zdjęcia z kopii w Kroku 3 nie ruszy pliku oryginału).
  Sprzedawca wgrywa własne zdjęcia w metaboksie galerii (Krok 3).
- Redirect do `post.php?action=edit&post=NEW_ID&asiaauto_dup=1` → success notice

**Blocklist meta w duplikacji** (`DUP_BLOCKED_META`):
```
_asiaauto_inner_id, _asiaauto_source, _asiaauto_url,
_asiaauto_original_price, _asiaauto_cif_price_pln,
_asiaauto_price_breakdown, _asiaauto_price_calculated_at,
_asiaauto_last_sync, _asiaauto_description_status,
_asiaauto_manual_entry, _asiaauto_manual_entry_by, _asiaauto_manual_entry_at,
price, sale_price, vin_number, mileage, registration_date,
gallery, _thumbnail_id, _edit_lock, _edit_last
```

Czyli: zdjęcia, cena, identyfikatory źródła, VIN, przebieg, data rejestracji, audit flag.
Zostaje: opis, taksonomie, kolory, moc, lokalizacja, pojemność, complectation, owners,
equipment, extra_prep. Sprzedawca ma szkielet ogłoszenia i poprawia indywidualne pola.

**Audit banner w metaboksie** — na górze, dwa warianty:

| Warunek | Banner |
|---------|--------|
| `_asiaauto_manual_entry === '1'` | Czerwony border, "Ogłoszenie ręczne — dodane przez {user} · {timestamp}" |
| `_asiaauto_inner_id !== ''` | Niebieski border, "Ogłoszenie z importu — inner_id: X. Edycja tutaj nie nadpisze danych — następny sync z auto-api przywróci pola z API" |

### Iteracje Kroku 2 (changelog sesji, 2026-04-10)

Krok 2 przeszedł 5 iteracji na podstawie feedbacku klienta podczas sesji:

| # | Problem | Fix |
|---|---------|-----|
| 1 | **Gutenberg chował metabox** w zwijanej sekcji "Metaboksy" na dole strony, domyślne metaboxy taksonomii dalej widoczne w sidebarze (dwa miejsca do edycji marki). | `use_block_editor_for_post_type` → false dla `listings` (classic editor). `remove_meta_box('{tax}div', …)` dla wszystkich 10 taksonomii na prio 20. |
| 2 | **`formatBreakdown()` zwracał tekst** z `─────` separatorami, które po zrenderowaniu jako HTML zlewały się w jedną linię — wyglądało jak brzydki dump. | Nowa prywatna metoda `renderBreakdownHtml($result)` — HTML tabela server-side, wzorowana na `aa-mi-table` z manual importu, zaktualizowana dla pipeline'u v0.28.0 i obu trybów CNY/CIF. CSS `.aa-breakdown` inline w metaboksie: max-width 640px, `#fffbeb` nagłówki, `#fde68a` borders, czerwony `#dc2626` wiersz końcowy. |
| 3 | **Przycisk "Wygeneruj tytuł" był u dołu metaboxa**, ale tytuł strony jest u góry — sprzedawca nie widział efektu po kliknięciu (przycisk 2 ekrany od pola Tytuł). | Przycisk usunięty z metaboksa. JS wstrzykuje go jako `div` pod `#titlewrap` (natychmiast po polu Tytuł) już przy inicjalizacji skryptu w metaboksie. |
| 4 | **Taksonomie były jako `<input list="...">` + `<datalist>`** — datalist pozwalał na wolny tekst, co przy najmniejszym błędzie literówki tworzyło nowe termy ("AITO2222" jako nowa marka). **Największy błąd designu w sesji.** | Wszystkie 9 taksonomii → `<select>` strict ze slugiem w `value`, nazwą w tekście opcji. `setTermBySlug()` strict — jeśli slug nie istnieje w DB → clear + log warning. Metoda `setTermByName()` wywalona. Link "Zarządzaj: X ↗" pod każdym selectem do `edit-tags.php` w nowej karcie — jeśli sprzedawca potrzebuje nowej marki, idzie do osobnego ekranu zamiast tworzyć przypadkiem przez literówkę. |
| 5 | **Duplikacja kopiowała galerię + featured image** — oba listingi trzymały te same attachment ID (ryzyko cascading delete w Kroku 3 + wizualnie wyglądało jakby zdjęcia były zduplikowane w Mediach). | `gallery` + `_thumbnail_id` dołożone do `DUP_BLOCKED_META`. `set_post_thumbnail()` usunięte z `handleDuplicate()`. Kopia wchodzi z pustą galerią — sprzedawca wgrywa własne zdjęcia w Kroku 3. |

Finalny stan: wszystkie 9 taksonomii jako strict `<select>`, Gutenberg off, metabox
w głównej kolumnie classic editora, sidebar czysty (tylko Opublikuj + Wyróżniający obraz),
HTML-owa tabela breakdownu, przycisk gen title pod #titlewrap, duplikacja bez zdjęć.

### Modified files (Krok 2)

| File | Changes |
|------|---------|
| `includes/class-asiaauto-listing-editor.php` | **NEW** — ~820 linii. Pełna klasa: metabox render + save handler + duplicate + AJAX preview + helpers (`setTermBySlug`, `getCurrentTermSlug`, `parseFloat`, `renderBreakdownHtml`). Inline CSS (grid, field styles, breakdown table). Inline JS (gen title injection pod #titlewrap, AJAX preview fetch, select text reader). |
| `asiaauto-sync.php` | Bump `ASIAAUTO_VERSION` → `0.29.0-wip`. `require_once includes/class-asiaauto-listing-editor.php`. `new AsiaAuto_Listing_Editor()` wewnątrz `if (is_admin())`. |

### Deployment workflow used (Krok 2)

1. MCP `read_file` na `asiaauto-sync.php` + `includes/class-asiaauto-cpt.php` — weryfikacja taksonomii i bootstrap patternu
2. Generacja pełnego pliku `class-asiaauto-listing-editor.php` + zaktualizowanego `asiaauto-sync.php` jako artefakty
3. `php -l` na obu plikach (PASS)
4. Klient wgrywa FTP-em, refresh listings edit screen
5. 5 iteracji feedbacku → 5 kolejnych patchy przez `str_replace` (Gutenberg off, HTML table, gen title injection, datalist→select, gallery out of dup) — każdy zakończony `php -l`
6. Klient potwierdza działanie: select-y marek strict, metabox w głównej kolumnie, HTML tabela breakdownu, duplikacja bez zdjęć

### Otwarte kwestie po Kroku 2

- **Testowe duplikaty z iteracji #4** — w DB zostały termy `make/aito2222` + potencjalnie `serie/aito-m9-222` stworzone przed fixem datalist→select. Usunięcie: panel WP Ogłoszenia → Marki/Modele → znajdź → Usuń. Alternatywnie `wp term delete make aito2222 --force` przez SSH. Listingi które wisiały na tych termach rozłączą się (taksonomia pusta, przy następnym zapisie w metaboksie sprzedawca wybierze prawdziwe termy z listy).
- **Primaauto seller role i cap `edit_posts`** — zweryfikowane, sprzedawca ma dostęp do metaboksa i do AJAX previewu (`current_user_can('edit_posts')` przechodzi). Brak regresji.

**Krok 3 ✅ DONE (2026-04-10):** Metabox galerii. Patrz osobna sekcja niżej.

**Krok 4 (pending):** Metabox extra_prep. 18 zakładek z kategoriami z `data/translations-extra-prep.php`.
Zapis do `_asiaauto_extra_prep` jako flat JSON z angielskimi kluczami i polskimi wartościami
(translator ma passthrough dla polskiego). Render spójny z tym co na single listing.

---

### Krok 3 — `class-asiaauto-gallery-metabox.php` ✅ DONE (2026-04-10)

Metabox galerii zdjęć dla listings CPT. Drag&drop upload, auto-konwersja do WebP,
SEO naming spójne z importerem, reorder przez drag&drop, toggle featured image,
delete z cascading file removal. Wszystko przez AJAX bez reloada strony.

**Architektura:**
- Klasa `AsiaAuto_Gallery_Metabox` rejestruje metabox na hooku `add_meta_boxes_listings`
  priority 15 (po Listing Editor priority 10), context `normal`, priority `high` —
  renderuje się w głównej kolumnie edytora pod "Dane pojazdu"
- Capability check: `current_user_can('edit_post', $id)` + `current_user_can('upload_files')`
- 4 AJAX endpoints:
  - `wp_ajax_asiaauto_gallery_upload` — multi-file `$_FILES` upload
  - `wp_ajax_asiaauto_gallery_delete` — `wp_delete_attachment(force:true)` + meta update
  - `wp_ajax_asiaauto_gallery_reorder` — persist drag&drop order
  - `wp_ajax_asiaauto_gallery_set_featured` — move attachment to front of gallery

**Reuse `AsiaAuto_Media`:** dołożona publiczna metoda `storeLocalUpload(int $post_id, string $tmp_path, string $orig_name, array $listing, int $n, int $total): ?int`.
Wewnątrz używa prywatnych helperów klasy (`getUploadDir`, `buildSeoBaseName`, `buildSeoTitle`,
`detectMime`, `uniquePath`) — spójne SEO naming z importerem Dongchedi. **Additive change —
zero zmian w istniejących metodach `downloadAndStore()`/`downloadSingleImage()`**, czyli
zero ryzyka regresji w imporcie.

Flow `storeLocalUpload()`:
1. Walidacja istnienia pliku tmp
2. MIME whitelist: `image/jpeg`, `image/png`, `image/webp`, `image/gif`
3. Budowa SEO filename: `{mark}-{model}-{year}-{city_pl}-{post_id}-{n}.webp`
   (ręczne ogłoszenia używają `post_id` jako `inner_id` substytut)
4. Konwersja do WebP przez `wp_get_image_editor()->save($path, 'image/webp')` quality 85
   (JPG ~2MB → WebP ~400KB, spójnie z importerem)
5. `wp_insert_attachment($args, $dest_path, $post_id, true)` — parent = listing post
6. `wp_generate_attachment_metadata()` — generuje sub-sizes `asiaauto-card`,
   `asiaauto-thumb`, `asiaauto-gallery`
7. Alt text: `"{SEO Title} — zdjęcie {n} z {total}"`
8. Audit meta: `_asiaauto_manual_upload=1`, `_asiaauto_manual_upload_orig={orig_name}`

**Security layers w każdym AJAX endpoincie:**
1. `check_ajax_referer('asiaauto_gallery_metabox', 'nonce')`
2. `current_user_can('edit_post', $post_id)` — własność listingu
3. `current_user_can('upload_files')` — tylko na upload
4. `is_uploaded_file($tmp_path)` — blokuje injection dowolnej ścieżki do PHP filesystem
5. `UPLOAD_ERR_OK` per plik
6. Max 10 MB per plik, max 15 plików per galeria (spójne z `AsiaAuto_Media::$max_images`)
7. MIME whitelist server-side (klient mógłby ominąć JS-owy `accept`)
8. Delete/reorder/set-featured sprawdzają `$attachment->post_parent === $post_id` —
   nie można manipulować cudzymi załącznikami

**Gallery meta format:** `gallery` = array of int attachment IDs, pierwszy zawsze = featured.
`_thumbnail_id` zawsze === `gallery[0]` (twarda synchronizacja).

**UI (inline CSS + JS):**
- Dropzone z `is-dragover` state (czerwony border #dc2626)
- CSS grid `repeat(auto-fill, minmax(150px, 1fr))`, aspect 4/3
- Numerki pozycji (1, 2, 3...) aktualizowane po każdej zmianie kolejności
- Featured: żółta ramka (#e8ac07) + box-shadow + badge "GŁÓWNE"
- Akcje per kafelek: `★` set featured, `×` delete z `confirm()`
- Native HTML5 drag&drop reorder (dragstart, dragover, drop + insert before/after
  na podstawie pozycji X kursora)
- Status bar z spinnerem i komunikatami (is-error / is-success) + auto-fade sukcesów

### Iteracje Krok 3

Po pierwszym deployu zostały wykryte dwa problemy — jeden retroaktywny, jeden aktualny.

| # | Problem | Fix |
|---|---------|-----|
| 1 | **Dongchedi CDN zwrócił wygasłe URL-e zdjęć dla 2 konkretnych listingów (#219460, #219463) importowanych o 16:42.** Analiza `x-expires` w URL-ach: `1775692768` = 2026-04-09 01:59 CEST, co było ~39h **przed** momentem importu. Log: `download_url failed ... Forbidden` (HTTP 403 z CDN). Użytkownik podejrzewał regresję po wgraniu `class-asiaauto-media.php` z Kroku 3. | **Udowodnione że to nie regresja:** `downloadSingleImage()` bit-w-bit identyczne z oryginałem, patch był czysto additive (tylko `storeLocalUpload()`). Tabela timeline porównała: #219431 o 14:28 → 9/9 pobrane (URL ważny +7 dni), #219460 o 16:42 → 0/15 (URL −1.62 dnia przed expiry). Strefy czasowe zgadzają się co do sekundy (serwer CEST UTC+2, MySQL `@@time_zone=SYSTEM`, post_date_gmt identyczne z post_date). **Root cause:** API auto-api.com zwróciło stare (nieważne) URL-e z cache'u dla tych konkretnych `inner_id`. Po pewnym czasie API samo wróciło do serwowania świeżych — kolejne importy przyszły ze zdjęciami. Recovery opcja: `wp eval` one-liner wołający `$api->getOffer()` + `$media->downloadAndStore()` dla wymuszenia świeżych URL-i. |
| 2 | **Featured image nie ustawia się po uploadzie zdjęć do zduplikowanego listingu** (`_thumbnail_id = NULL` mimo `gallery` z 3 elementami). Post #219455 i #219577 w tym samym stanie. Objaw usera: "nie pokazuje się na liście samochodów po publikacji" — Motors theme filtruje po `_thumbnail_id`, kafelek bez miniaturki nie jest renderowany. | **Root cause:** `set_post_thumbnail($post_id, $id)` w WordPress core ma wewnętrzny check `wp_get_attachment_image($id, 'thumbnail')` który przy pustym wyniku wywołuje `delete_post_meta('_thumbnail_id')` zamiast `update_post_meta`. W kontekście tuż po `wp_generate_attachment_metadata()` (ten sam request AJAX) WP object cache może jeszcze nie mieć wygenerowanego HTML miniaturki — szczególnie dla WebP pod Motors theme. **Fix w 2 miejscach:** (a) wszystkie 4 AJAX endpointy (`ajaxUpload`, `ajaxDelete`, `ajaxReorder`, `ajaxSetFeatured`) używają raw `update_post_meta('_thumbnail_id', ...)` / `delete_post_meta('_thumbnail_id')` zamiast `set_post_thumbnail()` / `delete_post_thumbnail()` — omijając wewnętrzny check WP. (b) `renderMetabox()` dostała **self-healing logikę**: jeśli `gallery` ma elementy a `_thumbnail_id` ≠ `gallery[0]`, naprawia to natychmiast w DB i loguje `[gallery] render: self-heal featured image for post #X → #Y`. Dla istniejących zepsutych listingów wystarczy otworzyć edycję — fix odpala się automatycznie. Dodatkowo: `ajaxUpload` loguje każdą zmianę thumbnail linią `[gallery] upload: post #X thumbnail → #Y (was #Z, added N files)`. |

**Bonus — "dziwna metoda wybierania featured" w renderMetabox (usunięta w iteracji #2):** pierwsza wersja renderu miała defensywny reorder lokalnej zmiennej `$gallery` (jeśli `_thumbnail_id` nie był pierwszy, wrzucał go na front tylko na potrzeby HTML, bez zapisu do DB). Intencja była kosmetyczna — zabezpieczenie przed sytuacją gdy ktoś ustawi featured ręcznie przez klasyczny WP panel. W praktyce: czysty reorder prezentacji nie pomagał na realny bug `_thumbnail_id = NULL` (warunek `if ($featured_id > 0)` nie wchodził), a usera wprowadzał w błąd że logika featured jest skomplikowana. Zastąpione twardą synchronizacją gallery[0] → `_thumbnail_id` z auto-fixem DB.

### Modified files (Krok 3)

| File | Changes |
|------|---------|
| `includes/class-asiaauto-media.php` | **Modified** — dodana publiczna metoda `storeLocalUpload()` (~100 linii) przed sekcją "Existing methods". Zero zmian w `downloadAndStore()`, `downloadSingleImage()`, `downloadToTemp()`, helperach (`getUploadDir`, `buildSeoBaseName`, itd.). |
| `includes/class-asiaauto-gallery-metabox.php` | **NEW** — ~720 linii. Klasa `AsiaAuto_Gallery_Metabox` z render metabox (inline CSS + JS ~350 linii), 4 AJAX endpoints (~300 linii), helper `buildListingData()`. Self-healing w renderze, raw meta updates w AJAX. |
| `asiaauto-sync.php` | `require_once includes/class-asiaauto-gallery-metabox.php`. `new AsiaAuto_Gallery_Metabox()` wewnątrz `if (is_admin())` po `new AsiaAuto_Listing_Editor()`. |

### Deployment workflow (Krok 3)

1. MCP `read_file` na `class-asiaauto-media.php` — weryfikacja istniejącego kodu, helperów, pattern SEO naming
2. Generacja 3 plików jako artefakty (media + gallery-metabox + sync bootstrap)
3. `php -l` na wszystkich trzech (PASS)
4. Klient wgrywa FTP-em (18:39)
5. Iteracja #1: fałszywy alarm regresji importera — tabela timeline + analiza stref czasowych + porównanie z serwerowym plikiem (identyczny = additive) udowodniły że to zewnętrzny problem Dongchedi CDN. Pozostawione bez patcha kodu.
6. Iteracja #2: realny bug `_thumbnail_id = NULL` — identyfikacja przez SQL (`postmeta` dla #219455 fizycznie bez `_thumbnail_id`, mimo że `gallery` ma 3 elementy). Hipoteza o `set_post_thumbnail()` wewnętrznym check → fix przez raw meta ops + self-heal w renderze. Patch przez `str_replace`.
7. Klient wgrywa fix, test na #219577 (wcześniej zepsuty ten sam sposób) — otwarcie edycji auto-naprawia `_thumbnail_id` przez self-heal, listing od razu pojawia się na liście samochodów. Potwierdzone działanie.

### Otwarte kwestie po Kroku 3

- **Testowe duplikaty #219444, #219447, #219450** — stare kopie sprzed fixu z Kroku 2 (gdzie `DUP_BLOCKED_META` jeszcze nie zawierało `gallery`+`_thumbnail_id`). Wszystkie w koszu, można je opróżnić lub zostawić — nie wpływają na produkcję.
- **Testowy term `make/aito2222`** z iteracji Kroku 2 — dalej w DB, usunięcie opcjonalne (patrz sekcja "Otwarte kwestie po Kroku 2").
- **Defensive patch na `downloadToTemp()`** (opcjonalnie) — dodanie przeglądarkowego User-Agent + Referer + retry z backoff na HTTP 403/429. Zapobiegałoby przyszłym incydentom z Dongchedi CDN rate-limitingiem. Nie wgrane — niski priorytet, bo awaria Dongchedi była jednorazowa.

### TODO — pozostały Krok 4

**Krok 4 (pending):** Metabox extra_prep. 18 zakładek z kategoriami z `data/translations-extra-prep.php`.
Zapis do `_asiaauto_extra_prep` jako flat JSON z angielskimi kluczami i polskimi wartościami
(translator ma passthrough dla polskiego). Render spójny z tym co na single listing page
(sprawdzić jak frontend renderuje extra_prep z importowanych listingów — prawdopodobnie
JSON decode + foreach groups → foreach items).

### Backward compatibility (Krok 1)

- Importer Dongchedi — bez zmian, `applyToListing()` dalej działa dla MODE A (zweryfikowane
  na produkcji: sync po podmianie pliku leci bez błędów, nowe listingi importowane, istniejące
  aktualizowane przez `updateListing()` → `applyToListing()`)
- Istniejące breakdowny w meta `_asiaauto_price_breakdown` (bez `input_mode`) — `formatBreakdown()`
  domyślnie traktuje brak pola jako `'cny'`, więc stare struktury renderują się bez błędu
- `bulkRecalculate()` — sygnatura bez zmian, nowe pola w zwracanym stats są addytywne
- `saveConfig()` / `getConfig()` — bez zmian, config cen ten sam co v0.28.0
- Admin UI (`class-asiaauto-admin.php`) — bez zmian, nie wymaga update (Krok 1 dotyka tylko
  `class-asiaauto-price.php`)

### Known discrepancies (Krok 1)

- **Plugin version mismatch:** `asiaauto-sync.php` header nadal pokazuje `Version: 0.28.0`
  i `ASIAAUTO_VERSION = '0.28.0'`, ale `class-asiaauto-price.php` ma `@since 0.29.0` w docblocku
  i nowe metody. Bump wersji → 0.29.0 przy następnym patchu (wdrożenie Kroku 2), żeby nie
  mnożyć podmian plików. Funkcjonalnie nie ma to wpływu — wersja w headerze nie jest nigdzie
  sprawdzana jako guard.

## v0.28.0 — Price pipeline reorder + agencja celna + VAT na końcu (2026-04-10)

Pełna przebudowa pipeline'u kalkulatora cen wg nowych wytycznych klienta.
Zmiana kolejności kroków, dodanie nowej pozycji "Agencja celna / wyładunek",
przeniesienie VAT 23% z kroku 5 na koniec (krok 9), liczony od pełnej sumy z prowizją.

### Pipeline change (v0.18.0 → v0.28.0)

**Stary pipeline (v0.18.0):**
```
bazowa → +transport_ubezp → +cło% → +akcyza% → +VAT% → +transport_kraj → +rej_homol → +prowizja → zaokr
```

**Nowy pipeline (v0.28.0):**
```
bazowa → +chiny_rejestracja_transport → +agencja_celna_wyladunek → +cło% → +transport_z_portu
→ +akcyza% → +homologacja_detailing → +prowizja → +VAT% → zaokr
```

Kolejność krok-po-kroku z domyślnymi wartościami:

| # | Krok | Domyślnie | Operacja |
|---|------|-----------|----------|
| 1 | Cena bazowa PLN | — | `cena_cny × kurs` |
| 2 | Chiny / rejestracja / transport / ubezpieczenie | 18 000 zł | dodaj |
| 3 | Agencja celna / wyładunek | 2 600 zł | dodaj |
| 4 | Cło 10% | 10% | `(1+2+3) × 10%` |
| 5 | Transport z portu | 3 400 zł | dodaj |
| 6 | Akcyza (per fuel) | 3.1% petrol/diesel | `(1+2+3+4+5) × stawka` |
| 7 | Homologacja / detailing / rejestracja / ubezpieczenie | 1 500 zł | dodaj |
| 8 | Prowizja | max(10%, 10 000 zł) | `(1..7) × % lub min` |
| 9 | VAT 23% | 23% | `(1..8) × 23%` |
| — | Zaokrąglenie | 1 000 zł | w górę |

### Kluczowe różnice koncepcyjne

1. **Dodano nową pozycję "Agencja celna / wyładunek"** (krok 3) — wcześniej koszt
   ten był wliczony do innych pozycji, teraz osobny.
2. **VAT przeniesiony na koniec** — w v0.18.0 VAT był liczony od `(wartość celna + cło + akcyza)`
   w kroku 5, zgodnie z polskim prawem (art. 83 ustawy o VAT). W v0.28.0 VAT 23% liczony
   jest od pełnego subtotalu (1..8), włącznie z prowizją, transportem i homologacją.
   To **NIE jest klasyczny VAT importowy** — to model "VAT od ceny pod klucz" zgodny z
   interpretacją biznesową klienta. Decyzja świadoma, potwierdzona przez klienta 2026-04-10.
3. **Transport "z portu" przesunięty przed akcyzę** (krok 5) — wcześniej był po VAT
   jako "transport krajowy". Wpływa na podstawę akcyzy (powiększa ją o transport).
4. **Akcyza ma większą podstawę** — teraz `(1+2+3+cło+transport_z_portu)` zamiast
   `(1+2+cło)`. Dla benzyny 3.1% różnica to ok. +200 zł na ogłoszenie.

### Renamed config keys + lazy migration

Trzy stare klucze configa zostały zastąpione nowymi, semantycznie poprawnymi nazwami:

| Stary klucz (v0.18.0)       | Nowy klucz (v0.28.0)            | Nowy default |
|-----------------------------|---------------------------------|--------------|
| `transport_ubezpieczenie`   | `chiny_rejestracja_transport`   | 18 000 zł    |
| `transport_krajowy`         | `transport_z_portu`             | 3 400 zł     |
| `rejestracja_homologacja`   | `homologacja_detailing`         | 1 500 zł     |

Dodatkowo nowy klucz: `agencja_celna_wyladunek` (default 2 600 zł).

`AsiaAuto_Price::getConfig()` zawiera `LEGACY_KEY_MAP` — jeśli nowy klucz nie istnieje
w bazie, ale stary istnieje, jest mapowany. Pozwala to przejść z v0.27.0 → v0.28.0
bez migracji bazy. Pierwszy zapis konfiguracji z admina (lub `saveConfig()` programowo)
nadpisuje stare wartości nowymi kluczami i de facto zamyka migrację.

### New breakdown JSON fields

W stosunku do v0.18.0 dodane:
- `chiny_rejestracja_transport` (zastępuje `transport_ubezpieczenie`)
- `agencja_celna_wyladunek` ← NEW
- `transport_z_portu` (zastępuje `transport_krajowy`)
- `homologacja_detailing` (zastępuje `rejestracja_homologacja`)
- `podstawa_prowizji` (suma 1..7 — podstawa do liczenia 10% prowizji)

`subtotal` pozostawiony dla kompat (= `suma_przed_zaokr`). `podstawa_vat` to suma 1..8.

Stare breakdowny w meta `_asiaauto_price_breakdown` zostają w bazie aż do bulk recalc —
parsują się bez błędów, ale brak im nowych kluczy. Pierwszy `wp asiaauto recalculate-prices`
(albo "Zaktualizuj wszystkie ceny teraz" w adminie) nadpisuje wszystkie listingi nową strukturą.

### Admin UI changes

`renderPriceTab()` w `class-asiaauto-admin.php` — pełna przebudowa układu:

- 9 numerowanych pól w nowej kolejności + zaokrąglenie (było 9 pól w starej kolejności)
- Nazwy etykiet wg klienta: "Chiny / rejestracja / transport / ubezpieczenie",
  "Agencja celna / wyładunek", "Transport z portu", "Homologacja / detailing / rejestracja / ubezpieczenie"
- Opisy pól wyjaśniają na czym liczony jest każdy procent (cło, akcyza, prowizja, VAT)
- **Horyzontalna tabela akcyzy** — 9 paliw w kolumnach (Benzyna, Diesel, HEV, MHEV, PHEV, EV,
  EREV, CNG, Bi-fuel). Pod każdym paliwem input ze stawką + licznik ogłoszeń (`X ogł.`).
  Zwarta forma — zajmuje ~150px wysokości zamiast 9 wierszy po 35px = 315px.
- `renderPricePreview()` — 14 kolumn w kolejności pipeline'u, używa nowych kluczy breakdownu.
  Min-width 1600px, font 12px żeby się wszystko zmieściło.

`handleSave()` — sanityzuje 11 nowych kluczy (4 nowe + 7 starych zachowanych) zamiast 11 starych.

### Rationale — VAT od pełnej sumy

Model klienta zakłada że VAT 23% jest doliczany na końcu jako podatek od "całości"
(pod klucz), nie jako klasyczny VAT importowy. W praktyce oznacza to wyższy efektywny VAT
niż przy klasycznym imporcie:

- **Klasyczny VAT importowy (v0.18.0):** podstawa = bazowa + transport + cło + akcyza
- **VAT klienta (v0.28.0):** podstawa = wszystko powyżej + transport_z_portu + homologacja + prowizja

Dla EV (akcyza 0%, kurs 0.57, CNY 200 000) różnica ok. +5 000 zł VAT na ogłoszenie. Klient świadomy.

### Test results (przykład Xiaomi SU7 RWD electric, CNY 189 800, kurs 0.57)

| Krok | Wartość |
|------|---------|
| 1. Bazowa | 108 186 zł |
| 2. + Chiny | 18 000 zł |
| 3. + Agencja | 2 600 zł |
| podstawa cła | 128 786 zł |
| 4. Cło 10% | 12 879 zł |
| 5. + Transport | 3 400 zł |
| podstawa akcyzy | 145 065 zł |
| 6. Akcyza 0% (EV) | 0 zł |
| 7. + Homologacja | 1 500 zł |
| podstawa prowizji | 146 565 zł |
| 8. Prowizja 10% | 14 656 zł |
| podstawa VAT | 161 221 zł |
| 9. VAT 23% | 37 081 zł |
| Suma | 198 302 zł |
| **Cena końcowa** | **199 000 zł** |

Vs v0.18.0 dla tego samego ogłoszenia: 182 000 zł → +17 000 zł.

### Modified files

| File | Changes |
|------|---------|
| `class-asiaauto-price.php` | Pełna przebudowa: nowe `defaults()`, nowy `calculate()` (9-step pipeline w nowej kolejności + VAT na końcu), `LEGACY_KEY_MAP` const, lazy migration w `getConfig()`, walidacja 11 kluczy w `saveConfig()`, nowa struktura breakdown JSON, `formatBreakdown()` z numerowanymi krokami 1-9 |
| `class-asiaauto-admin.php` | `handleSave()` — sanityzacja nowych kluczy. `renderPriceTab()` — 9 nowych pól w nowej kolejności + horyzontalna tabela akcyzy (paliwa w kolumnach). `renderPricePreview()` — 14 kolumn pipeline'u, nowe klucze breakdownu, min-width 1600px |
| `asiaauto-sync.php` | Version bump 0.27.0 → 0.28.0 (header pluginu + `ASIAAUTO_VERSION` const) |

### Backward compatibility

- `applyToListing()` / `bulkRecalculate()` / `getBreakdown()` — sygnatury bez zmian, kod wywołujący w importerze i sync nie wymaga modyfikacji
- `formatBreakdown()` — sygnatura bez zmian, output zaktualizowany wizualnie
- Stare klucze `asiaauto_price_config` w wp_options — automatycznie mapowane przez `LEGACY_KEY_MAP` w `getConfig()` aż do pierwszego zapisu z admina
- Stare breakdowny w `_asiaauto_price_breakdown` meta — parsują się bez błędów, ale są nadpisywane przy pierwszym bulk recalc
- `class-asiaauto-contract.php` (PDF kontraktu) — używa tylko `price_final` z order meta, nie breakdownu, więc bez zmian
- Order module (`_order_price_breakdown` w meta zamówień) — używa **kopii** breakdownu zapisanego w momencie tworzenia zamówienia, więc historyczne zamówienia zachowują strukturę z dnia utworzenia

### Deployment workflow used

1. `class-asiaauto-price.php` wgrany na serwer → weryfikacja przez MCP `read_file`
2. Test panelu admina — wyświetla się ze starymi polami (lazy migration działa, brak fatal errora)
3. `class-asiaauto-admin.php` wgrany → nowe UI z 9 polami + horyzontalna tabela akcyz
4. Klient ustawił wartości: 18000 / 2600 / 3400 / 1500 → "Zapisz konfigurację cen"
5. Podgląd 10 listingów z porównaniem old vs new → akceptacja
6. "Zaktualizuj wszystkie ceny teraz" → bulk recalc ~6000 listingów
7. Weryfikacja na frontendzie

## Known Issues (2026-04-10)

### ✅ RESOLVED: Chinese characters in listing titles

Fixed in v0.14.0 session (2026-04-02). Was 51 listings with Chinese in titles.
Fully resolved in v0.23.0 (2026-04-04). Was 111 listings with Chinese (titles + model terms).

**v0.14.0 (2026-04-02):**
- `translations-complectations.php` — added 18 terms (+16 complectation + `激光` LiDAR + `卓越` Excellence)
- `translations-extra-prep.php` — added 15 battery brands (CALB, CATL Jiangsu, Gotion, EVE Power, Farasis, etc.), electric layout standalone values (`后置`→Tył, `前置`→Przód), body struct (`旅行车`→kombi)
- `class-asiaauto-translator.php` — fix standalone `快充X小时` pattern (was only handling combined 快充+慢充)
- `diag/fix-chinese-models.php` — added 4 new terms: `享界S9T`→S9T, `钛7 PHEV`→Tai 7 PHEV, `领克10 EM-P`→10 EM-P, `风云T11`→T11
- `wp asiaauto fix-titles` — 48 complectation fixes + 9 normalization
- `fix-chinese-models.php` — 21 term renames, 45 title fixes
- **Result:** 51 → 0 Chinese titles (2 remaining are `eπ008` with Greek π, not Chinese)

**v0.23.0 (2026-04-04) — complete pipeline fix:**
- `data/translations-models.php` — NEW: 35 model translations (ZH → EN, brand-stripped)
- `data/translations-complectations.php` — added 24 segments (暗夜骑士→Dark Knight, 科技→Tech, 魔方→Cube, etc.)
- `class-asiaauto-translator.php` — NEW: `translateModel()` method, ASCII passthrough
- `class-asiaauto-importer.php` — calls `translateModel()` before title build + `setTaxonomies()`
- `diag/fix-chinese-v23.php` — combined fixer: 12 model terms renamed, 113 titles fixed
- **Result:** 111 → 0 Chinese titles, 7 → 0 Chinese model terms. Pipeline prevents future recurrence.

### ✅ RESOLVED: Stale listings from old filter config

Fixed in v0.19.0 (2026-04-03). ~3 249 listings from non-matching brands and year 2023
purged via `wp asiaauto purge-outdated`. 4 listings with active orders protected.

### ✅ RESOLVED: zakonczone didn't clear listing reservation

Fixed in v0.21.0 (2026-04-03). `changeStatus()` now calls `clearListingReservation()` for both
`zakonczone` and `anulowane`. Previously only `anulowane` cleared — listings from completed orders
stayed on `/w-rzeszowie/` forever. Stale data cleaned by `migrate-v21.php`.

### ✅ RESOLVED: zakupione mapped to reserved instead of in_transit

Fixed in v0.21.0 (2026-04-03). `LISTING_RESERVATION_MAP` now maps `zakupione → in_transit`.
Car appears on `/w-drodze/` immediately after purchase, not stuck with just a badge on main listing.

### ✅ RESOLVED: Signed contract auto-status change without confirmation

Fixed in v0.22.0 (2026-04-03). Upload podpisanej umowy automatycznie zmieniał status na `podpisane`
bez możliwości poprawy/usunięcia pliku. Klient nie mógł wrzucić wielu stron (JPEG).
Now: multi-file upload + explicit "Potwierdzam przesłanie umowy" button.

### ✅ RESOLVED: Admin regenerate contract didn't revert signed status

Fixed in v0.22.0 (2026-04-03). "Regeneruj" na statusie `podpisane` nie cofał statusu —
klient miał starą wersję umowy na dysku. Now: auto-revert to `umowa_gotowa`, delete signed files,
custom email `contract_regenerated` to customer.

### Open: Orphaned taxonomy terms

After purge-outdated, empty terms remain (Li Auto, Volvo, NIO, AITO etc. with count=0).
Frontend hides them (`hide_empty`). Low priority — can clean with `wp term delete` if needed.

### Open: Lynk & Co HTML entity duplication

DB has two `make` terms: `Lynk & Co` and `Lynk &amp; Co`.
Low priority — all Lynk & Co listings removed by purge, no active orders reference them.
Can merge with `wp term delete` when convenient.

### Open: Data quality — mismatched brand/model

`Li Auto` + model `Roewe i6 Classic` — API returns `荣威i6经典` under Li Auto brand.
Root cause: auto-api.com data error. Most Li Auto listings removed by purge.

### Open: Chinese in non-title meta (not user-visible in titles)

- `stm_car_location` — ~5% listings with untranslated Chinese cities
- `additional_features` — ~25% with Chinese equipment text
- `_asiaauto_city`, `_asiaauto_address`, `_asiaauto_seller`, `_asiaauto_region` — raw Chinese (internal, by design)

### ✅ RESOLVED: Order admin capability (v0.24.0 → v0.25.0)

Verified 2026-04-05. Main menu + handleActions use ORDER_CAP. Config submenu intentionally manage_options.

### ✅ RESOLVED: Broken extra_prep JSON (93 listings)

Fixed in v0.25.0 (2026-04-05). wp_unslash stripped escape chars from JSON values
containing literal quotes (DJI drone system descriptions). 93 listings fixed.
Importer patched with wp_slash(), single page has fallback state machine parser.

### Open: No SEO plugin

No Rank Math / Yoast installed. Meta title/description uses WP defaults.
Schema.org added manually: homepage (AutoDealer + WebSite), single (Car), contact (LocalBusiness + OpeningHoursSpecification).
Inventory has no schema. Open Graph tags missing. WP sitemap at /wp-sitemap.xml.

## Backlog (updated 2026-04-10)

Planned work for v0.26+, grouped by complexity and priority.

### GRUPA 1-2 — Shortcody + Bugfix (all done)

Tasks #1-6: ✅ DONE (v0.23.0-v0.24.0)

### GRUPA 3 — Dane / import

| # | Task | Status |
|---|------|--------|
| 6 | Model fixer w pipeline importu | ✅ DONE (v0.23.0) |
| 7 | Fix broken extra_prep JSON (93 listings, wp_unslash) | ✅ DONE (v0.25.0) |

### GRUPA 4 — Admin backend

| # | Task | Description | Status |
|---|------|-------------|--------|
| 8 | Edycja listingu w panelu + ręczne dodawanie (primaauto) | Metabox "Dane pojazdu" w głównej kolumnie edytora (context=normal, priority=high — rozwiązuje "pola w sidebar zamiast w głównym oknie"). Wszystkie pola filtrowalne (make/serie/year/fuel/trans/body/drive/colors/condition/mileage) + cenowe (fuel dla akcyzy) + identyfikacyjne (VIN/loc/reg_date/owners/power/complectation) + **cena CIF PLN** w jednym metaboksie. **Taksonomie jako strict `<select>` — zero auto-create** (link "Zarządzaj ↗" do `edit-tags.php` dla nowych termów). Walidacja miękka (draft + admin_notice z listą braków). Ręczne dodawanie przez **duplikację** istniejącego listingu (row action "Duplikuj" → kopia meta+termów **bez zdjęć/inner_id/cen/VIN/mileage/reg_date** → draft → sprzedawca poprawia indywidualne pola). AJAX preview breakdownu ceny z HTML tabelą (`renderBreakdownHtml()` wzorowana na manual import, v0.28.0 pipeline). Gutenberg off, default taxonomy metaboxes removed from sidebar. Przycisk "Wygeneruj tytuł" wstrzykiwany JS-em pod #titlewrap. **Metabox galerii (Krok 3)**: drag&drop upload, auto WebP conversion (quality 85) przez `AsiaAuto_Media::storeLocalUpload()` (additive method, reuse private helperów SEO), 4 AJAX endpoints (upload/delete/reorder/set_featured), raw `update_post_meta('_thumbnail_id', ...)` zamiast `set_post_thumbnail()` (bypass internal DELETE path gdy `wp_get_attachment_image` zwraca empty), self-healing w `renderMetabox` (gallery[0] → `_thumbnail_id` jeśli niespójne), logging `[gallery] upload/render/delete/reorder` do asiaauto-sync.log. Capability `edit_posts` + `upload_files` (admin + primaauto). | WIP (Kroki 2+3/4 DONE v0.29.0-wip, Krok 4 extra_prep TODO) |
| 9 | Rola sprzedawcy `primaauto` | ✅ DONE (v0.24.0) |
| 10 | Anonimizacja panelu WP | ✅ DONE (v0.24.0) |
| 21 | Brandowany ekran logowania `/biuro/` | Self-contained CSS pasujący do homepage (navy/red/amber, Inter, text logo z amber kropką), ukryte "Oparte na WordPressie" + language switcher + backtoblog, generic error message, fix .htaccess (whitelist `load-styles.php`/`load-scripts.php`). | ✅ DONE (v0.27.0) |

### GRUPA 5 — Frontend / Elementor

| # | Task | Status |
|---|------|--------|
| 11 | Strona główna [asiaauto_homepage] | ✅ DONE (v0.25.0) |
| 12 | Fix CSS mobile (admin + frontend safe-area) | ✅ DONE (v0.25.0) |
| 19 | Strona kontakt [asiaauto_contact] | ✅ DONE (v0.26.0) |

### GRUPA 6 — Logika biznesowa

| # | Task | Description | Status |
|---|------|-------------|--------|
| 13 | Sync ceny zamówienie → listing | Decyzja biznesowa. | DISCUSSION |

### GRUPA 7 — SEO (nowe, v0.26+)

| # | Task | Description | Status |
|---|------|-------------|--------|
| 14 | Meta title/desc + Open Graph | Inventory: dynamic meta z filtrem. Single: Car title + price. OG tagi. Opcja: Rank Math lub custom. | TODO |
| 15 | Schema ItemList na inventory | Schema.org ItemList z listingami (paginowana). | TODO |
| 16 | llms.txt | Plik /llms.txt dla AI crawlerów. | TODO |
| 17 | Sitemap weryfikacja | /wp-sitemap.xml: listings CPT, taxonomie, image sitemap. Opcja: Rank Math. | TODO |
| 18 | Homepage CSS → plik zewnętrzny | Wyciągnąć inline CSS do assets/css/asiaauto-homepage.css + wp_enqueue_style. | TODO |
| 20 | Contact CSS → plik zewnętrzny | Wyciągnąć inline CSS z [asiaauto_contact] do assets/css/asiaauto-contact.css + wp_enqueue_style. | TODO |

### GRUPA 8 — Cena (v0.28+)

| # | Task | Description | Status |
|---|------|-------------|--------|
| 22 | Przebudowa pipeline'u cenowego wg klienta | Reorder kroków, nowa pozycja "Agencja celna / wyładunek", VAT 23% na końcu od pełnej sumy z prowizją, horyzontalna tabela akcyz w adminie, lazy migration starych kluczy configa. | ✅ DONE (v0.28.0) |
| 23 | Dual input mode (CNY + CIF PLN) | `AsiaAuto_Price::calculateFromCif()` dla ręcznych ogłoszeń. Pipeline od kroku 3 (agencja celna), kroki 1-2 wyzerowane (już w CIF). Refaktor: wspólny prywatny helper `runPipelineFromStep3()` dla obu trybów. `applyToListing()` + `bulkRecalculate()` auto-detect trybu po meta (`_asiaauto_cif_price_pln` vs `_asiaauto_original_price`). `formatBreakdown()` rozgałęzia nagłówek po `input_mode`. | ✅ DONE (v0.29.0, Krok 1 modułu manual editor) |

### GRUPA 9 — Umowa agency (v0.30+)

| # | Task | Description | Status |
|---|------|-------------|--------|
| 24 | Przebudowa umowy PDF pod model agency Prima-Auto | Separacja 3 bytów finansowych (prowizja w breakdown / wynagrodzenie kontraktowe / depozyt zabezpieczający). Nowe meta `_order_contract_commission_net` + `_order_vin`, nowe configi `contract_commission_percent/min/vat_rate` + `bank_swift` + `bank_name`, nowa sekcja metaboxa „Dane umowy" (zastąpiła „Zaliczka"), pełna przebudowa treści §1–§9 w `class-asiaauto-contract.php` na wersję klienta (umowa zlecenia sprowadzenia samochodu z Chin — test case BYD Song L EV AWD / DongFeng M-HERO, PRIMA-AUTO Ruslan Prima). Zmiana semantyczna: „zaliczka" → „depozyt zabezpieczający" w szablonach maili i etykietach UI (bez zmian formuły wyliczenia). `default_delivery_days` 60→120. Redesign tabu Ustawienia (dwukolumnowy układ z 6 kolorowymi kartami sekcji). **Pipeline cenowy, breakdown, `_order_commission_override` — nietknięte.** Pełny runbook w sekcji „Planned: Contract Rework — Agency Model (Prima-Auto)" na końcu tego pliku. | **IN PROGRESS 4/5** (Krok 1+2+3+4 DONE, Krok 5 pending) |


---

### GRUPA 10 — Załączniki umowy + breakdown rewamp (v0.31+)

| # | Task | Description | Status |
|---|------|-------------|--------|
| 25 | Załącznik nr 1 do umowy PDF | Osobna strona w PDF kontraktu ze specyfikacją pojazdu (marka, model, rok, VIN, kolor, paliwo, skrzynia, przebieg, moc, stan, opcjonalnie miniaturka z galerii). Nagłówek „Załącznik nr 1 do Umowy", referencja do numeru umowy, podpisy u dołu. Dotyczy `class-asiaauto-contract.php` — nowa metoda `renderAttachment1()` wołana w `renderHTML()` z `<pagebreak />` mPDF. | **PLANNED** |
| 26 | Modernizacja breakdownu cenowego (v1 → v2) | Przeprojektowanie struktury `_asiaauto_price_breakdown` z 6 płaskich pozycji (`cena_bazowa_pln`, `transport_ubezpieczenie`, `clo_kwota`, `transport_krajowy`, `rejestracja_homologacja`, `prowizja_kwota`) na 4 etapy zgodne z umową klienta Prima-Auto (etap 1 zakup Chiny USD, etap 2 transport morski USD, etap 3 odprawa PLN, etap 4 transport krajowy PLN) + osobne pole `internal_margin_pln` dla marży wewnętrznej (ukryta przed klientem). Flaga `is_estimate` dla pozycji do korekty aneksem. Snapshot kursu USD/PLN. Migracja v1→v2 lazy przy odczycie, `total_pln` musi pozostać identyczny numerycznie. **Zmiana inwazyjna** — dotyka pipeline'u w `class-asiaauto-price.php`, admin `renderCardPrice()`, wizard, maile, manual importer. | **PLANNED** |
| 27 | Załącznik nr 2 do umowy PDF | Trzecia strona PDF z tabelą etapów z breakdownu v2 (task #26). Format 1:1 z umową klienta: 4 wiersze etapów + kwota + waluta + opis + ⚠ dla `is_estimate`. `internal_margin_pln` NIGDY nie pojawia się w załączniku. Podpisy u dołu. Zależność: task #26 musi być DONE przed startem. Dotyczy `class-asiaauto-contract.php` — nowa metoda `renderAttachment2()`. | **PLANNED** (blocked by #26) |


---

## Planned: Contract Rework — Agency Model (Prima-Auto)

Status: **IN PROGRESS 4/5** (Krok 1+2+3+4 DONE, Krok 5 pending). Ostatnia aktualizacja: 2026-04-14.

### Postęp wdrożenia

| Krok | Status | Data | Pliki |
|---|---|---|---|
| 1 — Config i stałe meta | ✅ DONE | 2026-04-13 | `class-asiaauto-order.php`, `class-asiaauto-order-admin.php` |
| 2 — Metabox „Dane umowy" + helper + pole VIN | ✅ DONE | 2026-04-13 | `class-asiaauto-order.php`, `class-asiaauto-order-admin.php` |
| 3 — `AsiaAuto_Contract::collectData()` rozszerzenie | ✅ DONE | 2026-04-14 | `class-asiaauto-contract.php` |
| 4 — Przebudowa `renderHTML()` (pełen rewrite §1-§9) + bonus: `bank_name` config + redesign tabu Ustawienia | ✅ DONE | 2026-04-14 | `class-asiaauto-contract.php`, `class-asiaauto-order.php`, `class-asiaauto-order-admin.php` |
| 5 — Treści maili + etykiety statusów | TODO | — | Admin UI (wp_options `asiaauto_order_content`) |

### Co już działa w kodzie (Krok 1+2)

**Nowe stałe klasy `AsiaAuto_Order`:**
- `META_CONTRACT_COMMISSION_NET = '_order_contract_commission_net'`
- `META_VIN = '_order_vin'` (editable VIN na poziomie zamówienia, fallback do `vin_number` listingu)

**Nowe klucze configu `asiaauto_order_config` (`wp_options`):**
- `contract_commission_percent` (float, default `0.0`)
- `contract_commission_min` (int, default `5000`)
- `contract_commission_vat_rate` (float, default `23.0`)
- `bank_swift` (string, default `''`)
- `default_delivery_days` — default zmieniony `60 → 120` (§4 ust. 1 umowy Ruslana)
- `transfer_title_template` — default zmieniony na `'Depozyt AsiaAuto zamówienie #{order_id}'`

**Nowy helper:**
```php
AsiaAuto_Order::calculateContractCommission(int $price_pln, ?array $config = null): int
```
Formuła: `max(price × percent / 100, min)`, bez zaokrąglenia (w przeciwieństwie do `calculateDeposit()`). Wywoływany w `create()` obok `calculateDeposit()`, zapis do `META_CONTRACT_COMMISSION_NET`.

**Metabox „Dane umowy" (zastąpił sekcję „Zaliczka"):**
- Grupa **Pojazd**: pole VIN (text, max 17, auto-uppercase, strip non-alphanumeric po stronie JS i PHP)
  - Fallback: `_order_vin` → `vin_number` listingu → pusty
  - Żółty ⚠ przy fallback z listingu, czerwony ⚠ przy całkowitym braku VIN
- Grupa **Finanse**: depozyt zabezpieczający + wynagrodzenie zleceniobiorcy
  - Live JS: brutto = `net × (1 + vat_rate/100)`, zwrot = `deposit − brutto`
  - Czerwony kolor + tooltip przy ujemnym zwrocie
  - Fallback A dla wynagrodzenia: pusty meta → pre-fill z `calculateContractCommission()` z żółtym ⚠ „sugerowane z configu — zapisz, żeby utrwalić"
- Wspólny form dla obu pól edytowalnych, przycisk „Zapisz dane umowy"
- Osobny mini-form „Oznacz depozyt wpłacony" (nie zmieniony endpoint `mark_deposit_paid`)
- Read-only „Dane do przelewu" z wierszem SWIFT/BIC gdy skonfigurowany

**Spójność semantyczna:**
- Lista zamówień: kolumna „Zaliczka" → „Depozyt"
- Szybka akcja: „Oznacz zaliczkę" → „Oznacz depozyt"
- Flash message: „Zaliczka oznaczona jako wpłacona." → „Depozyt oznaczony jako wpłacony."
- Log statusów: „Zaliczka oznaczona…" → „Depozyt oznaczony…"

**Pipeline cenowy, breakdown, `_order_commission_override`, `_order_deposit_amount` formuła, wizard — bez zmian. Treść PDF umowy przebudowana w Kroku 4 (patrz niżej).**

### Co przyniosły Kroki 3+4 (2026-04-14)

**Krok 3 — `collectData()` rozszerzenie (`class-asiaauto-contract.php` v0.30.2):**
- Nowe klucze w array danych konsumowane przez `renderHTML()`: `contract_commission_net`, `contract_commission_vat`, `contract_commission_gross`, `contract_commission_vat_rate`, `contract_commission_net_words`, `contract_commission_gross_words`
- Fallback commission: puste `_order_contract_commission_net` → wyliczenie z `calculateContractCommission()` z configa (bez zapisu do bazy — lazy fallback dla starych zamówień)
- `getVehicleData()` przyjmuje `$order_id` jako drugi parametr, VIN z fallbackiem `_order_vin` → `vin_number` listingu → `'—'`
- `delivery_days` default fallback 60 → 120 (ujednolicenie z `configDefaults()` v0.30.0)
- Math helper `numberToWords()` — konwersja int → polski zapis słowny (obsługa milionów, tysięcy, setek, dziesiątek, jednostek z prawidłową odmianą)

**Krok 4 — pełen rewrite `renderHTML()` pod treść umowy klienta (`class-asiaauto-contract.php` v0.30.3):**
- **Tytuł**: „UMOWA ZLECENIA SPROWADZENIA SAMOCHODU Z CHIN" (zamiast starej „Umowa zlecenia importu samochodu osobowego")
- **§1** — 8 ustępów, zagnieżdżona lista 1)–5) z literami a–d (marka/rok/VIN/stan), deposit-box z 5 liniami (numer konta, SWIFT, **Nazwa banku**, Odbiorca, Tytuł przelewu), hardcoded „ZWROTNY DEPOZYT GWARANCYJNY" w treści umowy
- **§2** — 4 ustępy, płatności bezpośrednio na rachunki chińskich sprzedawców i urzędów celno-skarbowych
- **§3** — wynagrodzenie netto + słownie + VAT% + brutto + słownie, wszystko z `contract_commission_*` Kroku 3
- **§4** — 10 ustępów, ust. 4 z literami a–g (7 przyczyn opóźnień), `{delivery_days}` dni w ust. 1
- **§5** — 4 ustępy, **14 dni** w ust. 2 (zmiana z 7)
- **§6** — **NOWY** paragraf „Kary umowne", 3 ustępy, symetryczne kary między stronami
- **§7** — 8 ustępów, kanały komunikacji (poczta/e-mail/WhatsApp/SMS), raport z oględzin, odpowiedzialność za roszczenia
- **§8** — 2 ustępy, polubowne + sąd siedziby Zleceniobiorcy
- **§9** — 5 ustępów, klauzula salwatoryjna, dwa egzemplarze
- Notka o załącznikach nr 1 i nr 2 na końcu (w MVP Kroku 4 tylko odwołanie tekstowe, same załączniki to osobny etap)
- **Usunięte z głównej treści**: tabela techniczna pojazdu (kolor/silnik/przebieg/skrzynia) — idzie do Załącznika nr 1; `price_box` z §3 — umowa klienta tego nie ma
- **`renderPDF()` sygnatura zmieniona**: `(string $html, array $data)` zamiast `(string $html, string $contract_number)` — stopka czyta `company_name` z `$data` zamiast hardcoded „AsiaAuto.pl" (branding Prima-Auto w stopce każdej strony)
- **`generate()`** przekazuje całe `$data` do `renderPDF()`

**Krok 4 bonus — wyjście poza pierwotny scope (ujawnione po wizualnej inspekcji PDF #222262):**

1. **Nowy klucz configa `bank_name`** (`class-asiaauto-order.php` v0.30.4):
   - `configDefaults()` → `'bank_name' => ''`
   - Semantyczne rozdzielenie od `bank_account_name`:
     - `bank_name` = **nazwa instytucji bankowej** (np. „Alior Bank", „mBank")
     - `bank_account_name` = **właściciel rachunku / odbiorca przelewu** (Prima-Auto Ruslan Prima)
   - Wcześniej `bank_account_name` było mylnie używane w renderHTML jako „Nazwa banku" — bug semantyczny naprawiony
   - W `collectData()` nowy klucz `company_bank_institution` = `$config['bank_name']`, konsumowany w deposit-box
   - `handleSaveConfig()` w `class-asiaauto-order-admin.php` dodaje sanityzację pola

2. **Redesign tabu Ustawienia** (`class-asiaauto-order-admin.php` v0.30.4):
   - `renderConfigTabSettings()` pełen rewrite z płaskiego liniowego układu na **dwukolumnowy grid z 6 kolorowymi kartami sekcji**:
     ```
     ┌──────────────────────────┬──────────────────────────┐
     │ 🏢 Dane firmy            │ 🏦 Dane do przelewu       │  navy / blue
     │ (Zleceniobiorca)         │ (rachunek + tytuł)        │
     ├──────────────────────────┼──────────────────────────┤
     │ 💰 Depozyt               │ 📄 Wynagrodzenie          │  green / orange
     │ (formuła)                │ (§3 umowy)                │
     ├──────────────────────────┼──────────────────────────┤
     │ 📧 Powiadomienia         │ ⚙️ Inne ustawienia        │  gray / purple
     └──────────────────────────┴──────────────────────────┘
     ```
   - Każda karta: akcentowa ramka `border-left: 4px` w kolorze sekcji, nagłówek z ikoną, opis sekcji, własny `form-table`
   - CSS inline w `<style>` na początku metody (nie rusza zewnętrznego `assets/css/asiaauto-order-admin.css`)
   - Responsive: `<1100px` przechodzi na 1 kolumnę
   - Submit button w osobnym boksie pod gridem z navy border-top
   - Pole `bank_name` ma czerwony badge „NOWE" dla wyróżnienia
   - **Telefon kontaktowy przeniesiony** z sekcji „Dane do przelewu" do „Dane firmy" — semantycznie poprawne miejsce
   - Usunięty dead code `getInlineCSS()` (private metoda nigdzie nie wywoływana)
   - W metaboksie zamówienia (`renderCardContractData`) dodana linia „Nazwa banku" w bloku „Dane do przelewu" (conditional render — pokazuje się tylko gdy `bank_name` niepuste)

### Weryfikacja na zamówieniu #222262 (2026-04-14)

Test case: AURANET / Jan Schenk, XPeng XPeng MONA M03 2024, VIN WDDCOSTAMCOSTAM, price 155555, deposit 5000, commission_net 6000 (override z metaboxa), delivery 90 dni.

- PDF wygenerowany czysto przez `regenerate()`, 5 stron A4, attachment #231057
- Stopka: „Umowa nr AA/2026/0005 | Prima-Auto Ruslan Prima | str. X/5" ✅
- Deposit-box: 5 linii 1:1 z wzorem klienta (nr konta + SWIFT + Nazwa banku + Odbiorca + Tytuł) ✅
- §3 math: `6000 × 1.23 = 7380` + słownie „sześć tysięcy" / „siedem tysięcy trzysta osiemdziesiąt" ✅
- §4 ust. 1: 90 dni (z `_order_delivery_estimate`, nie fallback configa) ✅
- §5 ust. 2: 14 dni ✅
- §6 kary, §7 oświadczenia, §9 klauzula — wszystko 1:1 z DOC klienta ✅
- Zero PHP errors w `asiaauto-sync.log`

### Known issues (drobne, nie blokery — do adresacji przy Kroku 6/7 lub osobno)

1. **Gramatyka: „Zawarta DD.MM.YYYY roku w Rzeszów"** — powinno być „w Rzeszowie" (miejscownik). `collectData()` ma hardcoded `'city' => 'Rzeszów'` (mianownik). Fix: albo zmienić na `'Rzeszowie'`, albo wynieść do configa jako dwa pola `company_city` (mianownik, dla nagłówka „Rzeszów, DD.MM.YYYY r.") + `company_city_locative` (miejscownik, dla „w Rzeszowie"). Druga opcja lepsza bo pokrywa inne lokalizacje gdyby Prima miała oddziały.

2. **Duplikacja prefiksu marki: „XPeng XPeng MONA M03"** — taksonomia `serie` (model) zawiera prefix marki, więc `$make + ' ' + $model` daje duplikat. Dotyczy całego pluginu (wyświetlanie listingów, tytułów), nie tylko umowy. Fix globalny: helper `AsiaAuto_Order::formatVehicleMakeModel($make, $model)` z `preg_replace('/^' . preg_quote($make, '/') . '\\s*/i', '', $model)` — używany wszędzie gdzie składamy markę+model.

### Pozostało w Contract Rework

**Krok 5 — Treści maili + etykiety statusów** (edycja przez admin UI, bez deploy PHP):

Szablony maili do aktualizacji semantycznie (zaliczka → depozyt zabezpieczający):
- `status_umowa_gotowa` — „Pobierz PDF, podpisz, prześlij skan. Po potwierdzeniu wpłać **depozyt zabezpieczający** {deposit_amount} zł…"
- `status_podpisane` — „Oczekujemy na wpłatę **depozytu zabezpieczającego**."
- `status_zarezerwowane` — „**Depozyt zabezpieczający** zaksięgowany. Rozpoczynamy procedurę zakupu w Chinach."
- `contract_regenerated` — weryfikacja wzmianek o zaliczce
- `order_started_customer` — weryfikacja

Globalna reguła: find-and-replace w ~15 szablonach: „zaliczka" → „depozyt zabezpieczający" (z zachowaniem odmiany).

Status display (`asiaauto_order_status_display`): sprawdzić `zakonczone` i `anulowane` czy opisy kliencie nie wspominają zaliczki.

Test: wysyłka testowego maila lub zmiana statusu na zamówieniu testowym.

### VIN — miejsce w umowie klienta (zweryfikowane 2026-04-13 z DOC)

VIN pojawia się w umowie Ruslana **dwa razy**:
1. **§1 PRZEDMIOT UMOWY, ust. 1** — jako parametr nabywanego pojazdu (obok marki/modelu/roku produkcji)
2. **Załącznik nr 1 do Umowy** — pełna specyfikacja aukcji (marka, paliwo, kolor, rok rejestracji, VIN)

W §2 jest pasywna wzmianka „weryfikacja za pomocą numeru VIN" bez podania samego numeru.

W obecnym PDF AsiaAuto (`class-asiaauto-contract.php::renderHTML()`) VIN jest w tabeli parametrów §1 razem z innymi polami (marka, wersja, rok, nadwozie, silnik, przebieg, VIN, pochodzenie). Wersja Ruslana jest prostsza — w §1 tylko marka i model / rok produkcji / VIN, reszta przesunięta do załącznika nr 1. Przebudowa §1 w Kroku 4b.

### Cel

Przebudowa treści PDF generowanego przez `class-asiaauto-contract.php` z obecnego
modelu „pod klucz" (v0.16.0 — „Umowa Zlecenia Importu Samochodu Osobowego") na
model **agencyjny** zgodny z umową dostarczoną przez klienta Ruslana Prima
(PRIMA-AUTO, test case: BYD Song L EV AWD / DongFeng M-HERO dla ROBOCLEAN POLAND).

### Co zostaje bez zmian (ważne!)

- Pipeline cenowy `AsiaAuto_Price` (9 kroków, v0.28.0 + dual-mode v0.29.0) — **bez zmian**
- Cena końcowa „pod klucz" prezentowana klientowi na listingu, w karcie zamówienia, w breakdownie admina — **bez zmian**
- `_order_commission_override` i cała wewnętrzna marża sprzedawcy w breakdown — **bez zmian**
- CPT, 11 statusów, role (`asiaauto_customer`, `primaauto`), wizard strukturalnie, REST API — **bez zmian**
- `_order_deposit_amount` + formuła `deposit_percent` × `deposit_min` × `deposit_rounding` — **bez zmian kodu, tylko zmiana semantyczna**: „zaliczka na poczet ceny" → „depozyt zabezpieczający umowę"
- Breakdown w admin karcie zamówienia — **bez zmian**, klient ma pełną ewidencję rentowności per pojazd

### Separacja trzech bytów finansowych

To jest fundament zadania. Trzy pojęcia które wcześniej się zlewały i muszą być rozłączne.

#### 1. Prowizja w breakdown (marża wewnętrzna)

- **Gdzie:** `AsiaAuto_Price::calculate()` krok 8, config `prowizja_procent` + `prowizja_min`
- **Per-order override:** `_order_commission_override` (int/'' — nadpisuje breakdown)
- **Znaczenie:** wewnętrzna marża Prima-Auto wliczona w cenę „pod klucz", niewidoczna dla klienta, używana do ewidencji rentowności
- **Dotyka umowy?** NIE
- **Zmiany?** BRAK

#### 2. Wynagrodzenie zleceniobiorcy z umowy (§3 ust. 1)

- **Gdzie (nowe):** meta `_order_contract_commission_net` (int, PLN netto)
- **Wyliczane przy tworzeniu zamówienia:** `max(price_final × contract_commission_percent / 100, contract_commission_min)` — analogicznie do istniejącej formuły depozytu
- **Sprzedawca może nadpisać ręcznie** w metaboksie „Dane umowy"
- **Znaczenie:** kwota pojawiająca się w §3 umowy jako wynagrodzenie Prima-Auto za pośrednictwo, potwierdzana fakturą VAT wystawianą bez podpisu klienta (§3 ust. 2)
- **Dotyka `_order_price_final` lub breakdown?** NIE

#### 3. Depozyt zabezpieczający umowę (§1 ust. 3)

- **Gdzie (istniejące):** meta `_order_deposit_amount`, config `deposit_percent` + `deposit_min` + `deposit_rounding`
- **Formuła wyliczenia:** BEZ ZMIAN (`max(price × %, min)`, zaokrąglenie w górę do `rounding`)
- **Znaczenie semantyczne:** zmienia się z „zaliczki na poczet ceny" na „depozyt zabezpieczający wykonanie umowy, zwrotny przy nieudanym zakupie, pomniejszany o wynagrodzenie z §3 przy udanym"
- **Zmiana w kodzie:** ZERO (tylko etykiety w UI, tekst w PDF i szablonach maili)

#### Relacja między (2) i (3)

§1 ust. 5 umowy klienta: przy udanym zakupie depozyt pomniejsza się o wynagrodzenie z §3 i reszta wraca do klienta. **W systemie nic tego nie automatyzuje** — zwrot to fizyczny przelew zrobiony ręcznie przez Prima-Auto. PDF pokazuje obie kwoty i opisuje mechanizm słownie.

**Info dynamiczne w metaboksie „Dane umowy":**
```
wynagrodzenie brutto = contract_commission_net × (1 + vat_rate/100)
do zwrotu po udanym zakupie = _order_deposit_amount − wynagrodzenie brutto
```

### Plan wdrożenia — 5 kroków

Każdy krok testowany osobno. Między krokami — potwierdzenie użytkownika przed przejściem dalej.

#### Krok 1 — Config i stałe meta ✅ DONE (2026-04-13)

**Pliki:** `includes/class-asiaauto-order.php` (stałe) + miejsce gdzie są defaulty `asiaauto_order_config`

**Zmiany:**
- Nowa stała `META_CONTRACT_COMMISSION_NET = '_order_contract_commission_net'` w `AsiaAuto_Order`
- Nowe klucze w defaultach configu `asiaauto_order_config`:
  - `contract_commission_percent` (float, default `0.0`)
  - `contract_commission_min` (int, default `5000`)
  - `contract_commission_vat_rate` (float, default `23.0`)
- Aktualizacja etykiet w admin UI configa (Narzędzia → AsiaAuto Zamówienia → tab Ustawienia):
  - „Zaliczka (%)" → „Depozyt zabezpieczający (%)"
  - „Zaliczka min" → „Depozyt min (PLN)"
  - „Zaokrąglenie zaliczki" → „Zaokrąglenie depozytu"
- `transfer_title_template` default: `'Depozyt AsiaAuto zamówienie #{order_id}'`
- Nowa sekcja w admin configu „Wynagrodzenie zleceniobiorcy (umowa)" z 3 polami

**Test:** deploy, wejście w admin config, zapis nowych pól do `wp_options`, fallback dla istniejących zamówień przez `get_option` z defaultami.

**Ryzyko:** zero dla pipeline'u, breakdown, istniejących zamówień. Stare wartości `deposit_*` zostają w bazie nieruszone — zmienia się tylko ich etykieta.

#### Krok 2 — Metabox „Dane umowy" + helper wyliczający prowizję kontraktową ✅ DONE (2026-04-13)

> **Uwaga:** W trakcie realizacji Kroku 2 dodano także pole VIN (`_order_vin`, grupa „Pojazd" w metaboksie) — nie było go w oryginalnym planie, ale klient zasygnalizował potrzebę po analizie umowy DOC. Szczegóły wyżej w sekcji „Co już działa w kodzie". Fallback VIN z `_order_vin` → `vin_number` listingu → pusty, po stronie admina UI + sanitize po stronie PHP (uppercase, alphanumeric, max 17 znaków).

**Status opcji z runbooka (wszystkie potwierdzone 2026-04-13):**
- UX save: opcja A (wspólny form dla obu pól) ✅
- Live JS trigger: `input` (natychmiast przy pisaniu) ✅
- Fallback dla starych zamówień: opcja A (pre-fill z configu bez zapisu do bazy, żółty ⚠) ✅
- Wywołanie helpera w `create()` dla każdego nowego zamówienia ✅
- Usunięcie sekcji „Zaliczka" + rename kolumn/akcji/flash ✅

Treść poniżej zostawiona jako referencja planu. Rzeczywisty stan kodu = aktualna wersja na serwerze (via MCP `read_file`).

**Pliki:** `includes/class-asiaauto-order-admin.php` (metabox) + `includes/class-asiaauto-order.php` (helper)

**Zmiany:**
- Nowa metoda `AsiaAuto_Order::calculateContractCommission(int $price_final, ?array $config = null): int` — analogiczna do istniejącej kalkulacji depozytu, bez zaokrąglenia do tysięcy (bo prowizja jest okrągła na innym poziomie):
  ```
  max($price × $percent / 100, $min)
  ```
- Wywoływana przy tworzeniu zamówienia w tym samym miejscu co wyliczenie depozytu, zapis do `_order_contract_commission_net`
- Nowa sekcja w metaboksie admina **„Dane umowy"** z dwoma polami:
  - Depozyt zabezpieczający (odczyt `_order_deposit_amount`, edytowalny — już istnieje save handler, przenosimy UI z obecnej sekcji „Zaliczka")
  - Wynagrodzenie zleceniobiorcy (netto PLN) — odczyt `_order_contract_commission_net`, fallback do `calculateContractCommission()` z configa przy pustym polu
- Obok pola wynagrodzenia — live JS: „Brutto: X zł (VAT {rate}%)"
- Poniżej obu pól — live JS: „Do zwrotu po udanym zakupie: Y zł" (jeśli wynik ujemny, czerwony warning „Depozyt mniejszy niż wynagrodzenie brutto — sprawdź kwoty")
- Save handler rozszerzony o `_order_contract_commission_net` (sanitize_int, clamp ≥ 0)
- Stara sekcja „Zaliczka" znika (zastąpiona przez „Dane umowy")

**Test:** utworzenie nowego zamówienia przez wizard, sprawdzenie w adminie że obie wartości się wyliczają i wyświetlają. Ręczna edycja obu pól, save, reload, persystencja. Edycja wynagrodzenia do 0 i sprawdzenie fallbacku do configu.

**Ryzyko:** niskie. Nie dotyka wyliczenia `_order_deposit_amount`, dodaje drugą wartość liczoną analogicznie.

#### Krok 3 — `AsiaAuto_Contract::collectData()` rozszerzenie (bez zmian szablonu)

**Plik:** `includes/class-asiaauto-contract.php` — tylko metoda `collectData()`, NIE tykamy `renderHTML()` w tym kroku

**Zmiany w `collectData()`:**
- Odczyt `$contract_commission_net` z meta `_order_contract_commission_net` z fallbackiem do wyliczenia z configu (dla zamówień utworzonych przed krokiem 2)
- Odczyt `$vat_rate` z `$config['contract_commission_vat_rate']` (default 23.0)
- Wyliczenia:
  ```
  $contract_commission_vat = round($net * $vat_rate / 100)
  $contract_commission_gross = $net + $vat
  ```
- Nowe klucze w zwracanej tablicy:
  - `contract_commission_net` (int)
  - `contract_commission_vat` (int)
  - `contract_commission_gross` (int)
  - `contract_commission_vat_rate` (float)
  - `contract_commission_net_words` (string — Polish number-to-words)
  - `contract_commission_gross_words` (string)
- **Istniejące klucze zostają bez zmian** (`price_pln`, `deposit_amount`, `vehicle`, `customer_*`, `company_*`, `contract_number`, `date`, `city`, `delivery_days`)

**Zmiana wartości defaultowej:**
- `default_delivery_days` w configu: **60 → 120** (§4 ust. 1 umowy Ruslana)

**Test:** ręczne wywołanie `AsiaAuto_Contract::regenerate($order_id)` na istniejącym zamówieniu testowym (przycisk „Regeneruj" w adminie albo WP-CLI). Sprawdzenie że PDF się generuje bez błędów i **wygląda identycznie jak przed zmianą** (bo `renderHTML()` nadal nie używa nowych kluczy). Weryfikacja przez MCP: `read_file` na PDF atachment lub log `grep "Contract PDF" asiaauto-sync.log`.

**Ryzyko:** minimalne. Dodajemy nowe klucze do tablicy, istniejący szablon ich nie używa → brak zmian wizualnych.

#### Krok 4 — Przebudowa `renderHTML()` pod treść umowy klienta

**Plik:** `includes/class-asiaauto-contract.php` — metoda `renderHTML()`

**Styl i oprawa wizualna — ZOSTAJĄ BEZ ZMIAN:**
- Header z logo navy + numer umowy + data po prawej, linia 2pt navy
- `COLOR_NAVY = #1B2A4A`
- Tabela parties dwukolumnowa z tłem `#F5F6F8`
- Klasy CSS (`.section-title`, `.section-body`, `.data-table`, `.scope-list`, `.price-box`)
- Stopka mPDF `SetHTMLFooter` z numerem umowy + `{PAGENO}/{nbpg}`
- Podpisy na dole dwukolumnowe z linią navy

**Zmienia się wyłącznie treść paragrafów.** Rozbijamy na 5 podkroków, po każdym regeneracja testowego PDF-a i potwierdzenie użytkownika zanim następny.

##### Krok 4a — Header, tytuł, parties

- Tytuł: **„UMOWA ZLECENIA SPROWADZENIA SAMOCHODU Z CHIN"** (zamiast „Umowa zlecenia importu samochodu osobowego")
- Data i miasto z configu (`'Rzeszów'` hardcoded — zostaje)
- Parties — bez zmian strukturalnych. Klient jako osoba fizyczna (PESEL) LUB firma (NIP). Logika `$customer_display` z obecnego kodu już to obsługuje (`billing_company_name` vs `customer_name`) — tylko weryfikacja że działa dla sp. k.

**Test 4a:** regeneracja PDF, wizualna weryfikacja nagłówka i sekcji stron.

##### Krok 4b — §1 Przedmiot umowy + §2 Płatności

**§1** (8 ustępów wg umowy klienta):

1. Zleceniodawca zleca, Zleceniobiorca przyjmuje zobowiązanie do:
   1) nabycia w Chinach pojazdu o parametrach: marka, model, rok, VIN, stan techniczny („w stanie w jakim znajduje się w dacie zakupu, opisany w załączniku nr 1"), za cenę maksymalną z załącznika nr 2
   2) dostarczenia z miejsca zakupu do portu wysyłki w Chinach
   3) transport morski (Gdańsk/Bremerhaven) przez firmę spedycyjną
   4) odprawa celna w porcie odbioru przez firmę spedycyjną
   5) dostarczenie dokumentów: zaświadczenie o wyrejestrowaniu w Chinach, faktura zakupu
2. Nabycie przez zakup u wybranego dealera
3. **Depozyt zabezpieczający umowę w kwocie `{deposit_amount} zł brutto`** na rachunek: {company_bank_name}, nr {company_bank}, SWIFT: {company_swift}, tytuł „ZWROTNY DEPOZYT GWARANCYJNY", lub gotówką przy podpisaniu
4. Nieudany zakup → zwrot w całości w 3 dni roboczych
5. Udany zakup → depozyt pomniejszony o wynagrodzenie z §3, reszta zwracana po zapłacie kwot §2 i §3
6. Brak zapłaty §2/§3 → przepadek depozytu
7. Definicje „data zakupu" i „data dostarczenia"
8. Obowiązek współdziałania na etapie licytowania i płatności

**§2 Płatności** (kluczowa zmiana semantyczna):

1. Koszty nabycia, sprowadzenia i transportu krajowego Zleceniodawca **płaci bezpośrednio** na rachunki chińskiego sprzedawcy, urzędów celno-skarbowych i firm spedycyjnych wskazanych przez Zleceniobiorcę, wg szacunkowej kalkulacji z załącznika nr 2
2. Niezwłoczne przedłożenie dowodów wpłat (osobiście lub mailem)
3. Opóźnienie > 3 dni → odsetki ustawowe za opóźnienie
4. VAT, akcyza, cło — płatne zgodnie z dokumentami instytucji; Zleceniobiorca przekazuje dokumenty w 3 dni roboczych od otrzymania

**WAŻNE:** umowa klienta **nie ma nigdzie jednej kwoty PLN jako ceny końcowej**. Nie ma §2/§3 z „price_box" i `{price_pln} zł brutto` jak w obecnym szablonie.

**Decyzja otwarta (wymaga potwierdzenia użytkownika przed krokiem 4b):**
Czy w §2 pokazujemy gdziekolwiek `price_pln` z breakdownu jako informację pomocniczą („szacunkowa łączna cena 'pod klucz' na dzień zawarcia umowy"), czy całkowicie usuwamy? Umowa klienta tego nie ma. Rekomendacja: **usuwamy całkowicie** — zgodność 1:1 z wersją klienta.

**Załączniki nr 1 i nr 2 — status MVP:**
- **Załącznik nr 1** (opis pojazdu z aukcji): w MVP tylko odwołanie w tekście §1 ust. 1. Upload przez sprzedawcę i merge do PDF przez `$mpdf->AddPage()` + `<img>` — osobny etap (backlog).
- **Załącznik nr 2** (kalkulacja etapów): w MVP tylko odwołanie w tekście. Auto-generacja z `_asiaauto_price_breakdown` jako dodatkowa strona PDF — osobny etap.

**Test 4b:** regeneracja PDF, weryfikacja §1 (szczególnie depozyt w ust. 3 z dynamiczną kwotą) i §2 (brak price_box).

##### Krok 4c — §3 Wynagrodzenie + §4 Wydanie pojazdu

**§3 Wynagrodzenie Zleceniobiorcy:**

1. Wynagrodzenie z tytułu prowadzenia, monitorowania i organizacji transakcji wynosi **{contract_commission_net} zł netto** (słownie: {contract_commission_net_words}) powiększone o VAT {contract_commission_vat_rate}%. Łącznie brutto: **{contract_commission_gross} zł** (słownie: {contract_commission_gross_words}). Odliczana od depozytu z §1 ust. 3 po udanym zakupie, reszta depozytu zwracana Zleceniodawcy.
2. Upoważnienie do wystawienia faktury VAT bez podpisu Zleceniodawcy.

**§4 Wydanie pojazdu** (10 ustępów wg umowy klienta):

1. Termin **{delivery_days} dni** (default 120) od wystawienia faktury w Chinach
2. Wydanie = dostarczenie na adres wskazany
3. Miejsce wydania = wskazane przez Zleceniobiorcę w RP
4. Brak odpowiedzialności za opóźnienia z przyczyn: a) organy celne, b) organy administracyjne UE, c) transport morski, d) siła wyższa, e) działania Zleceniodawcy, f) osoby trzecie, g) zmiany przepisów
5. Automatyczne wydłużenie terminu; obowiązek powiadomienia w 3 dni
6. Zmiana miejsca — pisemne potwierdzenie drugiej strony
7. Niezgłoszenie się po odbiór → obciążenie kosztami przechowywania
8. Nowy termin odbioru w 7 dni roboczych
9. Odbiór potwierdzany pisemnym oświadczeniem
10. Prawo zatrzymania dokumentów do czasu uregulowania należności

**Test 4c:** regeneracja, weryfikacja §3 (netto + VAT + brutto, słownie, obie kwoty), §4 (10 ustępów, dynamiczny `{delivery_days}`).

##### Krok 4d — §5 Odstąpienie + §6 Kary umowne

**§5 Odstąpienie:**

1. Zleceniodawca — odstąpienie przy niewykonaniu przez Zleceniobiorcę (z zastrzeżeniem §4 ust. 4-5), po bezskutecznym pisemnym wezwaniu z ust. 2
2. **Dodatkowy termin ≥ 14 dni** (nie 7 jak w obecnym szablonie)
3. Zleceniobiorca — odstąpienie przy: a) brak odbioru po terminie §4 ust. 8, b) brak zapłaty §1/§2
4. Odstąpienie nie wpływa na kary umowne i odszkodowania

**§6 Kary umowne** (nowy paragraf — NIE MA GO w obecnym szablonie):

1. Zleceniodawca → Zleceniobiorcy: **kara w wysokości pełnej wartości depozytu**, gdy Zleceniobiorca odstąpi z przyczyn Zleceniodawcy
2. Zleceniobiorca → Zleceniodawcy: **kara w wysokości wynagrodzenia z §3**, gdy Zleceniodawca odstąpi z przyczyn Zleceniobiorcy
3. Poza karami — odszkodowanie na zasadach ogólnych KC dla obu stron

**Test 4d:** weryfikacja obecności nowego §6 z obiema symetrycznymi karami.

##### Krok 4e — §7 Oświadczenia + §8 Spory + §9 Końcowe + podpisy

**§7 Oświadczenia** (8 ustępów):

1. Zleceniobiorca prowadzi czynności uzgadniając i otrzymując potwierdzenia kanałami: **poczta, email, WhatsApp, SMS** (pod rygorem nieważności)
2. Zleceniodawca godzi się na koszty; Zleceniobiorca potwierdza aktualność danych rachunków (obowiązek powiadomienia w 3 dni przy zmianie)
3. Zleceniodawca poinformowany o ryzyku: zakres uszkodzeń może być większy niż na zdjęciach — brak roszczeń co do stanu technicznego niewynikającego z zaniedbań Zleceniobiorcy
4. Zleceniobiorca przekaże raport z oględzin przed zakupem
5. Brak odpowiedzialności Zleceniobiorcy za błędne informacje w opisie aukcji (weryfikowalne przez VIN)
6. Załączniki 1 i 2 korygowane aneksami; obowiązek rozpatrywania wniosków drugiej strony
7. Zleceniodawca przyjmuje odpowiedzialność za roszczenia osób trzecich
8. Informowanie o sytuacjach z ust. 6 w 3 dni

**§8 Rozwiązywanie sporów:**

1. Polubowne rozwiązywanie (nie jest to zapis na Sąd polubowny)
2. Sąd właściwy dla siedziby Zleceniobiorcy

**§9 Postanowienia końcowe** (5 punktów):

1. W sprawach nieuregulowanych — KC
2. Zmiany — forma pisemna
3. Załączniki i aneksy stanowią integralną część
4. Klauzula salwatoryjna
5. Dwa egzemplarze

**Podpisy** — bez zmian strukturalnych, etykiety „Zleceniodawca" / „Zleceniobiorca ({company_name})".

**Test 4e:** pełen PDF z kompletną treścią §1–§9, podpisy, stopka, paginacja. **Porównanie 1:1 z plikiem przesłanym przez klienta** — to jest moment końcowej walidacji całego kroku 4.

#### Krok 5 — Treści maili i etykiety statusów

**Gdzie:** `wp_options` → `asiaauto_order_email_templates` + `asiaauto_order_status_display`. Edytowalne z admin UI (Narzędzia → AsiaAuto Zamówienia → tab Treści maili / Statusy). **Bez deploy PHP.**

**Szablony maili do aktualizacji:**
- `status_umowa_gotowa` — „Pobierz PDF, podpisz, prześlij skan. Po potwierdzeniu wpłać **depozyt zabezpieczający** {deposit_amount} zł…"
- `status_podpisane` — „Oczekujemy na wpłatę **depozytu zabezpieczającego**."
- `status_zarezerwowane` — „**Depozyt zabezpieczający** zaksięgowany. Rozpoczynamy procedurę zakupu w Chinach."
- `contract_regenerated` — weryfikacja czy wspomina zaliczkę
- `order_started_customer` — weryfikacja

**Globalna reguła:** find-and-replace w 15 szablonach maili: „zaliczka" → „depozyt zabezpieczający" (z zachowaniem odmiany), „Zaliczka" → „Depozyt zabezpieczający". Z UI, per szablon, z podglądem.

**Etykiety statusów w `asiaauto_order_status_display`:** większość neutralna. Sprawdzić `zakonczone` i `anulowane` — czy opisy klienckie nie wspominają zaliczki.

**Test 5:** testowy mail (przycisk „Wyślij test" albo zmiana statusu na zamówieniu testowym).

### Otwarte decyzje przed startem kroku 1

Te pytania muszą mieć odpowiedź zanim ruszy kodowanie:

1. **Wartości defaultowe configu:**
   - `deposit_percent = 0` + `deposit_min = 6150` + `deposit_rounding = 100` (stała kwota domyślna, zgodnie z przykładem z umowy Zielaka)
   - `contract_commission_percent = 0` + `contract_commission_min = 5000` + `contract_commission_vat_rate = 23`
   - Czy te wartości, czy inne?

2. **`price_pln` w §2 PDF-a** — całkowicie usuwamy, czy zostawiamy jako informację szacunkową pod §2? Umowa klienta tego nie ma. **Rekomendacja: usuwamy.**

3. **Załączniki nr 1 i nr 2** — MVP tylko odwołanie w tekście, czy od razu merge do PDF? **Rekomendacja: MVP tylko odwołanie, merge w osobnym etapie.**

4. **`default_delivery_days` 60 → 120** — globalnie, czy per-order override? **Rekomendacja: globalnie w configu.**

5. **`bank_swift` w configu** — dodajemy nowe pole, bo umowa klienta wymienia SWIFT/BIC? **Rekomendacja: tak, dodajemy jedno pole.**

### Referencje do plików

**Główny plik do edycji:**
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-contract.php`

**Pliki pomocnicze:**
- `includes/class-asiaauto-order.php` — stałe META_*, helper `calculateContractCommission()`
- `includes/class-asiaauto-order-admin.php` — metabox „Dane umowy"
- Miejsce defaultów `asiaauto_order_config` — nowe klucze

**Do odczytu dla kontekstu (przez MCP `read_file` przed każdym krokiem):**
- Aktualna wersja `class-asiaauto-contract.php` (nigdy nie ufać pamięci — czytać świeżo z serwera)
- `includes/class-asiaauto-price.php` — żeby NIE dotknąć pipeline'u
- `includes/class-asiaauto-order.php` — istniejące stałe META_*, `META_DEPOSIT_AMOUNT`, `META_CONTRACT_ID`

### Zasady pracy

- **Jeden krok = jedna zmiana = jeden test.** Nie mieszamy kroków.
- **Między krokami zawsze potwierdzenie użytkownika.** Nie przechodzimy do kolejnego bez OK.
- **Każdy krok idempotentny** — bezpieczny do ponownego uruchomienia.
- **Zmiany deployowane przez użytkownika** (FTP/SSH). Generujemy **pełne pliki**, nie patche.
- **MCP read-only** — weryfikacja zmian po deploy przez `read_file` i `logs`.
- **Nie tykamy pipeline'u cenowego** (`AsiaAuto_Price`), breakdown, `_order_commission_override`, `_asiaauto_price_breakdown`.
- **Polska terminologia prawna** — zachowujemy dokładnie z umowy klienta, bez kreatywnych przeredagowań.
- **Każdą sesję zaczynamy od `view /mnt/skills/user/asiaauto/SKILL.md`** żeby mieć aktualny status (wersja pluginu, postęp kroków).

### Kontekst: test case

Plik użyty do ustalenia treści umowy: `PL_Umowa_spr_sam_z_chin_BYD_Song_L_EV_AWD_Jarosław_Zielak.docx` przesłany przez Ruslana 2026-04-13. Zleceniobiorca: PRIMA-AUTO Ruslan Prima, Rzeszów, NIP 8133898576. Zleceniodawca w przykładzie: ROBOCLEAN POLAND Sp. z o.o. SPÓŁKA KOMANDYTOWA, Warszawa, NIP 5252652294. Pojazd: DongFeng M-HERO (mimo nazwy pliku „BYD Song L"), VIN LDP29H921RM003636. Depozyt w przykładzie: 6150 zł brutto. Wynagrodzenie: 5000 zł netto + VAT. Max cena pojazdu w Chinach: 71 700 USD. Termin wydania: 120 dni od faktury w Chinach.

---

## GRUPA 10 — Załączniki umowy + breakdown rewamp (status 2026-04-14)

Kontynuacja po zamkniętej GRUPIE 9 (Contract Rework Kroki 1–4, DONE na zam. #222262).
Zakres grupy: dwa nowe załączniki PDF do umowy (opis pojazdu + kalkulacja kosztów) oraz
przebudowa struktury breakdownu cenowego pod nową logikę USD-centric.

Kolejność wdrożenia w praktyce: Krok 7 (breakdown rewamp) poszedł jako pierwszy, bo jest
fundamentem dla późniejszego Załącznika nr 2 — bez nowej struktury JSON breakdownu nie ma
sensu generować załącznika z kalkulacją etapów.

### Krok 6 — Załącznik nr 1 (opis pojazdu z aukcji)

Status: **do potwierdzenia w nowym wątku** — sprawdzić obecność metody
`renderAttachment1()` w `class-asiaauto-contract.php`. Jeśli brak — do zrobienia po
deployu Kroku 7. Jeśli jest — zweryfikować, że konsumuje aktualne taksonomie/meta.

Zakres (kiedy będzie robiony):
- Osobna strona PDF generowana po głównej treści umowy, przed Załącznikiem nr 2
- Nagłówek, tabela parametrów pojazdu z taksonomii + meta, miniaturka zdjęcia, podpisy
- Źródła danych: taksonomie `make`, `serie`, `ca-year`, `body`, `fuel`, `exterior-color`,
  `transmission`, `drive`, `condition`; meta `mileage`, `engine`, `_asiaauto_horse_power`,
  `_asiaauto_complectation`, `stm_car_location`; VIN z `_order_vin` (Krok 3 GRUPY 9);
  miniaturka `get_the_post_thumbnail_url($listing_id, 'medium')`
- Plik: `class-asiaauto-contract.php`, nowa metoda `renderAttachment1()` wołana w
  `renderHTML()` po głównej treści, przed końcowym `</body>`, z `<pagebreak />` mPDF

### Krok 7 — Pipeline USD-centric v0.30.5 (DONE 2026-04-14, czeka na deploy)

**⚠ PIVOT vs oryginalny plan.** Pierwotny plan zakładał 4-stage structured breakdown
(`stage_1_china_purchase` USD, `stage_2_sea_transport` USD, `stage_3_customs` PLN,
`stage_4_domestic_transport` PLN) z osobnym polem `internal_margin_pln` jako marża
wewnętrzna wyrwana z pipeline'u. **Po rozmowie z userem (2026-04-14) plan został
odrzucony** — zamiast niego wdrożono wariant **USD-centric** na istniejącym 9-krokowym
pipeline, z przeniesieniem kroków 1+2 do domeny USD i zachowaniem kroków 3–9 w PLN.
CIF pozostaje punktem konwersji USD→PLN.

**Kluczowa decyzja architektoniczna (twardo potwierdzona przez usera):**
pipeline `step_8_prowizja_wewnetrzna` (marża wewnętrzna firmy ukryta w cenie pod klucz)
i umowa `_order_contract_commission_net` (wynagrodzenie §3, faktura VAT) to **dwa
całkowicie niezależne byty**. Zero mappingu między nimi. Krok 7 **NIE DOTYKA** ani
`class-asiaauto-contract.php`, ani `calculateContractCommission()`, ani metody
`META_CONTRACT_COMMISSION_NET`. Prowizja w breakdownie cenowym pozostaje w swoim
miejscu — nie jest wyciągana jako osobne pole, bo służy innemu celowi niż wynagrodzenie
§3 umowy.

#### Decyzje zamknięte w sesji 2026-04-14

| # | Decyzja | Wartość |
|---|---|---|
| 1 | Umowa vs pipeline | **Niezależne.** Krok 7 nie modyfikuje contract.php ani commission logiki umowy |
| 2 | `chiny_rejestracja_transport_usd` default | 5000 USD (zastępuje stary `chiny_rejestracja_transport` = 18000 PLN) |
| 3 | `kurs_usd_pln` default | 3.70 |
| 4 | `kurs_cny_usd` default | 0.15 (efektywny CNY→PLN = 0.555 vs stary 0.57, Δ ≈ −1.19%) |
| 5 | Stary `kurs_cny_pln` | Usunięty z `defaults()` i `saveConfig()`. `getConfig()` robi lazy migration — jeśli w bazie jest stary klucz, przelicza na `kurs_cny_usd = kurs_cny_pln / kurs_usd_pln` przy pierwszym odczycie |
| 6 | Legacy meta `_asiaauto_cif_price_pln` | Wygaszony. Listing #222255 (jedyny z tym meta w bazie, CIF PLN = 100 000) migruje się automatycznie przy pierwszym `applyToListing()` na `_asiaauto_cif_price_usd = 27 027.03` |
| 7 | Deploy → bulk recalc | Jednorazowy bulk recalc całej bazy (~5 724 listingów CNY + 1 legacy CIF PLN) zaraz po wgraniu plików. Potem przycisk „🔄 Przelicz wszystkie listingi" w panelu admina do ręcznych wywołań |
| 8 | Tryb CNY vs CIF USD override | Brak flagi `is_estimate`. Sprzedawca ręcznie nadpisuje CIF USD w metaboksie listingu, pre-fill z `cena_cny × kurs_cny_usd + chiny_rejestracja_transport_usd`. Priorytet w `applyToListing()`: `_asiaauto_cif_price_usd > 0` → wygrywa override, inaczej → tryb CNY. Brak flagi „orientacyjne" — decyzja użytkownika: „CIF wpisany ręcznie jest zawsze finalny dla sprzedawcy" |

#### Struktura pipeline v2

```
Tryb CNY auto-api (default dla listingów Dongchedi):
  step_1_cena_bazowa_usd     = cena_cny × kurs_cny_usd
  step_2_chiny_transport_usd = chiny_rejestracja_transport_usd (stała 5000)
  cif_usd = step_1 + step_2
  cif_pln = cif_usd × kurs_usd_pln
  → step_3 agencja (PLN, stała)
  → step_4 cło % (liczone od bazy = cif_pln + step_3)
  → step_5 transport z portu (PLN, stała)
  → step_6 akcyza % per paliwo (liczona od bazy = po step_5)
  → step_7 homologacja (PLN, stała)
  → step_8 prowizja_wewnetrzna % (min 11000, liczona od bazy = po step_7)
  → step_9 VAT 23% (liczony od pełnej sumy 1..8)
  → zaokrąglenie → cena_koncowa_pln

Tryb CIF USD override (sprzedawca nadpisał _asiaauto_cif_price_usd):
  step_1_cena_bazowa_usd     = cif_price_usd (cała kwota override'u)
  step_2_chiny_transport_usd = 0 (wliczone w CIF sprzedawcy)
  cif_usd = cif_price_usd
  cif_pln = cif_usd × kurs_usd_pln
  → kroki 3–9 identyczne jak w trybie CNY
```

**Efekt praktyczny:** zmiana `kurs_cny_usd` w configu wpływa tylko na listingi w trybie
CNY (tryb override ma twarde USD niezależne od kursu CNY→USD). Zmiana `kurs_usd_pln`
wpływa na wszystkie listingi — bo konwersja USD→PLN jest wspólna dla obu trybów.

#### Struktura JSON `_asiaauto_price_breakdown` v2

```json
{
  "version": 2,
  "input_mode": "cny_auto_api" | "cif_usd_manual",
  "fx": {
    "kurs_cny_usd": 0.15,
    "kurs_usd_pln": 3.70,
    "snapshot_at": "2026-04-14T..."
  },
  "source_price": {
    "currency": "CNY" | "USD",
    "amount": 220000,
    "source": "dongchedi_api" | "manual_cif_usd"
  },
  "step_1_cena_bazowa": { "currency": "USD", "amount_usd": 33000, "amount_pln": 122100 },
  "step_2_chiny_transport": { "currency": "USD", "amount_usd": 5000, "amount_pln": 18500 },
  "cif": { "amount_usd": 38000, "amount_pln": 140600 },
  "step_3_agencja": { "currency": "PLN", "amount_pln": 2600 },
  "step_4_clo": { "currency": "PLN", "percent": 10, "base_pln": 143200, "amount_pln": 14320 },
  "step_5_transport_portu": { "currency": "PLN", "amount_pln": 3400 },
  "step_6_akcyza": {
    "currency": "PLN",
    "percent": 0,
    "fuel_type": "electric",
    "base_pln": 160920,
    "amount_pln": 0
  },
  "step_7_homologacja": { "currency": "PLN", "amount_pln": 1500 },
  "step_8_prowizja_wewnetrzna": {
    "currency": "PLN",
    "percent": 10,
    "min_pln": 11000,
    "base_pln": 162420,
    "calculated_pln": 16242,
    "amount_pln": 16242
  },
  "step_9_vat": { "currency": "PLN", "percent": 23, "base_pln": 178662, "amount_pln": 41092.26 },
  "total": {
    "subtotal_pln": 219754.26,
    "rounding_pln": 1000,
    "cena_koncowa_pln": 220000
  },
  "_legacy_flat": {
    "cena_bazowa_pln": 122100,
    "transport_ubezpieczenie": 18500,
    "clo_kwota": 14320,
    "transport_krajowy": 3400,
    "rejestracja_homologacja": 1500,
    "prowizja_kwota": 16242,
    "vat_kwota": 41092.26,
    "cena_koncowa": 220000
  }
}
```

**`_legacy_flat` + duplikacja tych samych kluczy na top-level** zapewnia backward compat
dla starego kodu odczytującego v1 klucze (np. `renderCardPrice()` w order admin dla
starych zamówień). Stare karty zamówień pokazują stare pozycje bez modyfikacji, nowe
zamówienia dostają nowy layout z USD + kursami.

#### Meta listingu v0.30.5

| Meta key | Status | Opis |
|---|---|---|
| `_asiaauto_original_price` | istnieje, bez zmian | CNY z auto-api, ustawiane przez importer przy sync |
| `_asiaauto_cif_price_usd` | **NOWE** | Opcjonalny override ręczny (priorytet > CNY w `applyToListing()`) |
| `_asiaauto_cif_price_pln` | **WYGASZANY** | Auto-migracja przy pierwszym `applyToListing()` — dzieli przez `kurs_usd_pln`, zapisuje do `_usd`, usuwa stary klucz, loguje info |

#### Config klucze v0.30.5 (po lazy migration)

```
# Nowe klucze
kurs_cny_usd                     = 0.15
kurs_usd_pln                     = 3.70
chiny_rejestracja_transport_usd  = 5000     # USD (było 18000 PLN)

# Bez zmian vs v0.29.0
agencja_celna_wyladunek          = 2600     # PLN
clo_procent                      = 10.0     # %
transport_z_portu                = 3400     # PLN
akcyza_fuel_map                  = { petrol: 3.1, diesel: 3.1, hybrid: 1.55,
                                      mhev: 1.55, phev: 0, electric: 0,
                                      erev: 0, cng: 3.1, "bi-fuel": 3.1 }
homologacja_detailing            = 1500     # PLN
prowizja_procent                 = 10.0     # %  (marża wewnętrzna)
prowizja_min                     = 11000    # PLN (marża wewnętrzna, min)
vat_procent                      = 23.0     # %
zaokraglenie                     = 1000     # PLN

# Usunięte (po lazy migration w getConfig())
kurs_cny_pln                     → przeliczone na kurs_cny_usd
chiny_rejestracja_transport      → przeliczone na _usd
```

#### Pliki dostarczone w v0.30.5 (5 plików)

Wszystkie w `/mnt/user-data/outputs/asiaauto-v0.30.5/`, wszystkie lint-clean
(`php -l`), wszystkie pokryte testami jednostkowymi (42/42 PASS — `test_math.php` 7/7
+ `test_integration.php` 35/35).

| Plik | Zakres zmian v0.30.5 |
|---|---|
| `class-asiaauto-price.php` | Pełen rewrite. Nowe `defaults()`, lazy migration w `getConfig()`, `saveConfig()` zapisuje tylko nowe klucze. `calculateFromCny()` (USD pipeline) + nowe `calculateFromCifUsd()`. Alias `calculate()` → `calculateFromCny()`, alias `calculateFromCif($pln)` → `calculateFromCifUsd($pln / kurs_usd_pln)`. Prywatny helper `runPipelineFromCif($cif_pln)` dla wspólnych kroków 3–9 (zastępuje dawny `runPipelineFromStep3()`). Nowy helper `buildLegacyFlat()` produkujący klucze v1 dla backward compat. `applyToListing()` z priorytetem CIF USD > CNY + auto-migracja legacy `_asiaauto_cif_price_pln` → `_usd`. `persistBreakdown()`, `getBreakdown()` z lazy upgrade v1→v2 przez prywatny `upgradeLegacyBreakdown()` (rekonstruuje pola USD z PLN dzielonym przez aktualny `kurs_usd_pln`, bez zapisu do DB). `bulkRecalculate()` z trzema zbiorami (CIF USD, CNY minus CIF USD, legacy CIF PLN minus pozostałe); statsy: `cif_usd_count`, `cny_count`, `legacy_count`, `price_changes`, warning w logu przy overlap. `formatBreakdown()` dual-branch v2/v1. Stałe: `BREAKDOWN_VERSION = 2`, `META_CIF_USD = '_asiaauto_cif_price_usd'`, `META_CIF_PLN_LEGACY = '_asiaauto_cif_price_pln'`. |
| `class-asiaauto-admin.php` | Tab „Kalkulator cen" przebudowany na **8 kolorowych kart sekcyjnych** na wzór Kroku 4 GRUPA 9 (order admin config): 💱 Kursy walut (kurs_cny_usd + kurs_usd_pln + live efektywny CNY→PLN), 🇨🇳 Chiny/rejestracja/transport USD, 📦 Agencja celna, 🛃 Cło + transport z portu, ⛽ Akcyza per paliwo (full width, 9 kolumn fuel), 📋 Homologacja/detailing, 💰 Prowizja wewnętrzna (z jawnym hintem „to marża ukryta, NIE ma nic wspólnego z §3 umowy"), 🧾 VAT + zaokrąglenie. Nowy endpoint AJAX `wp_ajax_asiaauto_bulk_recalc` + przycisk „🔄 Przelicz wszystkie listingi" z live counts z DB (cnt_cif_usd, cnt_cny, cnt_legacy) + progress div. `renderPricePreview()` pokazuje 10 mieszanych przykładów (8 CNY + 2 CIF USD) w kolumnach v2: Tryb, Wejście, Baza USD, +Chiny USD, CIF PLN (highlight), kroki 3–9, Cena końcowa (zielone tło), W bazie (⚠/✓). |
| `class-asiaauto-listing-editor.php` | Pole `_asiaauto_cif_price_usd` w metaboksie „Dane pojazdu" (opcjonalne — puste = listing używa ceny CNY z auto-api). Live preview wyliczonego CIF z `cena_cny × kurs_cny_usd + chiny_rejestracja_transport_usd`. AJAX endpoint `asiaauto_preview_cif_breakdown` wywołujący `calculateFromCifUsd`. `DUP_BLOCKED_META` zawiera oba klucze cen (nowy USD i legacy PLN) — duplikacja czyści oba. |
| `class-asiaauto-admin-manual-import.php` | JS preview breakdownu kompletnie przepisany pod strukturę v2. Archaiczne klucze z v0.27 (`transport_ubezpieczenie`, `rejestracja_homologacja`, `subtotal`) — usunięte z renderera. Nowe kolumny: CNY wejście → step_1 USD → step_2 USD → CIF PLN → agencja → cło → transport → akcyza → homologacja → prowizja → VAT → cena końcowa. Czyta `price_breakdown.step_1_cena_bazowa.amount_usd`, `.cif.amount_pln`, `.step_4_clo.amount_pln` itd. |
| `class-asiaauto-order-admin.php` | `renderCardPrice()` rozszerzony o dispatcher v1/v2 (`$is_v2 = $breakdown['version'] >= 2`). Nowy prywatny helper `renderCardPriceBreakdownV2()` (~115 linii): tryb wejścia + kursy w nagłówku, etapy USD na żółtym tle (#fff8dc), CIF razem na turkusowym (#e6fffa z border-top #38b2ac), etapy 3–9 PLN bez tła, cena końcowa na zielonym (#f0fff4, kolor tekstu #276749). Stare zamówienia (v1) dostają automatycznie stary layout przez `_legacy_flat` (zero regresji). Docblock klasy rozszerzony o wpis `@modified 0.30.5`. Wersja CSS enqueue podbita z `0.30.4` na `0.30.5`. |

#### Testy wewnętrzne (42/42 PASS)

Testy zlokalizowane w `/home/claude/asiaauto-v0.30.5/test_math.php` i `test_integration.php`.
Uruchamiane przez `php test_math.php` i `php test_integration.php` bez dodatkowej konfiguracji
(autonomiczne, stubują WordPress).

**Math (7/7 PASS):**
- TEST 1: Listing #94647 Hongqi HS7 PHEV (cny=153 800) → v1: 168 000 PLN, v2: 166 000 PLN (Δ = −2 000, efekt zmiany kursu 0.57 → 0.555)
- TEST 2: Listing #222255 XPeng electric (legacy CIF PLN=100 000 → migracja CIF USD = 27 027.03) → v1: 160 000, v2: 160 000 (Δ = 0, dokładna równość po migracji)
- TEST 3: Edge case puste kursy (`kurs_cny_usd = 0`) → graceful `przeliczone = false`, cena 0, version 2
- TEST 4: Backward compat alias — `calculate()` === `calculateFromCny()` (MATCH ✓)
- TEST 5: `calculateFromCif(100000 PLN, electric)` → 160 000 zł (zgodne z TEST 2)
- TEST 6: Lazy upgrade v1→v2 rekonstruuje `cif_usd = 28 558.37`, `step_1_usd = 23 693.51` z v1 breakdownu bez zapisu do DB, `_legacy_flat` obecne
- TEST 7: `formatBreakdown()` output dual-branch dla obu trybów (CNY + CIF USD override)

**Integration (35/35 PASS):**
- 29 asercji zgodności kluczy JS ↔ PHP breakdown v2 (version, input_mode, source_price, fx, wszystkie step_*, cif, total)
- 6 asercji legacy flat keys (cena_bazowa_pln, transport_ubezpieczenie, clo_kwota, transport_krajowy, rejestracja_homologacja, prowizja_kwota) — zgodność między top-level a `_legacy_flat`
- `input_mode` rozróżnia `cny_auto_api` vs `cif_usd_manual`
- W trybie CIF USD override: `step_2_chiny_transport.amount_usd = 0` ✓
- `applyToListing()` priorytet CIF USD > CNY zweryfikowany w źródle

#### Stan wdrożenia po sesji 2026-04-14

Pliki leżą w `/mnt/user-data/outputs/asiaauto-v0.30.5/`, **user nie wgrał ich jeszcze
na serwer**. User zasygnalizował że w nowym wątku będzie miał uwagi do implementacji
Kroku 7 zanim wrzuci pliki — czyli przy wznowieniu należy być gotowym na iteracje i
korekty na bazie stanu sandboxowego (nie produkcyjnego).

#### Protokół deployu (po zamknięciu iteracji nad uwagami)

1. **Backup** obecnych 5 plików z `wp-content/plugins/asiaauto-sync/includes/`
2. Wgraj nowe 5 plików v0.30.5 na serwer
3. Odśwież panel admina (Ctrl+F5)
4. Wejdź w **AsiaAuto → Kalkulator cen** — sprawdź że zakładka otwiera się bez błędów,
   8 kart sekcyjnych widoczne, podgląd (10 przykładów) po prawej renderuje się
5. **Nie zmieniając configu**, kliknij przycisk „🔄 Przelicz wszystkie listingi" —
   to jednorazowy bulk recalc dla całej bazy. Lazy migration starych kluczy configu
   uruchomi się przy pierwszym `getConfig()`, więc wyniki powinny być numerycznie
   bardzo blisko obecnych cen (akceptowalny dryf ~1-2%)
6. Weryfikacja baseline:
   - `#94647` → cena końcowa **166 000 PLN** (było 168 000, Δ = −2 000)
   - `#222255` → cena końcowa **160 000 PLN** (bez zmian, auto-migracja CIF PLN → USD)
7. Test metaboxu listingu: otwórz edycję dowolnego listingu → w sekcji „Dane pojazdu"
   widoczne pole **CIF USD** (puste = używa CNY z API, wpisana kwota = tryb override)
8. Test karty zamówienia (nowe): otwórz dowolne nowe zamówienie → karta „Cena" pokazuje
   breakdown v2 (tryb + kursy w nagłówku, etapy USD na żółtym, CIF na turkusowym, etapy
   PLN + cena końcowa na zielonym)
9. Test karty zamówienia (stare): otwórz zamówienie sprzed deployu → karta „Cena" nadal
   pokazuje stary layout v1 (backward compat przez `_legacy_flat`, zero regresji)

#### Drobny kosmetyk — poza zakresem Kroku 7

W `class-asiaauto-order-admin.php::enqueueStyles()` jest dziedziczony z produkcji
nieprawidłowy indent (3 spacje + zamykający `}` na kolumnie 0 zamiast 4 spacji i wcięcia
klamry). Funkcjonalnie nie przeszkadza — `php -l` przechodzi, klamry się bilansują.
Nie ruszałem tego w Kroku 7, żeby nie mieszać zakresów. Do ewentualnego wyczyszczenia
osobnym patch w nowym wątku.

### Krok 8 — Załącznik nr 2 (kalkulacja kosztów etapów)

Status: **do zrobienia po Kroku 7 deploy**, wymaga potwierdzenia że nie istnieje już
w `class-asiaauto-contract.php::renderAttachment2()`.

Po wdrożeniu Kroku 7 i zamknięciu iteracji nad uwagami, generujemy trzecią stronę PDF
z tabelą etapów na podstawie breakdownu v2. Format 1:1 z umową klienta: pokazujemy
CIF (USD + PLN) + etapy 3–9 (PLN) + cena końcowa. Podpisy u dołu strony.

**Kluczowe: `step_8_prowizja_wewnetrzna.amount_pln` NIGDY nie pojawia się w załączniku 2.**
Klient widzi wyłącznie CIF + PLN-owe koszty państwowe (agencja, cło, transport portu,
akcyza, homologacja) + VAT + cena końcowa. Marża wewnętrzna jest ukryta w końcowej cenie
i nie ma prawa wycieknąć do załącznika.

Otwarte decyzje (do rozstrzygnięcia przed kodowaniem):
- Czy pokazywać rozbicie CIF na step_1 i step_2 USD, czy tylko zagregowane CIF USD/PLN?
- Jak zaprezentować wiersz prowizji wewnętrznej, żeby klient tego nie widział — zaszyć
  w „cenie bazowej" (subtelne) czy pominąć zupełnie (wtedy suma etapów ≠ cena końcowa)?
- Czy wzmianka o kursie USD/PLN w przypisie załącznika?
- Format aneksów — czy przygotowujemy od razu szablon aneksu do korygowania pozycji
  po doprecyzowaniu kwot (np. po wycenie transportu morskiego)?

### Zasady pracy (te same co w Contract Rework GRUPA 9)

- Jeden krok = jedno wdrożenie, potwierdzenie użytkownika między krokami
- **Migracja breakdownu v1→v2 jest jednokierunkowa ale lazy** — odczyt starych
  breakdownów przez `upgradeLegacyBreakdown()` rekonstruuje pola USD z PLN dzielonym
  przez aktualny `kurs_usd_pln` bez zapisu do DB. Real-time rewrite do v2 dzieje się
  dopiero przy następnym `applyToListing()` / bulk recalc
- **Pipeline cenowy pod klucz musi pozostać numerycznie zbliżony** przed i po
  migracji — akceptowalny dryf ~1–2% wynikający ze zmiany efektywnego kursu
  (0.57 → 0.555). Przy większych odchyleniach: abort + analiza przed deployem
- Marża wewnętrzna `step_8_prowizja_wewnetrzna` zawsze ukryta przed klientem — nigdy
  nie w załączniku 2, nigdy nie w mailach statusów, nigdy w wizardzie. Widoczna tylko
  w panelu admina (`renderCardPrice()`) i w `formatBreakdown()` dla CLI/logów
- **Wynagrodzenie §3 umowy (`_order_contract_commission_net`) i marża wewnętrzna
  (`step_8_prowizja_wewnetrzna`) to niezależne byty** — zero mappingu między nimi.
  Krok 7 nie modyfikuje `calculateContractCommission()` ani `META_CONTRACT_COMMISSION_NET`
- Testy na zamówieniu testowym #222262 po każdym kroku, weryfikacja przez MCP
  (`order_details`, `listing_details`, `read_file` na aktualny stan pliku)

### Krok 9 — Override resilience + widget redesign + profiler v0.30.6 (DONE 2026-04-15, czeka na deploy)

Trzy patche, każdy izolowany, wszystkie po `php -l` clean. Sandbox: `/home/claude/asiaauto-v0.30.6/`.
Output: `/mnt/user-data/outputs/asiaauto-v0.30.6/`.

#### Patch A — `class-asiaauto-admin.php` (kolumna CIF dwuwierszowa + profiler)

**Element 1 — kolumna CIF.** Wcześniej w `renderPricePreview()` była kolumna „CIF PLN" jednowierszowa
z samą wartością PLN. Sprzedawca otwierając metabox ręcznych ogłoszeń widział pole „CIF (USD) — OVERRIDE"
z wartością w dolarach (i wyliczoną referencją obok), ale w tabelce podglądu kalkulacji widział tylko PLN.
Niespójność narracyjna z głównym pipelineiem USD-centric.

Po patchu kolumna nazywa się tylko „CIF" i jest dwuwierszowa:

```html
<span class="aa-cif-cell">
    <span class="aa-cif-usd">{cif_usd} $</span>     <!-- bold, kolor #1e293b, normalny rozmiar -->
    <span class="aa-cif-pln">{cif_pln} zł</span>    <!-- font-size:11px, kolor #64748b -->
</span>
```

Source: `$b['cif']['amount_usd']` i `$b['cif']['amount_pln']` z breakdown v2 (oba pola natywne od v0.30.5,
nie wymagało zmian w pipeline'ie). Liczba kolumn 16 → 16 (bez zmian). CSS dodany inline w `renderPriceTab`:
`.aa-cif-cell { line-height:1.25 }`, `.aa-cif-usd { display:block; font-weight:700 }`, `.aa-cif-pln { display:block; font-size:11px; margin-top:2px }`.

**Element 2 — profiler `?aa_profile=1`.** Dwa pola klasy:

```php
private array $profile = [];
private bool $profile_enabled = false;
```

Trzy metody helper (`profileStart`, `profileEnd`, `renderProfileReport`) — wszystkie no-op gdy
`$profile_enabled === false`. Aktywacja w `renderPage()`:

```php
$this->profile_enabled = isset($_GET['aa_profile']) && $_GET['aa_profile'] === '1' && current_user_can('manage_options');
```

Czternaście punktów pomiaru rozsianych po metodach renderujących:

```
renderPage:total
renderPage:get_options
renderPage:rotation_stats
renderStatus
renderStatus:disk_usage
renderPriceTab
renderPriceTab:fuel_terms
renderPriceTab:counts_postmeta       ← 3× COUNT(*) na postmeta — najpewniejszy bottleneck
renderPricePreview:total
renderPricePreview:select_ids
renderPricePreview:select_meta
renderPricePreview:fuel_map
renderPricePreview:loop_calc
renderFiltersTab
```

Output: HTML comment u dołu strony (ASCII tabela) + visible warning bar z collapsowaną tabelą inline.
Kolory wartości: <30ms szare, 30–100ms pomarańczowe, >100ms czerwone. Sumaryczny czas wyświetlany
zawsze, dla szybkiego porównania run-to-run.

Po wgraniu i analizie outputu profilera planowana optymalizacja bottlenecka — najpewniej cache transient
na 3× COUNT z invalidate przy `ajaxBulkRecalc` i `saveConfig`. To trafi w osobny Krok 9.5 / 10.

#### Patch B — `class-asiaauto-importer.php::updateListing()` (Dziura 1: meta CNY dryf)

**Diagnoza.** W oryginalnym `updateListing()` przy zmianie ceny wywoływany był tylko
`AsiaAuto_Price::applyToListing($post_id, $new_price_cny)`, a w środku `applyToListing()` priorytet jest:

1. CIF USD > 0 → `calculateFromCifUsd()` + `persistBreakdown()` + `return $breakdown` (early return)
2. CNY > 0 → `update_post_meta(META_ORIGINAL, $price_cny)` + `calculateFromCny()` + `persistBreakdown()`

Czyli jeśli listing miał ustawione `_asiaauto_cif_price_usd > 0`, funkcja kończyła się w gałęzi CIF
**zanim** dotarła do `update_post_meta(META_ORIGINAL, ...)`. W praktyce: cena CNY z aukcji zmienia się
co tydzień (Dongchedi cuts), `logPriceChange()` w `updateListing` zapisuje to do `_asiaauto_price_history`,
ale `_asiaauto_original_price` zostaje na wartości sprzed override **na zawsze**. Skutki:
- Kolumna „Wejście" w widget preview pokazywała 189 800 ¥ zamiast aktualnej 165 000 ¥
- Admin nie miał sygnału że na Dongchedi cena spadła i opłaca się usunąć override
- Niespójność między `_asiaauto_price_history` (rośnie) a `_asiaauto_original_price` (zamrożony)

**Fix.** Jedna nowa linia w `updateListing()` — `update_post_meta` przed `applyToListing`:

```php
if (isset($data['new_price']) || isset($data['price'])) {
    $new_price_cny = $data['new_price'] ?? $data['price'];
    $this->logPriceChange($post_id, $new_price_cny);

    // v0.30.6: Reference CNY meta is always refreshed — independent of pricing mode.
    // applyToListing() decides whether to use it (MODE CNY) or ignore it (MODE CIF USD override).
    update_post_meta($post_id, '_asiaauto_original_price', (float) $new_price_cny);

    AsiaAuto_Price::applyToListing($post_id, (float) $new_price_cny);
}
```

Cena PLN klienta (`price`) i `_asiaauto_price_breakdown` — bez zmian funkcjonalnych. Override CIF USD
nadal wygrywa, breakdown nadal generowany z CIF USD. Zmieniła się tylko semantyka meta CNY: teraz to
**zawsze aktualna cena referencyjna z aukcji**, niezależnie od trybu wyceny pod klucz.

#### Patch C — `class-asiaauto-price.php` (Dziura 2: warning spam)

W `applyToListing()` (linia ~649) i `bulkRecalculate()` (linia ~896) były dwa wywołania
`AsiaAuto_Logger::warning(...)` z komunikatem o overlap CIF USD + CNY. Audit warning miał sens jako
sygnał do wykrywania niezamierzonego overlap, ale dla świadomego override (które jest dokładnie tym
do czego pole `_asiaauto_cif_price_usd` w metaboksie zostało stworzone) generowało:

- 1 wpis WARNING per listing per sync (cron co 15 min)
- N wpisów WARNING na każdy bulk recalc

Zdegradowane do `info(...)` z dopiskiem `(intentional override)` / `(intentional)`. Komunikat zostaje,
ale poziom logowania nie zaśmieca strumienia warningów (które powinny zostawać na realne anomalie).

#### Bilans odporności override CIF USD na sync (po wgraniu Patchy B+C)

| Aspekt | Stan | Komentarz |
|--------|------|-----------|
| `price` (cena PLN klienta) | ✅ niezmieniona | override wygrywa w `applyToListing` |
| `_asiaauto_price_breakdown` v2 | ✅ niezmieniony | recalc na bazie CIF USD |
| `_asiaauto_cif_price_usd` (override) | ✅ nigdy nie nadpisywane | zero ścieżki w sync flow która by to ruszała |
| Mileage, location, equipment, images | ✅ aktualizowane normalnie | niezależne od pricing logic |
| `_asiaauto_price_history` | ✅ logowane zawsze (CNY z API) | śledzi cenę aukcji niezależnie od trybu |
| `_asiaauto_original_price` | ✅ aktualizowane zawsze (Patch B) | meta referencyjna spójna z aukcją |
| Reserved listing | ✅ skip całego update | `break` w sync run() przed `updateListing` |
| Event `removed` z API | ✅ idzie do `markRemoved()` | override nie ratuje listingu który zniknął z aukcji — nie da się go kupić |
| Audit log overlap | ✅ INFO (Patch C) | nie generuje fałszywych warningów |

#### Pliki dostarczone w v0.30.6

Wszystkie w `/mnt/user-data/outputs/asiaauto-v0.30.6/`, wszystkie po `php -l class-asiaauto-*.php`
clean (PHP 8.3.6).

| Plik | Zakres zmian v0.30.6 |
|------|---------------------|
| `class-asiaauto-admin.php` | Patch A: kolumna CIF dwuwierszowa + profiler `?aa_profile=1` (14 punktów). Docblock klasy + `renderPricePreview()` rozszerzone o `@since 0.30.6`. Tekst intro „Pipeline v0.30.5" → „v0.30.6". |
| `class-asiaauto-importer.php` | Patch B: 1 nowa linia `update_post_meta('_asiaauto_original_price')` w `updateListing()` przed `applyToListing()`. Docblock klasy + `updateListing()` rozszerzone o uzasadnienie. |
| `class-asiaauto-price.php` | Patch C: `warning(...)` → `info(...)` w `applyToListing()` i `bulkRecalculate()` w gałęzi „CIF wins". Docblock klasy: nowy wpis `@since 0.30.6`. |

#### Stan wdrożenia po sesji 2026-04-15

Pliki leżą w `/mnt/user-data/outputs/asiaauto-v0.30.6/`, **user nie wgrał ich jeszcze na serwer**.
Po wgraniu i pomyślnych testach (#1 kolumna CIF, #2 profiler output, #3 sync na listingu z override):
1. Podbicie `ASIAAUTO_VERSION` w `asiaauto-sync.php` z `0.29.0-wip` na `0.30.6`
2. Aktualizacja wpisu pluginu w sekcji „Active Plugins" tego skilla
3. Usunięcie bloku „🚀 PENDING UPDATE — v0.30.6" z góry skilla (wpisy historyczne zostają tu, w Kroku 9)
4. Analiza outputu profilera z testu #2 → planowanie Kroku 9.5 (cache transient na 3× COUNT)
