<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require SP_ROOT . '/inc/admin.php';
require_login();

$icons = category_icon_options();
$colors = category_colors();

if (is_post()) {
    csrf_check();
    $action = input('action');
    $id = (int) input('id');
    if ($action === 'save') {
        $name = (string) input('name');
        $icon = isset($icons[input('icon')]) ? input('icon') : 'box';
        $color = preg_match('/^#[0-9a-fA-F]{6}$/', input('color')) ? input('color') : '#ff2e93';
        if ($name === '') {
            flash('error', 'Bitte einen Namen angeben.');
            redirect('admin/kategorien.php');
        }
        $slug = slugify($name);
        $n = 1;
        $base = $slug;
        while (db_value('SELECT COUNT(*) FROM #__categories WHERE slug = ? AND id <> ?', array($slug, $id))) {
            $slug = $base . '-' . (++$n);
        }
        $data = array('name' => $name, 'slug' => $slug, 'icon' => $icon, 'color' => $color, 'sort' => (int) input('sort'));
        if ($id) {
            db_update('categories', $data, 'id = ?', array($id));
        } else {
            db_insert('categories', $data);
        }
        flash('success', 'Kategorie gespeichert.');
    }
    if ($action === 'delete' && $id) {
        $used = (int) db_value('SELECT COUNT(*) FROM #__products WHERE category_id = ?', array($id));
        if ($used) {
            flash('error', 'Diese Kategorie enthält noch ' . plural($used, 'Gerät', 'Geräte') . '. Bitte zuerst umsortieren.');
        } else {
            db_exec('DELETE FROM #__categories WHERE id = ?', array($id));
            flash('success', 'Kategorie gelöscht.');
        }
    }
    redirect('admin/kategorien.php');
}

$counts = array();
foreach (db_all('SELECT category_id, COUNT(*) AS n FROM #__products GROUP BY category_id') as $r) {
    $counts[(int) $r['category_id']] = (int) $r['n'];
}
$rows = categories_all();
$rows[] = array('id' => 0, 'name' => '', 'icon' => 'box', 'color' => '#ff2e93', 'sort' => (count($rows) + 1) * 10);

admin_header('Kategorien', 'kategorien');
?>
<p class="muted">Kategorien erscheinen als Filter-Chips im Katalog. Icon und Farbe bestimmen den Look der Gerätekarten ohne eigenes Foto.</p>
<div class="edit-list">
  <?php foreach ($rows as $c): $cid = (int) $c['id']; ?>
    <form class="edit-row<?= $cid ? '' : ' is-new' ?>" method="post" style="--accent:<?= e($c['color']) ?>">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $cid ?>">
      <span class="edit-icon"><?= icon($c['icon']) ?></span>
      <label class="field"><span><?= $cid ? 'Name' : 'Neue Kategorie' ?></span><input type="text" name="name" value="<?= e($c['name']) ?>" <?= $cid ? 'required' : 'placeholder="z. B. Bühne & Traversen"' ?>></label>
      <label class="field"><span>Icon</span><select name="icon"><?php foreach ($icons as $k => $l): ?><?= opt($k, $c['icon'], $l) ?><?php endforeach; ?></select></label>
      <label class="field"><span>Farbe</span><select name="color" data-color-select><?php foreach ($colors as $k => $l): ?><?= opt($k, $c['color'], $l) ?><?php endforeach; ?></select></label>
      <label class="field field-xs"><span>Reihenf.</span><input type="number" name="sort" value="<?= (int) $c['sort'] ?>"></label>
      <div class="edit-actions">
        <?php if ($cid): ?><span class="muted small"><?= plural(isset($counts[$cid]) ? $counts[$cid] : 0, 'Gerät', 'Geräte') ?></span><?php endif; ?>
        <button class="btn btn-primary btn-sm" type="submit" name="action" value="save"><?= $cid ? 'Speichern' : icon('plus') . 'Anlegen' ?></button>
        <?php if ($cid): ?><button class="icon-btn icon-btn-sm" type="submit" name="action" value="delete" data-confirm="Kategorie löschen?" aria-label="Löschen"><?= icon('trash') ?></button><?php endif; ?>
      </div>
    </form>
  <?php endforeach; ?>
</div>
<?php admin_footer(); ?>
