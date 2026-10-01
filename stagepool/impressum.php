<?php
require __DIR__ . '/inc/bootstrap.php';
view('header', array('pageTitle' => 'Impressum'));
?>
<section class="wrap legal prose">
  <?= md_lite(setting('impressum')) ?>
</section>
<?php view('footer'); ?>
