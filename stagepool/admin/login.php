<?php
require dirname(__DIR__) . '/inc/bootstrap.php';

if (current_user()) {
    redirect('admin/index.php');
}
$error = '';
$email = '';
if (is_post()) {
    csrf_check();
    $email = (string) input('email');
    if (login_blocked()) {
        $error = 'Zu viele Fehlversuche. Bitte warte 15 Minuten.';
    } elseif (attempt_login($email, (string) (isset($_POST['password']) ? $_POST['password'] : ''))) {
        $target = isset($_SESSION['sp_after_login']) ? $_SESSION['sp_after_login'] : '';
        unset($_SESSION['sp_after_login']);
        redirect(safe_return($target, 'admin/index.php'));
    } else {
        $error = 'E-Mail oder Passwort stimmen nicht.';
    }
}
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Login · <?= e(setting('site_name')) ?></title>
<link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="login-page">
<?= icon_sprite() ?>
<div class="stage" aria-hidden="true"><span class="beam b1"></span><span class="beam b3"></span><span class="beam b5"></span><span class="haze"></span></div>
<main class="login-box">
  <a class="brand" href="<?= e(url('')) ?>"><span class="brand-mark"><?= icon('spot') ?></span><span class="brand-name"><?= e(setting('site_name')) ?></span></a>
  <h1>Team-Login</h1>
  <p class="muted">Backend für Geräte, Anfragen und Sperrzeiten.</p>
  <?php foreach (flashes() as $m): ?><div class="flash flash-<?= e($m['type']) ?>"><?= e($m['msg']) ?></div><?php endforeach; ?>
  <?php if ($error): ?><div class="flash flash-error"><?= icon('alert') ?><span><?= e($error) ?></span></div><?php endif; ?>
  <form method="post" action="<?= e(url('admin/login.php')) ?>">
    <?= csrf_field() ?>
    <label class="field"><span>E-Mail</span><input type="email" name="email" value="<?= e($email) ?>" autocomplete="username" required autofocus></label>
    <label class="field"><span>Passwort</span><input type="password" name="password" autocomplete="current-password" required></label>
    <button class="btn btn-primary btn-block" type="submit"><?= icon('lock') ?>Anmelden</button>
  </form>
  <a class="link-btn" href="<?= e(url('')) ?>"><?= icon('arrow-l') ?>Zur Website</a>
</main>
</body>
</html>
