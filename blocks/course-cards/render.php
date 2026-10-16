<?php
/**
 * Dynamic renderer for the CourseTransit Course Cards block.
 *
 * @var array $attributes Block attributes.
 */

defined('ABSPATH') || exit;

(static function () use ($attributes) {

// Shared CourseTransit block helpers.
require_once dirname(__DIR__) . '/helpers.php';

global $wpdb;

$limit = isset($attributes['limit']) ? max(1, min(12, (int) $attributes['limit'])) : 6;
$columns = isset($attributes['columns']) ? max(1, min(4, (int) $attributes['columns'])) : 3;
$show_image = !isset($attributes['showImage']) || (bool) $attributes['showImage'];
$show_description = !isset($attributes['showDescription']) || (bool) $attributes['showDescription'];
$show_price = !isset($attributes['showPrice']) || (bool) $attributes['showPrice'];
$button_text = isset($attributes['buttonText']) ? trim((string) $attributes['buttonText']) : __('View Course', 'coursetransit');
$button_text = $button_text !== '' ? $button_text : __('View Course', 'coursetransit');
$image_size = isset($attributes['imageSize']) ? sanitize_key((string) $attributes['imageSize']) : 'medium_large';
$image_size = in_array($image_size, ['thumbnail', 'medium', 'medium_large', 'large', 'full'], true) ? $image_size : 'medium_large';
$image_ratio = isset($attributes['imageRatio']) ? sanitize_key((string) $attributes['imageRatio']) : '16-9';
$image_ratio = in_array($image_ratio, ['16-9', '4-3', '1-1', 'auto'], true) ? $image_ratio : '16-9';
$order_by = isset($attributes['orderBy']) ? sanitize_key((string) $attributes['orderBy']) : 'added';
$order_by = in_array($order_by, ['alpha', 'price', 'added', 'popular'], true) ? $order_by : 'added';
$order = isset($attributes['order']) ? sanitize_key((string) $attributes['order']) : 'desc';
$order = in_array($order, ['asc', 'desc'], true) ? $order : 'desc';
$category_id = isset($attributes['category']) ? absint($attributes['category']) : 0;
$instructor_id = isset($attributes['instructor']) ? absint($attributes['instructor']) : 0;


if (!function_exists('wc_get_product')) {
    return;
}

$table = $wpdb->prefix . 'coursetransit_courses';
$table_exists = wp_cache_get('coursetransit_courses_table_exists', 'coursetransit_blocks');
if (false === $table_exists) {
    // Table identifier is escaped for the LIKE expression and derived from the trusted WP prefix.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Existence check is cached immediately below.
    $table_exists = $wpdb->get_var(
        $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))
    );
    wp_cache_set('coursetransit_courses_table_exists', $table_exists, 'coursetransit_blocks', 300);
}

if ($table_exists !== $table) {
    if (current_user_can('edit_posts')) {
        echo '<div class="wp-block-group coursetransit-course-cards-empty">';
        echo '<p>' . esc_html__('CourseTransit courses table was not found: ', 'coursetransit');
        echo '<code>' . esc_html($table) . '</code></p></div>';
    }
    return;
}

$map_table = $wpdb->prefix . 'coursetransit_instructor_course_map';
$instructors_table = $wpdb->prefix . 'coursetransit_instructors';
$posts_table = $wpdb->posts;
$postmeta_table = $wpdb->postmeta;
$term_relationships = $wpdb->term_relationships;
$term_taxonomy = $wpdb->term_taxonomy;

$joins = "\nLEFT JOIN {$postmeta_table} AS ct_price_meta\n    ON ct_price_meta.post_id = c.wc_product_id\n    AND ct_price_meta.meta_key = '_price'\nLEFT JOIN {$postmeta_table} AS ct_sales_meta\n    ON ct_sales_meta.post_id = c.wc_product_id\n    AND ct_sales_meta.meta_key = '_total_sales'";

$where = [
    'c.moodle_id != %d',
    'c.visible = 1',
    'c.last_sync_status = %s',
];
$params = [1, 'success'];

if ($category_id > 0) {
    $where[] = "EXISTS (\n        SELECT 1\n        FROM {$term_relationships} AS ctr\n        INNER JOIN {$term_taxonomy} AS ctt ON ctt.term_taxonomy_id = ctr.term_taxonomy_id\n        WHERE ctr.object_id = c.wc_product_id\n          AND ctt.taxonomy = 'product_cat'\n          AND ctt.term_id = %d\n    )";
    $params[] = $category_id;
}

