<?php
/**
 * [asiaauto_single] — single listing page, self-contained.
 *
 * Design: mobile.de — flat table "Dane techniczne" + "Pokaż więcej",
 * sidebar with title/price/CTA.
 *
 * Translations: AsiaAuto_Translator::translateExtraPrep() — proven pipeline.
 *
 * @since 0.11.0
 * @modified 0.25.0 — resilient JSON parsing for broken extra_prep (wp_unslash bug)
 * @package AsiaAuto
 */
defined('ABSPATH') || exit;

class AsiaAuto_Single {

    private const FUEL_SHORT = [
        'electric'=>'EV','erev'=>'EREV','phev'=>'PHEV',
        'petrol'=>'Benzyna','diesel'=>'Diesel','hybrid'=>'HEV',
    ];
    private const TECH_VISIBLE_ROWS = 12;

    public function __construct() {
        add_shortcode('asiaauto_single', [$this, 'render']);
        add_filter('document_title_parts', [$this, 'filterTitle']);
        add_action('wp_head', [$this, 'renderMeta'], 1);
        $this->initRankMathSuppression();
    }

    /**
     * RankMath Pro generuje śmieciowy desc dla CPT listings (auto-extract z post_content
     * łapie chińskie znaki z importu Dongchedi). Tłumimy emisję RM tylko dla single listings,
     * żeby zostawić własną bogatą desc/og: emitowaną w renderMeta() z marką/ceną/USP.
     * Schema, dataLayer, hub-y, /samochody/, /marki/, /informacje/ — RM dalej rządzi.
     */
    private function initRankMathSuppression(): void {
        $suppress_str = function ($value) {
            return is_singular('listings') ? '' : $value;
        };
        $suppress_arr = function ($value) {
            return is_singular('listings') ? [] : $value;
        };
        add_filter('rank_math/frontend/title',           $suppress_str, 99);
        add_filter('rank_math/frontend/description',     $suppress_str, 99);
        add_filter('rank_math/frontend/robots',          $suppress_arr, 99);
        add_filter('rank_math/opengraph/facebook/og_title',       $suppress_str, 99);
        add_filter('rank_math/opengraph/facebook/og_description', $suppress_str, 99);
        add_filter('rank_math/opengraph/facebook/og_type',        $suppress_str, 99);
        add_filter('rank_math/opengraph/facebook/og_image',       $suppress_str, 99);
        add_filter('rank_math/opengraph/twitter/twitter_title',       $suppress_str, 99);
        add_filter('rank_math/opengraph/twitter/twitter_description', $suppress_str, 99);
        add_filter('rank_math/opengraph/twitter/twitter_image',       $suppress_str, 99);
        add_filter('rank_math/opengraph/twitter/card_type',           $suppress_str, 99);
    }

