<?php
use CourseTransit\Helpers\Url;

if (!defined('ABSPATH')) {
    exit;
}
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
        <ul class="nav flex-column">

            <li class="nav-item">
                <a class="nav-link <?php echo esc_attr(Url::active('dashboard.index')); ?>"
                    style="text-decoration:none;"
                    href="<?php echo esc_url(admin_url('admin.php?page=coursetransit&route=dashboard.index')); ?>">
                    <i class="material-icons">dashboard</i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo esc_attr(Url::active('courses.index')); ?>" style="text-decoration:none;"
                    href="<?php echo esc_url(admin_url('admin.php?page=coursetransit&route=courses.index')); ?>">
                    <i class="material-icons">library_books</i>
                    <span>Courses</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo esc_attr(Url::active('instructors.index')); ?>"
                    style="text-decoration:none;"
                    href="<?php echo esc_url(admin_url('admin.php?page=coursetransit&route=instructors.index')); ?>">
                    <i class="material-icons">person</i>
                    <span>Instructors</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo esc_attr(Url::active('orders.index')); ?>" style="text-decoration:none;"
                    href="<?php echo esc_url(admin_url('admin.php?page=coursetransit&route=orders.index')); ?>">
                    <i class="material-icons">shopping_cart</i>
                    <span>Orders</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo esc_attr(Url::active('emails.index')); ?>" style="text-decoration:none;"
                    href="<?php echo esc_url(admin_url('admin.php?page=coursetransit&route=emails.index')); ?>">
                    <i class="material-icons">email</i>
                    <span>Emails</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo esc_attr(Url::active('settings.index')); ?>" style="text-decoration:none;"
                    href="<?php echo esc_url(admin_url('admin.php?page=coursetransit&route=settings.index')); ?>">
                    <i class="material-icons">settings</i>
                    <span>Settings</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo esc_url(admin_url()); ?>" style="text-decoration:none;">
                    <i class="material-icons">arrow_back</i>

                    <span>
                        WordPress Admin
                    </span>
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

            <li class="nav-item">
            <a class="nav-link d-flex align-items-center" target="_blank"
                href="https://justaddwater.in/products/coursetransit-wordpress-moodle-integration/#pricing"
                style="text-decoration:none; color: #4f46e5; font-weight: 600;">
                <i class="material-icons" style="color: #4f46e5;">rocket_launch</i>
                <span>Upgrade Now</span>
            </a>
        </li>
        </ul>
    </div>

</aside>