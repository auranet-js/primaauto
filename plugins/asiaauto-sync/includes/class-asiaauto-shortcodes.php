<?php
/**
 * Shortcodes for single listing template (Elementor Theme Builder).
 *
 * Provides visual components that Elementor cannot render natively:
 * - [asiaauto_gallery]      — Image carousel + thumbnails + lightbox
 * - [asiaauto_key_specs]    — Icon bar with key parameters (smart power for PHEV/EV)
 * - [asiaauto_badges]       — Condition/fuel/status badges
 * - [asiaauto_price]        — Formatted price (132 000 PLN)
 * - [asiaauto_specs]        — Extra prep grouped tables (14 categories)
 * - [asiaauto_tech_specs]   — Technical data collapsible (6 sections, mobile.de style)
 * - [asiaauto_equipment]    — Equipment checklist with checkmarks (collapsible)
 * - [asiaauto_cta]          — CTA buttons: Zarezerwuj / Zadzwoń / WhatsApp
 * - [asiaauto_404_listing]  — Custom 404 for deleted/sold listings
 * - [aa_phone]              — Bare phone number for Elementor button href (tel:)
 * - [aa_whatsapp]           — WhatsApp link with current page URL for Elementor button href
 * - [aa_back]               — "Wróć do listy" button with history.back()
 * - [aa_breadcrumb]         — Breadcrumbs: Oferta → Marka → Model (single listing)
 *
 * @since 0.9.1
 * @since 0.9.4 [asiaauto_404_listing] added
 * @since 0.10.2 [asiaauto_tech_specs], [asiaauto_equipment], [asiaauto_cta] added
 * @since 0.10.2 [asiaauto_key_specs] power logic for PHEV/EREV/EV, short fuel labels
 * @since 0.22.1 [asiaauto_order_cta] stock reservation type — allow ordering
 * @since 0.23.0 [aa_phone] bare phone for Elementor dynamic href
 * @since 0.23.0 [aa_whatsapp] WhatsApp link with page URL for Elementor button
 * @since 0.23.0 [aa_back] back button with history.back()
 * @since 0.23.0 [aa_breadcrumb] breadcrumbs on single listing
 * @package AsiaAuto
 */

defined('ABSPATH') || exit;

class AsiaAuto_Shortcodes {

    private static bool $gallery_assets_enqueued = false;

    public function __construct() {
        add_shortcode('asiaauto_gallery', [$this, 'renderGallery']);
        add_shortcode('asiaauto_key_specs', [$this, 'renderKeySpecs']);
        add_shortcode('asiaauto_badges', [$this, 'renderBadges']);
        add_shortcode('asiaauto_price', [$this, 'renderPrice']);
        add_shortcode('asiaauto_price_netto', [$this, 'renderPriceNetto']);
        add_shortcode('asiaauto_price_breakdown', [$this, 'renderPriceBreakdown']);
        add_shortcode('asiaauto_included', [$this, 'renderIncluded']);
        add_shortcode('asiaauto_specs', [$this, 'renderSpecs']);
        add_shortcode('asiaauto_updated', [$this, 'renderUpdated']);
        add_shortcode('asiaauto_404_listing', [$this, 'render404Listing']);
        add_shortcode('asiaauto_order_url', [$this, 'renderOrderUrl']);
        add_shortcode('asiaauto_order_cta', [$this, 'renderOrderCta']);        
        add_shortcode('asiaauto_tech_specs', [$this, 'renderTechSpecs']);
        add_shortcode('asiaauto_equipment', [$this, 'renderEquipment']);
        add_shortcode('asiaauto_cta', [$this, 'renderCTA']);

        // v0.23.0 — simple utility shortcodes
        add_shortcode('aa_phone', [$this, 'renderPhone']);
        add_shortcode('aa_whatsapp', [$this, 'renderWhatsApp']);
        add_shortcode('aa_back', [$this, 'renderBack']);
        add_shortcode('aa_breadcrumb', [$this, 'renderBreadcrumb']);

        // v0.30.7 — customer login panel
        add_shortcode('asiaauto_klient_panel', [$this, 'renderKlientPanel']);
    }

    // =========================================================================
    // [aa_phone] — Bare phone number for Elementor button href
    // =========================================================================

    /**
     * [aa_phone] — Returns company phone number from order config.
     *
     * Usage in Elementor: Button → Link → Dynamic → Shortcode → [aa_phone]
     * Outputs: tel:+48783807381 (default, for href)
     *
     * Attributes:
     *   format="raw"  — +48 783 807 381 (human-readable, with spaces)
     *   format="tel"  — tel:+48783807381 (default, for href)
     *   format="clean" — +48783807381 (no spaces, no tel: prefix)
     *   slot="1"      — primary (company_phone, default)
     *   slot="2"      — additional (company_phone_2)
     *
     * @since 0.23.0
     * @since 0.30.15 slot="2" for company_phone_2
     */
    public function renderPhone(array $atts = []): string {
        $atts = shortcode_atts([
            'format' => 'tel',
            'slot'   => '1',
        ], $atts, 'aa_phone');

        $config = get_option('asiaauto_order_config', []);
        $key = ((string) $atts['slot'] === '2') ? 'company_phone_2' : 'company_phone';
        $phone = $config[$key] ?? '';

        if (empty($phone)) {
            return '';
        }

        $clean = preg_replace('/[\s\-\(\)]/', '', $phone);

        return match ($atts['format']) {
            'raw'   => esc_html($phone),
            'clean' => esc_attr($clean),
            default => 'tel:' . esc_attr($clean),
        };
    }

    // =========================================================================
    // [aa_whatsapp] — WhatsApp link for Elementor button href
    // =========================================================================

    /**
     * [aa_whatsapp] — Returns WhatsApp wa.me link with pre-filled message.
     *
     * Usage in Elementor: Button → Link → Dynamic → Shortcode → [aa_whatsapp]
     * Outputs: https://wa.me/48783807381?text=... (bare URL, for href)
     *
     * Message includes current page URL. On single listing also includes title.
     * On non-listing pages sends generic inquiry with page URL.
     *
     * Attributes:
     *   text="custom message" — override default message (use {url} and {title} placeholders)
     *
     * @since 0.23.0
     */
    public function renderWhatsApp(array $atts = []): string {
        $atts = shortcode_atts([
            'text' => '',
        ], $atts, 'aa_whatsapp');

        $config = get_option('asiaauto_order_config', []);
        $phone = $config['company_phone'] ?? '';

        if (empty($phone)) {
            return '';
        }

        // wa.me requires number without + prefix
        $phone_clean = preg_replace('/[\s\-\(\)]/', '', $phone);
        $phone_wa = ltrim($phone_clean, '+');

        // Kontekst auta: karta oferty (CPT listings) albo kreator zamówienia
        // (/zamow/?listing_id=NNN). Bez drugiego przypadku wiadomość niosła goly
        // adres /zamow/ — get_permalink() gubi query string — i u odbiorcy
        // otwierała pusty kreator „Nie wskazano pojazdu" (zgłoszenie 20.09.2026).
        $post_id = get_the_ID();
        $is_listing_page = ($post_id && get_post_type($post_id) === 'listings');
        $listing_id = $is_listing_page ? (int) $post_id : (int) ($_GET['listing_id'] ?? 0);

        $title = '';
        $listing_url = '';
        if ($listing_id > 0) {
            $listing_post = get_post($listing_id);
            if ($listing_post
                && $listing_post->post_type === 'listings'
                && $listing_post->post_status === 'publish') {
                $title       = get_the_title($listing_id);
                $listing_url = (string) get_permalink($listing_id);
            }
        }

        $current_url = $this->currentUrlForMessage();

        // Build message — no \n because Elementor runs esc_url() on shortcode output
        // which strips %0A. WhatsApp auto-links URLs regardless.
        if (!empty($atts['text'])) {
            $message = str_replace(
                ['{url}', '{title}'],
                [$listing_url ?: $current_url, $title],
                $atts['text']
            );
        } elseif ($title !== '' && $listing_url !== '') {
            $message = $is_listing_page
                ? "Dzień dobry, interesuję się ofertą: {$title} — {$listing_url}"
                : "Dzień dobry, chcę zamówić: {$title} — {$listing_url}";
        } elseif ($current_url !== '') {
            $message = "Dzień dobry, piszę w sprawie: {$current_url}";
        } else {
            $message = 'Dzień dobry, piszę w sprawie oferty na primaauto.com.pl';
        }

        // Build URL: sanitize parts separately, avoid esc_url on query string
        $wa_text = rawurlencode($message);

        return 'https://wa.me/' . esc_attr($phone_wa) . '?text=' . esc_attr($wa_text);
    }

    /**
     * Adres bieżącej strony do wklejenia w wiadomość WhatsApp.
     *
     * W odróżnieniu od get_permalink() zachowuje pełną ścieżkę i filtry z adresu
     * (np. /porownywarka/byd-han-vs-zeekr-001/), ale usuwa parametry wrażliwe
     * (magic_token, order_id) i śledzące (utm_*, fbclid…).
     *
     * Zwraca '' dla stron aplikacyjnych otwartych bez kontekstu (pusty kreator
     * zamówienia, pusta porównywarka, panel klienta) — taki link u odbiorcy
     * otwiera pustą stronę, więc lepiej wysłać samo zapytanie bez adresu.
     *
     * @since 0.44.1
     */
    private function currentUrlForMessage(): string {
        $req = (string) ($_SERVER['REQUEST_URI'] ?? '');
        if ($req === '') {
            return (string) (get_permalink() ?: home_url('/'));
        }

        $path  = (string) parse_url($req, PHP_URL_PATH);
        $query = (string) parse_url($req, PHP_URL_QUERY);

        $params = [];
        if ($query !== '') {
            parse_str($query, $params);
            $wytnij = ['magic_token', 'order_id', 'listing_id', '_wpnonce', 'token', 'key', 'email',
                       'fbclid', 'gclid', 'msclkid', 'gad_source'];
            foreach (array_keys($params) as $klucz) {
                if (str_starts_with((string) $klucz, 'utm_') || in_array($klucz, $wytnij, true)) {
                    unset($params[$klucz]);
                }
            }
        }

        // Strona aplikacyjna bez kontekstu — sam adres nic odbiorcy nie mówi.
        if (empty($params) && in_array(trim($path, '/'), ['zamow', 'porownywarka', 'klient'], true)) {
            return '';
        }

        return home_url($path . (!empty($params) ? '?' . http_build_query($params) : ''));
    }

    // =========================================================================
    // [aa_back] — "Wróć do listy" button
    // =========================================================================

    /**
     * [aa_back] — Renders a styled back button with history.back().
     *
     * Drop into Elementor Shortcode widget on single listing or inventory template.
     * Uses design system colors (Inter, Primary Navy, Accent Red).
     *
     * Attributes:
     *   text="Custom label" — default: "← Wróć do listy"
     *
     * @since 0.23.0
     */
    public function renderBack(array $atts = []): string {
        $atts = shortcode_atts([
            'text' => '← Wróć do listy',
        ], $atts, 'aa_back');

        $this->renderBackCSS();

        return '<button type="button" class="aa-back" onclick="history.back()">'
             . esc_html($atts['text'])
             . '</button>';
    }

