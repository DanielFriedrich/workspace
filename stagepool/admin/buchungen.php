<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require SP_ROOT . '/inc/admin.php';
require_login();

$status = (string) input('status');
$q = (string) input('q');
$zeit = (string) input('zeit', 'kommend');
$page = max(1, (int) input('seite', 1));
$per = 40;

$where = array('1 = 1');
$params = array();
$statuses = booking_statuses();
if (isset($statuses[$status])) {
    $where[] = 'status = ?';
    $params[] = $status;
}
if ($zeit === 'kommend') {
    $where[] = 'end_date >= ?';
    $params[] = date_add_days(today(), -1);
} elseif ($zeit === 'vergangen') {
    $where[] = 'end_date < ?';
    $params[] = today();
}
if ($q !== '') {
    $where[] = '(code LIKE ? OR customer_name LIKE ? OR email LIKE ? OR organisation LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
$sqlWhere = implode(' AND ', $where);
$total = (int) db_value('SELECT COUNT(*) FROM #__bookings WHERE ' . $sqlWhere, $params);
$order = $zeit === 'vergangen' ? 'start_date DESC' : 'start_date ASC';
$rows = db_all('SELECT * FROM #__bookings WHERE ' . $sqlWhere . ' ORDER BY ' . $order . ', id DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per), $params);

$counts = array();
foreach (db_all('SELECT status, COUNT(*) AS n FROM #__bookings GROUP BY status') as $r) {
    $counts[$r['status']] = (int) $r['n'];
}
$itemCounts = array();
if ($rows) {
    $ids = array_map(function ($r) { return (int) $r['id']; }, $rows);
    foreach (db_all('SELECT booking_id, SUM(qty) AS n FROM #__booking_items WHERE booking_id IN (' . db_in($ids) . ') GROUP BY booking_id', $ids) as $r) {
        $itemCounts[(int) $r['booking_id']] = (int) $r['n'];
    }
}
$base = array('q' => $q, 'zeit' => $zeit !== 'kommend' ? $zeit : '');

admin_header('Anfragen & Buchungen', 'buchungen');
?>
<div class="toolbar-admin">
  <nav class="tabs">
    <a href="<?= e(url('admin/buchungen.php', $base)) ?>"<?= $status === '' ? ' aria-current="page"' : '' ?>>Alle</a>
    <?php foreach ($statuses as $k => $s): ?>
      <a href="<?= e(url('admin/buchungen.php', array_merge($base, array('status' => $k)))) ?>"<?= $status === $k ? ' aria-current="page"' : '' ?>><?= e($s['label']) ?><?php if (!empty($counts[$k])): ?><em><?= $counts[$k] ?></em><?php endif; ?></a>
    <?php endforeach; ?>
  </nav>
  <form class="filters" method="get">
    <input type="hidden" name="status" value="<?= e($status) ?>">
    <label class="search"><?= icon('search') ?><input type="search" name="q" value="<?= e($q) ?>" placeholder="Nr., Name, E-Mail …"></label>
    <select name="zeit" data-autosubmit>
      <?= opt('kommend', $zeit, 'Laufend & kommend') ?><?= opt('vergangen', $zeit, 'Vergangen') ?><?= opt('alle', $zeit, 'Alle Zeiträume') ?>
    </select>
    <a class="btn btn-primary btn-sm" href="<?= e(url('admin/buchung.php', array('neu' => 1))) ?>"><?= icon('plus') ?>Neue Buchung</a>
  </form>
</div>

<div class="table-wrap">
<table class="table table-admin">
  <thead><tr><th>Nr.</th><th>Status</th><th>Zeitraum</th><th>Kunde</th><th>Anlass</th><th class="num">Geräte</th><th class="num">Summe</th><th>Eingang</th></tr></thead>
  <tbody>
  <?php if (!$rows): ?><tr><td colspan="8" class="muted">Keine Einträge gefunden.</td></tr><?php endif; ?>
  <?php foreach ($rows as $b): $late = $b['status'] === 'picked_up' && $b['end_date'] < today(); ?>
    <tr class="row-link<?= $late ? ' is-late' : '' ?>" data-href="<?= e(url('admin/buchung.php', array('id' => $b['id']))) ?>">
      <td><a class="code" href="<?= e(url('admin/buchung.php', array('id' => $b['id']))) ?>"><?= e($b['code']) ?></a></td>
      <td><?= status_badge($b['status']) ?><?= $late ? ' <span class="status status-late">überfällig</span>' : '' ?></td>
      <td><?= e(period_de($b['start_date'], $b['end_date'])) ?></td>
      <td><b><?= e($b['customer_name']) ?></b><?= $b['organisation'] ? '<br><small class="muted">' . e($b['organisation']) . '</small>' : '' ?></td>
      <td><?= e($b['event_type']) ?></td>
      <td class="num"><?= isset($itemCounts[(int) $b['id']]) ? $itemCounts[(int) $b['id']] : 0 ?></td>
      <td class="num"><?= money($b['total']) ?></td>
      <td class="muted small"><?= e(date('d.m.y H:i', strtotime($b['created_at']))) ?><?= $b['source'] !== 'web' ? '<br>' . e($b['source'] === 'admin' ? 'manuell' : $b['source']) : '' ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php if ($total > $per): $pages = (int) ceil($total / $per); ?>
  <nav class="pager">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
      <a href="<?= e(url('admin/buchungen.php', array_merge($base, array('status' => $status, 'seite' => $i)))) ?>"<?= $i === $page ? ' aria-current="page"' : '' ?>><?= $i ?></a>
    <?php endfor; ?>
  </nav>
<?php endif; ?>
<?php admin_footer(); ?>
