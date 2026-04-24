# Spec: Standalone theme `primaauto` — eliminacja Elementora

> Data: 2026-04-24
> Status: design / czeka na materiały od klienta (eksporty Elementor)
> Sesja brainstormingu: zob. wątek Claude Code 2026-04-24

---

## 1. Cel

Wyeliminować plugin `elementor` + `elementor-pro` z front-endu primaauto.com.pl przez wymianę aktywnego motywu z `asiaauto` (child Hello Elementor) na nowy, samodzielny motyw `primaauto`.

**Główny driver: Core Web Vitals.** Z front-endu znikają:
- `elementor-frontend.min.js` + Pro chunki + swiper + share-link + eicons
- 4× per request inline `elementor-post-{id}.css` (Header / Footer / Single / Kit)
- Hello Elementor `style.css` + `theme.json` block presets
- Font Awesome (ładowane przez Elementor)
- Wrappery `.elementor-element`, `.elementor-widget-container` (lżejszy DOM = lepszy LCP/CLS)

Cel uboczny: czystsza architektura motywu, bez parent-theme zależności od Elementora, łatwiejsza iteracja designu w przyszłości.

## 2. Stan aktualny (źródła)

### 2.1 Wtyczki aktywne (4)
- `complianz-gdpr-premium` 7.5.7.2 — RODO, **zostaje**
- `elementor` 4.0.3 — **do usunięcia po stabilizacji**
- `elementor-pro` 4.0.3 — **do usunięcia po stabilizacji**
- `asiaauto-sync` 0.31.8 — własny plugin, **kluczowy, theme-agnostic, bez zmian**

### 2.2 Motyw aktywny
- `asiaauto` (child) — 5 plików: `functions.php`, `style.css`, `page-marki.php`, `taxonomy-make.php`, `taxonomy-serie.php`
- Parent: `hello-elementor` — minimalistyczny, ale zaprojektowany pod Elementor

### 2.3 Co Elementor renderuje na froncie

| Template ID | Rola | Theme Builder condition | Co w środku |
|---|---|---|---|
| 93632 | Kit | global tokens | kolory + typografia (Inter, body 15/1.6, H1 32/700) |
| 93650 | Header | `include/general` | logo, WP nav-menu, 2× icon-list (linki + telefony przez `aa_phone` dynamic tag), `aa_whatsapp`, `"sticky":"top"` |
| 93679 | Footer | `include/general` | logo, opis, nawigacja icon-list (4 linki), kontakt icon-list, divider, copyright, link Auranet |
| 101874 | single-listing | `include/singular/listings` | `aa_breadcrumb`, `asiaauto_gallery`, `asiaauto_key_specs`, `asiaauto_price_breakdown`, `asiaauto_tech_specs`, `asiaauto_equipment`, `asiaauto_updated`, `asiaauto_order_cta`, `aa_phone`, `aa_whatsapp` + ozdobniki Elementora |
| 145549 | archive listings | `include/archive/listings_archive` | **DEAD** — `https://primaauto.com.pl/listings/` zwraca 404, używa nieistniejącego JetSmartFilters |
| 154093 | 404 | `include/singular/not_found404` | jeden shortcode `[asiaauto_404_listing]` |
| 198673 | Single content page | `include/singular/page` (exclude front_page) | heading + `theme-post-content` |

**Strony WP (5) edytowane Elementorem — `post_content` to praktycznie tylko shortcode:**
- 93629 Strona główna → `[asiaauto_homepage]`
- 93720 Samochody z Chin → `<h1>` + opis + `[asiaauto_inventory]`
- 153875 W drodze → `[asiaauto_inventory reservation_status="in_transit"]`
- 153877 W Polsce → `[asiaauto_inventory reservation_status="on_lot"]`
- 186946 Kontakt → `[asiaauto_contact]`

### 2.4 Plugin assets (nietykane)

| Plik | Rozmiar | Zakres |
|---|---|---|
| `assets/css/asiaauto-single.css` | 143 linii | layout single listing |
| `assets/css/asiaauto-inventory.css` | 654 linii | layout listy + filtry |
| `assets/css/asiaauto-order-wizard.css` | 989 linii | wizard zamówienia |
| `assets/js/asiaauto-{single,inventory,tracking,order-wizard}.js` | — | interakcje |

