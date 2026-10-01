<?php
require __DIR__ . '/inc/bootstrap.php';

$days = (int) input('tage', 21);
$days = in_array($days, array(14, 21, 28), true) ? $days : 21;
$start = parse_date(input('start'));
if (!$start || $start < today()) {
    $start = today();
}
$catSlug = (string) input('kategorie');
$locId = (int) input('standort');

$categories = categories_all();
$locations = locations_all();
$products = array();
foreach (catalog_products() as $p) {
    if ($catSlug !== '' && $p['category_slug'] !== $catSlug) {
        continue;
    }
    if ($locId && (int) $p['location_id'] !== $locId) {
        continue;
    }
    $products[] = $p;
}
$base = array('kategorie' => $catSlug, 'standort' => $locId ?: '', 'tage' => $days !== 21 ? $days : '');
$prev = date_add_days($start, -7);
if ($prev < today()) {
    $prev = today();
}

view('header', array('pageTitle' => 'Belegungskalender', 'active' => 'kalender'));
?>
<section class="wrap page-head">
  <p class="eyebrow">Belegungskalender</p>
  <h1>Was ist wann frei?</h1>
  <p class="lead">Alle Geräte auf einen Blick. Klick auf einen Starttag und einen Endtag, um den Zeitraum zu übernehmen und deine Anfrage zusammenzustellen.</p>
</section>

<section class="wrap">
  <form class="toolbar" method="get" action="<?= e(url('kalender.php')) ?>">
    <label class="field field-inline"><span>Ab</span><input type="date" name="start" value="<?= e($start) ?>" min="<?= e(today()) ?>" data-autosubmit></label>
    <label class="field field-inline"><span>Kategorie</span>
      <select name="kategorie" data-autosubmit>
        <option value="">Alle Kategorien</option>
        <?php foreach ($categories as $c): ?><option value="<?= e($c['slug']) ?>"<?= $catSlug === $c['slug'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label class="field field-inline"><span>Standort</span>
      <select name="standort" data-autosubmit>
        <option value="">Egal</option>
        <?php foreach ($locations as $l): ?><option value="<?= (int) $l['id'] ?>"<?= $locId === (int) $l['id'] ? ' selected' : '' ?>><?= e($l['name']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label class="field field-inline"><span>Ansicht</span>
      <select name="tage" data-autosubmit>
        <?php foreach (array(14 => '2 Wochen', 21 => '3 Wochen', 28 => '4 Wochen') as $k => $v): ?><option value="<?= $k ?>"<?= $days === $k ? ' selected' : '' ?>><?= $v ?></option><?php endforeach; ?>
      </select>
    </label>
    <div class="toolbar-nav">
      <a class="icon-btn" href="<?= e(url('kalender.php', array_merge($base, array('start' => $prev)))) ?>" aria-label="Eine Woche zurück"><?= icon('chev-l') ?></a>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('kalender.php', $base)) ?>">Heute</a>
      <a class="icon-btn" href="<?= e(url('kalender.php', array_merge($base, array('start' => date_add_days($start, 7))))) ?>" aria-label="Eine Woche vor"><?= icon('chev-r') ?></a>
    </div>
    <noscript><button class="btn btn-ghost btn-sm" type="submit">Anzeigen</button></noscript>
  </form>

  <?= calendar_legend() ?>

  <?php if ($products): ?>
    <div class="timeline-scroll"><?= render_timeline($products, $start, $days) ?></div>
  <?php else: ?>
    <div class="empty"><h3>Keine Geräte für diese Auswahl.</h3></div>
  <?php endif; ?>
</section>

<div class="range-bar" data-range-bar hidden>
  <div class="wrap range-bar-inner">
    <div><span class="muted small">Dein Zeitraum</span><b data-range-label>–</b></div>
    <div class="range-bar-actions">
      <button class="btn btn-ghost btn-sm" type="button" data-range-clear>Zurücksetzen</button>
      <a class="btn btn-ghost btn-sm" data-range-product hidden href="#">Dieses Gerät ansehen</a>
      <a class="btn btn-primary btn-sm" data-range-go href="#"><?= icon('search') ?>Verfügbare Geräte zeigen</a>
    </div>
  </div>
</div>
<script>window.SP_URLS = {catalog: <?= json_encode(url('')) ?>, product: <?= json_encode(url('produkt.php')) ?>, earliest: <?= json_encode(earliest_start()) ?>};</script>
<?php view('footer'); ?>
