<?php

namespace CourseTransit\Support;

class Logger
{
    public static function log(string $title, $data = null): void
    {
        // Optional debug mode
        if (
            !defined('COURSETRANSIT_DEBUG') ||
            COURSETRANSIT_DEBUG !== true
        ) {
            return;
        }

        global $wpdb;

        $table = esc_sql($wpdb->prefix . 'coursetransit_logs');

        // Detect source/module automatically
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $source = isset($trace[1]['class'])
            ? $trace[1]['class']
            : 'system';

        // Sanitize sensitive data
        $data = self::sanitizeContext($data);

        // Encode context
        $context = null;

        if ($data !== null) {

            if (is_array($data) || is_object($data)) {

                $context = wp_json_encode(
                    $data,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                );

            } else {

                $context = (string) $data;
            }
        }

        // Request info
        $request_url = '';

        if (isset($_SERVER['REQUEST_URI'])) {
            $request_url = sanitize_text_field(
                wp_unslash($_SERVER['REQUEST_URI'])
            );
        }

        $method = '';

        if (isset($_SERVER['REQUEST_METHOD'])) {
            $method = sanitize_text_field(
                wp_unslash($_SERVER['REQUEST_METHOD'])
            );
        }

        $ip_address = '';

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $ip_address = sanitize_text_field(
                wp_unslash($_SERVER['REMOTE_ADDR'])
            );
        }

        // Insert log
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->insert(
            $table,
            [
                'level' => 'info',
                'source' => $source,
                'action' => 'log',

                'title' => sanitize_text_field($title),
                'message' => null,
                'context' => $context,

                'status' => 'success',

                'user_id' => get_current_user_id(),

                'ip_address' => $ip_address,
                'request_url' => $request_url,
                'method' => $method,

                'trace' => null,

                'created_at' => current_time('mysql'),
            ],
            [
                '%s',

                '%s',
                '%s',
                '%s',
                '%s',

                '%s',
                '%s',
                '%s',

                '%s',

                '%d',

                '%s',
                '%s',
                '%s',

                '%s',

                '%s',
                '%s',
            ]
        );

        // Prune old logs (7 days)
        if (wp_rand(1, 20) === 1) {

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is controlled via $wpdb->prefix.
                    "DELETE FROM {$table}
                     WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                    7
                )
            );
        }
    }

    protected static function sanitizeContext($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $sensitive_keys = [
            'password',
            'pass',
            'token',
            'jwt',
            'secret',
            'authorization',
            'cookie',
            'api_key',
            'userprivateaccesskey',
            'access_token',
            'refresh_token',
            'auth',
            'auth_token',
            'apikey',
            'bearer',
            'sesskey',
        ];

        foreach ($data as $key => $value) {

            if (
                in_array(
                    strtolower((string) $key),
                    $sensitive_keys,
                    true
                )
            ) {

                $data[$key] = '[REDACTED]';
            }

            if (is_array($value)) {
                $data[$key] = self::sanitizeContext($value);
            }
        }

        return $data;
    }

    protected static function detectModule(string $source): string
    {
        $source = strtolower($source);

        if (strpos($source, 'sync') !== false) {
            return 'sync';
        }

        if (strpos($source, 'sso') !== false) {
            return 'sso';
        }

        if (strpos($source, 'moodle') !== false) {
            return 'moodle';
        }

        if (strpos($source, 'api') !== false) {
            return 'api';
        }

        return 'general';
    }
}