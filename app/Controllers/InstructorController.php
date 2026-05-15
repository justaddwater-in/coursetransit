<?php
namespace CourseTransit\Controllers;

defined('ABSPATH') || exit;

class InstructorController extends BaseController
{
    public function index()
    {
        $this->render('instructors/index');
    }

    /* =========================================================
     * DATATABLE (matches CoursesController::table style)
     * =======================================================*/
    public function datatable()
    {
        check_ajax_referer('coursetransit_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'coursetransit_instructors';

        // DataTables params
        $draw = intval($_GET['draw'] ?? 1);
        $start = intval($_GET['start'] ?? 0);
        $length = intval($_GET['length'] ?? 10);
        $search = '';

        if (isset($_GET['search']['value'])) {
            $search = sanitize_text_field(wp_unslash($_GET['search']['value']));
        }

        $columns = [
            'sr',
            'avatar',
            'name',
            'email',
            'total_courses',
            'total_students',
            'rating',
            'is_active',
            'updated_at',
        ];

        $orderColumnIndex = isset($_GET['order'][0]['column'])
            ? intval($_GET['order'][0]['column'])
            : 2;

        $orderDir = isset($_GET['order'][0]['dir'])
            ? sanitize_text_field(wp_unslash($_GET['order'][0]['dir']))
            : 'asc';

        $orderDir = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';

        $orderColumn = $columns[$orderColumnIndex] ?? 'name';

        $allowedOrderColumns = [
            'name',
            'email',
            'total_courses',
            'total_students',
            'rating',
            'updated_at',
        ];

        if (!in_array($orderColumn, $allowedOrderColumns, true)) {
            $orderColumn = 'name';
        }

        $orderColumn = esc_sql($orderColumn);
        $orderDir = esc_sql($orderDir);
        /* -------------------- SEARCH FILTER -------------------- */
        $where_values = [];

        if ($search) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where_values = [$like, $like];
        }

        /* -------------------- TOTAL COUNT -------------------- */
        $total = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM " . esc_sql($table)
        );

