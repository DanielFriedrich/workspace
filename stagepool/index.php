<?php
require __DIR__ . '/inc/bootstrap.php';

/* ---------- Zeitraum aus dem Filter übernehmen ---------- */
if (isset($_GET['reset'])) {
    period_clear();
    redirect('', array('kategorie' => input('kategorie'), 'standort' => input('standort'), 'q' => input('q')));
}
if (isset($_GET['von']) || isset($_GET['bis'])) {
    if (input('von') === '' && input('bis') === '') {
        period_clear();
    } else {
        $err = period_set(input('von'), input('bis'));
        if ($err) {
            flash('error', $err);
        }
    }
    // Saubere URL ohne Datum (Zeitraum liegt in der Session)
    redirect('', array('kategorie' => input('kategorie'), 'standort' => input('standort'), 'q' => input('q'), 'sort' => input('sort')));
}

$period = period_get();
$q = (string) input('q');
$catSlug = (string) input('kategorie');
$locId = (int) input('standort');
$sort = (string) input('sort', 'empfohlen');

$categories = categories_all();
$locations = locations_all();
$all = catalog_products();

/* ---------- Filtern ---------- */
$counts = array();
$products = array();
$needle = lower($q);
foreach ($all as $p) {
    if ($locId && !product_at_location($p, $locId)) {
        continue;
    }
    if ($needle !== '') {
        $hay = lower($p['name'] . ' ' . $p['short_desc'] . ' ' . $p['description'] . ' ' . $p['category_name'] . ' ' . $p['specs']);
        if (strpos($hay, $needle) === false) {
            continue;
        }
    }
    $products[] = $p;
}

$free = array();
$hidden = 0;
if ($period && $products) {
    $free = avail_free($products, $period['from'], $period['to']);
    $tmp = array();
    foreach ($products as $p) {
        if ($free[(int) $p['id']] > 0) {
            $tmp[] = $p;
        } elseif (!$catSlug || $p['category_slug'] === $catSlug) {
            $hidden++;
        }
    }
    $products = $tmp;
}
foreach ($products as $p) {
    $k = (string) $p['category_slug'];
    $counts[$k] = isset($counts[$k]) ? $counts[$k] + 1 : 1;
}
$totalBeforeCat = count($products);
if ($catSlug !== '') {
    $products = array_values(array_filter($products, function ($p) use ($catSlug) {
        return $p['category_slug'] === $catSlug;
    }));
}

/* ---------- Sortieren ---------- */
usort($products, function ($a, $b) use ($sort) {
    if ($sort === 'preis-auf') {
        return unit_price($a) <=> unit_price($b);
    }
    if ($sort === 'preis-ab') {
        return unit_price($b) <=> unit_price($a);
    }
    if ($sort === 'name') {
        return strcasecmp($a['name'], $b['name']);
    }
    if ((int) $a['featured'] !== (int) $b['featured']) {
        return (int) $b['featured'] - (int) $a['featured'];
    }
    return 0;
});

$params = array('kategorie' => $catSlug, 'standort' => $locId ?: '', 'q' => $q, 'sort' => $sort !== 'empfohlen' ? $sort : '');
$filtered = $q !== '' || $catSlug !== '' || $locId || $period;
$deviceCount = 0;
foreach ($all as $p) {
    $deviceCount += (int) $p['quantity'];
}

