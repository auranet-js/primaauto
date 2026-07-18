<?php
defined('ABSPATH') || exit;

/**
 * SEO: meta (canonical, description, og, twitter) + schema (BreadcrumbList,
 * CollectionPage, ItemList) dla widoków, których nie obsługuje class-asiaauto-single.php.
 *
 * Zakres:
 * - Homepage:     canonical + meta description + og:* + twitter (schema WebSite/AutoDealer
 *                 obsługuje class-asiaauto-homepage.php)
 * - Hub marki:    /samochody/<make>/  — pełen meta + BreadcrumbList + CollectionPage + ItemList
 * - Hub serii:    /samochody/<make>/<serie>/  — jw. + 4-poziomowy BreadcrumbList
 * - Thin taxons:  transmission, drive, exterior-color, interior-color, condition —
 *                 noindex,follow + usunięte z wp-sitemap.xml
 *
 * Źródła treści:
 * - Meta desc (huby):  term_meta 'asiaauto_wiki_body' (158 chars z pierwszego akapitu) →
 *                      fallback template "Importuj {marka} prosto z Chin..."
 * - OG image (huby):   pierwsze zdjęcie pierwszego listingu → fallback custom_logo
 *
 * Single listings (/oferta/*) — NIE ruszamy, pełen SEO już jest w class-asiaauto-single.php.
 */
class AsiaAuto_SEO
{
    private const THIN_TAXONOMIES = ['transmission', 'drive', 'exterior-color', 'interior-color', 'condition'];
    private const DESC_MAX_LEN    = 158;
    private const ITEMLIST_LIMIT  = 10;

    /**
     * Marki, których hubów NIE wpuszczamy do indeksu mimo treści — brand fit, nie jakość strony.
     * Prima-Auto nie chce wyglądać na importera Golfa. Nadpisywalne opcją `asiaauto_hub_index_blocked_makes`.
     */
    private const HUB_INDEX_BLOCKED_MAKES = ['volkswagen', 'mini', 'iveco'];

    /** Progi treści kwalifikujące hub serii do indeksu przy count=0. */
    private const HUB_INDEX_MIN_WIKI = 500;
    private const HUB_INDEX_MIN_SPEC = 200;

    public function __construct()
    {
        add_action('wp_head', [$this, 'renderResourceHints'], 0);
        add_action('wp_head', [$this, 'renderMeta'], 1);
        add_action('wp_head', [$this, 'renderSchema'], 2);
        add_filter('wp_sitemaps_taxonomies', [$this, 'filterSitemapTaxonomies']);
        add_filter('document_title_parts', [$this, 'filterHomeTitle'], 20);
        add_filter('wp_robots', [$this, 'filterRobots']);
        // RankMath przykrywa nasz wp_robots filter — hook ich własny żeby wymusić noindex na thin tax.
        add_filter('rank_math/frontend/robots', [$this, 'filterRankMathRobots']);
        add_filter('rank_math/json_ld', [$this, 'addHubDateModified'], 99, 2);
        add_action('template_redirect', [$this, 'blockAuthorArchive']);
        add_action('template_redirect', [$this, 'redirectListingsArchive']);
        add_filter('author_link', [$this, 'filterAuthorLink'], 10, 1);
    }

    /**
     * Author archives ujawniają loginy WP w URL-u (/author/<login>/) — info disclosure.
     * Strona nie publikuje wpisów per autor, więc archive jest thin content + zbędne.
     * Hard 301 do home dla /?author=ID i /author/<login>/.
     */
    public function blockAuthorArchive(): void
    {
        if (is_author() || (isset($_GET['author']) && (int) $_GET['author'] > 0)) {
            wp_safe_redirect(home_url('/'), 301);
            exit;
        }
    }

    public function filterAuthorLink($link)
    {
        return home_url('/');
    }

