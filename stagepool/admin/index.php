<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require SP_ROOT . '/inc/admin.php';
require_login();

$t = today();
$in7 = date_add_days($t, 7);
$open = db_all("SELECT * FROM #__bookings WHERE status IN ('requested', 'offered') ORDER BY start_date LIMIT 20");
$pickups = db_all("SELECT * FROM #__bookings WHERE status = 'confirmed' AND start_date <= ? ORDER BY start_date LIMIT 20", array($in7));
$returns = db_all("SELECT * FROM #__bookings WHERE status = 'picked_up' ORDER BY end_date LIMIT 20");
$stats = array(
    'open'    => (int) db_value("SELECT COUNT(*) FROM #__bookings WHERE status IN ('requested', 'offered')"),
    'today'   => (int) db_value("SELECT COUNT(*) FROM #__bookings WHERE status = 'confirmed' AND start_date = ?", array($t)),
    'out'     => (int) db_value("SELECT COUNT(*) FROM #__bookings WHERE status = 'picked_up'"),
    'overdue' => (int) db_value("SELECT COUNT(*) FROM #__bookings WHERE status = 'picked_up' AND end_date < ?", array($t)),
    'products'=> (int) db_value('SELECT COUNT(*) FROM #__products WHERE active = 1'),
    'revenue' => (float) db_value("SELECT COALESCE(SUM(total), 0) FROM #__bookings WHERE status IN ('confirmed', 'picked_up', 'returned') AND start_date >= ?", array(date('Y-01-01'))),
);
$unbilled = db_all("SELECT b.* FROM #__bookings b WHERE b.status IN ('picked_up', 'returned') AND NOT EXISTS (SELECT 1 FROM #__documents d WHERE d.booking_id = b.id AND d.type = 'invoice' AND d.status <> 'cancelled') ORDER BY b.end_date LIMIT 20");
$openInvoices = db_all("SELECT * FROM #__documents WHERE type = 'invoice' AND status IN ('open', 'sent') ORDER BY due_date LIMIT 20");
$blocks = db_all('SELECT * FROM #__blocks WHERE end_date >= ? ORDER BY start_date LIMIT 5', array($t));

function booking_rows(array $rows, $mode)
{
    if (!$rows) {
        echo '<p class="muted empty-line">Nichts zu tun. ✓</p>';
        return;
    }
    echo '<ul class="task-list">';
    foreach ($rows as $b) {
        $late = $mode === 'return' && $b['end_date'] < today();
        $when = $mode === 'return' ? 'Rückgabe ' . date_de($b['end_date'], true) : period_de($b['start_date'], $b['end_date']);
        echo '<li' . ($late ? ' class="is-late"' : '') . '><a href="' . e(url('admin/buchung.php', array('id' => $b['id']))) . '">'
            . '<span class="code">' . e($b['code']) . '</span><span class="task-name">' . e($b['customer_name']) . '</span>'
            . '<span class="task-when">' . ($late ? icon('alert') . 'überfällig · ' : '') . e($when) . '</span>'
            . '<span class="task-sum">' . money($b['total'], true) . '</span></a></li>';
    }
    echo '</ul>';
}

admin_header('Übersicht', 'dashboard');
?>
<?php if (!is_file(SP_ROOT . '/storage/probe.txt')) { @file_put_contents(SP_ROOT . '/storage/probe.txt', 'stagepool-probe'); } ?>
<div class="flash flash-error" id="probe-warning" hidden><?= icon('alert') ?><span><b>Sicherheitswarnung:</b> Der Ordner <code>storage/</code> (Datenbank, Logs, Belege) ist von außen abrufbar. Euer Webserver wertet die mitgelieferten Sperr-Regeln nicht aus (typisch bei nginx). Bitte die Regeln aus <code>docs/nginx.conf.example</code> bzw. <code>docs/SYNOLOGY.md</code> einrichten. Details: <a href="<?= e(url('diagnose.php')) ?>">Diagnose</a>.</span></div>
<script>
fetch(<?= json_encode(url('storage/probe.txt')) ?> + '?t=' + Date.now(), { cache: 'no-store' }).then(function (r) { return r.ok ? r.text() : ''; }).then(function (t) {
  if (t.indexOf('stagepool-probe') !== -1) { document.getElementById('probe-warning').hidden = false; }
}).catch(function () {});
</script>
<div class="kpis">
  <a class="kpi kpi-accent" href="<?= e(url('admin/buchungen.php', array('status' => 'requested'))) ?>"><span>Offene Anfragen</span><b><?= $stats['open'] ?></b><?= icon('list') ?></a>
  <a class="kpi" href="<?= e(url('admin/buchungen.php', array('status' => 'confirmed'))) ?>"><span>Abholungen heute</span><b><?= $stats['today'] ?></b><?= icon('handover') ?></a>
  <a class="kpi" href="<?= e(url('admin/buchungen.php', array('status' => 'picked_up'))) ?>"><span>Gerade verliehen</span><b><?= $stats['out'] ?></b><?= icon('truck') ?><?php if ($stats['overdue']): ?><em><?= $stats['overdue'] ?> überfällig</em><?php endif; ?></a>
  <div class="kpi"><span>Umsatz <?= date('Y') ?> (bestätigt)</span><b><?= money($stats['revenue'], true) ?></b><?= icon('tag') ?></div>
