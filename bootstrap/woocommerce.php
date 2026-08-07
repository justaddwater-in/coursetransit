<?php
defined('ABSPATH') || exit;

/**
 * Detect manual status changes on CourseTransit-linked products.
 *
 * Fires on every WC product save. If the save is NOT coming from
 * CourseSyncService itself (which sets CourseSyncService::isApplyingStatus()
 * around its own status-changing saves), and the product is one CourseTransit
 * created, we flip it out of "sync-managed" status. From that point on,
 * CourseSyncService will leave this product's status alone on future syncs,
 * so a manual change made here (wp-admin edit screen, the plugin's own
 * Quick Edit modal, WooCommerce REST API, etc) always sticks.
 */
add_action('woocommerce_before_product_object_save', function ($product) {

    if (\CourseTransit\Services\CourseSyncService::isApplyingStatus()) {
        // This save is CourseTransit re-applying the Product Status
        // setting itself — not a manual change. Leave the flag alone.
        return;
    }

    $product_id = $product->get_id();

    if (!$product_id) {
        return;
    }

    if (!get_post_meta($product_id, '_coursetransit_moodle_id', true)) {
        // Not a CourseTransit-linked product.
        return;
    }

    update_post_meta($product_id, '_coursetransit_status_managed', 'no');
});

// Always sell courses individually
add_filter('woocommerce_is_sold_individually', '__return_true');

// Change button text on product page
add_filter('woocommerce_product_single_add_to_cart_text', function () {
    return __('Enroll Now', 'coursetransit');
});

// Redirect directly to checkout after add to cart
add_filter('woocommerce_add_to_cart_redirect', function () {
    return wc_get_checkout_url();
});

// Redirect to checkout on cart errors
add_filter('woocommerce_cart_redirect_after_error', function () {
    return wc_get_checkout_url();
});



/**
 * Auto-complete free course orders
 */
add_action('woocommerce_thankyou', function ($order_id) {

    if (!$order_id) {
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    // Only auto-complete if payment is NOT required
    if ($order->get_status() === 'processing' && $order->get_total() == 0) {
        $order->update_status('completed', 'Auto-completed free course order');
    }

}, 10);

/**
 * Auto-complete free course orders
 */
add_action('woocommerce_order_status_completed', function ($order_id) {

    if (!$order_id) {
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    // Prevent duplicate enrollment
    if ($order->get_meta('_coursetransit_enrolled')) {
        return;
    }


    $email = $order->get_billing_email();
    $first = $order->get_billing_first_name();
    $last = $order->get_billing_last_name();

    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();

        \CourseTransit\Services\MoodleEnrollmentService::enroll(
            $email,
            $first,
            $last,
            $product_id
        );
    }

    // Mark order as enrolled
    $order->update_meta_data('_coursetransit_enrolled', 1);
    $order->save();

}, 10, 1);


add_action('wp_enqueue_scripts', function () {

    if (!is_product())
        return;

    // Register empty handle
    wp_register_style(
        'coursetransit-curriculum',
        false,
        [],
        COURSETRANSIT_VERSION
    );
    wp_enqueue_style('coursetransit-curriculum');

    // Add CSS inline
    wp_add_inline_style('coursetransit-curriculum', '
    #curriculum .accordion-item {
  border: none;
  background: transparent;
  margin-bottom: 12px;
}

#curriculum .accordion-button {
  background: #ffffff;
  border-radius: 14px;
  padding: 14px 18px;
  font-weight: 600;
  font-size: 15px;
  color: #0f172a;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
  transition: all 0.2s ease;
}

#curriculum .accordion-button:hover {
  background: #f8fafc;
}

#curriculum .accordion-button:focus {
  box-shadow: none;
}

/* Active state */
#curriculum .accordion-button:not(.collapsed) {
  background: #a9795c;
  color: #ffffff;
}

#curriculum .accordion-button:not(.collapsed)::after {
  filter: brightness(0) invert(1);
}

/* BODY MUST NEVER BE WHITE TEXT */
#curriculum .accordion-body {
  background: #ffffff;
  border-radius: 0 0 14px 14px;
  padding: 14px 18px 18px;
  font-size: 14px;
  line-height: 1.65;
  color: #374151; /* FIXED */
}