    /**
     * Archiwum CPT listings (/oferta/) to auto-generowany fallback WP (has_archive):
     * ściana pełnych opisów ofert = thin + duplicate content, index,follow, w sitemap,
     * nie ma go w menu, nic nie linkuje (tylko RankMath sitemap). Hard 301 do właściwej
     * listy /samochody/. Dotyczy WYŁĄCZNIE archiwum — pojedyncze oferty /oferta/{slug}/
     * to is_singular('listings'), pozostają nietknięte.
     */
    public function redirectListingsArchive(): void
    {
        if (is_post_type_archive('listings')) {
            wp_safe_redirect(home_url('/samochody/'), 301);
            exit;
        }
    }


    public function renderResourceHints(): void
    {
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
        echo '<link rel="dns-prefetch" href="https://fonts.googleapis.com">' . "\n";
    }

    public function renderMeta(): void
    {
        // od v0.32.6: RankMath przejmuje canonical/description/og:*/twitter:*
        // Zostawiamy ZERO emisji — RankMath robi wszystko z `rank_math_title`/`rank_math_description`/itp.
        // Jeśli RankMath nie aktywny — fallback do starego kodu.
        if (defined('RANK_MATH_VERSION')) {
            return;
        }
        $ctx = $this->context();
        if (!$ctx) {
            return;
        }

        echo '<link rel="canonical" href="' . esc_url($ctx['url']) . "\">\n";
        echo '<meta name="description" content="' . esc_attr($ctx['desc']) . "\">\n";
        echo '<meta property="og:type" content="' . esc_attr($ctx['og_type']) . "\">\n";
        echo '<meta property="og:title" content="' . esc_attr($ctx['title']) . "\">\n";
        echo '<meta property="og:description" content="' . esc_attr($ctx['desc']) . "\">\n";
        echo '<meta property="og:url" content="' . esc_url($ctx['url']) . "\">\n";
        echo '<meta property="og:site_name" content="Prima-Auto">' . "\n";
        echo '<meta property="og:locale" content="pl_PL">' . "\n";
        if (!empty($ctx['og_image'])) {
            echo '<meta property="og:image" content="' . esc_url($ctx['og_image']) . "\">\n";
        }
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($ctx['title']) . "\">\n";
        echo '<meta name="twitter:description" content="' . esc_attr($ctx['desc']) . "\">\n";
        if (!empty($ctx['og_image'])) {
            echo '<meta name="twitter:image" content="' . esc_url($ctx['og_image']) . "\">\n";
        }
    }

    public function renderSchema(): void
    {
        if (is_front_page() || is_home()) {
            return; // homepage schema = class-asiaauto-homepage.php
        }

        $ctx = $this->context();
        if (!$ctx || empty($ctx['is_hub'])) {
            return;
        }

        // v0.32.6: RankMath emituje BreadcrumbList + CollectionPage. Zostawiamy tylko ItemList
        // (RankMath nie ma listy listingów per hub — to nasz custom value-add dla SEO).
        // FAQPage idzie z class-asiaauto-brand-hub.php osobno.
        // v0.32.23: Product + AggregateOffer dla hub modelu — Google Vehicle Search / Product Snippet "od X PLN".
        $schemas = array_filter(
            defined('RANK_MATH_VERSION')
                ? [$this->buildItemList($ctx), $this->buildProductForSerieHub($ctx), $this->buildCarForSerieHub($ctx)]
                : [
                    $this->buildBreadcrumbs($ctx),
                    $this->buildCollectionPage($ctx),
                    $this->buildItemList($ctx),
                    $this->buildProductForSerieHub($ctx),
                    $this->buildCarForSerieHub($ctx),
                ]
        );

        foreach ($schemas as $schema) {
            echo '<script type="application/ld+json">'
                . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                . "</script>\n";
        }
    }