Razem ~1786 linii własnego CSS — renderują cały content-area. Theme dostarcza tylko: header, footer, page skeleton, globalne tokeny.

### 2.5 Sticky/fixed elements (KRYTYCZNE — patrz §6)

**Header Elementor (93650):** `"sticky":"top"`, **`min_height: 70px`** (oba breakpointy — potwierdzone z eksportu JSON 2026-04-24).

**Plugin CSS — hardcoded zależności od wysokości headera:**
- `asiaauto-single.css:26` → sticky sidebar single, `top: 78px` (**8px szczelina pod headerem 70px = celowa**)
- `asiaauto-inventory.css:65` → sticky filtry sidebar desktop, `top: 78px`
- `asiaauto-inventory.css:528` → sticky pasek filtrów mobile, `top: 70px` (mobile bez szczeliny — sticky filtry przyklejone do dolnej krawędzi headera)

**Plugin sticky/fixed (zostaje, niezależne od motywu):**
- Sticky sidebar single (gallery/specs lewa kolumna)
- Fixed bottom CTA bar mobile single (telefon/WhatsApp)
- Sticky sidebar filtrów inventory desktop
- Sticky pasek filtrów inventory mobile
- Fixed drawer filtrów mobile + overlay
- Sticky bottom action bar inventory
- Sticky footer wizardu zamówienia

## 3. Decyzje projektowe

### 3.1 Strategia migracji
**Nowy standalone motyw** `primaauto` (slug bez roku). Cutover przez `wp theme activate primaauto`. Rollback: `wp theme activate asiaauto` (1 komenda, motyw asiaauto pozostaje na dysku jako safety net).

Switch motywu = atomowy cutover Theme Builder Elementora — lokalizacje (`elementor-location-header/footer/single`) są wpinane przez hooki aktywnego motywu, więc zmiana motywu automatycznie odcina rendering Elementor templatów (Plugin może być nadal aktywny — wyłączymy go dopiero po tygodniu obserwacji produkcji).

### 3.2 CSS — wariant „Hybryda" (klon w design-systemie)

Wygląd 1:1 z produkcji (zero ryzyka wizualnego). Implementacja przez tokeny CSS:

```css
:root {
  --c-primary:    #1B2A4A;
  --c-secondary:  #718096;
  --c-text:       #2D3748;
  --c-accent:     #D63031;
  --c-bg:         #F5F6F8;
  --c-surface:    #FFFFFF;
  --c-amber:      #E8AC07;
  --c-success:    #38A169;
  --c-on-primary: #B0BEC5;
  --c-border:     #E1E4E8;
  --c-btn-hover:  #B52828;
  --c-header-bg:  #9B0000;  /* override accent dla headera (z eksportu Elementor) */

  --shadow-card: 0 1px 4px rgba(0,0,0,0.08);  /* z box_shadow karty single */

  --font-body: 'Inter', sans-serif;
  --fz-body: 15px;
  --lh-body: 1.6;
  --fz-h1: 32px; --fw-h1: 700;
  --fz-h2: 24px; --fw-h2: 600;
  --fz-h3: 16px; --fw-h3: 600;

  --header-h: 70px;  /* desktop = mobile, potwierdzone z JSON */
}
```

Wartości pochodzą z Elementor Kit (93632) — pełna inwentaryzacja w §2.3.

### 3.3 Inter font — self-host

Pliki `Inter-Regular.woff2`, `Inter-SemiBold.woff2`, `Inter-Bold.woff2` w `themes/primaauto/fonts/`.
- `<link rel="preload">` dla wariantu 400 (body) w `wp_head`
- `font-display: swap`
- Bez `fonts.googleapis.com` → -1 DNS lookup, eliminacja blokowania third-party

### 3.4 Hamburger menu — vanilla JS (~50 linii)

CSS-only checkbox-trick działa, ale focus-trap + ARIA wymaga JS. Brak bibliotek.
- `<button aria-expanded>` + `<dialog>`-like pattern
- ESC zamyka, Tab cykluje wewnątrz, scroll-lock body

