<?php

defined('ABSPATH') || exit;

/**
 * Linkowanie dwukierunkowe Leksykonu (T-214): pod hasłem /wiki/{haslo}/ sekcja
 * „W ofercie: N aut z technologią X" — licznik + kilka kart ofert.
 *
 * Dopasowanie: meta `_wiki_term_keys` (JSON {klucz: wartość}) → `_asiaauto_extra_prep` ofert.
 *
 * DLACZEGO INDEKS, A NIE `LIKE` W ZAPYTANIU:
 * `_asiaauto_extra_prep` to ~36 MB JSON-a bez indeksu na treści, a naiwny wzorzec
 * `meta_value LIKE '%"klucz":"%wartość%'` przecieka poza pole — `"car_refrigerator":""`
 * z `"air_suspension":"标配"` dalej w tym samym rekordzie łapie się jako „ma lodówkę".
 * Pierwsza wersja tej klasy tak właśnie liczyła i dawała identyczne 2811 dla lodówki,
 * klamek i zawieszenia pneumatycznego. Dlatego: jeden przelot po ofertach, json_decode,
 * porównanie wartości pole-po-polu, wynik do opcji (mapa slug → count + ids).
 *
 * Wartości chińskie: importer w części partii zapisał je jako "\uXXXX", w części jako
 * "uXXXX" (WP zjadł backslashe), w części literalnie. `decodeValue()` sprowadza do UTF-8.
 * Semantyka: 标配 (standard) / 选配 (opcja) / 支持 (obsługiwane) = auto MA funkcję;
 * "" / 无 / 否 / 不支持 / - = NIE ma (i takich ofert nie liczymy).
 *
 * Odświeżanie: cron `asiaauto_wiki_cars_reindex` co 6 h + lazy rebuild gdy indeks
 * starszy niż 12 h. Inwentarz zmienia się codziennie, więc treść nie może być wmurowana
 * w post — ale nie może też skanować 36 MB przy każdym odsłonie.
 *
 * @since 0.34.1
 */
class AsiaAuto_Wiki_Cars {

    private const OPT_INDEX = 'asiaauto_wiki_cars_index';
    private const OPT_TIME  = 'asiaauto_wiki_cars_index_time';
    private const CRON_HOOK = 'asiaauto_wiki_cars_reindex';
    private const STALE     = 12 * HOUR_IN_SECONDS;
    private const CARDS     = 4;
    private const KEEP_IDS  = 8;   // ile ID ofert trzymamy w indeksie na hasło

    /** Wartości oznaczające „auto NIE ma tej funkcji". */
    private const NEGATIVE = ['', '-', '—', '无', '否', '不支持', '不配备', '无此配置'];

    public function __construct() {
        add_shortcode('asiaauto_wiki_cars', [$this, 'render']);
        add_action(self::CRON_HOOK, [self::class, 'buildIndex']);
        add_action('init', [self::class, 'scheduleCron']);
    }

