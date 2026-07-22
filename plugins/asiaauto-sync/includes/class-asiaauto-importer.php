<?php

defined('ABSPATH') || exit;

/**
 * Imports car listings from auto-api.com into Motors Pro CPT.
 *
 * Meta keys and taxonomy slugs verified against Motors Pro (asiaauto.pl, post #21).
 * Motors Pro stores taxonomy values BOTH as terms AND as post meta (slug = meta_key).
 *
 * Updated 2026-03-19: adjusted for real API data (lowercase enums, Chinese cities,
 * images as comma-separated string, new fields).
 * Updated 2026-03-20: image download pipeline (downloadAndStore with SEO naming).
 * Updated 2026-03-20: DeepL description translation (ZH→PL) at import time.
 * Updated 2026-03-27: updateListing now downloads images if gallery is empty.
 * Updated 2026-03-31: slugify() helper — '+' → '-plus' before sanitize_title() to prevent
 *                     slug collisions (e.g. "XPeng P7+" vs "XPeng P7" → xpeng-p7-plus vs xpeng-p7).
 * Updated 2026-04-04: v0.23.0 — translateModel() in importListing() + setTaxonomies()
 * Updated 2026-04-15: v0.30.6 — updateListing() zawsze odświeża _asiaauto_original_price
 *                     (cena CNY z aukcji) PRZED applyToListing() — żeby meta referencyjna
 *                     była aktualna nawet gdy aktywny jest override CIF USD. applyToListing
 *                     decyduje samodzielnie czy użyć tej meta (MODE CNY) czy zignorować
 *                     na rzecz CIF USD (MODE override).
 */
class AsiaAuto_Importer {

    private AsiaAuto_Translator $translator;
    private AsiaAuto_Media $media;

    // Motors Pro CPT — verified
    private string $post_type = 'listings';

    // Motors Pro meta keys — verified against post #21
    private string $meta_price            = 'price';
    private string $meta_sale_price       = 'sale_price';
    private string $meta_mileage          = 'mileage';
    private string $meta_engine           = 'engine';
    private string $meta_fuel_consumption = 'fuel-consumption';
    private string $meta_fuel_economy     = 'fuel-economy';
    private string $meta_location         = 'stm_car_location';
    private string $meta_vin              = 'vin_number';
    private string $meta_stock            = 'stock_number';
    private string $meta_registration     = 'registration_date';

    // Motors Pro taxonomy slugs — verified
    private string $tax_make         = 'make';
    private string $tax_model        = 'serie';
    private string $tax_body         = 'body';
    private string $tax_fuel         = 'fuel';
    private string $tax_transmission = 'transmission';
    private string $tax_drive        = 'drive';
    private string $tax_ext_color    = 'exterior-color';
    private string $tax_int_color    = 'interior-color';
    private string $tax_year         = 'ca-year';
    private string $tax_condition    = 'condition';

    public function __construct(AsiaAuto_Translator $translator, AsiaAuto_Media $media) {
        $this->translator = $translator;
        $this->media = $media;
    }

