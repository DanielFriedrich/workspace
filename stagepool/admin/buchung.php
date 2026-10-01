<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require SP_ROOT . '/inc/admin.php';
require_login();

$isNew = input('neu') !== '' && !input('id');
$b = null;
if (!$isNew) {
    $b = booking_load((int) input('id'));
    if (!$b) {
        flash('error', 'Buchung nicht gefunden.');
        redirect('admin/buchungen.php');
    }
}
$statuses = booking_statuses();

/** Liest Positionen aus dem Formular: bestehende (items[pid]) + neue Zeilen (add_pid[]/add_qty[]). */
function posted_items()
{
    $wanted = array();
    if (isset($_POST['items']) && is_array($_POST['items'])) {
        foreach ($_POST['items'] as $pid => $qty) {
            if ((int) $qty > 0 && empty($_POST['remove'][$pid])) {
                $wanted[(int) $pid] = (int) $qty;
            }
        }
    }
    $addPid = isset($_POST['add_pid']) && is_array($_POST['add_pid']) ? $_POST['add_pid'] : array();
    $addQty = isset($_POST['add_qty']) && is_array($_POST['add_qty']) ? $_POST['add_qty'] : array();
    foreach ($addPid as $i => $pid) {
        $pid = (int) $pid;
        $qty = isset($addQty[$i]) ? (int) $addQty[$i] : 1;
        if ($pid > 0 && $qty > 0) {
            $wanted[$pid] = (isset($wanted[$pid]) ? $wanted[$pid] : 0) + $qty;
        }
    }
    return $wanted;
}

