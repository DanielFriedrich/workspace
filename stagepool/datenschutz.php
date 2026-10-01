<?php
require __DIR__ . '/inc/bootstrap.php';
view('header', array('pageTitle' => 'Datenschutz'));
?>
<section class="wrap legal prose">
  <?= md_lite(setting('privacy')) ?>
</section>
<?php view('footer'); ?>
