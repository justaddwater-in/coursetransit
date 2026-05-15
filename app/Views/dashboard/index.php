<?php
/**
 * Dashboard view
 * - Displays key stats and charts
 * - Shows setup checklist if WooCommerce settings are not configured properly
 */
if (!defined('ABSPATH')) {
  exit;
}
?>


<?php

$coursetransit_issues = [];

if (get_option('woocommerce_enable_signup_and_login_from_checkout') !== 'yes') {
  $coursetransit_issues[] = 'Enable login during checkout';
}

if (get_option('woocommerce_enable_myaccount_registration') !== 'yes') {
  $coursetransit_issues[] = 'Allow account creation during checkout';
}

if (get_option('woocommerce_registration_generate_password') !== 'no') {
  $coursetransit_issues[] = 'Enable password setup email';
}


$coursetransit_checks = [

  'Enable login during checkout' => [
    'value' => get_option('woocommerce_enable_checkout_login_reminder'),
    'expected' => 'yes',
    'desc' => 'Allows existing users to log in during checkout.',
  ],

  'Allow account creation during checkout' => [
    'value' => get_option('woocommerce_enable_signup_and_login_from_checkout'),
    'expected' => 'yes',
    'desc' => 'Lets new users create an account while purchasing a course.',
  ],

  'Send password setup email' => [
    'value' => get_option('woocommerce_registration_generate_password'),
    'expected' => 'yes',
    'desc' => 'Sends users a secure link to set their password after signup.',
  ],
];

$coursetransit_status_list = [];
$coursetransit_issues = [];

foreach ($coursetransit_checks as $coursetransit_label => $coursetransit_check) {

  $coursetransit_is_enabled = ($coursetransit_check['value'] === $coursetransit_check['expected']);

  $coursetransit_status_list[] = [
    'label' => $coursetransit_label,
    'status' => $coursetransit_is_enabled,
    'desc' => $coursetransit_check['desc'],
  ];

  if (!$coursetransit_is_enabled) {
    $coursetransit_issues[] = $coursetransit_label;
  }
}

$coursetransit_should_show = !empty($coursetransit_issues);

if ($coursetransit_should_show): ?>
  <div id="ct-setup-modal" class="ct-modal">
    <div class="ct-card">

      <div class="ct-header">
        <h3>Complete WooCommerce Setup</h3>
        <p>
          To ensure CourseTransit works correctly with Moodle and course purchases,
          a few WooCommerce settings need to be enabled.
        </p>
      </div>

      <div class="ct-body">

        <div class="ct-help">
          These settings are required to automatically create user accounts,
          allow login during checkout, and properly grant access to purchased courses.
        </div>

        <!-- SIDE BY SIDE SECTION -->
        <div class="ct-setup-layout">

          <!-- LEFT: CHECKLIST -->
          <div class="ct-setup-col">
            <ul class="ct-checklist">
              <?php foreach ($coursetransit_status_list as $coursetransit_item): ?>
                <li class="<?php echo $coursetransit_item['status'] ? 'ok' : 'bad'; ?>">
                  <span class="ct-icon">
                    <?php echo $coursetransit_item['status'] ? '✓' : ''; ?>
                  </span>

                  <div class="ct-text">
                    <strong><?php echo esc_html($coursetransit_item['label']); ?></strong>
                    <div class="ct-desc"><?php echo esc_html($coursetransit_item['desc']); ?></div>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <!-- RIGHT: IMAGE -->
          <div class="ct-setup-col">
            <strong class="ct-setup-heading">
              Where to find these settings:
            </strong>

            <img src="<?php echo \esc_url(COURSETRANSIT_URL . 'assets/images/wc-account_2.png'); ?>"
              class="ct-setup-image">
          </div>

        </div>

      </div>

      <div class="ct-footer">
        <div class="ct-actions">
          <div class="left">
            You only need to configure this once.
          </div>

          <div class="ct-action-buttons">
            <button id="ct-auto-fix" class="button button-secondary">
              Fix Automatically
            </button>

            <a href="<?php echo \esc_url(admin_url('admin.php?page=wc-settings&tab=account')); ?>"
              class="button button-primary">
              Open Settings
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>
<?php endif; ?>

