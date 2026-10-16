<?php
defined('ABSPATH') || exit;

use CourseTransit\Route;
use CourseTransit\Services\InstallWebhook;

InstallWebhook::register();

/**
 * Handle POST requests for CourseTransit admin page
 */
add_action('admin_init', function () {

    $method = isset($_SERVER['REQUEST_METHOD'])
        ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD']))
        : '';

    if ($method !== 'POST') {
        return;
    }

    $page = '';

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page check.
    if (isset($_GET['page'])) {
        $page = sanitize_text_field(
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page check.
            wp_unslash($_GET['page'])
        );
    }

    if ($page !== 'coursetransit') {
        return;
    }

    $route = '';

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Internal admin routing only.
    if (isset($_GET['route'])) {
        $route = sanitize_text_field(
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page check.
            wp_unslash($_GET['route'])
        );
    }

    if (!$route) {
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

    $page = '';

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page check.
    if (isset($_GET['page'])) {
        $page = sanitize_text_field(
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page check.
            wp_unslash($_GET['page'])
        );
    }

    if ($page !== 'coursetransit') {
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

    $page = '';

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page check.
    if (isset($_GET['page'])) {
        $page = sanitize_text_field(
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page check.
            wp_unslash($_GET['page'])
        );
    }

    if ($page !== 'coursetransit') {
        return;
    }

    wp_enqueue_style(
        'coursetransit-google-sans',
        COURSETRANSIT_URL . 'assets/css/google-sans.css',
        [],
        '1.0'
    );
});



add_action('init', function () {

    register_post_type('ctransit_instructor', [
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

    if (!is_singular('ctransit_instructor') || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $instructor_id = get_the_ID();
    $avatar = get_post_meta($instructor_id, '_coursetransit_avatar', true);
    $headline = get_post_meta($instructor_id, '_coursetransit_headline', true);
    $courses = get_post_meta($instructor_id, '_coursetransit_courses', true);
    $expertise = get_post_meta($instructor_id, '_coursetransit_expertise', true);
    $bio = trim((string) get_post_field('post_content', $instructor_id));

    if (!$avatar) {
        $avatar = COURSETRANSIT_ASSETS_URL . 'images/avatar.png';
    }

    $courses = is_array($courses) ? $courses : [];
    $expertise = is_array($expertise) ? $expertise : array_filter(array_map('trim', explode(',', (string) $expertise)));

    ob_start();
    ?>
    <div class="ct-instructor-profile">
        <?php if ($headline) : ?>
            <p class="ct-instructor-profile__headline"><?php echo esc_html($headline); ?></p>
        <?php endif; ?>

        <div class="ct-instructor-profile__intro">
            <img src="<?php echo esc_url($avatar); ?>" class="ct-instructor-profile__avatar" alt="<?php echo esc_attr(get_the_title($instructor_id)); ?>">
        </div>

        <?php if ($bio !== '') : ?>
            <section class="ct-instructor-profile__about">
                <h2><?php echo esc_html__('About', 'coursetransit'); ?></h2>
                <?php echo wp_kses_post(wpautop($bio)); ?>
            </section>
        <?php endif; ?>

        <?php $expertise_items = array_values(array_filter(array_map('trim', array_map('strval', $expertise)))); ?>
        <?php if ($expertise_items) : ?>
            <section class="ct-instructor-profile__section">
                <h2><?php echo esc_html__('Expertise', 'coursetransit'); ?></h2>
                <p><?php echo esc_html(implode(', ', $expertise_items)); ?></p>
            </section>
        <?php endif; ?>

        <?php if ($courses) : ?>
            <?php $course_links = []; ?>
            <?php foreach ($courses as $course_id) : ?>
                <?php
                $course_id = absint($course_id);
                $course_post = $course_id ? get_post($course_id) : null;
                if (!$course_post || $course_post->post_type !== 'product' || $course_post->post_status !== 'publish') {
                    continue;
                }
                $course_links[$course_id] = get_the_title($course_id);
                ?>
            <?php endforeach; ?>
            <?php if ($course_links) : ?>
                <section class="ct-instructor-profile__section">
                    <h2><?php echo esc_html__('Courses', 'coursetransit'); ?></h2>
                    <ul class="ct-instructor-profile__courses">
                        <?php foreach ($course_links as $course_id => $course_title) : ?>
                            <li><a href="<?php echo esc_url(get_permalink($course_id)); ?>"><?php echo esc_html($course_title); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php

    return ob_get_clean();
});

add_filter('the_excerpt', function ($excerpt) {

    if (!is_post_type_archive('ctransit_instructor') || !in_the_loop()) {
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

    $action = '';
    $plugin = '';

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WordPress core plugin activation flow.
    if (isset($_GET['action'])) {
        $action = sanitize_text_field(
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WordPress core plugin activation flow.
            wp_unslash($_GET['action'])
        );
    }

    if ($action !== 'activate') {
        return;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WordPress core plugin activation flow.
    if (isset($_GET['plugin'])) {
        $plugin = sanitize_text_field(
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WordPress core plugin activation flow.
            wp_unslash($_GET['plugin'])
        );
    }

    if ($plugin !== plugin_basename(COURSETRANSIT_FILE)) {
        return;
    }

    // If WooCommerce NOT active → block
    if (!class_exists('WooCommerce')) {

        deactivate_plugins(plugin_basename(COURSETRANSIT_FILE));

        wp_safe_redirect(
            admin_url('plugins.php?coursetransit_error=1')
        );

        exit;
    }
});

if (!class_exists('WooCommerce')) {
    return;
}