## 4. Architektura motywu `primaauto`

### 4.1 Struktura plików

```
wp-content/themes/primaauto/
├── style.css                   # header motywu + tokens + reset + typography
├── functions.php               # enqueue, theme supports, nav menus, image sizes
├── theme.json                  # minimalny — bez block presets (nic nie renderujemy Gutenbergiem)
├── header.php                  # sticky desktop + mobile hamburger
├── footer.php                  # 3-kolumnowy + copyright bar
├── index.php                   # fallback
├── page.php                    # generic — H1 + the_content() (obsłuży 4 strony shortcode-only)
├── front-page.php              # the_content() — strona główna ([asiaauto_homepage])
├── single-listings.php         # własny szablon CPT listings
├── 404.php                     # [asiaauto_404_listing]
├── page-marki.php              # przeniesione z asiaauto/ bez zmian
├── taxonomy-make.php           # przeniesione z asiaauto/ bez zmian
├── taxonomy-serie.php          # przeniesione z asiaauto/ bez zmian
├── assets/
│   ├── css/
│   │   ├── header.css
│   │   ├── footer.css
│   │   └── base.css            # reset, typography, utilities
│   └── js/
│       └── nav.js              # hamburger + focus trap
├── fonts/
│   ├── Inter-Regular.woff2
│   ├── Inter-SemiBold.woff2
│   └── Inter-Bold.woff2
└── screenshot.png
```

### 4.2 Mapowanie WP template hierarchy → pliki

| Strona | Template | Renderuje |
|---|---|---|
| `/` (93629) | `front-page.php` | `the_content()` → `[asiaauto_homepage]` |
| `/samochody/` (93720) | `page.php` | `<h1>` + opis + `[asiaauto_inventory]` |
| `/w-drodze/` (153875) | `page.php` | `[asiaauto_inventory reservation_status="in_transit"]` |
| `/w-rzeszowie/` (153877) | `page.php` | `[asiaauto_inventory reservation_status="on_lot"]` |
| `/kontakt/` (186946) | `page.php` | `[asiaauto_contact]` |
| `/oferta/{slug}/` | `single-listings.php` | shortcody pluginu w określonej kolejności |
| `/samochody/{make}/` | `taxonomy-make.php` | bez zmian |
| `/samochody/{make}/{serie}/` | `taxonomy-serie.php` (przez `template_include`) | bez zmian |
| `/marki/` | `page-marki.php` | bez zmian |
| 404 | `404.php` | `[asiaauto_404_listing]` |

### 4.3 `single-listings.php` — 4 oddzielne kontenery z responsive control

Eksport JSON 2026-04-24 ujawnił że produkcja ma **3 kontenery `hide_*` + 1 desktop wspólny**, NIE pojedynczy markup. Mobile i desktop mają wyraźnie inne układy.

