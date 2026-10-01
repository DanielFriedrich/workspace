<?php
/**
 * Stagepool – Installations-Assistent
 * Prüft den Server, legt die Datenbank an, schreibt config/config.php
 * und erstellt den ersten Admin-Zugang.
 */
define('SP_INSTALLER', true);
require dirname(__DIR__) . '/inc/bootstrap.php';
require SP_ROOT . '/inc/schema.php';

$lockFile = SP_ROOT . '/storage/installed.lock';
$configFile = SP_ROOT . '/config/config.php';
$locked = is_file($lockFile);

/* ---------- Systemprüfung ---------- */
$checks = array(
    array('PHP-Version ≥ 7.3 (gefunden: ' . PHP_VERSION . ')', version_compare(PHP_VERSION, '7.3.0', '>='), true),
    array('PDO (Datenbankzugriff)', extension_loaded('pdo'), true),
    array('PDO MySQL-Treiber', extension_loaded('pdo_mysql'), false),
    array('PDO SQLite-Treiber', extension_loaded('pdo_sqlite'), false),
    array('mbstring (Umlaute)', extension_loaded('mbstring'), false),
    array('JSON', function_exists('json_encode'), true),
    array('Hash (hash_hmac)', function_exists('hash_hmac') && function_exists('hash_equals'), true),
    array('Sessions', function_exists('session_start'), true),
    array('GD (Bilder verkleinern, optional)', extension_loaded('gd'), false),
    array('Ordner /config beschreibbar', is_writable(SP_ROOT . '/config'), true),
    array('Ordner /storage beschreibbar', is_writable(SP_ROOT . '/storage'), true),
    array('Ordner /uploads/products beschreibbar', is_writable(SP_ROOT . '/uploads/products'), true),
);
$checksOk = true;
foreach ($checks as $c) {
    if ($c[2] && !$c[1]) {
        $checksOk = false;
    }
}
if (!extension_loaded('pdo_mysql') && !extension_loaded('pdo_sqlite')) {
    $checksOk = false;
}

$existing = is_file($configFile) ? (require $configFile) : array();
$f = array(
    'driver' => extension_loaded('pdo_mysql') ? 'mysql' : 'sqlite',
    'host' => 'localhost', 'port' => '3306', 'name' => '', 'user' => '', 'prefix' => 'sp_',
    'site_name' => 'Stagepool', 'admin_name' => '', 'admin_email' => '', 'demo' => '1',
);
if (!empty($existing['db'])) {
    foreach (array('driver', 'host', 'port', 'name', 'user', 'prefix') as $k) {
        if (isset($existing['db'][$k])) {
            $f[$k] = (string) $existing['db'][$k];
        }
    }
}
$errors = array();
$done = false;