    private function renderBackCSS(): void {
        static $done = false;
        if ($done) return;
        $done = true;
        ?>
        <style>
        .aa-back {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 8px 16px;
            background: transparent;
            border: 1px solid #E1E4E8;
            border-radius: 6px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 500;
            color: #1B2A4A;
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s;
            line-height: 1.4;
        }
        .aa-back:hover {
            border-color: #1B2A4A;
            background: #F8F9FA;
        }
        </style>
        <?php
    }

    // =========================================================================
    // [aa_breadcrumb] — Breadcrumbs on single listing
    // =========================================================================

    /**
     * [aa_breadcrumb] — Renders breadcrumb trail on single listing page.
     *
     * Output: Oferta → {Marka} → {Model}
     * Links point to taxonomy archives: /samochody/{mark}/, /samochody/{mark}/{serie}/
     * (Since 0.31.0 — poprzednio /marka/, /model/. URL generuje get_term_link()
     * + filter AsiaAuto_CPT::filterSerieTermLink dla dwupoziomowego serie.)
     * On non-listing pages renders nothing.
     *
     * Design: subtle, Inter 13px, separator "›", links in Navy with hover.
     *
     * @since 0.23.0
     */

    /** Tokeny napedu. DM-i PRZED DM — kolejnosc ma znaczenie przy dopinaniu. @since 0.33.23 */
    private const ANCHOR_DRIVE_TOKENS = ['PHEV', 'EREV', 'DM-i', 'DM-p', 'EV', 'DM'];

    /** Lustro V3_BRAND_ALIAS z class-asiaauto-single.php — anchor ma mowic to samo, co title. @since 0.33.23 */
    private const ANCHOR_BRAND_ALIAS = ['baw' => ['Beijing']];

    /**
     * Pelna nazwa modelu dla anchora oferta→hub (breadcrumb + JSON-LD BreadcrumbList).
     *
     * Anchor prowadzi do HUBA, wiec niesie fraze HUBA ({model}) — fraze wersyjna
     * ({model} {wersja}) niesie title/H1 oferty. Dzis anchor to surowy $serie->name,
     * czyli 258 z 302 modeli (2 908 ofert) linkuje do hubu napisem bez marki („8X").
     *
     * Baza: _serie_full_title — kuratorowane, lepsze niz naiwne sklejenie make+name:
     * zdejmuje nawiasy („Leopard 3 (Tai 3) FCB" → „BYD Leopard 3"), nie dubluje marki
     * przy make „Dongfeng Fengshen", dekoduje „Lynk &amp; Co", poprawia case („XPENG").
     * Do tego trzy korekty (symulacja 100 serii, 2026-07-16):
     *   1. zdejmij naped, ktorego NIE ma w nazwie termu — „Zeekr 8X PHEV" → „Zeekr 8X"
     *      (nikt nie szuka „zeekr 8x phev"; hub bierze „zeekr 8x”, 2 900/mc),
     *   2. dopnij naped, ktory JEST w nazwie termu, a full_title go zgubil — „BYD Han"
     *      → „BYD Han DM-i”. BEZ TEGO KOLIZJA: Sealion 5 DM i Sealion 5 EV to dwa rozne
     *      huby, a oba dostawaly „BYD Sealion 5” (sami tworzylibysmy kanibalizacje anchora),
     *   3. alias marki — „Beijing 212 T01" → „BAW 212 T01”, spojnie z V3_BRAND_ALIAS.
     *      Term hubu NIE jest dotykany (defekt _serie_full_title zostaje w tracku hubow).
     * Fallback dla 35 serii bez full_title: make + name z guardem antydubletowym.
     *
     * Zweryfikowane na calej puli: kolizje anchora 1 → 0.
     *
     * @since 0.33.23
     */
    public static function serieAnchor(WP_Term $serie, ?WP_Term $make = null): string {
        $name = html_entity_decode($serie->name, ENT_QUOTES, 'UTF-8');
        $mk   = $make ? html_entity_decode($make->name, ENT_QUOTES, 'UTF-8') : '';
        $ft   = (string) get_term_meta($serie->term_id, '_serie_full_title', true);

        if ($ft === '') {
            if ($mk === '' || stripos($name, $mk) !== false) return $name;
            $nt = explode(' ', $name);
            $bt = explode(' ', $mk);
            if (isset($nt[0], $bt[0]) && strcasecmp($nt[0], $bt[0]) === 0) return $name; // „IM LS7” vs „IM Motors”
            return $mk . ' ' . $name;
        }

        $a = html_entity_decode($ft, ENT_QUOTES, 'UTF-8');

        foreach (self::ANCHOR_DRIVE_TOKENS as $tok) {
            $re = '/\b' . preg_quote($tok, '/') . '\b/i';
            if (preg_match($re, $a) && !preg_match($re, $name)) {
                $a = trim(preg_replace('/\s*\b' . preg_quote($tok, '/') . '\b\s*/i', ' ', $a));
            }
        }
        foreach (self::ANCHOR_DRIVE_TOKENS as $tok) {
            $re = '/\b' . preg_quote($tok, '/') . '\b/i';
            if (preg_match($re, $name, $m) && !preg_match($re, $a)) {
                $a = trim($a . ' ' . $m[0]);
                break;
            }
        }
        if ($make && isset(self::ANCHOR_BRAND_ALIAS[$make->slug])) {
            foreach (self::ANCHOR_BRAND_ALIAS[$make->slug] as $old) {
                if (stripos($a, $mk) === false && preg_match('/^' . preg_quote($old, '/') . '\b/i', $a)) {
                    $a = preg_replace('/^' . preg_quote($old, '/') . '\b/i', $mk, $a);
                    break;
                }
            }
        }
        return trim(preg_replace('/\s+/u', ' ', $a));
    }

    public function renderBreadcrumb(array $atts = []): string {
        $post_id = get_the_ID();
        if (!$post_id || get_post_type($post_id) !== 'listings') {
            return '';
        }

        $crumbs = [];

        // Root: Samochody (listings archive, /samochody/)
        $crumbs[] = [
            'label' => 'Samochody',
            'url'   => home_url('/samochody/'),
            'key'   => 'root',
        ];

        // Marka (taxonomy: make, slug: marka) — klikalny
        $make_terms = get_the_terms($post_id, 'make');
        if ($make_terms && !is_wp_error($make_terms)) {
            $make = $make_terms[0];
            $crumbs[] = [
                'label' => $make->name,
                'url'   => get_term_link($make),
                'key'   => 'make',
            ];

            // Model (taxonomy: serie, slug: model) — klikalny link do hubu modelu.
            // Anchor = pelna nazwa (serieAnchor), nie surowy $serie->name — link ma niesc
            // fraze, o ktora walczy hub. Na mobile to JEDYNY widoczny poziom (CSS kompakt).
            $serie_terms = get_the_terms($post_id, 'serie');
            if ($serie_terms && !is_wp_error($serie_terms)) {
                $serie = $serie_terms[0];
                $serie_url = get_term_link($serie);
                $crumbs[] = [
                    'label' => self::serieAnchor($serie, $make),
                    'url'   => is_wp_error($serie_url) ? null : $serie_url,
                    'key'   => 'serie',
                ];
            }
        }

        // Tytuł listingu — ostatni element, nieklikalny (current)
        $crumbs[] = [
            'label' => get_the_title($post_id),
            'url'   => null,
            'key'   => 'current',
        ];

        if (count($crumbs) < 2) {
            return '';
        }

        $this->renderBreadcrumbCSS();

        $parts = [];
        $last = count($crumbs) - 1;
        foreach ($crumbs as $i => $crumb) {
            // Modyfikator per poziom — CSS mobile pokazuje tylko --serie (kompakt), reszta chowana.
            $key = $crumb['key'] ?? '';
            if ($i === $last) {
                $parts[] = '<span class="aa-breadcrumb__current">' . esc_html($crumb['label']) . '</span>';
            } else {
                $url = is_wp_error($crumb['url']) ? '#' : $crumb['url'];
                $cls = 'aa-breadcrumb__link' . ($key !== '' ? ' aa-breadcrumb__link--' . $key : '');
                $parts[] = '<a href="' . esc_url($url) . '" class="' . esc_attr($cls) . '">' . esc_html($crumb['label']) . '</a>';
            }
        }

        return '<nav class="aa-breadcrumb" aria-label="Breadcrumb">'
             . implode('<span class="aa-breadcrumb__sep">›</span>', $parts)
             . '</nav>';
    }

