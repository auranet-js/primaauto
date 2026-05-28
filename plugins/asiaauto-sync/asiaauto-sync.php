<?php
/**
 * Plugin Name: Prima-Auto Sync
 * Description: Synchronizacja ogłoszeń z Dongchedi/Che168, pipeline cenowy, zamówienia, umowy PDF.
 * Version: 0.32.58
 * Author: Auranet / Jan Schenk
 * Author URI: https://auranet.com.pl
 * Text Domain: asiaauto
 * Domain Path: /languages
 * Requires PHP: 8.0
 * License: Proprietary
 */

defined('ABSPATH') || exit;

define('ASIAAUTO_VERSION', '0.32.58');
define('ASIAAUTO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ASIAAUTO_PLUGIN_URL', plugin_dir_url(__FILE__));

// Composer autoload (mPDF, API client)
if (file_exists(ASIAAUTO_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once ASIAAUTO_PLUGIN_DIR . 'vendor/autoload.php';
}

// ─── Includes ────────────────────────────────────────────────────────
// Static / utility classes (no constructor or CLI-only with args)
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-logger.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-mapping.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-api.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-price.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-translator.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-ui-translator.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-media.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-importer.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-sync.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-rotation.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-taxonomy.php';

// Classes with hooks (no-arg constructors)
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-cpt.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-admin.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-admin-manual-import.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-admin-listings-views.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-order-content.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-order.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-order-admin.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-order-api.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-order-wizard.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-contract.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-shortcodes.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-brand-hub.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-rest-hub.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-hub-title-generator.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-inventory.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-single.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-redirects.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-security.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-homepage.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-contact.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-seo.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-perf.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-login.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-listing-editor.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-gallery-metabox.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-indexing.php';

// ─── Diagnostyka admin panel (v0.32.0+) ──────────────────────────────
require_once ASIAAUTO_PLUGIN_DIR . 'includes/diag-checks/interface-check.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/diag-checks/class-check-base.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/diag-checks/class-check-missing-images.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/diag-checks/class-check-chinese-chars.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/diag-checks/class-check-broken-extra-prep.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/diag-checks/class-check-duplicate-listings.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/diag-checks/class-check-make-without-wiki.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/diag-checks/class-check-serie-without-wiki.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/diag-checks/class-check-listings-without-mapping.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/diag-checks/class-check-mapping-without-term.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/diag-checks/class-check-serie-broken-parent.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/diag-checks/class-check-duplicate-serie-terms.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-diag.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-admin-diag.php';

add_action('admin_init', function () {
    AsiaAuto_Diag_Registry::instance()->registerAjaxHandlers();
});

// ─── WP-CLI ──────────────────────────────────────────────────────────
if (defined('WP_CLI') && WP_CLI) {
    require_once ASIAAUTO_PLUGIN_DIR . 'cli/class-asiaauto-cli.php';
}

// ─── Custom image sizes ──────────────────────────────────────────────
add_action('after_setup_theme', function () {
    add_image_size('asiaauto-card', 350, 250, true);
    add_image_size('asiaauto-thumb', 190, 132, true);
    add_image_size('asiaauto-gallery', 800, 600, true);
});

// ─── Mail sender ─────────────────────────────────────────────────────
add_filter('wp_mail_from_name', function () { return 'Zamówienia Prima-Auto'; });
add_filter('wp_mail_from', function () { return 'china@primaauto.com.pl'; });

// ─── Frontend tracking (tel/wa click → dataLayer) ────────────────────
add_action('wp_enqueue_scripts', function () {
    $file = ASIAAUTO_PLUGIN_DIR . 'assets/js/asiaauto-tracking.js';
    wp_enqueue_script(
        'asiaauto-tracking',
        ASIAAUTO_PLUGIN_URL . 'assets/js/asiaauto-tracking.js',
        [],
        file_exists($file) ? filemtime($file) : ASIAAUTO_VERSION,
        true
    );
});

// ─── Cron intervals (custom) ─────────────────────────────────────────
add_filter('cron_schedules', function ($schedules) {
    $schedules['asiaauto_15min'] = [
        'interval' => 15 * 60,
        'display'  => 'Co 15 minut (Prima-Auto sync)',
    ];
    return $schedules;
});

// ─── Cron handler: asiaauto_sync_changes ─────────────────────────────
add_action('asiaauto_sync_changes', function () {
    if (!AsiaAuto_Sync::isEnabled()) {
        return;
    }
    if (!defined('ASIAAUTO_API_KEY') || !defined('ASIAAUTO_API_BASE_URL')) {
        AsiaAuto_Logger::error('Sync cron: missing ASIAAUTO_API_KEY or ASIAAUTO_API_BASE_URL in wp-config.php');
        return;
    }
    $api  = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);
    $sync = new AsiaAuto_Sync($api);
    $sync->run('dongchedi');
});

// ─── Cron handler: asiaauto_daily_cleanup ────────────────────────────
add_action('asiaauto_daily_cleanup', function () {
    $rotation = new AsiaAuto_Rotation();
    $rotation->cleanup();
});

// ─── Bootstrap ───────────────────────────────────────────────────────
add_action('plugins_loaded', function () {
    // Frontend + backend hook classes (no-arg constructors)
    new AsiaAuto_CPT();
    new AsiaAuto_Order();
    new AsiaAuto_Order_API();
    new AsiaAuto_Order_Wizard();
    new AsiaAuto_Contract();
    new AsiaAuto_Shortcodes();
    new AsiaAuto_Brand_Hub();
    new AsiaAuto_REST_Hub();
    new AsiaAuto_Inventory();
    new AsiaAuto_Single();
    new AsiaAuto_Redirects();
    new AsiaAuto_Security();
    new AsiaAuto_Homepage();
    new AsiaAuto_Contact();
    new AsiaAuto_Login();
    new AsiaAuto_Indexing_API();

    // Admin-only classes
    if (is_admin()) {
        new AsiaAuto_Admin();
        new AsiaAuto_Admin_Manual_Import();
        new AsiaAuto_Admin_Listings_Views();
        new AsiaAuto_Order_Admin();
        new AsiaAuto_Listing_Editor();
        new AsiaAuto_Gallery_Metabox();
    }
});

// ─── Activation ──────────────────────────────────────────────────────
register_activation_hook(__FILE__, function () {
    AsiaAuto_CPT::registerPostType();
    AsiaAuto_CPT::registerTaxonomies();
    AsiaAuto_Order::registerPostType();
    AsiaAuto_Order::registerCustomerRole();
    AsiaAuto_Security::grantAdminOrderCap();
    AsiaAuto_Security::grantAdminImportCap();
    AsiaAuto_Security::cleanStaleRoles();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});