        /* -------------------- FILTERED COUNT -------------------- */
        if (!empty($where_values)) {

            $filtered = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM " . esc_sql($table) . " 
                    WHERE name LIKE %s OR email LIKE %s",
                    $where_values[0],
                    $where_values[1]
                )
            );

        } else {
            $filtered = $total;
        }
        /* -------------------- MAIN QUERY -------------------- */
        $rows_key = 'ct_instructors_rows_' . md5(
            $search . $orderColumn . $orderDir . $length . $start
        );

        if (!empty($where_values)) {

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "
                SELECT id, name, email, avatar, courses,
                        total_courses, total_students, rating,
                        is_active, is_public, updated_at, post_id
                FROM " . esc_sql($table)
                    . "
                WHERE name LIKE %s OR email LIKE %s
                ORDER BY " . esc_sql($orderColumn) . " " . esc_sql($orderDir)
                    . "
                LIMIT %d OFFSET %d
                ",
                    $where_values[0],
                    $where_values[1],
                    $length,
                    $start
                ),
                ARRAY_A
            );

        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "
                SELECT id, name, email, avatar, courses,
                        total_courses, total_students, rating,
                        is_active, is_public, updated_at
                FROM " . esc_sql($table) . "
                ORDER BY " . esc_sql($orderColumn) . " " . esc_sql($orderDir) . "
                LIMIT %d OFFSET %d
                ",
                    $length,
                    $start
                ),
                ARRAY_A
            );

        }

        /* -------------------- FORMAT DATA -------------------- */
        $sr = $start + 1;

        $data = array_map(function ($row) use (&$sr, $wpdb) {

            $avatar = esc_url(
                $row['avatar'] ?: COURSETRANSIT_ASSETS_URL . 'images/avatar.png'
            );

            $courses = json_decode($row['courses'], true);
            $courses = is_array($courses) ? $courses : [];
            $courseCount = count($courses);

            $actions = [];
            $actions[] = '<div class="coursetransit-actions" style="display:flex;gap:6px;align-items:center;">';

            $profile_url = '';

            if (!empty($row['id'])) {
                $inst_table = $wpdb->prefix . 'coursetransit_instructors';
                $profile_key = 'ct_instructor_post_' . $row['id'];

                $post_id = wp_cache_get($profile_key);

                if ($post_id === false) {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                    $post_id = (int) $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT post_id FROM " . esc_sql($inst_table) . " WHERE id = %d",
                            $row['id']
                        )
                    );

                    wp_cache_set($profile_key, $post_id, '', 300);
                }

                if ($post_id) {
                    $profile_url = get_permalink($post_id);
                }
            }

            // if ($profile_url) {
            //     $actions[] = '
            // <a href="' . esc_url($profile_url) . '" target="_blank" class="button button-small" style="border-radius:4px;padding:2px 10px;display:flex;align-items:center;gap:4px;">
            //     <span class="material-icons" style="font-size:16px;">visibility</span>
            //     Public Page
            // </a>';
            // }

            $actions[] = '
        <button class="button button-small edit-instructor"
            data-id="' . intval($row['id']) . '"
            style="border-radius:4px;padding:2px 10px;display:flex;align-items:center;gap:4px;">
            <span class="material-icons" style="font-size:16px;">edit</span>
            Edit
        </button>';

            $actions[] = '
        <button class="button button-small delete-instructor"
            data-id="' . intval($row['id']) . '"
            style="border-color:#dc3545;background:#dc3545;color:#fff;border-radius:4px;padding:2px 10px;display:flex;align-items:center;gap:4px;">
            <span class="material-icons" style="font-size:16px;">delete</span>
            Delete
        </button>';

            $actions[] = '</div>';

            return [
                'sr' => $sr++,
                'avatar' => '<img src="' . $avatar . '" style="width:38px;height:38px;border-radius:50%;object-fit:cover;">',
                'name' => $profile_url
                    ? '<a href="' . esc_url($profile_url) . '" target="_blank" style="font-weight:600;">' . esc_html($row['name']) . '</a>'
                    : esc_html($row['name']),
                'email' => esc_html($row['email']),
                'courses' => '<a href="#" class="view-instructor-courses" data-id="' . intval($row['id']) . '" style="font-weight:600;">' . $courseCount . '</a>',
                // 'status' => $row['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>',
                'public' => $row['is_public'] ? '<span class="badge badge-success">Public</span>' : '<span class="badge badge-secondary">Private</span>',
                'updated_at' => $row['updated_at']
                    ? wp_date('Y-m-d H:i:s', (int) $row['updated_at'])
                    : '—',
                'actions' => implode('', $actions),
            ];
        }, $rows);

        wp_send_json([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }




    public function save()
    {
        check_ajax_referer('coursetransit_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        global $wpdb;
        $table = esc_sql($wpdb->prefix . 'coursetransit_instructors');
        $map_table = $wpdb->prefix . 'coursetransit_instructor_course_map';

        $id = intval($_POST['id'] ?? 0);

        /* ========= REQUIRED ========= */

        $name = isset($_POST['name'])
            ? sanitize_text_field(wp_unslash($_POST['name']))
            : '';
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $bio = isset($_POST['bio']) ? wp_kses_post(wp_unslash($_POST['bio'])) : '';

        if (!$name)
            wp_send_json_error(['message' => 'Name required'], 400);
        // if (!$email)
        //     wp_send_json_error(['message' => 'Email required'], 400);

        /* ========= OPTIONAL ========= */

        $headline = isset($_POST['headline'])
            ? sanitize_text_field(wp_unslash($_POST['headline']))
            : '';
        $avatar = isset($_POST['avatar']) ? esc_url_raw(wp_unslash($_POST['avatar'])) : '';
        $courses = isset($_POST['courses']) && is_array($_POST['courses'])
            ? array_values(array_filter(array_map('intval', wp_unslash($_POST['courses']))))
            : [];

        $focus_areas = isset($_POST['focus_areas'])
            ? sanitize_text_field(wp_unslash($_POST['focus_areas']))
            : '';

        $focus = !empty($focus_areas)
            ? array_filter(array_map('trim', explode(',', $focus_areas)))
            : [];

        $expertise_input = isset($_POST['expertise'])
            ? sanitize_text_field(wp_unslash($_POST['expertise']))
            : '';

        $expertise = !empty($expertise_input)
            ? array_filter(array_map('trim', explode(',', $expertise_input)))
            : [];
        /* ========= GENERATE SLUG ========= */

        $slug = sanitize_title($name);
        $base_slug = $slug;
        $i = 1;

        while (true) {

            $slug_key = 'ct_slug_check_' . md5($slug . $id);

            $exists = wp_cache_get($slug_key);

            if ($exists === false) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $exists = (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM " . esc_sql($table) . " WHERE slug = %s AND id != %d",
                        $slug,
                        $id
                    )
                );

                wp_cache_set($slug_key, $exists, '', 300);
            }

            if (!$exists) {
                break;
            }

            $slug = $base_slug . '-' . $i++;
        }

        /* ========= DB DATA ========= */

        $data = [
            'slug' => $slug,
            'name' => $name,
            'email' => $email,
            'headline' => $headline,
            'bio' => wp_kses_post($bio),
            'focus_areas' => json_encode($focus),
            'expertise' => json_encode($expertise),
            'courses' => json_encode($courses),
            'total_courses' => count($courses),
            'avatar' => $avatar,
            'is_active' => isset($_POST['is_active']) ? intval($_POST['is_active']) : 1,
            'is_public' => isset($_POST['is_public']) ? intval($_POST['is_public']) : 1,
            'updated_at' => time(),
        ];

        /* ========= INSERT / UPDATE DB ========= */

        if ($id) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->update($table, $data, ['id' => $id]);
        } else {
            $data['created_at'] = time();
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->insert($table, $data);
            $id = $wpdb->insert_id;
        }

        /* ========= HYBRID MAPPING SYNC ========= */

        // Remove old mapping
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->delete($map_table, ['instructor_id' => $id]);

        // Insert fresh mapping
        if (!empty($courses)) {
            foreach ($courses as $course_id) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $wpdb->insert($map_table, [
                    'instructor_id' => $id,
                    'course_id' => $course_id,
                ]);
            }
        }
        /* ========= SYNC WITH WP POST ========= */

        $post_key = 'ct_instructor_postid_' . $id;

        $post_id = wp_cache_get($post_key);

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $post_id = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT post_id FROM " . esc_sql($table) . " WHERE id = %d",
                    $id
                )
            );

        /* Fallback — find existing post by slug (prevents duplicates forever) */
        if (!$post_id) {
            $existing = get_page_by_path($slug, OBJECT, 'coursetransit_instructor');
            if ($existing) {
                $post_id = $existing->ID;

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $wpdb->update(
                    $table,
                    ['post_id' => $post_id],
                    ['id' => $id]
                );
            }
        }

        $post_data = [
            'post_title' => $name,
            'post_name' => $slug,
            'post_content' => wp_kses_post($bio),
            'post_status' => $data['is_public'] ? 'publish' : 'draft',
            'post_type' => 'ct_instructor',
        ];

        if ($post_id) {
            $post_data['ID'] = $post_id;
            wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->update(
                $table,
                ['post_id' => $post_id],
                ['id' => $id]
            );
        }

        /* ========= SYNC META ========= */

        update_post_meta($post_id, '_coursetransit_avatar', $avatar);
        update_post_meta($post_id, '_coursetransit_headline', $headline);
        update_post_meta($post_id, '_coursetransit_email', $email);
        update_post_meta($post_id, '_coursetransit_courses', $courses);
        update_post_meta($post_id, '_coursetransit_focus', $focus);
        update_post_meta($post_id, '_coursetransit_expertise', $expertise);

        wp_cache_delete('ct_instructors_total');

        wp_send_json_success(['message' => 'Instructor saved']);
    }


    /* =========================================================
     * DELETE
     * =======================================================*/
    public function delete()
    {
        check_ajax_referer('coursetransit_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        global $wpdb;
        $table = esc_sql($wpdb->prefix . 'coursetransit_instructors');

        $id = intval($_POST['id'] ?? 0);
        if (!$id)
            wp_send_json_error(['message' => 'Invalid ID'], 400);

        // Get linked WP post
        $post_key = 'ct_instructor_postid_' . $id;

        $post_id = wp_cache_get($post_key);

        if ($post_id === false) {

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $post_id = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT post_id FROM " . esc_sql($table) . " WHERE id = %d",
                    $id
                )
            );

            wp_cache_set($post_key, $post_id, '', 300);
        }

        if ($post_id) {
            wp_delete_post($post_id, true);
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->delete($table, ['id' => $id]);

        wp_cache_delete('ct_instructors_total');
        wp_send_json_success(['message' => 'Instructor deleted']);
    }


    public function courses()
    {
        check_ajax_referer('coursetransit_nonce');

        global $wpdb;
        $table = esc_sql($wpdb->prefix . 'coursetransit_courses');

        // Exclude Moodle default/system course (id = 1)
        $where = 'WHERE moodle_id != 1';

        $key = 'ct_courses_dropdown';

        $rows = wp_cache_get($key);

        if ($rows === false) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $rows = $wpdb->get_results("
                SELECT id, fullname
                FROM " . esc_sql($table) . "
                WHERE moodle_id != 1
                ORDER BY fullname ASC
            ");

            wp_cache_set($key, $rows, '', 300);
        }

        $data = array_map(function ($r) {
            return [
                'id' => (int) $r->id,
                'text' => $r->fullname
            ];
        }, $rows);

        wp_send_json_success($data);
    }


    public function get()
    {
        check_ajax_referer('coursetransit_nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        global $wpdb;
        $table = esc_sql($wpdb->prefix . 'coursetransit_instructors');

        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            wp_send_json_error(['message' => 'Invalid ID'], 400);
        }

        $key = 'ct_instructor_' . $id;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM " . esc_sql($table) . " WHERE id = %d", $id),
            ARRAY_A
        );

        if (!$row) {
            wp_send_json_error(['message' => 'Instructor not found'], 404);
        }

        // decode JSON fields safely
        $row['focus_areas'] = implode(', ', json_decode($row['focus_areas'] ?? '[]', true));
        $row['expertise'] = implode(', ', json_decode($row['expertise'] ?? '[]', true));
        $row['courses'] = json_decode($row['courses'] ?? '[]', true);

        wp_send_json_success($row);
    }

    public function coursesList()
    {
        check_ajax_referer('coursetransit_nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        global $wpdb;

        $insTable = esc_sql($wpdb->prefix . 'coursetransit_instructors');
        $courseTable = esc_sql($wpdb->prefix . 'coursetransit_courses');

        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            wp_send_json_error(['message' => 'Invalid instructor'], 400);
        }

        $key = 'ct_instructor_courses_' . $id;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $instructor = $wpdb->get_row(
            $wpdb->prepare("SELECT courses FROM " . esc_sql($insTable) . " WHERE id = %d", $id),
            ARRAY_A
        );

        if (!$instructor) {
            wp_send_json_error(['message' => 'Instructor not found'], 404);
        }

        $courseIds = json_decode($instructor['courses'], true) ?: [];

        if (!$courseIds) {
            wp_send_json_success(['courses' => []]);
        }

        $placeholders = implode(',', array_fill(0, count($courseIds), '%d'));

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $query = $wpdb->prepare(
            "SELECT id, fullname, shortname, course_image_url
            FROM " . esc_sql($courseTable) . "
            WHERE id IN (" . implode(',', array_fill(0, count($courseIds), '%d')) . ")",
            $courseIds
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared -- Query already prepared above.
        $courses = $wpdb->get_results($query, ARRAY_A);

        wp_send_json_success(['courses' => $courses]);
    }

}
