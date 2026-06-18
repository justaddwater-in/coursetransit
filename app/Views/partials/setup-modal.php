<?php
/**
 * CourseTransit Setup Modal
 */
if (!defined('ABSPATH')) {
    exit;
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