$isTeam = price_tier() === 'internal';
$active = 'katalog';
view('header', array('active' => $active));
?>
<section class="hero">
  <div class="stage" aria-hidden="true">
    <span class="beam b1"></span><span class="beam b2"></span><span class="beam b3"></span><span class="beam b4"></span><span class="beam b5"></span>
    <span class="haze"></span><span class="floor"></span>
  </div>
  <div class="wrap hero-inner">
    <div class="hero-copy">
      <p class="eyebrow"><span class="dot"></span>Mini-Verleih für kleine Events</p>
      <h1>Licht. Sound.<br><span class="grad">Effekte.</span></h1>
      <p class="lead"><?= e(setting('hero_text')) ?></p>
      <ul class="hero-stats">
        <li><b><?= (int) $deviceCount ?></b><span>Geräte im Pool</span></li>
        <li><b><?= count($categories) ?></b><span>Kategorien</span></li>
        <li><b><?= max(1, count($locations)) ?></b><span><?= count($locations) === 1 ? 'Standort' : 'Standorte' ?></span></li>
      </ul>
    </div>

    <form class="finder" id="finder" action="<?= e(url('')) ?>#katalog" method="get">
      <div class="finder-head">
        <span class="finder-icon"><?= icon('calendar') ?></span>
        <div>
          <h2>Wann ist dein Event?</h2>
          <p>Wir zeigen dir nur, was in deinem Zeitraum frei ist.</p>
        </div>
      </div>
      <div class="field-pair">
        <label class="field">
          <span>Abholung / Start</span>
          <input type="date" name="von" value="<?= e($period ? $period['from'] : '') ?>" min="<?= e(earliest_start()) ?>" data-range-start required>
        </label>
        <label class="field">
          <span>Rückgabe / Ende</span>
          <input type="date" name="bis" value="<?= e($period ? $period['to'] : '') ?>" min="<?= e(earliest_start()) ?>" data-range-end required>
        </label>
      </div>
      <label class="field">
        <span>Standort</span>
        <select name="standort">
          <option value="">Egal – alle Standorte</option>
          <?php foreach ($locations as $l): ?>
            <option value="<?= (int) $l['id'] ?>"<?= $locId === (int) $l['id'] ? ' selected' : '' ?>><?= e($l['name'] . ($l['city'] ? ' · ' . $l['city'] : '')) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <input type="hidden" name="kategorie" value="<?= e($catSlug) ?>">
      <input type="hidden" name="q" value="<?= e($q) ?>">
      <button class="btn btn-primary btn-block" type="submit"><?= icon('search') ?>Verfügbare Technik zeigen</button>
      <p class="finder-note"><?= icon('info') ?>
        <?php $buf = buffer_days(); if ($buf > 0): ?>
          Für Abholung und Rückgabe halten wir automatisch <?= plural($buf, 'Tag', 'Tage') ?> davor und danach frei.
        <?php else: ?>
          Abholung und Rückgabe stimmen wir nach deiner Anfrage ab.
        <?php endif; ?>
      </p>
      <?php if ($period): ?>
        <a class="finder-reset" href="<?= e(url('', array_merge($params, array('reset' => 1)))) ?>"><?= icon('x') ?>Zeitraum zurücksetzen</a>
      <?php endif; ?>
    </form>
  </div>
</section>

<div class="ticker" aria-hidden="true">
  <div class="ticker-track">
    <?php for ($i = 0; $i < 2; $i++): foreach ($categories as $c): ?>
      <span><?= icon($c['icon']) ?><?= e($c['name']) ?></span>
    <?php endforeach; endfor; ?>
  </div>
</div>

