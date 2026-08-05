<?php

namespace CourseTransit\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles the (opt-in) "share your admin email" prompt shown once after
 * activation, and the webhook call that fires if the site owner agrees.
 *
 * WordPress.org guidelines require that any data sent to a third-party
 * server be disclosed and, ideally, consent-based rather than silent.
 * Nothing here is sent until the admin explicitly clicks "Allow".
 */
class InstallWebhook
{
    const OPTION_STATUS = 'coursetransit_install_consent'; // '', 'pending', 'allowed'
    const OPTION_SNOOZE = 'coursetransit_install_consent_snooze_until';
    const NONCE_ACTION = 'coursetransit_install_consent';
    const ACTION_HOOK = 'coursetransit_install_consent';
    const SNOOZE_SECONDS = 7 * DAY_IN_SECONDS;

    /**
     * Called from register_activation_hook(). Does NOT send anything —
     * it just flags that the consent notice should be shown.
     */
    public static function on_activate(): void
    {
        add_option(self::OPTION_STATUS, 'pending', '', false);
        delete_option(self::OPTION_SNOOZE);
    }

    public static function register(): void
    {
        add_action('admin_notices', [self::class, 'render_notice']);
        add_action('admin_post_' . self::ACTION_HOOK, [self::class, 'handle_consent']);
        add_action('admin_init', [self::class, 'add_privacy_policy_content']);
    }

    /**
     * Suggests text for the site's Privacy Policy page, per the WP Plugin
     * Handbook: https://developer.wordpress.org/plugins/privacy/suggesting-text-for-the-site-privacy-policy/
     * This only adds suggested text to wp-admin > Privacy > Policy Guide —
     * it does not publish anything on its own or change the live policy.
     */
    public static function add_privacy_policy_content(): void
    {
        if (!function_exists('wp_add_privacy_policy_content')) {
            return;
        }

        $content = '<p class="privacy-policy-tutorial">' .
            esc_html__(
                'CourseTransit includes an optional, opt-in prompt shown once after activation.',
                'coursetransit'
            ) .
            '</p>' .
            '<strong class="privacy-policy-tutorial">' .
            esc_html__('Suggested Text:', 'coursetransit') .
            '</strong> ' .
            esc_html__(
                'If you choose to share your information through the CourseTransit setup notice, your administrator email address, website domain, administrator name, and WordPress, PHP, and CourseTransit version information are securely sent to the CourseTransit service to provide product updates, setup guidance, compatibility notices, and licensing communications. If you decline, no information is sent and the plugin continues to function normally.',
                'coursetransit'
            );

        wp_add_privacy_policy_content(
            esc_html__('CourseTransit', 'coursetransit'),
            wp_kses_post(wpautop($content, false))
        );
    }

    public static function render_notice(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Don't show if the user has already allowed.
        $status = get_option(self::OPTION_STATUS, false);

        if ($status === 'allowed') {
            return;
        }

        // Don't show if a valid snooze exists.
        $snooze_until = get_option(self::OPTION_SNOOZE, false);

        if ($snooze_until !== false && (int) $snooze_until > time()) {
            return;
        }

        $allow_url = wp_nonce_url(
            admin_url('admin-post.php?action=' . self::ACTION_HOOK . '&choice=allow'),
            self::NONCE_ACTION
        );

        $later_url = wp_nonce_url(
            admin_url('admin-post.php?action=' . self::ACTION_HOOK . '&choice=later'),
            self::NONCE_ACTION
        );
        ?>

        <div class="notice notice-info">
            <p>
                <strong><?php esc_html_e('Help us improve CourseTransit (Optional)', 'coursetransit'); ?></strong>
            </p>

            <p>
                <?php
                esc_html_e(
                    'With your permission, CourseTransit will securely share your email address and CourseTransit version information with the CourseTransit service to provide product updates and compatibility notices. This is optional, and the plugin will work normally if you decline.',
                    'coursetransit'
                );
                ?>
            </p>

            <p>
                <a href="<?php echo esc_url($allow_url); ?>" class="button button-primary button-small">
                    <?php esc_html_e('Yes, I agree', 'coursetransit'); ?>
                </a>

                <a href="<?php echo esc_url($later_url); ?>" class="button button-small">
                    <?php esc_html_e('Not now', 'coursetransit'); ?>
                </a>
            </p>
        </div>

        <?php
    }

    public static function handle_consent(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to do this.', 'coursetransit'));
        }

        check_admin_referer(self::NONCE_ACTION);

        $choice = isset($_GET['choice'])
            ? sanitize_text_field(wp_unslash($_GET['choice']))
            : '';

        if ($choice === 'allow') {
            update_option(self::OPTION_STATUS, 'allowed');
            delete_option(self::OPTION_SNOOZE);
            self::send();
        } else {
            // "Remind me later" — status stays 'pending' so the notice
            // reappears automatically once the snooze window passes.
            update_option(self::OPTION_SNOOZE, time() + self::SNOOZE_SECONDS);
        }

        wp_safe_redirect(wp_get_referer() ?: admin_url());
        exit;
    }

    /**
     * Best-effort split of the site admin's display name into first/last.
     * Both are optional fields on the API, so a miss here is harmless.
     */
    protected static function get_admin_name(): array
    {
        $admin_email = get_option('admin_email');
        $user = get_user_by('email', $admin_email);

        $first_name = '';
        $last_name = '';

        if ($user instanceof \WP_User) {
            $first_name = $user->first_name;
            $last_name = $user->last_name;

            if (empty($first_name) && empty($last_name) && !empty($user->display_name)) {
                $parts = explode(' ', $user->display_name, 2);
                $first_name = $parts[0];
                $last_name = $parts[1] ?? '';
            }
        }

        return [$first_name, $last_name];
    }

    /**
     * Fires the actual webhook. Only ever called after explicit consent.
     */
    protected static function send(): void
    {
        $webhook_url = apply_filters(
            'coursetransit_install_webhook_url',
            'https://store.justaddwater.in/api/client-info'
        );

        list($first_name, $last_name) = self::get_admin_name();

        $domain = wp_parse_url(home_url(), PHP_URL_HOST);

        $payload = [
            'client_type' => 'wordpress',
            'plugin' => 'coursetransit',
            'domain' => $domain ? sanitize_text_field($domain) : '',
            'first_name' => sanitize_text_field($first_name),
            'last_name' => sanitize_text_field($last_name),
            'email' => sanitize_email(get_option('admin_email')),
            'plugin_version' => defined('COURSETRANSIT_VERSION') ? COURSETRANSIT_VERSION : '',
            'platform_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
        ];

        if (empty($webhook_url) || !wp_http_validate_url($webhook_url)) {
            return;
        }

        $response = wp_remote_post(
            $webhook_url,
            [
                'timeout' => 10,
                'blocking' => true, // Need the response to log validation errors below.
                'headers' => ['Content-Type' => 'application/json'],
                'body' => wp_json_encode($payload),
            ]
        );

        if (is_wp_error($response)) {
            return;
        }

    }
}