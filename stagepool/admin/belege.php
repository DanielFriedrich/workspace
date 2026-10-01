<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require SP_ROOT . '/inc/admin.php';
require_login();

$type = (string) input('typ');
$status = (string) input('status');
$year = (int) input('jahr', date('Y'));
$q = (string) input('q');

$where = array('d.doc_date >= ? AND d.doc_date <= ?');
$params = array($year . '-01-01', $year . '-12-31');
if (isset(doc_types()[$type])) {
    $where[] = 'd.type = ?';
    $params[] = $type;
}
if ($status === 'unbezahlt') {
    $where[] = "d.type = 'invoice' AND d.status IN ('open', 'sent')";
} elseif (isset(doc_statuses()[$status])) {
    $where[] = 'd.status = ?';
    $params[] = $status;
}
if ($q !== '') {
    $where[] = '(d.number LIKE ? OR d.customer_name LIKE ?)';
    array_push($params, '%' . $q . '%', '%' . $q . '%');
}
$rows = db_all('SELECT d.*, b.code FROM #__documents d LEFT JOIN #__bookings b ON b.id = d.booking_id WHERE ' . implode(' AND ', $where) . ' ORDER BY d.doc_date DESC, d.id DESC', $params);
$sumInvoices = 0.0;
$sumOpen = 0.0;
foreach ($rows as $r) {
    if ($r['type'] === 'invoice' && $r['status'] !== 'cancelled') {
        $sumInvoices += (float) $r['total'];
        if ($r['status'] !== 'paid') {
            $sumOpen += (float) $r['total'];
        }
    }
}
$years = array();
foreach (db_all('SELECT DISTINCT substr(doc_date, 1, 4) AS y FROM #__documents ORDER BY y DESC') as $r) {
    $years[] = (int) $r['y'];
}
if (!in_array((int) date('Y'), $years, true)) {
    array_unshift($years, (int) date('Y'));
}

admin_header('Angebote & Rechnungen', 'belege');
?>
<div class="kpis">
  <div class="kpi"><span>Rechnungen <?= $year ?></span><b><?= money($sumInvoices, true) ?></b><?= icon('tag') ?></div>
  <a class="kpi<?= $sumOpen > 0 ? ' kpi-accent' : '' ?>" href="<?= e(url('admin/belege.php', array('status' => 'unbezahlt', 'jahr' => $year))) ?>"><span>Davon offen</span><b><?= money($sumOpen, true) ?></b><?= icon('clock') ?></a>
  <div class="kpi"><span>Belege in der Auswahl</span><b><?= count($rows) ?></b><?= icon('list') ?></div>
</div>
<div class="toolbar-admin">
  <form class="filters" method="get">
    <label class="search"><?= icon('search') ?><input type="search" name="q" value="<?= e($q) ?>" placeholder="Nummer oder Kunde …"></label>
    <select name="typ" data-autosubmit><option value="">Angebote &amp; Rechnungen</option><?= opt('offer', $type, 'Nur Angebote') ?><?= opt('invoice', $type, 'Nur Rechnungen') ?></select>
    <select name="status" data-autosubmit><option value="">Alle Status</option><?= opt('unbezahlt', $status, 'Unbezahlte Rechnungen') ?><?php foreach (doc_statuses() as $k => $l): ?><?= opt($k, $status, $l) ?><?php endforeach; ?></select>
    <select name="jahr" data-autosubmit><?php foreach ($years as $y): ?><?= opt($y, $year, (string) $y) ?><?php endforeach; ?></select>
  </form>
</div>
<div class="table-wrap">
<table class="table table-admin">
  <thead><tr><th>Nummer</th><th>Art</th><th>Status</th><th>Kunde</th><th>Datum</th><th>Fällig / gültig</th><th>Buchung</th><th class="num">Betrag</th><th></th></tr></thead>
  <tbody>
  <?php if (!$rows): ?><tr><td colspan="9" class="muted">Keine Belege. Angebote und Rechnungen erstellst du direkt in einer Buchung.</td></tr><?php endif; ?>
  <?php foreach ($rows as $r): $late = $r['type'] === 'invoice' && in_array($r['status'], array('open', 'sent'), true) && $r['due_date'] < today(); ?>
    <tr class="row-link<?= $r['status'] === 'cancelled' ? ' is-inactive' : '' ?><?= $late ? ' is-late' : '' ?>" data-href="<?= e(url('admin/beleg.php', array('id' => $r['id']))) ?>">
      <td><a class="code" href="<?= e(url('admin/beleg.php', array('id' => $r['id']))) ?>"><?= e($r['number']) ?></a></td>
      <td><?= e(doc_types()[$r['type']]) ?></td>
      <td><?= doc_status_badge($r['status']) ?><?= $late ? ' <span class="status status-late">überfällig</span>' : '' ?></td>
      <td><?= e(strtok($r['customer_name'], "\n")) ?></td>
      <td><?= e(date_de($r['doc_date'])) ?></td>
      <td><?= e(date_de($r['due_date'])) ?></td>
      <td><?= $r['code'] ? '<a href="' . e(url('admin/buchung.php', array('id' => $r['booking_id']))) . '">' . e($r['code']) . '</a>' : '–' ?></td>
      <td class="num"><b><?= money($r['total']) ?></b></td>
      <td><a class="icon-btn icon-btn-sm" href="<?= e(url('admin/beleg.php', array('id' => $r['id'], 'pdf' => 1, 'download' => 1))) ?>" title="PDF herunterladen" aria-label="PDF herunterladen"><?= icon('upload') ?></a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<p class="muted small">Rechnungsnummern werden pro Jahr fortlaufend vergeben und nie doppelt verwendet. Stornierte Rechnungen bleiben zur Nachvollziehbarkeit erhalten.</p>
<?php admin_footer(); ?>