    /**
     * Wpina dateModified w node CollectionPage/WebPage RankMatha dla hubów,
     * które mają stored facts (_asiaauto_facts.updated z reworku) — sygnał świeżości.
     * RankMath kontroluje @graph (nasz buildCollectionPage jest wtedy dead code).
     */
    public function addHubDateModified($data, $jsonld = null)
    {
        if (!is_array($data)) {
            return $data;
        }
        $term = get_queried_object();
        if (!($term instanceof WP_Term)) {
            return $data;
        }
        $facts = json_decode((string) get_term_meta($term->term_id, '_asiaauto_facts', true), true);
        if (empty($facts['updated'])) {
            return $data;
        }
        foreach ($data as $key => $node) {
            if (!is_array($node)) {
                continue;
            }
            $type = $node['@type'] ?? '';
            if (in_array($type, ['CollectionPage', 'WebPage', 'ItemPage'], true)) {
                $data[$key]['dateModified'] = $facts['updated'];
            }
        }
        return $data;
    }

    public function filterSitemapTaxonomies(array $taxonomies): array
    {
        foreach (self::THIN_TAXONOMIES as $name) {
            unset($taxonomies[$name]);
        }
        return $taxonomies;
    }

    public function filterHomeTitle(array $parts): array
    {
        // od v0.32.6: RankMath kontroluje title — disable nasz filter.
        if (defined('RANK_MATH_VERSION')) {
            return $parts;
        }
        if (!is_front_page() && !is_home()) {
            return $parts;
        }
        $parts['title'] = 'Prima-Auto — Import samochodów z Chin do Polski';
        unset($parts['tagline']);
        return $parts;
    }

    public function filterRobots(array $robots): array
    {
        if (is_author()) {
            $robots['noindex']            = true;
            $robots['follow']             = true;
            $robots['max-image-preview']  = 'large';
            return $robots;
        }
        // Inventory z query params (filtry typu ?nadwozie=suv, ?paliwo=hybrid, ?marka=byd) —
        // duplikat treści hub-ów + thin tax. Crawl OK (follow), ale nie indeksuj.
        if ($this->isFilteredInventory()) {
            $robots['noindex']            = true;
            $robots['follow']             = true;
            $robots['max-image-preview']  = 'large';
            return $robots;
        }
        // /zamow/?listing_id=X — formularz wizard akcyjny per listing, identyczny szablon
        // dla 1841 ogłoszeń = duplicate content × 1841. Canonical do /zamow/ niewystarczający.
        if ($this->isOrderWizardPerListing()) {
            $robots['noindex']            = true;
            $robots['follow']             = true;
            $robots['max-image-preview']  = 'large';
            return $robots;
        }
        foreach (self::THIN_TAXONOMIES as $tax) {
            if (is_tax($tax)) {
                $robots['noindex']            = true;
                $robots['follow']             = true;
                $robots['max-image-preview']  = 'large';
                return $robots;
            }
        }
        return $robots;
    }

    /**
     * Detekcja: czy aktualna strona to inventory `[asiaauto_inventory]` Z aktywnymi filtrami query.
     * `/samochody/` to WP page z shortcode (nie post_type_archive WP).
     */
    private function isFilteredInventory(): bool
    {
        global $post;
        if (!$post || !is_singular()) return false;
        if (!has_shortcode($post->post_content, 'asiaauto_inventory')) return false;
        $params = ['nadwozie', 'paliwo', 'marka', 'model', 'naped', 'rok', 'cena', 'kolor', 'skrzynia'];
        foreach ($params as $p) {
            if (!empty($_GET[$p])) return true;
        }
        return false;
    }

    private function isOrderWizardPerListing(): bool
    {
        global $post;
        if (!$post || !is_singular()) return false;
        if ($post->post_name !== 'zamow') return false;
        return !empty($_GET['listing_id']);
    }

