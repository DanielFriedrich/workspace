<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require SP_ROOT . '/inc/admin.php';
require_login();

if (is_post()) {
    csrf_check();
    $action = input('action');
    if ($action === 'create') {
        $scope = in_array(input('scope'), array('all', 'location', 'product'), true) ? input('scope') : 'product';
        $ref = $scope === 'location' ? (int) input('location_id') : ($scope === 'product' ? (int) input('product_id') : null);
        $from = parse_date(input('start_date'));
        $to = parse_date(input('end_date'));
        if ($err = validate_period($from, $to, true)) {
            flash('error', $err);
        } elseif ($scope !== 'all' && !$ref) {
            flash('error', $scope === 'location' ? 'Bitte einen Standort wählen.' : 'Bitte ein Gerät wählen.');
        } else {
            db_insert('blocks', array(
                'scope' => $scope, 'ref_id' => $ref, 'start_date' => $from, 'end_date' => $to,
                'reason' => (string) input('reason'), 'created_by' => (int) current_user()['id'], 'created_at' => now(),
            ));
            // Hinweis auf bestehende Buchungen im Zeitraum
            $affected = db_all(
                "SELECT DISTINCT b.id, b.code, b.customer_name FROM #__bookings b
                   JOIN #__booking_allocations a ON a.booking_id = b.id
                   JOIN #__product_stock s ON s.id = a.stock_id
                  WHERE b.status IN ('requested', 'offered', 'confirmed', 'picked_up') AND b.start_date <= ? AND b.end_date >= ?"
                . ($scope === 'location' ? ' AND s.location_id = ' . (int) $ref : '')
                . ($scope === 'product' ? ' AND s.product_id = ' . (int) $ref : ''),
                array($to, $from)
            );
            flash('success', 'Sperrzeit angelegt. Neue Anfragen für diesen Zeitraum sind jetzt blockiert.');
            if ($affected) {
                $codes = array();
                foreach ($affected as $a) {
                    $codes[] = $a['code'] . ' (' . $a['customer_name'] . ')';
                }
                flash('error', 'Achtung: Bestehende Buchungen im Zeitraum bleiben gültig und müssen ggf. manuell geklärt werden: ' . implode(', ', $codes));
            }
        }
    }
    if ($action === 'delete') {
        db_exec('DELETE FROM #__blocks WHERE id = ?', array((int) input('id')));
        flash('success', 'Sperrzeit entfernt.');
    }
    redirect('admin/sperrzeiten.php');
}

$showPast = input('alle') === '1';
$rows = db_all(
    'SELECT bl.*, u.name AS user_name, l.name AS location_name, p.name AS product_name
       FROM #__blocks bl
       LEFT JOIN #__users u ON u.id = bl.created_by
       LEFT JOIN #__locations l ON bl.scope = \'location\' AND l.id = bl.ref_id
       LEFT JOIN #__products p ON bl.scope = \'product\' AND p.id = bl.ref_id'
    . ($showPast ? '' : ' WHERE bl.end_date >= ?') . ' ORDER BY bl.start_date',
    $showPast ? array() : array(today())
);
$preProduct = (int) input('produkt');
$preLocation = (int) input('standort');
$preScope = $preLocation ? 'location' : ($preProduct ? 'product' : 'location');

admin_header('Sperrzeiten', 'sperrzeiten');
?>
<p class="muted">Sperrzeiten blocken Geräte, ohne dass eine Buchung nötig ist – zum Beispiel wenn jemand im Urlaub ist (ganzer Standort), ein Gerät in Reparatur ist oder ihr es selbst braucht.</p>

<div class="admin-cols">
  <section class="card-admin">
    <div class="card-admin-head"><h2><?= icon('ban') ?><?= $showPast ? 'Alle Sperrzeiten' : 'Aktuelle & geplante Sperrzeiten' ?></h2>
      <a class="link-btn" href="<?= e(url('admin/sperrzeiten.php', $showPast ? array() : array('alle' => 1))) ?>"><?= $showPast ? 'Nur aktuelle' : 'Auch vergangene' ?></a></div>
    <?php if (!$rows): ?><p class="muted empty-line">Keine Sperrzeiten.</p><?php else: ?>
    <div class="table-wrap">
    <table class="table table-admin">
      <thead><tr><th>Zeitraum</th><th>Bereich</th><th>Grund</th><th>Von</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr<?= $r['end_date'] < today() ? ' class="is-inactive"' : '' ?>>
            <td><b><?= e(period_de($r['start_date'], $r['end_date'])) ?></b><br><small class="muted"><?= plural(days_inclusive($r['start_date'], $r['end_date']), 'Tag', 'Tage') ?></small></td>
            <td><?php if ($r['scope'] === 'all'): ?><span class="tag tag-hot">Alle Geräte</span>
              <?php elseif ($r['scope'] === 'location'): ?><?= icon('pin') ?> <?= e($r['location_name'] ?: 'Standort #' . $r['ref_id']) ?>
              <?php else: ?><?= icon('box') ?> <?= e($r['product_name'] ?: 'Gerät #' . $r['ref_id']) ?><?php endif; ?></td>
            <td><?= e($r['reason']) ?></td>
            <td class="muted small"><?= e($r['user_name']) ?></td>
            <td><form method="post" data-confirm="Sperrzeit entfernen?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="icon-btn icon-btn-sm" type="submit" aria-label="Entfernen"><?= icon('trash') ?></button></form></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </section>

  <aside class="admin-aside">
    <form class="card-admin" id="neu" method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="create">
      <h2><?= icon('plus') ?>Neue Sperrzeit</h2>
      <fieldset class="segmented" data-scope>
        <label><input type="radio" name="scope" value="location"<?= $preScope === 'location' ? ' checked' : '' ?>><span>Standort</span></label>
        <label><input type="radio" name="scope" value="product"<?= $preScope === 'product' ? ' checked' : '' ?>><span>Einzelnes Gerät</span></label>
        <label><input type="radio" name="scope" value="all"><span>Alles</span></label>
      </fieldset>
      <label class="field" data-scope-field="location"><span>Standort</span><select name="location_id"><option value="">Bitte wählen</option><?php foreach (locations_all(false) as $l): ?><?= opt($l['id'], $preLocation, $l['name']) ?><?php endforeach; ?></select></label>
      <label class="field" data-scope-field="product"><span>Gerät</span><select name="product_id"><option value="">Bitte wählen</option><?php foreach (catalog_products(false) as $p): ?><?= opt($p['id'], $preProduct, $p['name'] . ($p['location_name'] ? ' · ' . $p['location_name'] : '')) ?><?php endforeach; ?></select></label>
      <div class="form-grid">
        <label class="field"><span>Von</span><input type="date" name="start_date" required data-range-start></label>
        <label class="field"><span>Bis</span><input type="date" name="end_date" required data-range-end></label>
      </div>
      <label class="field"><span>Grund (intern)</span><input type="text" name="reason" placeholder="z. B. Urlaub, Reparatur, Eigenbedarf" maxlength="255"></label>
      <button class="btn btn-primary btn-block" type="submit"><?= icon('ban') ?>Sperren</button>
      <p class="muted small">Auf der Website erscheinen gesperrte Tage einfach als „gesperrt“ – der Grund ist nicht öffentlich.</p>
    </form>
  </aside>
</div>
<?php admin_footer(); ?>
