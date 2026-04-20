<?php
/**
 * Jednorazowa migracja: w templacie Elementor "asiaauto-single-listing" (ID 101874)
 * podmienia 2 widgety typu "html" (aa-incl + aa-why) na natywne icon-list Elementora,
 * identyczne stylistycznie jak sąsiedni widget "info pages" (dot-circle, Inter 14px bold,
 * granat #1B2A4A, szary #718096). Każdy item ma jednak tematyczną ikonę FA, żeby Janek
 * nie musiał ich klikać ręcznie w Elementorze.
 *
 * Użycie:
 *   php scripts/replace-singleelementor-htmlwith-iconlist.php           # DRY-RUN (nic nie zapisuje)
 *   php scripts/replace-singleelementor-htmlwith-iconlist.php --apply   # backup + UPDATE
 *
 * Backup: /home/host476470/backups/primaauto/elementor_data_101874_<date>.json
 */

define('TEMPLATE_ID', 101874);
define('BACKUP_DIR', '/home/host476470/backups/primaauto');
define('WIDGET_ID_INCL', '3fa0a83'); // aa-incl (W cenie)
define('WIDGET_ID_WHY',  'fae81fa'); // aa-why (Dlaczego Prima Auto)

$apply = in_array('--apply', $argv, true);

// Bootstrap WordPress
define('ABSPATH', '/home/host476470/domains/asiaauto.pl/public_html/');
define('WP_USE_THEMES', false);
require ABSPATH . 'wp-load.php';

/** @var wpdb $wpdb */
global $wpdb;

// ─── 1. Pobierz aktualny JSON ──────────────────────────────────────────────
$current = $wpdb->get_var($wpdb->prepare(
    "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_elementor_data'",
    TEMPLATE_ID
));

if (empty($current)) {
    fwrite(STDERR, "❌ Nie znaleziono _elementor_data dla post_id=" . TEMPLATE_ID . "\n");
    exit(1);
}

$data = json_decode($current, true);
if ($data === null) {
    fwrite(STDERR, "❌ JSON decode failed: " . json_last_error_msg() . "\n");
    exit(1);
}

echo "✅ Pobrano _elementor_data (" . strlen($current) . " znaków)\n";

// ─── 2. Zdefiniuj nowe widgety icon-list ─────────────────────────────────────

// Wspólne settings (wzorowane na widget "info pages" id=bef3e76)
$common_icon_list_settings = [
    'space_between' => ['unit' => 'px', 'size' => 8, 'sizes' => []],
    'icon_align'    => 'start',
    'icon_size'     => ['unit' => 'px', 'size' => 14, 'sizes' => []],
    'icon_vertical_offset' => ['unit' => 'px', 'size' => '', 'sizes' => []],
    '__globals__'   => [
        'icon_color'                 => 'globals/colors?id=secondary',
        'text_color'                 => 'globals/colors?id=primary',
        'icon_typography_typography' => '',
    ],
    'icon_color'    => '#1B2A4A',
    'icon_typography_typography'   => 'custom',
    'icon_typography_font_family'  => 'Inter',
    'icon_typography_font_size'    => ['unit' => 'px', 'size' => 14, 'sizes' => []],
    'icon_typography_font_weight'  => '600',
    'text_color'    => '#2D3748',
];

// Helper: build single icon_list item
$item = function (string $text, string $fa, ?string $id = null): array {
    return [
        'text'          => $text,
        'selected_icon' => ['value' => $fa, 'library' => 'fa-solid'],
        '_id'           => $id ?: substr(md5($text . $fa . microtime(true)), 0, 7),
    ];
};

// Widget 1: W cenie
$widget_incl = [
    'id'         => WIDGET_ID_INCL,
    'elType'     => 'widget',
    'settings'   => array_merge($common_icon_list_settings, [
        '_title' => 'W cenie',
        'icon_list' => [
            $item('Zmiana języka PL / EN',                             'fas fa-language',        'aa1in1'),
            $item('Sterowanie głosowe po polsku',                      'fas fa-microphone',      'aa1in2'),
            $item('Ładowarka 7 kW EU (PHEV/EREV)',                     'fas fa-charging-station','aa1in3'),
            $item('Przejściówka do ładowarek miejskich (PHEV/EREV)',   'fas fa-plug',            'aa1in4'),
            $item('Dodatkowy kluczyk / pilot',                         'fas fa-key',             'aa1in5'),
            $item('Dwa komplety filtrów',                              'fas fa-filter',          'aa1in6'),
        ],
    ]),
    'elements'   => [],
    'widgetType' => 'icon-list',
];