    private function renderBreadcrumbCSS(): void {
        static $done = false;
        if ($done) return;
        $done = true;
        ?>
        <style>
        .aa-breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            line-height: 1.4;
        }
        /* Dostepnosc 2.5.8: cel miał 18 px wysokosci przy wymaganych 24.
           Sam padding — ujemny margin sprawial, ze sasiednie cele nachodzily
           na siebie i axe zglaszal je jako "partially obscured". */
        .aa-breadcrumb__link {
            color: #5C6B7F;
            text-decoration: none;
            transition: color 0.15s;
            display: inline-block;
            padding-block: 3px;
        }
        .aa-breadcrumb__link:hover {
            color: #1B2A4A;
            text-decoration: underline;
        }
        .aa-breadcrumb__sep {
            margin: 0 8px;
            color: #CBD5E0;
            font-size: 14px;
        }
        .aa-breadcrumb__current {
            color: #2D3748;
            font-weight: 500;
        }
        </style>
        <?php
    }

    /**
     * [asiaauto_gallery] — Image carousel with thumbnails and lightbox.
     *
     * Reads gallery meta (serialized attach IDs) + _thumbnail_id.
     * Outputs: main image area + thumb strip + prev/next arrows + counter.
     * Click main image → fullscreen lightbox with keyboard nav.
     * Zero JS dependencies (vanilla).
     */
    public function renderGallery(array $atts = []): string {
        $post_id = get_the_ID();
        if (!$post_id) {
            return '';
        }

        $images = $this->getGalleryImages($post_id);
        if (empty($images)) {
            return '<div class="aa-gallery--empty">Brak zdjęć</div>';
        }

        $total = count($images);

        if (!self::$gallery_assets_enqueued) {
            self::$gallery_assets_enqueued = true;
        }

        ob_start();
        $this->renderGalleryCSS();
        ?>
        <div class="aa-gallery" data-total="<?= $total ?>">

            <!-- Main image area -->
            <div class="aa-gallery__main">
                <img
                    class="aa-gallery__img"
                    src="<?= esc_url($images[0]['gallery']) ?>"
                    alt="<?= esc_attr($images[0]['alt']) ?>"
                    data-index="0"
                    fetchpriority="high"
                    decoding="async"
                />
                <?php if ($total > 1): ?>
                    <button class="aa-gallery__arrow aa-gallery__arrow--prev" aria-label="Poprzednie zdjęcie">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <button class="aa-gallery__arrow aa-gallery__arrow--next" aria-label="Następne zdjęcie">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                <?php endif; ?>
                <span class="aa-gallery__counter">1 / <?= $total ?></span>
                <?php /* T-115 etap 2: waga w prawym górnym rogu, pełny ekran przeniesiony na dół (asiaauto-schowek.css) */ ?>
                <?= (is_singular('listings') && class_exists('AsiaAuto_Compare')) ? AsiaAuto_Compare::przyciskPorownania($post_id, 'galeria') : '' ?>
                <button class="aa-gallery__fullscreen" aria-label="Pełny ekran">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3m0 18h3a2 2 0 002-2v-3M3 16v3a2 2 0 002 2h3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>

            <!-- Thumbnail strip -->
            <?php if ($total > 1): ?>
            <div class="aa-gallery__thumbs">
                <div class="aa-gallery__thumbs-track">
                    <?php foreach ($images as $i => $img): ?>
                        <button
                            class="aa-gallery__thumb<?= $i === 0 ? ' aa-gallery__thumb--active' : '' ?>"
                            data-index="<?= $i ?>"
                            aria-label="Zdjęcie <?= $i + 1 ?> z <?= $total ?>"
                        >
                            <img
                                src="<?= esc_url($img['thumb']) ?>"
                                alt="<?= esc_attr($img['alt']) ?>"
                                loading="<?= $i < 6 ? 'eager' : 'lazy' ?>"
                            />
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Lightbox (hidden, activated on click) -->
            <div class="aa-lightbox" aria-hidden="true">
                <div class="aa-lightbox__backdrop"></div>
                <div class="aa-lightbox__content">
                    <img class="aa-lightbox__img" src="" alt="" />
                    <?php if ($total > 1): ?>
                        <button class="aa-lightbox__arrow aa-lightbox__arrow--prev" aria-label="Poprzednie">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <button class="aa-lightbox__arrow aa-lightbox__arrow--next" aria-label="Następne">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    <?php endif; ?>
                    <span class="aa-lightbox__counter">1 / <?= $total ?></span>
                    <button class="aa-lightbox__close" aria-label="Zamknij">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
            </div>

            <!-- Image data for JS -->
            <script type="application/json" class="aa-gallery__data">
                <?= wp_json_encode(array_map(function ($img) {
                    return [
                        'gallery'  => $img['gallery'],
                        'full'     => $img['full'],
                        'alt'      => $img['alt'],
                    ];
                }, $images)) ?>
            </script>
        </div>
        <?php
        $this->renderGalleryJS();
        return ob_get_clean();
    }

    /**
     * Collect gallery images from post meta.
     *
     * Priority: gallery meta (serialized attach IDs).
     * Fallback: _thumbnail_id only.
     * Returns array of ['thumb' => url, 'gallery' => url, 'full' => url, 'alt' => text].
     */
    private function getGalleryImages(int $post_id): array {
        $images = [];
        $seen_ids = [];

        // Featured image first
        $thumb_id = (int) get_post_thumbnail_id($post_id);

        // Gallery meta (serialized array of attachment IDs)
        $gallery_raw = get_post_meta($post_id, 'gallery', true);
        $gallery_ids = [];

        if (!empty($gallery_raw)) {
            if (is_string($gallery_raw)) {
                $gallery_ids = maybe_unserialize($gallery_raw);
            }
            if (is_array($gallery_raw)) {
                $gallery_ids = $gallery_raw;
            }
        }

        if (!is_array($gallery_ids)) {
            $gallery_ids = [];
        }

        // Prepend featured image if not already in gallery
        if ($thumb_id && !in_array($thumb_id, $gallery_ids, true)) {
            array_unshift($gallery_ids, $thumb_id);
        }

        // If still empty, try featured only
        if (empty($gallery_ids) && $thumb_id) {
            $gallery_ids = [$thumb_id];
        }

        foreach ($gallery_ids as $attach_id) {
            $attach_id = (int) $attach_id;
            if ($attach_id <= 0 || isset($seen_ids[$attach_id])) {
                continue;
            }
            $seen_ids[$attach_id] = true;

            $thumb_url   = wp_get_attachment_image_url($attach_id, 'asiaauto-thumb');
            $gallery_url = wp_get_attachment_image_url($attach_id, 'asiaauto-gallery');
            $full_url    = wp_get_attachment_image_url($attach_id, 'full');
            $alt         = get_post_meta($attach_id, '_wp_attachment_image_alt', true);

            if (!$gallery_url && !$full_url) {
                continue;
            }

            $images[] = [
                'thumb'   => $thumb_url ?: $gallery_url ?: $full_url,
                'gallery' => $gallery_url ?: $full_url,
                'full'    => $full_url ?: $gallery_url,
                'alt'     => $alt ?: get_the_title($post_id),
            ];
        }

        return $images;
    }

    /**
     * Gallery CSS — follows AsiaAuto design system.
     * Scoped to .aa-gallery to avoid conflicts with Elementor.
     */
    private function renderGalleryCSS(): void {
        ?>
        <style>
        /* === AsiaAuto Gallery === */
        .aa-gallery {
            --aa-radius: 6px;
            --aa-primary: #1B2A4A;
            --aa-surface: #FFFFFF;
            --aa-border: #E1E4E8;
            --aa-text-secondary: #5C6B7F;
            --aa-accent: #C92A2B;
            width: 100%;
        }

        /* --- Reset Elementor Kit global button styles --- */
        .aa-gallery button.aa-gallery__arrow,
        .aa-gallery button.aa-gallery__fullscreen,
        .aa-gallery button.aa-gallery__thumb,
        .aa-lightbox button.aa-lightbox__arrow,
        .aa-lightbox button.aa-lightbox__close {
            background-color: transparent;
            padding: 0;
            border: none;
            font-size: inherit;
            font-weight: inherit;
            line-height: 1;
            letter-spacing: 0;
            text-transform: none;
        }

        /* --- Main image --- */
        .aa-gallery__main {
            position: relative;
            width: 100%;
            aspect-ratio: 4 / 3;
            background: var(--aa-surface);
            border-radius: var(--aa-radius);
            overflow: hidden;
            cursor: pointer;
        }
        .aa-gallery__img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: opacity 0.2s ease;
        }
        .aa-gallery__img.aa-gallery__img--loading {
            opacity: 0.5;
        }

        /* --- Arrows --- */
        .aa-gallery .aa-gallery__arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 50%;
            background: rgba(255,255,255,0.9);
            color: var(--aa-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            transition: background 0.15s, transform 0.15s;
            z-index: 2;
            opacity: 0;
        }
        .aa-gallery .aa-gallery__main:hover .aa-gallery__arrow {
            opacity: 1;
        }
        .aa-gallery .aa-gallery__arrow:hover {
            background: #fff;
            transform: translateY(-50%) scale(1.05);
        }
        .aa-gallery .aa-gallery__arrow--prev { left: 12px; }
        .aa-gallery .aa-gallery__arrow--next { right: 12px; }

        /* --- Counter badge --- */
        .aa-gallery__counter {
            position: absolute;
            bottom: 12px;
            left: 12px;
            background: rgba(0,0,0,0.6);
            color: #fff;
            font-size: 13px;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 4px;
            pointer-events: none;
            font-family: 'Inter', sans-serif;
            z-index: 2;
        }

        /* --- Fullscreen button --- */
        .aa-gallery .aa-gallery__fullscreen {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 50%;
            background: rgba(0,0,0,0.5);
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.15s;
            z-index: 2;
        }
        .aa-gallery .aa-gallery__main:hover .aa-gallery__fullscreen {
            opacity: 1;
        }
        .aa-gallery .aa-gallery__fullscreen:hover {
            background: rgba(0,0,0,0.7);
        }

        /* --- Thumbnails --- */
        .aa-gallery__thumbs {
            margin-top: 8px;
            overflow: hidden;
        }
        .aa-gallery__thumbs-track {
            display: flex;
            gap: 6px;
            overflow-x: auto;
            scroll-behavior: smooth;
            scrollbar-width: thin;
            scrollbar-color: var(--aa-border) transparent;
            padding-bottom: 4px;
        }
        .aa-gallery__thumbs-track::-webkit-scrollbar {
            height: 4px;
        }
        .aa-gallery__thumbs-track::-webkit-scrollbar-thumb {
            background: var(--aa-border);
            border-radius: 2px;
        }
        .aa-gallery .aa-gallery__thumb {
            flex: 0 0 auto;
            width: 80px;
            height: 56px;
            border: 2px solid transparent;
            border-radius: 4px;
            overflow: hidden;
            cursor: pointer;
            padding: 0;
            background: var(--aa-surface);
            transition: border-color 0.15s, opacity 0.15s;
            opacity: 0.7;
        }
        .aa-gallery .aa-gallery__thumb--active {
            border-color: var(--aa-primary);
            opacity: 1;
        }
        .aa-gallery .aa-gallery__thumb:hover {
            opacity: 1;
        }
        .aa-gallery__thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* --- Lightbox --- */
        .aa-lightbox {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 99999;
        }
        .aa-lightbox[aria-hidden="false"] {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .aa-lightbox__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.92);
        }
        .aa-lightbox__content {
            position: relative;
            max-width: 95vw;
            max-height: 90vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .aa-lightbox__img {
            max-width: 95vw;
            max-height: 88vh;
            object-fit: contain;
            border-radius: 4px;
            transition: opacity 0.2s;
        }
        .aa-lightbox .aa-lightbox__close {
            position: fixed;
            top: 16px;
            right: 16px;
            width: 44px;
            height: 44px;
            border: none;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s;
            z-index: 3;
        }
        .aa-lightbox .aa-lightbox__close:hover {
            background: rgba(255,255,255,0.3);
        }
        .aa-lightbox .aa-lightbox__arrow {
            position: fixed;
            top: 50%;
            transform: translateY(-50%);
            width: 48px;
            height: 48px;
            border: none;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s;
            z-index: 3;
        }
        .aa-lightbox .aa-lightbox__arrow:hover {
            background: rgba(255,255,255,0.3);
        }
        .aa-lightbox .aa-lightbox__arrow--prev { left: 16px; }
        .aa-lightbox .aa-lightbox__arrow--next { right: 16px; }
        .aa-lightbox__counter {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            color: rgba(255,255,255,0.8);
            font-size: 14px;
            font-weight: 500;
            font-family: 'Inter', sans-serif;
            z-index: 3;
        }

        /* --- Empty state --- */
        .aa-gallery--empty {
            width: 100%;
            aspect-ratio: 4 / 3;
            background: #F5F6F8;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--aa-text-secondary, #5C6B7F);
            font-size: 15px;
            font-family: 'Inter', sans-serif;
        }

        /* --- Mobile --- */
        @media (max-width: 767px) {
            .aa-gallery__arrow {
                width: 36px;
                height: 36px;
                opacity: 1;
            }
            .aa-gallery__fullscreen {
                opacity: 1;
            }
            .aa-gallery__thumb {
                width: 64px;
                height: 44px;
            }
        }
        </style>
        <?php
    }

    /**
     * Gallery JS — vanilla, zero dependencies.
     * Handles: thumb click, arrows, counter, lightbox open/close/nav, keyboard.
     */
    private function renderGalleryJS(): void {
        ?>
        <script>
        (function() {
            document.querySelectorAll('.aa-gallery').forEach(function(gallery) {
                var data    = JSON.parse(gallery.querySelector('.aa-gallery__data').textContent);
                var total   = data.length;
                var current = 0;

                var mainImg   = gallery.querySelector('.aa-gallery__img');
                var counter   = gallery.querySelector('.aa-gallery__counter');
                var thumbs    = gallery.querySelectorAll('.aa-gallery__thumb');
                var prevBtn   = gallery.querySelector('.aa-gallery__arrow--prev');
                var nextBtn   = gallery.querySelector('.aa-gallery__arrow--next');
                var fsBtn     = gallery.querySelector('.aa-gallery__fullscreen');

                var lightbox    = gallery.querySelector('.aa-lightbox');
                var lbImg       = lightbox ? lightbox.querySelector('.aa-lightbox__img') : null;
                var lbCounter   = lightbox ? lightbox.querySelector('.aa-lightbox__counter') : null;
                var lbClose     = lightbox ? lightbox.querySelector('.aa-lightbox__close') : null;
                var lbBackdrop  = lightbox ? lightbox.querySelector('.aa-lightbox__backdrop') : null;
                var lbPrev      = lightbox ? lightbox.querySelector('.aa-lightbox__arrow--prev') : null;
                var lbNext      = lightbox ? lightbox.querySelector('.aa-lightbox__arrow--next') : null;

                function showImage(index) {
                    if (index < 0) index = total - 1;
                    if (index >= total) index = 0;
                    current = index;

                    mainImg.classList.add('aa-gallery__img--loading');
                    mainImg.src = data[index].gallery;
                    mainImg.alt = data[index].alt;
                    mainImg.onload = function() {
                        mainImg.classList.remove('aa-gallery__img--loading');
                    };

                    counter.textContent = (index + 1) + ' / ' + total;

                    thumbs.forEach(function(t, i) {
                        t.classList.toggle('aa-gallery__thumb--active', i === index);
                        if (i === index) {
                            t.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                        }
                    });
                }

                // Thumb clicks
                thumbs.forEach(function(thumb) {
                    thumb.addEventListener('click', function() {
                        showImage(parseInt(this.dataset.index, 10));
                    });
                });

                // Arrow clicks (gallery)
                if (prevBtn) prevBtn.addEventListener('click', function(e) { e.stopPropagation(); showImage(current - 1); });
                if (nextBtn) nextBtn.addEventListener('click', function(e) { e.stopPropagation(); showImage(current + 1); });

                // Lightbox
                function openLightbox() {
                    if (!lightbox) return;
                    lightbox.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                    showLightboxImage(current);
                }

                function closeLightbox() {
                    if (!lightbox) return;
                    lightbox.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                }

                function showLightboxImage(index) {
                    if (index < 0) index = total - 1;
                    if (index >= total) index = 0;
                    current = index;
                    lbImg.src = data[index].full;
                    lbImg.alt = data[index].alt;
                    if (lbCounter) lbCounter.textContent = (index + 1) + ' / ' + total;
                    // Sync gallery too
                    showImage(index);
                }

                // Open lightbox
                mainImg.addEventListener('click', openLightbox);
                if (fsBtn) fsBtn.addEventListener('click', function(e) { e.stopPropagation(); openLightbox(); });

                // Close lightbox
                if (lbClose) lbClose.addEventListener('click', closeLightbox);
                if (lbBackdrop) lbBackdrop.addEventListener('click', closeLightbox);

                // Lightbox arrows
                if (lbPrev) lbPrev.addEventListener('click', function() { showLightboxImage(current - 1); });
                if (lbNext) lbNext.addEventListener('click', function() { showLightboxImage(current + 1); });

                // Keyboard navigation
                document.addEventListener('keydown', function(e) {
                    if (lightbox && lightbox.getAttribute('aria-hidden') === 'false') {
                        if (e.key === 'Escape') closeLightbox();
                        if (e.key === 'ArrowLeft') showLightboxImage(current - 1);
                        if (e.key === 'ArrowRight') showLightboxImage(current + 1);
                    }
                });

                // Touch swipe (basic)
                var touchStartX = 0;
                [gallery.querySelector('.aa-gallery__main'), lightbox].forEach(function(el) {
                    if (!el) return;
                    el.addEventListener('touchstart', function(e) {
                        touchStartX = e.changedTouches[0].screenX;
                    }, { passive: true });
                    el.addEventListener('touchend', function(e) {
                        var diff = e.changedTouches[0].screenX - touchStartX;
                        if (Math.abs(diff) > 50) {
                            if (diff > 0) showImage(current - 1);
                            else showImage(current + 1);
                            if (lightbox.getAttribute('aria-hidden') === 'false') {
                                showLightboxImage(current);
                            }
                        }
                    }, { passive: true });
                });
            });
        })();
        </script>
        <?php
    }

    // =========================================================================
    // [asiaauto_key_specs] — Icon bar with 6 key parameters
    // =========================================================================

    /**
     * [asiaauto_key_specs] — Horizontal icon bar with key vehicle parameters.
     *
     * Shows: przebieg, moc, paliwo, skrzynia, rok, właściciele.
     * Reads from post meta + taxonomies + extra_prep.
     * Design reference: mobile.de top spec bar.
     *
     * Power logic:
     * - PHEV/EREV/EV → total electric power from extra_prep (kW + KM)
     * - ICE/48V/unknown → engine_max_horsepower from extra_prep or _asiaauto_horse_power meta
     *
     * @since 0.9.1
     * @since 0.10.2 Power logic for PHEV/EREV/EV, short fuel labels
     */
    public function renderKeySpecs(array $atts = []): string {
        $post_id = get_the_ID();
        if (!$post_id) {
            return '';
        }

        // Load extra_prep for power fields
        $ep = $this->getExtraPrep($post_id);

        $specs = [];

        // Przebieg
        $mileage = get_post_meta($post_id, 'mileage', true);
        if (!empty($mileage) && is_numeric($mileage)) {
            $specs[] = [
                'icon'  => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z" stroke="currentColor" stroke-width="1.8"/><path d="M12 6v6l4 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
                'value' => number_format((int) $mileage, 0, ',', ' ') . ' km',
                'label' => 'Przebieg',
            ];
        }

        // Moc — smart logic based on fuel type
        $power_spec = $this->resolvePower($post_id, $ep);
        if ($power_spec) {
            $specs[] = [
                'icon'  => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                'value' => $power_spec['value'],
                'label' => $power_spec['label'],
            ];
        }

        // Paliwo (taxonomy) — short label
        $fuel_terms = get_the_terms($post_id, 'fuel');
        if ($fuel_terms && !is_wp_error($fuel_terms)) {
            $fuel_short = $this->shortFuelLabel($fuel_terms[0]->name);
            $specs[] = [
                'icon'  => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M3 22V6a2 2 0 012-2h8a2 2 0 012 2v16" stroke="currentColor" stroke-width="1.8"/><path d="M3 22h12M15 10h2a2 2 0 012 2v3a2 2 0 002 0v-5l-3-3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><rect x="6" y="7" width="6" height="4" rx="0.5" stroke="currentColor" stroke-width="1.5"/></svg>',
                'value' => esc_html($fuel_short),
                'label' => 'Paliwo',
            ];
        }

        // Skrzynia biegów (taxonomy)
        $trans_terms = get_the_terms($post_id, 'transmission');
        if ($trans_terms && !is_wp_error($trans_terms)) {
            $specs[] = [
                'icon'  => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="5" cy="6" r="2" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="6" r="2" stroke="currentColor" stroke-width="1.8"/><circle cx="19" cy="6" r="2" stroke="currentColor" stroke-width="1.8"/><circle cx="5" cy="18" r="2" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="18" r="2" stroke="currentColor" stroke-width="1.8"/><path d="M5 8v8M12 8v8M19 8v4a2 2 0 01-2 2h-3" stroke="currentColor" stroke-width="1.8"/></svg>',
                'value' => esc_html($trans_terms[0]->name),
                'label' => 'Skrzynia',
            ];
        }

        // Rejestracja (registration_date meta, format DB: DD/MM/YYYY → UI: MM/YYYY).
        // Rok modelowy NIE jest tutaj — trafia do sekcji "Dane podstawowe" w [asiaauto_tech_specs].
        $reg_date = (string) get_post_meta($post_id, 'registration_date', true);
        if ($reg_date !== '' && preg_match('#^\d{2}/(\d{2})/(\d{4})$#', $reg_date, $m)) {
            $specs[] = [
                'icon'  => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
                'value' => esc_html("{$m[1]}/{$m[2]}"),
                'label' => 'Rejestracja',
            ];
        }

        // Właściciele
        $owners = get_post_meta($post_id, '_asiaauto_owners_count', true);
        if (!empty($owners) && is_numeric($owners)) {
            $specs[] = [
                'icon'  => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="1.8"/></svg>',
                'value' => (int) $owners,
                'label' => 'Właściciele',
            ];
        }

        if (empty($specs)) {
            return '';
        }

        ob_start();
        $this->renderKeySpecsCSS();
        ?>
        <div class="aa-key-specs">
            <?php foreach ($specs as $spec): ?>
                <div class="aa-key-specs__item">
                    <span class="aa-key-specs__icon"><?= $spec['icon'] ?></span>
                    <span class="aa-key-specs__value"><?= $spec['value'] ?></span>
                    <span class="aa-key-specs__label"><?= esc_html($spec['label']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Resolve power display value based on powertrain type.
     *
     * PHEV/EREV/EV → total electric power (front_electric_max_horsepower or total_electric_power)
     * ICE/48V → engine_max_horsepower
     *
     * @return array|null ['value' => '272 KM', 'label' => 'Moc łączna'] or null
     */
    private function resolvePower(int $post_id, array $ep): ?array {
        // Determine fuel type from taxonomy
        $fuel_terms = get_the_terms($post_id, 'fuel');
        $fuel_slug = '';
        if ($fuel_terms && !is_wp_error($fuel_terms)) {
            $fuel_slug = $fuel_terms[0]->slug;
        }

        $is_electric = in_array($fuel_slug, ['ev', 'bev', 'phev', 'erev', 'mhev'], true)
            || !empty($ep['total_electric_power']);

        if ($is_electric) {
            // Priority: front_electric_max_horsepower (total system HP for display)
            $hp_total = (int) ($ep['front_electric_max_horsepower'] ?? 0);
            $kw_total = (int) ($ep['total_electric_power'] ?? 0);

            if ($hp_total > 0) {
                $val = "{$hp_total} KM";
                if ($kw_total > 0) {
                    $val = "{$kw_total} kW ({$hp_total} KM)";
                }
                return ['value' => $val, 'label' => 'Moc łączna'];
            }

            if ($kw_total > 0) {
                return ['value' => "{$kw_total} kW", 'label' => 'Moc łączna'];
            }
        }

        // Fallback: engine horsepower (ICE or 48V mild hybrid)
        $engine_hp = (int) ($ep['engine_max_horsepower'] ?? 0);
        if ($engine_hp <= 0) {
            $engine_hp = (int) get_post_meta($post_id, '_asiaauto_horse_power', true);
        }

        if ($engine_hp > 0) {
            $engine_kw = (int) ($ep['engine_max_power'] ?? 0);
            $val = $engine_kw > 0
                ? "{$engine_kw} kW ({$engine_hp} KM)"
                : "{$engine_hp} KM";
            return ['value' => $val, 'label' => 'Moc'];
        }

        return null;
    }

    /**
     * Shorten fuel type label for key specs display.
     * "Hybryda plug-in (PHEV)" → "PHEV", "Elektryczny z range extenderem (EREV)" → "EREV"
     */
    private function shortFuelLabel(string $name): string {
        $lower = mb_strtolower($name);
        if (str_contains($lower, 'phev') || str_contains($lower, 'plug-in')) return 'PHEV';
        if (str_contains($lower, 'erev') || str_contains($lower, 'range ext')) return 'EREV';
        if (str_contains($lower, 'mhev') || str_contains($lower, '48v')) return 'MHEV';
        if (str_contains($lower, 'elektr') || str_contains($lower, 'bev')) return 'EV';
        return $name; // Benzyna, Diesel, etc. — pass through
    }

    /**
     * Load extra_prep from post meta (cached per request).
     */
    private function getExtraPrep(int $post_id): array {
        static $cache = [];
        if (isset($cache[$post_id])) {
            return $cache[$post_id];
        }
        $raw = get_post_meta($post_id, '_asiaauto_extra_prep', true);
        if (empty($raw)) {
            $cache[$post_id] = [];
            return [];
        }
        $ep = is_string($raw) ? json_decode($raw, true) : $raw;
        $cache[$post_id] = is_array($ep) ? $ep : [];
        return $cache[$post_id];
    }

    private function renderKeySpecsCSS(): void {
        static $rendered = false;
        if ($rendered) return;
        $rendered = true;
        ?>
        <style>
        .aa-key-specs {
            display: flex;
            flex-wrap: wrap;
            gap: 0;
            border: 1px solid #E1E4E8;
            border-radius: 6px;
            background: #fff;
            overflow: hidden;
        }
        .aa-key-specs__item {
            flex: 1 1 0;
            min-width: 100px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 14px 8px;
            border-right: 1px solid #E1E4E8;
        }
        .aa-key-specs__item:last-child {
            border-right: none;
        }
        .aa-key-specs__icon {
            color: #5C6B7F;
            display: flex;
            align-items: center;
        }
        .aa-key-specs__value {
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: #2D3748;
            text-align: center;
            line-height: 1.2;
        }
        .aa-key-specs__label {
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            font-weight: 400;
            color: #5C6B7F;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        @media (max-width: 767px) {
            .aa-key-specs {
                flex-wrap: wrap;
            }
            .aa-key-specs__item {
                flex: 1 1 calc(33.33% - 1px);
                min-width: 0;
                border-bottom: 1px solid #E1E4E8;
            }
            .aa-key-specs__item:nth-child(3) {
                border-right: none;
            }
            .aa-key-specs__item:nth-last-child(-n+3) {
                border-bottom: none;
            }
        }
        </style>
        <?php
    }

    // =========================================================================
    // [asiaauto_badges] — Status, condition, fuel type badges
    // =========================================================================

    /**
     * [asiaauto_badges] — Visual badges for listing status.
     *
     * Shows:
     * - Reservation status: Zarezerwowane (red), W drodze (amber), Na placu (green)
     * - Condition (taxonomy): Używany / Nowy
     * - Fuel type highlight: Elektryczny (green badge), Hybryda (teal)
     * - Source: Dongchedi
     */
    public function renderBadges(array $atts = []): string {
        $post_id = get_the_ID();
        if (!$post_id) {
            return '';
        }

        $badges = [];

        // Reservation status — highest priority
        $reservation = get_post_meta($post_id, '_asiaauto_reservation_status', true);
        if ($reservation === 'reserved') {
            $badges[] = ['text' => 'Zarezerwowane', 'class' => 'aa-badge--reserved'];
        } elseif ($reservation === 'in_transit') {
            $badges[] = ['text' => 'W drodze', 'class' => 'aa-badge--transit'];
        } elseif ($reservation === 'on_lot') {
            $badges[] = ['text' => 'Na placu — Rzeszów', 'class' => 'aa-badge--onlot'];
        }

        // Condition
        $condition_terms = get_the_terms($post_id, 'condition');
        if ($condition_terms && !is_wp_error($condition_terms)) {
            $cond_name = $condition_terms[0]->name;
            if (mb_stripos($cond_name, 'now') !== false) {
                $badges[] = ['text' => 'Nowy', 'class' => 'aa-badge--new'];
            }
        }

        // Fuel — highlight EV / Hybrid
        $fuel_terms = get_the_terms($post_id, 'fuel');
        if ($fuel_terms && !is_wp_error($fuel_terms)) {
            $fuel_name = mb_strtolower($fuel_terms[0]->name);
            if (str_contains($fuel_name, 'elektr')) {
                $badges[] = ['text' => 'Elektryczny', 'class' => 'aa-badge--ev'];
            } elseif (str_contains($fuel_name, 'hybr')) {
                $badges[] = ['text' => 'Hybryda', 'class' => 'aa-badge--hybrid'];
            }
        }

        // Source
        $source = get_post_meta($post_id, '_asiaauto_source', true);
        if (!empty($source)) {
            $source_label = ucfirst($source);
            $badges[] = ['text' => $source_label, 'class' => 'aa-badge--source'];
        }

        if (empty($badges)) {
            return '';
        }

        ob_start();
        $this->renderBadgesCSS();
        ?>
        <div class="aa-badges">
            <?php foreach ($badges as $badge): ?>
                <span class="aa-badge <?= esc_attr($badge['class']) ?>"><?= esc_html($badge['text']) ?></span>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function renderBadgesCSS(): void {
        static $rendered = false;
        if ($rendered) return;
        $rendered = true;
        ?>
        <style>
        .aa-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .aa-badge {
            display: inline-flex;
            align-items: center;
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 4px;
            line-height: 1.3;
            white-space: nowrap;
        }
        /* Reservation — most prominent */
        .aa-badge--reserved {
            background: #FDE8E8;
            color: #B52828;
        }
        .aa-badge--transit {
            background: #FFF5E5;
            color: #8B6914;
        }
        .aa-badge--onlot {
            background: #E6F7EE;
            color: #276749;
        }
        /* Condition */
        .aa-badge--new {
            background: #FFF5E5;
            color: #8B6914;
        }
        /* Fuel type */
        .aa-badge--ev {
            background: #E6F7EE;
            color: #276749;
        }
        .aa-badge--hybrid {
            background: #E0F2F7;
            color: #1A5568;
        }
        /* Source */
        .aa-badge--source {
            background: #EBF0F7;
            color: #1B2A4A;
        }
        </style>
        <?php
    }

    // =========================================================================
    // [asiaauto_specs] — Extra prep grouped specification tables
    // =========================================================================

    /**
     * [asiaauto_specs] — Vehicle specifications from extra_prep JSON.
     *
     * Reads _asiaauto_extra_prep meta, translates via AsiaAuto_Translator,
     * renders as grouped tables (14 categories).
     * Each group: heading + two-column key-value table.
     */
    public function renderSpecs(array $atts = []): string {
        $post_id = get_the_ID();
        if (!$post_id) {
            return '';
        }

        $raw = get_post_meta($post_id, '_asiaauto_extra_prep', true);
        if (empty($raw)) {
            return '';
        }

        $extra_prep = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!is_array($extra_prep) || empty($extra_prep)) {
            return '';
        }

        $translator = new AsiaAuto_Translator();
        $groups = $translator->translateExtraPrep($extra_prep);

        if (empty($groups)) {
            return '';
        }

        ob_start();
        $this->renderSpecsCSS();
        ?>
        <div class="aa-specs">
            <?php foreach ($groups as $cat_id => $group): ?>
                <div class="aa-specs__group">
                    <h3 class="aa-specs__group-title"><?= esc_html($group['label']) ?></h3>
                    <table class="aa-specs__table">
                        <tbody>
                            <?php foreach ($group['items'] as $item): ?>
                                <tr class="aa-specs__row">
                                    <td class="aa-specs__label"><?= esc_html($item['label']) ?></td>
                                    <td class="aa-specs__value"><?= esc_html($item['value']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function renderSpecsCSS(): void {
        static $rendered = false;
        if ($rendered) return;
        $rendered = true;
        ?>
        <style>
        .aa-specs {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        .aa-specs__group {
            background: #fff;
            border: 1px solid #E1E4E8;
            border-radius: 6px;
            overflow: hidden;
        }
        .aa-specs__group-title {
            font-family: 'Inter', sans-serif;
            font-size: 16px;
            font-weight: 600;
            color: #1B2A4A;
            padding: 14px 16px;
            margin: 0;
            border-bottom: 1px solid #E1E4E8;
            background: #F8F9FA;
        }
        .aa-specs__table {
            width: 100%;
            border-collapse: collapse;
        }
        .aa-specs__row {
            border-bottom: 1px solid #F0F1F3;
        }
        .aa-specs__row:last-child {
            border-bottom: none;
        }
        .aa-specs__label {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 400;
            color: #5C6B7F;
            padding: 10px 16px;
            width: 45%;
            vertical-align: top;
        }
        .aa-specs__value {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 500;
            color: #2D3748;
            padding: 10px 16px;
            vertical-align: top;
        }
        @media (max-width: 767px) {
            .aa-specs__label,
            .aa-specs__value {
                padding: 8px 12px;
                font-size: 12px;
            }
            .aa-specs__group-title {
                font-size: 14px;
                padding: 12px;
            }
        }
        </style>
        <?php
    }

    // =========================================================================
    // [asiaauto_updated] — Last sync date (fallback: post date)
    // =========================================================================

    /**
     * [asiaauto_updated] — Returns date of last API sync, or post creation date
     * if listing was never updated. Format: d.m.Y, H:i (PL).
     * No styles — format via Elementor Text widget prefix/suffix/typography.
     */
    public function renderUpdated(array $atts = []): string {
        $post_id = get_the_ID();
        if (!$post_id) {
            return '';
        }

        $raw  = get_post_meta($post_id, '_asiaauto_last_sync', true);
        $ts   = $raw ? strtotime($raw) : false;
        if (!$ts || $ts < 0) {
            $post = get_post($post_id);
            $ts   = $post ? strtotime($post->post_date) : time();
        }

        //return esc_html(date_i18n('d.m.Y', $ts)); sama data
        return esc_html(date_i18n('d.m.Y, H:i', $ts));
    }

    // =========================================================================
    // [asiaauto_price] — Formatted price (space separator)
    // =========================================================================


    /**
     * [asiaauto_price] — Outputs formatted price from post meta.
     *
     * 132000 → 132 000 PLN
     * No styles — use Elementor widget styling.
     */
    public function renderPrice(array $atts = []): string {
        $post_id = get_the_ID();
        if (!$post_id) {
            return '';
        }

        $price = get_post_meta($post_id, 'price', true);
        if (empty($price) || !is_numeric($price)) {
            return 'Cena na zapytanie';
        }

        return number_format((float) $price, 0, ',', ' ') . ' PLN';
    }

    /**
     * [asiaauto_price_netto] — Cena netto (brutto bez VAT).
     *
     * Format identyczny jak [asiaauto_price] — gola kwota "X XXX PLN",
     * bez stylów. Elementor stylizuje wynik przez ustawienia widgetu.
     * Cena `price` w meta jest brutto (VAT wliczony) — tutaj odejmujemy VAT.
     *
     * Attr: vat="23" (stawka VAT w %)
     *
     * @since 0.30.14
     */
    public function renderPriceNetto(array $atts = []): string {
        $post_id = get_the_ID();
        if (!$post_id) return '';

        $price = get_post_meta($post_id, 'price', true);
        if (empty($price) || !is_numeric($price)) {
            return 'Cena na zapytanie';
        }

        $atts    = shortcode_atts(['vat' => '23'], $atts);
        $vat_pct = (float) $atts['vat'];
        $netto   = (float) $price / (1 + $vat_pct / 100);

        return number_format($netto, 0, ',', ' ') . ' PLN';
    }

    /**
     * [asiaauto_included] — Lista elementów zawartych w cenie (CIF package).
     *
     * Zwraca czysty <ul> bez inline-styli. Elementor/theme stylizują listę
     * naturalnie (bullety, kolory, marginesy).
     *
     * Przeznaczony do wklejenia obok boksu "Informacje" w right-col single
     * listing (Elementor: dwie kolumny, w prawej Text Editor z tym shortcode).
     *
     * Attr: title="W cenie" — opcjonalny nagłówek (H3). Pusty = bez nagłówka.
     *
     * @since 0.30.14
     */
    public function renderIncluded(array $atts = []): string {
        $atts = shortcode_atts(['title' => ''], $atts);

        $items = [
            'Zmiana języka na PL / EN',
            'Sterowanie głosowe po polsku (koncern BYD: BYD, Denza, Leopard)',
            'Przenośna ładowarka 7 kW (EU)',
            'Przejściówka do ładowarek miejskich',
            'Dodatkowy kluczyk / pilot',
            'Dwa komplety filtrów eksploatacyjnych',
        ];

        $out = '';
        if (!empty($atts['title'])) {
            $out .= '<h3 class="aa-incl__title">' . esc_html($atts['title']) . '</h3>';
        }
        $out .= '<ul class="aa-incl">';
        foreach ($items as $item) {
            $out .= '<li>' . esc_html($item) . '</li>';
        }
        $out .= '</ul>';

        return $out;
    }

    /**
     * [asiaauto_price_breakdown] — Rozkład brutto + netto z własnymi stylami.
     *
     * Pełny blok: duża kwota brutto (czerwona) oraz szara linia "netto: X PLN"
     * w kolumnie obok. VAT usunięto (0.30.15) — kwota VAT była zbędna,
     * klient chce tylko brutto i netto.
     *
     * UWAGA: zawiera inline <style> — kolor i font-size SĄ hardkodowane
     * w shortcode. Jeśli wkleisz do widgetu Elementora który stylizuje tekst
     * (font-size, color), to style widgetu mogą zostać zdominowane przez te
     * z shortcode. Przeznaczony raczej do bloku Shortcode w Elementorze
     * (nie Text Editor) lub wprost w zawartości posta.
     *
     * Attr:
     *   vat="23" — stawka VAT w % (używana do wyliczenia netto)
     *
     * @since 0.30.14
     */
    public function renderPriceBreakdown(array $atts = []): string {
        $post_id = get_the_ID();
        if (!$post_id) return '';

        $price = get_post_meta($post_id, 'price', true);
        if (empty($price) || !is_numeric($price)) {
            return '<div class="aa-pricebd aa-pricebd--na">Cena na zapytanie</div>';
        }

        $atts = shortcode_atts(['vat' => '23'], $atts);

        $vat_pct = (float) $atts['vat'];
        $brutto  = (float) $price;
        $netto   = $brutto / (1 + $vat_pct / 100);

        $fmt = fn(float $v) => number_format($v, 0, ',', ' ');

        ob_start();
        ?>
        <div class="aa-pricebd">
            <div class="aa-pricebd__brutto"><?= $fmt($brutto) ?> PLN</div>
            <div class="aa-pricebd__meta">
                <div class="aa-pricebd__line"><span>netto:</span> <strong><?= $fmt($netto) ?> PLN</strong></div>
            </div>
        </div>
        <style>
            .aa-pricebd { font-family: inherit; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
            .aa-pricebd__brutto {
                flex: 1 1 50%;
                font-size: 24px; font-weight: 700; color: #C92A2B;
                line-height: 1.1; white-space: nowrap;
            }
            .aa-pricebd__meta {
                flex: 1 1 calc(50% - 12px); min-width: 130px;
                display: flex; flex-direction: column; line-height: 1.35;
            }
            .aa-pricebd__line { font-size: 12px; color: #5C6B7F; }
            .aa-pricebd__line strong { color: #2D3748; font-weight: 600; }
            .aa-pricebd--na { font-size: 18px; color: #5C6B7F; }
            @media (min-width: 768px) {
                .aa-pricebd__brutto { font-size: 30px; }
                .aa-pricebd__line { font-size: 13px; }
            }
        </style>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // [asiaauto_tech_specs] — Technical data (collapsible, like Technische Daten)
    // =========================================================================

    /**
     * [asiaauto_tech_specs] — Collapsible technical specifications.
     *
     * Groups extra_prep into logical sections:
     * 1. Podstawowe (body, dimensions, weight, luggage)
     * 2. Silnik (engine description, power, torque, displacement)
     * 3. Układ elektryczny (only PHEV/EREV/EV — battery, range, charging)
     * 4. Skrzynia biegów (gearbox, drive, modes)
     * 5. Zawieszenie i hamulce (suspension, brakes, tires)
     * 6. Zużycie i emisje (fuel consumption, WLTC)
     *
     * Initially shows first 8 rows per section, "Więcej" expands.
     *
     * @since 0.10.2
     */
    /**
     * T-187: blok „Inne egzemplarze {Model}" — wkomponowany między sekcje techniczne.
     * Reuse: [asiaauto_hub_listings] — ten sam komponent karty co huby i homepage.
     * Anchor/nazwa modelu = serieAnchor() → spójne z breadcrumbem i sticky navrow (v0.33.23).
     * CTA prowadzi do zafiltrowanego katalogu (tam są WSZYSTKIE sztuki; hub pokazuje tylko część)
     * — ten sam wzorzec, co „Wszystkie (N) →" na hubie.
     * @since 0.33.28
     */
    private function otherUnitsBlock(int $post_id): string {
        $make_terms = get_the_terms($post_id, 'make');
        if (!$make_terms || is_wp_error($make_terms)) return '';
        $make = $make_terms[0];

        $serie_terms = get_the_terms($post_id, 'serie');
        if (!$serie_terms || is_wp_error($serie_terms)) return '';
        $serie = $serie_terms[0];

        // Guard: jedyny egzemplarz modelu → nie ma czego pokazać (66 ofert-unikatów).
        $others = (int) $serie->count - 1;
        if ($others < 1) return '';

        $model  = self::serieAnchor($serie, $make);
        $filter = home_url('/samochody/?marka=' . rawurlencode($make->slug) . '&model=' . rawurlencode($serie->slug));

        return do_shortcode(sprintf(
            '[asiaauto_hub_listings n="4" make="%s" serie="%s" exclude="%d" orderby="price" heading="%s" cta_url="%s" cta_label="%s" anchor="inne-egzemplarze"]',
            esc_attr($make->slug),
            esc_attr($serie->slug),
            $post_id,
            esc_attr(sprintf('Inne egzemplarze %s (%d)', $model, $others)),
            esc_url($filter),
            esc_attr(sprintf('Wszystkie (%d) &rarr;', $others))
        ));
    }

    public function renderTechSpecs(array $atts = []): string {
        $post_id = get_the_ID();
        if (!$post_id) return '';

        // T-187 fix: 139 ofert (4,5%) nie ma extra_prep — skutek awarii getOffer() u dostawcy
        // od 01.07 (partial import bez specyfikacji). Bez tego bloku „inne egzemplarze" by tam
        // nie było WCALE, bo dziedziczyłby ten early return. Blok nie zależy od extra_prep.
        $ep = $this->getExtraPrep($post_id);
        if (empty($ep)) return $this->otherUnitsBlock($post_id);

        $sections = $this->buildTechSpecSections($ep, $post_id);
        if (empty($sections)) return $this->otherUnitsBlock($post_id);

        // T-187: liczymy raz, poza pętlą.
        $aa_other_units = $this->otherUnitsBlock($post_id);
        $aa_sec_i = 0;

        ob_start();
        $this->renderTechSpecsCSS();
        ?>
        <div class="aa-tech">
            <?php foreach ($sections as $sec_id => $sec): ?>
                <?php if (empty($sec['rows'])) continue; ?>
                <div class="aa-tech__section" data-section="<?= esc_attr($sec_id) ?>">
                    <h3 class="aa-tech__title"><?= esc_html($sec['title']) ?></h3>
                    <table class="aa-tech__table">
                        <tbody>
                            <?php foreach ($sec['rows'] as $i => $row): ?>
                                <tr class="aa-tech__row<?= $i >= 8 ? ' aa-tech__row--hidden' : '' ?>">
                                    <td class="aa-tech__label"><?= esc_html($row['label']) ?></td>
                                    <td class="aa-tech__value"><?= esc_html($row['value']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (count($sec['rows']) > 8): ?>
                        <button class="aa-tech__toggle" data-more="Więcej" data-less="Mniej">Więcej</button>
                    <?php endif; ?>
                </div>
                <?php // T-187: po 2. WYRENDEROWANEJ sekcji — kotwica po indeksie, NIE po nazwie
                      // („Układ elektryczny" nie istnieje dla ~24% katalogu — spalinowe).
                      $aa_sec_i++;
                      if ($aa_sec_i === 2 && $aa_other_units !== '') {
                          echo $aa_other_units;
                          $aa_other_units = '';
                      } ?>
            <?php endforeach; ?>
            <?php // Fallback: auto ma <2 sekcji → blok nie przepada, ląduje na końcu.
                  if ($aa_other_units !== '') echo $aa_other_units; ?>
        </div>
        <?php
        $this->renderTechSpecsJS();
        // T-214 auto-linker: terminy techniczne → hasła /wiki/ (linki tylko w tekście,
        // nagłówki i wnętrza <a> pomijane — patrz AsiaAuto_Autolink).
        return AsiaAuto_Autolink::html(ob_get_clean(), 'tech_specs', 3);
    }

    /**
     * Build tech spec sections from extra_prep data.
     */
    /** Publiczna od v0.42.0 — czyta ją porównywarka (AsiaAuto_Versions_Table), żeby sekcje były 1:1 z kartą oferty. */
    public function buildTechSpecSections(array $ep, int $post_id = 0): array {
        $translator = new AsiaAuto_Translator();
        $translated = $translator->translateExtraPrep($ep);

        // Map translated categories → our frontend sections
        $section_map = [
            'basic'      => 'podstawowe',
            'dimensions' => 'podstawowe',
            'body'       => 'podstawowe',
            'engine'     => 'silnik',
            'fuel'       => 'zuzycie',
            'gearbox'    => 'skrzynia',
            'suspension' => 'zawieszenie',
            'wheels'     => 'zawieszenie',
            'ev'         => 'elektryczny',
            'drive_modes' => 'skrzynia',
        ];

        $sections = [
            'podstawowe'   => ['title' => 'Dane podstawowe', 'rows' => []],
            'silnik'       => ['title' => 'Silnik', 'rows' => []],
            'elektryczny'  => ['title' => 'Układ elektryczny', 'rows' => []],
            'skrzynia'     => ['title' => 'Skrzynia biegów i napęd', 'rows' => []],
            'zawieszenie'  => ['title' => 'Zawieszenie, hamulce, opony', 'rows' => []],
            'zuzycie'      => ['title' => 'Zużycie i emisje', 'rows' => []],
        ];

        // Keys to skip (irrelevant for Polish importer)
        $skip_keys = [
            'period', 'battery_warranty', 'maintain_cost', 'official_price',
            'environmental_standards', 'fuel_label', 'voice_wake_up_word',
            'sub_brand_name', 'market_time', 'jb',
        ];

        foreach ($translated as $cat_id => $group) {
            $target_section = $section_map[$cat_id] ?? null;
            if (!$target_section || !isset($sections[$target_section])) continue;

            foreach ($group['items'] as $item) {
                if (in_array($item['key'], $skip_keys, true)) continue;
                $sections[$target_section]['rows'][] = [
                    'label' => $item['label'],
                    'value' => $item['value'],
                ];
            }
        }

        // T-226: identyfikacja pojazdu na początku sekcji "Dane podstawowe".
        // Wcześniej sekcja otwierała się rokiem modelowym i od razu wymiarami — brakowało
        // odpowiedzi na pytanie „czym to auto właściwie jest": marki, modelu, wersji i koloru.
        // Dane pochodzą z taksonomii i meta (nie z extra_prep), więc są kompletne dla obu źródeł.
        // Marki i modelu świadomie NIE linkujemy — mają już po ~4 linki na stronie
        // (breadcrumb, „Wszystkie oferty", inne egzemplarze, CTA katalogu).
        if ($post_id > 0) {
            $head = [];

            $tax_row = static function (string $tax, string $label) use ($post_id, &$head): void {
                $terms = get_the_terms($post_id, $tax);
                if ($terms && !is_wp_error($terms)) {
                    $head[] = ['label' => $label, 'value' => $terms[0]->name];
                }
            };

            $tax_row('make',  'Marka');
            $tax_row('serie', 'Model');

            $version = trim((string) get_post_meta($post_id, '_asiaauto_complectation', true));
            if ($version !== '') {
                $head[] = ['label' => 'Wersja', 'value' => $version];
            }

            $tax_row('ca-year',        'Rok modelowy');
            $tax_row('exterior-color', 'Kolor nadwozia');
            $tax_row('interior-color', 'Kolor wnętrza');

            if ($head) {
                array_splice($sections['podstawowe']['rows'], 0, 0, $head);
            }
        }

        // Remove empty sections (e.g. elektryczny for ICE cars)
        return array_filter($sections, fn($s) => !empty($s['rows']));
    }

    private function renderTechSpecsCSS(): void {
        static $done = false;
        if ($done) return;
        $done = true;
        ?>
        <style>
        .aa-tech { display: flex; flex-direction: column; gap: 20px; }
        .aa-tech__section {
            background: #fff;
            border: 1px solid #E1E4E8;
            border-radius: 6px;
            overflow: hidden;
        }
        .aa-tech__title {
            font-family: 'Inter', sans-serif;
            font-size: 16px;
            font-weight: 600;
            color: #1B2A4A;
            padding: 14px 16px;
            margin: 0;
            border-bottom: 1px solid #E1E4E8;
            background: #F8F9FA;
        }
        .aa-tech__table { width: 100%; border-collapse: collapse; }
        .aa-tech__row { border-bottom: 1px solid #F0F1F3; }
        .aa-tech__row:last-child { border-bottom: none; }
        .aa-tech__row--hidden { display: none; }
        .aa-tech__row.aa-tech__row--visible { display: table-row; }
        .aa-tech__label {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: #5C6B7F;
            padding: 10px 16px;
            width: 45%;
            vertical-align: top;
        }
        .aa-tech__value {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 500;
            color: #2D3748;
            padding: 10px 16px;
            vertical-align: top;
        }
        .aa-tech__toggle {
            display: block;
            width: 100%;
            padding: 10px 16px;
            border: none;
            border-top: 1px solid #E1E4E8;
            background: #FAFBFC;
            color: #C92A2B;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            transition: background 0.15s;
        }
        .aa-tech__toggle:hover { background: #F0F1F3; }
        @media (max-width: 767px) {
            .aa-tech__label, .aa-tech__value { padding: 8px 12px; font-size: 12px; }
            .aa-tech__title { font-size: 14px; padding: 12px; }
        }
        </style>
        <?php
    }

    private function renderTechSpecsJS(): void {
        ?>
        <script>
        (function(){
            document.querySelectorAll('.aa-tech__toggle').forEach(function(btn){
                btn.addEventListener('click', function(){
                    var section = btn.closest('.aa-tech__section');
                    var hidden = section.querySelectorAll('.aa-tech__row--hidden');
                    var visible = section.querySelectorAll('.aa-tech__row--visible');
                    if (hidden.length > 0 && visible.length === 0) {
                        hidden.forEach(function(r){ r.classList.add('aa-tech__row--visible'); });
                        btn.textContent = btn.dataset.less;
                    } else {
                        section.querySelectorAll('.aa-tech__row--visible').forEach(function(r){
                            r.classList.remove('aa-tech__row--visible');
                        });
                        btn.textContent = btn.dataset.more;
                    }
                });
            });
        })();
        </script>
        <?php
    }

    // =========================================================================
    // [asiaauto_equipment] — Equipment checklist (collapsible, like Ausstattung)
    // =========================================================================

    /**
     * [asiaauto_equipment] — Equipment features with checkmarks.
     *
     * Reads extra_prep, filters non-empty values that represent features
     * (standard = checkmark, specific value = text).
     * Groups: Bezpieczenstwo, ADAS, Komfort, Multimedia, Swiatla, Lusterka.
     * Initially shows 10 items, "Więcej" expands.
     *
     * @since 0.10.2
     */
    public function renderEquipment(array $atts = []): string {
        $post_id = get_the_ID();
        if (!$post_id) return '';

        $ep = $this->getExtraPrep($post_id);
        if (empty($ep)) return '';

        $translator = new AsiaAuto_Translator();
        $translated = $translator->translateExtraPrep($ep);

        // Map translated categories → our equipment sections
        $equip_cats = ['safety', 'adas', 'comfort', 'seats', 'multimedia', 'lights', 'mirrors', 'parking', 'remote'];

        $section_titles = [
            'safety'     => 'Bezpieczeństwo',
            'adas'       => 'Asystenci jazdy (ADAS)',
            'comfort'    => 'Komfort',
            'seats'      => 'Fotele',
            'multimedia' => 'Multimedia',
            'lights'     => 'Światła',
            'mirrors'    => 'Lusterka i szyby',
            'parking'    => 'Parkowanie i kamery',
            'remote'     => 'Łączność i zdalny dostęp',
        ];

        $sections = [];
        foreach ($equip_cats as $cat) {
            if (!isset($translated[$cat]) || empty($translated[$cat]['items'])) continue;

            $items = [];
            foreach ($translated[$cat]['items'] as $item) {
                // Skip numeric-only values (dimensions, counts — belong in tech specs)
                if (is_numeric($item['value'])) continue;

                $items[] = [
                    'label' => $item['label'],
                    'value' => $item['value'],
                    'is_standard' => ($item['raw'] === 'Tak' || $item['raw'] === '标配' || $item['value'] === 'Tak'),
                ];
            }

            if (!empty($items)) {
                $sections[$cat] = [
                    'title' => $section_titles[$cat] ?? $translated[$cat]['label'],
                    'items' => $items,
                ];
            }
        }

        if (empty($sections)) return '';

        ob_start();
        $this->renderEquipmentCSS();
        ?>
        <div class="aa-equip">
            <?php foreach ($sections as $sec_id => $sec): ?>
                <div class="aa-equip__section" data-section="<?= esc_attr($sec_id) ?>">
                    <h3 class="aa-equip__title"><?= esc_html($sec['title']) ?></h3>
                    <div class="aa-equip__grid">
                        <?php foreach ($sec['items'] as $i => $item): ?>
                            <div class="aa-equip__item<?= $i >= 10 ? ' aa-equip__item--hidden' : '' ?>">
                                <svg class="aa-equip__check" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                    <path d="M20 6L9 17l-5-5" stroke="#38A169" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span class="aa-equip__label"><?= esc_html($item['label']) ?></span>
                                <?php if (!$item['is_standard'] && $item['value'] !== $item['label']): ?>
                                    <span class="aa-equip__detail"><?= esc_html($item['value']) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($sec['items']) > 10): ?>
                        <button class="aa-equip__toggle" data-more="Więcej (<?= count($sec['items']) - 10 ?>)" data-less="Mniej">Więcej (<?= count($sec['items']) - 10 ?>)</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        $this->renderEquipmentJS();
        // T-214 auto-linker: terminy techniczne → hasła /wiki/ (linki tylko w tekście,
        // nagłówki i wnętrza <a> pomijane — patrz AsiaAuto_Autolink).
        return AsiaAuto_Autolink::html(ob_get_clean(), 'equipment', 3);
    }

    private function renderEquipmentCSS(): void {
        static $done = false;
        if ($done) return;
        $done = true;
        ?>
        <style>
        .aa-equip { display: flex; flex-direction: column; gap: 20px; }
        .aa-equip__section {
            background: #fff;
            border: 1px solid #E1E4E8;
            border-radius: 6px;
            overflow: hidden;
        }
        .aa-equip__title {
            font-family: 'Inter', sans-serif;
            font-size: 16px;
            font-weight: 600;
            color: #1B2A4A;
            padding: 14px 16px;
            margin: 0;
            border-bottom: 1px solid #E1E4E8;
            background: #F8F9FA;
        }
        .aa-equip__grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            padding: 4px 0;
        }
        .aa-equip__item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 8px 16px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: #2D3748;
            border-bottom: 1px solid #F5F6F8;
        }
        .aa-equip__item--hidden { display: none; }
        .aa-equip__item.aa-equip__item--visible { display: flex; }
        .aa-equip__check { flex-shrink: 0; margin-top: 1px; }
        .aa-equip__label { font-weight: 500; }
        .aa-equip__detail {
            font-size: 12px;
            color: #5C6B7F;
            margin-left: auto;
            text-align: right;
            white-space: nowrap;
        }
        .aa-equip__toggle {
            display: block;
            width: 100%;
            padding: 10px 16px;
            border: none;
            border-top: 1px solid #E1E4E8;
            background: #FAFBFC;
            color: #C92A2B;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            transition: background 0.15s;
        }
        .aa-equip__toggle:hover { background: #F0F1F3; }
        @media (max-width: 767px) {
            .aa-equip__grid { grid-template-columns: 1fr; }
            .aa-equip__item { padding: 7px 12px; font-size: 12px; }
            .aa-equip__title { font-size: 14px; padding: 12px; }
        }
        </style>
        <?php
    }

    private function renderEquipmentJS(): void {
        ?>
        <script>
        (function(){
            document.querySelectorAll('.aa-equip__toggle').forEach(function(btn){
                btn.addEventListener('click', function(){
                    var section = btn.closest('.aa-equip__section');
                    var hidden = section.querySelectorAll('.aa-equip__item--hidden');
                    var visible = section.querySelectorAll('.aa-equip__item--visible');
                    if (hidden.length > 0 && visible.length === 0) {
                        hidden.forEach(function(r){ r.classList.add('aa-equip__item--visible'); });
                        btn.textContent = btn.dataset.less;
                    } else {
                        section.querySelectorAll('.aa-equip__item--visible').forEach(function(r){
                            r.classList.remove('aa-equip__item--visible');
                        });
                        btn.textContent = btn.dataset.more;
                    }
                });
            });
        })();
        </script>
        <?php
    }

    // =========================================================================
    // [asiaauto_cta] — Call / WhatsApp / Reserve buttons
    // =========================================================================

    /**
     * [asiaauto_cta] — Contact action buttons.
     *
     * - Zarezerwuj → placeholder (future: order flow)
     * - Zadzwoń → tel: link with phone from asiaauto_order_config
     * - WhatsApp → wa.me link with pre-filled message including listing URL
     *
     * @since 0.10.2
     */
    public function renderCTA(array $atts = []): string {
        $post_id = get_the_ID();
        if (!$post_id) return '';

        $config = get_option('asiaauto_order_config', []);
        $phone = $config['company_phone'] ?? '';

        // Clean phone for tel: and wa.me (remove spaces, dashes)
        $phone_clean = preg_replace('/[\s\-\(\)]/', '', $phone);
        $phone_wa = ltrim($phone_clean, '+');

        $listing_url = get_permalink($post_id);
        $listing_title = get_the_title($post_id);
        $wa_text = urlencode("Dzień dobry, interesuję się ofertą: {$listing_title}\n{$listing_url}");

        ob_start();
        $this->renderCTACSS();
        ?>
        <div class="aa-cta">
            <a href="<?= esc_url(home_url('/zamow/?listing_id='.(int)$post_id)) ?>" class="aa-cta__btn aa-cta__btn--reserve">Zamów</a>
            <?php if (!empty($phone_clean)): ?>
                <a href="tel:<?= esc_attr($phone_clean) ?>" class="aa-cta__btn aa-cta__btn--phone">Zadzwoń</a>
                <a href="https://wa.me/<?= esc_attr($phone_wa) ?>?text=<?= esc_attr($wa_text) ?>"
                   target="_blank" rel="noopener"
                   class="aa-cta__btn aa-cta__btn--whatsapp">WhatsApp</a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function renderCTACSS(): void {
        static $done = false;
        if ($done) return;
        $done = true;
        ?>
        <style>
        .aa-cta {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .aa-cta__btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border-radius: 6px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s;
            white-space: nowrap;
            border: none;
        }
        .aa-cta__btn:hover { transform: translateY(-1px); text-decoration: none; }
        .aa-cta__btn--reserve {
            background: #C92A2B;
            color: #FFFFFF;
            flex: 1 1 auto;
        }
        .aa-cta__btn--reserve:hover { background: #B52828; color: #FFFFFF; }
        .aa-cta__btn--phone {
            background: #1B2A4A;
            color: #FFFFFF;
            flex: 1 1 auto;
        }
        .aa-cta__btn--phone:hover { background: #152238; color: #FFFFFF; }
        .aa-cta__btn--whatsapp {
            background: #25D366;
            color: #FFFFFF;
            flex: 1 1 auto;
        }
        .aa-cta__btn--whatsapp:hover { background: #1DA851; color: #FFFFFF; }
        @media (max-width: 767px) {
            .aa-cta { flex-direction: column; }
            .aa-cta__btn { width: 100%; padding: 14px 20px; font-size: 15px; }
        }
        </style>
        <?php
    }

    // =========================================================================
    // [asiaauto_404_listing] — Custom 404 for deleted/sold listings
    // =========================================================================

    /**
     * [asiaauto_404_listing]
     *
     * Drop in Elementor Theme Builder → 404 Page template.
     * Renders: info message + make tiles (→ /samochody/?marka=X) + CTA button.
     *
     * Reads make context from AsiaAuto_Redirects::getCurrentMake() — set by
     * template_redirect when URL matches /oferta/{prefix}-{rok}-{post_id}.
     * Active make tile is highlighted if make was resolved from post_id.
     */
    public function render404Listing(): string {
        $make          = class_exists('AsiaAuto_Redirects') ? AsiaAuto_Redirects::getCurrentMake() : null;
        $inventory_url = home_url('/samochody/');
        $makes         = $this->get404ActiveMakes();

        $this->render404Styles();

        ob_start();
        ?>
        <div class="aa-404">

            <div class="aa-404__icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>

            <h1 class="aa-404__title">To ogłoszenie nie jest już dostępne</h1>
            <p class="aa-404__sub">Pojazd został sprzedany lub oferta wygasła.</p>

            <?php if (!empty($makes)): ?>
                <p class="aa-404__makes-label">Przeglądaj według marki</p>
                <div class="aa-404__makes">
                    <?php foreach ($makes as $term):
                        $url    = esc_url(add_query_arg('marka', $term->slug, $inventory_url));
                        $active = ($make && $make->slug === $term->slug) ? ' aa-404__make--active' : '';
                    ?>
                        <a href="<?= $url ?>" class="aa-404__make<?= $active ?>">
                            <?= esc_html($term->name) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <a href="<?= esc_url($inventory_url) ?>" class="aa-404__cta">
                Najnowsze oferty
            </a>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get makes with at least one published listing, ordered by count DESC.
     *
     * @return WP_Term[]
     */
    private function get404ActiveMakes(): array {
        $terms = get_terms([
            'taxonomy'   => 'make',
            'hide_empty' => true,
            'orderby'    => 'count',
            'order'      => 'DESC',
            'number'     => 0,
        ]);

        return is_wp_error($terms) ? [] : $terms;
    }

    /**
     * Inline styles for [asiaauto_404_listing] — registered once per request.
     */
    private function render404Styles(): void {
        static $done = false;
        if ($done) return;
        $done = true;

        wp_register_style('aa-404', false);
        wp_enqueue_style('aa-404');
        wp_add_inline_style('aa-404', '
.aa-404 {
    text-align: center;
    padding: 40px 20px;
    font-family: Inter, sans-serif;
}
.aa-404__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #FEF2F2;
    color: #C92A2B;
    margin-bottom: 20px;
}
.aa-404__title {
    font-size: 22px;
    font-weight: 700;
    color: #1B2A4A;
    margin: 0 0 8px;
}
.aa-404__sub {
    font-size: 15px;
    color: #5C6B7F;
    margin: 0 0 36px;
}
.aa-404__makes-label {
    font-size: 12px;
    font-weight: 600;
    color: #5C6B7F;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 12px;
}
.aa-404__makes {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 8px;
    margin-bottom: 36px;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
}
.aa-404__make {
    display: inline-block;
    padding: 6px 14px;
    background: #FFFFFF;
    border: 1px solid #E1E4E8;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    color: #1B2A4A;
    text-decoration: none;
    transition: border-color .15s, background .15s;
}
.aa-404__make:hover {
    border-color: #1B2A4A;
    background: #EBF0F7;
    color: #1B2A4A;
    text-decoration: none;
}
.aa-404__make--active {
    border-color: #C92A2B;
    background: #FEF2F2;
    color: #C92A2B;
}
.aa-404__cta {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    background: #C92A2B;
    color: #FFFFFF;
    border-radius: 6px;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
    transition: background .15s;
}
.aa-404__cta:hover {
    background: #B52828;
    color: #FFFFFF;
    text-decoration: none;
}
@media (max-width: 600px) {
    .aa-404__make { font-size: 12px; padding: 5px 10px; }
    .aa-404__cta  { font-size: 14px; padding: 11px 22px; }
}
        ');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  [asiaauto_order_url] — bare URL for Elementor Dynamic Tag
    // ═══════════════════════════════════════════════════════════════════

    public function renderOrderUrl(array $atts = []): string {
        $atts = shortcode_atts(['id' => 0], $atts, 'asiaauto_order_url');
        $id = (int) $atts['id'] ?: get_the_ID();
        if (!$id) return '';

        if (AsiaAuto_Order::listingHasReservation($id)) {
            return '';
        }

        return esc_url(home_url('/zamow/?listing_id=' . $id));
    }

    // ═══════════════════════════════════════════════════════════════════
    //  [asiaauto_order_cta] — Elementor-native button for single listing
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Renders CTA using Elementor's own button markup → inherits Site Settings styles.
     * Drop into Elementor "Shortcode" widget on single listing template.
     *
     * v0.22.1: Stock-type reservations (internal purchase, in_transit/on_lot)
     *          now show active button — customer can still order.
     *          Only customer-type reservations are blocked.
     *          This aligns with inventory card logic in renderCard().
     *
     * @param array $atts  text = button label, id = listing ID (default: current post)
     */
    public function renderOrderCta(array $atts = []): string {
        $atts = shortcode_atts([
            'id'   => 0,
            'text' => 'Zamów ten samochód',
        ], $atts, 'asiaauto_order_cta');

        $id = (int) $atts['id'] ?: get_the_ID();
        if (!$id) return '';

        $reservation = get_post_meta($id, '_asiaauto_reservation_status', true);
        $reservationType = get_post_meta($id, '_asiaauto_reservation_type', true);

        // Stock-type reservation (internal purchase) — customer can still order
        if ($reservation && $reservationType === 'stock') {
            $url = esc_url(home_url('/zamow/?listing_id=' . $id));
            return '<div class="elementor-element elementor-align-justify elementor-widget elementor-widget-button">'
                 . '<a class="elementor-button elementor-button-link elementor-size-sm" href="' . $url . '">'
                 . '<span class="elementor-button-content-wrapper">'
                 . '<span class="elementor-button-text">' . esc_html($atts['text']) . '</span>'
                 . '</span></a></div>';
        }

        // Customer-type reservation — blocked
        if ($reservation) {
            $label = match ($reservation) {
                'reserved'   => 'Zarezerwowane',
                'in_transit' => 'W drodze',
                'on_lot'     => 'Na placu',
                default      => 'Niedostępne',
            };
            return '<div class="elementor-element elementor-align-justify elementor-widget elementor-widget-button">'
                 . '<span class="elementor-button elementor-size-sm" style="opacity:.5;cursor:not-allowed;display:block;text-align:center">'
                 . '<span class="elementor-button-content-wrapper">'
                 . '<span class="elementor-button-text">' . esc_html($label) . '</span>'
                 . '</span></span></div>';
        }

        // No reservation — normal active button
        $url = esc_url(home_url('/zamow/?listing_id=' . $id));

        return '<div class="elementor-element elementor-align-justify elementor-widget elementor-widget-button">'
             . '<a class="elementor-button elementor-button-link elementor-size-sm" href="' . $url . '">'
             . '<span class="elementor-button-content-wrapper">'
             . '<span class="elementor-button-text">' . esc_html($atts['text']) . '</span>'
             . '</span></a></div>';
    }

    // =========================================================================
    // [asiaauto_klient_panel] — panel klienta na /klient/
    //
    // T-204: cała obsługa (zakładki, dane, rejestracja, logowanie) mieszka
    // w AsiaAuto_Account. Shortcode zostaje jako punkt wejścia dla strony,
    // żeby nie trzeba było ruszać treści strony 238607.
    // =========================================================================

    public function renderKlientPanel(array $atts = []): string {
        return AsiaAuto_Account::instance()->render();
    }
}