```php
<main class="aa-single">

    <!-- 1. Mobile-only sticky-top (drugi sticky pod main headerem) -->
    <div class="aa-single__mobile-top is-sticky-top mobile-only">
        <h1 class="aa-single__title-mobile"><?php the_title(); ?></h1>
        <?php echo do_shortcode('[asiaauto_price_breakdown]'); ?>
    </div>

    <!-- 2. Desktop 2-kolumnowy + mobile pojedynczy stack -->
    <div class="aa-single__breadcrumb">
        <?php echo do_shortcode('[aa_breadcrumb]'); ?>
    </div>
    <div class="aa-single__layout">
        <div class="aa-single__main">
            <?php echo do_shortcode('[asiaauto_gallery]'); ?>
            <?php echo do_shortcode('[asiaauto_key_specs]'); ?>
            <div class="aa-single__benefits">
                <div class="aa-single__benefit-card aa-single__benefit-card--accent">
                    <h3>W cenie:</h3>
                    <ul>...</ul>  <!-- statyczna lista (źródło: nav-menu w wp-admin?) -->
                </div>
                <div class="aa-single__benefit-card">
                    <h3>Dlaczego my:</h3>
                    <ul>...</ul>
                </div>
                <div class="aa-single__benefit-card aa-single__benefit-card--bg">
                    <h3>Informacje:</h3>
                    <ul>...</ul>  <!-- 🐛 linki na asiaauto.pl/informacje/* — patrz §11 BUG -->
                </div>
            </div>
            <div class="aa-single__seller-desc">...opis sprzedającego...</div>
            <?php echo do_shortcode('[asiaauto_tech_specs]'); ?>
            <?php echo do_shortcode('[asiaauto_equipment]'); ?>
        </div>

        <!-- Sticky sidebar desktop-only -->
        <aside class="aa-single__sidebar is-sticky-top desktop-only">
            <h1 class="aa-single__title-desktop"><?php the_title(); ?></h1>
            <div class="aa-single__price-block">
                <p>CENA</p>
                <?php echo do_shortcode('[asiaauto_price_breakdown]'); ?>
            </div>
            <div class="aa-single__benefit-card aa-single__benefit-card--bg">
                <h3>W cenie:</h3>
                <ul>...</ul>
            </div>
            <?php echo do_shortcode('[asiaauto_order_cta]'); ?>
            <button class="aa-btn aa-btn--primary">...</button>
            <button class="aa-btn aa-btn--success">...</button>
        </aside>
    </div>

    <!-- 3. Mobile-only sticky-bottom (CTA bar dolna ramka ekranu) -->
    <div class="aa-single__mobile-cta is-sticky-bottom mobile-only">
        <?php echo do_shortcode('[asiaauto_order_cta]'); ?>
        <div class="aa-single__cta-buttons">
            <button class="aa-btn aa-btn--primary">...</button>
            <button class="aa-btn aa-btn--success">...</button>
        </div>
    </div>

    <!-- 4. Mobile-only Informacje (dodatkowa sekcja na końcu) -->
    <div class="aa-single__info-mobile mobile-only">
        <h3>Informacje</h3>
        <ul>...</ul>
    </div>

</main>
```

**Statyczne listy „W cenie" / „Dlaczego my" / „Informacje":** w obecnej produkcji są wpisane jako Elementor `icon-list` widget (a NIE shortcode). Decyzja do podjęcia w implementacji:
- **A)** Zaszyć w PHP jako tablica → najprościej, ale klient nie edytuje bez kodu
- **B)** Zarejestrować jako WP nav-menu (3 menu: „w-cenie", „dlaczego-my", „informacje") → klient edytuje w wp-admin → Wygląd → Menu
- **C)** Stworzyć Custom Field / option group w wp-admin → settings page

Domyślnie **B** (najbliższe obecnemu UX edycji).

**Statyczna lista buttonów w sidebar/CTA:** źródło tekstu/linków do potwierdzenia podczas implementacji (najpewniej tel + WhatsApp, dynamic tag `aa_phone`/`aa_whatsapp`).

## 5. Plan asset/enqueue

### 5.1 Co theme dorzuca
- `style.css` (theme header + tokens + reset)
- `assets/css/base.css`
- `assets/css/header.css` z `<link rel="preload">` dla Inter-400
- `assets/css/footer.css`
- `assets/js/nav.js` (defer, tylko gdy istnieje mobile menu w DOM)

### 5.2 Co plugin dorzuca (bez zmian)
- `aa-single` (CSS+JS) na single listing
- `asiaauto-inventory` (CSS+JS) na hubach + inventory pages
- `asiaauto-order-wizard` (CSS+JS) na wizard
- `asiaauto-tracking` na wszystkich (GA4/Ads conversions)

### 5.3 Co znika z front-endu
- Hello Elementor `style.css`, `theme.json` block presets, fonty Hello
- Elementor frontend bundle + Pro
- Eicons font, Font Awesome (Elementor)
- 4× per request inline `<style id="elementor-post-{id}-css">`
- Elementor wrappery DOM (`.elementor-element`, `.elementor-widget-*`, `.e-con-*`)

