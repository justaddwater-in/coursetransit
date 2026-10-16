<?php
/**
 * Plugin Name: CourseTransit
 * Plugin URI: https://justaddwater.in/products/coursetransit-wordpress-moodle-integration/
 * Description: A WooCommerce integration for Moodle that syncs courses and helps manage online course sales from WordPress.
 * Version: 2.1.0
 * Author: JustAddWater
 * Author URI: https://justaddwater.in/
 * License: GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: coursetransit
 *
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('COURSETRANSIT_FILE')) {
    define('COURSETRANSIT_FILE', __FILE__);
}


// Constants
define('COURSETRANSIT_PATH', plugin_dir_path(__FILE__));
define('COURSETRANSIT_URL', plugin_dir_url(__FILE__));
define('COURSETRANSIT_ASSETS_URL', plugin_dir_url(__FILE__) . 'assets/');
define('COURSETRANSIT_VERSION', '2.1.0');

// Public Core API version used by extensions such as CourseTransit Pro.
if (!defined('COURSETRANSIT_API_VERSION')) {
    define('COURSETRANSIT_API_VERSION', '1.0.0');
}

if (!defined('COURSETRANSIT_IS_CORE')) {
    define('COURSETRANSIT_IS_CORE', true);
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

// Signal that the CourseTransit Core bootstrap has loaded. Add-ons should
// use this hook to register their integrations without replacing Core files.
do_action('coursetransit_loaded', COURSETRANSIT_VERSION, COURSETRANSIT_API_VERSION);

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
use CourseTransit\Services\InstallWebhook;

/**
 * Activation hook
 */
register_activation_hook(__FILE__, function () {

    ob_start();

    Installer::run();

    if (get_option('coursetransit_install_consent', false) === false) {
        add_option('coursetransit_install_consent', 'pending', '', false);
    }

    delete_option('coursetransit_install_consent_snooze_until');
    delete_option('coursetransit_install_consent_result');

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



/**
 * Register the CourseTransit Course Cards Gutenberg block.
 *
 * The block is part of Free/Core and uses only the core CourseTransit
 * course data plus the mapped WooCommerce product data.
 */
add_action('init', function () {

    $course_cards_block = COURSETRANSIT_PATH . 'blocks/course-cards';

    if (!file_exists($course_cards_block . '/block.json')) {
        return;
    }

    wp_register_script(
        'coursetransit-course-cards-editor',
        COURSETRANSIT_URL . 'blocks/course-cards/index.js',
        [
            'wp-blocks',
            'wp-element',
            'wp-i18n',
            'wp-block-editor',
            'wp-components',
            'wp-server-side-render',
        ],
        COURSETRANSIT_VERSION,
        true
    );

    // Supply the editor with the real CourseTransit categories and public
    // instructors used by synced courses. The frontend never trusts these
    // values; render.php validates them against the database again.
    global $wpdb;

    $course_cards_categories = [];
    $course_cards_instructors = [];

    if (class_exists('WooCommerce')) {
        $courses_table = $wpdb->prefix . 'coursetransit_courses';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table identifiers are trusted WordPress core names and the CourseTransit table uses the WordPress prefix.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- SQL contains only trusted WordPress-prefixed table identifiers and fixed clauses; no user values are interpolated.
        $category_rows = $wpdb->get_results(
            "SELECT DISTINCT t.term_id, t.name
             FROM {$wpdb->terms} AS t
             INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_id = t.term_id
             INNER JOIN {$wpdb->term_relationships} AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
             INNER JOIN {$courses_table} AS c ON c.wc_product_id = tr.object_id
             WHERE tt.taxonomy = 'product_cat'
               AND c.visible = 1
               AND c.last_sync_status = 'success'
             ORDER BY t.name ASC",
            ARRAY_A
        );

        foreach ($category_rows as $row) {
            $course_cards_categories[] = [
                'label' => (string) $row['name'],
                'value' => (string) (int) $row['term_id'],
            ];
        }
    }

    $instructors_table = $wpdb->prefix . 'coursetransit_instructors';
    $map_table = $wpdb->prefix . 'coursetransit_instructor_course_map';
    $courses_table = $wpdb->prefix . 'coursetransit_courses';

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table identifiers are derived from the trusted WordPress prefix.
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- SQL contains only trusted WordPress-prefixed table identifiers and fixed clauses; no user values are interpolated.
    $instructor_rows = $wpdb->get_results(
        "SELECT DISTINCT i.id, i.name
         FROM {$instructors_table} AS i
         INNER JOIN {$map_table} AS m ON m.instructor_id = i.id
         INNER JOIN {$courses_table} AS c ON c.id = m.course_id
         WHERE i.is_active = 1
           AND i.is_public = 1
           AND c.visible = 1
           AND c.last_sync_status = 'success'
         ORDER BY i.name ASC",
        ARRAY_A
    );

    foreach ($instructor_rows as $row) {
        $course_cards_instructors[] = [
            'label' => (string) $row['name'],
            'value' => (string) (int) $row['id'],
        ];
    }

    wp_localize_script(
        'coursetransit-course-cards-editor',
        'CourseTransitCourseCards',
        [
            'categories' => $course_cards_categories,
            'instructors' => $course_cards_instructors,
        ]
    );

    register_block_type($course_cards_block);
});


/**
 * Fallback course selector endpoint. Uses admin-ajax so the block selector
 * still works on sites where REST routes are blocked by security/cache rules.
 */
add_action('wp_ajax_coursetransit_block_course_search', function () {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => __('You are not allowed to search courses.', 'coursetransit')], 403);
    }
    check_ajax_referer('coursetransit_block_course_search', 'nonce');
    global $wpdb;
    $search = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
    $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;
    $table = $wpdb->prefix . 'coursetransit_courses';
    $posts = $wpdb->posts;
    $where = ['c.moodle_id != %d'];
    $params = [1];
    if ($search !== '') {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $where[] = '(c.fullname LIKE %s OR p.post_title LIKE %s)';
        $params[] = $like; $params[] = $like;
    }
    if ($course_id > 0) { $where[] = 'c.id = %d'; $params[] = $course_id; }
    $sql = "SELECT c.id AS course_id, c.wc_product_id AS product_id, c.fullname, p.post_title
            FROM {$table} c
            LEFT JOIN {$posts} p ON p.ID = c.wc_product_id AND p.post_type = 'product'
            WHERE " . implode(' AND ', $where) . "
            ORDER BY c.fullname ASC LIMIT 50";
    $rows = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
    $data = [];
    foreach ((array) $rows as $row) {
        $title = trim((string) $row['fullname']);
        if ($title === '') $title = trim((string) $row['post_title']);
        if ($title === '') continue;
        $data[] = ['id' => (int) $row['course_id'], 'productId' => (int) $row['product_id'], 'title' => $title];
    }
    wp_send_json($data);
});

