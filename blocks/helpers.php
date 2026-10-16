<?php
/**
 * Shared helpers for CourseTransit frontend Gutenberg blocks.
 */
defined('ABSPATH') || exit;

if (!function_exists('coursetransit_blocks_get_enrolled_product_data')) {
    /**
     * Return directly enrolled CourseTransit product IDs keyed by the most
     * recent successful enrollment timestamp. Enrollment is tracked per order
     * line item so a partial multi-course order cannot enroll every product.
     */
    function coursetransit_blocks_get_enrolled_product_data($user_id) {
        $user_id = absint($user_id);
        if (!$user_id || !function_exists('wc_get_orders')) {
            return [];
        }

        $statuses = function_exists('wc_get_is_paid_statuses')
            ? wc_get_is_paid_statuses()
            : ['processing', 'completed'];

        $cache_key = 'enrolled_products_' . $user_id;
        $cached = wp_cache_get($cache_key, 'coursetransit_blocks');
        if (false !== $cached) {
            return $cached;
        }

        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'status'      => $statuses,
            'limit'       => -1,
            'orderby'     => 'date',
            'order'       => 'DESC',
            'return'      => 'objects',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- WooCommerce order meta is the canonical enrollment flag; customer and paid-status filters constrain this lookup.
            'meta_query'  => [
                [
                    'key'     => '_coursetransit_enrolled',
                    'value'   => '1',
                    'compare' => '=',
                ],
            ],
        ]);

        $product_data = [];
        foreach ($orders as $order) {
            if (!$order || !$order->get_meta('_coursetransit_enrolled', true)) {
                continue;
            }

            $created = $order->get_date_created();
            $timestamp = $created ? (int) $created->getTimestamp() : 0;

            foreach ($order->get_items('line_item') as $item) {
                $product_id = (int) $item->get_product_id();
                if ($product_id <= 0) {
                    continue;
                }

                // New orders use an item-level flag. For legacy single-item
                // orders, the existing order flag is unambiguous and remains
                // supported. Legacy multi-item orders cannot be inferred safely.
                $item_enrolled = (bool) $item->get_meta('_coursetransit_item_enrolled', true);
                $legacy_single_item = count($order->get_items('line_item')) === 1;
                if (!$item_enrolled && !$legacy_single_item) {
                    continue;
                }

                if (!isset($product_data[$product_id]) || $timestamp > $product_data[$product_id]) {
                    $product_data[$product_id] = $timestamp;
                }
            }
        }

        arsort($product_data, SORT_NUMERIC);
        wp_cache_set($cache_key, $product_data, 'coursetransit_blocks', 60);
        return $product_data;
    }
}

if (!function_exists('coursetransit_blocks_get_enrolled_product_ids')) {
    function coursetransit_blocks_get_enrolled_product_ids($user_id) {
        return array_map('intval', array_keys(coursetransit_blocks_get_enrolled_product_data($user_id)));
    }
}

if (!function_exists('coursetransit_blocks_get_paid_product_ids')) {
    /**
     * Backward-compatible alias. New blocks should use the enrolled helper.
     */
    function coursetransit_blocks_get_paid_product_ids($user_id) {
        return coursetransit_blocks_get_enrolled_product_ids($user_id);
    }
}

if (!function_exists('coursetransit_blocks_get_course_products')) {
    function coursetransit_blocks_get_course_products($product_ids, $limit = 12, $order_map = []) {
        global $wpdb;

        $product_ids = array_values(array_filter(array_map('absint', (array) $product_ids)));
        if (!$product_ids) {
            return [];
        }

        $limit = max(1, min(50, absint($limit)));
        $table = $wpdb->prefix . 'coursetransit_courses';

        $placeholders = implode(',', array_fill(0, count($product_ids), '%d'));
        $sql = "SELECT id, moodle_id, wc_product_id, fullname, summary, course_image_url,
                       created_at, moodle_created_at, moodle_updated_at
                FROM {$table}
                WHERE visible = 1
                  AND last_sync_status = %s
                  AND wc_product_id IN ({$placeholders})
                  AND moodle_id != %d";

        $params = array_merge(['success'], $product_ids, [1]);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Custom CourseTransit table query; all values use prepare() and table name comes from the trusted WordPress prefix.
        $rows = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        if (!is_array($rows)) {
            return [];
        }

        if ($order_map) {
            usort($rows, static function ($a, $b) use ($order_map) {
                $a_time = isset($order_map[(int) $a['wc_product_id']]) ? (int) $order_map[(int) $a['wc_product_id']] : 0;
                $b_time = isset($order_map[(int) $b['wc_product_id']]) ? (int) $order_map[(int) $b['wc_product_id']] : 0;
                if ($a_time === $b_time) {
                    return (int) $b['id'] <=> (int) $a['id'];
                }
                return $b_time <=> $a_time;
            });
        } else {
            usort($rows, static function ($a, $b) {
                return (int) $b['id'] <=> (int) $a['id'];
            });
        }

        return array_slice($rows, 0, $limit);
    }
}

