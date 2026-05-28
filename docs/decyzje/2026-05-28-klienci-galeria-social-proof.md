# ADR: Galeria klientów `/klienci/` — Gutenberg Gallery block (po rollbacku)

**Data:** 2026-05-28
**Wersja:** v0.32.57
**Status:** wdrożone na produkcję (po rollbacku z custom template)

## Kontekst

Ruslan dostarczył batch 47 zdjęć klientów Prima-Auto (zgody potwierdzone — patrz memory `project-client-gallery-consents`). Cel: silny dowód społeczny dla undecided buyers w lejku Ads/SEO. **Krytyczne wymaganie:** Ruslan musi móc samodzielnie dodawać/usuwać zdjęcia, bo widzi w batchu duplikaty kontentowe i będzie też dosyłał nowe.

## Decyzja (finalna)

Zwykła strona WP `/klienci/` z natywnym blokiem `wp:gallery` Gutenberga w content. ZERO custom kodu. Zarządzanie 100% w wp-admin → Strony → Klienci → edytor Gutenberga (drag&drop, dodawanie z biblioteki UI, lightbox built-in via Interactivity API).

## Architektura

WP page ID 350745, `_wp_page_template=''` (default `page.php`), content:

```
wp:heading {level:1}  →  H1 „Klienci Prima-Auto..."
wp:paragraph          →  lead z dowodu społecznego
wp:gallery {columns:4, imageCrop:true, linkTo:"none", sizeSlug:"medium_large"}
  wp:image × 47       →  każdy z lightbox:{enabled:true}
wp:heading {level:2}  →  CTA „Sprowadzimy auto..."
wp:paragraph          →  linki do /samochody/ + /zamow/
```

- `imageCrop:true` → square thumbs (problem różnych proporcji rozwiązany w runtime CSS bloku)
- `lightbox.enabled:true` → WP 6.4+ Interactivity API (swipe, klawiatura, ESC — out-of-the-box)
- `sizeSlug:medium_large` → ~768px thumb, full size w lightboxie

## Rollback z pierwotnej implementacji

**Pierwotnie (2026-05-28 popołudnie):** custom page template `themes/primaauto2026/page-klienci.php` (~360 linii):
- PHP query po `klienci-prima-auto-*.webp` z auto-discovery
- Inline CSS grid 4/3/2 col + aspect-ratio:1/1 + object-fit:cover
- Vanilla JS lightbox ~80 linii (klawiatura, swipe, focus return)
- ImageGallery JSON-LD ręcznie z 47 ImageObject
- Theme bump 1.0.6 → 1.0.7

**Powód rollbacku:** wszystko co napisane na piechotę istnieje w Gutenbergu out-of-the-box. Custom template był overengineered. Plus krytyczny problem: Ruslan nie mógłby sam dodać zdjęcia bez WebP-konwersji + numeracji 048/049 — co znaczyło wciąż friction. Gallery block w edytorze = klik „dodaj z biblioteki" + drag, koniec.

**Co usunięte przy rollbacku:**
- `themes/primaauto2026/page-klienci.php` (z disk + z repo)
- `PRIMAAUTO_THEME_VERSION` 1.0.7 → 1.0.6
- `_wp_page_template` meta na page 350745
- `ImageGallery` JSON-LD (Gallery block sam dostarcza `<img>` + alty)

## Alternatywy odrzucone (po rollbacku to wciąż obowiązują)

- **CPT `klient`** — overkill dla 47 statycznych zdjęć.
- **ACF Gallery** — wymaga dodatkowego pluginu/configu, Gallery block daje to samo.
- **Custom lightbox z biblioteki (Fancybox/GLightbox)** — Gutenberg ma natywny.
- **Auto-discovery po nazwie pliku** — nie pozwala Ruslanowi reorder/hide bez kasowania.

## Konsekwencje

**Pozytywne:**
- Ruslan zarządza sam w wp-admin (drag&drop, dodaj z biblioteki, „×" do usunięcia, „Aktualizuj"). Zero Janka.
- Schema indeksacji w Google Images via `<img>` + alty (Gallery block standard markup).
- Reorder, square crop, lightbox — natywnie.
- HTML edytowalny — można dorzucić tekst między grupami zdjęć, podpisy per grupa, banner CTA pośrodku.

**Negatywne / trade-off:**
- HTML 240KB vs 143KB w custom (Gutenberg `wp-block-library` CSS + Interactivity API JS). 0.12s response, lazyload native — akceptowalne.
- Lista 47 ID-ków siedzi w `post_content` jako `{"ids":[...]}` — przy delete attachmentu link zostaje (Gutenberg powinien obsłużyć graceful, do potwierdzenia podczas pierwszego ruchu Ruslana).
- Kolejność = ręcznie w edytorze (nie ASC po nazwie). Trade-off zaakceptowany: Ruslan widzi co dodaje gdzie.

## Pending follow-up

- **Cross-site linki do `/klienci/`** — single listing (po cenie/CTA, przed FAQ?), strona główna (sekcja proof z 3-4 miniaturkami), `/zamow/` (kafelek). **Decyzja gdzie i jak — kolejna sesja.**
- **OG image dedykowany 1200×630** — obecnie #001 (~3:4).
- **Liczby w hero** — wymaga konkretu od Ruslana (ile aut sprowadzonych, od kiedy).
- **ImageGallery JSON-LD** — dodać filterem w functions.php jeśli za 1-2 mies. okaże się że brak signalu hurts indexing.

## Powiązane

- Memory: `project-client-gallery-consents`, `project_session_2026_05_28_klienci_gallery`.
- QUEUE: sekcja „Galeria klientów" → DONE (po rollbacku).
- VERSIONS: 0.32.57.