### 5.4 Aktualizacja `class-asiaauto-perf.php`
Po stabilizacji nowego motywu + dezaktywacji Elementora można:
- usunąć ostrożny komentarz „Header/footer Elementor template still has its widget CSS"
- agresywniej dequeue na **wszystkich** stronach (nie tylko huby/single/archive)
- rozważyć dequeue `wp-emoji-release.min.js`, `comment-reply` (chyba że Complianz korzysta)

**Nie w tym specu** — osobny ticket po cutover.

## 6. Sticky behavior — twardy wymóg

Nowy header **musi mieć dokładnie 70px wysokości** (oba breakpointy — `min_height:70px` w eksporcie JSON 2026-04-24).

Plugin CSS hardcoduje `top: 78px` (single sidebar, inventory sidebar desktop) — to celowa **8px szczelina** między dolną krawędzią headera a górą sticky sidebara. Na mobile filtry mają `top: 70px` (przyklejone bezpośrednio do dolnej krawędzi headera).

**Implementacja:**
```css
.aa-header {
  position: sticky; top: 0; z-index: 100;
  height: var(--header-h);  /* 70px */
  background: var(--c-header-bg);
}
```

**Alternatywa (poza tym specem):** ekspozycja `--aa-header-h` przez theme, użycie `top: calc(var(--aa-header-h) + 8px)` w plugin CSS. Daje swobodę zmiany wysokości w przyszłości. Decyzja w fazie implementacji — domyślnie zostawiamy hardcoded 70/78.

## 7. Plan cutover

```
Faza 0 — przygotowanie (TEN spec)
  ✓ inwentaryzacja
  ✓ decyzje projektowe
  ⏳ materiały od klienta (eksporty Elementor + screenshoty)

Faza 1 — implementacja (osobny plan)
  - utworzenie themes/primaauto/ (na produkcji, ale nieaktywne)
  - plain plików: style.css, functions.php, header.php, footer.php, etc.
  - test na admin preview (?preview=1&theme=primaauto?) lub krótki switch w godzinach niskiego ruchu

Faza 2 — switch
  - mysqldump bazy (backup)
  - tar themes/asiaauto + themes/hello-elementor (backup)
  - wp theme activate primaauto
  - smoke test: home, /samochody/, /oferta/{listing}, /kontakt/, 404, hub marki, hub serii
  - jeśli OK — zostawiamy
  - jeśli nie OK — wp theme activate asiaauto (rollback w sekundach)

Faza 3 — obserwacja (7 dni)
  - PSI runs codziennie (porównanie do baseline z tmp/psi-final-2026-04-23/)
  - GA4: bounce rate, session duration na single listing
  - logi Apache: 5xx, 404 nieoczekiwane
  - feedback klienta

Faza 4 — czyszczenie (po stabilizacji)
  - wp plugin deactivate elementor-pro elementor
  - usunięcie elementor_library posts (po backupie)
  - wp theme delete hello-elementor (zostaje asiaauto jako safety dla peace of mind)
  - aktualizacja class-asiaauto-perf.php — agresywniejsze dequeue
  - cleanup elementor_* options w wp_options
```

## 8. Acceptance criteria

**Funkcjonalne:**
- Strona główna, /samochody/, /w-drodze/, /w-rzeszowie/, /kontakt/, /oferta/{slug}/, 404, /samochody/{make}/, /samochody/{make}/{serie}/, /marki/ — wszystkie renderują się **wizualnie identycznie** z produkcją (z dokładnością do tokenów kolorów/typo z §3.2)
- Sticky header działa (desktop + mobile), wysokość 78/70 px, nie kolizjuje ze sticky sidebarami plugin CSS
- Hamburger menu mobile otwiera się, zamyka ESC, Tab nie wycieka poza menu
- Wszystkie shortcody pluginu renderują się tak samo jak teraz
- WP nav-menu działa (przeniesione z asiaauto bez zmian)
- Complianz banner pojawia się normalnie

**Wydajność (cel — porównanie do baseline `tmp/psi-final-2026-04-23/`):**
- Mobile LCP: ≥ -300 ms vs baseline
- Mobile TBT: ≥ -200 ms vs baseline
- Mobile CLS: bez regresji
- Desktop wszystkie metryki: bez regresji
- Liczba wymaganych żądań HTTP per wyświetlenie single listing: -10 minimum

