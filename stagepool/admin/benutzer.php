<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require SP_ROOT . '/inc/admin.php';
require_admin();

$me = current_user();
if (is_post()) {
    csrf_check();
    $action = input('action');
    $id = (int) input('id');
    if ($action === 'save') {
        $data = array(
            'name' => (string) input('name'),
            'email' => strtolower((string) input('email')),
            'phone' => (string) input('phone'),
            'role' => input('role') === 'admin' ? 'admin' : 'member',
            'active' => input('active') === '1' ? 1 : 0,
        );
        $pw = isset($_POST['password']) ? (string) $_POST['password'] : '';
        $error = '';
        if ($data['name'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Bitte Name und gültige E-Mail angeben.';
        } elseif (db_value('SELECT COUNT(*) FROM #__users WHERE email = ? AND id <> ?', array($data['email'], $id))) {
            $error = 'Diese E-Mail-Adresse ist bereits vergeben.';
        } elseif (!$id && strlen($pw) < 8) {
            $error = 'Das Passwort muss mindestens 8 Zeichen haben.';
        } elseif ($id && $pw !== '' && strlen($pw) < 8) {
            $error = 'Das neue Passwort muss mindestens 8 Zeichen haben.';
        } elseif ($id === (int) $me['id'] && ($data['role'] !== 'admin' || !$data['active'])) {
            $error = 'Du kannst dir nicht selbst die Admin-Rechte entziehen oder dich deaktivieren.';
        }
        if ($error) {
            flash('error', $error);
            redirect('admin/benutzer.php');
        }
        if ($pw !== '') {
            $data['password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
        }
        if ($id) {
            db_update('users', $data, 'id = ?', array($id));
        } else {
            $data['created_at'] = now();
            db_insert('users', $data);
        }
        flash('success', 'Gespeichert.');
    }
    if ($action === 'delete' && $id && $id !== (int) $me['id']) {
        db_exec('UPDATE #__products SET owner_id = NULL WHERE owner_id = ?', array($id));
        db_exec('UPDATE #__blocks SET created_by = NULL WHERE created_by = ?', array($id));
        db_exec('UPDATE #__booking_log SET user_id = NULL WHERE user_id = ?', array($id));
        db_exec('DELETE FROM #__users WHERE id = ?', array($id));
        flash('success', 'Zugang gelöscht.');
    }
    redirect('admin/benutzer.php');
}

$counts = array();
foreach (db_all('SELECT owner_id, COUNT(*) AS n FROM #__products GROUP BY owner_id') as $r) {
    $counts[(int) $r['owner_id']] = (int) $r['n'];
}
$users = db_all('SELECT * FROM #__users ORDER BY name');
$users[] = array('id' => 0, 'name' => '', 'email' => '', 'phone' => '', 'role' => 'member', 'active' => 1, 'last_login' => null);

admin_header('Team', 'benutzer');
?>
<p class="muted">Alle Personen, die Geräte in den Pool einbringen, können einen eigenen Zugang bekommen. <b>Team</b> darf Geräte, Buchungen und Sperrzeiten verwalten, <b>Admin</b> zusätzlich Zugänge und Einstellungen.</p>
<div class="loc-cards">
  <?php foreach ($users as $u): $uid = (int) $u['id']; ?>
    <form class="card-admin loc-card<?= $uid ? '' : ' is-new' ?>" method="post" autocomplete="off">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $uid ?>">
      <h2><?= icon($uid ? 'user' : 'plus') ?><?= $uid ? e($u['name']) : 'Neuer Zugang' ?>
        <?php if ($uid): ?><small class="muted"><?= plural(isset($counts[$uid]) ? $counts[$uid] : 0, 'Gerät', 'Geräte') ?></small><?php endif; ?></h2>
      <div class="form-grid">
        <label class="field"><span>Name</span><input type="text" name="name" value="<?= e($u['name']) ?>" <?= $uid ? 'required' : '' ?>></label>
        <label class="field"><span>E-Mail (Login)</span><input type="email" name="email" value="<?= e($u['email']) ?>" <?= $uid ? 'required' : '' ?> autocomplete="off"></label>
        <label class="field"><span>Telefon</span><input type="text" name="phone" value="<?= e($u['phone']) ?>"></label>
        <label class="field"><span>Rolle</span><select name="role"><?= opt('member', $u['role'], 'Team') ?><?= opt('admin', $u['role'], 'Admin') ?></select></label>
        <label class="field field-wide"><span><?= $uid ? 'Neues Passwort (leer = unverändert)' : 'Passwort (min. 8 Zeichen)' ?></span><input type="password" name="password" autocomplete="new-password" minlength="8"></label>
        <label class="check"><input type="checkbox" name="active" value="1"<?= (int) $u['active'] ? ' checked' : '' ?>><span>Zugang aktiv</span></label>
      </div>
      <?php if ($uid && $u['last_login']): ?><p class="muted small">Letzter Login: <?= e(datetime_de($u['last_login'])) ?></p><?php endif; ?>
      <div class="form-actions">
        <?php if ($uid && $uid !== (int) $me['id']): ?><button class="btn btn-ghost btn-sm" type="submit" name="action" value="delete" data-confirm="Zugang von <?= e($u['name']) ?> löschen? Die Geräte bleiben erhalten."><?= icon('trash') ?>Löschen</button><?php endif; ?>
        <button class="btn btn-primary btn-sm" type="submit" name="action" value="save"><?= $uid ? 'Speichern' : 'Anlegen' ?></button>
      </div>
    </form>
  <?php endforeach; ?>
</div>
<?php admin_footer(); ?>