    /**
     * Import a single listing from API data.
     *
     * @param array  $data    Listing data from API
     * @param string $source  Source platform: 'dongchedi' or 'che168'
     * @return int|null       Created post ID or null on failure
     */
    public function importListing(array $data, string $source, bool $force = false, string $status = 'publish'): ?int {
        $inner_id = $data['inner_id'] ?? '';

        if (empty($inner_id)) {
            AsiaAuto_Logger::error("Listing has no inner_id, skipping");
            return null;
        }

        // Bramka pustej wydmuszki: auto-api zwraca rekord z samą ceną (mark/model/
        // year/extra_prep puste) dla ofert sprzedanych/usuniętych na źródle. Bez tej
        // bramki force=true (import ręczny) tworzył śmieć: tytuł „Listing {id}",
        // slug = sam post_id, zero parametrów i taksonomii. Dotyczy WSZYSTKICH ścieżek.
        if (self::isEmptyShell($data)) {
            AsiaAuto_Logger::warning(
                "Skipping {$source}:{$inner_id} — API zwróciło pustą wydmuszkę "
                . "(brak mark/model; oferta prawdopodobnie sprzedana lub usunięta na źródle)"
            );
            return null;
        }

        if (!$force && !$this->isAllowedByConfig($data, $source)) {
            return null;
        }

        $existing = $this->findByInnerId($inner_id, $source);
        if ($existing) {
            AsiaAuto_Logger::info("Listing {$source}:{$inner_id} already exists (post #{$existing}), skipping");
            return $existing;
        }

        $markCN  = $data['mark'] ?? '';
        $modelCN = $data['model'] ?? '';
        $year    = $data['year'] ?? '';

        // v6.1 mapping: gdy para (markCN, modelCN) zmapowana, użyj EU-nazw do title + slug.
        // Bez tego taksonomia była EU, ale post_title nadal surowy CN (np. „GAC Trumpchi Trumpchi GS4").
        $eu = ($markCN !== '' && $modelCN !== '')
            ? AsiaAuto_Mapping::getEuForCn($markCN, $modelCN)
            : null;

        if ($eu !== null) {
            $mark           = $eu['mark_eu'];
            $model          = $eu['serie_eu'];
            $model_for_slug = $model;
        } else {
            $mark  = $markCN;
            $model = $this->translator->translateModel($modelCN);

            if (!empty($mark) && !empty($model) && stripos($model, $mark) === 0) {
                $model_for_slug = trim(substr($model, strlen($mark)));
            } else {
                $model_for_slug = $model;
            }
        }

        $complectation_raw = $data['complectation'] ?? '';
        $complectation = $this->translator->translateComplectation($complectation_raw);

        $title = trim("{$mark} {$model_for_slug} {$year} {$complectation}");
        if (empty($title) || $title === $complectation) {
            $title = "Listing {$inner_id}";
        }

        $description_original = $data['description'] ?? '';
        $description_pl = '';
        $translation_status = 'none';

        if (!empty($description_original)) {
            $result = $this->translator->translateDescription($description_original);

            if ($result['translated'] !== null) {
                $description_pl = $result['translated'];
                $translation_status = 'ok';
            } else {
                $description_pl = $description_original;
                $translation_status = 'failed';
                AsiaAuto_Logger::warning(
                    "Description translation failed for {$source}:{$inner_id}: {$result['error']}"
                );
            }
        } else {
            $translation_status = 'empty';
        }

        // v0.32.34 W1: Preflight image-URL check. If API zwraca martwe URL-e
        // (cache stale po stronie auto-api.com), nie tworzymy listingu wcale —
        // i tak skończyłby w koszu jako ghost-offer (vide diag missing-images).
        // Manual import (force=true) pomija check — klient świadomie wskazał ofertę.
        $images = $this->parseImages($data['images'] ?? '');
        if (!$force && !empty($images) && self::allUrlsExpired($images)) {
            AsiaAuto_Logger::warning(
                "Skipping {$source}:{$inner_id} — all " . count($images)
                . " image URLs already expired (API cache stale, will retry next sync)"
            );
            return null;
        }

        $post_id = wp_insert_post([
            'post_type'    => $this->post_type,
            'post_status'  => $status,
            'post_title'   => $title,
            'post_content' => $description_pl,
        ], true);

        if (is_wp_error($post_id)) {
            AsiaAuto_Logger::error("Failed to create post for {$source}:{$inner_id}: {$post_id->get_error_message()}");
            return null;
        }

        $canonical_slug = self::buildListingSlug($mark, $model_for_slug, (string) $year, $post_id);
        wp_update_post(['ID' => $post_id, 'post_name' => $canonical_slug]);

        update_post_meta($post_id, '_asiaauto_source', $source);
        update_post_meta($post_id, '_asiaauto_inner_id', $inner_id);
        update_post_meta($post_id, '_asiaauto_url', $data['url'] ?? '');
        update_post_meta($post_id, '_asiaauto_last_sync', gmdate('c'));
        update_post_meta($post_id, '_asiaauto_original_price', $data['price'] ?? 0);

        if (!empty($description_original)) {
            update_post_meta($post_id, '_asiaauto_description_original', $description_original);
            update_post_meta($post_id, '_asiaauto_description_status', $translation_status);
        }

        update_post_meta($post_id, 'stm_car_user', 1);

        $this->setMotorsMeta($post_id, $data);
        $this->setTaxonomies($post_id, $data);

        $price_cny = (float) ($data['price'] ?? 0);
        if ($price_cny > 0) {
            AsiaAuto_Price::applyToListing($post_id, $price_cny);
        }

        // $images already parsed earlier (W1 preflight). Reuse without re-parsing.
        if (!empty($images)) {
            $city_pl = !empty($data['city'])
                ? $this->translator->translateCity($data['city'])
                : '';

            $this->media->downloadAndStore($post_id, $images, [
                'mark'     => $data['mark'] ?? '',
                'model'    => $data['model'] ?? '',
                'year'     => $data['year'] ?? '',
                'city_pl'  => $city_pl,
                'inner_id' => $inner_id,
            ]);
        }

        $desc_info = $translation_status === 'ok'
            ? "desc: {$translation_status}"
            : "desc: {$translation_status}";
        AsiaAuto_Logger::info("Imported listing {$source}:{$inner_id} → post #{$post_id} ({$title}, {$desc_info})");

        return $post_id;
    }

    /**
     * Update an existing listing with new data (price change, etc.)
     *
     * Images are downloaded if gallery is empty (handles failed initial downloads
     * or listings where images were added later by seller).
     * Description is NOT re-translated on update.
     * Use 'wp asiaauto translate' to re-translate failed descriptions.
     *
     * @since 0.30.6  Always refresh `_asiaauto_original_price` from API response BEFORE
     *                applyToListing() — pricing-mode-agnostic. Reason: when CIF USD
     *                override is active, applyToListing() exits early on the CIF branch
     *                without touching META_ORIGINAL, which means the meta would
     *                permanently drift away from the actual auction price. Now META_ORIGINAL
     *                is always the latest reference value from Dongchedi/Che168, regardless
     *                of which mode drives the final PLN price.
     */
    public function updateListing(int $post_id, array $data): void {
        // Track price history and recalculate if price changed
        if (isset($data['new_price']) || isset($data['price'])) {
            $new_price_cny = $data['new_price'] ?? $data['price'];
            $this->logPriceChange($post_id, $new_price_cny);

            // v0.30.6: Reference CNY meta is always refreshed — independent of pricing mode.
            // applyToListing() decides whether to use it (MODE CNY) or ignore it
            // (MODE CIF USD override). This keeps the displayed "Wejście" column in the
            // admin preview honest and gives admin a signal when auction price drifts.
            update_post_meta($post_id, '_asiaauto_original_price', (float) $new_price_cny);

            // Pipeline recalc — applyToListing decides the mode.
            AsiaAuto_Price::applyToListing($post_id, (float) $new_price_cny);
        }

        if (isset($data['km_age'])) {
            update_post_meta($post_id, $this->meta_mileage, (int) $data['km_age']);
        }

        if (isset($data['images'])) {
            $images = $this->parseImages($data['images']);
            if (!empty($images)) {
                $this->media->storeImageUrls($post_id, $images);
            }
        }

        $gallery = get_post_meta($post_id, 'gallery', true);
        $gallery_empty = empty($gallery) || (is_array($gallery) && count($gallery) === 0);

        if ($gallery_empty) {
            $this->downloadMissingImages($post_id, $data);
        }

        update_post_meta($post_id, '_asiaauto_last_sync', gmdate('c'));

        $inner_id = get_post_meta($post_id, '_asiaauto_inner_id', true);
        AsiaAuto_Logger::info("Updated listing post #{$post_id} (inner_id: {$inner_id})");
    }

