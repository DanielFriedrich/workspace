<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require SP_ROOT . '/inc/admin.php';
require_login();

if (is_post()) {
    csrf_check();
    $id = (int) input('id');
    if (input('action') === 'toggle' && $id) {
        db_exec('UPDATE #__products SET active = 1 - active, updated_at = ? WHERE id = ?', array(now(), $id));
        flash('success', 'Sichtbarkeit geändert.');
    }
    redirect(safe_return(input('return'), 'admin/produkte.php'));
}

$q = (string) input('q');
$cat = (int) input('kategorie');
$loc = (int) input('standort');
$owner = (int) input('besitzer');
$rows = array();
foreach (catalog_products(false) as $p) {
    if ($cat && (int) $p['category_id'] !== $cat) continue;
    if ($loc && (int) $p['location_id'] !== $loc) continue;
    if ($owner && (int) $p['owner_id'] !== $owner) continue;
    if ($q !== '' && strpos(lower($p['name'] . ' ' . $p['short_desc']), lower($q)) === false) continue;
    $rows[] = $p;
}
$upcoming = array();
foreach (db_all("SELECT bi.product_id, COUNT(DISTINCT b.id) AS n FROM #__booking_items bi JOIN #__bookings b ON b.id = bi.booking_id
                  WHERE b.status IN ('requested', 'offered', 'confirmed', 'picked_up') AND b.end_date >= ? GROUP BY bi.product_id", array(today())) as $r) {
    $upcoming[(int) $r['product_id']] = (int) $r['n'];
}

admin_header('Geräte', 'produkte');
?>
<div class="toolbar-admin">
  <form class="filters" method="get">
    <label class="search"><?= icon('search') ?><input type="search" name="q" value="<?= e($q) ?>" placeholder="Gerät suchen …"></label>
    <select name="kategorie" data-autosubmit><option value="">Alle Kategorien</option><?php foreach (categories_all() as $c): ?><?= opt($c['id'], $cat, $c['name']) ?><?php endforeach; ?></select>
    <select name="standort" data-autosubmit><option value="">Alle Standorte</option><?php foreach (locations_all(false) as $l): ?><?= opt($l['id'], $loc, $l['name']) ?><?php endforeach; ?></select>
    <select name="besitzer" data-autosubmit><option value="">Alle Besitzer</option><?php foreach (users_all() as $u): ?><?= opt($u['id'], $owner, $u['name']) ?><?php endforeach; ?></select>
  </form>
  <a class="btn btn-primary btn-sm" href="<?= e(url('admin/produkt.php')) ?>"><?= icon('plus') ?>Neues Gerät</a>
</div>

<div class="table-wrap">
<table class="table table-admin">
  <thead><tr><th></th><th>Gerät</th><th>Kategorie</th><th>Standort</th><th>Besitzer</th><th class="num">Stück</th><th class="num">Preis/Tag</th><th class="num">Buchungen</th><th>Sichtbar</th></tr></thead>
  <tbody>
  <?php if (!$rows): ?><tr><td colspan="9" class="muted">Keine Geräte gefunden.</td></tr><?php endif; ?>
  <?php foreach ($rows as $p): ?>
    <tr class="row-link<?= (int) $p['active'] ? '' : ' is-inactive' ?>" data-href="<?= e(url('admin/produkt.php', array('id' => $p['id']))) ?>" style="--accent:<?= e(accent($p)) ?>">
      <td class="thumb-cell"><?= product_media($p, 'thumb') ?></td>
      <td><a href="<?= e(url('admin/produkt.php', array('id' => $p['id']))) ?>"><b><?= e($p['name']) ?></b></a><?= (int) $p['featured'] ? ' <span class="tag">Highlight</span>' : '' ?></td>
      <td><?= e($p['category_name']) ?></td>
      <td><?= e($p['location_name'] ?: '–') ?></td>
      <td><?= e($p['owner_name'] ?: '–') ?></td>
      <td class="num"><?= (int) $p['quantity'] ?></td>
      <td class="num"><?= money($p['price_day']) ?></td>
      <td class="num"><?= isset($upcoming[(int) $p['id']]) ? $upcoming[(int) $p['id']] : 0 ?></td>
      <td>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="return" value="<?= e(current_url()) ?>">
          <button class="switch<?= (int) $p['active'] ? ' is-on' : '' ?>" type="submit" aria-label="Sichtbarkeit umschalten"><span></span></button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<p class="muted small">Unsichtbare Geräte erscheinen nicht auf der Website, bestehende Buchungen bleiben erhalten.</p>
<?php admin_footer(); ?>
