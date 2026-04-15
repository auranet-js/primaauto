# Ścieżki produkcyjne — asiaauto.pl

> Aktualizacja: 2026-04-15.

## Serwer

| Element | Wartość |
|---------|---------|
| Hosting | Hostido, serwer Elara (`elara.hostido.net.pl`) |
| Konto | `host476470` |
| PHP | 8.3 (GD + Imagick) |
| MariaDB | 10.11 |
| WordPress | 6.9.4 |
| DB prefix | `wp7j_` |

## Ścieżki

| Element | Ścieżka |
|---------|---------|
| WP root | `~/domains/asiaauto.pl/public_html/` |
| Plugin | `~/domains/asiaauto.pl/public_html/wp-content/plugins/asiaauto-sync/` |
| Plugin includes | `.../asiaauto-sync/includes/` (24 klas PHP) |
| Plugin CLI | `.../asiaauto-sync/cli/class-asiaauto-cli.php` |
| Plugin data | `.../asiaauto-sync/data/` (mapy tłumaczeń) |
| Plugin diag | `.../asiaauto-sync/diag/` (skrypty diagnostyczne) |
| Plugin logs | `.../asiaauto-sync/logs/asiaauto-sync.log` |
| Plugin assets CSS | `.../asiaauto-sync/assets/css/` |
| Plugin assets JS | `.../asiaauto-sync/assets/js/` |
| Child theme | `~/domains/asiaauto.pl/public_html/wp-content/themes/asiaauto/` |
| Zdjęcia listingów | `~/domains/asiaauto.pl/public_html/wp-content/uploads/asiaauto/YYYY/MM/` |
| Umowy PDF | `~/domains/asiaauto.pl/public_html/wp-content/uploads/asiaauto/contracts/` |
| mPDF temp | `/tmp/mpdf` |
| MCP server | `~/domains/asiaauto.pl/public_html/mcp-test/mcp.php` |
| Repo kontekstowe | `~/projekty/primaauto/` |
| SKILL.md | `~/projekty/primaauto/tmp/SKILL.md` |
| Debug log | `~/domains/asiaauto.pl/public_html/wp-content/debug.log` |

## wp-config.php — klucze specyficzne dla AsiaAuto

```
ASIAAUTO_API_KEY          — klucz auto-api.com
ASIAAUTO_API_BASE_URL     — https://api1.auto-api.com
ASIAAUTO_GEMINI_API_KEY   — klucz Google Gemini (tłumaczenia)
ASIAAUTO_DEEPL_API_KEY    — klucz DeepL (fallback)
ASIAAUTO_SOURCES          — array źródeł (dongchedi)
```

## wp_options — klucze AsiaAuto

| Klucz | Zawartość |
|-------|-----------|
| `asiaauto_import_config` | Config filtrów importu (marki, lata, ceny, km) |
| `asiaauto_price_config` | Config pipeline'u cenowego (kursy, stawki, prowizja) |
| `asiaauto_order_config` | Config zamówień (18 params: depozyt, firma, bank, wynagrodzenie) |
| `asiaauto_order_email_templates` | JSON — 15+1 szablonów maili |
| `asiaauto_order_status_display` | JSON — 11 statusów (label, kolor, opis) |
| `asiaauto_sync_enabled` | 0/1 — sync on/off |
| `asiaauto_last_change_id_dongchedi` | Ostatni change_id z API |
| `asiaauto_sync_history` | JSON — historia sync runs |

## Baza danych — kluczowe tabele

| Tabela | Dane AsiaAuto |
|--------|---------------|
| `wp7j_posts` | CPT `listings` + `asiaauto_order` |
| `wp7j_postmeta` | Wszystkie `_asiaauto_*` meta, `price`, `gallery`, `_thumbnail_id` |
| `wp7j_terms` + `wp7j_term_taxonomy` | 10 taksonomii listingów |
| `wp7j_term_relationships` | Powiązania listing ↔ taksonomie |
| `wp7j_options` | Config keys powyżej |
| `wp7j_usermeta` | Dane billing klientów, magic link tokens |