    /**
     * RankMath ma własny pipeline robots który przykrywa core wp_robots — hook ich filter
     * żeby wymusić noindex/follow na thin taxonomies oraz author archives.
     * Format zwracany: assoc array meta robots (np. ['index'=>'noindex', 'follow'=>'follow']).
     */
    public function filterRankMathRobots(array $robots): array
    {
        if (is_author()) {
            $robots['index']  = 'noindex';
            $robots['follow'] = 'follow';
            return $robots;
        }
        if ($this->isFilteredInventory()) {
            $robots['index']  = 'noindex';
            $robots['follow'] = 'follow';
            return $robots;
        }
        if ($this->isOrderWizardPerListing()) {
            $robots['index']  = 'noindex';
            $robots['follow'] = 'follow';
            return $robots;
        }
        foreach (self::THIN_TAXONOMIES as $tax) {
            if (is_tax($tax)) {
                $robots['index']  = 'noindex';
                $robots['follow'] = 'follow';
                return $robots;
            }
        }
        // WYŁĄCZONE 2026-07-18, kilka godzin po wdrożeniu — patrz `termQualifiesForIndex()`.
        // Kryterium było za luźne i wpuściło do indeksu 67 termów-widm. NIE włączać bez dedupe.
        return $robots;
    }

    /**
     * RankMath ma `noindex_empty_taxonomies=on`, a `term_taxonomy.count` hubów serii bywa 0 mimo
     * realnego stanu — oferty wiszą pod termami-wariantami (np. `CS55 Plus PHEV`), podczas gdy hub
     * agreguje szerzej i renderuje je poprawnie. Efekt: 88 hubów z towarem wypadło z indeksu.
     *
     * Kryterium celowo oparte na TREŚCI, nie na stanie magazynowym: hub z opisem i danymi
     * technicznymi jest wartościowy również po wyprzedaniu ostatniej sztuki — użytkownik trafia
     * na niego z frazy modelowej i znajduje warianty, odpowiedniki elektryczne, podobne modele.
     * Wyrzucanie takiego huba z indeksu kasowałoby ruch, który dopiero co zbudowaliśmy.
     */
    private function hubQualifiesForIndex(): bool
    {
        if (!is_tax('serie')) return false;
        $term = get_queried_object();
        if (!$term || empty($term->term_id)) return false;
        return self::termQualifiesForIndex((int) $term->term_id);
    }

    /**
     * ⚠ NIEUŻYWANE — wpięcie wyłączone 2026-07-18. Zostawione jako dokumentacja nieudanej próby.
     *
     * Kryterium „wiki ≥500 LUB spec ≥200" okazało się BŁĘDNE. Wpuściło do indeksu 68 hubów,
     * z czego **67 nigdy nie miało ani jednej oferty** (0 wierszy w `term_relationships`) —
     * to termy-widma z importu Dongchedi z opisem dogenerowanym przez LLM w kwietniu/maju.
     * 30 z nich nie miało nawet danych technicznych. Wśród odblokowanych: `Omoda` (nazwa marki,
     * nie model — są Omoda 5 / 9), `Hongqi HS7` (realny jest `HS7 PHEV`), `Jetour X90`
     * (realne: `X90 PLUS` / `X90 PRO`), `Geely ICON` (zero ofert w całej historii).
     *
     * Warunkiem poprawnego odblokowania NIE jest treść, tylko:
     *   1) historia ofert (`term_relationships` > 0) — dowód, że model realnie u nas był,
     *   2) dane techniczne (`_asiaauto_spec_snapshot`),
     *   3) dedupe wariantów — inaczej `Tiggo 8` kanibalizuje `Tiggo 8 Pro` / `Tiggo 8 PLUS`.
     * Punkt 3 to osobna robota na taksonomii, nie doklejka do filtra robots.
     */
    public static function termQualifiesForIndex(int $term_id): bool
    {
        if ($term_id <= 0) return false;

        $blocked = apply_filters(
            'asiaauto_hub_index_blocked_makes',
            (array) get_option('asiaauto_hub_index_blocked_makes', self::HUB_INDEX_BLOCKED_MAKES)
        );
        $make = (string) get_term_meta($term_id, '_asiaauto_primary_make_slug', true);
        if ($make !== '' && in_array($make, $blocked, true)) return false;

        $wiki = (string) get_term_meta($term_id, 'asiaauto_wiki_body', true);
        if (strlen($wiki) >= self::HUB_INDEX_MIN_WIKI) return true;

        $spec = (string) get_term_meta($term_id, '_asiaauto_spec_snapshot', true);
        return strlen($spec) >= self::HUB_INDEX_MIN_SPEC;
    }

