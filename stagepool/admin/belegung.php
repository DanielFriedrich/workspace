<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require SP_ROOT . '/inc/admin.php';
require_login();

$days = (int) input('tage', 28);
$days = in_array($days, array(14, 28, 42, 56), true) ? $days : 28;
$start = parse_date(input('start'));
if (!$start) {
    $start = date_add_days(today(), -3);
}
$cat = (int) input('kategorie');
$loc = (int) input('standort');
$products = array();
foreach (catalog_products(false) as $p) {
    if ($cat && (int) $p['category_id'] !== $cat) continue;
    if ($loc && !product_at_location($p, $loc)) continue;
    $products[] = $p;
}
$base = array('kategorie' => $cat ?: '', 'standort' => $loc ?: '', 'tage' => $days);

admin_header('Belegungsplan', 'belegung');
?>
<div class="toolbar-admin">
  <form class="filters" method="get">
    <input type="date" name="start" value="<?= e($start) ?>" data-autosubmit aria-label="Startdatum">
    <select name="kategorie" data-autosubmit><option value="">Alle Kategorien</option><?php foreach (categories_all() as $c): ?><?= opt($c['id'], $cat, $c['name']) ?><?php endforeach; ?></select>
    <select name="standort" data-autosubmit><option value="">Alle Standorte</option><?php foreach (locations_all(false) as $l): ?><?= opt($l['id'], $loc, $l['name']) ?><?php endforeach; ?></select>
    <select name="tage" data-autosubmit><?php foreach (array(14 => '2 Wochen', 28 => '4 Wochen', 42 => '6 Wochen', 56 => '8 Wochen') as $k => $l): ?><?= opt($k, $days, $l) ?><?php endforeach; ?></select>
  </form>
  <div class="toolbar-nav">
    <a class="icon-btn" href="<?= e(url('admin/belegung.php', array_merge($base, array('start' => date_add_days($start, -7))))) ?>" aria-label="Woche zurück"><?= icon('chev-l') ?></a>
    <a class="btn btn-ghost btn-sm" href="<?= e(url('admin/belegung.php', array_merge($base, array('start' => '')))) ?>">Heute</a>
    <a class="icon-btn" href="<?= e(url('admin/belegung.php', array_merge($base, array('start' => date_add_days($start, 7))))) ?>" aria-label="Woche vor"><?= icon('chev-r') ?></a>
  </div>
</div>
<?= calendar_legend(true) ?>
<p class="muted small">Klick auf eine belegte Zelle öffnet die Buchung. Zahlen in Zellen = noch freie Stück. Tooltips zeigen Kunde und Buchungsnummer.</p>
<?php if ($products): ?>
  <div class="timeline-scroll"><?= render_timeline($products, $start, $days, true) ?></div>
<?php else: ?>
  <p class="muted">Keine Geräte für diese Auswahl.</p>
<?php endif; ?>
<?php admin_footer(); ?>
