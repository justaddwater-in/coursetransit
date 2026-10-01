<?php
/**
 * CourseTransit Layout (Shards-ready)
 */

use CourseTransit\Helpers\ConnectionNotice;

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="container-fluid">
  <div class="row">

    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main-content col-lg-10 col-md-9 col-sm-12 p-0 offset-lg-2 offset-md-3">

      <?php include __DIR__ . '/partials/topbar.php'; ?>

      <?php ConnectionNotice::render(); ?>
      <?php do_action('coursetransit_after_connection_notice'); ?>

      <div class="main-content-container container-fluid px-4 py-4">

        <?php require COURSETRANSIT_PATH . 'app/Views/partials/setup-modal.php'; ?>
        <?php
        $coursetransit_view_path = apply_filters(
            'coursetransit_view_path',
            COURSETRANSIT_PATH . 'app/Views/' . $view . '.php',
            $view
        );

        if (is_string($coursetransit_view_path) && file_exists($coursetransit_view_path)) {
            require $coursetransit_view_path;
        } else {
            require COURSETRANSIT_PATH . 'app/Views/' . $view . '.php';
        }
        ?>
        
      </div>

    </main>
  </div>
</div>