if ($instructor_id > 0) {
    $where[] = "EXISTS (\n        SELECT 1
        FROM {$map_table} AS ctim
        INNER JOIN {$instructors_table} AS cti ON cti.id = ctim.instructor_id
        WHERE ctim.course_id = c.id
          AND ctim.instructor_id = %d
          AND cti.is_active = 1
          AND cti.is_public = 1
    ) OR EXISTS (\n        SELECT 1
        FROM {$instructors_table} AS cti_legacy
        WHERE cti_legacy.id = %d
          AND cti_legacy.is_active = 1
          AND cti_legacy.is_public = 1
          AND JSON_CONTAINS(cti_legacy.courses, %s, '$')
    )";
    $params[] = $instructor_id;
    $params[] = $instructor_id;
    $params[] = (string) $instructor_id;
}

$direction = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
$secondary_direction = $direction === 'ASC' ? 'ASC' : 'DESC';

switch ($order_by) {
    case 'alpha':
        $order_sql = "c.fullname {$direction}, c.id {$secondary_direction}";
        break;
    case 'price':
        $order_sql = "CAST(COALESCE(NULLIF(ct_price_meta.meta_value, ''), '0') AS DECIMAL(20,6)) {$direction}, c.fullname ASC";
        break;
    case 'popular':
        $order_sql = "CAST(COALESCE(NULLIF(ct_sales_meta.meta_value, ''), '0') AS UNSIGNED) {$direction}, c.fullname ASC";
        break;
    case 'added':
    default:
        $order_sql = "c.created_at {$direction}, c.id {$secondary_direction}";
        break;
}

$sql = "SELECT DISTINCT c.id, c.moodle_id, c.wc_product_id, c.fullname, c.summary, c.course_image_url
    FROM {$table} AS c
    {$joins}
    WHERE " . implode(' AND ', $where) . "\n    ORDER BY {$order_sql}";

$query_cache_key = 'course_cards_' . md5($sql . maybe_serialize($params));
$courses = wp_cache_get($query_cache_key, 'coursetransit_blocks');
if (false === $courses) {
    // Table identifiers come from the trusted WP prefix; all variable values use prepare placeholders.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Dynamic identifiers cannot be placeholders; query values are prepared and results cached below.
    $courses = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
    wp_cache_set($query_cache_key, $courses, 'coursetransit_blocks', 60);
}

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'coursetransit-course-cards',
]);

$grid_gap = 'var(--wp--style--block-gap, 1.5rem)';
$grid_inline_style = 'display:grid;grid-template-columns:repeat(' . $columns . ',minmax(0,1fr));gap:' . $grid_gap . ';width:100%;max-width:100%;min-width:0;box-sizing:border-box;';

$placeholder = defined('COURSETRANSIT_ASSETS_URL')
    ? COURSETRANSIT_ASSETS_URL . 'images/course-placeholder1.png'
    : '';

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated by get_block_wrapper_attributes(), which escapes attribute values.
echo '<div ' . $wrapper_attributes . '>';
echo '<div class="coursetransit-course-cards-grid" style="--coursetransit-columns:' . esc_attr($columns) . ';' . esc_attr($grid_inline_style) . '">';

$rendered = 0;

