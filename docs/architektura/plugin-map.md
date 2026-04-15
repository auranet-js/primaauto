# Mapa pluginu asiaauto-sync

> Aktualizacja: 2026-04-15. Źródło: analiza kodu produkcyjnego v0.29.0-wip (z patchami v0.30.6).

## Bootstrap

`asiaauto-sync.php` (192 linii) — require 24 klas, instancjuje 13 globalnie + 5 admin-only + 1 CLI.

## Klasy — przegląd

| Klasa | Plik | Linii | Rola |
|-------|------|-------|------|
| `AsiaAuto_CPT` | class-asiaauto-cpt.php | 317 | CPT `listings`, 10 taksonomii, 16 meta REST, admin columns |
| `AsiaAuto_Price` | class-asiaauto-price.php | 1065 | Pipeline cenowy 9 kroków, dual mode CNY/CIF USD, breakdown v2 |
| `AsiaAuto_Importer` | class-asiaauto-importer.php | 577 | Import listingów z API, tłumaczenia, zdjęcia, ceny |
| `AsiaAuto_Sync` | class-asiaauto-sync.php | 308 | Incremental sync via /changes, transient lock, reservation guard |
| `AsiaAuto_Rotation` | class-asiaauto-rotation.php | 282 | Lifecycle: sold→draft→trash→delete, ochrona rezerwacji |
| `AsiaAuto_Taxonomy` | class-asiaauto-taxonomy.php | 218 | Sync filtrów API → taksonomie WP |
| `AsiaAuto_Translator` | class-asiaauto-translator.php | 770 | 8 map statycznych, Gemini/DeepL, extra_prep, complectation |
| `AsiaAuto_Media` | class-asiaauto-media.php | 654 | Pobieranie zdjęć, WebP, SEO naming, storeLocalUpload |
| `AsiaAuto_API` | class-asiaauto-api.php | 98 | Wrapper na AutoApi\Client z 3x retry |
| `AsiaAuto_Admin` | class-asiaauto-admin.php | 1140 | Config importu/cen, sync toggle, profiler, bulk recalc |
| `AsiaAuto_Admin_Manual_Import` | class-asiaauto-admin-manual-import.php | 675 | Import pojedynczych ofert Dongchedi z admina |
| `AsiaAuto_Listing_Editor` | class-asiaauto-listing-editor.php | 954 | Metabox "Dane pojazdu", duplikacja, AJAX CIF preview |
| `AsiaAuto_Gallery_Metabox` | class-asiaauto-gallery-metabox.php | 834 | Galeria drag&drop, WebP upload, 4 AJAX endpoints |
| `AsiaAuto_Order` | class-asiaauto-order.php | 1358 | CPT `asiaauto_order`, 11 statusów, CRUD, customer, auth, mail |
| `AsiaAuto_Order_Content` | class-asiaauto-order-content.php | 784 | Szablony maili (15+1), etykiety statusów, wp_options |
| `AsiaAuto_Order_Admin` | class-asiaauto-order-admin.php | 1933 | Panel zamówień: lista, karta, config 3 taby |
| `AsiaAuto_Order_API` | class-asiaauto-order-api.php | 910 | REST API wizarda: 8 endpoints, auth, rate limit |
| `AsiaAuto_Order_Wizard` | class-asiaauto-order-wizard.php | 690 | Frontend wizard shortcode, 5 kroków, magic link |
| `AsiaAuto_Contract` | class-asiaauto-contract.php | 1075 | PDF umowy mPDF, §1-§9 model agencyjny, deferred cron |
| `AsiaAuto_Inventory` | class-asiaauto-inventory.php | 1506 | Strona ofert, 4 REST endpoints, faceted counts, search |
| `AsiaAuto_Single` | class-asiaauto-single.php | 398 | Strona pojedynczego listingu |
| `AsiaAuto_Shortcodes` | class-asiaauto-shortcodes.php | 2255 | 17 shortcodes (gallery, specs, badges, CTA, 404...) |
| `AsiaAuto_Homepage` | class-asiaauto-homepage.php | 669 | [asiaauto_homepage] — hero, stats, latest, Schema.org |
| `AsiaAuto_Contact` | class-asiaauto-contact.php | 489 | [asiaauto_contact] — dane firmy, mapa, Schema.org |
| `AsiaAuto_Redirects` | class-asiaauto-redirects.php | 93 | Custom 404 dla /oferta/ — ustawia kontekst marki |
| `AsiaAuto_Security` | class-asiaauto-security.php | 197 | Login /biuro/, rola primaauto, admin cleanup |
| `AsiaAuto_Login` | class-asiaauto-login.php | 316 | Branded login screen (text logo, Inter, navy/red) |
| `AsiaAuto_Logger` | class-asiaauto-logger.php | 39 | Logging do pliku |
| `AsiaAuto_UI_Translator` | class-asiaauto-ui-translator.php | 54 | Tłumaczenie UI |
| `AsiaAuto_CLI` | cli/class-asiaauto-cli.php | 2199 | 18 komend WP-CLI |

**Razem: ~23 050 linii PHP** (bez vendor/).

## Zależności między klasami

```
AsiaAuto_Sync
  ├── AsiaAuto_API (wrapper HTTP)
  ├── AsiaAuto_Importer
  │     ├── AsiaAuto_Translator (mapy + Gemini/DeepL)
  │     ├── AsiaAuto_Media (zdjęcia + WebP)
  │     └── AsiaAuto_Price (pipeline cenowy)
  └── AsiaAuto_Rotation (lifecycle listingów)

AsiaAuto_Order (centralny model zamówień)
  ├── AsiaAuto_Order_Content (szablony maili, etykiety)
  ├── AsiaAuto_Order_Admin (panel admin)
  ├── AsiaAuto_Order_API (REST wizard)
  ├── AsiaAuto_Order_Wizard (frontend shortcode)
  └── AsiaAuto_Contract (PDF mPDF)

AsiaAuto_Listing_Editor
  ├── AsiaAuto_Price (calculateFromCifUsd, applyToListing)
  ├── AsiaAuto_Importer (buildListingSlug — static)
  └── AsiaAuto_Gallery_Metabox
        └── AsiaAuto_Media (storeLocalUpload)

AsiaAuto_Inventory (standalone — minimal deps)
  └── AsiaAuto_Order (getConfig — numer telefonu)

AsiaAuto_Logger — używany wszędzie
AsiaAuto_Security — ORDER_CAP, rola primaauto
```

## Crony

| Hook | Interwał | Callback |
|------|----------|----------|
| `asiaauto_sync_changes` | 15 min | `AsiaAuto_Sync::run('dongchedi')` (guarded `isEnabled()`) |
| `asiaauto_daily_cleanup` | daily 03:00 | `AsiaAuto_Rotation::cleanup()` |
| `asiaauto_generate_contract_pdf` | single event (5s) | `AsiaAuto_Contract::deferredGenerate()` |

## Image sizes

| Nazwa | Wymiary | Crop | Użycie |
|-------|---------|------|--------|
| `asiaauto-card` | 350×250 | tak | Karty inventory/homepage |
| `asiaauto-thumb` | 190×132 | tak | Małe miniaturki |
| `asiaauto-gallery` | 800×600 | tak | Galeria single listing |

## Composer

- `autoapi/client` — SDK auto-api.com (Guzzle 7)
- `mpdf/mpdf` — generowanie PDF umów
