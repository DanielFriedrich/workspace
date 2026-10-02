<?php
/**
 * Stagepool – Server-Diagnose
 * Läuft unabhängig vom Rest der Anwendung und zeigt, was auf dem Server fehlt
 * (PHP-Erweiterungen, Schreibrechte, Sessions, Datenbank, Schutz der Ordner).
 * Nach der Installation nur für angemeldete Admins erreichbar.
 */
error_reporting(E_ALL);
ini_set('display_errors', '0');

$root = __DIR__;
$installed = @is_file($root . '/storage/installed.lock');

if ($installed) {
    // Nach der Installation: nur mit Admin-Login
    $allowed = false;
    try {
        require $root . '/inc/bootstrap.php';
        $allowed = is_admin();
    } catch (Throwable $ex) {
        $allowed = false;
    }
    if (!$allowed) {
        http_response_code(403);
        echo '<!doctype html><meta charset="utf-8"><title>Diagnose</title><body style="font-family:system-ui;background:#07070c;color:#eee;padding:2rem">'
            . '<h1>Diagnose gesperrt</h1><p>Stagepool ist installiert. Die Diagnose ist jetzt nur für angemeldete Admins verfügbar: zuerst im '
            . '<a style="color:#22d3ee" href="admin/login.php">Backend anmelden</a>, dann diese Seite neu laden.</p>'
            . '<p>Kommst du nicht mehr ins Backend, lösche per Dateimanager die Datei <code>storage/installed.lock</code> – dann ist die Diagnose wieder offen.</p></body>';
        exit;
    }
}

function d_e($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function d_writable($dir)
{
    if (!@is_dir($dir)) {
        return array(false, 'Ordner fehlt');
    }
    $probe = $dir . '/.write-test-' . mt_rand(1000, 9999);
    $ok = @file_put_contents($probe, 'x') !== false;
    if ($ok) {
        @unlink($probe);
        return array(true, 'beschreibbar');
    }
    return array(false, 'nicht beschreibbar');
}

$rows = array();
$add = function ($group, $label, $ok, $info = '', $required = true) use (&$rows) {
    $rows[$group][] = array('label' => $label, 'ok' => $ok, 'info' => $info, 'required' => $required);
};

// PHP
$add('PHP', 'PHP-Version ≥ 7.3', version_compare(PHP_VERSION, '7.3.0', '>='), PHP_VERSION . ' (' . PHP_SAPI . ')');
$add('PHP', 'Webserver', true, isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'unbekannt', false);
$ob = ini_get('open_basedir');
$add('PHP', 'open_basedir', true, $ob ? $ob : 'nicht gesetzt', false);
$add('PHP', 'Upload-Größe', true, 'upload_max_filesize ' . ini_get('upload_max_filesize') . ', post_max_size ' . ini_get('post_max_size'), false);

// Erweiterungen
$ext = array(
    'pdo' => array('PDO (Datenbank)', true),
    'pdo_mysql' => array('pdo_mysql (MySQL/MariaDB)', false),
    'pdo_sqlite' => array('pdo_sqlite (SQLite)', false),
    'mbstring' => array('mbstring (Umlaute)', false),
    'session' => array('session', true),
    'json' => array('json', true),
    'hash' => array('hash', true),
    'iconv' => array('iconv (PDF-Umlaute)', false),
    'gd' => array('gd (Bilder verkleinern)', false),
    'openssl' => array('openssl (SMTP mit TLS)', false),
);
foreach ($ext as $name => $def) {
    $add('PHP-Erweiterungen', $def[0], extension_loaded($name), extension_loaded($name) ? 'aktiv' : 'fehlt – im PHP-Profil aktivieren', $def[1]);
}
$add('PHP-Erweiterungen', 'Mindestens ein Datenbanktreiber', extension_loaded('pdo_mysql') || extension_loaded('pdo_sqlite'), 'pdo_mysql oder pdo_sqlite');

// Dateien & Rechte
$add('Dateien', 'Programmdateien vollständig', @is_file($root . '/inc/bootstrap.php') && @is_file($root . '/inc/lib/fpdf/fpdf.php') && @is_file($root . '/assets/css/app.css'),
    'inc/, assets/, admin/, install/ hochgeladen?');
foreach (array('config', 'storage', 'storage/logs', 'storage/receipts', 'uploads/products') as $dir) {
    $w = d_writable($root . '/' . $dir);
    $add('Dateien', '/' . $dir, $w[0], $w[1] . ($w[0] ? '' : ' – dem Webserver-Benutzer (Synology: „http“) Schreibrechte geben'));
}
if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
    $u = @posix_getpwuid(posix_geteuid());
    $add('Dateien', 'PHP läuft als Benutzer', true, $u ? $u['name'] : '?', false);
}
$add('Dateien', 'Konfiguration vorhanden', @is_file($root . '/config/config.php'), @is_file($root . '/config/config.php') ? 'config/config.php' : 'noch nicht – Installation unter install/index.php starten', false);
$add('Dateien', 'Installation abgeschlossen', $installed, $installed ? 'storage/installed.lock' : 'nein', false);