    /**
     * SITEMAPA — świadomie NIE ruszana. RankMath wycina termy `count=0` już na poziomie query
     * (`hide_empty`), więc filtr `rank_math/sitemap/entry` ich nie zobaczy. Włączenie
     * `tax_serie_include_empty` wpuszcza do query ~2700 termów i przy N+1 na term_meta generacja
     * się nie domyka — sitemapa serie spadła 297→63 (zweryfikowane 2026-07-18, cofnięte).
     * Odblokowane huby zgłaszamy do Google przez `~/bin/index-submit`; crawl i tak je odwiedza
     * (mediana lastCrawl 4 dni). Trwałe wejście do sitemapy = osobny task, wymaga batchowania meta.
     */

    private function context(): ?array
    {
        if (is_front_page() || is_home()) {
            return [
                'url'      => home_url('/'),
                'title'    => 'Prima-Auto — Import samochodów z Chin do Polski',
                'desc'     => 'Import samochodów elektrycznych, hybrydowych i spalinowych z Chin do Polski. BYD, Xiaomi, XPeng, Zeekr, Denza, Chery, Geely — pełne przygotowanie do rejestracji, gwarancja.',
                'og_type'  => 'website',
                'og_image' => $this->siteLogoUrl(),
            ];
        }

        $make_q  = get_query_var('make');
        $serie_q = get_query_var('serie');

        if ($make_q && $serie_q) {
            $serie_term = get_term_by('slug', $serie_q, 'serie');
            if (!$serie_term instanceof WP_Term) {
                return null;
            }
            $make_term = get_term_by('slug', $make_q, 'make');
            $url       = get_term_link($serie_term, 'serie');
            if (is_wp_error($url)) {
                return null;
            }

            $make_label  = $make_term instanceof WP_Term ? $this->termLabel($make_term) : ucfirst($make_q);
            $serie_label = $this->termLabel($serie_term);
            $full_label  = trim($make_label . ' ' . $serie_label);
            $count       = $this->countListings($serie_term);

            return [
                'url'       => $url,
                'title'     => $full_label . ' – Prima-Auto',
                'desc'      => $this->descriptionForHub($serie_term, 'serie', $full_label, $count),
                'og_type'   => 'website',
                'og_image'  => $this->firstListingImage($serie_term) ?: $this->siteLogoUrl(),
                'is_hub'    => 'serie',
                'term'      => $serie_term,
                'make_term' => $make_term instanceof WP_Term ? $make_term : null,
                'label'     => $full_label,
                'count'     => $count,
            ];
        }

        if ($make_q || is_tax('make')) {
            $make_term = $make_q ? get_term_by('slug', $make_q, 'make') : get_queried_object();
            if (!$make_term instanceof WP_Term || $make_term->taxonomy !== 'make') {
                return null;
            }
            $url = get_term_link($make_term, 'make');
            if (is_wp_error($url)) {
                return null;
            }
            $label = $this->termLabel($make_term);
            $count = $this->countListings($make_term);

            return [
                'url'      => $url,
                'title'    => $label . ' – Prima-Auto',
                'desc'     => $this->descriptionForHub($make_term, 'make', $label, $count),
                'og_type'  => 'website',
                'og_image' => $this->firstListingImage($make_term) ?: $this->siteLogoUrl(),
                'is_hub'   => 'make',
                'term'     => $make_term,
                'label'    => $label,
                'count'    => $count,
            ];
        }

        return null;
    }

    private function termLabel(WP_Term $term): string
    {
        $display = get_term_meta($term->term_id, 'display_name', true);
        return is_string($display) && $display !== '' ? $display : $term->name;
    }

    private function countListings(WP_Term $term): int
    {
        $q = new WP_Query([
            'post_type'      => 'listings',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => false,
            'tax_query'      => [[
                'taxonomy' => $term->taxonomy,
                'field'    => 'term_id',
                'terms'    => $term->term_id,
            ]],
        ]);
        return (int) $q->found_posts;
    }