$conflicts = array();
if (is_post()) {
    csrf_check();
    $action = input('action');

    /* ---------- Statuswechsel ---------- */
    if ($action === 'status' && $b) {
        $to = (string) input('to');
        if (!isset($statuses[$to])) {
            redirect('admin/buchung.php', array('id' => $b['id']));
        }
        // Beim (Wieder-)Aktivieren Verfügbarkeit prüfen
        if ($statuses[$to]['blocks'] && !$statuses[$b['status']]['blocks'] && input('force') !== '1') {
            $c = avail_check(booking_items_wanted($b), $b['start_date'], $b['end_date'], $b['id']);
            if ($c) {
                foreach ($c as $x) {
                    flash('error', 'Konflikt: ' . $x['name'] . ' – nur ' . $x['free'] . '× frei, benötigt ' . $x['wanted'] . '×.');
                }
                redirect('admin/buchung.php', array('id' => $b['id']));
            }
        }
        if ($to === 'confirmed' && $b['status'] === 'requested' && input('force') !== '1') {
            // Sperrzeiten könnten nachträglich angelegt worden sein
            $c = avail_check(booking_items_wanted($b), $b['start_date'], $b['end_date'], $b['id']);
            if ($c) {
                foreach ($c as $x) {
                    flash('error', 'Achtung, Überschneidung: ' . $x['name'] . ' (frei: ' . $x['free'] . ', benötigt: ' . $x['wanted'] . '). Bitte prüfen oder Buchung anpassen.');
                }
                redirect('admin/buchung.php', array('id' => $b['id']));
            }
        }
        $note = (string) input('note');
        booking_set_status($b['id'], $to, $note);
        $msg = 'Status geändert: ' . status_label($to) . '.';
        if (input('notify') === '1' && $b['email']) {
            $fresh = booking_load($b['id']);
            if (mail_booking_status($fresh, $note)) {
                booking_log($b['id'], 'mail', 'Statusmail: ' . status_label($to));
                $msg .= ' Der Kunde wurde per E-Mail informiert.';
            } else {
                $msg .= ' (Hinweis: Die E-Mail konnte nicht gesendet werden – siehe Einstellungen.)';
            }
        }
        flash('success', $msg);
        redirect('admin/buchung.php', array('id' => $b['id']));
    }

    /* ---------- Notiz ---------- */
    if ($action === 'note' && $b) {
        $note = trim((string) input('admin_note'));
        db_update('bookings', array('admin_note' => $note, 'updated_at' => now()), 'id = ?', array($b['id']));
        flash('success', 'Notiz gespeichert.');
        redirect('admin/buchung.php', array('id' => $b['id']));
    }

    /* ---------- Löschen ---------- */
    if ($action === 'delete' && $b && is_admin()) {
        db_exec('DELETE FROM #__booking_items WHERE booking_id = ?', array($b['id']));
        db_exec('DELETE FROM #__booking_log WHERE booking_id = ?', array($b['id']));
        db_exec('DELETE FROM #__bookings WHERE id = ?', array($b['id']));
        flash('success', 'Buchung ' . $b['code'] . ' wurde gelöscht.');
        redirect('admin/buchungen.php');
    }

    /* ---------- Speichern (neu oder bearbeiten) ---------- */
    if ($action === 'save') {
        $from = parse_date(input('start_date'));
        $to = parse_date(input('end_date'));
        $data = array(
            'customer_name' => (string) input('customer_name'),
            'email' => strtolower((string) input('email')),
            'phone' => (string) input('phone'),
            'organisation' => (string) input('organisation'),
            'event_type' => (string) input('event_type'),
            'handover' => input('handover') === 'delivery' ? 'delivery' : 'pickup',
            'delivery_address' => (string) input('delivery_address'),
            'message' => (string) input('message'),
            'admin_note' => (string) input('admin_note'),
        );
        $wanted = posted_items();
        $status = $isNew ? (string) input('status', 'confirmed') : $b['status'];
        if (!isset($statuses[$status])) {
            $status = 'confirmed';
        }
        $errors = array();
        if ($err = validate_period($from, $to, true)) {
            $errors[] = $err;
        }
        if ($data['customer_name'] === '') {
            $errors[] = 'Bitte einen Namen angeben.';
        }
        if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Die E-Mail-Adresse ist ungültig.';
        }
        if (!$wanted) {
            $errors[] = 'Bitte mindestens ein Gerät eintragen.';
        }
        if (!$errors && $statuses[$status]['blocks'] && input('force') !== '1') {
            $conflicts = avail_check($wanted, $from, $to, $b ? $b['id'] : 0);
            if ($conflicts) {
                $errors[] = 'Es gibt Überschneidungen mit anderen Buchungen oder Sperrzeiten (siehe unten). Bitte anpassen oder „trotzdem speichern“ wählen.';
            }
        }
        if ($errors) {
            foreach ($errors as $err) {
                flash('error', $err);
            }
            $_SESSION['sp_booking_form'] = array('data' => $data, 'from' => input('start_date'), 'to' => input('end_date'), 'wanted' => $wanted, 'status' => $status, 'conflicts' => $conflicts);
            redirect('admin/buchung.php', $isNew ? array('neu' => 1) : array('id' => $b['id']));
        }

        $lock = booking_lock();
        if ($isNew) {
            $id = booking_create($data, $wanted, $from, $to, $status, 'admin');
        } else {
            $id = (int) $b['id'];
            $data['start_date'] = $from;
            $data['end_date'] = $to;
            $data['updated_at'] = now();
            db_update('bookings', $data, 'id = ?', array($id));
            $sums = booking_write_items($id, $wanted, $from, $to);
            db_update('bookings', array('total' => $sums['total'], 'deposit_total' => $sums['deposit']), 'id = ?', array($id));
            booking_log($id, 'updated', 'Daten/Positionen bearbeitet');
        }
        booking_unlock($lock);

        // Preis manuell anpassen (Rabatt, Pauschale …)
        if (input('price_override') === '1') {
            db_update('bookings', array('total' => parse_money(input('total')), 'price_override' => 1), 'id = ?', array($id));
        } else {
            db_update('bookings', array('price_override' => 0), 'id = ?', array($id));
        }
        flash('success', $isNew ? 'Buchung angelegt.' : 'Änderungen gespeichert.');
        redirect('admin/buchung.php', array('id' => $id));
    }
}

/* ================================================================
 * Formularwerte
 * ================================================================ */
$saved = isset($_SESSION['sp_booking_form']) ? $_SESSION['sp_booking_form'] : null;
unset($_SESSION['sp_booking_form']);
if ($saved) {
    $conflicts = $saved['conflicts'];
}
$val = function ($k, $d = '') use ($saved, $b) {
    if ($saved && isset($saved['data'][$k])) {
        return $saved['data'][$k];
    }
    return $b && isset($b[$k]) ? $b[$k] : $d;
};
$from = $saved ? $saved['from'] : ($b ? $b['start_date'] : '');
$to = $saved ? $saved['to'] : ($b ? $b['end_date'] : '');
$wanted = $saved ? $saved['wanted'] : ($b ? booking_items_wanted($b) : array());

$products = catalog_products(false);
$productById = array();
foreach ($products as $p) {
    $productById[(int) $p['id']] = $p;
}
$log = $b ? db_all('SELECT l.*, u.name AS user_name FROM #__booking_log l LEFT JOIN #__users u ON u.id = l.user_id WHERE l.booking_id = ? ORDER BY l.id DESC', array($b['id'])) : array();

