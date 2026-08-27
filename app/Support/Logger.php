<?php

namespace CourseTransit\Support;

class Logger
{
    protected const LOG_RETENTION_DAYS = 7;

    protected const LEVEL_INFO = 'info';
    protected const LEVEL_WARNING = 'warning';
    protected const LEVEL_ERROR = 'error';
    protected const LEVEL_DEBUG = 'debug';

    protected const STATUS_SUCCESS = 'success';
    protected const STATUS_WARNING = 'warning';
    protected const STATUS_FAILED = 'failed';
    protected const STATUS_DEBUG = 'debug';

    /**
     * Generic logger.
     */
    public static function log(
        string $title,
        $context = null,
        string $level = self::LEVEL_INFO,
        string $status = self::STATUS_SUCCESS
    ): void {

        // Debug disabled.
        $settings = get_option(
            'coursetransit_log_settings',
            []
        );

        if (
            empty($settings['enabled']) ||
            (int) $settings['enabled'] !== 1
        ) {
            return;
        }

        global $wpdb;

        $table = $wpdb->prefix . 'coursetransit_logs';

        $source = self::detectSource();

        $context = self::prepareContext($context);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->insert(
            $table,
            [
                'level' => sanitize_key($level),

                'source' => $source,
                'action' => 'log',

                'title' => sanitize_text_field($title),
                'message' => null,
                'context' => $context,

                'status' => sanitize_key($status),

                'user_id' => get_current_user_id(),

                'ip_address' => self::server('REMOTE_ADDR'),
                'request_url' => self::server('REQUEST_URI'),
                'method' => self::server('REQUEST_METHOD'),

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

                '%d',

                '%s',
                '%s',
                '%s',

                '%s',

                '%s',
            ]
        );

        self::maybePruneLogs($table);
    }

    /**
     * Success log.
     */
    public static function success(
        string $title,
        $context = null
    ): void {

        self::log(
            $title,
            $context,
            self::LEVEL_INFO,
            self::STATUS_SUCCESS
        );
    }

    /**
     * Warning log.
     */
    public static function warning(
        string $title,
        $context = null
    ): void {

        self::log(
            $title,
            $context,
            self::LEVEL_WARNING,
            self::STATUS_WARNING
        );
    }

    /**
     * Error log.
     */
    public static function error(
        string $title,
        $context = null
    ): void {

        self::log(
            $title,
            $context,
            self::LEVEL_ERROR,
            self::STATUS_FAILED
        );
    }

    /**
     * Debug log.
     */
    public static function debug(
        string $title,
        $context = null
    ): void {

        self::log(
            $title,
            $context,
            self::LEVEL_DEBUG,
            self::STATUS_DEBUG
        );
    }

    /**
     * Detect log source automatically.
     */
    protected static function detectSource(): string
    {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        return isset($trace[1]['class'])
            ? sanitize_text_field($trace[1]['class'])
            : 'system';
    }

    /**
     * Prepare context payload.
     */
    protected static function prepareContext($context): ?string
    {
        if ($context === null) {
            return null;
        }

        $context = self::sanitizeContext($context);

        if (
            is_array($context) ||
            is_object($context)
        ) {

            $encoded = wp_json_encode(
                $context,
                JSON_UNESCAPED_SLASHES |
                JSON_UNESCAPED_UNICODE
            );

            if (!$encoded) {
                return null;
            }

            // Prevent huge DB entries.
            return substr($encoded, 0, 10000);
        }

        return substr((string) $context, 0, 10000);
    }

    /**
     * Recursively sanitize sensitive data.
     */
    protected static function sanitizeContext($data)
    {
        $sensitive_keys = [
            'password',
            'pass',
            'token',
            'jwt',
            'secret',
            'authorization',
            'cookie',
            'api_key',
            'apikey',
            'access_token',
            'refresh_token',
            'auth',
            'auth_token',
            'bearer',
            'sesskey',
            'userprivateaccesskey',
            'wstoken',
            'license_key',
            'licensekey',
        ];

        if (!is_array($data)) {
            return $data;
        }

        foreach ($data as $key => $value) {

            $normalized_key = strtolower(
                (string) $key
            );

            if (
                in_array(
                    $normalized_key,
                    $sensitive_keys,
                    true
                )
            ) {

                $data[$key] = '[REDACTED]';

                continue;
            }

            if (is_array($value)) {
                $data[$key] = self::sanitizeContext($value);
            }
        }

        return $data;
    }

    /**
     * Get sanitized server value.
     */
    protected static function server(
        string $key
    ): string {

        if (!isset($_SERVER[$key])) {
            return '';
        }

        return sanitize_text_field(
            wp_unslash($_SERVER[$key])
        );
    }

    /**
     * Randomly prune old logs.
     */
    protected static function maybePruneLogs(
        string $table
    ): void {

        if (wp_rand(1, 20) !== 1) {
            return;
        }

        global $wpdb;

        $table = esc_sql($table);

        $sql = "
            DELETE FROM {$table}
            WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)
        ";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
        $wpdb->query(
            $wpdb->prepare(
                $sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                self::LOG_RETENTION_DAYS
            )
        );
    }

    /**
     * Detect logical module from source.
     */
    protected static function detectModule(
        string $source
    ): string {

        $source = strtolower($source);

        $modules = [
            'sync' => 'sync',
            'sso' => 'sso',
            'moodle' => 'moodle',
            'api' => 'api',
        ];

        foreach ($modules as $needle => $module) {

            if (
                strpos($source, $needle) !== false
            ) {
                return $module;
            }
        }

        return 'general';
    }
}