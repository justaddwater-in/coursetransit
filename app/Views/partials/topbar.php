<?php
if (!defined('ABSPATH')) {
  exit;
}

$coursetransit_plugin_data = get_file_data(
  COURSETRANSIT_FILE, // main plugin file path constant
  ['Version' => 'Version']
);
$coursetransit_version = $coursetransit_plugin_data['Version'] ?? '1.0.0';
?>
<div class="main-navbar sticky-top bg-white">

  <nav class="navbar align-items-stretch navbar-light flex-md-nowrap p-0">

    <form action="#" class="main-navbar__search w-100 d-none d-md-flex d-lg-flex">
      <div class="input-group input-group-seamless me-3">
       
      </div>
    </form>

    <ul class="navbar-nav border-left flex-row">

      <li class="nav-item border-right d-flex align-items-center justify-content-center"
        style="height:56px; padding:0 12px;">

        <div class="d-flex align-items-center justify-content-center" style="height:100%;">
          <span style="font-size:13px; color:#6b7280; font-weight:500; line-height:1;">
              v<?php echo esc_html(COURSETRANSIT_VERSION); ?>
          </span>
        </div>

      </li>
    </ul>

    <nav class="nav">
      <a href="#" class="nav-link nav-link-icon toggle-sidebar d-md-inline d-lg-none text-center border-left">
        <i class="material-icons">&#xE5D2;</i>
      </a>
    </nav>

  </nav>

</div>