<?php

namespace CourseTransit\Helpers;

use CourseTransit\Support\Logger;

if (!defined('ABSPATH')) {
    exit;
}

class ConnectionNotice
{
    public static function render(): void
    {
        $route = '';

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin route check.
        if (isset($_GET['route'])) {

            $route = sanitize_text_field(
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin route check.
                wp_unslash($_GET['route'])
            );
        }

        // Don't show on settings page
        if ($route === 'settings.index') {
            return;
        }

        $settings = get_option('coursetransit_settings', []);

        $missing_setup =
            empty($settings['moodle_url']) ||
            empty($settings['moodle_token']);

        Logger::log('Connection Notice Check', [
            'missing_setup' => $missing_setup,
            'has_url' => !empty($settings['moodle_url']),
            'has_token' => !empty($settings['moodle_token']),
            'route' => $route,
        ]);

        if (!$missing_setup) {
            return;
        }

        ?>

        <div class="container-fluid px-4 pt-3">

            <div class="ct-setup-alert d-flex align-items-center justify-content-between">

                <div class="d-flex align-items-center">

                    <div class="ct-setup-icon">
                        <i class="fa fa-link"></i>
                    </div>

                    <div class="ms-3">

                        <div class="ct-setup-title">
                            <?php echo esc_html__('Complete your Moodle connection setup', 'coursetransit'); ?>
                        </div>

                        <div class="ct-setup-text">
                            <?php echo esc_html__('Configure your Moodle URL and token to enable course synchronization.', 'coursetransit'); ?>
                        </div>

                    </div>

                </div>

                <div class="me-4">

                    <a href="<?php echo esc_url(admin_url('admin.php?page=coursetransit&route=settings.index')); ?>"
                        class="button button-primary">

                        <?php echo esc_html__('Open Settings', 'coursetransit'); ?>

                    </a>

                </div>

            </div>

        </div>

        <?php
    }
}