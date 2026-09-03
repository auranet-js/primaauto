<?php
defined('ABSPATH') || exit;

const PRIMAAUTO_THEME_VERSION = '1.3.5';

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('responsive-embeds');
    add_theme_support('custom-logo', [
        'height'      => 60,
        'width'       => 240,
        'flex-height' => true,
        'flex-width'  => true,
    ]);

    // Slugi menu-1/menu-2 zgodne z Hello Elementor — istniejące menu WP
    // (term 6033 "Header" podpięty do menu-1) automatycznie zaczyna działać po switchu.
    register_nav_menus([
        'menu-1' => 'Nagłówek',
        'menu-2' => 'Stopka',
    ]);
});

add_action('wp_enqueue_scripts', function () {
    $base = get_template_directory_uri() . '/assets';
    wp_enqueue_style('primaauto-base',   "$base/css/base.css",   [], PRIMAAUTO_THEME_VERSION);
    wp_enqueue_style('primaauto-header', "$base/css/header.css", ['primaauto-base'], PRIMAAUTO_THEME_VERSION);
    wp_enqueue_style('primaauto-footer', "$base/css/footer.css", ['primaauto-base'], PRIMAAUTO_THEME_VERSION);
    wp_enqueue_style('primaauto-hub',    "$base/css/hub.css",    ['primaauto-base'], PRIMAAUTO_THEME_VERSION);
    // Dział wiedzy (T-214): artykuły, kategorie, Leksykon
    if (is_singular(['post', 'asiaauto_wiki']) || is_category() || is_post_type_archive('asiaauto_wiki') || is_home()) {
        wp_enqueue_style('primaauto-kb', "$base/css/kb.css", ['primaauto-base'], PRIMAAUTO_THEME_VERSION);
    }
    wp_enqueue_script('primaauto-nav',   "$base/js/nav.js", [], PRIMAAUTO_THEME_VERSION, ['strategy' => 'defer', 'in_footer' => true]);
    // Dostepnosc 2.4.11 (karta N-6): petla fokusu w banerze zgod — patrz naglowek pliku.
    wp_enqueue_script('primaauto-a11y-consent', "$base/js/a11y-consent-focus.js", [], PRIMAAUTO_THEME_VERSION, ['strategy' => 'defer', 'in_footer' => true]);
    wp_enqueue_script('primaauto-a11y-scroll', "$base/js/a11y-scroll-regions.js", [], PRIMAAUTO_THEME_VERSION, ['strategy' => 'defer', 'in_footer' => true]);
});

/**
 * Wyłączamy Elementor Pro Theme Builder location rendering.
 * Plugin pozostaje aktywny (renderuje stare strony 93629/93720/153875/153877/186946
 * jeśli ktoś wejdzie w admin Elementora), ale na froncie Theme Builder nie wstawia
 * już własnych header/footer/single — robi to nasz motyw.
 *
 * Po 7 dniach stabilności i finalnym `wp plugin deactivate elementor-pro elementor`
 * ten filter staje się no-op i można go usunąć.
 */
add_filter('elementor/theme/get_location_templates', '__return_empty_array');

/**
 * Preload Inter font (variant 400) — zmniejsza FOUT, poprawia LCP.
 */
add_action('wp_head', function () {
    $url = get_template_directory_uri() . '/fonts/Inter-Regular.woff2';
    echo '<link rel="preload" href="' . esc_url($url) . '" as="font" type="font/woff2" crossorigin>' . "\n";
}, 1);

/**
 * Dla dwupoziomowego URL /samochody/<make>/<serie>/ wymuszamy taxonomy-serie.php.
 * Rewrite rule w pluginie ustawia oba query vars — standardowa hierarchia WP
 * wybrałaby taxonomy-make.php (bo `make` jest primary), tu ją nadpisujemy.
 * (Przeniesione z themes/asiaauto/functions.php — to jest plugin-territory rewrite.)
 */
add_filter('template_include', function ($template) {
    $make  = get_query_var('make');
    $serie = get_query_var('serie');
    if ($make && $serie) {
        $t = locate_template(['taxonomy-serie.php']);
        if ($t) return $t;
    }
    return $template;
}, 99);

/**
 * Polski tytuł strony 404 (WCAG 2.2 — audyt 2026-07-31, punkt AA8).
 * Domyślny "Page Not Found" jest po angielsku przy lang="pl-PL".
 * Filtr RankMath dla instalacji z wtyczką + fallback na rdzeń WP.
 */
add_filter('rank_math/frontend/title', function ($title) {
    return is_404() ? 'Nie znaleziono strony - Prima-Auto - Import Samochodów z Chin' : $title;
}, 20);

add_filter('document_title_parts', function ($parts) {
    if (is_404()) {
        $parts['title'] = 'Nie znaleziono strony';
    }
    return $parts;
}, 20);

/**
 * Weryfikacja domeny w Meta Business (portfolio Prima-Auto) — 2026-08-28.
 * Kod z Ustawienia firmy → Bezpieczeństwo marki → Domeny. Bez niego Meta nie
 * przypisuje konwersji z iOS do reklam (Aggregated Event Measurement).
 * RankMath w tej wersji nie ma pola Facebook — stąd wpis w wp_head.
 */
add_action('wp_head', function () {
    echo '<meta name="facebook-domain-verification" content="945aj5ebvux0ph1vonne9nfuz8pb6t" />' . "\n";
}, 1);