// Session
$savePath = session_save_path();
$sessOk = false;
$sessInfo = '';
if (session_status() !== PHP_SESSION_ACTIVE) {
    $sessOk = @session_start();
} else {
    $sessOk = true;
}
if ($sessOk) {
    $_SESSION['sp_diag'] = isset($_SESSION['sp_diag']) ? $_SESSION['sp_diag'] + 1 : 1;
    $sessInfo = 'Aufruf Nr. ' . $_SESSION['sp_diag'] . ' – Seite neu laden: die Zahl muss steigen';
}
$pathPart = $savePath ? preg_replace('/^\d+;/', '', $savePath) : sys_get_temp_dir();
$add('Sessions', 'Session startet', $sessOk, $sessInfo ?: 'session_start() fehlgeschlagen');
$add('Sessions', 'Session-Ordner beschreibbar', @is_writable($pathPart), $pathPart . (@is_writable($pathPart) ? '' : ' – ohne funktionierende Sessions klappen Login und Formulare nicht'));

// Datenbanktest
$dbResult = null;
$dbForm = array('host' => '127.0.0.1', 'port' => '3307', 'socket' => '', 'name' => '', 'user' => '');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dbtest'])) {
    foreach ($dbForm as $k => $v) {
        $dbForm[$k] = isset($_POST[$k]) ? trim((string) $_POST[$k]) : $v;
    }
    try {
        $dsn = $dbForm['socket'] !== ''
            ? 'mysql:unix_socket=' . $dbForm['socket'] . ';dbname=' . $dbForm['name'] . ';charset=utf8mb4'
            : 'mysql:host=' . $dbForm['host'] . ';port=' . (int) $dbForm['port'] . ';dbname=' . $dbForm['name'] . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $dbForm['user'], isset($_POST['pass']) ? (string) $_POST['pass'] : '', array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5));
        $dbResult = array(true, 'Verbindung erfolgreich – Server-Version ' . $pdo->query('SELECT VERSION()')->fetchColumn() . '. Genau diese Werte im Installer eintragen.');
    } catch (Throwable $ex) {
        $dbResult = array(false, $ex->getMessage());
    }
}
$sockets = array();
foreach (array('/run/mysqld/mysqld10.sock', '/run/mysqld/mysqld.sock', '/var/run/mysqld/mysqld.sock', '/tmp/mysql.sock') as $s) {
    if (@file_exists($s)) {
        $sockets[] = $s;
    }
}