.coursetransit-learn-box {
  background: #f8f7f4;
  padding: 28px 32px;
  border-radius: 10px;
  margin-top: 30px;
}

.coursetransit-learn-box h3 {
  font-size: 20px;
  font-weight: 600;
  color: #111827;
  margin-bottom: 22px;
}

.coursetransit-learn-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  column-gap: 36px;
  row-gap: 18px;
}

.coursetransit-learn-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  font-size: 15px;
  line-height: 1.65;
  color: #374151;
}

/* Smaller subtle check */
.coursetransit-learn-item .check {
  width: 16px;
  height: 16px;
  min-width: 16px;
  border-radius: 50%;
  background: #a9795c; /* matches your theme */
  color: #ffffff;
  font-size: 11px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 4px;
}

/* Mobile */
@media (max-width: 768px) {
  .coursetransit-learn-grid {
    grid-template-columns: 1fr;
  }
}


    ');
});

add_filter('woocommerce_product_tabs', function ($tabs) {

    global $product, $wpdb;
    if (!$product)
        return $tabs;

    $id = $product->get_id();

    $course_table = esc_sql($wpdb->prefix . 'coursetransit_courses');
    $inst_table = esc_sql($wpdb->prefix . 'coursetransit_instructors');
    $map_table = esc_sql($wpdb->prefix . 'coursetransit_instructor_course_map');

    $instructors = [];

    /* Resolve course row id */
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    $course_row_id = $wpdb->get_var(
        $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Safe table name.
            "SELECT id FROM {$course_table} WHERE wc_product_id = %d",
            $id
        )
    );

    if ($course_row_id) {

        /* FAST mapping (supports multi instructor) */
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $instructors = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT i.post_id, i.name, i.avatar, i.headline, i.bio
                FROM " . esc_sql($map_table) . " m
                JOIN " . esc_sql($inst_table) . " i ON i.id = m.instructor_id
                WHERE m.course_id = %d",
                $course_row_id
            )
        );        
    }

    if (!empty($instructors)) {

        $tabs['coursetransit_instructor'] = [
            'title' => 'Instructor',
            'priority' => 17,
            'callback' => function () use ($instructors) {
                ?>

            <div style="padding:10px 0;">

                <?php foreach ($instructors as $inst): ?>

                    <?php
                        $instructor = $inst->name;
                        $bio = $inst->bio;
                        $title = $inst->headline;
                        $avatar_url = $inst->avatar;
                        $initial = strtoupper(substr($instructor, 0, 1));
                        ?>

                    <div style="display:flex;gap:16px;align-items:center;margin-bottom:14px;">

                        <!-- Avatar -->
                        <?php if ($avatar_url): ?>
                            <a href="<?php echo esc_url(get_permalink($inst->post_id ?? 0)); ?>">
                                <img src="<?php echo esc_url($avatar_url); ?>" alt=""
                                    style="width:70px;height:70px;border-radius:50%;object-fit:cover;">
                            </a>
                        <?php else: ?>
                            <div style="
                                    width:70px;height:70px;border-radius:50%;
                                    background:#f2ede9;
                                    display:flex;align-items:center;justify-content:center;
                                    font-weight:600;font-size:22px;color:#a9795c;">
                                <?php echo esc_html($initial); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Name + Title -->
                        <div>
                            <div style="font-size:17px;font-weight:600;">
                                <a href="<?php echo esc_url(get_permalink($inst->post_id ?? 0)); ?>"
                                    style="text-decoration:none;color:inherit;">
                                    <?php echo esc_html($instructor); ?>
                                </a>
                            </div>

                            <?php if ($title): ?>
                                <div style="font-size:14px;color:#777;margin-top:2px;">
                                    <?php echo esc_html($title); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Bio -->
                    <?php
                        echo $bio
                            ? wp_kses_post(wpautop($bio))
                            : esc_html__('Instructor details will be updated soon.', 'coursetransit');
                        ?>


                <?php endforeach; ?>

            </div>

            <?php
            }
        ];
    }

    return $tabs;
});

/**
 * Course Detail Tabs (Who This Is For / Gain / Outcomes / Curriculum)
 */
