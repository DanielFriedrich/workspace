<?php
require __DIR__ . '/inc/bootstrap.php';

$p = product_find((int) input('id'));
if (!$p) {
    http_response_code(404);
    view('header', array('pageTitle' => 'Nicht gefunden'));
    echo '<section class="wrap empty"><h1>Dieses Gerät gibt es nicht (mehr).</h1><a class="btn btn-ghost" href="' . e(url('')) . '">Zum Katalog</a></section>';
    view('footer');
    exit;
}
$pid = (int) $p['id'];
$period = period_get();

// Zeitraum aus Kalender-Link übernehmen
if (input('von') !== '' && input('bis') !== '') {
    $err = period_set(input('von'), input('bis'));
    if ($err) {
        flash('error', $err);
    }
    redirect('produkt.php', array('id' => $pid));
}

// Kalendermonate
$start = parse_date(input('m') . '-01');
if (!$start) {
    $start = date('Y-m-01', strtotime($period ? $period['from'] : today()));
}
if ($start < date('Y-m-01')) {
    $start = date('Y-m-01');
}
$months = array();
for ($i = 0; $i < 2; $i++) {
    $months[] = date('Y-m-01', strtotime($start . ' +' . $i . ' month'));
}
$calFrom = $months[0];
$calTo = date('Y-m-t', strtotime($months[1]));
$grid = avail_grid(array($p), $calFrom, $calTo);
$prevMonth = date('Y-m', strtotime($start . ' -1 month'));
$nextMonth = date('Y-m', strtotime($start . ' +1 month'));

$free = $period ? avail_free(array($p), $period['from'], $period['to']) : array();
$freeQty = $period ? $free[$pid] : null;
$qty = max(1, (int) $p['quantity']);
$inCart = cart_items();
$inCartQty = isset($inCart[$pid]) ? $inCart[$pid] : 0;

$specs = array();
foreach (preg_split('/\r?\n/', (string) $p['specs']) as $line) {
    $line = trim($line);
    if ($line === '') {
        continue;
    }
    $parts = explode(':', $line, 2);
    $specs[] = count($parts) === 2 ? array(trim($parts[0]), trim($parts[1])) : array('', $line);
}

$related = db_all(
    'SELECT p.*, c.icon AS category_icon, c.color AS category_color, c.name AS category_name, l.name AS location_name
       FROM #__products p LEFT JOIN #__categories c ON c.id = p.category_id LEFT JOIN #__locations l ON l.id = p.location_id
      WHERE p.active = 1 AND p.id <> ? AND p.category_id = ? ORDER BY p.featured DESC, p.sort LIMIT 4',
    array($pid, (int) $p['category_id'])
);

