<?php
/**
 * Statusseite einer Anfrage (Link aus der Bestätigungs-E-Mail).
 * Wird auch als Danke-Seite nach dem Absenden genutzt (danke.php).
 */
require_once __DIR__ . '/inc/bootstrap.php';

$code = strtoupper((string) input('code'));
$token = (string) input('t');
$row = $code !== '' ? db_one('SELECT id, token FROM #__bookings WHERE code = ?', array($code)) : null;
if (!$row || $token === '' || !hash_equals($row['token'], $token)) {
    http_response_code(404);
    view('header', array('pageTitle' => 'Anfrage nicht gefunden'));
    echo '<section class="wrap empty"><h1>Anfrage nicht gefunden</h1><p>Bitte nutze den Link aus deiner E-Mail.</p><a class="btn btn-ghost" href="' . e(url('')) . '">Zum Katalog</a></section>';
    view('footer');
    exit;
}
$b = booking_load($row['id']);
$canCancel = in_array($b['status'], array('requested', 'confirmed'), true) && $b['start_date'] > today();

if (is_post() && input('action') === 'cancel') {
    csrf_check();
    if ($canCancel) {
        booking_set_status($b['id'], 'cancelled', 'Storniert über den Kundenlink');
        booking_log($b['id'], 'customer_cancel');
        $b = booking_load($b['id']);
        mail_booking_status($b);
        foreach (notify_recipients() as $to) {
            send_mail($to, 'Storniert: ' . $b['code'] . ' – ' . $b['customer_name'], "Die Anfrage wurde vom Kunden storniert.\n\n" . booking_summary_text($b) . "\n\n" . absolute_url('admin/buchung.php', array('id' => $b['id'])));
        }
        flash('success', 'Deine Anfrage wurde storniert. Die Geräte sind wieder freigegeben.');
    }
    redirect('status.php', array('code' => $b['code'], 't' => $b['token']));
}

$isThanks = !empty($isThanks);
$steps = array(
    'requested' => array('Angefragt', 'Reserviert für dich'),
    'confirmed' => array('Bestätigt', 'Fest gebucht'),
    'picked_up' => array('Abgeholt', 'Viel Spaß beim Event'),
    'returned'  => array('Zurückgegeben', 'Abgeschlossen'),
);
$order = array_keys($steps);
$pos = array_search($b['status'], $order, true);

view('header', array('pageTitle' => $isThanks ? 'Danke für deine Anfrage' : 'Anfrage ' . $b['code']));
?>
<section class="wrap status-page">
  <?php if ($isThanks): ?>
    <div class="thanks">
      <div class="thanks-burst" aria-hidden="true"><?= icon('sparkle') ?></div>
      <p class="eyebrow">Anfrage gesendet</p>
      <h1>Danke, <?= e(strtok($b['customer_name'], ' ')) ?>!</h1>
      <p class="lead">Deine Technik ist jetzt für dich <b>reserviert</b>. Wir prüfen die Anfrage und melden uns mit einer Bestätigung. Eine Zusammenfassung haben wir an <b><?= e($b['email']) ?></b> geschickt.</p>
    </div>
  <?php else: ?>
    <p class="eyebrow">Deine Anfrage</p>
    <h1><?= e($b['code']) ?></h1>
  <?php endif; ?>

  <div class="status-card">
    <div class="status-top">
      <div><span class="muted small">Anfrage-Nr.</span><b class="code"><?= e($b['code']) ?></b></div>
      <div><span class="muted small">Zeitraum</span><b><?= e(period_de($b['start_date'], $b['end_date'])) ?></b></div>
      <div><span class="muted small">Status</span><?= status_badge($b['status']) ?></div>
    </div>

    <?php if ($pos !== false): ?>
    <ol class="progress">
      <?php foreach ($steps as $k => $s): $i = array_search($k, $order, true); ?>
        <li class="<?= $i < $pos ? 'is-done' : ($i === $pos ? 'is-current' : '') ?>"><span class="progress-dot"><?= $i <= $pos ? icon('check') : '' ?></span><b><?= e($s[0]) ?></b><small><?= e($s[1]) ?></small></li>
      <?php endforeach; ?>
    </ol>
    <?php else: ?>
      <p class="notice"><?= icon('info') ?><span>Diese Anfrage ist <?= e(lower(status_label($b['status']))) ?>. Die Geräte sind wieder freigegeben.</span></p>
    <?php endif; ?>

    <table class="table">
      <thead><tr><th>Gerät</th><th class="num">Anzahl</th><th class="num">Tage</th><th class="num">Summe</th></tr></thead>
      <tbody>
        <?php foreach ($b['items'] as $it): ?>
          <tr><td><?= e($it['product_name']) ?><?php if ($it['location_name']): ?><br><small class="muted"><?= icon('pin') ?><?= e($it['location_name']) ?></small><?php endif; ?></td>
            <td class="num"><?= (int) $it['qty'] ?></td><td class="num"><?= (int) $it['days'] ?></td><td class="num"><?= money($it['line_total']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr><th colspan="3">Mietpreis gesamt</th><th class="num"><?= money($b['total']) ?></th></tr>
        <?php if ((float) $b['deposit_total'] > 0): ?><tr><td colspan="3">Kaution (bei Abholung)</td><td class="num"><?= money($b['deposit_total']) ?></td></tr><?php endif; ?>
      </tfoot>
    </table>

    <div class="status-foot">
      <p class="muted small"><?= $b['handover'] === 'delivery' ? icon('truck') . 'Lieferung angefragt' : icon('handover') . 'Selbstabholung' ?> · Wir stimmen den Übergabetermin mit dir ab.</p>
      <?php if ($canCancel): ?>
        <form method="post" action="<?= e(url('status.php', array('code' => $b['code'], 't' => $b['token']))) ?>" data-confirm="Anfrage wirklich stornieren? Die Reservierung wird aufgehoben.">
          <?= csrf_field() ?><input type="hidden" name="action" value="cancel">
          <button class="btn btn-ghost btn-sm" type="submit"><?= icon('x') ?>Anfrage stornieren</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="status-actions">
    <a class="btn btn-ghost" href="<?= e(url('')) ?>"><?= icon('arrow-l') ?>Zurück zum Katalog</a>
    <?php if (setting('contact_email')): ?><a class="btn btn-ghost" href="mailto:<?= e(setting('contact_email')) ?>?subject=<?= rawurlencode('Anfrage ' . $b['code']) ?>"><?= icon('mail') ?>Frage zur Anfrage</a><?php endif; ?>
  </div>
</section>
<?php view('footer'); ?>