// Datei für den Schutztest (wird im Browser abgerufen; erreichbar = Schutz fehlt)
@file_put_contents($root . '/storage/probe.txt', 'stagepool-probe');
$base = rtrim(str_replace('\\', '/', dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/')), '/') . '/';

$problems = 0;
foreach ($rows as $g) {
    foreach ($g as $r) {
        if ($r['required'] && !$r['ok']) {
            $problems++;
        }
    }
}
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Diagnose · Stagepool</title>
<style>
  :root { color-scheme: dark; }
  body { margin: 0; background: #07070c; color: #f5f3ff; font: 15px/1.55 system-ui, -apple-system, "Segoe UI", sans-serif; }
  main { max-width: 860px; margin: 0 auto; padding: 32px 16px 64px; }
  h1 { font-size: 1.8rem; margin: 0 0 6px; }
  h2 { font-size: 1.05rem; margin: 28px 0 10px; color: #22d3ee; text-transform: uppercase; letter-spacing: .08em; }
  .lead { color: #a7a4bd; }
  .summary { padding: 14px 18px; border-radius: 12px; font-weight: 700; margin: 18px 0; }
  .good { background: rgba(61,220,151,.12); border: 1px solid rgba(61,220,151,.4); color: #9ff0c9; }
  .bad { background: rgba(255,77,109,.12); border: 1px solid rgba(255,77,109,.45); color: #ffb0c0; }
  table { width: 100%; border-collapse: collapse; background: #12111c; border-radius: 12px; overflow: hidden; }
  td { padding: 9px 12px; border-bottom: 1px solid rgba(255,255,255,.07); vertical-align: top; }
  td:first-child { width: 34px; text-align: center; font-weight: 800; }
  .ok { color: #3ddc97; } .no { color: #ff6b88; } .opt { color: #ffb547; }
  .info { color: #a7a4bd; font-size: .9rem; word-break: break-word; }
  form { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; background: #12111c; padding: 16px; border-radius: 12px; }
  label { display: grid; gap: 4px; font-size: .8rem; color: #a7a4bd; }
  input { padding: 9px 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,.18); background: #0c0b14; color: #fff; font: inherit; }
  button { grid-column: 1 / -1; padding: 11px; border: 0; border-radius: 999px; background: linear-gradient(100deg,#ff2e93,#a855f7,#22d3ee); color: #fff; font-weight: 800; cursor: pointer; }
  code { background: #1b1a29; padding: 1px 6px; border-radius: 6px; }
  a { color: #22d3ee; }
</style>
</head>
<body>
<main>
  <h1>Server-Diagnose</h1>
  <p class="lead">Prüft, ob dein Server alles hat, was Stagepool braucht. Rot = muss behoben werden, gelb = optional.</p>
  <div class="summary <?= $problems ? 'bad' : 'good' ?>">
    <?= $problems ? $problems . ' Problem(e) gefunden – siehe rote Zeilen.' : 'Alle Pflichtpunkte in Ordnung.' ?>
    <?php if (!$installed && !$problems): ?> Weiter zur <a href="install/index.php">Installation</a>.<?php endif; ?>
  </div>

  <?php foreach ($rows as $group => $list): ?>
    <h2><?= d_e($group) ?></h2>
    <table>
      <?php foreach ($list as $r): $cls = $r['ok'] ? 'ok' : ($r['required'] ? 'no' : 'opt'); ?>
        <tr><td class="<?= $cls ?>"><?= $r['ok'] ? '✓' : ($r['required'] ? '✗' : '!') ?></td><td><b><?= d_e($r['label']) ?></b><br><span class="info"><?= d_e($r['info']) ?></span></td></tr>
      <?php endforeach; ?>
    </table>
  <?php endforeach; ?>

  <h2>Schutz der internen Ordner</h2>
  <table><tr><td id="probe-icon">…</td><td><b>storage/ ist von außen gesperrt</b><br><span class="info" id="probe-info">wird geprüft …</span></td></tr></table>

  <h2>Datenbank testen (MySQL/MariaDB)</h2>
  <p class="info">Gefundene Sockets: <?= $sockets ? d_e(implode(', ', $sockets)) : 'keine sichtbar' ?>. Synology MariaDB 10: Server <code>127.0.0.1</code>, Port <code>3307</code> (oder Socket <code>/run/mysqld/mysqld10.sock</code>).</p>
  <?php if ($dbResult): ?><div class="summary <?= $dbResult[0] ? 'good' : 'bad' ?>"><?= d_e($dbResult[1]) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="dbtest" value="1">
    <label>Server<input name="host" value="<?= d_e($dbForm['host']) ?>"></label>
    <label>Port<input name="port" value="<?= d_e($dbForm['port']) ?>"></label>
    <label>Socket (optional)<input name="socket" value="<?= d_e($dbForm['socket']) ?>" placeholder="/run/mysqld/mysqld10.sock"></label>
    <label>Datenbank<input name="name" value="<?= d_e($dbForm['name']) ?>"></label>
    <label>Benutzer<input name="user" value="<?= d_e($dbForm['user']) ?>"></label>
    <label>Passwort<input name="pass" type="password"></label>
    <button type="submit">Verbindung testen</button>
  </form>

  <p class="info" style="margin-top:28px">Diese Seite darf nach der Installation bleiben – sie ist dann nur für eingeloggte Admins erreichbar. Anleitung für Synology: <code>docs/SYNOLOGY.md</code>.</p>
</main>
<script>
(function () {
  var icon = document.getElementById('probe-icon'), info = document.getElementById('probe-info');
  fetch(<?= json_encode($base . 'storage/probe.txt') ?> + '?t=' + Date.now(), { cache: 'no-store' }).then(function (r) {
    return r.ok ? r.text() : '';
  }).then(function (t) {
    if (t.indexOf('stagepool-probe') !== -1) {
      icon.textContent = '✗'; icon.className = 'no';
      info.textContent = 'Der Ordner storage/ ist von außen abrufbar! Bei nginx die Sperr-Regeln einrichten (docs/nginx.conf.example bzw. docs/SYNOLOGY.md).';
      var sum = document.querySelector('.summary');
      if (sum && sum.className.indexOf('good') !== -1) { sum.className = 'summary bad'; sum.textContent = 'Läuft – aber die internen Ordner sind nicht geschützt (siehe „Schutz der internen Ordner“).'; }
    } else {
      icon.textContent = '✓'; icon.className = 'ok';
      info.textContent = 'gesperrt – gut so.';
    }
  }).catch(function () { icon.textContent = '✓'; icon.className = 'ok'; info.textContent = 'gesperrt – gut so.'; });
})();
</script>
</body>
</html>