    /**
     * Download images for a listing that has URLs but no gallery.
     */
    public function downloadMissingImages(int $post_id, array $data = []): bool {
        $images = [];
        if (!empty($data['images'])) {
            $images = $this->parseImages($data['images']);
        }
        if (empty($images)) {
            $images = AsiaAuto_Media::getImageUrls($post_id);
        }

        if (empty($images)) {
            return false;
        }

        if (!AsiaAuto_Media::areImagesValid($post_id)) {
            AsiaAuto_Logger::warning("Image URLs expired for post #{$post_id}, skipping download");
            return false;
        }

        $inner_id = get_post_meta($post_id, '_asiaauto_inner_id', true);

        $make_terms = wp_get_object_terms($post_id, 'make', ['fields' => 'names']);
        $model_terms = wp_get_object_terms($post_id, 'serie', ['fields' => 'names']);
        $year_terms = wp_get_object_terms($post_id, 'ca-year', ['fields' => 'names']);

        $mark = !empty($make_terms) && !is_wp_error($make_terms) ? $make_terms[0] : '';
        $model = !empty($model_terms) && !is_wp_error($model_terms) ? $model_terms[0] : '';
        $year = !empty($year_terms) && !is_wp_error($year_terms) ? $year_terms[0] : '';

        $city_zh = get_post_meta($post_id, '_asiaauto_city', true);
        $city_pl = !empty($city_zh) ? $this->translator->translateCity($city_zh) : '';

        $listing = [
            'mark'     => $mark,
            'model'    => $model,
            'year'     => $year,
            'city_pl'  => $city_pl,
            'inner_id' => $inner_id,
        ];

        AsiaAuto_Logger::info("Downloading missing images for post #{$post_id} (" . count($images) . " URLs)");

        $attachment_ids = $this->media->downloadAndStore($post_id, $images, $listing);

        return !empty($attachment_ids);
    }

    /**
     * Find existing post by inner_id and source.
     */
    public function findByInnerId(string $inner_id, string $source): ?int {
        global $wpdb;

        $post_id = $wpdb->get_var($wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_asiaauto_inner_id' AND pm1.meta_value = %s
             INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_asiaauto_source' AND pm2.meta_value = %s
             WHERE p.post_type = %s AND p.post_status NOT IN ('trash', 'auto-draft')
             LIMIT 1",
            $inner_id,
            $source,
            $this->post_type
        ));

