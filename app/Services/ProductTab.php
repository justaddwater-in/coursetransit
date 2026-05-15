<?php

namespace CourseTransit\Services;

if (!defined('ABSPATH')) {
    exit;
}

class ProductTab
{
    public static function init()
    {
        if (!class_exists('WooCommerce'))
            return;

        add_filter('woocommerce_product_data_tabs', [self::class, 'add_tab']);
        add_action('woocommerce_product_data_panels', [self::class, 'render_panel']);
        add_action('woocommerce_process_product_meta', [self::class, 'save_fields']);
    }

    public static function add_tab($tabs)
    {
        $tabs['coursetransit'] = [
            'label' => 'CourseTransit',
            'target' => 'coursetransit_product_data',
            'class' => ['show_if_simple', 'coursetransit_tab'],
            'priority' => 70,
        ];
        return $tabs;
    }

    public static function render_panel()
    {
        ?>

        <div id="coursetransit_product_data" class="panel woocommerce_options_panel">



            <!-- ================= Content Sections (Clean Accordion) ================= -->
            <div class="options_group">
                <h4 class="ct-section-title">Course Content</h4>

                <details>
                    <summary><strong>What You'll Learn</strong></summary>
                    <?php woocommerce_wp_textarea_input([
                        'id' => '_coursetransit_learnings',
                        'label' => '',
                        'description' => 'Each line becomes a bullet on frontend',
                    ]); ?>
                </details>

                <details>
                    <summary><strong>Who This Is For</strong></summary>
                    <?php woocommerce_wp_textarea_input([
                        'id' => '_coursetransit_requirements',
                        'label' => '',
                    ]); ?>
                </details>

                <details>
                    <summary><strong>What You\'ll Gain</strong></summary>
                    <?php woocommerce_wp_textarea_input([
                        'id' => '_coursetransit_gain',
                        'label' => '',
                    ]); ?>
                </details>

                <details>
                    <summary><strong>Learning Outcomes</strong></summary>
                    <?php woocommerce_wp_textarea_input([
                        'id' => '_coursetransit_outcomes',
                        'label' => '',
                    ]); ?>
                </details>

                <details>
                    <summary><strong>Curriculum (Short Overview)</strong></summary>
                    <?php woocommerce_wp_textarea_input([
                        'id' => '_coursetransit_curriculum',
                        'label' => '',
                    ]); ?>
                </details>

            </div>

            <div class="options_group">

            </div>

        </div>
        <?php
    }


    public static function save_fields($product_id)
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (
            !isset($_POST['woocommerce_meta_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['woocommerce_meta_nonce'])),
                'woocommerce_save_data'
            )
        ) {
            return;
        }

        $fields = [
            '_coursetransit_duration' => 'sanitize_text_field',
            '_coursetransit_level' => 'sanitize_text_field',
            '_coursetransit_language' => 'sanitize_text_field',
            '_coursetransit_learnings' => 'sanitize_textarea_field',
            '_coursetransit_requirements' => 'sanitize_textarea_field',
            '_coursetransit_gain' => 'sanitize_textarea_field',
            '_coursetransit_outcomes' => 'sanitize_textarea_field',
            '_coursetransit_curriculum' => 'sanitize_textarea_field',
            '_coursetransit_modules' => 'intval',
            '_coursetransit_videos' => 'intval',
            '_coursetransit_downloads' => 'intval',
        ];

        foreach ($fields as $key => $sanitize) {

            if (!isset($_POST[$key])) {
                continue;
            }

            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $value = wp_unslash($_POST[$key]);

            update_post_meta(
                $product_id,
                $key,
                call_user_func($sanitize, $value)
            );
        }

        $checkboxes = [
            '_coursetransit_certificate',
            '_coursetransit_lifetime_access',
            '_coursetransit_moneyback',
            '_coursetransit_resources',
            '_coursetransit_mobile_access',
            '_coursetransit_subtitles',
            '_coursetransit_online',
        ];

        foreach ($checkboxes as $key) {

            update_post_meta(
                $product_id,
                $key,
                isset($_POST[$key]) ? 'yes' : 'no'
            );
        }
    }
}