view('header', array('pageTitle' => $p['name'], 'active' => 'katalog', 'description' => $p['short_desc']));
?>
<div class="wrap product-page" style="--accent:<?= e(accent($p)) ?>">
  <nav class="crumbs" aria-label="Brotkrumen">
    <a href="<?= e(url('')) ?>#katalog">Katalog</a><?= icon('chev-r') ?>
    <?php if ($p['category_slug']): ?><a href="<?= e(url('', array('kategorie' => $p['category_slug']))) ?>#katalog"><?= e($p['category_name']) ?></a><?= icon('chev-r') ?><?php endif; ?>
    <span><?= e($p['name']) ?></span>
  </nav>

  <div class="product-top">
    <div class="product-media">
      <?= product_media($p, 'media-lg') ?>
    </div>

    <div class="product-info">
      <p class="product-cat"><?= icon($p['category_icon'] ?: 'box') ?><?= e($p['category_name'] ?: 'Equipment') ?></p>
      <h1><?= e($p['name']) ?></h1>
      <p class="product-lead"><?= e($p['short_desc']) ?></p>

      <dl class="facts">
        <div><dt>Mietpreis</dt><dd><b class="price-big"><?= money($p['price_day'], true) ?></b> / Tag</dd></div>
        <?php if ((float) $p['deposit'] > 0): ?><div><dt>Kaution</dt><dd><?= money($p['deposit'], true) ?> pro Stück</dd></div><?php endif; ?>
        <div><dt>Im Pool</dt><dd><?= plural($qty, 'Stück', 'Stück') ?></dd></div>
        <div><dt>Standort</dt><dd><?= icon('pin') ?><?= e($p['location_name'] ? $p['location_name'] . ($p['location_city'] ? ', ' . $p['location_city'] : '') : 'nach Absprache') ?></dd></div>
      </dl>

      <form class="book-box" method="post" action="<?= e(url('warenkorb.php')) ?>" data-book-box data-price="<?= e((float) $p['price_day']) ?>" data-max="<?= $qty ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="id" value="<?= $pid ?>">
        <input type="hidden" name="return" value="<?= e(url('produkt.php', array('id' => $pid))) ?>">
        <div class="field-pair">
          <label class="field"><span>Von</span><input type="date" name="von" value="<?= e($period ? $period['from'] : '') ?>" min="<?= e(earliest_start()) ?>" data-range-start></label>
          <label class="field"><span>Bis</span><input type="date" name="bis" value="<?= e($period ? $period['to'] : '') ?>" min="<?= e(earliest_start()) ?>" data-range-end></label>
        </div>
        <div class="book-row">
          <?php if ($qty > 1): ?>
          <label class="field field-qty"><span>Anzahl</span>
            <select name="qty" data-qty>
              <?php for ($i = 1; $i <= $qty; $i++): ?><option value="<?= $i ?>"><?= $i ?></option><?php endfor; ?>
            </select>
          </label>
          <?php else: ?><input type="hidden" name="qty" value="1" data-qty><?php endif; ?>
          <div class="book-sum" data-sum>
            <?php if ($period): ?>
              <span><?= plural($period['days'], 'Tag', 'Tage') ?></span><b><?= money($p['price_day'] * $period['days']) ?></b>
            <?php else: ?>
              <span>Zeitraum wählen</span><b>–</b>
            <?php endif; ?>
          </div>
        </div>
        <?php if ($period): ?>
          <p class="avail-line <?= $freeQty > 0 ? 'is-ok' : 'is-no' ?>">
            <?= $freeQty > 0 ? icon('check') . ($qty > 1 ? $freeQty . ' von ' . $qty . ' Stück frei' : 'Im gewählten Zeitraum verfügbar') : icon('ban') . 'Im gewählten Zeitraum leider belegt' ?>
          </p>
        <?php endif; ?>
        <button class="btn btn-primary btn-block" type="submit"><?= icon('bag') ?><?= $inCartQty ? 'Weitere hinzufügen (' . $inCartQty . ' in der Anfrage)' : 'Zur Anfrage hinzufügen' ?></button>
        <p class="book-hint">Unverbindlich: Mit dem Absenden der Anfrage wird das Gerät für dich reserviert.</p>
      </form>
    </div>
  </div>

  <div class="product-body">
    <section class="product-calendar" aria-labelledby="cal-title">
      <div class="section-head">
        <h2 id="cal-title"><?= icon('calendar') ?>Verfügbarkeit</h2>
        <div class="cal-nav">
          <?php if ($start > date('Y-m-01')): ?><a class="icon-btn" href="<?= e(url('produkt.php', array('id' => $pid, 'm' => $prevMonth))) ?>#cal-title" aria-label="Vorheriger Monat"><?= icon('chev-l') ?></a><?php else: ?><span class="icon-btn is-disabled"><?= icon('chev-l') ?></span><?php endif; ?>
          <a class="icon-btn" href="<?= e(url('produkt.php', array('id' => $pid, 'm' => $nextMonth))) ?>#cal-title" aria-label="Nächster Monat"><?= icon('chev-r') ?></a>
        </div>
      </div>
      <p class="muted small">Tipp: Klick auf zwei freie Tage, um deinen Zeitraum direkt im Kalender zu wählen.</p>
      <div class="months" data-range-calendar>
        <?php foreach ($months as $m): ?>
          <?= render_month((int) substr($m, 0, 4), (int) substr($m, 5, 2), $grid[$pid]) ?>
        <?php endforeach; ?>
      </div>
      <?= calendar_legend() ?>
    </section>

    <section class="product-details">
      <h2>Beschreibung</h2>
      <div class="prose"><?= md_lite($p['description'] ?: $p['short_desc']) ?></div>
      <?php if ($specs): ?>
        <h2>Technische Daten</h2>
        <dl class="specs">
          <?php foreach ($specs as $s): ?><div><dt><?= e($s[0]) ?></dt><dd><?= e($s[1]) ?></dd></div><?php endforeach; ?>
        </dl>
      <?php endif; ?>
      <?php if ($p['location_name']): ?>
        <h2>Abholung</h2>
        <p class="pickup"><?= icon('pin') ?><span><b><?= e($p['location_name']) ?></b><br><?= e(trim($p['location_zip'] . ' ' . $p['location_city'])) ?><?= $p['location_contact'] ? '<br><span class="muted">' . e($p['location_contact']) . '</span>' : '' ?></span></p>
        <p class="muted small">Die genaue Adresse und den Termin bekommst du mit der Bestätigung.</p>
      <?php endif; ?>
    </section>
  </div>

  <?php if ($related): ?>
  <section class="related">
    <h2>Passt dazu</h2>
    <div class="grid grid-sm">
      <?php foreach ($related as $r): ?>
        <a class="mini-card" href="<?= e(url('produkt.php', array('id' => $r['id']))) ?>" style="--accent:<?= e(accent($r)) ?>">
          <?= product_media($r) ?>
          <span class="mini-name"><?= e($r['name']) ?></span>
          <span class="mini-price"><?= money($r['price_day'], true) ?> / Tag</span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
</div>
<?php view('footer'); ?>
