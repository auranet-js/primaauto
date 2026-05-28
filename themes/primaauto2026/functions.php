<?php
defined('ABSPATH') || exit;

const PRIMAAUTO_THEME_VERSION = '1.0.7';

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
    wp_enqueue_script('primaauto-nav',   "$base/js/nav.js", [], PRIMAAUTO_THEME_VERSION, ['strategy' => 'defer', 'in_footer' => true]);
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