<!-- PAGE HEADER -->
<div class="page-header row no-gutters py-4 ct-page-header">
  <div class="col-12 text-sm-left mb-0">
    <h3 class="page-title">Dashboard </h3>
  </div>
</div>

<!-- STATISTICS CARDS -->
<div class="row">

  <!-- Total Revenue -->
  <div class="col mb-4">
    <div class="stats-small stats-small--1 card card-small h-100">
      <div class="card-body p-0 d-flex align-items-center justify-content-center">
        <div class="stats-small__data text-center">
          <span class="stats-small__label text-uppercase">Total Revenue</span>

          <h6 class="stats-small__value count my-3">
            <?php echo function_exists('wc_price')
              ? wp_kses_post(wc_price($total_revenue))
              : esc_html(number_format($total_revenue, 2)); ?>
          </h6>
        </div>
      </div>
    </div>
  </div>

  <!-- This Month Revenue -->
  <div class="col mb-4">
    <div class="stats-small stats-small--1 card card-small h-100">
      <div class="card-body p-0 d-flex align-items-center justify-content-center">
        <div class="stats-small__data text-center">
          <span class="stats-small__label text-uppercase">This Month Revenue</span>

          <h6 class="stats-small__value count my-3">
            <?php echo function_exists('wc_price')
              ? wp_kses_post(wc_price($monthly_revenue))
              : esc_html(number_format($monthly_revenue, 2)); ?>
          </h6>
        </div>
      </div>
    </div>
  </div>

  <!-- Today's Orders -->
  <div class="col mb-4">
    <div class="stats-small stats-small--1 card card-small h-100">
      <div class="card-body p-0 d-flex align-items-center justify-content-center">
        <div class="stats-small__data text-center">
          <span class="stats-small__label text-uppercase">Today's Orders</span>

          <h6 class="stats-small__value count my-3">
            <?php echo number_format($todays_orders); ?>
          </h6>
        </div>
      </div>
    </div>
  </div>

  <!-- Total Orders -->
  <div class="col mb-4">
    <div class="stats-small stats-small--1 card card-small h-100">
      <div class="card-body p-0 d-flex align-items-center justify-content-center">
        <div class="stats-small__data text-center">
          <span class="stats-small__label text-uppercase">Total Orders</span>

          <h6 class="stats-small__value count my-3">
            <?php echo number_format($orders_count); ?>
          </h6>
        </div>
      </div>
    </div>
  </div>

  <!-- Total Courses -->
  <div class="col mb-4">
    <div class="stats-small stats-small--1 card card-small h-100">
      <div class="card-body p-0 d-flex align-items-center justify-content-center">
        <div class="stats-small__data text-center">
          <span class="stats-small__label text-uppercase">Total Courses</span>

          <h6 class="stats-small__value count my-3">
            <?php echo number_format($courses_count); ?>
          </h6>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- Orders Chart -->
<div class="row">
  <div class="col-12 mb-4">
    <div class="card card-small ct-full-card">
      <div class="card-header border-bottom ct-card-header">
        <h6 class=" m-0">Orders (Last 7 Days)</h6>
      </div>
      <div class="card-body">
        <canvas id="ordersChart" height="250"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Latest Orders & Courses -->