foreach ($courses as $course) {
    $moodle_id = (int) ($course['moodle_id'] ?? 0);
    $product_id = (int) ($course['wc_product_id'] ?? 0);
    $product = $product_id ? wc_get_product($product_id) : false;

    // Recovery path when the stored mapping is stale or missing.
    if (!$product) {
        $product_ids = get_posts([
            'post_type' => 'product',
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'meta_key' => '_coursetransit_moodle_id',
            'meta_value' => (string) $moodle_id,
            'fields' => 'ids',
            'posts_per_page' => 1,
            'no_found_rows' => true,
        ]);

        if (!empty($product_ids)) {
            $product_id = (int) $product_ids[0];
            $product = wc_get_product($product_id);
        }
    }

    if (!$product || $product->get_status() !== 'publish') {
        continue;
    }

    $permalink = get_permalink($product_id);
    if (!$permalink) {
        continue;
    }

    $title = trim((string) ($course['fullname'] ?? ''));
    if ($title === '') {
        $title = $product->get_name();
    }
    if ($title === '') {
        $title = __('Untitled course', 'coursetransit');
    }

    $summary = coursetransit_blocks_get_course_short_description($course, $product);
    $summary = trim(wp_strip_all_tags($summary));
    $summary = $summary !== '' ? wp_trim_words($summary, 22, '…') : '';

    $image_url = trim((string) ($course['course_image_url'] ?? ''));
    $image_attachment_id = 0;

    if ($image_url !== '') {
        $image_attachment_id = (int) attachment_url_to_postid($image_url);
    }

    if (!$image_attachment_id && $product->get_image_id()) {
        $image_attachment_id = (int) $product->get_image_id();
    }

    if ($image_attachment_id) {
        $image_url = (string) wp_get_attachment_image_url($image_attachment_id, $image_size);
    }
    if ($image_url === '') {
        $image_url = $placeholder;
    }

    $price_html = $show_price ? $product->get_price_html() : '';
    $image_fallback = $placeholder !== '' ? ' data-coursetransit-fallback="' . esc_url($placeholder) . '"' : '';

    echo '<article class="coursetransit-course-card">';

    if ($show_image && $image_url !== '') {
        $ratio_class = 'coursetransit-course-card-image--' . $image_ratio;
        $image_box_style = 'display:block;width:100%;max-width:100%;min-width:0;box-sizing:border-box;overflow:hidden;';
        $image_style = 'display:block;width:100%;max-width:100%;margin:0;padding:0;box-sizing:border-box;';
        if ($image_ratio === 'auto') {
            $image_box_style .= 'height:auto;aspect-ratio:auto;';
            $image_style .= 'height:auto;object-fit:initial;';
        } else {
            $ratio_value = $image_ratio === '4-3' ? '4 / 3' : ($image_ratio === '1-1' ? '1 / 1' : '16 / 9');
            $image_box_style .= 'aspect-ratio:' . $ratio_value . ';';
            $image_style .= 'height:100%;object-fit:cover;';
        }
        $columns_for_sizes = max(1, min(4, $columns));
        $desktop_width = (int) floor(100 / $columns_for_sizes) . 'vw';
        $sizes_attr = '(max-width: 600px) 100vw, (max-width: 782px) 50vw, ' . $desktop_width;
        echo '<figure class="wp-block-image size-' . esc_attr($image_size) . ' coursetransit-course-card-image ' . esc_attr($ratio_class) . '" style="' . esc_attr($image_box_style) . '">';
        echo '<a href="' . esc_url($permalink) . '" aria-label="' . esc_attr($title) . '" style="display:block;width:100%;max-width:100%;margin:0;padding:0;box-sizing:border-box;">';
        if ($image_attachment_id) {
            echo wp_get_attachment_image(
                $image_attachment_id,
                $image_size,
                false,
                [
                    'alt' => $title,
                    'loading' => 'lazy',
                    'decoding' => 'async',
                    'sizes' => $sizes_attr,
                    'style' => $image_style,
                ]
            );
        } else {
            echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($title) . '" loading="lazy" decoding="async" sizes="' . esc_attr($sizes_attr) . '" style="' . esc_attr($image_style) . '"' . wp_kses($image_fallback, ['img' => ['data-coursetransit-fallback' => true]]) . ' />';
        }
        echo '</a></figure>';
    }

    echo '<div class="wp-block-group is-layout-flow coursetransit-course-card-content">';
    echo '<h3 class="wp-block-heading wp-block-post-title coursetransit-course-card-title">';
    echo '<a href="' . esc_url($permalink) . '">' . esc_html($title) . '</a></h3>';

    $card_terms = get_the_terms($product_id, 'product_cat');
    if (!is_wp_error($card_terms) && !empty($card_terms)) {
        echo '<div class="coursetransit-course-card-meta">' . esc_html(implode(', ', wp_list_pluck($card_terms, 'name'))) . '</div>';
    }

    if ($show_description && $summary !== '') {
        echo '<p class="coursetransit-course-card-description">' . esc_html($summary) . '</p>';
    }

    echo '<div class="wp-block-group is-layout-flex coursetransit-course-card-footer">';
    if ($show_price && $price_html !== '') {
        echo '<div class="price coursetransit-course-card-price">' . wp_kses_post($price_html) . '</div>';
    }

    echo '<div class="wp-block-buttons coursetransit-course-card-actions">';
    echo '<div class="wp-block-button">';
    echo '<a class="wp-block-button__link wp-element-button coursetransit-course-card-button" href="' . esc_url($permalink) . '">' . esc_html($button_text) . '</a>';
    echo '</div></div></div></div></article>';

    $rendered++;
    if ($rendered >= $limit) {
        break;
    }
}

echo '</div>';

if ($rendered === 0) {
    echo '<div class="wp-block-group coursetransit-course-cards-empty">';
    echo '<p>' . esc_html__('No published CourseTransit courses match the selected filters.', 'coursetransit') . '</p>';
    echo '</div>';
}

echo '</div>';

})();