        return $post_id ? (int) $post_id : null;
    }

    /**
     * Check if listing matches import config (allowed marks, years, price range).
     */
    // T-186 (2026-07-22): publiczna, by Sync mógł odsiać oferty na danych z /changes
    // PRZED kosztownym getOffer() (che168: ~99% zdarzeń odpada na filtrze).
    // Logika bez zmian — zmieniona wyłącznie widoczność.
    public function isAllowedByConfig(array $data, string $source): bool {
        $config = get_option('asiaauto_import_config', []);
        $source_config = $config[$source] ?? [];

        if (empty($source_config) || empty($source_config['enabled'])) {
            return true;
        }

        if (!empty($source_config['marks'])) {
            $mark = $data['mark'] ?? '';
            if (!in_array($mark, $source_config['marks'], true)) {
                return false;
            }
        }

        $year = (int) ($data['year'] ?? 0);
        if (!empty($source_config['year_from']) && $year < $source_config['year_from']) {
            return false;
        }
        if (!empty($source_config['year_to']) && $year > $source_config['year_to']) {
            return false;
        }

        $km = (int) ($data['km_age'] ?? 0);
        if (!empty($source_config['km_from']) && $km < $source_config['km_from']) {
            return false;
        }
        if (!empty($source_config['km_to']) && $km > $source_config['km_to']) {
            return false;
        }

        $price = (int) ($data['price'] ?? 0);
        if (!empty($source_config['price_from']) && $price < $source_config['price_from']) {
            return false;
        }
        if (!empty($source_config['price_to']) && $price > $source_config['price_to']) {
            return false;
        }

        if (!empty($source_config['city_filter_enabled']) && !empty($source_config['city_filter_cities'])) {
            $city       = $data['city'] ?? '';
            $allowed_zh = array_column($source_config['city_filter_cities'], 'zh');
            if (!in_array($city, $allowed_zh, true)) {
                return false;
            }
        }

        if (!empty($source_config['model_blacklist']) && is_array($source_config['model_blacklist'])) {
            $mark = (string) ($data['mark'] ?? '');
            if ($mark !== '' && !empty($source_config['model_blacklist'][$mark])) {
                $key = $mark . '|' . (string) ($data['model'] ?? '');
                if (in_array($key, (array) $source_config['model_blacklist'][$mark], true)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Parse images field from API — handles both string and array.
     */
    private function parseImages($raw): array {
        if (is_array($raw)) {
            $urls = $raw;
        } elseif (is_string($raw) && !empty($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $urls = $decoded;
            } elseif (str_contains($raw, ',')) {
                $urls = array_map('trim', explode(',', $raw));
            } else {
                $urls = [$raw];
            }
        } else {
            return [];
        }

        return array_values(array_filter($urls, function ($url) {
            return !empty($url) && filter_var($url, FILTER_VALIDATE_URL);
        }));
    }

    /**
     * Set Motors Pro meta fields (non-taxonomy).
     */
    private function setMotorsMeta(int $post_id, array $data): void {
        if (isset($data['displacement'])) {
            $displacement = $data['displacement'];
            update_post_meta($post_id, $this->meta_engine, $displacement);
        }
        if (isset($data['fuel_consumption'])) {
            update_post_meta($post_id, $this->meta_fuel_consumption, $data['fuel_consumption']);
        }

        if (!empty($data['city'])) {
            $location = $this->translator->translateCity($data['city']);
            update_post_meta($post_id, $this->meta_location, $location);
        } elseif (!empty($data['address'])) {
            $location = $this->translator->translateAddress($data['address']);
            update_post_meta($post_id, $this->meta_location, $location);
        }

        if (isset($data['vin'])) {
            update_post_meta($post_id, $this->meta_vin, $data['vin']);
        }

        if (!empty($data['reg_date'])) {
            $timestamp = strtotime($data['reg_date']);
            if ($timestamp) {
                update_post_meta($post_id, $this->meta_registration, date('d/m/Y', $timestamp));
            }
        }

        if (isset($data['horse_power'])) {
            update_post_meta($post_id, '_asiaauto_horse_power', (int) $data['horse_power']);
        }
        if (isset($data['owners_count'])) {
            update_post_meta($post_id, '_asiaauto_owners_count', (int) $data['owners_count']);
        }
        if (isset($data['complectation'])) {
            $compl_raw = sanitize_text_field($data['complectation']);
            $compl_translated = $this->translator->translateComplectation($compl_raw);
            update_post_meta($post_id, '_asiaauto_complectation', $compl_translated);
            if ($compl_translated !== $compl_raw) {
                update_post_meta($post_id, '_asiaauto_complectation_original', $compl_raw);
            }
        }
        if (isset($data['is_dealer'])) {
            update_post_meta($post_id, '_asiaauto_is_dealer', (bool) $data['is_dealer']);
        }
        if (isset($data['seller'])) {
            update_post_meta($post_id, '_asiaauto_seller', sanitize_text_field($data['seller']));
        }
        if (isset($data['salon_id'])) {
            update_post_meta($post_id, '_asiaauto_salon_id', sanitize_text_field($data['salon_id']));
        }
        if (isset($data['region'])) {
            update_post_meta($post_id, '_asiaauto_region', sanitize_text_field($data['region']));
        }
        if (isset($data['city'])) {
            update_post_meta($post_id, '_asiaauto_city', sanitize_text_field($data['city']));
        }
        if (!empty($data['address'])) {
            update_post_meta($post_id, '_asiaauto_address', strip_tags(trim($data['address'])));
        }
        if (isset($data['km_age'])) {
            update_post_meta($post_id, $this->meta_mileage, (int) $data['km_age']);
        }

        if (!empty($data['extra_prep'])) {
            $ep = is_array($data['extra_prep']) ? $data['extra_prep'] : json_decode($data['extra_prep'], true);
            if (is_array($ep)) {
                update_post_meta($post_id, '_asiaauto_extra_prep', wp_slash(wp_json_encode($ep)));
            }
        }

        if (!empty($data['equipment'])) {
            $equipment = is_array($data['equipment']) ? $data['equipment'] : [$data['equipment']];
            if (!empty($equipment)) {
                update_post_meta($post_id, 'additional_features', implode(',', $equipment));
            }
        }
    }

    private function setTaxonomies(int $post_id, array $data): void {
        // v6.1 mapowanie CN→EU: gdy mamy parę (mark, model) z mapy, użyj EU-nazw
        $markCN  = (string) ($data['mark'] ?? '');
        $modelCN = (string) ($data['model'] ?? '');
        $eu = ($markCN !== '' && $modelCN !== '')
            ? AsiaAuto_Mapping::getEuForCn($markCN, $modelCN)
            : null;

        $markFinal  = $eu['mark_eu']  ?? $markCN;
        $serieFinal = $eu['serie_eu'] ?? '';

        if ($markFinal !== '') {
            $this->setTaxonomyAndMeta($post_id, $this->tax_make, $markFinal);
        }

        if ($serieFinal !== '') {
            // Mapowanie v6.1 — EU serie label (np. "ATTO 3 (Yuan PLUS)") z parent=markFinal
            // 4-ty arg = $eu['slug'] żeby importer użył keeper-slug zamiast slugify(label).
            // Bez tego label "ATTO 3 (Yuan PLUS)" → "atto-3-yuan-plus" orphan zamiast "atto-3" keeper.
            // T-190 guard: serie idzie przez wariant make-aware (kolizje slugów między markami
            // typu m8 AITO/GAC + drift slugów mapowania po merge'ach — nie odtwarzaj duplikatów).
            $serieSlug = isset($eu['slug']) ? (string) $eu['slug'] : '';
            $this->setSerieTaxonomyAndMeta($post_id, $serieFinal, $serieSlug);
        } elseif (!empty($data['model'])) {
            $model_translated = $this->translator->translateModel($data['model']);
            $this->setSerieTaxonomyAndMeta($post_id, $model_translated, '');
        }

        if (!empty($data['year'])) {
            $this->setTaxonomyAndMeta($post_id, $this->tax_year, (string) $data['year']);
        }

        if (!empty($data['body_type']) && $data['body_type'] !== '-') {
            $body_pl = $this->translator->translate('body_type', $data['body_type']);
            if (!empty($body_pl)) {
                $this->setTaxonomyAndMeta($post_id, $this->tax_body, $body_pl, $data['body_type']);
            }
        }

        if (!empty($data['engine_type']) && $data['engine_type'] !== '-') {
            $fuel_pl = $this->translator->translate('engine_type', $data['engine_type']);
            if (!empty($fuel_pl)) {
                $this->setTaxonomyAndMeta($post_id, $this->tax_fuel, $fuel_pl, $data['engine_type']);
            }
        }

        if (!empty($data['transmission_type']) && $data['transmission_type'] !== '-') {
            $trans_pl = $this->translator->translate('transmission', $data['transmission_type']);
            if (!empty($trans_pl)) {
                $this->setTaxonomyAndMeta($post_id, $this->tax_transmission, $trans_pl, $data['transmission_type']);
            }
        }

        if (!empty($data['drive_type']) && $data['drive_type'] !== '-') {
            $drive_pl = $this->translator->translate('drive_type', $data['drive_type']);
            if (!empty($drive_pl)) {
                $this->setTaxonomyAndMeta($post_id, $this->tax_drive, $drive_pl, $data['drive_type']);
            }
        }

        if (!empty($data['color']) && $data['color'] !== '--' && $data['color'] !== 'other') {
            $color_pl = $this->translator->translate('color', $data['color']);
            if (!empty($color_pl)) {
                $this->setTaxonomyAndMeta($post_id, $this->tax_ext_color, $color_pl, $data['color']);
            }
        }

        if (!empty($data['interior_color'])) {
            $int_color_pl = $this->translator->translate('color', $data['interior_color']);
            if (!empty($int_color_pl)) {
                $this->setTaxonomyAndMeta($post_id, $this->tax_int_color, $int_color_pl, $data['interior_color']);
            }
        }

        $section = $data['section'] ?? 'used';
        $condition = ($section === 'new') ? 'Nowy' : 'Używany';
        $this->setTaxonomyAndMeta($post_id, $this->tax_condition, $condition, ($section === 'new') ? 'new' : 'used');

        // Huby SEO (since 0.31.0): term_meta primary_make_slug dla serie — pozwala
        // filtrowi term_link generować /samochody/<mark>/<serie>/ bez kontekstu.
        $this->updateSerieprimaryMake($post_id);

        // Regen hub title + description (auto-aktualizacja count + min/max price w SERP).
        // Hook implementacja: AsiaAuto_HubTitleGenerator (since 0.32.43).
        do_action('asiaauto_after_set_taxonomies', $post_id);
    }

    /**
     * Po ustawieniu taksonomii — zapisz slug marki jako primary_make na termie serie.
     * Najprostsza strategia: każdy import nadpisuje. Batch `diag/backfill-serie-primary-make.php`
     * po wdrożeniu rozstrzyga to po liczbie listingów (najczęstszy make wygrywa).
     *
     * @since 0.31.0
     */
    private function updateSerieprimaryMake(int $post_id): void {
        $make_terms  = wp_get_object_terms($post_id, $this->tax_make,  ['fields' => 'slugs']);
        $serie_terms = wp_get_object_terms($post_id, $this->tax_model, ['fields' => 'ids']);

        if (is_wp_error($make_terms) || is_wp_error($serie_terms)) return;
        if (empty($make_terms) || empty($serie_terms)) return;

        $primary_make_slug = $make_terms[0];
        foreach ($serie_terms as $serie_id) {
            update_term_meta((int) $serie_id, '_asiaauto_primary_make_slug', $primary_make_slug);
        }
    }

    private static function slugify(string $value): string {
        $value = str_replace('+', '-plus', $value);
        $value = preg_replace('/[^\x00-\x7F]+/', '', $value);
        $value = preg_replace('/[-\s]{2,}/', ' ', $value);
        return sanitize_title(trim($value));
    }

    private function setTaxonomyAndMeta(int $post_id, string $taxonomy, string $value, string $api_value = ''): void {
        $slug = !empty($api_value) ? sanitize_title($api_value) : self::slugify($value);

        $term = get_term_by('slug', $slug, $taxonomy);
        if (!$term) {
            $result = wp_insert_term($value, $taxonomy, ['slug' => $slug]);
            $term_id = is_wp_error($result) ? null : (int) $result['term_id'];
        } else {
            $term_id = (int) $term->term_id;
        }

        if ($term_id) {
            wp_set_object_terms($post_id, $term_id, $taxonomy);
        } else {
            wp_set_object_terms($post_id, $value, $taxonomy);
        }

        update_post_meta($post_id, $taxonomy, $slug);
    }

    /**
     * T-190 guard (2026-07-07): make-aware wariant setTaxonomyAndMeta WYŁĄCZNIE dla `serie`.
     *
     * Naprawia trzy wady historycznej ścieżki (dowody: ADR 2026-07-07-t190-galaxy-pod-geely,
     * raport t190-diagnoza — 19 wzorców / ~106 aut na złych termach):
     *  1. lookup po slugu był GLOBALNY w taksonomii → kolizje slugów między markami
     *     (m8 AITO/GAC, 07 Avatr/WEY, n7 Denza/Nissan…) przypinały auto do CUDZEJ marki;
     *  2. nowy term powstawał BEZ parenta → sieroty (hub „Nie znaleziono modelu");
     *  3. slug ze stale mapowania odtwarzał termy skasowane merge'ami (T-019 galaxy-*)
     *     — teraz łapie je dopasowanie po znormalizowanej nazwie wśród dzieci marki.
     *
     * Zachowanie zachowawcze: bez termu marki na poście → fallback 1:1 na starą ścieżkę.
     *
     * @since 0.33.16
     */
    private function setSerieTaxonomyAndMeta(int $post_id, string $value, string $api_slug): void {
        $slug = !empty($api_slug) ? sanitize_title($api_slug) : self::slugify($value);

        // Kontekst marki: term make ustawiony chwilę wcześniej w setTaxonomies().
        $make_terms = wp_get_object_terms($post_id, $this->tax_make);
        $make_term  = (!is_wp_error($make_terms) && !empty($make_terms)) ? $make_terms[0] : null;

        if (!$make_term instanceof WP_Term) {
            // Brak marki = brak kontekstu do disambiguacji → stare zachowanie.
            $this->setTaxonomyAndMeta($post_id, $this->tax_model, $value, $api_slug);
            return;
        }

        $term_id = $this->resolveSerieTermId($slug, $value, $make_term);

        if (!$term_id) {
            $result = wp_insert_term($value, $this->tax_model, [
                'slug'   => $slug,
                'parent' => (int) $make_term->term_id,
            ]);
            if (is_wp_error($result)) {
                AsiaAuto_Logger::warning(
                    "Serie guard: wp_insert_term('{$value}', slug '{$slug}', parent {$make_term->slug}) failed: "
                    . $result->get_error_message() . " — fallback na starą ścieżkę"
                );
                $this->setTaxonomyAndMeta($post_id, $this->tax_model, $value, $api_slug);
                return;
            }
            $term_id = (int) $result['term_id'];
            AsiaAuto_Logger::info(
                "Serie guard: utworzono term #{$term_id} '{$value}' parent={$make_term->slug} (post {$post_id})"
            );
        }

        wp_set_object_terms($post_id, $term_id, $this->tax_model);

        // Meta `serie` = REALNY slug użytego termu (WP mógł zunikalnić slug przy kolizji
        // pod innym parentem) — wcześniej zapisywany był surowy slug z mapowania.
        $term = get_term($term_id, $this->tax_model);
        $final_slug = ($term instanceof WP_Term) ? $term->slug : $slug;
        update_post_meta($post_id, $this->tax_model, $final_slug);

        // primary_make_slug zgodny z parentem — updateSerieprimaryMake() i tak nadpisze
        // tą samą wartością; tu gwarancja spójności od pierwszego zapisu.
        update_term_meta($term_id, '_asiaauto_primary_make_slug', $make_term->slug);
    }

    /**
     * Szuka istniejącego termu serie W OBRĘBIE marki (make-aware):
     *  1. dziecko marki o dokładnie tym slugu;
     *  2. dziecko marki o tej samej znormalizowanej nazwie (łapie drift slugów mapowania,
     *     np. mapping `galaxy-starship-8-phev` vs kanoniczny `starship-8-phev`);
     *     dopasowanie brzegowe „z prefiksem marki/sub-marki lub bez" akceptowane
     *     TYLKO gdy jednoznaczne (dokładnie 1 kandydat) — inaczej null (utworzy nowy,
     *     rekoncyliacja T-190 wyłapie w raporcie zamiast cichego złego przypięcia).
     *
     * @since 0.33.16
     */
    private function resolveSerieTermId(string $slug, string $label, WP_Term $make_term): ?int {
        $children = get_terms([
            'taxonomy'   => $this->tax_model,
            'parent'     => (int) $make_term->term_id,
            'hide_empty' => false,
        ]);
        if (is_wp_error($children) || empty($children)) {
            return null;
        }

        // 1. Slug exact pod tą marką.
        foreach ($children as $child) {
            if ($child->slug === $slug) {
                return (int) $child->term_id;
            }
        }

        // 2. Znormalizowana nazwa: exact ma priorytet, potem jednoznaczny wariant brzegowy.
        $needle   = self::slugify($label);
        $boundary = [];
        foreach ($children as $child) {
            $cand = self::slugify($child->name);
            if ($cand === '' || $needle === '') {
                continue;
            }
            if ($cand === $needle) {
                return (int) $child->term_id;
            }
            // „Galaxy E5" vs „E5", „Tank 500 Hi4-T" vs „500 Hi4-T" — jeden jest
            // sufiksem drugiego na granicy tokenu (różnica = prefiks marki/sub-marki).
            if (self::isTokenSuffix($cand, $needle) || self::isTokenSuffix($needle, $cand)) {
                $boundary[] = (int) $child->term_id;
            }
        }
        if (count($boundary) === 1) {
            return $boundary[0];
        }
        if (count($boundary) > 1) {
            AsiaAuto_Logger::warning(
                "Serie guard: niejednoznaczne dopasowanie '{$label}' pod {$make_term->slug} "
                . '(kandydaci: ' . implode(',', $boundary) . ') — tworzę osobny term'
            );
        }
        return null;
    }

    /** Czy $shorter == końcówka $longer na granicy tokenu ('e5' vs 'galaxy-e5'). @since 0.33.16 */
    private static function isTokenSuffix(string $shorter, string $longer): bool {
        return strlen($longer) > strlen($shorter) + 1
            && substr($longer, -(strlen($shorter) + 1)) === '-' . $shorter;
    }

    /**
     * Czy API zwróciło pustą wydmuszkę (oferta sprzedana/usunięta na źródle —
     * auto-api trzyma rekord z ceną, ale pola opisowe puste).
     * Bez marki i modelu nie da się zbudować ani tytułu, ani slugu, ani taksonomii.
     *
     * @since 0.32.68
     */
    public static function isEmptyShell(array $data): bool {
        $mark  = trim((string) ($data['mark'] ?? ''));
        $model = trim((string) ($data['model'] ?? ''));
        return $mark === '' && $model === '';
    }

    public static function buildListingSlug(string $mark, string $model, string $year, int $post_id): string {
        $parts = array_filter([
            self::slugify($mark),
            self::slugify($model),
            sanitize_title($year),
            (string) $post_id,
        ], fn($p) => $p !== '');

        return implode('-', $parts);
    }

    private function logPriceChange(int $post_id, $new_price): void {
        $history_json = get_post_meta($post_id, '_asiaauto_price_history', true);
        $history = $history_json ? json_decode($history_json, true) : [];

        $history[] = [
            'price' => $new_price,
            'date'  => gmdate('c'),
        ];

        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }

        update_post_meta($post_id, '_asiaauto_price_history', wp_json_encode($history));
    }

    /**
     * Check if all signed image URLs in array have expired (x-expires < now).
     *
     * Returns true tylko gdy KAŻDY URL-e z parametrem x-expires jest po terminie.
     * URL-e bez x-expires (che168 itd.) są ignorowane — false-positive ochrona.
     *
     * Używane jako preflight w importListing — zapobiega tworzeniu listingów
     * skazanych na cleanup przez diag missing-images.
     *
     * @since 0.32.34
     */
    public static function allUrlsExpired(array $urls): bool {
        if (empty($urls)) return false;
        $now = time();
        $checked = 0;
        foreach ($urls as $url) {
            if (!is_string($url)) continue;
            if (!preg_match('/x-expires=(\d+)/', $url, $m)) continue;
            $checked++;
            if ((int) $m[1] > $now) return false; // at least one fresh
        }
        return $checked > 0;
    }

    // =========================================================================
    // DRY-RUN (T-185) — CZYSTE METODY ADDYTYWNE, BEZ EFEKTÓW UBOCZNYCH.
    // Używane WYŁĄCZNIE przez stronę „Import z Che168" (podgląd symulacji).
    // importListing()/setMotorsMeta()/setTaxonomies() (ścieżka realnego zapisu,
    // strefa krucha §3.2) NIE wołają tych metod — replikują logikę 1:1, bez ryzyka
    // regresji. Wierność udowodniona smoke'iem buildPlan vs realny listing dongchedi
    // (title 6/6, meta 88/88, terms 54/54).
    // T-186 (2026-06-17): normalizacja tożsamości Che168 PRZENIESIONA do adaptera
    // (AsiaAuto_Che168_Adapter::normalize → AsiaAuto_Mapping::canonicalKeyForSource),
    // więc mark/model wchodzą tu już w kształcie klucza brand-mappingu. Dry-run i realny
    // importListing używają tego samego getEuForCn() — strefa krucha NIE wymaga edycji.
    // Silnikiem kanonizacji jest resolveChe168() (przez canonicalKeyForSource) w adapterze.
    // =========================================================================

    /**
     * Wylicza tożsamość (mark/serie/model_for_slug/title/mapped/slug_pattern) BEZ zapisu.
     * Logika 1:1 z importListing 99-130, ale resolver zależny od źródła (che168 dry-run).
     */
    public function computeIdentity(array $data, string $source): array {
        $inner_id = $data['inner_id'] ?? '';
        $markCN   = $data['mark'] ?? '';
        $modelCN  = $data['model'] ?? '';
        $year     = $data['year'] ?? '';
        $engine   = (string) ($data['engine_type'] ?? '');

        // Po normalizacji-przy-wejściu (adapter Che168, T-186) mark/model są już w kształcie
        // klucza brand-mappingu → zwykłe getEuForCn(), 1:1 z importListing l.106 (dry-run == import).
        $eu = ($markCN !== '' && $modelCN !== '')
            ? AsiaAuto_Mapping::getEuForCn($markCN, $modelCN)
            : null;

        if ($eu !== null) {
            $mark           = $eu['mark_eu'];
            $serie          = $eu['serie_eu'];
            $model_for_slug = $serie;
        } else {
            $mark  = $markCN;
            $serie = $this->translator->translateModel($modelCN);
            if (!empty($mark) && !empty($serie) && stripos($serie, $mark) === 0) {
                $model_for_slug = trim(substr($serie, strlen($mark)));
            } else {
                $model_for_slug = $serie;
            }
        }

        $complectation = $this->translator->translateComplectation($data['complectation'] ?? '');
        $title = trim("{$mark} {$model_for_slug} {$year} {$complectation}");
        if (empty($title) || $title === $complectation) {
            $title = "Listing {$inner_id}";
        }

        $slug_pattern = self::buildListingSlug($mark, $model_for_slug, (string) $year, 0);
        $slug_pattern = preg_replace('/(^|-)0$/', '${1}{post_id}', $slug_pattern);

        return [
            'mark'           => $mark,
            'serie'          => $serie,
            'model_for_slug' => $model_for_slug,
            'title'          => $title,
            'mapped'         => $eu !== null,
            'slug_pattern'   => $slug_pattern,
        ];
    }

    /** Wylicza meta Motors Pro jako [meta_key => value] BEZ zapisu (1:1 z setMotorsMeta). */
    public function computeMeta(array $data): array {
        $meta = [];
        if (isset($data['displacement']))      $meta[$this->meta_engine] = $data['displacement'];
        if (isset($data['fuel_consumption']))  $meta[$this->meta_fuel_consumption] = $data['fuel_consumption'];
        if (!empty($data['city'])) {
            $meta[$this->meta_location] = $this->translator->translateCity($data['city']);
        } elseif (!empty($data['address'])) {
            $meta[$this->meta_location] = $this->translator->translateAddress($data['address']);
        }
        if (isset($data['vin'])) $meta[$this->meta_vin] = $data['vin'];
        if (!empty($data['reg_date'])) {
            $ts = strtotime($data['reg_date']);
            if ($ts) $meta[$this->meta_registration] = date('d/m/Y', $ts);
        }
        if (isset($data['horse_power']))  $meta['_asiaauto_horse_power']  = (int) $data['horse_power'];
        if (isset($data['owners_count'])) $meta['_asiaauto_owners_count'] = (int) $data['owners_count'];
        if (isset($data['complectation'])) {
            $compl_raw = sanitize_text_field($data['complectation']);
            $compl_translated = $this->translator->translateComplectation($compl_raw);
            $meta['_asiaauto_complectation'] = $compl_translated;
            if ($compl_translated !== $compl_raw) {
                $meta['_asiaauto_complectation_original'] = $compl_raw;
            }
        }
        if (isset($data['is_dealer'])) $meta['_asiaauto_is_dealer'] = (bool) $data['is_dealer'];
        if (isset($data['seller']))    $meta['_asiaauto_seller']    = sanitize_text_field($data['seller']);
        if (isset($data['salon_id']))  $meta['_asiaauto_salon_id']  = sanitize_text_field($data['salon_id']);
        if (isset($data['region']))    $meta['_asiaauto_region']    = sanitize_text_field($data['region']);
        if (isset($data['city']))      $meta['_asiaauto_city']      = sanitize_text_field($data['city']);
        if (!empty($data['address']))  $meta['_asiaauto_address']   = strip_tags(trim($data['address']));
        if (isset($data['km_age']))    $meta[$this->meta_mileage]   = (int) $data['km_age'];
        if (!empty($data['extra_prep'])) {
            $ep = is_array($data['extra_prep']) ? $data['extra_prep'] : json_decode($data['extra_prep'], true);
            if (is_array($ep)) $meta['_asiaauto_extra_prep'] = wp_slash(wp_json_encode($ep));
        }
        if (!empty($data['equipment'])) {
            $equipment = is_array($data['equipment']) ? $data['equipment'] : [$data['equipment']];
            if (!empty($equipment)) $meta['additional_features'] = implode(',', $equipment);
        }
        return $meta;
    }

    /** Wylicza termy [taxonomy,value,slug,api_value,exists] BEZ tworzenia (1:1 z setTaxonomies). */
    public function computeTerms(array $data, string $source): array {
        $markCN  = (string) ($data['mark'] ?? '');
        $modelCN = (string) ($data['model'] ?? '');
        $engine  = (string) ($data['engine_type'] ?? '');
        // jw. — dane Che168 znormalizowane w adapterze; getEuForCn 1:1 z setTaxonomies l.514.
        $eu = ($markCN !== '' && $modelCN !== '')
            ? AsiaAuto_Mapping::getEuForCn($markCN, $modelCN)
            : null;

        $markFinal  = $eu['mark_eu']  ?? $markCN;
        $serieFinal = $eu['serie_eu'] ?? '';

        $raw = [];
        if ($markFinal !== '') $raw[] = [$this->tax_make, $markFinal, ''];
        if ($serieFinal !== '') {
            $raw[] = [$this->tax_model, $serieFinal, isset($eu['slug']) ? (string) $eu['slug'] : ''];
        } elseif (!empty($data['model'])) {
            $raw[] = [$this->tax_model, $this->translator->translateModel($data['model']), ''];
        }
        if (!empty($data['year'])) $raw[] = [$this->tax_year, (string) $data['year'], ''];
        if (!empty($data['body_type']) && $data['body_type'] !== '-') {
            $v = $this->translator->translate('body_type', $data['body_type']);
            if (!empty($v)) $raw[] = [$this->tax_body, $v, $data['body_type']];
        }
        if (!empty($data['engine_type']) && $data['engine_type'] !== '-') {
            $v = $this->translator->translate('engine_type', $data['engine_type']);
            if (!empty($v)) $raw[] = [$this->tax_fuel, $v, $data['engine_type']];
        }
        if (!empty($data['transmission_type']) && $data['transmission_type'] !== '-') {
            $v = $this->translator->translate('transmission', $data['transmission_type']);
            if (!empty($v)) $raw[] = [$this->tax_transmission, $v, $data['transmission_type']];
        }
        if (!empty($data['drive_type']) && $data['drive_type'] !== '-') {
            $v = $this->translator->translate('drive_type', $data['drive_type']);
            if (!empty($v)) $raw[] = [$this->tax_drive, $v, $data['drive_type']];
        }
        if (!empty($data['color']) && $data['color'] !== '--' && $data['color'] !== 'other') {
            $v = $this->translator->translate('color', $data['color']);
            if (!empty($v)) $raw[] = [$this->tax_ext_color, $v, $data['color']];
        }
        if (!empty($data['interior_color'])) {
            $v = $this->translator->translate('color', $data['interior_color']);
            if (!empty($v)) $raw[] = [$this->tax_int_color, $v, $data['interior_color']];
        }
        $section = $data['section'] ?? 'used';
        $raw[] = [$this->tax_condition, ($section === 'new') ? 'Nowy' : 'Używany', ($section === 'new') ? 'new' : 'used'];

        // T-190 guard parity: dla serie `exists` liczone make-aware,
        // 1:1 z resolveSerieTermId() w realnej ścieżce (dry-run == import).
        $make_term = ($markFinal !== '')
            ? get_term_by('slug', self::slugify($markFinal), $this->tax_make)
            : false;

        $terms = [];
        foreach ($raw as [$taxonomy, $value, $api_value]) {
            $slug = !empty($api_value) ? sanitize_title($api_value) : self::slugify($value);
            if ($taxonomy === $this->tax_model && $make_term instanceof WP_Term) {
                $exists = $this->resolveSerieTermId($slug, $value, $make_term) !== null;
            } else {
                $exists = (bool) get_term_by('slug', $slug, $taxonomy);
            }
            $terms[] = [
                'taxonomy'  => $taxonomy,
                'value'     => $value,
                'slug'      => $slug,
                'api_value' => $api_value,
                'exists'    => $exists,
            ];
        }
        return $terms;
    }

    /**
     * Czysta symulacja importu (dry-run). To co zwraca == co importListing() zapisałby
     * (logika replikowana 1:1). Bez efektów ubocznych — żadnych wpisów do bazy/termów.
     */
    public function buildPlan(array $data, string $source): array {
        $identity = $this->computeIdentity($data, $source);
        $meta     = $this->computeMeta($data);
        $terms    = $this->computeTerms($data, $source);
        $images   = $this->parseImages($data['images'] ?? '');

        $extra_prep = [];
        if (!empty($data['extra_prep'])) {
            $extra_prep = is_array($data['extra_prep'])
                ? $data['extra_prep']
                : (json_decode($data['extra_prep'], true) ?: []);
        }

        $warnings = [];
        if (self::isEmptyShell($data)) {
            $warnings[] = 'Pusta wydmuszka (brak mark/model) — realny import zostałby pominięty.';
        }
        if (!$identity['mapped']) {
            $warnings[] = 'Marka/model bez mapowania → hub może być sierotą (kandydat do che168-model-map).';
        }
        if (empty($images)) {
            $warnings[] = 'Brak zdjęć w danych API.';
        }

        return [
            'title'              => $identity['title'],
            'slug_pattern'       => $identity['slug_pattern'],
            'mark'               => $identity['mark'],
            'serie'              => $identity['serie'],
            'mapped'             => $identity['mapped'],
            'terms'              => $terms,
            'meta'               => $meta,
            'extra_prep'         => $extra_prep,
            'images'             => $images,
            'price_cny'          => (float) ($data['price'] ?? 0),
            'description_status' => empty($data['description'] ?? '') ? 'empty' : 'pending',
            'warnings'           => $warnings,
        ];
    }
}
