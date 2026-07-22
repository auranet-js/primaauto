<?php

defined('ABSPATH') || exit;

/**
 * Auto-linker Leksykonu (T-214): zamienia pierwsze wystąpienie terminu technicznego
 * w treści na link do hasła /wiki/{slug}/.
 *
 * Wpięcie (4 punkty renderu — patrz plan T-214):
 *  1. dane techniczne oferty  — AsiaAuto_Single::techSpecs()
 *  2. wyposażenie oferty      — AsiaAuto_Shortcodes::renderEquipment()
 *  3. opis oferty             — AsiaAuto_Single::description()
 *  4. wiki + FAQ hubów        — AsiaAuto_Brand_Hub::renderWiki()/renderFaq()
 *
 * Zasady bezpieczeństwa dla HTML:
 *  - podmiana WYŁĄCZNIE w węzłach tekstowych (segmenty poza `<...>`), nigdy w atrybutach,
 *  - pomijane wnętrza `a`, `script`, `style`, `textarea`, `option`, `button` i nagłówków h1-h6
 *    (nagłówek niesie frazę SEO strony — nie oddajemy jej linkiem wychodzącym),
 *  - jedno hasło = maksymalnie jeden link na render, twardy limit `$max` linków łącznie,
 *  - na stronie hasła nie linkujemy do samego siebie.
 *
 * Mapa aliasów budowana z meta `_wiki_aliases` opublikowanych haseł, cache w transiencie
 * (unieważniany przy zapisie/usunięciu hasła).
 *
 * @since 0.34.1
 */
class AsiaAuto_Autolink {

    private const TRANSIENT = 'asiaauto_autolink_map';
    private const TTL       = DAY_IN_SECONDS;

    /** Domyślny limit linków na jedno wywołanie filtru. */
    public const MAX_DEFAULT = 4;

