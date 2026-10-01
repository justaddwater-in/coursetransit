<?php
use CourseTransit\Helpers\Url;

if (!defined('ABSPATH')) {
    exit;
}
$coursetransit_pro_active = (bool) apply_filters('coursetransit_pro_active', false);
?>

<!-- <aside id="sidebar" class="main-sidebar col-12 col-md-3 col-lg-2 px-0"> -->
<aside id="sidebar" class="main-sidebar col-12 col-md-3 col-lg-2 px-0 d-flex flex-column" style="height:100vh;">

    <!-- TOP NAV -->
    <div class="main-navbar">
        <nav class="navbar align-items-stretch navbar-light bg-white flex-md-nowrap border-bottom p-0">
            <a class="navbar-brand w-100 me-0" href="#" style="line-height: 25px;">
                <div class="d-table m-auto">
                    <div class="d-flex align-items-center">
                        <img id="main-logo" src="<?php echo esc_url(COURSETRANSIT_URL . 'assets/images/logo.png'); ?>"
                            alt="CourseTransit" style="max-height: 30px;">
                    </div>
                </div>
            </a>
            <a class="toggle-sidebar d-sm-inline d-md-none d-lg-none">
                <i class="material-icons">&#xE5C4;</i>
            </a>
        </nav>
    </div>

    <!-- SEARCH -->
    <form class="main-sidebar__search w-100 border-right d-sm-flex d-md-none d-lg-none">
        <div class="input-group input-group-seamless me-3">
            <div class="input-group-prepend">
                <div class="input-group-text">
                    <i class="fas fa-search"></i>
                </div>
            </div>
            <input class="navbar-search form-control" type="text" placeholder="Search..." />
        </div>
    </form>

    <div class="nav-wrapper" style="flex:1; overflow-y:auto;">
        <?php
        $coursetransit_nav_items = [
            [
                'route' => 'dashboard.index',
                'icon' => 'dashboard',
                'label' => __('Dashboard', 'coursetransit'),
                'url' => admin_url('admin.php?page=coursetransit&route=dashboard.index'),
            ],
            [
                'route' => 'courses.index',
                'icon' => 'library_books',
                'label' => __('Courses', 'coursetransit'),
                'url' => admin_url('admin.php?page=coursetransit&route=courses.index'),
            ],
            [
                'route' => 'bundles.index',
                'icon' => 'collections_bookmark',
                'label' => __('Bundles', 'coursetransit'),
                'url' => '#',
                'pro' => true,
                'feature' => 'bundles',
            ],
            [
                'route' => 'instructors.index',
                'icon' => 'person',
                'label' => __('Instructors', 'coursetransit'),
                'url' => admin_url('admin.php?page=coursetransit&route=instructors.index'),
            ],
            [
                'route' => 'orders.index',
                'icon' => 'shopping_cart',
                'label' => __('Orders', 'coursetransit'),
                'url' => admin_url('admin.php?page=coursetransit&route=orders.index'),
            ],
            [
                'route' => 'emails.index',
                'icon' => 'email',
                'label' => __('Emails', 'coursetransit'),
                'url' => admin_url('admin.php?page=coursetransit&route=emails.index'),
            ],
            [
                'route' => 'settings.index',
                'icon' => 'settings',
                'label' => __('Settings', 'coursetransit'),
                'url' => admin_url('admin.php?page=coursetransit&route=settings.index'),
            ],
        ];

        /**
         * Extend the CourseTransit admin navigation from an add-on.
         *
         * Each item may contain: route, icon, label, url, pro, feature.
         * Core's PRO promotional items remain available when Pro is absent.
         */
        $coursetransit_nav_items = apply_filters(
            'coursetransit_admin_nav_items',
            $coursetransit_nav_items
        );
        ?>
        <ul class="nav flex-column">
            <?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local loop variable.
            foreach ($coursetransit_nav_items as $item): ?>
                <?php
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local variable.
                $is_pro = !empty($item['pro']);
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local variable.
                $feature = isset($item['feature']) ? sanitize_key($item['feature']) : '';
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local variable.
                $classes = 'nav-link';
                if (!$is_pro && !empty($item['route'])) {
                    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local variable.
                    $classes .= ' ' . Url::active($item['route']);
                }
                if ($is_pro) {
                    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local variable.
                    $classes .= ' coursetransit-pro-feature-trigger';
                }
                ?>
                <li class="nav-item">
                    <a class="<?php echo esc_attr($classes); ?>"
                        href="<?php echo esc_url($item['url'] ?? '#'); ?>"
                        style="text-decoration:none;"
                        <?php if ($is_pro): ?>data-pro-feature="<?php echo esc_attr($feature); ?>"<?php endif; ?>>
                        <i class="material-icons"><?php echo esc_html($item['icon'] ?? 'extension'); ?></i>
                        <span>
                            <?php echo esc_html($item['label'] ?? ''); ?>
                            <?php if ($is_pro): ?>
                                <span class="badge bg-warning text-dark ms-1">PRO</span>
                            <?php endif; ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>

            <li class="nav-item">
                <a class="nav-link" href="<?php echo esc_url(admin_url()); ?>" style="text-decoration:none;">
                    <i class="material-icons">arrow_back</i>
                    <span><?php echo esc_html__('WordPress Admin', 'coursetransit'); ?></span>
                </a>
            </li>
        </ul>
    </div>

    <div style="border-top:1px solid #e5e7eb; padding:12px 0; background:#fff;">
        <div style="font-size:11px; color:#9ca3af; padding:0 16px 8px;">
            SUPPORT
        </div>
        <ul class="nav flex-column">

            <li class="nav-item">
                <a class="nav-link d-flex align-items-center" style="text-decoration:none;" target="_blank"
                    href="https://justaddwater.in/documentation/coursetransit-guided-setup/">
                    <i class="material-icons">auto_fix_high</i>
                    <span>Guided Setup</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link d-flex align-items-center" style="text-decoration:none;" target="_blank"
                    href="https://justaddwater.in/products/coursetransit-help-center">
                    <i class="material-icons">help_outline</i>
                    <span>Help Center</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center" style="text-decoration:none;" target="_blank"
                    href="https://wordpress.org/support/plugin/coursetransit/reviews/">
                    <i class="material-icons">star_rate</i>
                    <span>Leave Us a Review</span>
                </a>
            </li>

            <?php if (!$coursetransit_pro_active): ?>
            <li class="nav-item">
            <a class="nav-link d-flex align-items-center" target="_blank"
                href="https://justaddwater.in/products/coursetransit-wordpress-moodle-integration/#pricing"
                style="text-decoration:none; color: #4f46e5; font-weight: 600;">
                <i class="material-icons" style="color: #4f46e5;">rocket_launch</i>
                <span>Upgrade Now</span>
            </a>
        </li>
        <?php endif; ?>
        </ul>
    </div>

</aside>