add_filter('woocommerce_product_tabs', function ($tabs) {

    global $product;
    if (!$product)
        return $tabs;

    $id = $product->get_id();

    $requirements = get_post_meta($id, '_coursetransit_requirements', true);
    $gain = get_post_meta($id, '_coursetransit_gain', true);
    $outcomes = get_post_meta($id, '_coursetransit_outcomes', true);
    $curriculum = get_post_meta($id, '_coursetransit_curriculum', true);

    /**
     * Smart Renderer
     * - Supports intro + bullets
     * - Works with only bullets
     * - Ignores empty lines
     */
    $render_smart = function ($text) {

        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text))));

        if (empty($lines)) {
            echo '<div style="color:#777;">Details will be updated soon.</div>';
            return;
        }

        $first = $lines[0];

        // Detect intro paragraph
        $is_intro =
            strlen($first) > 60 ||                // long sentence
            str_ends_with($first, ':') ||         // ends with colon
            preg_match('/[.!?]$/', $first);       // ends with punctuation

        echo '<div style="padding:10px 0;">';

        if ($is_intro) {
            $intro = array_shift($lines);
            echo '<p style="margin-bottom:10px;">' . esc_html($intro) . '</p>';
        }

        if (!empty($lines)) {
            echo '<ul style="padding-left:18px;margin:0;">';
            foreach ($lines as $line) {
                echo '<li style="margin-bottom:6px;">' . esc_html($line) . '</li>';
            }
            echo '</ul>';
        }

        echo '</div>';
    };

    /* Who This Is For */
    if (!empty(trim($requirements))) {
        $tabs['coursetransit_requirements'] = [
            'title' => 'Who This Is For',
            'priority' => 18,
            'callback' => function () use ($requirements, $render_smart) {
                $render_smart($requirements);
            }
        ];
    }

    /* What You'll Gain */
    if (!empty(trim($gain))) {
        $tabs['coursetransit_gain'] = [
            'title' => 'What You\'ll Gain',
            'priority' => 19,
            'callback' => function () use ($gain, $render_smart) {
                $render_smart($gain);
            }
        ];
    }

    /* Learning Outcomes */
    if (!empty(trim($outcomes))) {
        $tabs['coursetransit_outcomes'] = [
            'title' => 'Learning Outcomes',
            'priority' => 20,
            'callback' => function () use ($outcomes, $render_smart) {
                $render_smart($outcomes);
            }
        ];
    }

    /* Curriculum */
    if (!empty(trim($curriculum))) {
        $tabs['coursetransit_curriculum'] = [
            'title' => 'Curriculum',
            'priority' => 16,
            'callback' => function () use ($curriculum, $render_smart) {
                $render_smart($curriculum);
            }
        ];
    }

    return $tabs;
});



add_filter('the_content', function ($content) {

    if (!is_product() || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    global $product;
    if (!$product)
        return $content;

    $learnings = get_post_meta($product->get_id(), '_coursetransit_learnings', true);

    if (!$learnings)
        return $content;

    $items = array_filter(array_map('trim', explode("\n", $learnings)));

    ob_start();
    ?>

    <div class="woocommerce-product-details__short-description">
        <h2><?php esc_html_e('What you will learn', 'coursetransit'); ?></h2>

        <ul>
            <?php foreach ($items as $item): ?>
                <li><?php echo esc_html($item); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php

    $box = ob_get_clean();

    return $content . $box;
});


/**
 * Auto-complete paid virtual course orders (Woo native way)
 * Safe for Razorpay + LMS
 */
add_filter('woocommerce_payment_complete_order_status', function ($status, $order_id, $order) {

    if (!$order instanceof WC_Order) {
        return $status;
    }

    // Only for fully paid orders
    if (!$order->is_paid()) {
        return $status;
    }

    // Ensure ALL items are virtual (courses)
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();

        if (!$product || !$product->is_virtual()) {
            return $status; // keep default (Processing)
        }
    }

    // Let Woo naturally mark it Completed
    return 'completed';

}, 20, 3);

/* Remove WooCommerce Reviews Tab */
// add_filter('woocommerce_product_tabs', 'remove_reviews_tab', 98);
// function remove_reviews_tab($tabs)
// {
//     unset($tabs['reviews']);
//     return $tabs;
// }