    public function render(array $atts = []): string {
        $post_id = get_the_ID();
        if (!$post_id || get_post_type($post_id) !== 'listings') return '';

        $pu = plugin_dir_url(dirname(__FILE__));
        $pp = plugin_dir_path(dirname(__FILE__));
        $css_path = $pp.'assets/css/asiaauto-single.css';
        $js_path  = $pp.'assets/js/asiaauto-single.js';
        $css_ver = file_exists($css_path) ? (string) filemtime($css_path) : ASIAAUTO_VERSION;
        $js_ver  = file_exists($js_path)  ? (string) filemtime($js_path)  : ASIAAUTO_VERSION;
        wp_enqueue_style('aa-single', $pu.'assets/css/asiaauto-single.css', [], $css_ver);
        wp_enqueue_script('aa-single', $pu.'assets/js/asiaauto-single.js', [], $js_ver, true);

        $d = $this->gather($post_id);
        ob_start();
        // Schema Car + BreadcrumbList renderowane przez wp_head action (renderMeta).
        // Tu nie emitujemy żeby uniknąć duplikatu @type=Car.
        ?>
        <div class="aa-single">

            <a href="<?= esc_url(home_url('/samochody/')) ?>" class="aa-single__back">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Wróć do wyników
            </a>

            <?= do_shortcode('[aa_breadcrumb]') ?>

            <?= $this->stickyHead($d, '--mobile') ?>

            <div class="aa-single__layout">
                <div class="aa-single__main">
                    <?= do_shortcode('[asiaauto_gallery]') ?>
                    <?= $this->keySpecs($d) ?>
                    <?= $this->uspStrip() ?>
                    <?= $this->description($d) ?>
                    <?= do_shortcode('[asiaauto_tech_specs]') ?>
                    <?= do_shortcode('[asiaauto_equipment]') ?>
                    <?= $this->relatedModels($d) ?>
                    <?= $this->infoBox('aa-info--mobile') ?>
                </div>
                <aside class="aa-single__sidebar">
                    <?= $this->sidebar($d) ?>
                </aside>
            </div>
            <?= $this->mobileCta($d) ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function gather(int $pid): array {
        $ep_raw = get_post_meta($pid, '_asiaauto_extra_prep', true);
        $ep = [];
        if (!empty($ep_raw)) {
            if (is_array($ep_raw)) {
                $ep = $ep_raw;
            } elseif (is_string($ep_raw)) {
                $ep = json_decode($ep_raw, true);
                // v0.25.0 — fallback for broken JSON (wp_unslash stripped escape chars)
                if (!is_array($ep)) {
                    $ep = self::fixBrokenFlatJson($ep_raw);
                }
            }
        }
        $tg = [];
        if (!empty($ep)) {
            $tr = new AsiaAuto_Translator();
            $tg = $tr->translateExtraPrep($ep);
        }
        $tx = fn($t) => (($tt = get_the_terms($pid,$t)) && !is_wp_error($tt)) ? $tt[0] : null;
        $fuel = $tx('fuel'); $cond = $tx('condition');
        $cfg = get_option('asiaauto_order_config', []);

        return [
            'pid'=>$pid, 'title'=>get_the_title($pid), 'url'=>get_permalink($pid),
            'desc'=>get_the_content(null,false,$pid), 'ep'=>$ep, 'tg'=>$tg,
            'make'=>($tx('make'))->name??'', 'model'=>($tx('serie'))->name??'',
            'fuel_slug'=>$fuel->slug??'', 'fuel_name'=>$fuel->name??'',
            'trans'=>($tx('transmission'))->name??'', 'year'=>($tx('ca-year'))->name??'',
            'reg_date'=>(string)get_post_meta($pid,'registration_date',true),
            'body'=>($tx('body'))->name??'', 'drive'=>($tx('drive'))->name??'',
            'color'=>($tx('exterior-color'))->name??'', 'cond'=>$cond->name??'',
            'price'=>get_post_meta($pid,'price',true),
            'mileage'=>get_post_meta($pid,'mileage',true),
            'hp'=>get_post_meta($pid,'_asiaauto_horse_power',true),
            'owners'=>get_post_meta($pid,'_asiaauto_owners_count',true),
            'reservation'=>get_post_meta($pid,'_asiaauto_reservation_status',true),
            'phone'=>$cfg['company_phone']??'',
            'pdate'=>get_post_field('post_date',$pid),
            'thumb'=>get_the_post_thumbnail_url($pid,'full'),
        ];
    }

    /**
     * Parse broken flat JSON object using state machine.
     *
     * When wp_json_encode() produces {"key":"value with \"quotes\""},
     * WordPress wp_unslash() strips backslashes → {"key":"value with "quotes""}
     * which is invalid JSON. json_decode() returns null.
     *
     * This method detects structural vs non-structural quotes by looking
     * at what follows each ": comma/brace = end of value, anything else
     * = broken quote inside value. Works reliably for flat JSON objects
     * (no nested objects/arrays — which is the case for extra_prep).
     *
     * @param string $raw  Broken JSON string
     * @return array       Parsed key-value pairs, or empty on failure
     * @since 0.25.0
     */
    private static function fixBrokenFlatJson(string $raw): array {
        $raw = trim($raw);
        if (empty($raw) || $raw[0] !== '{') {
            return [];
        }

        // Remove outer braces
        $inner = substr($raw, 1, -1);
        $len   = strlen($inner);
        $i     = 0;

        $result = [];
        $key    = '';
        $value  = '';
        // States: KEY_START, IN_KEY, COLON, VAL_START, IN_VALUE
        $state  = 'KEY_START';

        while ($i < $len) {
            $c = $inner[$i];

            switch ($state) {
                case 'KEY_START':
                    if ($c === '"') {
                        $key   = '';
                        $state = 'IN_KEY';
                    }
                    break;

                case 'IN_KEY':
                    if ($c === '"') {
                        $state = 'COLON';
                    } else {
                        $key .= $c;
                    }
                    break;

                case 'COLON':
                    if ($c === ':') {
                        $state = 'VAL_START';
                    }
                    break;

                case 'VAL_START':
                    if ($c === '"') {
                        $value = '';
                        $state = 'IN_VALUE';
                    }
                    break;

                case 'IN_VALUE':
                    if ($c === '"') {
                        // Is this the structural end-of-value quote?
                        // Check next non-space char: , or end-of-string = yes
                        $j = $i + 1;
                        while ($j < $len && $inner[$j] === ' ') {
                            $j++;
                        }
                        if ($j >= $len || $inner[$j] === ',') {
                            // End of value
                            $result[$key] = $value;
                            $state = 'KEY_START';
                        } else {
                            // Broken quote inside value — keep literal
                            $value .= $c;
                        }
                    } else {
                        $value .= $c;
                    }
                    break;
            }

            $i++;
        }

        // Catch last value if loop ended in IN_VALUE state
        if ($state === 'IN_VALUE' && !empty($key)) {
            $result[$key] = $value;
        }

        if (!empty($result)) {
            AsiaAuto_Logger::info("fixBrokenFlatJson: recovered " . count($result) . " keys from broken JSON (post may need re-save)");
        }

        return $result;
    }

    // === Key Specs ===
    private function keySpecs(array $d): string {
        $s = [];
        if (!empty($d['mileage']) && is_numeric($d['mileage']))
            $s[] = ['i'=>'mileage','v'=>number_format((int)$d['mileage'],0,',',' ').' km','l'=>'Przebieg'];
        $pw = $this->power($d['ep']);
        if ($pw['display']) $s[] = ['i'=>'power','v'=>$pw['display'],'l'=>$pw['label'] ?: 'Moc'];
        if ($d['fuel_slug']) $s[] = ['i'=>'fuel','v'=>self::FUEL_SHORT[$d['fuel_slug']]??$d['fuel_name'],'l'=>'Paliwo'];
        if ($d['trans']) $s[] = ['i'=>'trans','v'=>$d['trans'],'l'=>'Skrzynia'];
        if (!empty($d['reg_date']) && preg_match('#^\d{2}/(\d{2})/(\d{4})$#', $d['reg_date'], $m))
            $s[] = ['i'=>'year','v'=>"{$m[1]}/{$m[2]}",'l'=>'Rejestracja'];
        if (!empty($d['owners']) && is_numeric($d['owners']))
            $s[] = ['i'=>'owners','v'=>(int)$d['owners'],'l'=>'Właściciele'];
        if (!$s) return '';
        $h = '<div class="aa-ks">';
        foreach ($s as $x)
            $h .= '<div class="aa-ks__item"><span class="aa-ks__icon">'.$this->icon($x['i']).'</span><span class="aa-ks__val">'.esc_html($x['v']).'</span><span class="aa-ks__lbl">'.esc_html($x['l']).'</span></div>';
        return $h.'</div>';
    }

    private function power(array $ep, ?int $post_id = null): array {
        $pid = $post_id ?: (int) get_the_ID();
        if ($pid <= 0) {
            return ['kw'=>null,'km'=>null,'display'=>'','label'=>''];
        }
        return AsiaAuto_Inventory::resolvePower($pid, $ep);
    }

    // === Tech Specs — flat table, "Pokaż więcej" ===
    private function techSpecs(array $d): string {
        if (empty($d['tg'])) return '';
        $rows = [];
        foreach ($d['tg'] as $g) foreach ($g['items'] as $it) $rows[] = $it;
        if (!$rows) return '';

        $vis = self::TECH_VISIBLE_ROWS;
        $tot = count($rows);
        $more = $tot > $vis;

        $h = '<div class="aa-single__tech"><h2 class="aa-section-title">Dane techniczne</h2><div class="aa-card">';
        $h .= '<table class="aa-spec-table"><tbody>';
        foreach ($rows as $i => $r) {
            $cls = ($more && $i >= $vis) ? ' aa-spec-row--hidden' : '';
            $h .= '<tr class="aa-spec-row'.$cls.'"><td class="aa-spec-label">'.esc_html($r['label']).'</td><td class="aa-spec-value">'.esc_html($r['value']).'</td></tr>';
        }
        $h .= '</tbody></table>';
        if ($more)
            $h .= '<button class="aa-show-more" type="button" data-hidden-class="aa-spec-row--hidden"><span class="aa-show-more__on">Pokaż więcej ('.($tot-$vis).')</span><span class="aa-show-more__off">Pokaż mniej</span></button>';
        $h .= '</div></div>';
        return $h;
    }

    // === Description ===
    private function description(array $d): string {
        $c = $d['desc'];
        if (empty(trim(strip_tags($c)))) return '';
        return '<div class="aa-single__desc"><h2 class="aa-section-title">Opis pojazdu</h2><div class="aa-card aa-desc-content">'.wp_kses_post($c).'</div></div>';
    }

    // === Sidebar ===
    private function sidebar(array $d): string {
        $h  = $this->stickyHead($d, '--desktop');
        $h .= '<hr class="aa-sep aa-sep--desktop"/>';

        // Info — desktop only (mobile renders this in main flow at bottom)
        $h .= $this->infoBox('aa-info--desktop');

        $h .= '<hr class="aa-sep aa-sep--desktop"/>';

        $h .= $this->cta($d);

        $h .= '<div class="aa-single__updated aa-single__updated--desktop">Zaktualizowano: '.do_shortcode('[asiaauto_updated]').'</div>';
        return $h;
    }

    // === Sticky head — title + brutto/netto, renderowane dwa razy: ===
    //  --desktop wewnątrz sidebar (inline w aside, sticky razem z aside)
    //  --mobile  poza layout (sticky pod headerem strony, top: var(--header-h))
    private function stickyHead(array $d, string $variant = ''): string {
        $badges = [];
        $res = $d['reservation'];
        if ($res === 'reserved')       $badges[] = ['t'=>'Zarezerwowane','c'=>'aa-badge--reserved'];
        elseif ($res === 'in_transit') $badges[] = ['t'=>'W drodze','c'=>'aa-badge--transit'];
        elseif ($res === 'on_lot')     $badges[] = ['t'=>'Na placu — Rzeszów','c'=>'aa-badge--onlot'];

        $cn = mb_strtolower($d['cond']);
        if (str_contains($cn,'now')) $badges[] = ['t'=>'Nowy','c'=>'aa-badge--new'];

        $fn = mb_strtolower($d['fuel_name']);
        if (str_contains($fn,'elektr'))                                   $badges[] = ['t'=>'Elektryczny','c'=>'aa-badge--ev'];
        elseif (str_contains($fn,'hybr') || str_contains($fn,'erev'))     $badges[] = ['t'=>'Hybryda','c'=>'aa-badge--hybrid'];

        $cls = 'aa-single__sticky-head';
        if ($variant !== '') $cls .= ' aa-single__sticky-head'.$variant;

        // Mobile variant ma własną kopię tytułu jako h1; desktop variant też (sidebar).
        // Każda kopia ma display:none na nieaktywnym viewporcie — Google indekser widzi obie
        // jako equivalent w responsive design, nie penalizuje.
        $h  = '<div class="'.esc_attr($cls).'">';

        // Mobile-only back arrow (left grid column) — visible only when variant is --mobile
        $h .= '<a href="'.esc_url(home_url('/samochody/')).'" class="aa-single__sticky-back" aria-label="Wróć do wyników">';
        $h .= '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        $h .= '</a>';

        if ($badges) {
            $h .= '<div class="aa-badges">';
            foreach ($badges as $b) $h .= '<span class="aa-badge '.$b['c'].'">'.esc_html($b['t']).'</span>';
            $h .= '</div>';
        }

        $h .= '<h1 class="aa-single__title">'.esc_html($d['title']).'</h1>';

        $rel = $this->relTime($d['pdate']);
        if ($rel) $h .= '<span class="aa-single__added">'.esc_html($rel).'</span>';

        $h .= '<div class="aa-single__price-box">';
        if (!empty($d['price']) && is_numeric($d['price'])) {
            $brutto = (float) $d['price'];
            $h .= '<span class="aa-single__price">'.number_format($brutto, 0, ',', ' ').' PLN</span>';
            $h .= '<span class="aa-single__price-netto">netto: '.number_format($brutto / 1.23, 0, ',', ' ').' PLN</span>';
        } else {
            $h .= '<span class="aa-single__price">Cena na zapytanie</span>';
        }
        $h .= '</div>';

        $h .= '</div>';
        return $h;
    }

    // Reusable info box (rendered twice: desktop sidebar + mobile bottom of main)
    private function infoBox(string $extra_class = ''): string {
        $info_base = home_url('/informacje/');
        $info_links = [
            ['Proces zamawiania',         $info_base.'proces-zamawiania/'],
            ['Gwarancja i serwis',        $info_base.'gwarancja-i-serwis/'],
            ['Homologacja i rejestracja', $info_base.'homologacja-i-rejestracja/'],
            ['Język obsługi pojazdu',     $info_base.'jezyk-obslugi-pojazdu/'],
            ['Galeria sprzedanych aut',   home_url('/klienci/')],
        ];
        $cls = 'aa-info';
        if ($extra_class !== '') $cls .= ' '.$extra_class;
        $h  = '<div class="'.esc_attr($cls).'"><h3 class="aa-info__title">Informacje</h3><ul class="aa-info__list">';
        foreach ($info_links as [$label, $url])
            $h .= '<li><a href="'.esc_url($url).'">'.esc_html($label).'</a></li>';
        $h .= '</ul></div>';
        return $h;
    }

    // === CTA ===
    private function cta(array $d): string {
        $phone = preg_replace('/[^+0-9]/','',$d['phone']);
        $wa = 'https://wa.me/'.ltrim($phone,'+').'?text='.rawurlencode('Dzień dobry, pytam o ofertę: '.$d['title'].' — '.$d['url']);

        $order_url = home_url('/zamow/?listing_id='.(int)$d['pid']);

        $h = '<div class="aa-cta">';
        $h .= '<a href="'.esc_url($order_url).'" class="aa-cta__btn aa-cta__reserve">Zamów</a>';

        if (!empty($phone)) {
            $h .= '<a href="tel:'.esc_attr($phone).'" class="aa-cta__btn aa-cta__phone">'
                .'<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6A19.79 19.79 0 012.12 4.18 2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.362 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0122 16.92z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>'
                .' Zadzwoń</a>';
        }

        $h .= '<a href="'.esc_url($wa).'" target="_blank" rel="noopener" class="aa-cta__btn aa-cta__wa">'
            .'<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>'
            .' WhatsApp</a>';

        $h .= '</div>';
        return $h;
    }

    // === USP strip — 3 kolumny (W cenie / Dlaczego my / Informacje), czerwona sekcja ===
    private function uspStrip(): string {
        $info_base = home_url('/informacje/');
        $cols = [
            [
                'title' => 'W cenie',
                'class' => 'aa-usp-col--included',
                'items' => [
                    ['icon' => 'language',   'text' => 'Zmiana języka interfejsu PL / EN'],
                    ['icon' => 'microphone', 'text' => 'Sterowanie głosowe po polsku (dla wybranych modeli, np. BYD)'],
                    ['icon' => 'key',        'text' => 'Dodatkowy kluczyk / pilot'],
                    ['icon' => 'filter',     'text' => 'Dwa komplety filtrów (oleju, powietrza, kabinowy)'],
                    ['icon' => 'plug',       'text' => 'Ładowarka 7 kW EU (PHEV / EREV / elektryki)'],
                    ['icon' => 'plug',       'text' => 'Przejściówka do ładowarek miejskich (PHEV / EREV / elektryki)'],
                ],
            ],
            [
                'title' => 'Dlaczego my',
                'class' => 'aa-usp-col--why',
                'items' => [
                    ['icon' => 'check',    'text' => 'Pełna obsługa importu'],
                    ['icon' => 'pin',      'text' => 'Odbiór w Rzeszowie'],
                    ['icon' => 'tag',      'text' => 'Transparentna cena'],
                    ['icon' => 'award',    'text' => '20 lat doświadczenia'],
                    ['icon' => 'contract', 'text' => 'Umowa agencyjna'],
                ],
            ],
            [
                'title' => 'Informacje',
                'class' => 'aa-usp-col--info',
                'items' => [
                    ['icon' => 'dot', 'text' => 'Proces zamawiania',         'href' => $info_base.'proces-zamawiania/'],
                    ['icon' => 'dot', 'text' => 'Gwarancja i serwis',        'href' => $info_base.'gwarancja-i-serwis/'],
                    ['icon' => 'dot', 'text' => 'Homologacja i rejestracja', 'href' => $info_base.'homologacja-i-rejestracja/'],
                    ['icon' => 'dot', 'text' => 'Język obsługi pojazdu',     'href' => $info_base.'jezyk-obslugi-pojazdu/'],
                    ['icon' => 'dot', 'text' => 'Galeria sprzedanych aut',   'href' => home_url('/klienci/')],
                ],
            ],
        ];

        $h = '<div class="aa-usp-strip">';
        foreach ($cols as $col) {
            $h .= '<div class="aa-usp-col '.esc_attr($col['class']).'">';
            $h .= '<h3 class="aa-usp-col__title">'.esc_html($col['title']).'</h3>';
            $h .= '<ul class="aa-usp-col__list">';
            foreach ($col['items'] as $it) {
                $line = '<span class="aa-usp-col__icon">'.$this->uspIcon($it['icon']).'</span><span>'.esc_html($it['text']).'</span>';
                if (!empty($it['href'])) {
                    $h .= '<li><a href="'.esc_url($it['href']).'">'.$line.'</a></li>';
                } else {
                    $h .= '<li>'.$line.'</li>';
                }
            }
            $h .= '</ul></div>';
        }
        $h .= '</div>';
        return $h;
    }

    private function uspIcon(string $name): string {
        $icons = [
            'language'   => '<path d="M12.87 15.07l-2.54-2.51.03-.03A17.52 17.52 0 0 0 14.07 6H17V4h-7V2H8v2H1v2h11.17C11.5 7.92 10.44 9.75 9 11.35 8.07 10.32 7.3 9.19 6.69 8h-2c.73 1.63 1.73 3.17 2.98 4.56l-5.09 5.02L4 19l5-5 3.11 3.11.76-2.04zM18.5 10h-2L12 22h2l1.12-3h4.75L21 22h2l-4.5-12zm-2.62 7l1.62-4.33L19.12 17h-3.24z" fill="currentColor"/>',
            'microphone' => '<path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5.3-3c0 3-2.54 5.1-5.3 5.1S6.7 14 6.7 11H5c0 3.41 2.72 6.23 6 6.72V21h2v-3.28c3.28-.48 6-3.3 6-6.72h-1.7z" fill="currentColor"/>',
            'key'        => '<path d="M12.65 10A5.99 5.99 0 0 0 7 6c-3.31 0-6 2.69-6 6s2.69 6 6 6a5.99 5.99 0 0 0 5.65-4H17v4h4v-4h2v-4H12.65zM7 14c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z" fill="currentColor"/>',
            'filter'     => '<path d="M4.25 5.61C6.27 8.2 10 13 10 13v6c0 .55.45 1 1 1h2c.55 0 1-.45 1-1v-6s3.72-4.8 5.74-7.39A.998.998 0 0 0 18.95 4H5.04c-.83 0-1.3.95-.79 1.61z" fill="currentColor"/>',
            'plug'       => '<path d="M16 7V3h-2v4h-4V3H8v4H6v5c0 2.97 2.16 5.43 5 5.91V22h2v-4.09c2.84-.48 5-2.94 5-5.91V7h-2z" fill="currentColor"/>',
            'check'      => '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="currentColor"/>',
            'pin'        => '<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z" fill="currentColor"/>',
            'tag'        => '<path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z" fill="currentColor"/>',
            'award'      => '<path d="M12 2L9 9l-7 .75 5.13 4.87L5.5 22 12 18.27 18.5 22l-1.63-7.38L22 9.75 15 9z" fill="currentColor"/>',
            'contract'   => '<path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" fill="currentColor"/>',
            'dot'        => '<circle cx="12" cy="12" r="4" fill="currentColor"/>',
        ];
        $path = $icons[$name] ?? $icons['dot'];
        return '<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">'.$path.'</svg>';
    }

    // === Related models (siblings tej samej marki) ===
    // v0.32.24: internal linking single → hub modelu (sibling serie pod tym samym make).
    private function relatedModels(array $d): string {
        $pid = (int) $d['pid'];
        $make_terms = wp_get_object_terms($pid, 'make', ['number' => 1]);
        if (empty($make_terms) || is_wp_error($make_terms)) return '';
        $make = $make_terms[0];

        $serie_terms = wp_get_object_terms($pid, 'serie', ['number' => 1]);
        $exclude = (!empty($serie_terms) && !is_wp_error($serie_terms)) ? [(int) $serie_terms[0]->term_id] : [];

        $siblings = get_terms([
            'taxonomy'   => 'serie',
            'parent'     => $make->term_id,
            'hide_empty' => true,
            'exclude'    => $exclude,
            'orderby'    => 'count',
            'order'      => 'DESC',
            'number'     => 8,
        ]);
        if (empty($siblings) || is_wp_error($siblings)) return '';

        $make_label = (string) (get_term_meta($make->term_id, 'display_name', true) ?: $make->name);
        $h  = '<section class="aa-related">';
        $h .= '<h2 class="aa-related__title">Inne modele '.esc_html($make_label).'</h2>';
        $h .= '<div class="aa-related__grid">';
        foreach ($siblings as $sib) {
            $url = get_term_link($sib);
            if (is_wp_error($url)) continue;
            $display = get_term_meta($sib->term_id, 'display_name', true);
            $name = is_string($display) && $display !== '' ? $display : $sib->name;
            $count = (int) $sib->count;
            $unit = $count === 1 ? 'oferta' : (($count > 1 && $count < 5) ? 'oferty' : 'ofert');
            $h .= '<a href="'.esc_url($url).'" class="aa-related__item">';
            $h .= '<span class="aa-related__name">'.esc_html($name).'</span>';
            $h .= '<span class="aa-related__count">'.$count.' '.$unit.'</span>';
            $h .= '</a>';
        }
        $h .= '</div></section>';
        return $h;
    }

    // === Mobile sticky CTA ===
    private function mobileCta(array $d): string {
        $phone = preg_replace('/[^+0-9]/','',$d['phone']);
        $wa = 'https://wa.me/'.ltrim($phone,'+').'?text='.rawurlencode('Dzień dobry, pytam o ofertę: '.$d['title'].' — '.$d['url']);
        $order_url = home_url('/zamow/?listing_id='.(int)$d['pid']);
        $h = '<div class="aa-mobile-cta">';
        $h .= '<a href="'.esc_url($order_url).'" class="aa-mcta aa-mcta--order"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M5 7h14l-1.5 10.5a2 2 0 0 1-2 1.7h-7a2 2 0 0 1-2-1.7L5 7z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 7V5a3 3 0 0 1 6 0v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg> Zamów</a>';
        if (!empty($phone))
            $h .= '<a href="tel:'.esc_attr($phone).'" class="aa-mcta aa-mcta--phone"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6A19.79 19.79 0 012.12 4.18 2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.362 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0122 16.92z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg> Zadzwoń</a>';
        $h .= '<a href="'.esc_url($wa).'" target="_blank" rel="noopener" class="aa-mcta aa-mcta--wa"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg> WhatsApp</a>';
        return $h.'</div>';
    }

    // === Schema ===
    private function schema(array $d): string {
        $pw = $this->power($d['ep']);
        $s = ['@context'=>'https://schema.org','@type'=>'Car','name'=>$d['title'],'url'=>$d['url'],
            'brand'=>['@type'=>'Brand','name'=>$d['make']],'model'=>$d['model'],
            'vehicleModelDate'=>$d['year'],'bodyType'=>$d['body'],
            'fuelType'=>match($d['fuel_slug']){'electric'=>'Electricity','erev','phev'=>'Electricity, Gasoline','petrol'=>'Gasoline','diesel'=>'Diesel',default=>$d['fuel_slug']},
            'vehicleTransmission'=>$d['trans'],'driveWheelConfiguration'=>$d['drive'],'color'=>$d['color'],
            'itemCondition'=>str_contains(mb_strtolower($d['cond']),'now')?'https://schema.org/NewCondition':'https://schema.org/UsedCondition',
            'mileageFromOdometer'=>['@type'=>'QuantitativeValue','value'=>(int)($d['mileage']?:0),'unitCode'=>'KMT']];
        if ($pw['kw']) $s['vehicleEngine']=['@type'=>'EngineSpecification','enginePower'=>['@type'=>'QuantitativeValue','value'=>$pw['kw'],'unitCode'=>'KWT']];
        if (!empty($d['price'])&&is_numeric($d['price']))
            $s['offers']=['@type'=>'Offer','price'=>(float)$d['price'],'priceCurrency'=>'PLN','availability'=>'https://schema.org/InStock','url'=>$d['url'],'seller'=>['@type'=>'Organization','name'=>'Prima Auto','url'=>home_url('/')]];
        if ($d['thumb']) $s['image']=$d['thumb'];
        $s = array_filter($s, fn($v)=>$v!==null&&$v!==''&&$v!==0);
        return '<script type="application/ld+json">'.wp_json_encode($s,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)."</script>\n";
    }

    // === Helpers ===
    private function relTime(string $d): string {
        if (!$d) return '';
        try{$tz=new \DateTimeZone('Europe/Warsaw');$now=new \DateTimeImmutable('now',$tz);$then=new \DateTimeImmutable($d,$tz);}catch(\Exception $e){return '';}
        $diff=$now->getTimestamp()-$then->getTimestamp(); if($diff<0)return'Dodano przed chwilą';
        $min=(int)floor($diff/60);$hrs=(int)floor($diff/3600);$days=(int)floor($diff/86400);
        if($min<2)return'Dodano przed chwilą'; if($min<60)return"Dodano {$min} min temu";
        if($hrs===1)return'Dodano 1 godz. temu'; if($hrs<24)return"Dodano {$hrs} godz. temu";
        if($days===1)return'Dodano wczoraj'; if($days<31)return"Dodano {$days} dni temu";
        return'Dodano '.((int)floor($diff/2592000)).' mies. temu';
    }

    // =========================================================================
    // SEO: meta title + meta description + OG tags
    // =========================================================================

    public function filterTitle(array $parts): array {
        if (!is_singular('listings')) return $parts;
        $pid = get_the_ID();
        if (!$pid) return $parts;

        // Baza z post_title — importer robi tam dedupe prefixu marki (linia 93-97),
        // więc nie ma duplikatu typu „Denza Denza D9 DM". Term serie tego nie ma.
        $base = trim((string) get_the_title($pid));
        if ($base === '') return $parts;

        $inner_id = (int) get_post_meta($pid, '_asiaauto_inner_id', true);

        $templates = [
            0 => "Używane $base z Chin — Prima Auto",
            1 => "$base import z Chin — Prima Auto",
            2 => "$base zamów z Chin — Prima Auto",
            3 => "$base gotowy do rejestracji w Polsce — Prima Auto",
            4 => "$base — auto z Chin — Prima Auto",
            5 => "$base z rynku chińskiego — Prima Auto",
            6 => "$base na zamówienie z Chin — Prima Auto",
            7 => "Sprowadź $base z Chin — Prima Auto",
            8 => "$base import prosto z Chin",
            9 => "Zamów Online $base z Chin w 2026 — Prima Auto",
        ];

        $parts['title'] = trim(preg_replace('/\s+/u', ' ', $templates[$inner_id % 10]));
        // Szablony zawierają „Prima Auto" — wyłączamy domyślne doklejanie blog name
        // (WP inaczej dałby title = „... — Prima Auto – Prima-Auto").
        unset($parts['site']);
        return $parts;
    }

    public function renderMeta(): void {
        if (!is_singular('listings')) return;
        $pid = get_the_ID();
        if (!$pid) return;

        $make     = ($t = get_the_terms($pid, 'make'))    && !is_wp_error($t) ? $t[0]->name : '';
        $model    = ($t = get_the_terms($pid, 'serie'))   && !is_wp_error($t) ? $t[0]->name : '';
        $year     = ($t = get_the_terms($pid, 'ca-year')) && !is_wp_error($t) ? $t[0]->name : '';
        $fuel     = ($t = get_the_terms($pid, 'fuel'))    && !is_wp_error($t) ? $t[0]->name : '';
        $drive    = ($t = get_the_terms($pid, 'drive'))   && !is_wp_error($t) ? $t[0]->name : '';
        $body     = ($t = get_the_terms($pid, 'body'))    && !is_wp_error($t) ? $t[0]->name : '';
        $mileage  = (int) get_post_meta($pid, 'mileage', true);
        $price    = (float) get_post_meta($pid, 'price', true);
        $thumb    = get_the_post_thumbnail_url($pid, 'large') ?: get_the_post_thumbnail_url($pid, 'full');
        $url      = get_permalink($pid);
        $inner_id = (string) get_post_meta($pid, '_asiaauto_inner_id', true);
        $cif_usd  = (float) get_post_meta($pid, '_asiaauto_cif_usd', true);
        $city     = (string) get_post_meta($pid, 'stm_car_location', true);

        // Baza z post_title — importer zdedupeował prefix marki (linia 93-97),
        // więc nie dubluje marki w modelu. Termy (make/serie) używane niżej dla
        // schema.brand i dataLayer.item_brand / item_category osobno.
        $base = trim((string) get_the_title($pid));
        $desc = $base;
        if ($fuel)        $desc .= ', ' . $fuel;
        if ($mileage > 0) $desc .= ', ' . number_format($mileage, 0, ',', ' ') . ' km';
        if ($price > 0)   $desc .= ', od ' . number_format($price, 0, ',', ' ') . ' PLN';
        $desc .= '. Import z Chin – Prima Auto.';

        $og_title = $base !== '' ? "$base – import z Chin | Prima Auto" : 'Prima Auto';

        // === GTM dataLayer: view_item event (przed metami, żeby GTM miał kontekst przy load) ===
        $dl_item_name = $base;
        $dl = [
            'event'     => 'view_item',
            'page_type' => 'listing_single',
            'ecommerce' => [
                'currency' => 'PLN',
                'value'    => $price > 0 ? $price : null,
                'items'    => [array_filter([
                    // content_id katalogu Meta + GA4 item_id: inner_id gdy jest (zsynchronizowane),
                    // post_id jako fallback dla ręcznych listingów bez inner_id (parytet z feedem vehicle_id).
                    'item_id'       => $inner_id !== '' ? $inner_id : (string) $pid,
                    'item_name'     => $dl_item_name,
                    'item_brand'    => $make,
                    'item_category' => $model,
                    'item_variant'  => $year,
                    'price'         => $price > 0 ? $price : null,
                ], fn($v) => $v !== null && $v !== '')],
            ],
            'listing' => array_filter([
                'inner_id' => $inner_id,
                'mark'     => $make,
                'model'    => $model,
                'year'     => $year,
                'fuel'     => $fuel,
                'drive'    => $drive,
                'body'     => $body,
                'city'     => $city,
                'cif_usd'  => $cif_usd > 0 ? $cif_usd : null,
            ], fn($v) => $v !== null && $v !== ''),
        ];
        echo '<script>window.dataLayer=window.dataLayer||[];window.dataLayer.push('
            . wp_json_encode($dl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . ');</script>' . "\n";

        // RankMath Pro auto-extractuje desc z post_content dla CPT listings — generuje śmieci
        // (chińskie znaki z importu Dongchedi). Filtry niżej (initRankMathSuppression) blokują
        // emisję RM dla single listings; my emitujemy własną bogatą desc z marką/cena/USP.
        echo '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
        echo '<meta property="og:type" content="product">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($og_title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($desc) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
        if ($thumb) {
            echo '<meta property="og:image" content="' . esc_url($thumb) . '">' . "\n";
            echo '<meta property="og:image:width" content="800">' . "\n";
            echo '<meta property="og:image:height" content="600">' . "\n";
        }

        // Schema.org — output here (in wp_head) because Elementor single template
        // uses individual shortcodes, not [asiaauto_single], so schema() in render() never fires.
        $fuel_slug = ($t = get_the_terms($pid, 'fuel')) && !is_wp_error($t) ? $t[0]->slug : '';
        $body         = ($t = get_the_terms($pid, 'body'))         && !is_wp_error($t) ? $t[0]->name : '';
        $transmission = ($t = get_the_terms($pid, 'transmission')) && !is_wp_error($t) ? $t[0]->name : '';
        $color        = ($t = get_the_terms($pid, 'color'))        && !is_wp_error($t) ? $t[0]->name : '';
        $cond_term    = ($t = get_the_terms($pid, 'condition'))    && !is_wp_error($t) ? $t[0]      : null;
        $cond_slug    = $cond_term ? $cond_term->slug : '';
        $itemCondition = $cond_slug === 'new'
            ? 'https://schema.org/NewCondition'
            : ($cond_slug === 'used' ? 'https://schema.org/UsedCondition' : null);
        $ep_raw = get_post_meta($pid, '_asiaauto_extra_prep', true);
        $ep     = is_string($ep_raw) ? (json_decode($ep_raw, true) ?: []) : (is_array($ep_raw) ? $ep_raw : []);
        $pw     = $this->power($ep, $pid);
        $schema = array_filter([
            '@context'             => 'https://schema.org',
            '@type'                => 'Car',
            'name'                 => get_the_title($pid),
            'url'                  => $url,
           'brand'                => ['@type' => 'Brand', 'name' => $make],
            'manufacturer'         => $make ? ['@type' => 'Organization', 'name' => $make] : null,
            'model'                => $model,
            'vehicleModelDate'     => $year,
            'bodyType'             => $body ?: null,
            'fuelType'             => match($fuel_slug) {
                'electric' => 'Electricity',
                'erev', 'phev' => 'Electricity, Gasoline',
                'petrol' => 'Gasoline',
                'diesel' => 'Diesel',
                default => $fuel,
            },
            'vehicleTransmission'      => $transmission ?: null,
            'driveWheelConfiguration'  => $drive ?: null,
            'color'                    => $color ?: null,
            'itemCondition'            => $itemCondition,
            'vehicleEngine'            => $pw['kw'] ? [
                '@type'        => 'EngineSpecification',
                'enginePower'  => ['@type' => 'QuantitativeValue', 'value' => $pw['kw'], 'unitCode' => 'KWT'],
            ] : null,
            'mileageFromOdometer'  => $mileage > 0 ? [
                '@type' => 'QuantitativeValue',
                'value' => $mileage,
                'unitCode' => 'KMT',
            ] : null,
            'image'                => $thumb ?: null,
            'offers'               => $price > 0 ? [
                '@type'          => 'Offer',
                'price'          => $price,
                'priceCurrency'  => 'PLN',
                'availability'   => 'https://schema.org/InStock',
                'priceValidUntil' => gmdate('Y-m-d', strtotime('+90 days')),
                'url'            => $url,
                'seller'         => ['@type' => 'Organization', 'name' => 'Prima Auto', 'url' => home_url('/')],
                'shippingDetails' => [
                    '@type'             => 'OfferShippingDetails',
                    'shippingRate'      => ['@type' => 'MonetaryAmount', 'value' => 0, 'currency' => 'PLN'],
                    'shippingDestination' => ['@type' => 'DefinedRegion', 'addressCountry' => 'PL'],
                    'deliveryTime'      => [
                        '@type'                => 'ShippingDeliveryTime',
                        'handlingTime'         => ['@type' => 'QuantitativeValue', 'minValue' => 0, 'maxValue' => 7, 'unitCode' => 'DAY'],
                        'transitTime'          => ['@type' => 'QuantitativeValue', 'minValue' => 56, 'maxValue' => 84, 'unitCode' => 'DAY'],
                    ],
                ],
            ] : null,
        ], fn($v) => $v !== null && $v !== '');
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "</script>\n";

        // BreadcrumbList JSON-LD (Samochody › Marka › Model › Tytuł) — mirroruje [aa_breadcrumb]
        $bc_items = [[
            '@type'    => 'ListItem',
            'position' => 1,
            'name'     => 'Samochody',
            'item'     => home_url('/samochody/'),
        ]];
        $pos = 1;
        $make_terms = get_the_terms($pid, 'make');
        if ($make_terms && !is_wp_error($make_terms)) {
            $make_link = get_term_link($make_terms[0]);
            $pos++;
            $bc_items[] = [
                '@type'    => 'ListItem',
                'position' => $pos,
                'name'     => $make_terms[0]->name,
                'item'     => is_wp_error($make_link) ? home_url('/samochody/') : $make_link,
            ];
            $serie_terms = get_the_terms($pid, 'serie');
            if ($serie_terms && !is_wp_error($serie_terms)) {
                $serie_link = get_term_link($serie_terms[0]);
                $pos++;
                $bc_items[] = [
                    '@type'    => 'ListItem',
                    'position' => $pos,
                    'name'     => $serie_terms[0]->name,
                    'item'     => is_wp_error($serie_link) ? $url : $serie_link,
                ];
            }
        }
        $pos++;
        $bc_items[] = [
            '@type'    => 'ListItem',
            'position' => $pos,
            'name'     => get_the_title($pid),
            'item'     => $url,
        ];
        $breadcrumb_schema = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $bc_items,
        ];
        echo '<script type="application/ld+json">' . wp_json_encode($breadcrumb_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";
    }

    private function icon(string $t): string {
        return match($t){
            'mileage'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z" stroke="currentColor" stroke-width="1.8"/><path d="M12 6v6l4 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
            'power'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'fuel'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M3 22V6a2 2 0 012-2h8a2 2 0 012 2v16" stroke="currentColor" stroke-width="1.8"/><path d="M3 22h12M15 10h2a2 2 0 012 2v3a2 2 0 002 0v-5l-3-3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><rect x="6" y="7" width="6" height="4" rx="0.5" stroke="currentColor" stroke-width="1.5"/></svg>',
            'trans'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="5" cy="6" r="2" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="6" r="2" stroke="currentColor" stroke-width="1.8"/><circle cx="19" cy="6" r="2" stroke="currentColor" stroke-width="1.8"/><circle cx="5" cy="18" r="2" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="18" r="2" stroke="currentColor" stroke-width="1.8"/><path d="M5 8v8M12 8v8M19 8v4a2 2 0 01-2 2h-3" stroke="currentColor" stroke-width="1.8"/></svg>',
            'year'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
            'owners'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="1.8"/></svg>',
            default=>'',
        };
    }
}
