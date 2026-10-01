<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require SP_ROOT . '/inc/admin.php';
require_login();

$d = doc_load((int) input('id'));
if (!$d) {
    flash('error', 'Beleg nicht gefunden.');
    redirect('admin/belege.php');
}
if (input('pdf') !== '') {
    doc_output($d, input('download') === '');
}

if (is_post()) {
    csrf_check();
    $action = input('action');
    if ($action === 'send') {
        $to = strtolower(trim((string) input('email')));
        if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
            db_update('documents', array('customer_email' => $to), 'id = ?', array($d['id']));
            $d['customer_email'] = $to;
        }
        $ok = doc_send($d, (string) input('message'));
        flash($ok ? 'success' : 'error', $ok ? 'E-Mail an ' . $d['customer_email'] . ' verschickt.' : 'Die E-Mail konnte nicht gesendet werden. Adresse und E-Mail-Einstellungen prüfen.');
    } elseif ($action === 'paid' && $d['type'] === 'invoice') {
        doc_mark_paid($d, input('paid_at'), (string) input('payment_method'));
        flash('success', 'Als bezahlt markiert und in der Buchhaltung als Einnahme verbucht.');
    } elseif (in_array($action, array('accepted', 'cancelled', 'open'), true)) {
        doc_set_status($d, $action);
        if ($action === 'accepted' && $d['booking_id']) {
            $b = db_one('SELECT status FROM #__bookings WHERE id = ?', array($d['booking_id']));
            if ($b && in_array($b['status'], array('requested', 'offered'), true)) {
                booking_set_status($d['booking_id'], 'confirmed', 'Angebot ' . $d['number'] . ' angenommen');
            }
        }
        flash('success', 'Status geändert.');
    } elseif ($action === 'update') {
        $note = (string) input('note');
        $addr = (string) input('customer_address');
        db_update('documents', array('note' => $note, 'customer_address' => $addr), 'id = ?', array($d['id']));
        flash('success', 'Gespeichert. Das PDF wurde aktualisiert.');
    }
    redirect('admin/beleg.php', array('id' => $d['id']));
}

$booking = $d['booking_id'] ? db_one('SELECT id, code, status FROM #__bookings WHERE id = ?', array($d['booking_id'])) : null;
$tx = db_one('SELECT * FROM #__transactions WHERE document_id = ?', array($d['id']));
$isInvoice = $d['type'] === 'invoice';