<div class="row">
  <!-- Latest Orders -->
  <div class="col-12 col-md-6 mb-4">
    <div class="card card-small ct-full-card">

      <div class="card-header border-bottom ct-card-header-sm">
        <div class="row no-gutters align-items-center">

          <div class="col-6 col-sm-6 text-sm-left">
            <h6 class="m-0">Latest Orders</h6>
          </div>

          <div class="col-6 col-sm-6 d-flex align-items-center justify-content-sm-end justify-content-center mt-2 mt-sm-0">
            <div class="d-flex gap-2">

              <a href="<?php echo esc_url(admin_url('admin.php?page=coursetransit&route=orders.index')); ?>"
                class="button button-small">
                View All
              </a>

            </div>
          </div>

        </div>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table mb-0">
            <thead class="bg-light">
              <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Total</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($latest_orders)): ?>
                <?php foreach ($latest_orders as $order): ?>
                  <tr>
                    <td>
                      <a href="<?php echo esc_url(admin_url('post.php?post=' . $order->get_id() . '&action=edit')); ?>"
                        target="_blank">
                        #<?php echo esc_html($order->get_id()); ?>
                      </a>
                    </td>
                    <td>
                      <?php echo esc_html($order->get_formatted_billing_full_name() ?: 'Guest'); ?>
                    </td>
                    <td>
                      <?php
                      $status = $order->get_status();

                      $map = [
                        'completed' => 'success',
                        'processing' => 'secondary',
                        'on-hold' => 'secondary',
                        'failed' => 'secondary',
                        'pending' => 'secondary',
                        'cancelled' => 'secondary',
                        'refunded' => 'secondary',
                      ];

                      $coursetransit_class = $map[$status] ?? 'secondary';
                      ?>
                      <span class="badge badge-<?php echo esc_attr($coursetransit_class); ?>">
                        <?php echo esc_html(wc_get_order_status_name($status)); ?>
                      </span>
                    </td>
                    <td>
                      <?php echo wp_kses_post(wc_price($order->get_total())); ?>
                    </td>
                    <td>
                      <?php echo esc_html($order->get_date_created()->date('Y-m-d')); ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" class="text-center p-3">No recent orders found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <!-- Latest Courses -->
  <div class="col-12 col-md-6 mb-4">
    <div class="card card-small ct-full-card">
      <div class="card-header border-bottom ct-card-header-sm">
        <div class="row no-gutters align-items-center">

          <div class="col-6 col-sm-6 text-sm-left">
            <h6 class="m-0">Latest Courses</h6>
          </div>

          <div class="col-6 col-sm-6 d-flex align-items-center justify-content-sm-end justify-content-center mt-2 mt-sm-0">
            <div class="d-flex gap-2">

              <a href="<?php echo esc_url(admin_url('admin.php?page=coursetransit&route=courses.index')); ?>"
                class="button button-small">
                View All
              </a>

            </div>
          </div>

        </div>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table mb-0">
            <thead class="bg-light">
              <tr>
                <th>Course</th>
                <!-- <th>Shortname</th> -->
                <th>Status</th>
                <th>Last Sync</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($latest_courses)): ?>
                <?php foreach ($latest_courses as $coursetransit_course): ?>
                  <tr>
                    <td>
                      <?php if (!empty($coursetransit_course['wc_product_id'])): ?>
                        <a href="<?php echo esc_url(get_permalink($coursetransit_course['wc_product_id'])); ?>"
                          target="_blank">
                          <strong><?php echo esc_html($coursetransit_course['fullname']); ?></strong>
                        </a>
                      <?php else: ?>
                        <strong><?php echo esc_html($coursetransit_course['fullname']); ?></strong>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php
                      if (!empty($coursetransit_course['wc_product_id'])) {

                        $coursetransit_product = wc_get_product($coursetransit_course['wc_product_id']);

                        if ($coursetransit_product) {
                          $status = $coursetransit_product->get_status(); // publish, draft
                  
                          $map = [
                            'publish' => '<span class="badge badge-success">Published</span>',
                            'draft' => '<span class="badge badge-secondary">Draft</span>',
                            'pending' => '<span class="badge badge-warning">Pending</span>',
                            'private' => '<span class="badge badge-dark">Private</span>',
                          ];

                          echo wp_kses_post(
                            $map[$status] ?? '<span class="badge badge-light">' . esc_html(ucfirst($status)) . '</span>'
                          );
                        } else {
                          echo '<span class="badge badge-light">—</span>';
                        }

                      } else {
                        echo '<span class="badge badge-light">—</span>';
                      }
                      ?>
                    </td>
                    <td class="ct-nowrap">
                      <?php echo esc_html(
                        $coursetransit_course['last_synced_at']
                        ? wp_date('Y-m-d', strtotime($coursetransit_course['last_synced_at']))
                        : '—'
                      ); ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" class="text-center p-3">No courses found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>