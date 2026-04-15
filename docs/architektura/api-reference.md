# API Reference — asiaauto-sync

> Aktualizacja: 2026-04-15.

## REST API endpoints (namespace `asiaauto/v1`)

### Inventory (AsiaAuto_Inventory)

| Metoda | Endpoint | Parametry | Opis |
|--------|----------|-----------|------|
| GET | `/listings` | marka, model, paliwo, nadwozie, rocznik, kolor, cena_min, cena_max, sort, strona, reservation_status | Grid listingów (HTML + total + pages) |
| GET | `/models` | make (CSV slugów) | Modele per marka (zależny dropdown) |
| GET | `/filter-counts` | jak /listings | Faceted counts per taksonomia |
| GET | `/search-suggestions` | q (min 2 znaki) | Autocomplete max 8 wyników |

### Order Wizard (AsiaAuto_Order_API)

| Metoda | Endpoint | Auth | Opis |
|--------|----------|------|------|
| GET | `/listing/{id}/reserve` | public | Dostępność, cena, depozyt |
| POST | `/order/start` | public* | Rejestracja + nowe zamówienie |
| GET | `/order/{id}` | owner/admin | Pełny stan wizarda |
| POST | `/order/{id}/billing` | owner/admin | 12 pól danych billing |
| POST | `/order/{id}/upload` | owner/admin | Upload podpisanej umowy / dowodu wpłaty |
| POST | `/order/{id}/delete-upload` | owner/admin | Usuń upload przed potwierdzeniem |
| POST | `/order/{id}/confirm-signed` | owner/admin | Potwierdź podpis → status podpisane |
| GET | `/order/{id}/transfer` | owner/admin | Dane do przelewu |
| POST | `/auth/login` | public* | Logowanie klienta (email + hasło) |

*Rate limit 10/h + honeypot.

### Admin AJAX

| Action | Klasa | Opis |
|--------|-------|------|
| `asiaauto_bulk_recalc` | AsiaAuto_Admin | Masowe przeliczenie cen |
| `asiaauto_preview_offer` | AsiaAuto_Admin_Manual_Import | Preview oferty Dongchedi |
| `asiaauto_manual_import` | AsiaAuto_Admin_Manual_Import | Import oferty |
| `asiaauto_preview_cif_breakdown` | AsiaAuto_Listing_Editor | Preview breakdownu CIF |
| `asiaauto_gallery_upload` | AsiaAuto_Gallery_Metabox | Upload zdjęć galerii |
| `asiaauto_gallery_delete` | AsiaAuto_Gallery_Metabox | Usunięcie zdjęcia |
| `asiaauto_gallery_reorder` | AsiaAuto_Gallery_Metabox | Zmiana kolejności |
| `asiaauto_gallery_set_featured` | AsiaAuto_Gallery_Metabox | Ustawienie featured |

## WP-CLI (namespace `wp asiaauto`)

### Import / Sync

| Komenda | Opis |
|---------|------|
| `sync-filters --source=dongchedi` | Sync marek/modeli/filtrów z API |
| `import --source=dongchedi [--limit=N]` | Pełny import ofert |
| `sync --source=dongchedi` | Incremental sync via /changes |
| `download-missing-images [--limit=N]` | Pobierz brakujące zdjęcia |

### Listings

| Komenda | Opis |
|---------|------|
| `status` | Statystyki, sync state, cron, zdjęcia, config cen |
| `check --inner-id=X --source=Y` | Sprawdź listing w WP |
| `inspect --inner-id=X [--raw]` | Deep inspect: API vs WP |
| `inspect --offer-url=URL` | Inspect po URL Dongchedi |
| `restore --inner-id=X` | Przywróć usunięty listing |
| `fix-titles [--dry-run]` | Przebuduj tytuły z taksonomii |
| `fix-slugs [--dry-run]` | Napraw slugi listingów |

### Ceny

| Komenda | Opis |
|---------|------|
| `recalculate-prices [--dry-run]` | Masowe przeliczenie cen |
| `price-check --cny=80000` | Symulacja ceny dla kwoty CNY |
| `price-check --inner-id=X` | Breakdown dla konkretnego listingu |

### Czyszczenie

| Komenda | Opis |
|---------|------|
| `cleanup [--dry-run]` | Rotacja: draft→trash→delete |
| `purge-outdated [--dry-run] [--yes]` | Usunięcie listingów poza filtrami |
| `purge-all --yes` | Usuń WSZYSTKO (testing only) |

### Zamówienia

| Komenda | Opis |
|---------|------|
| `order-create --listing=X [--type=...]` | Utwórz zamówienie |
| `order-status <id> --status=X [--note=...]` | Zmień status |
| `order-list [--status=X] [--limit=N]` | Lista zamówień |

## auto-api.com (zewnętrzne)

SDK: `autoapi/client` (Composer). Wrapper: `AsiaAuto_API` z 3x retry.

| Metoda PHP | HTTP | Endpoint |
|-----------|------|----------|
| `getFilters($source)` | GET | `/api/v2/{source}/filters` |
| `getOffers($source, $params)` | GET | `/api/v2/{source}/offers` |
| `getOffer($source, $inner_id)` | GET | `/api/v2/{source}/offer?inner_id=X` |
| `getChangeId($source, $date)` | GET | `/api/v2/{source}/change_id?date=X` |
| `getChanges($source, $change_id)` | GET | `/api/v2/{source}/changes?change_id=X` |

Auth: `api_key` query param. Timeout: 30s. Retry: 3x exponential backoff (500ms base). AuthException: no retry.
