<?php
namespace CourseTransit\Controllers;

defined('ABSPATH') || exit;

class LogsController extends BaseController
{
    public function getSettings()
    {
        check_ajax_referer('coursetransit_nonce');

        if (!current_user_can('manage_options')) {

            wp_send_json_error(
                ['message' => 'Unauthorized'],
                403
            );
        }

        $settings = get_option(
            'coursetransit_log_settings',
            [
                'enabled' => 0,
            ]
        );

        wp_send_json_success($settings);
    }
    public function saveSettings()
    {
        check_ajax_referer('coursetransit_nonce');

        if (!current_user_can('manage_options')) {

            wp_send_json_error(
                ['message' => 'Unauthorized'],
                403
            );
        }

        $settings = [
            'enabled' => !empty($_POST['enabled'])
                ? 1
                : 0,
        ];

        update_option(
            'coursetransit_log_settings',
            $settings
        );

        wp_send_json_success([
            'message' => 'Log settings saved',
        ]);
    }
    public function datatable()
    {
        check_ajax_referer('coursetransit_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        global $wpdb;

        $table = esc_sql($wpdb->prefix . 'coursetransit_logs');

        $draw = intval($_GET['draw'] ?? 1);
        $start = intval($_GET['start'] ?? 0);
        $length = intval($_GET['length'] ?? 20);

        $search = '';

        if (isset($_GET['search']['value'])) {
            $search = sanitize_text_field(
                wp_unslash($_GET['search']['value'])
            );
        }

        /* ----------------------------------------
         * TOTAL
         * ------------------------------------- */

        $totalSql = "SELECT COUNT(*) FROM {$table}";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
        $total = (int) $wpdb->get_var($totalSql);

        /* ----------------------------------------
         * FILTERED + ROWS
         * ------------------------------------- */

        if ($search) {

            $like = '%' . $wpdb->esc_like($search) . '%';

            $countSql = "
            SELECT COUNT(*)
            FROM {$table}
            WHERE
                title LIKE %s
                OR source LIKE %s
                OR level LIKE %s
        ";

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
            $filtered = (int) $wpdb->get_var(
                $wpdb->prepare(
                    $countSql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $like,
                    $like,
                    $like
                )
            );

            $querySql = "
            SELECT *
            FROM {$table}
            WHERE
                title LIKE %s
                OR source LIKE %s
                OR level LIKE %s
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d
        ";

            $query = $wpdb->prepare(
                $querySql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $like,
                $like,
                $like,
                $length,
                $start
            );

        } else {

            $filtered = $total;

            $querySql = "
            SELECT *
            FROM {$table}
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d
        ";

            $query = $wpdb->prepare(
                $querySql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $length,
                $start
            );
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $rows = $wpdb->get_results(
            $query, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            ARRAY_A
        );

        $sr = $start + 1;

        $data = array_map(function ($row) use (&$sr) {

            $badge = '<span class="badge badge-secondary">Info</span>';

            if ($row['level'] === 'error') {
                $badge = '<span class="badge badge-danger">Error</span>';
            }

            if ($row['level'] === 'warning') {
                $badge = '<span class="badge badge-warning">Warning</span>';
            }

            if ($row['level'] === 'debug') {
                $badge = '<span class="badge badge-dark">Debug</span>';
            }

            return [
                'sr' => $sr++,
                'level' => $badge,
                'title' => esc_html($row['title']),
                'source' => esc_html($row['source']),
                'status' => esc_html($row['status']),
                'created_at' => wp_date(
                    'd M Y, h:i A',
                    strtotime($row['created_at'])
                ),
                'actions' => '
            <button
                class="button button-small cts-view-log"
                data-context="' . esc_attr($row['context']) . '"
            >
                View
            </button>
        ',
            ];

        }, $rows);

        wp_send_json([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }
}