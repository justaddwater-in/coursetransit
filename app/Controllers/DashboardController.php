<?php
namespace CourseTransit\Controllers;

class DashboardController extends BaseController
{
    public function index()
    {
        global $wpdb;

        $all_orders = wc_get_orders([
            'limit' => -1,
            'status' => array_keys(wc_get_order_statuses()),
        ]);

        $ct_orders = array_filter($all_orders, function ($order) {
            return $this->isCourseTransitOrder($order);
        });

        $table = esc_sql($wpdb->prefix . 'wc_order_stats');
        $coursetransit_table = esc_sql($wpdb->prefix . 'coursetransit_courses');

        // Total Orders
        $orders_count = count(array_filter($ct_orders, function ($order) {
            return in_array(
                $order->get_status(),
                ['completed'],
                true
            );
        }));

        // Total Courses
        $courses_count = wp_cache_get('ct_courses_count');
        if ($courses_count === false) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $courses_count = (int) $wpdb->get_var(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT COUNT(*) FROM {$coursetransit_table} WHERE moodle_id != 1"
            );
            wp_cache_set('ct_courses_count', $courses_count, '', 300);
        }

        // Total Revenue
        $total_revenue = 0;

        foreach ($ct_orders as $order) {

            if (
                !in_array(
                    $order->get_status(),
                    ['completed', 'processing'],
                    true
                )
            ) {
                continue;
            }

            $total_revenue += (float) $order->get_total();
        }

        // Today's Orders
        $todays_orders = 0;

        $today = wp_date('Y-m-d');

        foreach ($ct_orders as $order) {

            $created = $order->get_date_created();

            if (
                $created &&
                $created->date('Y-m-d') === $today
            ) {
                $todays_orders++;
            }
        }

        // Monthly Revenue
        $monthly_revenue = 0;

        $current_year = wp_date('Y');
        $current_month = wp_date('m');

        foreach ($ct_orders as $order) {

            if (
                !in_array(
                    $order->get_status(),
                    ['completed', 'processing'],
                    true
                )
            ) {
                continue;
            }

            $created = $order->get_date_created();

            if (
                !$created ||
                $created->date('Y') !== $current_year ||
                $created->date('m') !== $current_month
            ) {
                continue;
            }

            $monthly_revenue += (float) $order->get_total();
        }

        // Latest Orders
        $latest_orders = [];
        if (function_exists('wc_get_orders')) {
            $latest_orders = array_slice($ct_orders, 0, 5);
        }

        // Latest Courses
        $latest_courses = wp_cache_get('ct_latest_courses');
        if ($latest_courses === false) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $latest_courses = $wpdb->get_results(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT id, fullname, shortname, last_synced_at, visible, wc_product_id
                    FROM " . esc_sql($coursetransit_table) . "
                    WHERE moodle_id != 1
                    ORDER BY last_synced_at DESC
                    LIMIT 5",
                ARRAY_A
            );
            wp_cache_set('ct_latest_courses', $latest_courses, '', 300);
        }

        // Orders Chart
        $orders_chart = [];

        // Prepare last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = wp_date('Y-m-d', strtotime("-{$i} days"));
            $orders_chart[$date] = 0;
        }

        // Count only CourseTransit orders
        foreach ($ct_orders as $order) {

            if (
                !in_array(
                    $order->get_status(),
                    ['completed', 'processing', 'on-hold'],
                    true
                )
            ) {
                continue;
            }

            $created = $order->get_date_created();

            if (!$created) {
                continue;
            }

            $day = $created->date('Y-m-d');

            if (isset($orders_chart[$day])) {
                $orders_chart[$day]++;
            }
        }

        wp_enqueue_script(
            'coursetransit-dashboard',
            \COURSETRANSIT_URL . 'assets/js/dashboard.js',
            ['jquery'],
            '1.0',
            true
        );

        wp_localize_script('coursetransit-dashboard', 'CourseTransitDashboard', [
            'ordersChart' => $orders_chart,
        ]);

        $this->render('dashboard/index', [
            'orders_count' => $orders_count,
            'courses_count' => $courses_count,
            'total_revenue' => $total_revenue,
            'todays_orders' => $todays_orders,
            'monthly_revenue' => $monthly_revenue,
            'latest_orders' => $latest_orders,
            'latest_courses' => $latest_courses,
            'orders_chart' => $orders_chart,
        ]);
    }

    protected function isCourseTransitOrder($order): bool
    {
        foreach ($order->get_items() as $item) {

            $product_id = $item->get_product_id();

            if (!$product_id) {
                continue;
            }

            $moodle_id = get_post_meta(
                $product_id,
                '_coursetransit_moodle_id',
                true
            );

            if (!empty($moodle_id)) {
                return true;
            }
        }

        return false;
    }
}