    /** Tagi, których wnętrza nie tykamy. */
    private const SKIP_TAGS = ['a', 'script', 'style', 'textarea', 'option', 'button',
                               'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

    /** Sluggi już podlinkowane w bieżącym żądaniu (jedno hasło = jeden link na stronę). */
    private static array $used = [];

    public function __construct() {
        add_action('save_post_asiaauto_wiki', [self::class, 'flush']);
        add_action('deleted_post', [self::class, 'flushIfWiki'], 10, 2);
        // 5. punkt renderu (poza planem T-214, ale najtańszy): treść samych haseł —
        // linkowanie hasło↔hasło buduje wewnętrzną siatkę Leksykonu. Self-link odpada
        // w html() po slugu bieżącego posta.
        add_filter('the_content', [self::class, 'filterWikiContent'], 20);
    }

    public static function filterWikiContent(string $content): string {
        if (!is_singular('asiaauto_wiki') || !in_the_loop() || !is_main_query()) return $content;
        return self::html($content, 'wiki_body', 6);
    }

    public static function flush(): void {
        delete_transient(self::TRANSIENT);
    }

    public static function flushIfWiki($post_id, $post = null): void {
        if ($post && isset($post->post_type) && $post->post_type === 'asiaauto_wiki') self::flush();
    }

    /**
     * Mapa aliasów: lista rekordów ['alias','slug','url','ci'] posortowana malejąco po długości
     * aliasu (najdłuższa fraza wygrywa: „silnik synchroniczny z magnesami trwałymi" przed „PSM").
     */
    public static function map(): array {
        $cached = get_transient(self::TRANSIENT);
        if (is_array($cached)) return $cached;

        $posts = get_posts([
            'post_type'        => 'asiaauto_wiki',
            'post_status'      => 'publish',
            'posts_per_page'   => -1,
            'orderby'          => 'title',
            'order'            => 'ASC',
            'suppress_filters' => false,
        ]);

        $map  = [];
        $seen = [];
        foreach ($posts as $p) {
            $slug = $p->post_name;
            $url  = get_permalink($p);
            if (!$url) continue;

            $aliases = (string) get_post_meta($p->ID, '_wiki_aliases', true);
            foreach (preg_split('#[,/]#u', $aliases) as $alias) {
                $alias = trim((string) $alias);
                if ($alias === '') continue;
                // Zbyt krótkie = ryzyko trafienia w środek innego słowa mimo granic wyrazu.
                if (mb_strlen($alias) < 3) continue;
                // CJK pomijamy: na polskim froncie nie występuje, a granice wyrazu w regexie
                // dla pisma bez spacji zachowują się nieprzewidywalnie.
                if (preg_match('#[\x{4E00}-\x{9FFF}]#u', $alias)) continue;

                $key = mb_strtolower($alias);
                if (isset($seen[$key])) continue;   // pierwszy właściciel aliasu wygrywa
                $seen[$key] = true;

                $map[] = [
                    'alias' => $alias,
                    'slug'  => $slug,
                    'url'   => $url,
                    // Skróty (LFP, V2L, 800V, ADAS) tylko w oryginalnej pisowni — inaczej
                    // złapałyby przypadkowe fragmenty. Frazy opisowe case-insensitive.
                    'ci'    => (bool) preg_match('#[a-ząćęłńóśźż]#u', $alias),
                ];
            }
        }

        usort($map, static fn($a, $b) => mb_strlen($b['alias']) <=> mb_strlen($a['alias']));
        set_transient(self::TRANSIENT, $map, self::TTL);
        return $map;
    }

    /**
     * Linkuje terminy w podanym HTML.
     *
     * @param string $html    fragment HTML (musi być już bezpieczny — nie sanityzujemy tu wejścia)
     * @param string $context etykieta miejsca renderu (do debugowania/filtrów)
     * @param int    $max     limit linków w tym fragmencie
     */
    public static function html(string $html, string $context = '', int $max = self::MAX_DEFAULT): string {
        if ($html === '' || !apply_filters('asiaauto_autolink_enabled', true, $context)) return $html;

        $map = self::map();
        if (!$map) return $html;

        // Na stronie hasła nie linkujemy do samego siebie.
        $self = '';
        if (is_singular('asiaauto_wiki')) {
            $post = get_post();
            $self = $post ? $post->post_name : '';
        }

        $added    = 0;
        $segments = preg_split('#(<[^>]+>)#u', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (!is_array($segments)) return $html;

        $skip = 0;
        foreach ($segments as $i => $seg) {
            if ($seg === '') continue;

            if ($seg[0] === '<') {
                if (preg_match('#^</?([a-zA-Z0-9]+)#', $seg, $m)) {
                    $tag = strtolower($m[1]);
                    if (in_array($tag, self::SKIP_TAGS, true)) {
                        if ($seg[1] === '/')                    $skip = max(0, $skip - 1);
                        elseif (!str_ends_with($seg, '/>'))     $skip++;
                    }
                }
                continue;
            }

            if ($skip > 0 || $added >= $max || trim($seg) === '') continue;

            foreach ($map as $entry) {
                if ($added >= $max) break;
                if ($entry['slug'] === $self) continue;
                if (isset(self::$used[$entry['slug']])) continue;

                $re = '#(?<![\p{L}\p{N}_-])' . preg_quote($entry['alias'], '#')
                    . '(?![\p{L}\p{N}_-])#u' . ($entry['ci'] ? 'i' : '');

                $seg = preg_replace_callback($re, static function ($m) use ($entry) {
                    return '<a href="' . esc_url($entry['url']) . '" class="aa-autolink" '
                         . 'title="' . esc_attr('Słownik: ' . $entry['alias']) . '">'
                         . $m[0] . '</a>';
                }, $seg, 1, $count);

                if ($count) {
                    self::$used[$entry['slug']] = true;
                    $added++;
                }
            }

            $segments[$i] = $seg;
        }

        return implode('', $segments);
    }

    /** Reset licznika „jedno hasło = jeden link" (np. dla testów CLI). */
    public static function reset(): void {
        self::$used = [];
    }
}