/**
 * Course selector endpoint used by CourseTransit Gutenberg blocks.
 * Editors can search synced, visible CourseTransit courses without requiring
 * WooCommerce REST API keys.
 */
add_action('rest_api_init', function () {
    register_rest_route('coursetransit/v1', '/block-courses', [
        'methods'             => 'GET',
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        },
        'callback'            => function (WP_REST_Request $request) {
            global $wpdb;

            $search = sanitize_text_field((string) $request->get_param('search'));
            $requested_course_id = absint($request->get_param('course_id'));
            $limit  = 50;
            $table  = $wpdb->prefix . 'coursetransit_courses';
            $posts  = $wpdb->posts;

            $where  = [
                'c.visible = 1',
                'c.last_sync_status = %s',
                'c.moodle_id != %d',
                'p.post_type = %s',
                'p.post_status = %s',
            ];
            $params = ['success', 1, 'product', 'publish'];

            if ($search !== '') {
                $like = '%' . $wpdb->esc_like($search) . '%';
                $where[] = '(c.fullname LIKE %s OR p.post_title LIKE %s)';
                $params[] = $like;
                $params[] = $like;
            }

            if ($requested_course_id > 0) {
                $where[] = 'c.id = %d';
                $params[] = $requested_course_id;
            }

            $sql = "SELECT c.id AS course_id, c.wc_product_id AS product_id, c.fullname, p.post_title
                    FROM {$table} AS c
                    INNER JOIN {$posts} AS p ON p.ID = c.wc_product_id
                    WHERE " . implode(' AND ', $where) . "
                    GROUP BY c.id, c.wc_product_id, c.fullname, p.post_title
                    ORDER BY c.fullname ASC
                    LIMIT {$limit}";

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Values are prepared and limit is an integer allowlisted above.
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- SQL uses trusted table identifiers and every variable value is passed through prepare().
            $rows = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
            $data = [];

            foreach ((array) $rows as $row) {
                $title = trim((string) $row['fullname']);
                if ($title === '') {
                    $title = trim((string) $row['post_title']);
                }
                if ($title === '') {
                    continue;
                }
                $data[] = [
                    'id'        => (int) $row['course_id'],
                    'productId' => (int) $row['product_id'],
                    'title'     => $title,
                ];
            }

            return rest_ensure_response($data);
        },
    ]);
});


/**
 * CourseTransit block inserter category and learner-facing blocks.
 */
add_filter('block_categories_all', function ($categories) {
    foreach ($categories as $category) {
        if (!empty($category['slug']) && $category['slug'] === 'course-transit') {
            return $categories;
        }
    }

    $categories[] = [
        'slug'  => 'course-transit',
        'title' => __('CourseTransit', 'coursetransit'),
        'icon'  => null,
    ];

    return $categories;
}, 10, 1);

add_action('init', function () {
    $blocks = [
        'my-courses' => [
            'handle' => 'coursetransit-my-courses-editor',
            'deps'   => ['wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-components', 'wp-server-side-render'],
        ],
        'course-description' => [
            'handle' => 'coursetransit-course-description-editor',
            'deps'   => ['wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-api-fetch'],
        ],
        'dashboard' => [
            'handle' => 'coursetransit-dashboard-editor',
            'deps'   => ['wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-components', 'wp-server-side-render'],
        ],
    ];

    foreach ($blocks as $directory => $config) {
        $path = COURSETRANSIT_PATH . 'blocks/' . $directory;
        $block_json = $path . '/block.json';
        $script = $path . '/index.js';

        if (!file_exists($block_json) || !file_exists($script)) {
            continue;
        }

        wp_register_script(
            $config['handle'],
            COURSETRANSIT_URL . 'blocks/' . $directory . '/index.js',
            $config['deps'],
            file_exists($script) ? (string) filemtime($script) : COURSETRANSIT_VERSION,
            true
        );

        if ($directory === 'course-description') {
            wp_localize_script(
                $config['handle'],
                'CourseTransitCourseSelector',
                [
                    'restUrl' => esc_url_raw(rest_url('coursetransit/v1/block-courses')),
                    'nonce'   => wp_create_nonce('wp_rest'),
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'searchNonce' => wp_create_nonce('coursetransit_block_course_search'),
                ]
            );
        }

        register_block_type($path);
    }
});


if (!class_exists('WooCommerce')) {
    return;
}
