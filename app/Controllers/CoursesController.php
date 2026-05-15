<?php
namespace CourseTransit\Controllers;

use CourseTransit\Services\CourseSyncService;

class CoursesController extends BaseController
{
    public function index()
    {
        $this->render('courses/index');
    }

    /**
     * AJAX: Sync courses from Moodle
     */
    public function sync()
    {
        check_ajax_referer('coursetransit_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $offset = intval($_POST['offset'] ?? 0);
        $limit = intval($_POST['limit'] ?? 5);

        try {
            $result = CourseSyncService::runChunk($offset, $limit);

            wp_send_json_success($result);

        } catch (\Throwable $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    public function table()
    {
        check_ajax_referer('coursetransit_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        global $wpdb;

        $table = esc_sql($wpdb->prefix . 'coursetransit_courses');

        $draw = intval($_GET['draw'] ?? 1);
        $start = intval($_GET['start'] ?? 0);
        $length = intval($_GET['length'] ?? 10);
        // $search = sanitize_text_field($_GET['search']['value'] ?? '');
        $search = isset($_GET['search']['value'])
            ? sanitize_text_field(wp_unslash($_GET['search']['value']))
            : '';

        $columns = [
            'sr',
            'course_image_url',
            'fullname',
            'shortname',
            'visible',
            'last_synced_at',
        ];

        $allowedOrderColumns = [
            'fullname',
            'shortname',
            'visible',
            'last_synced_at',
        ];

        $orderColumnIndex = intval($_GET['order'][0]['column'] ?? 2);
        $orderColumn = $columns[$orderColumnIndex] ?? 'fullname';

        if (!in_array($orderColumn, $allowedOrderColumns, true)) {
            $orderColumn = 'fullname';
        }

        $orderDir = ($_GET['order'][0]['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
        $orderDir = ($orderDir === 'DESC') ? 'DESC' : 'ASC';

        $where = 'WHERE moodle_id != %d';
        $whereArgs = [1];

        if ($search) {

            $like = '%' . $wpdb->esc_like($search) . '%';

            $where .= ' AND (fullname LIKE %s OR shortname LIKE %s)';

            $whereArgs[] = $like;
            $whereArgs[] = $like;
        }


        /* ---------------- TOTAL ---------------- */
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $total = (int) $wpdb->get_var(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE moodle_id != %d",
                1
            )
        );

        /* ---------------- FILTERED ---------------- */
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $filtered = (int) $wpdb->get_var(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} {$where}",
                ...$whereArgs
            )
        );

        /* ---------------- MAIN QUERY ---------------- */
        $queryArgs = array_merge($whereArgs, [$length, $start]);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $rows = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->prepare(
                "
            SELECT id, moodle_id, wc_product_id, fullname, shortname, visible,
                   last_synced_at, course_image_url, activities_json
            FROM {$table}
            {$where}
            ORDER BY
                CAST(visible AS UNSIGNED) DESC,
                last_synced_at DESC,
                {$orderColumn} {$orderDir}
            LIMIT %d OFFSET %d
            ",
                ...$queryArgs
            ),
            ARRAY_A
        );

        /* ---------------- FORMAT DATA ---------------- */
        $sr = $start + 1;

        $data = array_map(function ($row) use (&$sr) {

            $product_id = (int) ($row['wc_product_id'] ?? 0);
            $price = '—';

            if ($product_id) {
                $product = wc_get_product($product_id);

                if ($product) {
                    $regular = $product->get_regular_price();
                    $sale = $product->get_sale_price();

                    if ($sale) {
                        $price = '<del>' . wc_price($regular) . '</del> <ins>' . wc_price($sale) . '</ins>';
                    } elseif ($regular) {
                        $price = wc_price($regular);
                    }
                }
            }

            $placeholder = COURSETRANSIT_ASSETS_URL . 'images/course-placeholder1.png';

            $imageUrl = !empty($row['course_image_url'])
                ? esc_url($row['course_image_url'])
                : esc_url($placeholder);

            $image = '
            <img
                src="' . $imageUrl . '"
                alt="Course image"
                style="
                    width:42px;
                    height:42px;
                    object-fit:cover;
                    border-radius:6px;
                    background:#f3f4f6;
                "
            >
        ';

            $actions = [];
            $actions[] = '<div class="coursetransit-actions" style="
            display:flex;
            gap:6px;
            align-items:center;
        ">';

            /* Sync */
            $actions[] = '
            <button
                class="button button-small sync-course"
                data-moodle-id="' . intval($row['moodle_id']) . '"
                style="
                    border-radius:4px;
                    padding:2px 10px;
                    display:flex;
                    align-items:center;
                    gap:4px;
                ">
                <span class="material-icons" style="font-size:16px;">sync</span>
                Sync
            </button>
        ';

            /* Details */
            //     $actions[] = '
            //     <button
            //         class="button button-small view-activities"
            //         data-id="' . intval($row['id']) . '"
            //         style="
            //             border-radius:4px;
            //             padding:2px 10px;
            //             display:flex;
            //             align-items:center;
            //             gap:4px;
            //         ">
            //         <span class="material-icons" style="font-size:16px;">list_alt</span>
            //         Details
            //     </button>
            // ';

            /* WooCommerce product actions */
            if ($product_id) {

                $actions[] = '
                <button
                        class="button button-small view-activities"
                        data-id="' . intval($row['id']) . '"
                        style="
                            border-radius:4px;
                            padding:2px 10px;
                            display:flex;
                            align-items:center;
                            gap:4px;
                        ">
                        <span class="material-icons" style="font-size:16px;">list_alt</span>
                        Details
                    </button>
                    ';

                $actions[] = '
                <button
                    class="button button-small quick-edit-course"
                    data-product-id="' . $product_id . '"
                    data-course-id="' . intval($row['id']) . '"
                    style="
                        border-radius:4px;
                        padding:2px 10px;
                        display:flex;
                        align-items:center;
                        gap:4px;
                    ">
                    <span class="material-icons" style="font-size:16px;">bolt</span>
                    Quick Edit
                </button>
            ';

                // $actions[] = '
                //     <a href="' . esc_url(get_edit_post_link($product_id, '')) . '"
                //        class="button button-small"
                //        style="
                //             border-radius:4px;
                //             padding:2px 10px;
                //             display:flex;
                //             align-items:center;
                //             gap:4px;
                //        ">
                //         <span class="material-icons" style="font-size:16px;">edit</span>
                //         Edit
                //     </a>
                // ';
            }

            $actions[] = '</div>';

            return [
                'sr' => $sr++,
                'image' => $image,
                'fullname' => $product_id
                    ? '<a href="' . esc_url(get_permalink($product_id)) . '" target="_blank">
                        ' . esc_html($row['fullname']) . '
                    </a>'
                    : esc_html($row['fullname']),
                'shortname' => esc_html($row['shortname']),
                'visible' => (function () use ($product_id) {

                    if (!$product_id) {
                        return '<span class="badge badge-light">—</span>';
                    }

                    $product = wc_get_product($product_id);

                    if (!$product) {
                        return '<span class="badge badge-light">—</span>';
                    }

                    $status = $product->get_status(); // publish, draft, etc.
    
                    $map = [
                        'publish' => '<span class="badge badge-success">Published</span>',
                        'draft' => '<span class="badge badge-secondary">Draft</span>',
                        'pending' => '<span class="badge badge-secondary">Pending</span>',
                        'private' => '<span class="badge badge-secondary">Private</span>',
                    ];

                    return $map[$status] ?? '<span class="badge badge-light">' . ucfirst($status) . '</span>';

                })(),
                'last_synced_at' => esc_html($row['last_synced_at']),
                'price' => $price,
                'actions' => implode(' ', $actions),
            ];
        }, $rows);

        wp_send_json([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }



    public function activities()
    {
        check_ajax_referer('coursetransit_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $courseId = intval($_GET['id'] ?? 0);
        if (!$courseId) {
            wp_send_json_error('Invalid course ID', 400);
        }

        global $wpdb;
        $table = esc_sql($wpdb->prefix . 'coursetransit_courses');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $course = $wpdb->get_row(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->prepare("
            SELECT
                id,
                moodle_id,
                wc_product_id,
                fullname,
                shortname,
                displayname,
                idnumber,
                visible,
                format,
                num_sections,
                news_items,
                max_bytes,
                lang,
                force_theme,
                group_mode,
                group_mode_force,
                default_grouping_id,
                enable_completion,
                completion_notify,
                show_grades,
                show_reports,
                hidden_sections,
                start_date,
                end_date,
                moodle_created_at,
                moodle_updated_at,
                last_synced_at,
                course_image_url,
                curriculum_json
            FROM $table
            WHERE id = %d
        ", $courseId),
            ARRAY_A
        );

        if (!$course) {
            wp_send_json_error('Course not found', 404);
        }

        wp_send_json_success([
            'course' => [
                'id' => $course['id'],
                'moodle_id' => $course['moodle_id'],
                'fullname' => $course['fullname'],
                'shortname' => $course['shortname'],
                'displayname' => $course['displayname'],
                'idnumber' => $course['idnumber'],
                'visible' => $course['visible'] ? 'Visible' : 'Hidden',

                'format' => $course['format'],
                'num_sections' => $course['num_sections'],
                'news_items' => $course['news_items'],
                'max_bytes' => size_format($course['max_bytes']),
                'lang' => $course['lang'],
                'force_theme' => $course['force_theme'],

                'group_mode' => $course['group_mode'],
                'group_mode_force' => $course['group_mode_force'],
                'default_grouping' => $course['default_grouping_id'],

                'completion' => $course['enable_completion'] ? 'Enabled' : 'Disabled',
                'completion_notify' => $course['completion_notify'] ? 'Yes' : 'No',

                'show_grades' => $course['show_grades'] ? 'Yes' : 'No',
                'show_reports' => $course['show_reports'] ? 'Yes' : 'No',
                'hidden_sections' => $course['hidden_sections'],

                'start_date' => $course['start_date']
                    ? wp_date('Y-m-d', $course['start_date']) : '—',
                'end_date' => $course['end_date']
                    ? wp_date('Y-m-d', $course['end_date']) : '—',
                'created_at' => $course['moodle_created_at']
                    ? wp_date('Y-m-d', $course['moodle_created_at']) : '—',
                'updated_at' => $course['moodle_updated_at']
                    ? wp_date('Y-m-d', $course['moodle_updated_at']) : '—',

                'last_synced' => $course['last_synced_at'],
                'image' => $course['course_image_url'],

                'product' => [
                    'id' => $course['wc_product_id'],
                    'view' => $course['wc_product_id']
                        ? get_permalink($course['wc_product_id']) : null,
                    'edit' => $course['wc_product_id']
                        ? get_edit_post_link($course['wc_product_id'], '') : null,
                ],
            ],
            'curriculum' => json_decode($course['curriculum_json'], true) ?: []
        ]);
    }

    public function syncSingle()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        check_ajax_referer('coursetransit_nonce');

        $moodleId = intval($_POST['moodle_id'] ?? 0);

        if (!$moodleId) {
            wp_send_json_error(['message' => 'Invalid course ID'], 400);
        }

        try {
            CourseSyncService::syncOne($moodleId);

            wp_send_json_success([
                'message' => 'Course synced successfully'
            ]);
        } catch (\Throwable $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getProduct()
    {
        check_ajax_referer('coursetransit_nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Unauthorized', 403);
        }
        $product_id = intval($_GET['product_id'] ?? 0);

        if (!$product_id) {
            wp_send_json_error(['message' => 'Invalid product']);
        }

        $product = wc_get_product($product_id);

        if (!$product) {
            wp_send_json_error(['message' => 'Product not found']);
        }

        global $wpdb;
        $table = esc_sql($wpdb->prefix . 'coursetransit_courses');

        // Find linked course
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $course = $wpdb->get_row(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->prepare("SELECT * FROM $table WHERE wc_product_id = %d LIMIT 1", $product_id),
            ARRAY_A
        );

        wp_send_json_success([
            // PRODUCT
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'status' => $product->get_status(),
            'sku' => $product->get_sku(),
            'visibility' => $product->get_catalog_visibility(),
            'featured' => $product->get_featured() ? 'yes' : 'no',

            // LINKS
            'edit_url' => admin_url('post.php?post=' . $product_id . '&action=edit'),

            // 🔥 HEADER DATA (from course)
            'course_name' => $course['fullname'] ?? $product->get_name(),
            'course_shortname' => $course['shortname'] ?? '',
            'course_status' => isset($course['visible'])
                ? ($course['visible'] ? 'Visible' : 'Hidden')
                : ucfirst($product->get_status()),
            'course_image' => $course['course_image_url'] ?? '',
            'last_synced' => $course['last_synced_at'] ?? '',
        ]);
    }

    public function updateProduct()
    {
        check_ajax_referer('coursetransit_nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $product_id = intval($_POST['product_id'] ?? 0);
        $product = wc_get_product($product_id);

        if (!$product) {
            wp_send_json_error(['message' => 'Invalid product']);
        }

        /* ---------------- BASIC ---------------- */

        $name = isset($_POST['name'])
            ? sanitize_text_field(wp_unslash($_POST['name']))
            : '';

        $slug = isset($_POST['slug'])
            ? sanitize_title(wp_unslash($_POST['slug']))
            : '';

        $status = isset($_POST['status'])
            ? sanitize_text_field(wp_unslash($_POST['status']))
            : 'draft';

        /* ---------------- PRICING ---------------- */

        $price = isset($_POST['price'])
            ? sanitize_text_field(wp_unslash($_POST['price']))
            : '';

        $price = wc_format_decimal($price);

        $sale_price = isset($_POST['sale_price'])
            ? sanitize_text_field(wp_unslash($_POST['sale_price']))
            : '';

        $sale_price = wc_format_decimal($sale_price);

        /* ---------------- INVENTORY ---------------- */

        $sku = isset($_POST['sku'])
            ? sanitize_text_field(wp_unslash($_POST['sku']))
            : '';

        $manage_stock = isset($_POST['manage_stock'])
            && sanitize_text_field(wp_unslash($_POST['manage_stock'])) === 'yes';

        $stock_qty = isset($_POST['stock_qty'])
            ? intval(wp_unslash($_POST['stock_qty']))
            : 0;

        $stock_status = isset($_POST['stock_status'])
            ? sanitize_text_field(wp_unslash($_POST['stock_status']))
            : 'instock';

        /* ---------------- VISIBILITY ---------------- */

        $visibility = isset($_POST['visibility'])
            ? sanitize_text_field(wp_unslash($_POST['visibility']))
            : 'visible';

        $featured = isset($_POST['featured'])
            && sanitize_text_field(wp_unslash($_POST['featured'])) === 'yes';

        /* ---------------- UPDATE PRODUCT ---------------- */

        $product->set_name($name);
        $product->set_slug($slug);
        $product->set_status($status);

        $product->set_regular_price($price);
        $product->set_sale_price($sale_price);

        $product->set_sku($sku);
        $product->set_manage_stock($manage_stock);
        $product->set_stock_quantity($stock_qty);
        $product->set_stock_status($stock_status);

        $product->set_catalog_visibility($visibility);
        $product->set_featured($featured);

        $product->save();

        global $wpdb;

        $table = esc_sql($wpdb->prefix . 'coursetransit_courses');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            $table,
            [
                'fullname' => $name,
                'displayname' => $name,
                'visible' => $status === 'publish' ? 1 : 0,
                'updated_at' => current_time('mysql'),
            ],
            [
                'wc_product_id' => $product_id,
            ],
            [
                '%s',
                '%s',
                '%d',
                '%s',
            ],
            [
                '%d',
            ]
        );

        wp_send_json_success([
            'message' => 'Updated',
        ]);
    }

    public function fetchMoodleCourses()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        check_ajax_referer('coursetransit_nonce');

        try {
            $settings = get_option('coursetransit_settings', []);

            $baseUrl = $settings['moodle_url'] ?? '';
            $token = $settings['moodle_token'] ?? '';

            if (!$baseUrl || !$token) {
                wp_send_json_error('Moodle not configured');
            }

            $client = new \CourseTransit\Services\MoodleClient($baseUrl, $token);
            $response = $client->fetchCourses();

            $courses = $response['data'] ?? $response;

            if (empty($courses)) {
                wp_send_json_error('No courses found');
            }

            // Remove site course
            $courses = array_filter($courses, function ($c) {
                return ($c['id'] ?? 0) != 1;
            });

            // FETCH IMAGE PER COURSE
            global $wpdb;

            $table = esc_sql($wpdb->prefix . 'coursetransit_courses');

            $courses = array_map(function ($course) use ($wpdb, $table) {

                // Default placeholder
                $course['image'] = COURSETRANSIT_ASSETS_URL . 'images/course-placeholder1.png';

                // Fetch locally stored WP image
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                $local_image = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT course_image_url
                        FROM {$table}
                        WHERE moodle_id = %d
                        LIMIT 1",
                        $course['id']
                    )
                );

                if (!empty($local_image)) {
                    $course['image'] = esc_url($local_image);
                }

                return $course;

            }, $courses);

            // \CourseTransit\Support\Logger::log('FINAL COURSES', $courses);

            wp_send_json_success(array_values($courses));

        } catch (\Throwable $e) {
            wp_send_json_error($e->getMessage(), 500);
        }
    }

}