if (!$locked && $checksOk && is_post()) {
    csrf_check();
    foreach ($f as $k => $v) {
        if (isset($_POST[$k])) {
            $f[$k] = trim((string) $_POST[$k]);
        }
    }
    $f['demo'] = input('demo') === '1' ? '1' : '';
    $password = isset($_POST['db_pass']) ? (string) $_POST['db_pass'] : '';
    if ($password === '' && !empty($existing['db']['pass']) && input('keep_pass') === '1') {
        $password = $existing['db']['pass'];
    }
    $adminPass = isset($_POST['admin_pass']) ? (string) $_POST['admin_pass'] : '';
    $f['admin_email'] = strtolower($f['admin_email']);

    if (!preg_match('/^[a-z][a-z0-9_]{0,15}$/', $f['prefix'])) {
        $errors[] = 'Das Tabellen-Präfix darf nur Kleinbuchstaben, Ziffern und _ enthalten (z. B. sp_).';
    }
    if ($f['admin_name'] === '' || !filter_var($f['admin_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Bitte Name und gültige E-Mail für den Admin-Zugang angeben.';
    }
    if (strlen($adminPass) < 8) {
        $errors[] = 'Das Admin-Passwort muss mindestens 8 Zeichen haben.';
    }

    if ($f['driver'] === 'sqlite') {
        $dbConf = array(
            'driver' => 'sqlite',
            'path' => !empty($existing['db']['path']) ? $existing['db']['path'] : 'storage/stagepool-' . bin2hex(random_bytes(6)) . '.sqlite',
            'prefix' => $f['prefix'],
        );
    } else {
        $dbConf = array('driver' => 'mysql', 'host' => $f['host'], 'port' => (int) $f['port'], 'name' => $f['name'],
            'user' => $f['user'], 'pass' => $password, 'prefix' => $f['prefix']);
        if ($f['name'] === '' || $f['user'] === '') {
            $errors[] = 'Bitte Datenbankname und Benutzer angeben.';
        }
    }

    if (!$errors) {
        try {
            $GLOBALS['sp_config'] = array(
                'db' => $dbConf,
                'secret' => !empty($existing['secret']) ? $existing['secret'] : bin2hex(random_bytes(32)),
                'timezone' => 'Europe/Berlin',
                'debug' => false,
                'base_path' => '',
                'site_url' => '',
            );
            unset($GLOBALS['sp_pdo']);
            db();
            schema_install($dbConf['driver']);
            settings_all(true);

            // Grundeinstellungen nur setzen, wenn noch nicht vorhanden
            if (!db_value("SELECT COUNT(*) FROM #__settings WHERE name = 'site_name'")) {
                setting_set('site_name', $f['site_name'] !== '' ? $f['site_name'] : 'Stagepool');
                setting_set('contact_email', $f['admin_email']);
                setting_set('notify_email', $f['admin_email']);
            }
            install_default_categories();

            // Admin anlegen oder (Passwort-Reset) aktualisieren
            $user = db_one('SELECT id FROM #__users WHERE email = ?', array($f['admin_email']));
            if ($user) {
                db_update('users', array('name' => $f['admin_name'], 'role' => 'admin', 'active' => 1, 'password_hash' => password_hash($adminPass, PASSWORD_DEFAULT)), 'id = ?', array($user['id']));
                $adminId = (int) $user['id'];
            } else {
                $adminId = db_insert('users', array('name' => $f['admin_name'], 'email' => $f['admin_email'], 'phone' => '', 'role' => 'admin', 'active' => 1, 'password_hash' => password_hash($adminPass, PASSWORD_DEFAULT), 'created_at' => now()));
            }
            if ($f['demo'] && !(int) db_value('SELECT COUNT(*) FROM #__products')) {
                install_demo_data($adminId);
            }

            $php = "<?php\n// Stagepool – Konfiguration (erstellt am " . date('d.m.Y H:i') . ")\n// Diese Datei enthält Zugangsdaten: nicht öffentlich teilen!\nreturn " . var_export($GLOBALS['sp_config'], true) . ";\n";
            if (@file_put_contents($configFile, $php, LOCK_EX) === false) {
                throw new RuntimeException('config/config.php konnte nicht geschrieben werden. Bitte Schreibrechte prüfen.');
            }
            @chmod($configFile, 0640);
            @file_put_contents($lockFile, 'Installiert am ' . date('c') . "\n");
            $done = true;
        } catch (Exception $ex) {
            $msg = $ex->getMessage();
            if ($ex instanceof PDOException) {
                $msg = 'Datenbankverbindung fehlgeschlagen: ' . $msg;
            }
            $errors[] = $msg;
        }
    }
}
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Installation · Stagepool</title>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="login-page install-page">
<?= icon_sprite() ?>
<div class="stage" aria-hidden="true"><span class="beam b1"></span><span class="beam b3"></span><span class="beam b5"></span><span class="haze"></span></div>
<main class="login-box install-box">
  <span class="brand"><span class="brand-mark"><?= icon('spot') ?></span><span class="brand-name">Stagepool</span></span>

  <?php if ($done): ?>
    <h1>Fertig installiert ✨</h1>
    <p>Die Datenbank ist eingerichtet und dein Admin-Zugang angelegt.</p>
    <ol class="install-next">
      <li>Melde dich im <a href="<?= e(url('admin/login.php')) ?>">Backend</a> an.</li>
      <li>Unter <b>Einstellungen</b>: Kontaktdaten, Impressum, Datenschutz und E-Mail-Versand eintragen.</li>
      <li>Standorte und Geräte anlegen (oder die Demo-Daten anpassen).</li>
      <li>Zur Sicherheit kannst du den Ordner <code>/install</code> jetzt löschen. (Er ist durch <code>storage/installed.lock</code> ohnehin gesperrt.)</li>
    </ol>
    <a class="btn btn-primary btn-block" href="<?= e(url('admin/login.php')) ?>"><?= icon('lock') ?>Zum Login</a>
    <a class="btn btn-ghost btn-block" href="<?= e(url('')) ?>">Website ansehen</a>

  <?php elseif ($locked): ?>
    <h1>Bereits installiert</h1>
    <p>Stagepool ist schon eingerichtet. Aus Sicherheitsgründen ist der Assistent gesperrt.</p>
    <p class="muted small">Passwort vergessen oder Neuinstallation nötig? Lösche per FTP die Datei <code>storage/installed.lock</code> und rufe diese Seite erneut auf. Bestehende Daten bleiben erhalten; mit derselben Admin-E-Mail wird nur das Passwort neu gesetzt.</p>
    <a class="btn btn-primary btn-block" href="<?= e(url('admin/login.php')) ?>">Zum Login</a>

  <?php else: ?>
    <h1>Installation</h1>
    <p class="muted">In zwei Minuten startklar. Du brauchst nur die Datenbank-Zugangsdaten von deinem Hoster.</p>

    <h2 class="install-h">1. Server-Check</h2>
    <ul class="checks">
      <?php foreach ($checks as $c): ?>
        <li class="<?= $c[1] ? 'ok' : ($c[2] ? 'fail' : 'warn') ?>"><?= icon($c[1] ? 'check' : ($c[2] ? 'x' : 'info')) ?><?= e($c[0]) ?></li>
      <?php endforeach; ?>
    </ul>
    <?php if (!$checksOk): ?>
      <div class="flash flash-error"><?= icon('alert') ?><span>Bitte zuerst die rot markierten Punkte beheben (PHP-Version im Hosting-Panel wählen, Ordnerrechte per FTP auf 755/775 setzen). Mindestens ein PDO-Treiber (MySQL oder SQLite) wird benötigt.</span></div>
    <?php else: ?>
      <?php foreach ($errors as $err): ?><div class="flash flash-error"><?= icon('alert') ?><span><?= e($err) ?></span></div><?php endforeach; ?>
      <form method="post" class="install-form" autocomplete="off">
        <?= csrf_field() ?>
        <h2 class="install-h">2. Datenbank</h2>
        <fieldset class="segmented" data-scope>
          <label><input type="radio" name="driver" value="mysql"<?= $f['driver'] === 'mysql' ? ' checked' : '' ?><?= extension_loaded('pdo_mysql') ? '' : ' disabled' ?>><span>MySQL / MariaDB</span></label>
          <label><input type="radio" name="driver" value="sqlite"<?= $f['driver'] === 'sqlite' ? ' checked' : '' ?><?= extension_loaded('pdo_sqlite') ? '' : ' disabled' ?>><span>SQLite (ohne DB-Server)</span></label>
        </fieldset>
        <div data-scope-field="mysql">
          <div class="form-grid">
            <label class="field"><span>Server</span><input type="text" name="host" value="<?= e($f['host']) ?>"></label>
            <label class="field"><span>Port</span><input type="number" name="port" value="<?= e($f['port']) ?>"></label>
            <label class="field"><span>Datenbankname</span><input type="text" name="name" value="<?= e($f['name']) ?>"></label>
            <label class="field"><span>Benutzer</span><input type="text" name="user" value="<?= e($f['user']) ?>"></label>
            <label class="field field-wide"><span>Passwort</span><input type="password" name="db_pass" autocomplete="new-password"></label>
            <?php if (!empty($existing['db']['pass'])): ?><label class="check field-wide"><input type="checkbox" name="keep_pass" value="1" checked><span>Gespeichertes DB-Passwort verwenden, wenn leer</span></label><?php endif; ?>
          </div>
        </div>
        <p class="muted small" data-scope-field="sqlite">SQLite speichert alles in einer Datei im geschützten Ordner <code>/storage</code>. Ideal für kleine Installationen ohne MySQL.</p>
        <label class="field"><span>Tabellen-Präfix</span><input type="text" name="prefix" value="<?= e($f['prefix']) ?>"></label>

        <h2 class="install-h">3. Plattform &amp; Admin</h2>
        <label class="field"><span>Name der Plattform</span><input type="text" name="site_name" value="<?= e($f['site_name']) ?>"></label>
        <div class="form-grid">
          <label class="field"><span>Dein Name</span><input type="text" name="admin_name" value="<?= e($f['admin_name']) ?>" required></label>
          <label class="field"><span>E-Mail (Login)</span><input type="email" name="admin_email" value="<?= e($f['admin_email']) ?>" required></label>
          <label class="field field-wide"><span>Passwort (min. 8 Zeichen)</span><input type="password" name="admin_pass" minlength="8" required autocomplete="new-password"></label>
        </div>
        <label class="check"><input type="checkbox" name="demo" value="1"<?= $f['demo'] ? ' checked' : '' ?>><span>Demo-Geräte, Standorte und Beispielbuchungen anlegen (empfohlen zum Ausprobieren)</span></label>
        <button class="btn btn-primary btn-block" type="submit"><?= icon('sparkle') ?>Jetzt installieren</button>
      </form>
    <?php endif; ?>
  <?php endif; ?>
</main>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
