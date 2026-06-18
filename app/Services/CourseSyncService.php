<?php
namespace CourseTransit\Services;

use Exception;
use WC_Product_Simple;
use CourseTransit\Services\MoodleClient;
use CourseTransit\Support\Logger;

class CourseSyncService
{
    public static function run(): array
    {
        $settings = get_option('coursetransit_settings', []);

        if (empty($settings['moodle_url']) || empty($settings['moodle_token'])) {
            throw new Exception('Moodle settings are missing');
        }

        $client = new MoodleClient(
            $settings['moodle_url'],
            $settings['moodle_token']
        );

        $courses = $client->fetchCourses();

        /* ==== NEW: Collect Moodle IDs for soft-delete detection ==== */
        $moodle_ids = array_column($courses, 'id');

        $stats = [
            'fetched' => count($courses),
            'inserted' => 0,
            'updated' => 0,
        ];

        foreach ($courses as $course) {

            $result = self::processCourse($client, $course);

            if ($result === 'inserted') {
                $stats['inserted']++;
            } elseif ($result === 'updated') {
                $stats['updated']++;
            }
        }

        /* ==== NEW: Soft delete missing courses ==== */
        self::markMissingCourses($moodle_ids);

        return $stats;
    }

    protected static function upsert(
        array $course,
        int $product_id,
        ?string $wpImageUrl,
        array $activities,
        array $curriculum
    ): string {
        global $wpdb;

        $table = esc_sql($wpdb->prefix . 'coursetransit_courses');
        $now = current_time('mysql');

        $data = [
            'moodle_id' => $course['id'],
            'wc_product_id' => $product_id,
            'shortname' => $course['shortname'] ?? '',
            'fullname' => $course['fullname'] ?? '',
            'displayname' => $course['displayname'] ?? null,
            'summary' => $course['summary'] ?? null,
            'format' => $course['format'] ?? null,
            'visible' => $course['visible'] ?? 1,
            'start_date' => $course['startdate'] ?? null,
            'end_date' => $course['enddate'] ?? null,
            'course_image_url' => $wpImageUrl,
            'activities_json' => wp_json_encode($activities),
            'raw_data' => wp_json_encode($course),
            'curriculum_json' => wp_json_encode($curriculum),
            'last_sync_status' => 'success',
            'last_synced_at' => $now,
            'updated_at' => $now,
        ];

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM " . esc_sql($table) . " WHERE moodle_id = %d",
                $course['id']
            )
        );

        if ($exists) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update($table, $data, ['moodle_id' => $course['id']]);
            return 'updated';
        }

        $data['created_at'] = $now;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->insert($table, $data);
        return 'inserted';
    }

    public static function syncOne(int $moodleId): void
    {
        $settings = get_option('coursetransit_settings', []);

        if (empty($settings['moodle_url']) || empty($settings['moodle_token'])) {
            throw new Exception('Moodle settings missing');
        }

        $client = new MoodleClient(
            $settings['moodle_url'],
            $settings['moodle_token']
        );

        try {
            $course = $client->fetchCourseById($moodleId);
        } catch (Exception $e) {

            // API failure vs real delete (important distinction)
            Logger::error(
                'Failed to fetch Moodle course',
                [
                    'module' => 'course_sync',
                    'moodle_course_id' => $moodleId,
                    'error_message' => $e->getMessage(),
                ]
            );

            throw $e; // don’t mark missing on API failure
        }

        // DELETE / NOT FOUND CASE
        if (!$course || empty($course['id'])) {

            Logger::warning(
                'Course no longer exists in Moodle',
                [
                    'module' => 'course_sync',
                    'moodle_course_id' => $moodleId,
                    'action' => 'mark_missing',
                ]
            );

            self::markOneMissing($moodleId);
            return; // stop execution
        }

        self::processCourse($client, $course);
    }

    protected static function processCourse(MoodleClient $client, array $course): string
    {
        if ((int) $course['id'] === 1) {
            return 'skipped';
        }

        global $wpdb;
        $table = esc_sql($wpdb->prefix . 'coursetransit_courses');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $settings = get_option('coursetransit_settings', []);

        // Product content sync mode: all, new, disabled
        $product_content_sync = sanitize_text_field(
            $settings['product_content_sync'] ?? 'all'
        );

        // Image sync mode: all, new, keep_existing, disabled
        $image_sync_mode = sanitize_text_field(
            $settings['image_sync'] ?? 'all'
        );

        // Curriculum sync mode: all, new, disabled
        $sync_curriculum = sanitize_text_field(
            $settings['sync_curriculum'] ?? 'all'
        );

        // Category sync mode: all, new, disabled
        $category_sync = sanitize_text_field(
            $settings['category_sync'] ?? 'all'
        );

        /* ===============================
         * 1. CREATE / UPDATE PRODUCT
         * =============================== */

        // Try to find existing product by DB record
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $product_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT wc_product_id FROM " . esc_sql($table) . " WHERE moodle_id = %d",
                $course['id']
            )
        );

        if (!$product_id) {

            // Fallback: try to find by meta
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            $existing = get_posts([
                'post_type' => 'product',

                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                'meta_key' => '_coursetransit_moodle_id',

                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
                'meta_value' => (string) $course['id'],

                'fields' => 'ids',
                'posts_per_page' => 1,
            ]);

            if (!empty($existing)) {
                $product_id = $existing[0];
            }
        }

        $product_already_exists = !empty($product_id);

        if (!$product_id) {

            $product_status = sanitize_text_field(
                $settings['product_status'] ?? 'publish'
            );

            $product = new WC_Product_Simple();

            $product->set_name($course['fullname']);
            $product->set_status($product_status);

            $product->set_virtual(true);
            $product->set_downloadable(true);
            $product->set_catalog_visibility('hidden');

            $product_id = $product->save();
        }

        /* ==== AUTO RESTORE ==== */

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $prev_status = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT last_sync_status FROM " . esc_sql($table) . " WHERE moodle_id = %d",
                $course['id']
            )
        );

        if ($prev_status === 'missing') {

            $product = wc_get_product($product_id);

            if ($product) {
                $product->set_status('publish');
                $product->set_catalog_visibility('hidden');
                $product->save();
            }

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update(
                $table,
                ['visible' => 1],
                ['moodle_id' => $course['id']]
            );

            Logger::success(
                'Previously missing course restored',
                [
                    'module' => 'course_sync',
                    'moodle_course_id' => $course['id'],
                    'product_id' => $product_id,
                ]
            );
        }

        /* ==== VISIBILITY SYNC ==== */

        if (isset($course['visible'])) {

            $product = wc_get_product($product_id);

            if ((int) $course['visible'] === 0) {

                if ($product) {
                    $product->set_status('draft');
                    $product->set_catalog_visibility('visible');
                    $product->save();
                }

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->update(
                    $table,
                    ['visible' => 0],
                    ['moodle_id' => $course['id']]
                );

                Logger::warning(
                    'Course hidden due to Moodle visibility',
                    [
                        'module' => 'course_sync',
                        'moodle_course_id' => $course['id'],
                        'visibility' => 0,
                        'product_id' => $product_id,
                    ]
                );
            }

            if ((int) $course['visible'] === 1) {

                if ($product && $product->get_status() === 'draft') {

                    $product->set_status('publish');

                    $product->set_catalog_visibility(
                        (int) ($course['visible'] ?? 1) === 1
                        ? 'visible'
                        : 'hidden'
                    );

                    $product->save();
                }

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->update(
                    $table,
                    ['visible' => 1],
                    ['moodle_id' => $course['id']]
                );
            }
        }

        update_post_meta(
            $product_id,
            '_coursetransit_moodle_id',
            $course['id']
        );

        /* ===============================
         * 2. PRODUCT CONTENT
         * =============================== */

        $post_content = $course['summary'] ?? '';

        if (
            $product_content_sync !== 'disabled' &&
            (
                !$product_already_exists ||
                $product_content_sync === 'all'
            )
        ) {

            wp_update_post([
                'ID' => $product_id,
                'post_title' => wp_strip_all_tags(
                    $course['fullname'] ?? 'Untitled Course'
                ),
                'post_content' => wp_kses_post($post_content),
                'post_excerpt' => wp_trim_words(
                    wp_strip_all_tags($post_content),
                    35
                ),
            ]);
        }

        /* ===============================
         * 3. IMAGE
         * =============================== */

        $wpImageUrl = null;

        $courseDetails = $client->fetchCourseById($course['id']);

        /* ===============================
         * COURSE CATEGORY SYNC
         * =============================== */

        $moodle_category = trim((string) (
            $course['categoryname']
            ?? $courseDetails['categoryname']
            ?? ''
        ));

        if ($moodle_category !== '') {

            $term = term_exists($moodle_category, 'product_cat');

            if (!$term) {

                $term = wp_insert_term(
                    $moodle_category,
                    'product_cat'
                );
            }

            if (!is_wp_error($term)) {

                $term_id = is_array($term)
                    ? (int) $term['term_id']
                    : (int) $term;

                wp_set_object_terms(
                    $product_id,
                    [$term_id],
                    'product_cat'
                );
            }
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $imageUrl = null;

        if (!empty($courseDetails['courseimage'])) {
            $imageUrl = $courseDetails['courseimage'];
        }

        if (!$imageUrl && !empty($courseDetails['overviewfiles'])) {

            foreach ($courseDetails['overviewfiles'] as $file) {

                if (
                    !empty($file['mimetype']) &&
                    str_starts_with($file['mimetype'], 'image/') &&
                    !empty($file['fileurl'])
                ) {
                    $imageUrl = $file['fileurl'];
                    break;
                }
            }
        }

        /* ===============================
         * IMAGE SYNC RULES
         * =============================== */

        if ($image_sync_mode === 'disabled') {
            $imageUrl = null;
        }

        if (
            $image_sync_mode === 'new' &&
            $product_already_exists
        ) {
            $imageUrl = null;
        }

        if (
            $image_sync_mode === 'keep_existing' &&
            get_post_thumbnail_id($product_id)
        ) {
            $imageUrl = null;
        }

        if ($imageUrl) {

            $old_thumbnail_id = get_post_thumbnail_id($product_id);

            if ($old_thumbnail_id) {
                wp_delete_attachment($old_thumbnail_id, true);
            }

            $attachment_id = media_sideload_image(
                $imageUrl,
                $product_id,
                null,
                'id'
            );

            if (!is_wp_error($attachment_id)) {

                set_post_thumbnail(
                    $product_id,
                    $attachment_id
                );

                $wpImageUrl = wp_get_attachment_url(
                    $attachment_id
                );
            }
        }

        /* ===============================
         * CURRICULUM SYNC RULES
         * =============================== */

        if (
            $sync_curriculum === 'disabled' ||
            (
                $sync_curriculum === 'new' &&
                $product_already_exists
            )
        ) {

            if ($sync_curriculum === 'disabled') {

                Logger::debug(
                    'Curriculum synchronization disabled',
                    [
                        'module' => 'course_sync',
                        'moodle_course_id' => $course['id'],
                    ]
                );

            }

            return self::upsert(
                $course,
                (int) $product_id,
                $wpImageUrl,
                [],
                []
            );
        }

        /* ===============================
         * 4. ACTIVITIES + CURRICULUM
         * =============================== */

        $contents = $client->fetchCourseContents($course['id']);

        $activities = [];

        foreach ($contents as $section) {

            foreach ($section['modules'] ?? [] as $module) {

                $activities[] = [
                    'name' => $module['name'] ?? '',
                    'type' => $module['modname'] ?? '',
                    'url' => $module['url'] ?? null,
                ];
            }
        }

        $curriculum = [];

        foreach ($contents as $section) {

            if (empty($section['modules'])) {
                continue;
            }

            $sectionBlock = [
                'title' => $section['name'] ?? 'Untitled section',
                'items' => [],
            ];

            foreach ($section['modules'] as $module) {

                $sectionBlock['items'][] = [
                    'title' => $module['name'] ?? '',
                    'type' => $module['modname'] ?? '',
                    'url' => $module['url'] ?? null,
                ];
            }

            $curriculum[] = $sectionBlock;
        }

        return self::upsert(
            $course,
            (int) $product_id,
            $wpImageUrl,
            $activities,
            $curriculum
        );
    }

    /* ==== NEW: Soft delete missing courses ==== */
    protected static function markMissingCourses(array $moodle_ids): void
    {
        global $wpdb;

        if (empty($moodle_ids)) {
            return;
        }

        // Ensure integers (safety)
        $moodle_ids = array_map('intval', $moodle_ids);

        $table = esc_sql($wpdb->prefix . 'coursetransit_courses');

        $settings = get_option('coursetransit_settings', []);

        $missing_course_action = sanitize_text_field(
            $settings['missing_course_action'] ?? 'draft'
        );

        // Create placeholders like %d, %d, %d
        $placeholders = implode(',', array_fill(0, count($moodle_ids), '%d'));

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $missing = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT moodle_id, wc_product_id
        FROM " . esc_sql($table) . "
        WHERE moodle_id NOT IN (" . $placeholders . ")",
                ...$moodle_ids
            )
        );
        // phpcs:enable

        if (empty($missing)) {
            return;
        }

        foreach ($missing as $course) {

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update(
                $table,
                [
                    'last_sync_status' => 'missing',
                    'visible' => 0,
                ],
                ['moodle_id' => $course->moodle_id]
            );

            if (!empty($course->wc_product_id)) {

                $product = wc_get_product($course->wc_product_id);

                if ($product) {

                    // Trash product
                    if ($missing_course_action === 'trash') {

                        wp_trash_post($course->wc_product_id);

                        Logger::warning(
                            'Missing Moodle course moved to trash',
                            [
                                'module' => 'course_sync',
                                'moodle_course_id' => $course->moodle_id,
                                'product_id' => $course->wc_product_id,
                            ]
                        );
                    }

                    // Keep product published
                    elseif ($missing_course_action === 'keep') {

                        Logger::warning(
                            'Missing Moodle course kept published',
                            [
                                'module' => 'course_sync',
                                'moodle_course_id' => $course->moodle_id,
                                'product_id' => $course->wc_product_id,
                                'strategy' => 'keep',
                            ]
                        );
                    }

                    // Default: mark draft
                    else {

                        $product->set_status('draft');
                        $product->set_catalog_visibility('hidden');
                        $product->save();

                        Logger::warning(
                            'Missing Moodle course marked as draft',
                            [
                                'module' => 'course_sync',
                                'moodle_course_id' => $course->moodle_id,
                                'product_id' => $course->wc_product_id,
                            ]
                        );
                    }
                }
            }
        }
    }

    public static function markOneMissing(int $moodleId): void
    {
        global $wpdb;

        $table = esc_sql($wpdb->prefix . 'coursetransit_courses');
        $settings = get_option('coursetransit_settings', []);
        $missing_course_action = sanitize_text_field(
            $settings['missing_course_action'] ?? 'draft'
        );

        // Fetch course from DB
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT moodle_id, wc_product_id 
                FROM " . esc_sql($table) . "
                WHERE moodle_id = %d 
                LIMIT 1",
                $moodleId
            )
        );

        // If not found, nothing to do
        if (!$row) {
            Logger::debug(
                'Missing course update skipped',
                [
                    'module' => 'course_sync',
                    'moodle_course_id' => $moodleId,
                    'reason' => 'course_not_found_in_local_database',
                ]
            );
            return;
        }

        // Update DB (same as bulk method)
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            $table,
            [
                'last_sync_status' => 'missing',
                'visible' => 0,
            ],
            ['moodle_id' => $moodleId],
            ['%s', '%d'],
            ['%d']
        );

        // Update Woo product
        if (!empty($row->wc_product_id)) {

            $product = wc_get_product((int) $row->wc_product_id);

            if ($product) {

                // Trash product
                if ($missing_course_action === 'trash') {

                    wp_trash_post($row->wc_product_id);

                    Logger::warning(
                        'Missing Moodle course moved to trash',
                        [
                            'module' => 'course_sync',
                            'moodle_course_id' => $row->moodle_id,
                            'product_id' => $row->wc_product_id,
                            'strategy' => 'trash',
                        ]
                    );
                }

                // Keep product published
                elseif ($missing_course_action === 'keep') {

                    Logger::warning(
                        'Missing Moodle course kept published',
                        [
                            'module' => 'course_sync',
                            'moodle_course_id' => $row->moodle_id,
                            'product_id' => $row->wc_product_id,
                            'strategy' => 'keep',
                        ]
                    );
                }

                // Default: mark draft
                else {

                    $product->set_status('draft');
                    $product->set_catalog_visibility('hidden');
                    $product->save();

                    Logger::warning(
                        'Missing Moodle course marked as draft',
                        [
                            'module' => 'course_sync',
                            'moodle_course_id' => $row->moodle_id,
                            'product_id' => $row->wc_product_id,
                            'strategy' => 'draft',
                        ]
                    );
                }
            }
        }
    }

    public static function runChunk(int $offset, int $limit): array
    {
        $settings = get_option('coursetransit_settings', []);

        $moodle_url = trim((string) ($settings['moodle_url'] ?? ''));
        $moodle_token = trim((string) ($settings['moodle_token'] ?? ''));

        /*
        |--------------------------------------------------------------------------
        | Validate Moodle Connection Settings
        |--------------------------------------------------------------------------
        */
        if (empty($moodle_url) || empty($moodle_token)) {

            throw new \Exception(
                'CourseTransit is not connected to a Moodle LMS yet. Please configure and connect a valid Moodle LMS from Settings before syncing courses.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Create Moodle Client
        |--------------------------------------------------------------------------
        */
        $client = new MoodleClient(
            $moodle_url,
            $moodle_token
        );

        /*
        |--------------------------------------------------------------------------
        | Fetch Courses
        |--------------------------------------------------------------------------
        */
        try {

            $courses = $client->fetchCourses();

        } catch (\Throwable $e) {

            throw new \Exception(
                'Unable to connect to Moodle. Please verify your Moodle URL and Token.'
            );
        }

        if (!is_array($courses)) {
            throw new \Exception(
                'Invalid response received from Moodle.'
            );
        }

        $courses = array_filter($courses, fn($c) => $c['id'] != 1);
        $courses = array_values($courses);

        $total = count($courses);

        if (
            $offset === 0 &&
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce already verified in controller.
            empty(sanitize_text_field(wp_unslash($_POST['processed_once'] ?? '')))
        ) {
            return [
                'total' => $total,
                'processed' => 0,
                'next_offset' => 0,
                'done' => false,
                'init' => true
            ];
        }

        $chunk = array_slice($courses, $offset, $limit);

        $processed = 0;

        foreach ($chunk as $course) {
            self::processCourse($client, $course);
            $processed++;
        }

        return [
            'total' => $total,
            'processed' => $offset + $processed,
            'next_offset' => $offset + $limit,
            'done' => ($offset + $processed) >= $total,
            'init' => false
        ];
    }
}
