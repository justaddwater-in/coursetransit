<?php

namespace CourseTransit\Helpers;

if (!defined('ABSPATH')) {
    exit;
}
class Assets
{
    public static function load()
    {
        /**
         * Load assets ONLY on CourseTransit admin page
         * (Call this conditionally via admin_enqueue_scripts)
         */
        $version = defined('WP_DEBUG') && WP_DEBUG
            ? time()
            : COURSETRANSIT_VERSION;

        /* =========================
         * STYLES
         * ========================= */

        // Font Awesome (Shards dependency)
        wp_enqueue_style(
            'coursetransit-fontawesome',
            COURSETRANSIT_URL . 'assets/css/fontawesome.css',
            [],
            $version
        );

        // Material Icons (Shards dependency)
        wp_enqueue_style(
            'coursetransit-material-icons',
            COURSETRANSIT_URL . 'assets/css/material-icons.css',
            [],
            $version
        );
        // wp_enqueue_style('coursetransit-material-icons', COURSETRANSIT_URL . 'assets/css/material-icons.css');

        // Bootstrap 4 CSS (required by Shards)
        wp_enqueue_style(
            'coursetransit-bootstrap',
            COURSETRANSIT_URL . 'assets/css/bootstrap.min.css',
            [],
            $version
        );


        // Shards Dashboard core CSS
        wp_enqueue_style(
            'coursetransit-shards',
            COURSETRANSIT_URL . 'assets/shards/css/shards-dashboards.1.1.0.min.css',
            ['coursetransit-bootstrap'],
            $version
        );

        // Shards extras CSS
        wp_enqueue_style(
            'coursetransit-shards-extras',
            COURSETRANSIT_URL . 'assets/shards/css/extras.1.1.0.min.css',
            ['coursetransit-shards'],
            $version
        );

        // coursetransit app overrides (KEEP THIS)
        wp_enqueue_style(
            'coursetransit-app',
            COURSETRANSIT_URL . 'assets/css/app.css',
            ['coursetransit-shards-extras'],
            $version
        );

        /* =========================
         * SCRIPTS
         * ========================= */

        // jQuery (WordPress core)
        wp_enqueue_script('jquery');

        // Popper.js (Bootstrap dependency)
        wp_enqueue_script(
            'coursetransit-popper',
            COURSETRANSIT_URL . 'assets/js/popper.min.js',
            ['jquery'],
            $version,
            true
        );
        wp_enqueue_script(
            'coursetransit-chart',
            COURSETRANSIT_URL . 'assets/js/chart.umd.min.js',
            ['jquery'],
            '4.5.1',
            true
        );
        // Bootstrap 4 JS
        wp_enqueue_script(
            'coursetransit-bootstrap',
            COURSETRANSIT_URL . 'assets/js/bootstrap.min.js',
            ['jquery'],
            $version,
            true
        );

        // Shards Dashboard JS (NO demo logic)
        wp_enqueue_script(
            'coursetransit-shards-dashboard',
            COURSETRANSIT_URL . 'assets/shards/js/shards-dashboards.1.1.0.min.js',
            ['jquery', 'coursetransit-bootstrap'],
            $version,
            true
        );

        // coursetransit Settings JS
        wp_enqueue_script(
            'coursetransit-settings',
            COURSETRANSIT_URL . 'assets/js/settings.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('coursetransit-settings', 'CourseTransitAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('coursetransit_settings_nonce'),
        ]);

        wp_enqueue_script(
            'coursetransit-orders',
            COURSETRANSIT_URL . 'assets/js/orders.js',
            ['jquery'],
            $version,
            true
        );

        wp_enqueue_script(
            'coursetransit-emails',
            COURSETRANSIT_URL . 'assets/js/emails.js',
            ['jquery'],
            $version,
            true
        );

        wp_enqueue_script(
            'coursetransit-dashboard',
            COURSETRANSIT_URL . 'assets/js/dashboard.js',
            ['jquery'],
            $version,
            true
        );

        wp_enqueue_script(
            'coursetransit-instructors',
            COURSETRANSIT_URL . 'assets/js/instructors.js',
            ['jquery'],
            $version,
            true
        );

        wp_enqueue_script(
            'coursetransit-courses',
            COURSETRANSIT_URL . 'assets/js/courses.js',
            ['jquery'],
            $version,
            true
        );

        $settings = get_option('coursetransit_settings', []);

        wp_localize_script('coursetransit-courses', 'CourseTransitAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('coursetransit_nonce'),
            'moodle_base_url' => rtrim($settings['moodle_url'] ?? '', '/'),
            'placeholder_image' => COURSETRANSIT_URL . 'assets/img/placeholder.png', // optional if you use it
        ]);

        wp_localize_script('coursetransit-instructors', 'CourseTransitInstructors', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('coursetransit_nonce'),
            'default_avatar' => COURSETRANSIT_ASSETS_URL . 'images/avatar.png',
        ]);

        wp_enqueue_style(
            'coursetransit-datatables',
            COURSETRANSIT_URL . 'assets/css/datatables.min.css',
            [],
            $version
        );


        wp_enqueue_script(
            'coursetransit-datatables',
            COURSETRANSIT_URL . 'assets/js/datatables.min.js',
            ['jquery'],
            $version,
            true
        );

        wp_enqueue_style(
            'coursetransit-select2',
            COURSETRANSIT_URL . 'assets/css/select2.min.css',
            [],
            $version
        );


        wp_enqueue_script(
            'coursetransit-select2',
            COURSETRANSIT_URL . 'assets/js/select2.min.js',
            ['jquery'],
            $version,
            true
        );

        wp_enqueue_style(
            'coursetransit-sweetalert',
            COURSETRANSIT_URL . 'assets/css/sweetalert2.min.css',
            [],
            $version
        );


        // SweetAlert2 JS
        wp_enqueue_script(
            'coursetransit-sweetalert',
            COURSETRANSIT_URL . 'assets/js/sweetalert2.all.min.js',
            [],
            $version,
            true
        );

        wp_enqueue_media();
    }
}