admin_header(doc_title($d), 'belege');
?>
<a class="link-btn back" href="<?= e(url('admin/belege.php')) ?>"><?= icon('arrow-l') ?>Alle Belege</a>
<section class="booking-head">
  <div class="booking-head-main">
    <div class="booking-title"><span class="code code-lg"><?= e($d['number']) ?></span><?= doc_status_badge($d['status']) ?></div>
    <p class="booking-meta"><b><?= e(doc_types()[$d['type']]) ?> über <?= money($d['total']) ?></b> · <?= e(strtok($d['customer_name'], "\n")) ?> · vom <?= e(date_de($d['doc_date'])) ?> · <?= $isInvoice ? 'fällig' : 'gültig bis' ?> <?= e(date_de($d['due_date'])) ?></p>
    <p class="muted small">
      <?php if ($booking): ?>Buchung <a class="link-btn" href="<?= e(url('admin/buchung.php', array('id' => $booking['id']))) ?>"><?= e($booking['code']) ?></a> · <?php endif; ?>
      <?= $d['sent_at'] ? 'versendet am ' . e(datetime_de($d['sent_at'])) : 'noch nicht versendet' ?>
      <?= $d['paid_at'] ? ' · bezahlt am ' . e(date_de($d['paid_at'])) : '' ?>
    </p>
  </div>
  <div class="booking-actions">
    <a class="btn btn-primary" href="<?= e(url('admin/beleg.php', array('id' => $d['id'], 'pdf' => 1, 'download' => 1))) ?>"><?= icon('upload') ?>PDF herunterladen</a>
    <details class="action-pop">
      <summary class="btn btn-ghost"><?= icon('mail') ?>Per E-Mail senden</summary>
      <form method="post" class="action-form">
        <?= csrf_field() ?><input type="hidden" name="action" value="send">
        <label class="field"><span>Empfänger</span><input type="email" name="email" value="<?= e($d['customer_email']) ?>" required></label>
        <label class="field"><span>Nachricht (optional)</span><textarea name="message" rows="3"></textarea></label>
        <button class="btn btn-primary btn-sm" type="submit">Senden</button>
      </form>
    </details>
    <?php if ($isInvoice && $d['status'] !== 'paid' && $d['status'] !== 'cancelled'): ?>
      <details class="action-pop">
        <summary class="btn btn-ghost"><?= icon('check') ?>Zahlung erfassen</summary>
        <form method="post" class="action-form">
          <?= csrf_field() ?><input type="hidden" name="action" value="paid">
          <label class="field"><span>Bezahlt am</span><input type="date" name="paid_at" value="<?= e(today()) ?>"></label>
          <label class="field"><span>Zahlart</span><select name="payment_method"><?php foreach (payment_methods() as $m): ?><option><?= e($m) ?></option><?php endforeach; ?></select></label>
          <p class="muted small">Wird automatisch als Einnahme in der Buchhaltung verbucht.</p>
          <button class="btn btn-primary btn-sm" type="submit">Als bezahlt markieren</button>
        </form>
      </details>
    <?php endif; ?>
    <?php if (!$isInvoice && in_array($d['status'], array('open', 'sent'), true)): ?>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="accepted"><button class="btn btn-ghost" type="submit"><?= icon('check') ?>Angenommen</button></form>
    <?php endif; ?>
    <?php if ($d['status'] !== 'cancelled'): ?>
      <form method="post" data-confirm="<?= $isInvoice ? 'Rechnung stornieren? Die Nummer bleibt vergeben, eine verbuchte Zahlung wird aus der Buchhaltung entfernt.' : 'Angebot zurückziehen?' ?>"><?= csrf_field() ?><input type="hidden" name="action" value="cancelled"><button class="btn btn-ghost" type="submit"><?= icon('ban') ?><?= $isInvoice ? 'Stornieren' : 'Zurückziehen' ?></button></form>
    <?php else: ?>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="open"><button class="btn btn-ghost" type="submit"><?= icon('return') ?>Wieder aktivieren</button></form>
    <?php endif; ?>
  </div>
</section>

<div class="admin-cols">
  <div class="card-admin pdf-preview">
    <iframe src="<?= e(url('admin/beleg.php', array('id' => $d['id'], 'pdf' => 1))) ?>#view=FitH" title="PDF-Vorschau"></iframe>
  </div>
  <aside class="admin-aside">
    <form class="card-admin" method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="update">
      <h2><?= icon('edit') ?>Angaben im PDF</h2>
      <label class="field"><span>Empfänger</span><textarea rows="2" disabled><?= e($d['customer_name']) ?></textarea></label>
      <label class="field"><span>Adresse (Komma = neue Zeile)</span><input type="text" name="customer_address" value="<?= e($d['customer_address']) ?>"></label>
      <label class="field"><span>Zusatztext</span><textarea name="note" rows="3"><?= e($d['note']) ?></textarea></label>
      <p class="muted small">Positionen und Beträge sind fest. Für Änderungen die Buchung anpassen und einen neuen Beleg erstellen<?= $isInvoice ? ' (diese Rechnung vorher stornieren)' : '' ?>.</p>
      <button class="btn btn-ghost btn-sm" type="submit">Speichern</button>
    </form>
    <?php if ($tx): ?>
      <section class="card-admin">
        <h2><?= icon('check') ?>Buchhaltung</h2>
        <p>Einnahme vom <?= e(date_de($tx['tx_date'])) ?> über <b><?= money($tx['amount']) ?></b> (<?= e($tx['payment_method']) ?>)</p>
        <a class="link-btn" href="<?= e(url('admin/buchhaltung.php', array('id' => $tx['id']))) ?>">In der Buchhaltung ansehen</a>
      </section>
    <?php endif; ?>
  </aside>
</div>
<?php admin_footer(); ?>
