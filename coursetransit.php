<?php
/**
 * Plugin Name: CourseTransit
 * Description: A WooCommerce integration for Moodle that syncs courses and helps manage online course sales from WordPress.
 * Version:     1.3.0
 * Author:      JustAddWater
 * Author URI:  https://justaddwater.in/
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: coursetransit
 * Domain Path: /languages
 *
 * Requires at least: 6.0
 * Requires PHP:      7.4
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('COURSETRANSIT_FILE')) {
    define('COURSETRANSIT_FILE', __FILE__);
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log('CourseTransit: vendor/autoload.php missing');
    }
}

// Constants
define('COURSETRANSIT_PATH', plugin_dir_path(__FILE__));
define('COURSETRANSIT_URL', plugin_dir_url(__FILE__));
define('COURSETRANSIT_ASSETS_URL', plugin_dir_url(__FILE__) . 'assets/');
define('COURSETRANSIT_VERSION', '1.3.0');
if (!defined('COURSETRANSIT_DEBUG')) {
    define('COURSETRANSIT_DEBUG', defined('WP_DEBUG') && WP_DEBUG);
}

/**
 * PSR-4 Autoloader
 */
spl_autoload_register(function ($class) {

    if (strpos($class, 'CourseTransit\\') !== 0) {
        return;
    }

    $path = COURSETRANSIT_PATH . 'app/' .
        str_replace(['CourseTransit\\', '\\'], ['', '/'], $class) . '.php';

    if (file_exists($path)) {
        require_once $path;
    }
});

/**
 * Load bootstrap files
 */
$coursetransit_bootstrap_files = [
    'ajax.php',
    'admin.php',
    'woocommerce.php',
    'assets.php',
];

foreach ($coursetransit_bootstrap_files as $coursetransit_file) {
    $path = COURSETRANSIT_PATH . 'bootstrap/' . $coursetransit_file;
    if (file_exists($path)) {
        require_once $path;
    }
}

// WooCommerce dependency check
add_action('plugins_loaded', function () {

    // WooCommerce dependency check
    if (!class_exists('WooCommerce')) {

        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        if (is_plugin_active(plugin_basename(__FILE__))) {

            deactivate_plugins(plugin_basename(__FILE__));

            add_action('admin_notices', function () {

                echo '<div class="notice notice-error is-dismissible">
                    <p>
                        <strong>CourseTransit:</strong>
                        WooCommerce must be installed and activated.
                    </p>
                </div>';
            });
        }

        return;
    }

}, 1);

use CourseTransit\Installer;

/**
 * Activation hook
 */
register_activation_hook(__FILE__, function () {
    ob_start();
    Installer::run();
    ob_end_clean();
});

add_filter('admin_body_class', function ($classes) {

    $screen = get_current_screen();

    if (
        $screen &&
        isset($screen->id) &&
        strpos($screen->id, 'coursetransit') !== false
    ) {
        $classes .= ' coursetransit-page';
    }

    return $classes;
});

/**
 * Initialize services
 */
add_action('plugins_loaded', function () {

    if (
        class_exists('WooCommerce') &&
        class_exists(\CourseTransit\Services\ProductTab::class)
    ) {
        \CourseTransit\Services\ProductTab::init();
    }

});

add_action('admin_notices', function () {

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice check.
    $coursetransit_error = '';

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice check.
    if (isset($_GET['coursetransit_error'])) {

        $coursetransit_error = sanitize_text_field(
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice check.
            wp_unslash($_GET['coursetransit_error'])
        );
    }

    if (!$coursetransit_error) {
        return;
    }

    echo '<div class="notice notice-error is-dismissible">
        <p><strong>CourseTransit:</strong> WooCommerce must be installed and activated.</p>
    </div>';
});


add_action('after_plugin_row_' . plugin_basename(__FILE__), function () {

    if (class_exists('WooCommerce')) {
        return;
    }

    $woo_file = WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';

    if (file_exists($woo_file)) {

        // Installed but NOT active
        $action_url = wp_nonce_url(
            admin_url('plugins.php?action=activate&plugin=woocommerce/woocommerce.php'),
            'activate-plugin_woocommerce/woocommerce.php'
        );

        $action_text = esc_html__('Activate WooCommerce', 'coursetransit');

    } else {

        // Not installed
        $action_url = admin_url('plugin-install.php?s=woocommerce&tab=search&type=term');
        $action_text = esc_html__('Install WooCommerce', 'coursetransit');
    }

    echo '<tr class="plugin-update-tr">
        <td colspan="4" class="plugin-update colspanchange">
            <div class="update-message notice inline notice-error notice-alt">
                <p>
                    <strong>CourseTransit:</strong> WooCommerce must be installed and activated.
                    <a href="' . esc_url($action_url) . '">' . esc_html($action_text) . '</a>
                </p>
            </div>
        </td>
    </tr>';
});



if (!class_exists('WooCommerce')) {
    return;
}