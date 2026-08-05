<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

/**
 * Remove plugin options
 */
delete_option('coursetransit_settings');
delete_option('coursetransit_db_version');
delete_option('coursetransit_email_templates');
delete_option('coursetransit_install_consent');
delete_option('coursetransit_install_consent_snooze_until');

/**
 * Remove custom tables
 */
$coursetransit_tables = [
    $wpdb->prefix . 'coursetransit_courses',
    $wpdb->prefix . 'coursetransit_instructors',
    $wpdb->prefix . 'coursetransit_instructor_course_map',
    $wpdb->prefix . 'coursetransit_logs',
];

foreach ($coursetransit_tables as $coursetransit_table) {

    $coursetransit_table = esc_sql($coursetransit_table);

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $wpdb->query("DROP TABLE IF EXISTS {$coursetransit_table}");
}