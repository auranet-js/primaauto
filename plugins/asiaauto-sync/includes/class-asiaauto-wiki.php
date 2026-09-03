<?php

defined('ABSPATH') || exit;

/**
 * Leksykon (dział wiedzy, T-214): CPT `asiaauto_wiki` pod hasła technologii
 * chińskiej motoryzacji (PSM, LFP, EREV, 800V, marki audio itd.).
 *
 * URL: /wiki/{haslo}/ · archiwum: /wiki/
 * Treść generowana pipeline'em scripts/kb/ (fazy F1-F2), sekcje dynamiczne
 * ("auta z tą technologią") renderowane z bazy przy wyświetleniu, nie zamrażane.
 *
 * Meta:
 *  - _wiki_aliases    (string, CSV) — odmiany hasła dla auto-linkera ("PSM, silnik synchroniczny")
 *  - _wiki_term_keys  (string, JSON) — klucze extra_prep + wartości, które hasło reprezentuje
 *  - _wiki_category   (string) — bateria|naped|adas|audio|komfort|normy
 *
 * @since 0.34.0
 */
class AsiaAuto_Wiki {

    public const CATEGORIES = [
        'bateria' => 'Bateria i ładowanie',
        'naped'   => 'Napęd',
        'adas'    => 'ADAS i autonomia',
        'audio'   => 'Audio i multimedia',
        'komfort'  => 'Komfort i wnętrze',
        'podwozie' => 'Podwozie i zawieszenie',
        'nadwozie' => 'Nadwozie',
        'normy'    => 'Normy i pomiary',
    ];

    public function __construct() {
        add_action('init', [$this, 'registerPostType'], 5);
        add_action('init', [$this, 'registerMeta'], 5);
    }

    public function registerPostType(): void {
        register_post_type('asiaauto_wiki', [
            'labels' => [
                'name'          => 'Słownik',
                'singular_name' => 'Hasło słownika',
                'add_new'       => 'Dodaj hasło',
                'add_new_item'  => 'Dodaj hasło leksykonu',
                'edit_item'     => 'Edytuj hasło',
                'search_items'  => 'Szukaj haseł',
                'not_found'     => 'Brak haseł',
            ],
            'public'        => true,
            'has_archive'   => 'wiki',
            'rewrite'       => ['slug' => 'wiki', 'with_front' => false],
            'menu_icon'     => 'dashicons-book-alt',
            'menu_position' => 27,
            'supports'      => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions'],
            'show_in_rest'  => true,
        ]);
    }

    public function registerMeta(): void {
        foreach (['_wiki_aliases', '_wiki_term_keys', '_wiki_category'] as $key) {
            register_post_meta('asiaauto_wiki', $key, [
                'type'          => 'string',
                'single'        => true,
                'show_in_rest'  => false,
                'auth_callback' => static fn() => current_user_can('edit_posts'),
            ]);
        }
    }
}