if (!function_exists('coursetransit_blocks_get_course_by_id')) {
    function coursetransit_blocks_get_course_by_id($course_id) {
        global $wpdb;

        $course_id = absint($course_id);
        if (!$course_id) {
            return null;
        }

        $table = $wpdb->prefix . 'coursetransit_courses';
        $sql = "SELECT id, moodle_id, wc_product_id, fullname, summary, summary_format,
                       course_image_url, created_at, updated_at
                FROM {$table}
                WHERE visible = 1
                  AND last_sync_status = %s
                  AND id = %d
                  AND moodle_id != %d
                LIMIT 1";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Custom CourseTransit table query; table name comes from the trusted WordPress prefix and values are prepared.
        return $wpdb->get_row($wpdb->prepare($sql, 'success', $course_id, 1), ARRAY_A) ?: null;
    }
}

if (!function_exists('coursetransit_blocks_get_course_by_product_id')) {
    function coursetransit_blocks_get_course_by_product_id($product_id) {
        global $wpdb;

        $product_id = absint($product_id);
        if (!$product_id) {
            return null;
        }

        $table = $wpdb->prefix . 'coursetransit_courses';
        $sql = "SELECT id, moodle_id, wc_product_id, fullname, summary, summary_format,
                       course_image_url, created_at, updated_at
                FROM {$table}
                WHERE visible = 1
                  AND last_sync_status = %s
                  AND wc_product_id = %d
                  AND moodle_id != %d
                ORDER BY id DESC
                LIMIT 1";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Custom CourseTransit table query; table name comes from the trusted WordPress prefix and values are prepared.
        return $wpdb->get_row($wpdb->prepare($sql, 'success', $product_id, 1), ARRAY_A) ?: null;
    }
}


if (!function_exists('coursetransit_blocks_get_course_instructors')) {
    /**
     * Resolve public instructors for a CourseTransit course from the normalized
     * mapping table, with a legacy JSON fallback.
     */
    function coursetransit_blocks_get_course_instructors($course_id) {
        global $wpdb;
        $course_id = absint($course_id);
        if (!$course_id) {
            return [];
        }

        $cache_key = 'course_instructors_' . $course_id;
        $cached = wp_cache_get($cache_key, 'coursetransit_blocks');
        if (false !== $cached) {
            return $cached;
        }

        $map_table = $wpdb->prefix . 'coursetransit_instructor_course_map';
        $instructors_table = $wpdb->prefix . 'coursetransit_instructors';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Trusted prefixed table identifiers cannot be SQL value placeholders; course ID is prepared and result is cached.
        $rows = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT i.name
             FROM {$map_table} AS m
             INNER JOIN {$instructors_table} AS i ON i.id = m.instructor_id
             WHERE m.course_id = %d
               AND i.is_active = 1
               AND i.is_public = 1
             ORDER BY i.name ASC",
            $course_id
        ));

        if (empty($rows)) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Trusted prefixed table identifier cannot be an SQL value placeholder; course ID is prepared and result is cached.
            $rows = $wpdb->get_col($wpdb->prepare(
                "SELECT name
                 FROM {$instructors_table}
                 WHERE is_active = 1
                   AND is_public = 1
                   AND JSON_CONTAINS(courses, %s, '$')
                 ORDER BY name ASC",
                (string) $course_id
            ));
        }

        $rows = array_values(array_filter(array_map('sanitize_text_field', (array) $rows)));
        wp_cache_set($cache_key, $rows, 'coursetransit_blocks', 300);
        return $rows;
    }
}

