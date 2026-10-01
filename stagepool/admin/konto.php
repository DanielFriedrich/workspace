<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require SP_ROOT . '/inc/admin.php';
require_login();

$me = current_user();
if (is_post()) {
    csrf_check();
    $row = db_one('SELECT * FROM #__users WHERE id = ?', array($me['id']));
    $current = isset($_POST['current']) ? (string) $_POST['current'] : '';
    $new = isset($_POST['password']) ? (string) $_POST['password'] : '';
    $name = (string) input('name');
    $phone = (string) input('phone');
    if ($name === '') {
        flash('error', 'Bitte einen Namen angeben.');
    } else {
        db_update('users', array('name' => $name, 'phone' => $phone), 'id = ?', array($me['id']));
        if ($new !== '') {
            if (!password_verify($current, $row['password_hash'])) {
                flash('error', 'Das aktuelle Passwort ist falsch – das Passwort wurde nicht geändert.');
                redirect('admin/konto.php');
            }
            if (strlen($new) < 8) {
                flash('error', 'Das neue Passwort muss mindestens 8 Zeichen haben.');
                redirect('admin/konto.php');
            }
            db_update('users', array('password_hash' => password_hash($new, PASSWORD_DEFAULT)), 'id = ?', array($me['id']));
            session_regenerate_id(true);
        }
        flash('success', 'Gespeichert.');
    }
    redirect('admin/konto.php');
}

admin_header('Mein Konto', 'konto');
?>
<form class="card-admin narrow" method="post">
  <?= csrf_field() ?>
  <div class="form-grid">
    <label class="field field-wide"><span>Name</span><input type="text" name="name" value="<?= e($me['name']) ?>" required></label>
    <label class="field field-wide"><span>E-Mail (Login)</span><input type="email" value="<?= e($me['email']) ?>" disabled></label>
    <label class="field field-wide"><span>Telefon</span><input type="text" name="phone" value="<?= e($me['phone']) ?>"></label>
  </div>
  <h3>Passwort ändern</h3>
  <div class="form-grid">
    <label class="field"><span>Aktuelles Passwort</span><input type="password" name="current" autocomplete="current-password"></label>
    <label class="field"><span>Neues Passwort</span><input type="password" name="password" autocomplete="new-password" minlength="8"></label>
  </div>
  <div class="form-actions"><button class="btn btn-primary" type="submit"><?= icon('check') ?>Speichern</button></div>
</form>
<?php admin_footer(); ?>