    private function firstListingImage(WP_Term $term): string
    {
        $q = new WP_Query([
            'post_type'      => 'listings',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'tax_query'      => [[
                'taxonomy' => $term->taxonomy,
                'field'    => 'term_id',
                'terms'    => $term->term_id,
            ]],
        ]);
        if (empty($q->posts)) {
            return '';
        }
        $thumb_id = get_post_thumbnail_id($q->posts[0]);
        if (!$thumb_id) {
            return '';
        }
        $url = wp_get_attachment_image_url($thumb_id, 'large');
        return is_string($url) ? $url : '';
    }

    private function descriptionForHub(WP_Term $term, string $kind, string $label, int $count): string
    {
        $wiki = get_term_meta($term->term_id, 'asiaauto_wiki_body', true);
        if (is_string($wiki) && $wiki !== '') {
            $clean = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags($wiki)));
            if (mb_strlen($clean) >= 80) {
                return $this->truncate($clean, self::DESC_MAX_LEN);
            }
        }

        $unit = $count === 1 ? 'oferta' : ($count > 1 && $count < 5 ? 'oferty' : 'ofert');
        if ($count === 0) {
            return sprintf(
                '%s — import samochodów z Chin do Polski. Sprawdź katalog marek i ofert dostępnych w Prima-Auto.',
                $label
            );
        }
        if ($kind === 'make') {
            return sprintf(
                'Importuj %s prosto z Chin do Polski — %d %s w katalogu. Elektryki, hybrydy, spalinowe. Pełne przygotowanie do rejestracji, sprawdź oferty Prima-Auto.',
                $label,
                $count,
                $unit
            );
        }
        return sprintf(
            '%s — import prosto z Chin do Polski. %d %s z pełnym przygotowaniem do rejestracji. Sprawdź konfigurację i ceny u Prima-Auto.',
            $label,
            $count,
            $unit
        );
    }

    private function truncate(string $s, int $max): string
    {
        if (mb_strlen($s) <= $max) {
            return $s;
        }
        $cut  = mb_substr($s, 0, $max);
        $last = mb_strrpos($cut, ' ');
        if ($last !== false && $last > $max - 20) {
            $cut = mb_substr($cut, 0, $last);
        }
        return rtrim($cut, " .,—–-") . '…';
    }

    private function siteLogoUrl(): string
    {
        $logo_id = get_theme_mod('custom_logo');
        if (!$logo_id) {
            return '';
        }
        $url = wp_get_attachment_image_url($logo_id, 'full');
        return is_string($url) ? $url : '';
    }

    private function buildBreadcrumbs(array $ctx): array
    {
        $items = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Strona główna', 'item' => home_url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Samochody',     'item' => home_url('/samochody/')],
        ];

        if ($ctx['is_hub'] === 'serie' && !empty($ctx['make_term'])) {
            $make_term = $ctx['make_term'];
            $make_url  = get_term_link($make_term);
            if (!is_wp_error($make_url)) {
                $items[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $this->termLabel($make_term), 'item' => $make_url];
                $items[] = ['@type' => 'ListItem', 'position' => 4, 'name' => $this->termLabel($ctx['term']), 'item' => $ctx['url']];
            }
        } else {
            $items[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $ctx['label'], 'item' => $ctx['url']];
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    private function buildCollectionPage(array $ctx): array
    {
        return [
            '@context'    => 'https://schema.org',
            '@type'       => 'CollectionPage',
            'name'        => $ctx['title'],
            'url'         => $ctx['url'],
            'description' => $ctx['desc'],
            'inLanguage'  => 'pl-PL',
            'isPartOf'    => [
                '@type' => 'WebSite',
                'name'  => 'Prima-Auto',
                'url'   => home_url('/'),
            ],
        ];
    }

    private function buildItemList(array $ctx): ?array
    {
        $q = new WP_Query([
            'post_type'      => 'listings',
            'post_status'    => 'publish',
            'posts_per_page' => self::ITEMLIST_LIMIT,
            'no_found_rows'  => true,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'tax_query'      => [[
                'taxonomy' => $ctx['term']->taxonomy,
                'field'    => 'term_id',
                'terms'    => $ctx['term']->term_id,
            ]],
        ]);

        if (empty($q->posts)) {
            return null;
        }

        $items = [];
        $pos   = 1;
        foreach ($q->posts as $post) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $pos++,
                'url'      => get_permalink($post),
                'name'     => get_the_title($post),
            ];
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'ItemList',
            'name'            => $ctx['label'] . ' — oferty',
            'numberOfItems'   => $ctx['count'],
            'itemListElement' => $items,
        ];
    }

    /**
     * v0.32.74: Car schema dla hub modelu (serie) — spec techniczny z AsiaAuto_Spec
     * (additionalProperty + vehicleConfiguration). Uzupełnia Product+AggregateOffer
     * (cena) o dane techniczne dla Google/AI. count=0 → fallback snapshot (AsiaAuto_Spec).
     */
    private function buildCarForSerieHub(array $ctx): ?array
    {
        if (($ctx['is_hub'] ?? '') !== 'serie' || empty($ctx['term']) || !class_exists('AsiaAuto_Spec')) {
            return null;
        }
        $car = AsiaAuto_Spec::renderJsonLd((int) $ctx['term']->term_id);
        if (!$car) {
            return null;
        }
        $car['url'] = $ctx['url'];
        if (!empty($ctx['og_image'])) {
            $car['image'] = $ctx['og_image'];
        }
        return $car;
    }

    /**
     * v0.32.23: Product + AggregateOffer dla hub modelu (taxonomy=serie).
     * Dla hub marki zwraca null (zbyt szeroka grupa, niska trafność dla Product Snippet).
     */
    private function buildProductForSerieHub(array $ctx): ?array
    {
        if (($ctx['is_hub'] ?? '') !== 'serie' || empty($ctx['make_term']) || (int) ($ctx['count'] ?? 0) === 0) {
            return null;
        }

        $stats = $this->getPriceStatsForTerm($ctx['term']);
        if ($stats === null) {
            return null;
        }

        $product = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => $this->termLabel($ctx['term']),
            'url'         => $ctx['url'],
            'description' => $ctx['desc'] ?? '',
            'brand'       => [
                '@type' => 'Brand',
                'name'  => $this->termLabel($ctx['make_term']),
            ],
            'offers'      => [
                '@type'         => 'AggregateOffer',
                'priceCurrency' => 'PLN',
                'lowPrice'      => $stats['low'],
                'highPrice'     => $stats['high'],
                'offerCount'    => $stats['count'],
                'availability'  => 'https://schema.org/InStock',
            ],
        ];

        if (!empty($ctx['og_image'])) {
            $product['image'] = $ctx['og_image'];
        }

        return $product;
    }

    /**
     * Min/max/count z meta `price` dla wszystkich publish listings w danym term.
     * Zwraca null gdy brak ofert z liczbową ceną > 0.
     */
    private function getPriceStatsForTerm(WP_Term $term): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT MIN(CAST(pm.meta_value AS DECIMAL(15,2))) AS low,
                    MAX(CAST(pm.meta_value AS DECIMAL(15,2))) AS high,
                    COUNT(DISTINCT p.ID) AS cnt
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'price'
             INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
             INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
             WHERE p.post_type = 'listings'
               AND p.post_status = 'publish'
               AND tt.taxonomy = %s
               AND tt.term_id = %d
               AND CAST(pm.meta_value AS DECIMAL(15,2)) > 0",
            $term->taxonomy,
            $term->term_id
        ), ARRAY_A);

        if (!$row || (int) $row['cnt'] === 0) {
            return null;
        }

        return [
            'low'   => (float) $row['low'],
            'high'  => (float) $row['high'],
            'count' => (int) $row['cnt'],
        ];
    }
}

new AsiaAuto_SEO();