if (!function_exists('coursetransit_blocks_course_image')) {
    function coursetransit_blocks_course_image($course, $product, $size = 'medium_large', $sizes = '100vw') {
        $url = trim((string) ($course['course_image_url'] ?? ''));
        $allowed_sizes = ['thumbnail', 'medium', 'medium_large', 'large', 'full'];
        $size = in_array($size, $allowed_sizes, true) ? $size : 'medium_large';
        $attachment_id = $url !== '' ? (int) attachment_url_to_postid($url) : 0;

        if (!$attachment_id && $product && $product->get_image_id()) {
            $attachment_id = (int) $product->get_image_id();
        }

        if ($attachment_id) {
            $image = wp_get_attachment_image($attachment_id, $size, false, [
                'alt'     => (string) ($course['fullname'] ?? ''),
                'loading' => 'lazy',
                'sizes'   => $sizes,
            ]);
            if ($image) {
                return $image;
            }
        }

        if ($url !== '') {
            return '<img src="' . esc_url($url) . '" alt="' . esc_attr((string) ($course['fullname'] ?? '')) . '" loading="lazy" decoding="async" />';
        }

        return '';
    }
}


if (!function_exists('coursetransit_blocks_get_course_short_description')) {
    /**
     * Resolve the learner-facing short description for course cards.
     * Prefer WooCommerce short description, then synced Moodle summary,
     * then the full WooCommerce description as a final fallback.
     */
    function coursetransit_blocks_get_course_short_description($course, $product = null) {
        $candidates = [];

        if ($product && is_object($product) && is_a($product, 'WC_Product')) {
            $candidates[] = $product->get_short_description();
        }

        $candidates[] = (string) ($course['summary'] ?? '');

        if ($product && is_object($product) && is_a($product, 'WC_Product')) {
            $candidates[] = $product->get_description();
        }

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }
}

if (!function_exists('coursetransit_blocks_get_course_full_description')) {
    /**
     * Resolve the full course description for the single-course overview.
     * Prefer the WooCommerce full description, then synced Moodle summary,
     * then WooCommerce short description.
     */
    function coursetransit_blocks_get_course_full_description($course, $product = null) {
        $candidates = [];

        if ($product && is_object($product) && is_a($product, 'WC_Product')) {
            $candidates[] = $product->get_description();
        }

        $candidates[] = (string) ($course['summary'] ?? '');

        if ($product && is_object($product) && is_a($product, 'WC_Product')) {
            $candidates[] = $product->get_short_description();
        }

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }
}


if (!function_exists('coursetransit_blocks_progress_error')) {
    /** Log only actual Moodle integration errors; never log successful requests or responses. */
    function coursetransit_blocks_progress_error($message, array $context = []) {
        if (class_exists('\\CourseTransit\\Support\\Logger')) {
            \CourseTransit\Support\Logger::error(
                'Moodle course progress: ' . sanitize_text_field($message),
                array_merge(['component' => 'block_progress'], $context)
            );
        }
    }
}

if (!function_exists('coursetransit_blocks_moodle_api_call')) {
    /** Call a Moodle function through the same CourseTransit auth wrapper used by existing API calls. */
    function coursetransit_blocks_moodle_api_call($function, array $params = []) {
        $settings = get_option('coursetransit_settings', []);
        $base_url = rtrim((string) ($settings['moodle_url'] ?? ''), '/');
        $token = (string) ($settings['moodle_token'] ?? '');
        if ($base_url === '' || $token === '') {
            coursetransit_blocks_progress_error('Moodle connection is not configured.', ['function' => $function]);
            return new WP_Error('coursetransit_moodle_not_configured', __('Moodle connection is not configured.', 'coursetransit'));
        }

        $payload = array_merge(['siteurl' => site_url()], $params);
        $request_body = [
            'wstoken' => $token,
            'wsfunction' => 'auth_coursetransit_execute_action',
            'moodlewsrestformat' => 'json',
            'function' => sanitize_key($function),
            'payload' => wp_json_encode($payload),
        ];
        

        $response = wp_remote_post($base_url . '/webservice/rest/server.php', [
            'timeout' => 25,
            'sslverify' => true,
            'headers' => ['Accept' => 'application/json', 'Origin' => site_url(), 'Referer' => site_url()],
            'body' => $request_body,
        ]);

        if (is_wp_error($response)) {
            coursetransit_blocks_progress_error('Moodle request failed.', ['function' => $function, 'error' => $response->get_error_message()]);
            return $response;
        }
        $http_status = (int) wp_remote_retrieve_response_code($response);
        $raw_body = (string) wp_remote_retrieve_body($response);
        if (200 !== $http_status) {
            coursetransit_blocks_progress_error('Moodle returned an HTTP error.', ['function' => $function, 'http_status' => $http_status]);
            return new WP_Error('coursetransit_moodle_http_error', sprintf(
                /* translators: %d: HTTP response status returned by Moodle. */
                __('Moodle returned HTTP status %d.', 'coursetransit'),
                $http_status
            ));
        }

        $json = json_decode($raw_body, true);
        if (!is_array($json)) {
            coursetransit_blocks_progress_error('Moodle returned invalid JSON.', ['function' => $function]);
            return new WP_Error('coursetransit_moodle_invalid_json', __('Moodle returned an invalid JSON response.', 'coursetransit'));
        }
        if (!empty($json['exception']) || empty($json['success'])) {
            $message = isset($json['message']) ? sanitize_text_field($json['message']) : 'CourseTransit wrapper returned success=false.';
            coursetransit_blocks_progress_error('Moodle API call failed.', ['function' => $function, 'message' => $message]);
            return new WP_Error('coursetransit_moodle_api_error', $message);
        }
        if (!array_key_exists('data', $json)) {
            coursetransit_blocks_progress_error('Moodle response did not contain data.', ['function' => $function]);
            return null;
        }
        $data = is_string($json['data']) ? json_decode($json['data'], true) : $json['data'];
        if (is_string($json['data']) && strtolower(trim($json['data'])) === 'null') {
            $data = null;
        }
        return $data;
    }
}