</div>

<div class="dash-grid">
  <section class="card-admin">
    <div class="card-admin-head"><h2><?= icon('list') ?>Neue Anfragen</h2><a class="link-btn" href="<?= e(url('admin/buchungen.php', array('status' => 'requested'))) ?>">Alle</a></div>
    <p class="muted small">Diese Geräte sind reserviert und warten auf deine Bestätigung.</p>
    <?php booking_rows($open, 'open'); ?>
  </section>
  <section class="card-admin">
    <div class="card-admin-head"><h2><?= icon('handover') ?>Abholungen (nächste 7 Tage)</h2></div>
    <?php booking_rows($pickups, 'pickup'); ?>
  </section>
  <section class="card-admin">
    <div class="card-admin-head"><h2><?= icon('return') ?>Rückgaben</h2></div>
    <?php booking_rows($returns, 'return'); ?>
  </section>
  <section class="card-admin">
    <div class="card-admin-head"><h2><?= icon('tag') ?>Abrechnung</h2><a class="link-btn" href="<?= e(url('admin/belege.php', array('status' => 'unbezahlt'))) ?>">Belege</a></div>
    <?php if (!$unbilled && !$openInvoices): ?><p class="muted empty-line">Alles abgerechnet und bezahlt. ✓</p><?php endif; ?>
    <?php if ($unbilled): ?>
      <p class="muted small">Ausgegeben/zurück, aber noch ohne Rechnung:</p>
      <?php booking_rows($unbilled, 'pickup'); ?>
    <?php endif; ?>
    <?php if ($openInvoices): ?>
      <p class="muted small" style="margin-top:12px">Offene Rechnungen:</p>
      <ul class="task-list">
        <?php foreach ($openInvoices as $d): $late = $d['due_date'] < today(); ?>
          <li<?= $late ? ' class="is-late"' : '' ?>><a href="<?= e(url('admin/beleg.php', array('id' => $d['id']))) ?>"><span class="code"><?= e($d['number']) ?></span><span class="task-name"><?= e(strtok($d['customer_name'], "\n")) ?></span><span class="task-when"><?= $late ? icon('alert') . 'überfällig seit ' : 'fällig ' ?><?= e(date_de($d['due_date'])) ?></span><span class="task-sum"><?= money($d['total'], true) ?></span></a></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
  <section class="card-admin">
    <div class="card-admin-head"><h2><?= icon('ban') ?>Aktuelle Sperrzeiten</h2><a class="link-btn" href="<?= e(url('admin/sperrzeiten.php')) ?>">Verwalten</a></div>
    <?php if (!$blocks): ?><p class="muted empty-line">Keine Sperrzeiten geplant.</p><?php else: ?>
      <ul class="task-list">
        <?php foreach ($blocks as $bl): ?><li><a href="<?= e(url('admin/sperrzeiten.php')) ?>"><span class="task-name"><?= e($bl['reason'] ?: 'Gesperrt') ?></span><span class="task-when"><?= e(period_de($bl['start_date'], $bl['end_date'])) ?></span></a></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>

<div class="quick-actions">
  <a class="btn btn-primary" href="<?= e(url('admin/buchung.php', array('neu' => 1))) ?>"><?= icon('plus') ?>Buchung manuell anlegen</a>
  <a class="btn btn-ghost" href="<?= e(url('admin/buchhaltung.php', array('neu' => 'expense'))) ?>#eintrag"><?= icon('tag') ?>Ausgabe erfassen</a>
  <a class="btn btn-ghost" href="<?= e(url('admin/produkt.php')) ?>"><?= icon('box') ?>Neues Gerät</a>
  <a class="btn btn-ghost" href="<?= e(url('admin/sperrzeiten.php')) ?>#neu"><?= icon('ban') ?>Zeitraum sperren</a>
  <a class="btn btn-ghost" href="<?= e(url('admin/belegung.php')) ?>"><?= icon('timeline') ?>Belegungsplan</a>
</div>
<?php admin_footer(); ?>
