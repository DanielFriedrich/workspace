<?php
if (!defined('SP_APP')) { exit; }

/** Layout und Helfer für das Backend. */

function admin_nav()
{
    $nav = array(
        array('index.php', 'Übersicht', 'sparkle', 'dashboard'),
        array('buchungen.php', 'Anfragen & Buchungen', 'list', 'buchungen'),
        array('belegung.php', 'Belegungsplan', 'timeline', 'belegung'),
        array('belege.php', 'Angebote & Rechnungen', 'tag', 'belege'),
        array('buchhaltung.php', 'Buchhaltung', 'grid', 'buchhaltung'),
        array('sperrzeiten.php', 'Sperrzeiten', 'ban', 'sperrzeiten'),
        array('produkte.php', 'Geräte', 'box', 'produkte'),
        array('kategorien.php', 'Kategorien', 'sliders', 'kategorien'),
        array('standorte.php', 'Standorte', 'pin', 'standorte'),
    );
    if (is_admin()) {
        $nav[] = array('benutzer.php', 'Team', 'users', 'benutzer');
        $nav[] = array('einstellungen.php', 'Einstellungen', 'settings', 'einstellungen');
    }
    $nav[] = array('konto.php', 'Mein Konto', 'user', 'konto');
    return $nav;
}

function admin_header($title, $active = '')
{
    $user = current_user();
    $open = (int) db_value("SELECT COUNT(*) FROM #__bookings WHERE status IN ('requested', 'offered')");
    ?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · Backend · <?= e(setting('site_name')) ?></title>
<link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin">
<?= icon_sprite() ?>
<div class="admin-shell">
  <aside class="admin-side">
    <a class="brand" href="<?= e(url('admin/index.php')) ?>"><span class="brand-mark"><?= icon('spot') ?></span><span class="brand-name"><?= e(setting('site_name')) ?><small>Backend</small></span></a>
    <nav class="admin-nav" aria-label="Backend">
      <?php foreach (admin_nav() as $item): ?>
        <a href="<?= e(url('admin/' . $item[0])) ?>"<?= $active === $item[3] ? ' aria-current="page"' : '' ?>>
          <?= icon($item[2]) ?><span><?= e($item[1]) ?></span>
          <?php if ($item[3] === 'buchungen' && $open): ?><em class="nav-badge" title="Offene Anfragen"><?= $open ?></em><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="admin-side-foot">
      <a href="<?= e(url('')) ?>" target="_blank"><?= icon('external') ?><span>Zur Website</span></a>
      <a href="<?= e(url('admin/logout.php', array('_token' => csrf_token()))) ?>"><?= icon('logout') ?><span>Abmelden</span></a>
    </div>
  </aside>
  <div class="admin-main">
    <header class="admin-top">
      <h1><?= e($title) ?></h1>
      <div class="admin-user"><?= icon('user') ?><span><?= e($user['name']) ?></span><?= $user['role'] === 'admin' ? '<em>Admin</em>' : '' ?></div>
    </header>
    <div class="admin-content">
    <?php foreach (flashes() as $m): ?>
      <div class="flash flash-<?= e($m['type']) ?>" role="status"><?= icon($m['type'] === 'error' ? 'alert' : 'check') ?><span><?= e($m['msg']) ?></span></div>
    <?php endforeach;
}

function admin_footer()
{
    ?>
    </div>
  </div>
</div>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
<?php
}

function opt($value, $current, $label)
{
    return '<option value="' . e($value) . '"' . ((string) $value === (string) $current ? ' selected' : '') . '>' . e($label) . '</option>';
}

/** Bild-Upload für Geräte. Gibt Dateiname, '' (kein Upload) oder false (Fehler) zurück. */
function handle_image_upload($field, $productId)
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        flash('error', 'Upload fehlgeschlagen (Fehlercode ' . (int) $f['error'] . '). Ist die Datei evtl. zu groß?');
        return false;
    }
    if ($f['size'] > 10 * 1024 * 1024) {
        flash('error', 'Das Bild ist zu groß (max. 10 MB).');
        return false;
    }
    $info = @getimagesize($f['tmp_name']);
    $types = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif');
    if (defined('IMAGETYPE_WEBP')) {
        $types[IMAGETYPE_WEBP] = 'webp';
    }
    if (!$info || !isset($types[$info[2]])) {
        flash('error', 'Bitte lade ein JPG-, PNG-, WebP- oder GIF-Bild hoch.');
        return false;
    }
    $ext = $types[$info[2]];
    $dir = SP_ROOT . '/uploads/products';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $name = (int) $productId . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    $target = $dir . '/' . $name;
    if (!move_uploaded_file($f['tmp_name'], $target)) {
        flash('error', 'Das Bild konnte nicht gespeichert werden. Sind die Schreibrechte für /uploads gesetzt?');
        return false;
    }
    @chmod($target, 0644);
    image_shrink($target, $info, 1600);
    return $name;
}

/** Verkleinert große Bilder, wenn die GD-Erweiterung vorhanden ist. */
function image_shrink($path, $info, $max)
{
    list($w, $h, $type) = $info;
    if (!function_exists('imagecreatetruecolor') || ($w <= $max && $h <= $max)) {
        return;
    }
    $loaders = array(IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng');
    if (defined('IMAGETYPE_WEBP') && function_exists('imagecreatefromwebp')) {
        $loaders[IMAGETYPE_WEBP] = 'imagecreatefromwebp';
    }
    if (!isset($loaders[$type]) || !function_exists($loaders[$type])) {
        return;
    }
    $src = @call_user_func($loaders[$type], $path);
    if (!$src) {
        return;
    }
    $ratio = min($max / $w, $max / $h);
    $nw = (int) round($w * $ratio);
    $nh = (int) round($h * $ratio);
    $dst = imagecreatetruecolor($nw, $nh);
    if ($type !== IMAGETYPE_JPEG) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    if ($type === IMAGETYPE_JPEG) {
        imagejpeg($dst, $path, 85);
    } elseif ($type === IMAGETYPE_PNG) {
        imagepng($dst, $path, 6);
    } elseif (function_exists('imagewebp')) {
        imagewebp($dst, $path, 85);
    }
    imagedestroy($src);
    imagedestroy($dst);
}

function delete_upload($file)
{
    if ($file && preg_match('/^[\w.-]+$/', $file) && is_file(SP_ROOT . '/uploads/products/' . $file)) {
        @unlink(SP_ROOT . '/uploads/products/' . $file);
    }
}
