<?php
if (!defined('SP_APP')) { exit; }
$pageTitle = isset($pageTitle) ? $pageTitle : '';
$active = isset($active) ? $active : '';
$siteName = setting('site_name', 'Stagepool');
$hdrPeriod = period_get();
$hdrCart = cart_count();
$description = isset($description) ? $description : setting('site_tagline');
$nameParts = preg_split('/(?<=[a-zäöü])(?=[A-ZÄÖÜ])/u', $siteName, 2);
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle !== '' ? $pageTitle . ' · ' . $siteName : $siteName . ' – ' . setting('site_tagline')) ?></title>
<meta name="description" content="<?= e($description) ?>">
<meta name="theme-color" content="#07070c">
<meta property="og:title" content="<?= e($pageTitle !== '' ? $pageTitle : $siteName) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="preload" href="<?= e(url('assets/fonts/unbounded-latin-800-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e(url('assets/fonts/manrope-latin-500-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="page-<?= e($active) ?>">
<?= icon_sprite() ?>
<a class="skip" href="#main">Zum Inhalt springen</a>
<header class="site-header">
  <div class="wrap header-inner">
    <a class="brand" href="<?= e(url('')) ?>" aria-label="<?= e($siteName) ?> – Startseite">
      <span class="brand-mark" aria-hidden="true"><?= icon('spot') ?></span>
      <span class="brand-name"><?= e($nameParts[0]) ?><?php if (isset($nameParts[1])): ?><b><?= e($nameParts[1]) ?></b><?php endif; ?></span>
    </a>
    <nav class="main-nav" id="main-nav" aria-label="Hauptnavigation">
      <a href="<?= e(url('')) ?>"<?= $active === 'katalog' ? ' aria-current="page"' : '' ?>>Entdecken</a>
      <a href="<?= e(url('kalender.php')) ?>"<?= $active === 'kalender' ? ' aria-current="page"' : '' ?>>Belegungskalender</a>
      <a href="<?= e(url('info.php')) ?>"<?= $active === 'info' ? ' aria-current="page"' : '' ?>>So funktioniert’s</a>
    </nav>
    <div class="header-actions">
      <a class="period-chip<?= $hdrPeriod ? ' is-set' : '' ?>" href="<?= e(url('', array())) ?>#finder" title="Zeitraum wählen">
        <?= icon('calendar') ?>
        <span><?= $hdrPeriod ? e(date('d.m.', strtotime($hdrPeriod['from'])) . ' – ' . date('d.m.', strtotime($hdrPeriod['to']))) : 'Zeitraum wählen' ?></span>
      </a>
      <a class="cart-btn" href="<?= e(url('warenkorb.php')) ?>" aria-label="Anfragekorb (<?= (int) $hdrCart ?>)"<?= $active === 'warenkorb' ? ' aria-current="page"' : '' ?>>
        <?= icon('bag') ?><span class="cart-label">Anfrage</span>
        <span class="cart-count<?= $hdrCart ? ' has-items' : '' ?>" data-cart-count><?= (int) $hdrCart ?></span>
      </a>
      <?php if ($hdrUser = current_user()): ?>
        <a class="team-chip" href="<?= e(url('admin/')) ?>" title="Du bist als Team angemeldet – es gelten die internen Teampreise."><?= icon('users') ?><span>Teampreise · <?= e(strtok($hdrUser['name'], ' ')) ?></span></a>
      <?php endif; ?>
      <button class="nav-toggle" type="button" aria-controls="main-nav" aria-expanded="false" aria-label="Menü"><?= icon('menu') ?></button>
    </div>
  </div>
</header>
<main id="main">
<?php $msgs = flashes(); if ($msgs): ?>
  <div class="wrap flash-stack">
  <?php foreach ($msgs as $m): ?>
    <div class="flash flash-<?= e($m['type']) ?>" role="status"><?= icon($m['type'] === 'error' ? 'alert' : 'check') ?><span><?= e($m['msg']) ?></span></div>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
