# ADR: Galeria klientów `/klienci/` jako social proof

**Data:** 2026-05-28
**Wersja:** v0.32.57 (theme primaauto2026 → 1.0.7)
**Status:** wdrożone na produkcję

## Kontekst

Ruslan dostarczył batch zdjęć klientów Prima-Auto z autami sprowadzonymi z Chin (zgody potwierdzone — patrz memory `project-client-gallery-consents`). Batch oryginalnie planowany na 30 szt., finalnie 47 (mask `klienci-prima-auto-NNN.webp` w bibliotece mediów, IDs 350682-350728). Cel: silny dowód społeczny dla undecided buyers w lejku Ads/SEO; możliwa poprawa conversion na single listing przez „inni już zaufali" frame.

## Decyzja

Statyczna strona WP `/klienci/` + page template `page-klienci.php` z dynamicznym query po nazwie attachmentu. Brak CPT, brak adminowego UI w tej iteracji — KISS.

## Architektura

**Template** (`themes/primaauto2026/page-klienci.php`):
- Query: `get_posts({post_type=attachment, s='klienci-prima-auto', post_mime_type=image/webp})` + post-filter `post_name LIKE 'klienci-prima-auto-%'`. Order: title ASC (czyli kolejność numerków 001 → 047).
- Grid: 4/3/2 col (desktop/tablet/mobile), `aspect-ratio: 1/1` + `object-fit: cover`. Różne proporcje oryginałów (150×150 → 1536×1152) ujednolicone wizualnie bez letterboxa.
- Lightbox: vanilla JS inline (~80 linii). Klawiatura ←/→ ESC, swipe touch, focus return, body scroll lock.
- Lazyload native; pierwsze 6 zdjęć `loading="eager"` (LCP-friendly).
- JSON-LD `ImageGallery` z 47 `ImageObject` (contentUrl/thumbnailUrl/width/height).

**Dynamiczna lista** — galeria reaguje automatycznie na zmiany w bibliotece mediów. Dodanie attachmentu z nazwą `klienci-prima-auto-048.webp` → następna iteracja query załapie. Brak hardcoded ID-ków.

## Alternatywy odrzucone

- **CPT `klient`** — overkill dla 47 statycznych zdjęć bez danych dodatkowych.
- **ACF Gallery** w content stronie WP — wymaga ręcznego dodawania każdego zdjęcia w edytorze, mniej KISS niż auto-discovery.
- **Lightbox z biblioteki (Fancybox/GLightbox)** — dodatkowy dependency dla ~80 linii własnego kodu. Zysk: brak. Strata: cudzy JS w stack.
- **Letterbox 4:3 z padding** — niejednolity look (różne tła pod różnymi zdjęciami).

## Konsekwencje

**Pozytywne:**
- Zero adminowej obsługi — upload do Media Library, galeria sama załapie.
- Schema `ImageGallery` może zaindeksować się w Google Images.
- Lightbox UX dorównuje produktowym galeriom (klawiatura, swipe).

**Negatywne / do rozwiązania:**
- Brak filtra duplikatów — jeśli batch ma powtarzające się zdjęcia, wszystkie się wyświetlą. Rozwiązanie: ręczne czyszczenie w Media Library (`/wp-admin/upload.php?s=klienci-prima-auto`) lub dorobić meta `_aa_klienci_hidden=1` per attachment + filter w query.
- Brak reorderingu — kolejność wynika z nazwy pliku (001 → 047). Zmiana kolejności = rename slug attachmentu albo dorobić `menu_order`.
- Brak adminowego UI do zarządzania — przy częstym dorzucaniu zdjęć przez Ruslana to friction. Możliwe rozszerzenie w v0.32.58 (custom admin page lub meta box na attachment).
- Inline CSS (~200 linii) i JS (~80 linii) w page template — minimalnie rozdyma HTML, ale ładuje się tylko na `/klienci/`.

## Pending follow-up

- **Filtr duplikatów / hide** — jeśli batch ma duplikaty (user flagował 2026-05-28), albo ręczne czyszczenie w Media Library, albo dorobić checkbox „ukryj z galerii klientów" na karcie attachmentu (admin meta box).
- **Cross-site linki** — single listing, strona główna, `/zamow/` → CTA „Zobacz zadowolonych klientów".
- **OG image dedykowany 1200×630** — obecnie używamy #001 (proporcje ~3:4). Best: banner z 4 miniaturkami + logo.
- **Banner z liczbami** w hero — wymaga decyzji Ruslana (ile aut sprowadzonych, od którego roku).

## Powiązane

- Memory: `project-client-gallery-consents`, `project_session_2026_05_28_klienci_gallery` (NEW).
- QUEUE: sekcja „Galeria klientów" → DONE.
- VERSIONS: 0.32.57.