**Stabilność:**
- 7 dni produkcji bez krytycznych błędów (5xx, brakujące assets)
- Konwersje GA4 (click_phone, click_whatsapp, generate_lead) działają tak jak przed switchem

## 9. Materiały od klienta — status

Otrzymane 2026-04-24 (commit `84daeb0`):

| Plik | Źródło | Status |
|---|---|---|
| `tmp/elementor-265656-2026-04-24.json` | Header (93650) | ✅ przeanalizowane → §2.5, §3.2, §6 zaktualizowane |
| `tmp/elementor-265659-2026-04-24.json` | Footer (93679) | ✅ przeanalizowane → §4.2 zgodne |
| `tmp/elementor-265662-2026-04-24.json` | Single listing (101874) | ✅ przeanalizowane → §4.3 całkowicie przepisane (4 kontenery, mobile/desktop split) |
| `tmp/primaauto-header-desktop.png` | Screenshot | ✅ tło `#9B0000`, layout zweryfikowany |
| `tmp/primaauto-header-mobile.png` | Screenshot | ✅ hamburger amber, pill telefon+WhatsApp |

Świadomie pominięte przez klienta (czyste shortcode wrappery, nic w Elementor edycji nie wymaga rozpoznania):
- Strona główna (93629) → `[asiaauto_homepage]`
- Inventory page (93720) → `<h1>` + opis + `[asiaauto_inventory]`

## 10. Open questions

- Czy `primaauto` (bez roku) jako slug — **OK (Janek 2026-04-24)**
- Wymiar headera 78/70 px — **OK (Janek 2026-04-24)**
- Inter self-host woff2 — **OK (Janek 2026-04-24)**
- Vanilla JS hamburger — **OK (Janek 2026-04-24)**
- Czy `theme.json` ma być pusty czy z minimalnymi presetami dla edytora WP-admin — **odpowiedź po wgraniu materiałów** (jeśli nie używasz block editora w WP admin do żadnej strony — pusty)
- Czy plik `front-page.php` ma renderować coś poza `the_content()` (np. preload obrazu hero z homepage shortcode) — **odpowiedź po obejrzeniu home.json**

## 11. Co JEST poza tym specem

- Refaktor plugin CSS na CSS variables (`--aa-header-h` zamiast hardcoded 78px) — osobny ticket
- Przepisanie `class-asiaauto-shortcodes.php` na coś nowoczesniejszego — nieruszane, działa
- Nowy design (inny look niż produkcja) — odrzucone, klonujemy
- Migracja stron 153875/153877/93720 z Elementor `_elementor_edit_mode` na czysty `post_content` — opcjonalnie po cutover, post_content już jest poprawny shortcode, edit_mode meta to tylko hint dla Elementor admin
- Migracja huby SEO (`page-marki.php`, `taxonomy-make.php`, `taxonomy-serie.php`) — kopiujemy as-is, nie zmieniamy

### 🐛 BUG do osobnego ticketu (znalezione przy review eksportu single 2026-04-24)
W single listing template (101874) sekcja „Informacje" zawiera linki na **starą domenę**:
- `https://asiaauto.pl/informacje/proces-zamawiania/`
- `https://asiaauto.pl/informacje/gwarancja-i-serwis/`
- `https://asiaauto.pl/informacje/jezyk-obslugi-pojazdu/`
- `https://asiaauto.pl/informacje/finansowanie/` (link wskazuje na jezyk-obslugi-pojazdu — kolejny bug)
- `https://asiaauto.pl/informacje/regulamin-serwisu/` (j.w.)
- `https://asiaauto.pl/informacje/homologacja-i-rejestracja/`

Powinny wskazywać na `primaauto.com.pl/informacje/...`. Obecnie redirectują przez 301 (asiaauto → primaauto) — działa, ale dodaje hop.
Naprawimy przy okazji wpisywania tych list w PHP/menu w nowym motywie.

---

**Następny krok:** klient wgrywa eksporty Elementor JSON do `tmp/elementor-export/` → review materiałów → przejście do `writing-plans` skill (implementation plan).
