<?php
namespace CourseTransit;

class Installer
{
    const DB_VERSION = '1.2.3';

    public static function run()
    {
        self::create_tables();
        self::store_version();
    }

    protected static function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        /**
         * =========================
         * Courses Table
         * =========================
         */
        $table = $wpdb->prefix . 'coursetransit_courses';

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

            moodle_id BIGINT UNSIGNED NOT NULL,
            wc_product_id BIGINT UNSIGNED NULL,

            shortname VARCHAR(255) NOT NULL,
            fullname VARCHAR(255) NOT NULL,
            displayname VARCHAR(255) NULL,
            idnumber VARCHAR(255) NULL,

            category_id BIGINT UNSIGNED NULL,
            category_sortorder BIGINT UNSIGNED NULL,

            summary LONGTEXT NULL,
            summary_format TINYINT DEFAULT 1,
            format VARCHAR(50) NULL,

            show_grades TINYINT DEFAULT 1,
            show_reports TINYINT DEFAULT 0,
            visible TINYINT DEFAULT 1,
            hidden_sections TINYINT DEFAULT 0,

            news_items INT DEFAULT 0,
            num_sections INT DEFAULT 0,
            max_bytes BIGINT DEFAULT 0,

            group_mode TINYINT DEFAULT 0,
            group_mode_force TINYINT DEFAULT 0,
            default_grouping_id BIGINT DEFAULT 0,

            enable_completion TINYINT DEFAULT 0,
            completion_notify TINYINT DEFAULT 0,

            lang VARCHAR(20) NULL,
            force_theme VARCHAR(100) NULL,

            start_date BIGINT NULL,
            end_date BIGINT NULL,
            moodle_created_at BIGINT NULL,
            moodle_updated_at BIGINT NULL,

            course_image_url VARCHAR(512) NULL,
            activities_json LONGTEXT NULL,
            curriculum_json LONGTEXT NULL,

            last_sync_status VARCHAR(20) DEFAULT 'success',
            last_synced_at DATETIME NULL,

            raw_data LONGTEXT NULL,

            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,

            PRIMARY KEY (id),
            UNIQUE KEY moodle_id (moodle_id),
            KEY shortname (shortname),
            KEY category_id (category_id),
            KEY visible (visible),
            KEY last_synced_at (last_synced_at)

        ) {$charset_collate};";

        dbDelta($sql);

        /**
         * =========================
         * Instructors Table
         * =========================
         */
        $instructors_table = $wpdb->prefix . 'coursetransit_instructors';

        $sql = "CREATE TABLE {$instructors_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

            post_id BIGINT UNSIGNED NULL,
            wp_user_id BIGINT UNSIGNED NULL,

            slug VARCHAR(190) NOT NULL,
            name VARCHAR(190) NOT NULL,
            email VARCHAR(190) NULL,
            bio LONGTEXT NULL,

            avatar VARCHAR(255) NULL,
            cover_image VARCHAR(255) NULL,

            headline VARCHAR(255) NULL,
            focus_areas LONGTEXT NULL,
            expertise LONGTEXT NULL,

            courses LONGTEXT NULL,

            total_courses INT NOT NULL DEFAULT 0,
            total_students INT NOT NULL DEFAULT 0,
            rating DECIMAL(3,2) NOT NULL DEFAULT 0.00,
            total_reviews INT NOT NULL DEFAULT 0,

            is_active TINYINT(1) NOT NULL DEFAULT 1,
            is_public TINYINT(1) NOT NULL DEFAULT 1,

            moodle_user_id BIGINT NULL,
            last_synced_at BIGINT NULL,

            created_at BIGINT NOT NULL,
            updated_at BIGINT NOT NULL,

            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            UNIQUE KEY unique_post (post_id),

            KEY post_id (post_id),
            KEY wp_user_id (wp_user_id),
            KEY moodle_user_id (moodle_user_id),
            KEY is_active (is_active),
            KEY is_public (is_public),
            KEY updated_at (updated_at)

        ) {$charset_collate};";

        dbDelta($sql);

        /**
         * =========================
         * Instructor-Course Mapping
         * =========================
         */
        $map_table = $wpdb->prefix . 'coursetransit_instructor_course_map';

        $sql = "CREATE TABLE {$map_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

            instructor_id BIGINT UNSIGNED NOT NULL,
            course_id BIGINT UNSIGNED NOT NULL,

            PRIMARY KEY (id),

            UNIQUE KEY uniq_pair (instructor_id, course_id),
            KEY instructor_idx (instructor_id),
            KEY course_idx (course_id)

        ) {$charset_collate};";

        dbDelta($sql);

        /**
         * =========================
         * Logs Table
         * =========================
         */
        $logs_table = $wpdb->prefix . 'coursetransit_logs';

        $sql = "CREATE TABLE {$logs_table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        level VARCHAR(20) NOT NULL DEFAULT 'info',
        source VARCHAR(150) NULL,
        action VARCHAR(100) NULL,

        title TEXT NOT NULL,
        message LONGTEXT NULL,
        context LONGTEXT NULL,

        status VARCHAR(20) DEFAULT 'success',

        user_id BIGINT UNSIGNED NULL,

        ip_address VARCHAR(100) NULL,
        request_url LONGTEXT NULL,
        method VARCHAR(20) NULL,

        trace LONGTEXT NULL,

        created_at DATETIME NOT NULL,

        PRIMARY KEY (id),

        KEY level (level),
        KEY source (source),
        KEY action (action),
        KEY status (status),
        KEY user_id (user_id),
        KEY created_at (created_at)

        ) {$charset_collate};";

        dbDelta($sql);
    }

    protected static function store_version()
    {
        update_option('coursetransit_db_version', self::DB_VERSION);
    }
}