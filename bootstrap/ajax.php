<?php
defined('ABSPATH') || exit;

use CourseTransit\Controllers\CoursesController;
use CourseTransit\Controllers\OrdersController;
use CourseTransit\Controllers\SettingsController;
use CourseTransit\Controllers\EmailsController;
use CourseTransit\Controllers\InstructorController;
use CourseTransit\Controllers\LogsController;

add_action('wp_ajax_coursetransit_sync_courses', function () {
    (new CoursesController())->sync();
});
add_action('wp_ajax_coursetransit_courses_activities', function () {
    (new CoursesController())->activities();
});

add_action('wp_ajax_coursetransit_courses_table', function () {
    (new CoursesController())->table();
});

add_action('wp_ajax_coursetransit_sync_single_course', function () {
    (new CoursesController())->syncSingle();
});

add_action('wp_ajax_coursetransit_get_course_settings', function () {
    (new CoursesController())->getCourseSettings();
});

add_action('wp_ajax_coursetransit_save_course_settings', function () {
    (new CoursesController())->saveCourseSettings();
});

add_action('wp_ajax_coursetransit_get_product', function () {
    (new CoursesController())->getProduct();
});

add_action('wp_ajax_coursetransit_update_product', function () {
    (new CoursesController())->updateProduct();
});

add_action('wp_ajax_coursetransit_fetch_moodle_courses', function () {
    (new CoursesController())->fetchMoodleCourses();
});

add_action('wp_ajax_coursetransit_orders_table', function () {
    (new OrdersController())->table();
});

add_action('wp_ajax_coursetransit_order_details', function () {
    (new OrdersController())->details();
});

add_action('wp_ajax_coursetransit_settings_test', function () {

    check_ajax_referer('coursetransit_nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['error' => 'Unauthorized']);
    }

    if (!class_exists(SettingsController::class)) {
        wp_send_json_error(['error' => 'SettingsController not found']);
    }

    $controller = new SettingsController();

    if (!method_exists($controller, 'testAndSave')) {
        wp_send_json_error(['error' => 'testAndSave method missing']);
    }

    $controller->testAndSave();
});

add_action('wp_ajax_coursetransit_send_test_email', [EmailsController::class, 'sendTest']);

add_action('wp_ajax_coursetransit_save_email_template', function () {

    check_ajax_referer('coursetransit_nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    if (!isset($_POST['template'], $_POST['subject'], $_POST['body'])) {
        wp_send_json_error('Invalid request');
    }

    $template_key = sanitize_key(
        wp_unslash($_POST['template'])
    );

    // Allow current + new template
    if (!in_array($template_key, ['enrollment', 'enrollment_existing'], true)) {
        wp_send_json_error('Invalid template');
    }

    $templates = get_option('coursetransit_email_templates', []);

    $templates[$template_key] = [
        'subject' => sanitize_text_field(wp_unslash($_POST['subject'])),
        'body' => wp_kses_post(wp_unslash($_POST['body'])),
    ];

    update_option('coursetransit_email_templates', $templates);

    wp_send_json_success([
        'message' => 'Template saved successfully.',
    ]);
});

add_action('wp_ajax_coursetransit_get_email_template', function () {

    check_ajax_referer('coursetransit_nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $key = '';

    if (isset($_POST['template'])) {

        $key = sanitize_key(
            wp_unslash($_POST['template'])
        );
    }

    $templates = get_option('coursetransit_email_templates', []);

    $template = $templates[$key] ?? [
        'subject' => '',
        'body' => '',
    ];

    wp_send_json_success($template);
});

/* =========================================================
 * INSTRUCTORS
 * =======================================================*/

add_action('wp_ajax_coursetransit_instructors_table', function () {
    (new InstructorController())->datatable();
});
add_action('wp_ajax_coursetransit_delete_instructor', function () {
    (new InstructorController())->delete();
});
add_action('wp_ajax_coursetransit_get_courses', fn() => (new InstructorController())->courses());
add_action('wp_ajax_coursetransit_save_instructor', fn() => (new InstructorController())->save());
add_action('wp_ajax_coursetransit_get_instructor', fn() => (new InstructorController())->get());
add_action('wp_ajax_coursetransit_instructor_courses', function () {
    (new InstructorController())->coursesList();
});

add_action('wp_ajax_coursetransit_logs_table', function () {
    (new LogsController())->datatable();
});

add_action('wp_ajax_coursetransit_get_log_settings', function () {
    (new LogsController())->getSettings();
});

add_action('wp_ajax_coursetransit_save_log_settings', function () {
    (new LogsController())->saveSettings();
});