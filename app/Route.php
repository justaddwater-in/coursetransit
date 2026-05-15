<?php
namespace CourseTransit;

use CourseTransit\Controllers\DashboardController;
use CourseTransit\Controllers\CoursesController;
use CourseTransit\Controllers\SettingsController;
use CourseTransit\Controllers\OrdersController;
use CourseTransit\Controllers\EmailsController;
use CourseTransit\Controllers\InstructorController;

class Route
{
    public static function render()
    {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission.', 'coursetransit'));
        }

        /* --------------------
         | Register routes
         *-------------------*/

        // GET pages
        Router::get('dashboard.index', DashboardController::class);
        Router::get('emails.index', EmailsController::class);
        Router::get('courses.index', CoursesController::class);
        Router::get('instructors.index', InstructorController::class);
        Router::get('settings.index', SettingsController::class);

        Router::get('courses.table', CoursesController::class, 'table');
        Router::get('orders.index', OrdersController::class);

        // POST actions
        Router::post('courses.sync', CoursesController::class, 'sync');

        /* --------------------
         | Dispatch
         *-------------------*/
        Router::dispatch();
    }
}
