<?php
namespace CourseTransit\Controllers;
use CourseTransit\Support\Logger;

class SettingsController extends BaseController
{
    public function index()
    {
        $settings = get_option('coursetransit_settings', []);

        if (!empty($settings['moodle_token'])) {
            $token = $settings['moodle_token'];

            $start = substr($token, 0, 4);   // first 4 chars
            $end = substr($token, -4);     // last 4 chars

            $settings['moodle_token'] = $start . str_repeat('*', max(strlen($token) - 8, 4)) . $end;
        }

        $this->render('settings/index', [
            'settings' => $settings,
        ]);
    }

    public function testAndSave()
    {
        // AJAX only
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            wp_send_json_error(['message' => 'Invalid request']);
        }

        // Permission check
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        // Nonce check
        if (!check_ajax_referer('coursetransit_settings_nonce', '_wpnonce', false)) {
            wp_send_json_error(['message' => 'Security check failed']);
        }

        // Input
        $moodleUrl = '';

        if (isset($_POST['settings']['moodle_url'])) {

            $moodleUrl = sanitize_text_field(
                wp_unslash($_POST['settings']['moodle_url'])
            );

            $moodleUrl = untrailingslashit($moodleUrl);
            $moodleUrl = esc_url_raw($moodleUrl);
        }

        $newToken = isset($_POST['settings']['moodle_token'])
            ? sanitize_text_field(
                wp_unslash($_POST['settings']['moodle_token'])
            )
            : '';

        $existing = get_option('coursetransit_settings', []);
        $oldToken = $existing['moodle_token'] ?? '';

        // Detect masked token (contains *)
        $isMasked = strpos($newToken, '*') !== false;

        // Decide final token
        if (!empty($newToken) && !$isMasked) {
            $moodleToken = $newToken; // user entered new token
        } else {
            $moodleToken = $oldToken; // reuse existing
        }

        // Validation
        if (!$moodleUrl) {
            wp_send_json_error(['message' => 'Moodle URL is required']);
        }

        if (!$moodleToken) {
            wp_send_json_error(['message' => 'Token not found. Please enter it.']);
        }

        $endpoint = $moodleUrl . '/auth/coursetransit/api.php';

        $payload = [
            'token' => $moodleToken,
            'function' => 'core_webservice_get_site_info',
            'payload' => new \stdClass(),
        ];

        $response = wp_remote_post($endpoint, [
            'timeout' => 15,
            'sslverify' => true,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Origin' => site_url(),
                'Referer' => site_url(),
            ],
            'body' => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }

        $status = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($status !== 200) {
            wp_send_json_error(['message' => 'Connection failed']);
        }

        $json = json_decode($body, true);

        if (!is_array($json)) {
            wp_send_json_error(['message' => 'Invalid response from Moodle']);
        }

        if (!empty($json['exception'])) {
            wp_send_json_error(['message' => $json['message'] ?? 'Moodle error']);
        }

        if (!empty($json['error'])) {
            wp_send_json_error(['message' => $json['error']]);
        }

        // Save settings (only overwrite token if new one provided)
        update_option('coursetransit_settings', [
            'moodle_url' => sanitize_text_field($moodleUrl),
            'moodle_token' => sanitize_text_field($moodleToken),
        ]);

        logger::log('Settings saved successfully', [
            'moodle_url' => $moodleUrl,
            'token_provided' => !$isMasked,
            'json_response' => $json,
        ]);

        wp_send_json_success([
            'message' => 'Connection verified successfully',
            'connection' => [
                'site_name' => sanitize_text_field($json['sitename'] ?? $json['fullname'] ?? 'Unknown Site'),
                'user' => sanitize_text_field($json['fullname'] ?? $json['username'] ?? 'Unknown User'),
                'username' => sanitize_text_field($json['username'] ?? ''),
                'version' => sanitize_text_field($json['release'] ?? $json['version'] ?? 'Unknown'),
                'url' => esc_url_raw($json['siteurl'] ?? $moodleUrl),
                'avatar' => esc_url_raw($json['userpictureurl'] ?? ''),
                'status' => 'connected',
            ],
        ]);
    }

}