// Widget 2: Dlaczego Prima Auto
$widget_why = [
    'id'         => WIDGET_ID_WHY,
    'elType'     => 'widget',
    'settings'   => array_merge($common_icon_list_settings, [
        '_title' => 'Dlaczego Prima Auto',
        'icon_list' => [
            $item('Pełna obsługa importu',  'fas fa-check-circle',    'aa2wh1'),
            $item('Odbiór w Rzeszowie',     'fas fa-map-marker-alt',  'aa2wh2'),
            $item('Transparentna cena',     'fas fa-tag',             'aa2wh3'),
            $item('20 lat doświadczenia',   'fas fa-award',           'aa2wh4'),
            $item('Umowa agencyjna',        'fas fa-file-contract',   'aa2wh5'),
        ],
    ]),
    'elements'   => [],
    'widgetType' => 'icon-list',
];

// ─── 3. Rekurencyjny walker: znajdź i podmień ────────────────────────────────

$replaced = 0;
$walker = function (&$nodes) use (&$walker, $widget_incl, $widget_why, &$replaced) {
    foreach ($nodes as &$node) {
        if (!is_array($node)) continue;
        if (isset($node['id'])) {
            if ($node['id'] === WIDGET_ID_INCL) {
                $node = $widget_incl;
                $replaced++;
                echo "  ✅ Podmieniono widget id=" . WIDGET_ID_INCL . " (html → icon-list 'W cenie')\n";
                continue;
            }
            if ($node['id'] === WIDGET_ID_WHY) {
                $node = $widget_why;
                $replaced++;
                echo "  ✅ Podmieniono widget id=" . WIDGET_ID_WHY . " (html → icon-list 'Dlaczego Prima Auto')\n";
                continue;
            }
        }
        if (!empty($node['elements']) && is_array($node['elements'])) {
            $walker($node['elements']);
        }
    }
};

$walker($data);

if ($replaced !== 2) {
    fwrite(STDERR, "❌ Oczekiwano 2 podmian, wykonano: {$replaced}. Abort.\n");
    exit(1);
}

// ─── 4. Serialize nowy JSON ───────────────────────────────────────────────────
$new = wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($new === false) {
    fwrite(STDERR, "❌ JSON encode failed\n");
    exit(1);
}

echo "\n📏 Rozmiar: {" . strlen($current) . "} → {" . strlen($new) . "} znaków (" . (strlen($new) - strlen($current)) . ")\n";

// ─── 5. DRY-RUN vs APPLY ──────────────────────────────────────────────────────

if (!$apply) {
    echo "\n🏁 DRY-RUN. Nic nie zapisano. Aby zapisać uruchom: php scripts/replace-singleelementor-htmlwith-iconlist.php --apply\n";
    echo "\n--- Podgląd fragmentu nowego widgetu 'W cenie' ---\n";
    echo wp_json_encode($widget_incl, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    exit(0);
}

// ─── 6. Backup + UPDATE ───────────────────────────────────────────────────────

if (!is_dir(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0755, true);
}

$backup_file = BACKUP_DIR . '/elementor_data_' . TEMPLATE_ID . '_' . date('Y-m-d_His') . '.json';
if (file_put_contents($backup_file, $current) === false) {
    fwrite(STDERR, "❌ Nie udało się zapisać backupu do {$backup_file}\n");
    exit(1);
}
echo "💾 Backup: {$backup_file}\n";

$updated = $wpdb->update(
    $wpdb->postmeta,
    ['meta_value' => $new],
    ['post_id' => TEMPLATE_ID, 'meta_key' => '_elementor_data'],
    ['%s'],
    ['%d', '%s']
);

if ($updated === false) {
    fwrite(STDERR, "❌ UPDATE failed: " . $wpdb->last_error . "\n");
    exit(1);
}

echo "✅ UPDATE wykonany, rows affected: {$updated}\n";

// ─── 7. Regeneruj CSS Elementora dla tego templatu ────────────────────────────
if (class_exists('\Elementor\Plugin')) {
    $post_css = \Elementor\Core\Files\CSS\Post::create(TEMPLATE_ID);
    $post_css->update();
    echo "🎨 Elementor CSS zregenerowany dla templatu " . TEMPLATE_ID . "\n";
}

// Wyczyść inne cache
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo "🧹 wp_cache_flush\n";
}

echo "\n🎯 Gotowe. Sprawdź pojedynczy listing w podglądzie i wejdź do edytora Elementor żeby dopracować stylowanie.\n";
