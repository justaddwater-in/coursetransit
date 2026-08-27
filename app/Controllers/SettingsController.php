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
        // AJAX only.
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            wp_send_json_error([
                'message' => 'Invalid request',
            ]);
        }

        // Nonce check.
        if (
            !check_ajax_referer(
                'coursetransit_settings_nonce',
                '_wpnonce',
                false
            )
        ) {
            wp_send_json_error([
                'message' => 'Security check failed',
            ]);
        }

        // Permission check.
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => 'Unauthorized',
            ]);
        }

        // Input.
        $moodleUrl = '';

        if (isset($_POST['settings']['moodle_url'])) {

            $moodleUrl = sanitize_text_field(
                wp_unslash(
                    $_POST['settings']['moodle_url']
                )
            );

            $moodleUrl = untrailingslashit(
                $moodleUrl
            );

            $moodleUrl = esc_url_raw(
                $moodleUrl
            );
        }

        $newToken = isset(
            $_POST['settings']['moodle_token']
        )
            ? sanitize_text_field(
                wp_unslash(
                    $_POST['settings']['moodle_token']
                )
            )
            : '';

        $existing = get_option(
            'coursetransit_settings',
            []
        );

        $oldToken =
            $existing['moodle_token']
            ?? '';

        // Detect masked token.
        $isMasked =
            strpos($newToken, '*')
            !== false;

        // Final token.
        if (
            !empty($newToken)
            && !$isMasked
        ) {
            $moodleToken =
                $newToken;
        } else {
            $moodleToken =
                $oldToken;
        }

        // Validation.
        if (!$moodleUrl) {
            wp_send_json_error([
                'message' =>
                    'Moodle URL is required',
            ]);
        }

        if (!$moodleToken) {
            wp_send_json_error([
                'message' =>
                    'Token not found. Please enter it.',
            ]);
        }

        // Moodle endpoint.
        $endpoint =
            $moodleUrl .
            '/webservice/rest/server.php';

        // Payload required by Moodle.
        $payload = [
            'siteurl' => site_url(),
        ];

        $response = wp_remote_post(
            $endpoint,
            [
                'timeout' => 15,
                'sslverify' => true,

                'headers' => [
                    'Accept' =>
                        'application/json',

                    'Origin' =>
                        site_url(),

                    'Referer' =>
                        site_url(),
                ],

                'body' => [
                    'wstoken' =>
                        $moodleToken,

                    'wsfunction' =>
                        'auth_coursetransit_execute_action',

                    'moodlewsrestformat' =>
                        'json',

                    'function' =>
                        'core_webservice_get_site_info',

                    'payload' =>
                        wp_json_encode(
                            $payload
                        ),
                ],
            ]
        );

        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' =>
                    $response->get_error_message(),
            ]);
        }

        $status =
            wp_remote_retrieve_response_code(
                $response
            );

        $body =
            wp_remote_retrieve_body(
                $response
            );

        if ($status !== 200) {
            wp_send_json_error([
                'message' =>
                    'Connection failed',
            ]);
        }

        $json = json_decode(
            $body,
            true
        );

        if (!is_array($json)) {
            wp_send_json_error([
                'message' =>
                    'Invalid response from Moodle',
            ]);
        }

        // Moodle exception.
        if (!empty($json['exception'])) {
            wp_send_json_error([
                'message' =>
                    $json['message']
                    ?? 'Moodle error',
            ]);
        }

        // CourseTransit wrapper response.
        if (empty($json['success'])) {

            $errordata = [];

            if (!empty($json['data'])) {
                $errordata =
                    json_decode(
                        $json['data'],
                        true
                    );
            }

            wp_send_json_error([
                'message' =>
                    $errordata['message']
                    ?? 'Connection failed',
            ]);
        }

        // Decode wrapped Moodle data.
        $connectiondata = [];

        if (!empty($json['data'])) {
            $connectiondata =
                json_decode(
                    $json['data'],
                    true
                );
        }

        if (
            !is_array(
                $connectiondata
            )
        ) {
            wp_send_json_error([
                'message' =>
                    'Invalid Moodle response',
            ]);
        }

        // Save settings.
        // Preserve existing settings.
        $settings = get_option('coursetransit_settings', []);

        $settings['moodle_url'] = sanitize_text_field($moodleUrl);
        $settings['moodle_token'] = sanitize_text_field($moodleToken);

        update_option(
            'coursetransit_settings',
            $settings
        );

        Logger::log(
            'Settings saved successfully',
            [
                'moodle_url' =>
                    $moodleUrl,

                'token_provided' =>
                    !$isMasked,

                'json_response' =>
                    $connectiondata,
            ]
        );

        wp_send_json_success([
            'message' =>
                'Connection verified successfully',

            'connection' => [
                'site_name' =>
                    sanitize_text_field(
                        $connectiondata['sitename']
                        ?? 'Unknown Site'
                    ),

                'user' =>
                    sanitize_text_field(
                        $connectiondata['fullname']
                        ?? $connectiondata['username']
                        ?? 'Unknown User'
                    ),

                'username' =>
                    sanitize_text_field(
                        $connectiondata['username']
                        ?? ''
                    ),

                'version' =>
                    sanitize_text_field(
                        $connectiondata['release']
                        ?? $connectiondata['version']
                        ?? 'Unknown'
                    ),

                'url' =>
                    esc_url_raw(
                        $connectiondata['siteurl']
                        ?? $moodleUrl
                    ),

                'avatar' =>
                    esc_url_raw(
                        $connectiondata['userpictureurl']
                        ?? ''
                    ),

                'status' =>
                    'connected',
            ],
        ]);
    }

}
