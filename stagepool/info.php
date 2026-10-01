<?php
require __DIR__ . '/inc/bootstrap.php';
$locations = locations_all();
$buf = buffer_days();
view('header', array('pageTitle' => 'So funktioniert’s', 'active' => 'info'));
?>
<section class="wrap page-head">
  <p class="eyebrow">So funktioniert’s</p>
  <h1>Leihen ohne Umwege</h1>
  <p class="lead">Wir sind ein kleiner Pool aus Leuten, die Event-Technik besitzen – und sie für kleine Veranstaltungen verleihen. Keine Großproduktionen, sondern Geburtstage, Vereinsfeste, Hochzeiten, Partys und Auftritte.</p>
</section>

<section class="wrap info-grid">
  <article class="panel">
    <h2><?= icon('calendar') ?>Reservierung &amp; Buchung</h2>
    <ol class="flow">
      <li><b>Anfrage senden</b><span>Die Geräte sind ab sofort für dich <em>reserviert</em> und für andere blockiert.</span></li>
      <li><b>Bestätigung</b><span>Wir prüfen alles und bestätigen per E-Mail – jetzt ist es <em>fest gebucht</em>.</span></li>
      <li><b>Abholung</b><span>Du holst die Technik am Standort ab, wir erklären dir alles Wichtige.</span></li>
      <li><b>Rückgabe</b><span>Nach dem Event bringst du alles zurück – danach ist es wieder frei.</span></li>
    </ol>
    <?php if ($buf > 0): ?><p class="muted"><?= icon('info') ?> Vor und nach deinem Zeitraum halten wir automatisch <?= plural($buf, 'Tag', 'Tage') ?> für Übergabe und Check frei. Dafür zahlst du nichts.</p><?php endif; ?>
  </article>

  <article class="panel" id="abholung">
    <h2><?= icon('pin') ?>Abholung &amp; Standorte</h2>
    <div class="prose"><?= md_lite(setting('pickup_info')) ?></div>
    <?php if ($locations): ?>
      <ul class="loc-list">
        <?php foreach ($locations as $l): ?>
          <li><b><?= e($l['name']) ?></b><span><?= e(trim($l['zip'] . ' ' . $l['city'])) ?></span><?php if ($l['contact']): ?><small class="muted"><?= e($l['contact']) ?></small><?php endif; ?>
            <a class="link-btn" href="<?= e(url('', array('standort' => $l['id']))) ?>#katalog">Geräte an diesem Standort<?= icon('arrow-r') ?></a></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </article>

  <article class="panel">
    <h2><?= icon('tag') ?>Preise</h2>
    <p>Jedes Gerät hat einen Tagespreis. Gezählt werden alle Tage von Start bis Ende deines Zeitraums – der Anfragekorb rechnet automatisch zusammen.</p>
    <?php if (extra_day_percent() < 100): ?>
      <p><b>Mehrere Tage lohnen sich:</b> <?= e(pricing_hint()) ?> Beispiel bei 100 € pro Tag: 1 Tag <?= money(rental_price(100, 1), true) ?>, 2 Tage <?= money(rental_price(100, 2), true) ?>, 3 Tage <?= money(rental_price(100, 3), true) ?>.</p>
    <?php endif; ?>
    <p class="muted"><?= e(setting('price_note')) ?></p>
    <p>Für manche Geräte fällt bei Abholung eine Kaution an, die du bei vollständiger Rückgabe zurückbekommst.</p>
  </article>

  <article class="panel">
    <h2><?= icon('flame') ?>Flammen, Funken &amp; Laser</h2>
    <p>Effektgeräte mit Flammen, Funken oder Lasern geben wir nur nach persönlicher Einweisung heraus. Bitte kläre vorab mit deiner Location, ob die Effekte dort erlaubt sind.</p>
  </article>

  <article class="panel panel-wide prose" id="bedingungen">
    <?= md_lite(setting('terms')) ?>
  </article>

  <article class="panel panel-wide">
    <h2><?= icon('mail') ?>Kontakt</h2>
    <p>Fragen, Sonderwünsche oder größere Pakete? Melde dich gern direkt.</p>
    <p class="contact-line">
      <?php if (setting('contact_email')): ?><a class="btn btn-ghost" href="mailto:<?= e(setting('contact_email')) ?>"><?= icon('mail') ?><?= e(setting('contact_email')) ?></a><?php endif; ?>
      <?php if (setting('contact_phone')): ?><a class="btn btn-ghost" href="tel:<?= e(preg_replace('/[^\d+]/', '', setting('contact_phone'))) ?>"><?= icon('phone') ?><?= e(setting('contact_phone')) ?></a><?php endif; ?>
    </p>
  </article>
</section>
<?php view('footer'); ?>