if (!function_exists('coursetransit_blocks_moodle_course_url')) {
    /** Build a direct Moodle course URL from the synced Moodle course ID. */
    function coursetransit_blocks_moodle_course_url($course) {
        $settings = get_option('coursetransit_settings', []);
        $base_url = rtrim((string) ($settings['moodle_url'] ?? ''), '/');
        $moodle_course_id = absint($course['moodle_id'] ?? 0);
        if ($base_url === '' || $moodle_course_id < 2) {
            return '';
        }
        return $base_url . '/course/view.php?id=' . $moodle_course_id;
    }
}

if (!function_exists('coursetransit_blocks_get_moodle_progress')) {
    /** Resolve real learner progress via Moodle completion services. */
    function coursetransit_blocks_get_moodle_progress($course, $user_id) {
        $user_id = absint($user_id);
        $moodle_course_id = absint($course['moodle_id'] ?? 0);
        if (!$user_id || !$moodle_course_id) {
            return null;
        }

        $cache_key = 'cts_block_progress_v3_' . $user_id . '_' . $moodle_course_id; // Versioned to bypass old cached 65% demo values.
        $cached = get_transient($cache_key);
        if (is_array($cached) && isset($cached['percent'])) {
            
            return max(0, min(100, (int) $cached['percent']));
        }

        $wp_user = get_userdata($user_id);
        if (!$wp_user || empty($wp_user->user_email)) {
            coursetransit_blocks_progress_error('WordPress learner email is unavailable.', ['wp_user_id' => $user_id, 'moodle_course_id' => $moodle_course_id]);
            return null;
        }

        // Match the WordPress account to Moodle using the same core user API used by CourseTransit enrollment.
        $users = coursetransit_blocks_moodle_api_call('core_user_get_users_by_field', [
            'field' => 'email',
            'values' => [$wp_user->user_email],
        ]);
        if (is_wp_error($users) || !is_array($users) || empty($users[0]['id'])) {
            coursetransit_blocks_progress_error('Could not match the WordPress learner to a Moodle user.', ['wp_user_id' => $user_id, 'moodle_course_id' => $moodle_course_id, 'error' => is_wp_error($users) ? $users->get_error_message() : 'No matching Moodle user']);
            return null;
        }
        $moodle_user_id = absint($users[0]['id']);
        

        $activities = coursetransit_blocks_moodle_api_call('core_completion_get_activities_completion_status', [
            'courseid' => $moodle_course_id,
            'userid' => $moodle_user_id,
        ]);
        $course_status = coursetransit_blocks_moodle_api_call('core_completion_get_course_completion_status', [
            'courseid' => $moodle_course_id,
            'userid' => $moodle_user_id,
        ]);

        if (is_wp_error($activities) || is_wp_error($course_status)) {
            coursetransit_blocks_progress_error('Moodle completion lookup failed.', ['wp_user_id' => $user_id, 'moodle_course_id' => $moodle_course_id, 'activities_error' => is_wp_error($activities) ? $activities->get_error_message() : '', 'course_status_error' => is_wp_error($course_status) ? $course_status->get_error_message() : '']);
            return null;
        }
        if (!is_array($activities)) {
            coursetransit_blocks_progress_error('Moodle returned an unexpected activity-completion response.', ['moodle_course_id' => $moodle_course_id]);
            return null;
        }

        // core_completion_get_activities_completion_status returns {statuses: [...]}.
        $statuses = isset($activities['statuses']) && is_array($activities['statuses']) ? $activities['statuses'] : array_values($activities);
        $total = 0;
        $completed = 0;
        foreach ($statuses as $activity) {
            if (!is_array($activity)) {
                continue;
            }
            // Ignore entries without a usable completion state (not completion-tracked).
            if (!array_key_exists('state', $activity) && !array_key_exists('timecompleted', $activity)) {
                continue;
            }
            $total++;
            if ((isset($activity['state']) && (int) $activity['state'] > 0) || !empty($activity['timecompleted'])) {
                $completed++;
            }
        }

        // Moodle's documented response nests completion details in completionstatus.
        $course_completion = is_array($course_status) ? ($course_status['completionstatus'] ?? $course_status) : [];
        $completed_flag = is_array($course_completion) ? ($course_completion['completed'] ?? false) : false;
        $is_complete = in_array($completed_flag, [true, 1, '1'], true);
        if ($is_complete) {
            $percent = 100;
        } elseif ($total > 0) {
            $percent = (int) round(($completed / $total) * 100);
        } else {
            // Valid response but no completion-tracked activities: do not invent a percentage.
            $percent = 0;
        }

        
        set_transient($cache_key, ['percent' => $percent, 'cached_at' => time()], 30); // WordPress removes expired transients automatically.
        return $percent;
    }
}

