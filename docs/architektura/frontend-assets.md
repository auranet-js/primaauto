# Frontend assets + child theme

> Aktualizacja: 2026-04-15.

## CSS (assets/css/ — 70.5 KB)

| Plik | Rozmiar | Scope | Opis |
|------|---------|-------|------|
| `asiaauto-inventory.css` | 29.4 KB | `.aa-inv` | Inventory v2: filtry, karty, faceted counts, mobile drawer, Elementor nuclear reset, subpages |
| `asiaauto-order-wizard.css` | 25.7 KB | `.aa-wiz` | Wizard: 5 kroków, uploads, login, sidebar, responsive |
| `asiaauto-order-admin.css` | 7.6 KB | `.aa-admin` | Admin zamówień: lista, karta, config, responsive (tablet/mobile/small) |
| `asiaauto-single.css` | 7.8 KB | `.aa-single` | Single listing: galeria, specs, sidebar, mobile sticky CTA, safe-area |

**Inline CSS** (nie w plikach, w klasach PHP):
- `class-asiaauto-homepage.php` — homepage shortcode (~150 linii)
- `class-asiaauto-contact.php` — contact shortcode (~150 linii)
- `class-asiaauto-login.php` — branded login (~250 linii)
- `class-asiaauto-listing-editor.php` — metabox admin (~80 linii)
- `class-asiaauto-gallery-metabox.php` — galeria admin (~100 linii)

## JS (assets/js/ — 71.5 KB)

| Plik | Rozmiar | Opis |
|------|---------|------|
| `asiaauto-inventory.js` | 39.1 KB | Vanilla JS: AJAX grid, faceted counts, search autocomplete, URL pushState, make→model dependency, mobile drawer, AbortController |
| `asiaauto-order-wizard.js` | 31.3 KB | Vanilla JS: 5 kroków, multi-upload signed, file list, delete, confirm, login, tracking |
| `asiaauto-single.js` | 1.1 KB | Accordion (tech specs), mobile sticky CTA |

## Child theme (wp-content/themes/asiaauto/)

| Plik | Rozmiar | Opis |
|------|---------|------|
| `style.css` | 258 B | Theme header: "AsiaAuto by Auranet v1.0.0", Template: hello-elementor |
| `functions.php` | 411 B | Enqueue parent + child CSS. Zero custom hooków — cała logika w pluginie |

## .htaccess — sekcja AsiaAuto Login Hide

```apache
# BEGIN AsiaAuto Login Hide
# 1. Whitelist assets (load-styles, load-scripts, admin-ajax, admin-post) → [L]
# 2. /biuro/ → internal rewrite do wp-login.php
# 3. Blokada GET na wp-login.php dla niezalogowanych → 404
#    Wyjątki: POST, cookie wordpress_logged_in_, safe actions
# 4. Blokada /wp-admin/ dla niezalogowanych → 404
# END AsiaAuto Login Hide
```

Technika: `%{THE_REQUEST}` (odporne na internal rewrites).
Zmiana sluga: edytuj `.htaccess` rewrite + `AsiaAuto_Security::LOGIN_SLUG`.

## Image sizes (rejestrowane w asiaauto-sync.php)

| Nazwa | Wymiary | Crop | Filtr |
|-------|---------|------|-------|
| `asiaauto-card` | 350×250 | tak | Tylko dla `/asiaauto/` uploads |
| `asiaauto-thumb` | 190×132 | tak | j.w. |
| `asiaauto-gallery` | 800×600 | tak | j.w. |

Filtr `intermediate_image_sizes_advanced` — custom sizes generowane **tylko** dla plików w katalogu `asiaauto/`, nie dla wszystkich mediów.

## Konwencje CSS

- BEM: `.aa-{moduł}__{element}--{modyfikator}` (np. `.aa-inv__card--new`)
- CSS variables na root (login): `--aa-pri`, `--aa-accent`, `--aa-bg`
- Elementor Nuclear Reset: `!important` na button/input/select/a/h2/h3 w inventory
- Mobile-first, breakpoint 768px
- safe-area-inset-bottom dla iPhone (inventory sidebar, single CTA)