function product_options(array $products, $selected = 0)
{
    $h = '<option value="">Gerät wählen …</option>';
    $cat = null;
    foreach ($products as $p) {
        if ($p['category_name'] !== $cat) {
            if ($cat !== null) {
                $h .= '</optgroup>';
            }
            $cat = $p['category_name'];
            $h .= '<optgroup label="' . e($cat ?: 'Ohne Kategorie') . '">';
        }
        $h .= '<option value="' . (int) $p['id'] . '"' . ((int) $selected === (int) $p['id'] ? ' selected' : '') . '>'
            . e($p['name'] . ' (' . (int) $p['quantity'] . '×, ' . money($p['price_day'], true) . '/Tag' . ((int) $p['active'] ? '' : ', inaktiv') . ')') . '</option>';
    }
    return $h . ($cat !== null ? '</optgroup>' : '');
}

admin_header($isNew ? 'Neue Buchung' : 'Buchung ' . $b['code'], 'buchungen');
?>
<a class="link-btn back" href="<?= e(url('admin/buchungen.php')) ?>"><?= icon('arrow-l') ?>Alle Buchungen</a>

<?php if ($b): ?>
<section class="booking-head">
  <div class="booking-head-main">
    <div class="booking-title">
      <span class="code code-lg"><?= e($b['code']) ?></span><?= status_badge($b['status']) ?>
      <?php if ($b['status'] === 'picked_up' && $b['end_date'] < today()): ?><span class="status status-late">Rückgabe überfällig</span><?php endif; ?>
    </div>
    <p class="booking-meta"><?= icon('calendar') ?><b><?= e(period_de($b['start_date'], $b['end_date'])) ?></b> · <?= plural(days_inclusive($b['start_date'], $b['end_date']), 'Tag', 'Tage') ?>
      <?php if (buffer_days()): ?><span class="muted"> · blockiert inkl. Puffer: <?= e(date_de(date_add_days($b['start_date'], -buffer_days()))) ?> – <?= e(date_de(date_add_days($b['end_date'], buffer_days()))) ?></span><?php endif; ?></p>
    <p class="muted small"><?= e($statuses[$b['status']]['hint']) ?></p>
  </div>
  <div class="booking-actions">
    <?php
    $actions = array();
    if ($b['status'] === 'requested') {
        $actions = array(array('confirmed', 'Bestätigen', 'check', 'btn-primary'), array('rejected', 'Ablehnen', 'x', 'btn-ghost'));
    } elseif ($b['status'] === 'confirmed') {
        $actions = array(array('picked_up', 'Ausgabe erfassen', 'handover', 'btn-primary'), array('cancelled', 'Stornieren', 'x', 'btn-ghost'));
    } elseif ($b['status'] === 'picked_up') {
        $actions = array(array('returned', 'Rückgabe erfassen', 'return', 'btn-primary'));
    } else {
        $actions = array(array('requested', 'Wieder öffnen (reservieren)', 'return', 'btn-ghost'));
    }
    foreach ($actions as $a): ?>
      <details class="action-pop">
        <summary class="btn <?= $a[3] ?>"><?= icon($a[2]) ?><?= e($a[1]) ?></summary>
        <form method="post" class="action-form">
          <?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="to" value="<?= e($a[0]) ?>">
          <p><b><?= e($a[1]) ?></b> – neuer Status: <?= status_badge($a[0]) ?></p>
          <?php if ($b['email'] && in_array($a[0], array('confirmed', 'rejected', 'cancelled', 'picked_up', 'returned'), true)): ?>
            <label class="check"><input type="checkbox" name="notify" value="1"<?= in_array($a[0], array('confirmed', 'rejected', 'cancelled'), true) ? ' checked' : '' ?>><span>Kunde per E-Mail informieren</span></label>
            <label class="field"><span>Persönliche Nachricht (optional)</span><textarea name="note" rows="3" placeholder="z. B. Abholung am Freitag ab 17 Uhr, bitte Ausweis mitbringen."></textarea></label>
          <?php else: ?>
            <label class="field"><span>Notiz (optional)</span><input type="text" name="note"></label>
          <?php endif; ?>
          <?php if ($a[0] === 'confirmed' || $a[0] === 'requested'): ?><label class="check"><input type="checkbox" name="force" value="1"><span>Überschneidungen ignorieren</span></label><?php endif; ?>
          <button class="btn btn-primary btn-sm" type="submit">Ausführen</button>
        </form>
      </details>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<div class="admin-cols">
  <form class="card-admin booking-form" method="post" action="<?= e(url('admin/buchung.php', $isNew ? array('neu' => 1) : array('id' => $b['id']))) ?>">
    <?= csrf_field() ?><input type="hidden" name="action" value="save">
    <h2><?= icon('edit') ?><?= $isNew ? 'Buchung anlegen' : 'Bearbeiten' ?></h2>

    <div class="form-grid">
      <label class="field"><span>Von *</span><input type="date" name="start_date" value="<?= e($from) ?>" required data-range-start></label>
      <label class="field"><span>Bis *</span><input type="date" name="end_date" value="<?= e($to) ?>" required data-range-end></label>
      <?php if ($isNew): ?>
        <label class="field"><span>Status</span><select name="status">
          <?php foreach (array('confirmed', 'requested', 'picked_up') as $s): ?><?= opt($s, $saved ? $saved['status'] : 'confirmed', status_label($s)) ?><?php endforeach; ?>
        </select></label>
      <?php endif; ?>
    </div>

    <h3>Geräte</h3>
    <?php if ($conflicts): ?>
      <div class="flash flash-error"><?= icon('alert') ?><span>
        <?php foreach ($conflicts as $c): ?><?= e($c['name']) ?>: frei <?= (int) $c['free'] ?>, benötigt <?= (int) $c['wanted'] ?><br><?php endforeach; ?>
      </span></div>
    <?php endif; ?>
    <table class="table items-table">
      <thead><tr><th>Gerät</th><th class="num">Anzahl</th><th class="num">Preis/Tag</th><th class="num">Summe</th><th></th></tr></thead>
      <tbody>
      <?php
      $itemsByPid = array();
      if ($b) {
          foreach ($b['items'] as $it) {
              $itemsByPid[(int) $it['product_id']] = $it;
          }
      }
      foreach ($wanted as $pid => $qty):
          $p = isset($productById[$pid]) ? $productById[$pid] : null;
          $it = isset($itemsByPid[$pid]) ? $itemsByPid[$pid] : null;
          $name = $p ? $p['name'] : ($it ? $it['product_name'] : 'Gerät #' . $pid);
          $price = $it ? $it['price_day'] : ($p ? $p['price_day'] : 0);
          $max = $p ? max((int) $p['quantity'], $qty) : $qty;
      ?>
        <tr<?= isset($conflicts[$pid]) ? ' class="is-conflict"' : '' ?>>
          <td><?= e($name) ?><?= $p && $p['location_name'] ? '<br><small class="muted">' . e($p['location_name']) . '</small>' : '' ?></td>
          <td class="num"><input class="input-qty" type="number" name="items[<?= (int) $pid ?>]" value="<?= (int) $qty ?>" min="0" max="<?= (int) $max ?>"></td>
          <td class="num"><?= money($price) ?></td>
          <td class="num"><?= $it ? money($it['line_total']) : '–' ?></td>
          <td><label class="check check-sm" title="Entfernen"><input type="checkbox" name="remove[<?= (int) $pid ?>]" value="1"><?= icon('trash') ?></label></td>
        </tr>
      <?php endforeach; ?>
      <?php for ($i = 0; $i < ($wanted ? 2 : 4); $i++): ?>
        <tr class="add-row">
          <td><select name="add_pid[]"><?= product_options($products) ?></select></td>
          <td class="num"><input class="input-qty" type="number" name="add_qty[]" value="1" min="1" max="99"></td>
          <td colspan="3" class="muted small">neu</td>
        </tr>
      <?php endfor; ?>
      </tbody>
    </table>

    <div class="price-box">
      <?php if ($b): ?>
        <p>Gesamtpreis: <b><?= money($b['total']) ?></b><?= (int) $b['price_override'] ? ' <span class="tag">manuell angepasst</span>' : '' ?> · Kaution: <?= money($b['deposit_total']) ?></p>
      <?php endif; ?>
      <label class="check"><input type="checkbox" name="price_override" value="1"<?= $b && (int) $b['price_override'] ? ' checked' : '' ?> data-toggle="#total-field"><span>Gesamtpreis manuell festlegen (Rabatt, Pauschale)</span></label>
      <label class="field" id="total-field"<?= $b && (int) $b['price_override'] ? '' : ' hidden' ?>><span>Gesamtpreis in €</span><input type="text" name="total" inputmode="decimal" value="<?= e($b ? number_format((float) $b['total'], 2, ',', '') : '') ?>"></label>
    </div>

    <h3>Kunde</h3>
    <div class="form-grid">
      <label class="field"><span>Name *</span><input type="text" name="customer_name" value="<?= e($val('customer_name')) ?>" required></label>
      <label class="field"><span>Verein / Firma</span><input type="text" name="organisation" value="<?= e($val('organisation')) ?>"></label>
      <label class="field"><span>E-Mail</span><input type="email" name="email" value="<?= e($val('email')) ?>"></label>
      <label class="field"><span>Telefon</span><input type="text" name="phone" value="<?= e($val('phone')) ?>"></label>
      <label class="field"><span>Anlass</span><select name="event_type"><option value="">–</option>
        <?php foreach (event_types() as $t): ?><?= opt($t, $val('event_type'), $t) ?><?php endforeach; ?></select></label>
      <label class="field"><span>Übergabe</span><select name="handover"><?= opt('pickup', $val('handover', 'pickup'), 'Selbstabholung') ?><?= opt('delivery', $val('handover'), 'Lieferung') ?></select></label>
      <label class="field field-wide"><span>Lieferadresse</span><input type="text" name="delivery_address" value="<?= e($val('delivery_address')) ?>"></label>
      <label class="field field-wide"><span>Nachricht des Kunden</span><textarea name="message" rows="3"><?= e($val('message')) ?></textarea></label>
      <label class="field field-wide"><span>Interne Notiz</span><textarea name="admin_note" rows="2" placeholder="Nur für das Team sichtbar"><?= e($val('admin_note')) ?></textarea></label>
    </div>
    <div class="form-actions">
      <?php if ($conflicts): ?><label class="check"><input type="checkbox" name="force" value="1"><span>Trotz Überschneidung speichern</span></label><?php endif; ?>
      <button class="btn btn-primary" type="submit"><?= icon('check') ?><?= $isNew ? 'Buchung anlegen' : 'Speichern' ?></button>
    </div>
  </form>

  <?php if ($b): ?>
  <aside class="admin-aside">
    <section class="card-admin">
      <h2><?= icon('user') ?>Kontakt</h2>
      <p><b><?= e($b['customer_name']) ?></b><?= $b['organisation'] ? '<br>' . e($b['organisation']) : '' ?></p>
      <p class="contact-links">
        <?php if ($b['email']): ?><a class="btn btn-ghost btn-sm" href="mailto:<?= e($b['email']) ?>?subject=<?= rawurlencode('Deine Anfrage ' . $b['code']) ?>"><?= icon('mail') ?><?= e($b['email']) ?></a><?php endif; ?>
        <?php if ($b['phone']): ?><a class="btn btn-ghost btn-sm" href="tel:<?= e(preg_replace('/[^\d+]/', '', $b['phone'])) ?>"><?= icon('phone') ?><?= e($b['phone']) ?></a><?php endif; ?>
      </p>
      <p class="muted small"><?= $b['handover'] === 'delivery' ? icon('truck') . 'Lieferung: ' . e($b['delivery_address']) : icon('handover') . 'Selbstabholung' ?></p>
      <?php if ($b['message']): ?><blockquote class="quote"><?= nl2br(e($b['message'])) ?></blockquote><?php endif; ?>
      <label class="field"><span>Kundenlink (Status &amp; Storno)</span><input type="text" readonly value="<?= e(booking_status_link($b)) ?>" data-select-all></label>
    </section>

    <section class="card-admin">
      <h2><?= icon('clock') ?>Verlauf</h2>
      <ol class="log">
        <?php foreach ($log as $l): ?>
          <li><b><?= e(log_action_label($l['action'])) ?></b><?= $l['note'] ? '<span>' . e($l['note']) . '</span>' : '' ?><small class="muted"><?= e(datetime_de($l['created_at'])) ?><?= $l['user_name'] ? ' · ' . e($l['user_name']) : '' ?></small></li>
        <?php endforeach; ?>
      </ol>
    </section>

    <?php if (is_admin()): ?>
    <form class="card-admin danger-zone" method="post" data-confirm="Buchung <?= e($b['code']) ?> endgültig löschen? Tipp: Stornieren behält den Verlauf.">
      <?= csrf_field() ?><input type="hidden" name="action" value="delete">
      <button class="btn btn-danger btn-sm" type="submit"><?= icon('trash') ?>Buchung löschen</button>
    </form>
    <?php endif; ?>
  </aside>
  <?php endif; ?>
</div>
<?php admin_footer(); ?>