<section class="catalog wrap" id="katalog">
  <div class="catalog-head">
    <div>
      <p class="eyebrow">Katalog</p>
      <h2><?= $period ? 'Frei in deinem Zeitraum' : 'Equipment entdecken' ?></h2>
      <p class="catalog-count">
        <?= plural(count($products), 'Gerät', 'Geräte') ?><?= $period ? ' verfügbar' : '' ?>
        <?php if ($period): ?> · <?= e(period_de($period['from'], $period['to'])) ?> · <?= plural($period['days'], 'Miettag', 'Miettage') ?><?php endif; ?>
      </p>
    </div>
    <form class="catalog-tools" action="<?= e(url('')) ?>#katalog" method="get" role="search">
      <input type="hidden" name="kategorie" value="<?= e($catSlug) ?>">
      <input type="hidden" name="standort" value="<?= e($locId ?: '') ?>">
      <label class="search">
        <?= icon('search') ?>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Suchen: Nebel, Funk, Akku …" aria-label="Geräte durchsuchen">
      </label>
      <label class="select-sm">
        <span class="sr-only">Sortierung</span>
        <select name="sort" data-autosubmit>
          <option value="empfohlen"<?= $sort === 'empfohlen' ? ' selected' : '' ?>>Empfohlen</option>
          <option value="preis-auf"<?= $sort === 'preis-auf' ? ' selected' : '' ?>>Preis aufsteigend</option>
          <option value="preis-ab"<?= $sort === 'preis-ab' ? ' selected' : '' ?>>Preis absteigend</option>
          <option value="name"<?= $sort === 'name' ? ' selected' : '' ?>>Name A–Z</option>
        </select>
      </label>
    </form>
  </div>

  <nav class="chips" aria-label="Kategorien">
    <a class="chip<?= $catSlug === '' ? ' is-active' : '' ?>" href="<?= e(url('', array_merge($params, array('kategorie' => '')))) ?>#katalog">
      <?= icon('grid') ?>Alle<span class="chip-count"><?= $totalBeforeCat ?></span>
    </a>
    <?php foreach ($categories as $c): $n = isset($counts[$c['slug']]) ? $counts[$c['slug']] : 0; ?>
      <a class="chip<?= $catSlug === $c['slug'] ? ' is-active' : '' ?><?= $n === 0 ? ' is-empty' : '' ?>" style="--accent:<?= e(accent(array('category_color' => $c['color']))) ?>" href="<?= e(url('', array_merge($params, array('kategorie' => $c['slug'])))) ?>#katalog">
        <?= icon($c['icon']) ?><?= e($c['name']) ?><span class="chip-count"><?= $n ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <?php if ($period && $hidden > 0): ?>
    <p class="notice"><?= icon('info') ?><span><?= plural($hidden, 'Gerät ist', 'Geräte sind') ?> in diesem Zeitraum schon belegt und ausgeblendet. Im <a href="<?= e(url('kalender.php', array('start' => $period['from']))) ?>">Belegungskalender</a> siehst du, wann sie wieder frei sind.</span></p>
  <?php endif; ?>

  <?php if ($products): ?>
  <div class="grid">
    <?php foreach ($products as $p): $pid = (int) $p['id']; $f = $period ? $free[$pid] : null; ?>
      <article class="card" style="--accent:<?= e(accent($p)) ?>">
        <a class="card-media" href="<?= e(url('produkt.php', array('id' => $pid))) ?>" tabindex="-1" aria-hidden="true">
          <?= product_media($p) ?>
          <span class="card-cat"><?= icon($p['category_icon'] ?: 'box') ?><?= e($p['category_name']) ?></span>
          <?php if ((int) $p['quantity'] > 1): ?><span class="card-qty"><?= (int) $p['quantity'] ?>×</span><?php endif; ?>
        </a>
        <div class="card-body">
          <h3><a href="<?= e(url('produkt.php', array('id' => $pid))) ?>"><?= e($p['name']) ?></a></h3>
          <p class="card-desc"><?= e(excerpt($p['short_desc'], 110)) ?></p>
          <p class="card-loc"><?= icon('pin') ?><?= e(product_location_label($p)) ?></p>
          <div class="card-foot">
            <div class="price">
              <?php if ($isTeam): ?><em class="price-tag">Team</em><?php endif; ?>
              <b><?= money(unit_price($p), true) ?></b><span>/ Tag</span>
              <?php if ($isTeam): ?><small class="price-alt">Endkunde <?= money($p['price_day'], true) ?></small><?php endif; ?>
              <?php if ($period): ?><small><?= plural($period['days'], 'Tag', 'Tage') ?>: <?= money(rental_price(unit_price($p), $period['days']), true) ?></small><?php endif; ?>
            </div>
            <form method="post" action="<?= e(url('warenkorb.php')) ?>" data-add-form>
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="id" value="<?= $pid ?>">
              <input type="hidden" name="return" value="<?= e(current_url()) ?>">
              <button class="btn-add" type="submit" aria-label="<?= e($p['name']) ?> zur Anfrage hinzufügen"><?= icon('plus') ?><span>Anfragen</span></button>
            </form>
          </div>
          <?= availability_pill($f, (int) $p['quantity']) ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <div class="empty">
      <?= icon('search') ?>
      <h3>Nichts gefunden</h3>
      <p><?= $period ? 'In diesem Zeitraum ist mit diesen Filtern leider nichts frei. Probier einen anderen Zeitraum oder Standort.' : 'Mit diesen Filtern gibt es keine Treffer.' ?></p>
      <a class="btn btn-ghost" href="<?= e(url('', array('reset' => 1))) ?>">Alle Filter zurücksetzen</a>
    </div>
  <?php endif; ?>
</section>

<section class="steps wrap" aria-labelledby="steps-title">
  <p class="eyebrow">Ablauf</p>
  <h2 id="steps-title">In fünf Schritten zur Technik</h2>
  <ol class="steps-list">
    <li><span class="step-n">01</span><?= icon('search') ?><h3>Entdecken</h3><p>Stöbere durch Licht, Sound und Effekte aus unserem gemeinsamen Pool.</p></li>
    <li><span class="step-n">02</span><?= icon('calendar') ?><h3>Zeitraum wählen</h3><p>Gib an, wann dein Event ist – wir zeigen nur, was dann frei ist.</p></li>
    <li><span class="step-n">03</span><?= icon('bag') ?><h3>Anfragen</h3><p>Stell dein Paket zusammen. Mit der Anfrage ist alles für dich reserviert.</p></li>
    <li><span class="step-n">04</span><?= icon('check') ?><h3>Bestätigung</h3><p>Wir prüfen und bestätigen – dann ist die Technik fest gebucht.</p></li>
    <li><span class="step-n">05</span><?= icon('handover') ?><h3>Abholen &amp; zurück</h3><p>Abholung am Standort, Rückgabe nach dem Event – fertig.</p></li>
  </ol>
</section>
<?php view('footer'); ?>