if (!function_exists('coursetransit_blocks_get_static_progress')) {
    /** Safe zero fallback used only when real Moodle progress is unavailable. */
    function coursetransit_blocks_get_static_progress($product_id) {
        // Never fabricate progress when Moodle is unavailable.
        return 0;
    }
}

if (!function_exists('coursetransit_blocks_get_course_progress')) {
    function coursetransit_blocks_get_course_progress($course, $user_id = 0) {
        $user_id = absint($user_id ?: get_current_user_id());
        $product_id = absint($course['wc_product_id'] ?? 0);
        $progress = coursetransit_blocks_get_moodle_progress($course, $user_id);
        if ($progress === null) {
            
            $progress = coursetransit_blocks_get_static_progress($product_id);
        }

        /**
         * Future Moodle/API integration point. Return an integer 0-100.
         */
        $progress = apply_filters('coursetransit_course_progress', $progress, $course, $user_id);
        $progress = max(0, min(100, (int) $progress));

        if ($progress >= 100) {
            $status = 'completed';
            $label = __('Completed', 'coursetransit');
        } elseif ($progress > 0) {
            $status = 'in-progress';
            $label = __('In Progress', 'coursetransit');
        } else {
            $status = 'not-started';
            $label = __('Not Started', 'coursetransit');
        }

        return [
            'percent' => $progress,
            'status'  => $status,
            'label'   => $label,
        ];
    }
}

if (!function_exists('coursetransit_blocks_render_progress')) {
    function coursetransit_blocks_render_progress($course, $user_id = 0, $compact = false) {
        $data = coursetransit_blocks_get_course_progress($course, $user_id);
        $class = $compact ? 'coursetransit-progress coursetransit-progress--compact' : 'coursetransit-progress';

        echo '<div class="' . esc_attr($class) . '" data-progress="' . esc_attr($data['percent']) . '">';
        echo '<div class="coursetransit-progress__header">';
        echo '<span class="coursetransit-progress__label">' . esc_html__('Progress', 'coursetransit') . '</span>';
        echo '<span class="coursetransit-progress__value">' . esc_html($data['percent']) . '%</span>';
        echo '</div>';
        echo '<div class="coursetransit-progress__track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . esc_attr($data['percent']) . '" aria-label="' . esc_attr($data['label']) . '">';
        echo '<span class="coursetransit-progress__fill coursetransit-progress__fill--' . esc_attr($data['status']) . '" style="inline-size:' . esc_attr($data['percent']) . '%"></span>';
        echo '</div>';
        echo '<span class="coursetransit-progress__status coursetransit-progress__status--' . esc_attr($data['status']) . '">' . esc_html($data['label']) . '</span>';
        echo '</div>';
    }
}
