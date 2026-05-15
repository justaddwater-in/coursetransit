<?php
defined('ABSPATH') || exit;

use CourseTransit\Route;

/**
 * Handle POST requests for CourseTransit admin page
 */
add_action('admin_init', function () {

    if (!isset($_SERVER['REQUEST_METHOD']) || sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) !== 'POST') {
        return;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice check.
    if (!isset($_GET['page']) || $_GET['page'] !== 'coursetransit') {
        return;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin route check only.
    if (!isset($_GET['route'])) {
        return;
    }

    Route::render();
    exit;
});

/**
 * Add CourseTransit admin menu
 */
add_action('admin_menu', function () {

    add_menu_page(
        'CourseTransit',
        'CourseTransit',
        'manage_options',
        'coursetransit',
        function () {

            $method = isset($_SERVER['REQUEST_METHOD'])
                ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD']))
                : '';

            if ($method === 'GET') {
                \CourseTransit\Route::render();
            }
        },
        COURSETRANSIT_URL . 'assets/images/logo_s.png',
        999
    );

});


/**
 * Load assets ONLY for CourseTransit page
 */
add_action('admin_enqueue_scripts', function () {

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page check.
    if (!isset($_GET['page']) || $_GET['page'] !== 'coursetransit') {
        return;
    }

    \CourseTransit\Helpers\Assets::load();

}, 1);

add_action('admin_enqueue_scripts', function () {

    wp_enqueue_style(
        'coursetransit-admin-global',
        COURSETRANSIT_URL . 'assets/css/admin-global.css',
        [],
        COURSETRANSIT_VERSION
    );

});

/**
 * Load Google Sans font for CourseTransit page
 */
add_action('admin_enqueue_scripts', function () {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page check.
    if (!isset($_GET['page']) || $_GET['page'] !== 'coursetransit') {
        return;
    }

    wp_enqueue_style(
        'coursetransit-google-sans',
        COURSETRANSIT_URL . 'assets/css/google-sans.css',
        [],
        '1.0'
    );
});

/**
 * Add custom body class for CourseTransit page
 */
add_filter('admin_body_class', function ($classes) {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page check.
    if (isset($_GET['page']) && $_GET['page'] === 'coursetransit') {
        $classes .= ' coursetransit-page';
    }
    return $classes;
});




add_action('init', function () {

    register_post_type('ct_instructor', [
        'label' => esc_html__('Instructors', 'coursetransit'),
        'public' => true,
        'publicly_queryable' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'instructor'],
        'supports' => ['title', 'editor'],
        'show_in_rest' => true,
    ]);

});

add_filter('the_content', function ($content) {

    if (!is_singular('ct_instructor')) {
        return $content;
    }

    $avatar = get_post_meta(get_the_ID(), '_coursetransit_avatar', true);
    $headline = get_post_meta(get_the_ID(), '_coursetransit_headline', true);
    $courses = get_post_meta(get_the_ID(), '_coursetransit_courses', true);
    $focus = get_post_meta(get_the_ID(), '_coursetransit_focus', true);

    // fallback avatar
    if (!$avatar) {
        $avatar = COURSETRANSIT_ASSETS_URL . 'images/avatar.png';
    }

    // safe arrays
    $courses = is_array($courses) ? $courses : [];
    $focus = is_array($focus) ? $focus : [];

    ob_start();
    ?>


    <div class="ct-instructor-single">
        <!-- Avatar -->
        <img src="<?php echo esc_url($avatar); ?>" class="ct-instructor-avatar">

        <!-- Info -->
        <div class="ct-instructor-content">

            <!-- Name -->


            <!-- Headline -->
            <?php if ($headline): ?>
                <p class="ct-instructor-headline">
                    <?php echo esc_html($headline); ?>
                </p>
            <?php endif; ?>

            <!-- Focus Areas -->
            <?php if (!empty($focus)): ?>
                <div class="ct-instructor-focus">
                    <?php foreach ($focus as $f): ?>
                        <span class="ct-instructor-focus-item">
                            <?php echo esc_html($f); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>

    </div>

    <?php

    return ob_get_clean() . $content;
});

add_filter('the_excerpt', function ($excerpt) {

    if (!is_post_type_archive('ct_instructor') || !in_the_loop()) {
        return $excerpt;
    }

    $avatar = get_post_meta(get_the_ID(), '_coursetransit_avatar', true);
    $headline = get_post_meta(get_the_ID(), '_coursetransit_headline', true);

    // fallback avatar
    if (!$avatar) {
        $avatar = COURSETRANSIT_ASSETS_URL . 'images/avatar.png';
    }

    ob_start();
    ?>

    <div class="ct-instructor-excerpt">

        <img src="<?php echo esc_url($avatar); ?>" class="ct-instructor-excerpt-avatar">

        <div>


            <?php if ($headline): ?>
                <p class="ct-instructor-excerpt-headline">
                    <?php echo esc_html($headline); ?>
                </p>
            <?php endif; ?>

            <p class="ct-instructor-excerpt-text">
                <?php echo esc_html(wp_trim_words(
                    wp_strip_all_tags(get_the_content()),
                    15
                )); ?>
            </p>
        </div>

    </div>

    <?php

    return ob_get_clean();
});

add_action('admin_init', function () {

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WordPress core plugin activation flow.
    if (!isset($_GET['action']) || $_GET['action'] !== 'activate')
        return;
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WordPress core plugin activation flow.
    if (!isset($_GET['plugin']))
        return;

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WordPress core plugin activation flow.
    if ($_GET['plugin'] !== plugin_basename(__FILE__))
        return;

    // If WooCommerce NOT active → block
    if (!class_exists('WooCommerce')) {

        deactivate_plugins(plugin_basename(__FILE__));

        wp_safe_redirect(admin_url('plugins.php?coursetransit_error=1'));
        exit;
    }
});


add_action('admin_notices', function () {

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice check.
    if (!isset($_GET['coursetransit_error']))
        return;

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

        $action_text = 'Activate WooCommerce';

    } else {

        // Not installed
        $action_url = admin_url('plugin-install.php?s=woocommerce&tab=search&type=term');
        $action_text = 'Install WooCommerce';
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