    public static function scheduleCron(): void {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 600, 'twicedaily', self::CRON_HOOK);
        }
    }

    /**
     * Jeden przelot po ofertach → mapa `slug hasła` => ['count'=>int, 'ids'=>int[]].
     * Batche po 300 ofert, żeby nie trzymać 36 MB JSON-a w pamięci naraz.
     */
    public static function buildIndex(): array {
        global $wpdb;

        // Definicje haseł: slug => [klucz => szukana wartość (''=wystarczy obecność)]
        $terms = [];
        foreach (get_posts([
            'post_type' => 'asiaauto_wiki', 'post_status' => 'publish', 'posts_per_page' => -1,
        ]) as $p) {
            $keys = json_decode((string) get_post_meta($p->ID, '_wiki_term_keys', true), true);
            if (is_array($keys) && $keys) $terms[$p->post_name] = $keys;
        }
        if (!$terms) return [];

        $index = [];
        foreach (array_keys($terms) as $slug) $index[$slug] = ['count' => 0, 'ids' => []];

        $offset = 0;
        do {
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT p.ID, m.meta_value
                   FROM {$wpdb->posts} p
                   JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_asiaauto_extra_prep'
                   JOIN {$wpdb->postmeta} pr ON pr.post_id = p.ID AND pr.meta_key = 'price' AND pr.meta_value+0 > 0
                  WHERE p.post_type = 'listings' AND p.post_status = 'publish'
                  ORDER BY p.post_date DESC
                  LIMIT %d OFFSET %d", 300, $offset
            ));
            foreach ($rows as $row) {
                $ep = json_decode($row->meta_value, true);
                if (!is_array($ep)) continue;

                foreach ($terms as $slug => $keys) {
                    if (!self::listingMatches($ep, $keys)) continue;
                    $index[$slug]['count']++;
                    if (count($index[$slug]['ids']) < self::KEEP_IDS) {
                        $index[$slug]['ids'][] = (int) $row->ID;
                    }
                }
            }
            $offset += 300;
        } while (count($rows) === 300);

        update_option(self::OPT_INDEX, $index, false);
        update_option(self::OPT_TIME, time(), false);
        return $index;
    }

    /** Czy oferta spełnia którykolwiek z warunków hasła (OR po kluczach). */
    private static function listingMatches(array $ep, array $keys): bool {
        foreach ($keys as $key => $want) {
            if (!isset($ep[$key]) || !is_string($ep[$key])) continue;
            $have = self::decodeValue($ep[$key]);
            if (in_array($have, self::NEGATIVE, true)) continue;

            if (!is_string($want) || $want === '') return true;      // wystarczy obecność
            if (mb_strpos($have, self::decodeValue($want)) !== false) return true;
        }
        return false;
    }

    /**
     * Sprowadza wartość do UTF-8 niezależnie od tego, w której z trzech historycznych
     * form zapisał ją importer: "磷酸铁锂", "磷酸..." albo "u78f7u9178..."
     * (ta ostatnia po zjedzeniu backslashy przez WP).
     */
    private static function decodeValue(string $v): string {
        $v = trim($v);
        if ($v === '' || !preg_match('#u[0-9a-f]{4}#i', $v)) return $v;

        $json = preg_replace('#(?<!\\\\)u([0-9a-f]{4})#i', '\\\\u$1', $v);
        $out  = json_decode('"' . str_replace('"', '\"', $json) . '"');
        return is_string($out) ? $out : $v;
    }

    /** @return array{count:int, ids:int[]} */
    public static function match(string $slug, int $cards = self::CARDS): array {
        $index = get_option(self::OPT_INDEX);
        $age   = time() - (int) get_option(self::OPT_TIME, 0);

        if (!is_array($index) || $age > self::STALE) {
            $index = self::buildIndex();
        }
        $hit = $index[$slug] ?? ['count' => 0, 'ids' => []];
        return ['count' => (int) $hit['count'], 'ids' => array_slice($hit['ids'], 0, $cards)];
    }

    public function render(array $atts = []): string {
        $atts = shortcode_atts(['cards' => self::CARDS], $atts, 'asiaauto_wiki_cars');
        $post_id = (int) get_the_ID();
        if (!$post_id || get_post_type($post_id) !== 'asiaauto_wiki') return '';

        $cards = max(1, min(8, (int) $atts['cards']));
        $data  = self::match(get_post_field('post_name', $post_id), $cards);

        // Zero dopasowań = sekcji nie ma wcale. „0 aut" na stronie hasła to komunikat,
        // który tylko obniża zaufanie — hasło ma wartość informacyjną także bez oferty.
        if ($data['count'] === 0 || !$data['ids']) return '';

        $headword = (string) get_post_meta($post_id, '_wiki_headword', true);
        if ($headword === '') $headword = get_the_title($post_id);

        $n = $data['count'];
        $heading = sprintf('W ofercie: %d %s z technologią %s',
            $n, self::plural($n, 'auto', 'auta', 'aut'), $headword);

        ob_start();
        ?>
        <section class="aa-hub__latest-wrap pa-kb__cars">
            <div class="aa-home__section-header">
                <h2 class="aa-home__section-title"><?php echo esc_html($heading); ?></h2>
                <a href="<?php echo esc_url(home_url('/samochody/')); ?>" class="aa-home__section-link">Wszystkie auta →</a>
            </div>
            <div class="aa-home__latest">
                <?php foreach ($data['ids'] as $pid):
                    if (get_post_status($pid) !== 'publish') continue;   // indeks mógł się zestarzeć
                    $thumb_id = (int) get_post_thumbnail_id($pid);
                    $img = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'asiaauto-card') : '';
                    if (!$img && $thumb_id) $img = wp_get_attachment_image_url($thumb_id, 'medium');
                    $price   = (int) get_post_meta($pid, 'price', true);
                    $mileage = (int) get_post_meta($pid, 'mileage', true);

                    $year = '';
                    $yt = get_the_terms($pid, 'ca-year');
                    if (is_array($yt) && $yt) $year = $yt[0]->name;

                    $fuel_short = '';
                    $ft = get_the_terms($pid, 'fuel');
                    if (is_array($ft) && $ft) {
                        $fn = $ft[0]->name;
                        $fuel_short = match(true) {
                            str_contains($fn, 'PHEV') => 'PHEV',
                            str_contains($fn, 'EREV') => 'EREV',
                            str_contains($fn, 'EV')   => 'EV',
                            default                    => $fn,
                        };
                    }
                    $specs = implode(' · ', array_filter([
                        $fuel_short, $year,
                        $mileage > 0 ? number_format($mileage, 0, ',', ' ') . ' km' : '',
                    ]));
                ?>
                <a class="aa-home__car" href="<?php echo esc_url(get_permalink($pid)); ?>">
                    <div class="aa-home__car-img">
                        <?php if ($img): ?>
                            <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr(get_the_title($pid)); ?>" width="350" height="250" loading="lazy" decoding="async" />
                        <?php else: ?>
                            <div class="aa-home__car-noimg">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" stroke="#CBD5E0" stroke-width="1.5"/><circle cx="8.5" cy="8.5" r="1.5" stroke="#CBD5E0" stroke-width="1.5"/><path d="m21 15-5-5L5 21" stroke="#CBD5E0" stroke-width="1.5" stroke-linecap="round"/></svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="aa-home__car-body">
                        <div class="aa-home__car-title"><?php echo esc_html(get_the_title($pid)); ?></div>
                        <div class="aa-home__car-price"><?php echo number_format($price, 0, ',', ' '); ?> PLN</div>
                        <?php if ($specs !== ''): ?>
                            <div class="aa-home__car-specs"><?php echo esc_html($specs); ?></div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /** Polska liczba mnoga: 1 auto / 2-4 auta / 5+ aut. */
    private static function plural(int $n, string $one, string $few, string $many): string {
        if ($n === 1) return $one;
        $mod10  = $n % 10;
        $mod100 = $n % 100;
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) return $few;
        return $many;
    }
}
