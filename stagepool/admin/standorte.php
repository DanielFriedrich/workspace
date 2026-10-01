<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require SP_ROOT . '/inc/admin.php';
require_login();

if (is_post()) {
    csrf_check();
    $action = input('action');
    $id = (int) input('id');
    if ($action === 'save') {
        $data = array(
            'name' => (string) input('name'), 'street' => (string) input('street'), 'zip' => (string) input('zip'),
            'city' => (string) input('city'), 'contact' => (string) input('contact'), 'notes' => (string) input('notes'),
            'sort' => (int) input('sort'), 'active' => input('active') === '1' ? 1 : 0,
        );
        if ($data['name'] === '') {
            flash('error', 'Bitte einen Namen angeben.');
            redirect('admin/standorte.php');
        }
        if ($id) {
            db_update('locations', $data, 'id = ?', array($id));
        } else {
            db_insert('locations', $data);
        }
        flash('success', 'Standort gespeichert.');
    }
    if ($action === 'delete' && $id) {
        $used = (int) db_value('SELECT COUNT(*) FROM #__products WHERE location_id = ?', array($id));
        if ($used) {
            flash('error', 'An diesem Standort sind noch ' . plural($used, 'Gerät', 'Geräte') . ' eingetragen. Bitte zuerst umziehen oder den Standort deaktivieren.');
        } else {
            db_exec("DELETE FROM #__blocks WHERE scope = 'location' AND ref_id = ?", array($id));
            db_exec('DELETE FROM #__locations WHERE id = ?', array($id));
            flash('success', 'Standort gelöscht.');
        }
    }
    redirect('admin/standorte.php');
}

$counts = array();
foreach (db_all('SELECT location_id, COUNT(*) AS n FROM #__products GROUP BY location_id') as $r) {
    $counts[(int) $r['location_id']] = (int) $r['n'];
}
$rows = locations_all(false);
$rows[] = array('id' => 0, 'name' => '', 'street' => '', 'zip' => '', 'city' => '', 'contact' => '', 'notes' => '', 'sort' => (count($rows) + 1) * 10, 'active' => 1);

admin_header('Standorte', 'standorte');
?>
<p class="muted">Wo stehen die Geräte? Besucher können im Katalog nach Standort filtern. Die Straße wird erst in der Bestätigung genannt, öffentlich sind nur Name, PLZ und Ort sichtbar.</p>
<div class="loc-cards">
  <?php foreach ($rows as $l): $lid = (int) $l['id']; ?>
    <form class="card-admin loc-card<?= $lid ? '' : ' is-new' ?>" method="post">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $lid ?>">
      <h2><?= icon($lid ? 'pin' : 'plus') ?><?= $lid ? e($l['name']) : 'Neuer Standort' ?>
        <?php if ($lid): ?><small class="muted"><?= plural(isset($counts[$lid]) ? $counts[$lid] : 0, 'Gerät', 'Geräte') ?></small><?php endif; ?></h2>
      <div class="form-grid">
        <label class="field field-wide"><span>Name *</span><input type="text" name="name" value="<?= e($l['name']) ?>" placeholder="z. B. Werkstatt Mitte" <?= $lid ? 'required' : '' ?>></label>
        <label class="field field-wide"><span>Straße</span><input type="text" name="street" value="<?= e($l['street']) ?>"></label>
        <label class="field"><span>PLZ</span><input type="text" name="zip" value="<?= e($l['zip']) ?>"></label>
        <label class="field"><span>Ort</span><input type="text" name="city" value="<?= e($l['city']) ?>"></label>
        <label class="field field-wide"><span>Abholhinweis (öffentlich)</span><input type="text" name="contact" value="<?= e($l['contact']) ?>" placeholder="z. B. Abholung abends ab 18 Uhr"></label>
        <label class="field field-wide"><span>Interne Notiz</span><input type="text" name="notes" value="<?= e($l['notes']) ?>"></label>
        <label class="field"><span>Reihenfolge</span><input type="number" name="sort" value="<?= (int) $l['sort'] ?>"></label>
        <label class="check"><input type="checkbox" name="active" value="1"<?= (int) $l['active'] ? ' checked' : '' ?>><span>Aktiv (im Filter)</span></label>
      </div>
      <div class="form-actions">
        <?php if ($lid): ?><button class="btn btn-ghost btn-sm" type="submit" name="action" value="delete" data-confirm="Standort löschen?"><?= icon('trash') ?>Löschen</button>
          <a class="btn btn-ghost btn-sm" href="<?= e(url('admin/sperrzeiten.php', array('standort' => $lid))) ?>#neu"><?= icon('ban') ?>Standort sperren</a><?php endif; ?>
        <button class="btn btn-primary btn-sm" type="submit" name="action" value="save"><?= $lid ? 'Speichern' : 'Anlegen' ?></button>
      </div>
    </form>
  <?php endforeach; ?>
</div>
<?php admin_footer(); ?>
