<?php
require __DIR__ . '/inc/bootstrap.php';

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'fetch';

function form_stamp()
{
    $ts = time();
    return $ts . '.' . hash_hmac('sha256', (string) $ts, cfg('secret', 'stagepool'));
}

function form_stamp_ok($stamp)
{
    $parts = explode('.', (string) $stamp, 2);
    if (count($parts) !== 2 || !hash_equals(hash_hmac('sha256', $parts[0], cfg('secret', 'stagepool')), $parts[1])) {
        return false;
    }
    $age = time() - (int) $parts[0];
    return $age >= 3 && $age <= 86400;
}

/* ================================================================
 * Aktionen
 * ================================================================ */
if (is_post()) {
    csrf_check();
    $action = input('action');
    $return = safe_return(input('return'), 'warenkorb.php');

    if ($action === 'add') {
        $pid = (int) input('id');
        $qty = max(1, (int) input('qty', 1));
        $p = product_find($pid);
        if (!$p) {
            if ($isAjax) {
                json_out(array('ok' => false, 'message' => 'Gerät nicht gefunden.'), 404);
            }
            flash('error', 'Dieses Gerät ist nicht mehr verfügbar.');
            redirect($return);
        }
        if (input('von') !== '' || input('bis') !== '') {
            $err = period_set(input('von'), input('bis'));
            if ($err) {
                if ($isAjax) {
                    json_out(array('ok' => false, 'message' => $err), 422);
                }
                flash('error', $err);
                redirect($return);
            }
        }
        $items = cart_items();
        $newQty = min((int) $p['quantity'], (isset($items[$pid]) ? $items[$pid] : 0) + $qty);
        cart_set($pid, max(1, $newQty));
        $msg = $p['name'] . ' ist in deiner Anfrage.';
        $period = period_get();
        if ($period) {
            $free = avail_free(array($p), $period['from'], $period['to']);
            if ($free[$pid] < $newQty) {
                $msg = $p['name'] . ' ist in deiner Anfrage – aber im gewählten Zeitraum ' . ($free[$pid] > 0 ? 'nur ' . $free[$pid] . '× frei.' : 'leider belegt.');
            }
        }
        if ($isAjax) {
            json_out(array('ok' => true, 'message' => $msg, 'count' => cart_count()));
        }
        flash('success', $msg);
        redirect($return);
    }

    if ($action === 'update') {
        $qtys = isset($_POST['qty']) && is_array($_POST['qty']) ? $_POST['qty'] : array();
        foreach ($qtys as $pid => $qty) {
            cart_set((int) $pid, (int) $qty);
        }
        redirect('warenkorb.php');
    }

    if ($action === 'remove') {
        cart_set((int) input('id'), 0);
        flash('success', 'Gerät entfernt.');
        redirect('warenkorb.php');
    }

    if ($action === 'clear') {
        cart_clear();
        redirect('warenkorb.php');
    }

    if ($action === 'period') {
        $err = period_set(input('von'), input('bis'));
        if ($err) {
            flash('error', $err);
        }
        redirect('warenkorb.php');
    }

    if ($action === 'submit') {
        $form = array(
            'customer_name' => (string) input('name'),
            'email'         => strtolower((string) input('email')),
            'phone'         => (string) input('phone'),
            'organisation'  => (string) input('organisation'),
            'event_type'    => (string) input('event_type'),
            'handover'      => input('handover') === 'delivery' ? 'delivery' : 'pickup',
            'delivery_address' => (string) input('delivery_address'),
            'customer_address' => (string) input('customer_address'),
            'message'       => (string) input('message'),
        );
        $_SESSION['sp_form'] = $form;
        $errors = array();

        if (input('website') !== '' || !form_stamp_ok(input('stamp'))) {
            $errors[] = 'Die Anfrage konnte nicht verarbeitet werden. Bitte lade die Seite neu und versuche es noch einmal.';
        }
        $len = function ($s) {
            return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
        };
        if ($len($form['customer_name']) < 2 || $len($form['customer_name']) > 160) {
            $errors[] = 'Bitte gib deinen Namen an.';
        }
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Bitte gib eine gültige E-Mail-Adresse an.';
        }
        if (!preg_match('/^[0-9 +()\/.-]{5,40}$/', $form['phone'])) {
            $errors[] = 'Bitte gib eine Telefonnummer für Rückfragen an.';
        }
        if ($form['event_type'] !== '' && !in_array($form['event_type'], event_types(), true)) {
            $form['event_type'] = 'Sonstiges';
        }
        if ($form['handover'] === 'delivery' && $len($form['delivery_address']) < 5) {
            $errors[] = 'Bitte gib für die Lieferung eine Adresse an.';
        }
        if ($len($form['message']) > 3000 || $len($form['organisation']) > 160 || $len($form['delivery_address']) > 255 || $len($form['customer_address']) > 255) {
            $errors[] = 'Einige Angaben sind zu lang.';
        }
        if (input('consent') !== '1') {
            $errors[] = 'Bitte bestätige die Mietbedingungen und den Datenschutzhinweis.';
        }
        $period = period_get();
        if (!$period) {
            $errors[] = 'Bitte wähle zuerst deinen Mietzeitraum.';
        } elseif ($perr = validate_period($period['from'], $period['to'])) {
            $errors[] = $perr;
        }
        $details = cart_details();
        if (!$details['lines']) {
            $errors[] = 'Deine Anfrage enthält noch keine Geräte.';
        }
        if ($errors) {
            foreach ($errors as $err) {
                flash('error', $err);
            }
            redirect('warenkorb.php#anfrage');
        }

        $wanted = array();
        foreach ($details['lines'] as $line) {
            $wanted[(int) $line['product']['id']] = $line['qty'];
        }

        $lock = booking_lock();
        $conflicts = avail_check($wanted, $period['from'], $period['to']);
        if ($conflicts) {
            booking_unlock($lock);
            foreach ($conflicts as $c) {
                flash('error', $c['name'] . ': im Zeitraum ' . ($c['free'] > 0 ? 'nur noch ' . $c['free'] . '× frei.' : 'leider nicht mehr frei.'));
            }
            redirect('warenkorb.php');
        }
        $id = booking_create($form, $wanted, $period['from'], $period['to']);
        booking_unlock($lock);

        $booking = booking_load($id);
        mail_booking_created($booking);
        cart_clear();
        unset($_SESSION['sp_form']);
        $_SESSION['sp_last_booking'] = $booking['code'];
        redirect('danke.php', array('code' => $booking['code'], 't' => $booking['token']));
    }
    redirect('warenkorb.php');
}

/* ================================================================
 * Anzeige
 * ================================================================ */
$cart = cart_details();
$period = $cart['period'];
$form = isset($_SESSION['sp_form']) ? $_SESSION['sp_form'] : array();
$fv = function ($k, $d = '') use ($form) {
    return isset($form[$k]) ? $form[$k] : $d;
};
$canSubmit = $cart['lines'] && $period && $cart['conflicts'] === 0;

view('header', array('pageTitle' => 'Deine Anfrage', 'active' => 'warenkorb'));
?>
<section class="wrap page-head">
  <p class="eyebrow">Anfragekorb</p>
  <h1>Dein Event-Paket</h1>
  <p class="lead">Prüfe Zeitraum und Geräte, dann schick uns deine unverbindliche Anfrage. Ab dem Absenden ist alles für dich reserviert.</p>
</section>

<div class="wrap cart-layout">
  <div class="cart-main">
    <section class="panel">
      <div class="panel-head"><h2><?= icon('calendar') ?>Zeitraum</h2>
        <?php if ($period): ?><span class="tag"><?= plural($period['days'], 'Miettag', 'Miettage') ?></span><?php endif; ?>
      </div>
      <form class="period-form" method="post" action="<?= e(url('warenkorb.php')) ?>">
        <?= csrf_field() ?><input type="hidden" name="action" value="period">
        <label class="field"><span>Von</span><input type="date" name="von" value="<?= e($period ? $period['from'] : '') ?>" min="<?= e(earliest_start()) ?>" data-range-start required></label>
        <label class="field"><span>Bis</span><input type="date" name="bis" value="<?= e($period ? $period['to'] : '') ?>" min="<?= e(earliest_start()) ?>" data-range-end required></label>
        <button class="btn btn-ghost" type="submit"><?= $period ? 'Ändern' : 'Übernehmen' ?></button>
      </form>
      <?php if ($period && buffer_days() > 0): ?>
        <p class="muted small"><?= icon('info') ?> Zusätzlich blocken wir <?= date_de(date_add_days($period['from'], -buffer_days())) ?> und <?= date_de(date_add_days($period['to'], buffer_days())) ?> als Übergabe-Puffer (kostenlos).</p>
      <?php elseif (!$period): ?>
        <p class="muted small">Wähle zuerst deinen Zeitraum – dann prüfen wir sofort, ob alles frei ist.</p>
      <?php endif; ?>
    </section>

    <section class="panel">
      <div class="panel-head"><h2><?= icon('bag') ?>Geräte</h2>
        <?php if ($cart['lines']): ?>
          <form method="post" action="<?= e(url('warenkorb.php')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="clear"><button class="link-btn" type="submit">Alle entfernen</button></form>
        <?php endif; ?>
      </div>
      <?php if (!$cart['lines']): ?>
        <div class="empty empty-sm">
          <?= icon('bag') ?>
          <p>Noch nichts ausgewählt.</p>
          <a class="btn btn-primary" href="<?= e(url('')) ?>#katalog">Equipment entdecken</a>
        </div>
      <?php else: ?>
        <form method="post" action="<?= e(url('warenkorb.php')) ?>" class="cart-lines" data-cart-form>
          <?= csrf_field() ?><input type="hidden" name="action" value="update">
          <?php foreach ($cart['lines'] as $line): $p = $line['product']; $max = max(1, (int) $p['quantity']); ?>
            <div class="cart-line<?= $line['ok'] ? '' : ' has-conflict' ?>" style="--accent:<?= e(accent($p)) ?>">
              <a class="cart-thumb" href="<?= e(url('produkt.php', array('id' => $p['id']))) ?>"><?= product_media($p) ?></a>
              <div class="cart-info">
                <a class="cart-name" href="<?= e(url('produkt.php', array('id' => $p['id']))) ?>"><?= e($p['name']) ?></a>
                <span class="muted small"><?= icon('pin') ?><?= e($p['location_name'] ?: 'nach Absprache') ?> · <?= money($p['price_day'], true) ?> / Tag</span>
                <?php if (!$line['ok']): ?>
                  <span class="conflict"><?= icon('alert') ?><?= $line['free'] > 0 ? 'Nur ' . $line['free'] . '× frei im Zeitraum – bitte Anzahl anpassen.' : 'Im Zeitraum belegt – bitte entfernen oder Zeitraum ändern.' ?></span>
                <?php elseif ($period): ?>
                  <span class="ok small"><?= icon('check') ?>verfügbar</span>
                <?php endif; ?>
              </div>
              <div class="cart-qty">
                <?php if ($max > 1): ?>
                  <label class="sr-only" for="qty<?= (int) $p['id'] ?>">Anzahl</label>
                  <select id="qty<?= (int) $p['id'] ?>" name="qty[<?= (int) $p['id'] ?>]" data-autosubmit>
                    <?php for ($i = 1; $i <= $max; $i++): ?><option value="<?= $i ?>"<?= $i === $line['qty'] ? ' selected' : '' ?>><?= $i ?>×</option><?php endfor; ?>
                  </select>
                <?php else: ?>
                  <span class="muted">1×</span><input type="hidden" name="qty[<?= (int) $p['id'] ?>]" value="1">
                <?php endif; ?>
              </div>
              <div class="cart-sum"><?= $period ? money($line['line']) : money($p['price_day'] * $line['qty']) . '<small>/ Tag</small>' ?></div>
              <button class="icon-btn icon-btn-sm" type="submit" form="rm<?= (int) $p['id'] ?>" aria-label="<?= e($p['name']) ?> entfernen"><?= icon('trash') ?></button>
            </div>
          <?php endforeach; ?>
          <noscript><button class="btn btn-ghost btn-sm" type="submit">Mengen aktualisieren</button></noscript>
        </form>
        <?php foreach ($cart['lines'] as $line): ?>
          <form id="rm<?= (int) $line['product']['id'] ?>" method="post" action="<?= e(url('warenkorb.php')) ?>" hidden><?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="id" value="<?= (int) $line['product']['id'] ?>"></form>
        <?php endforeach; ?>
        <a class="link-btn add-more" href="<?= e(url('')) ?>#katalog"><?= icon('plus') ?>Weitere Geräte hinzufügen</a>
      <?php endif; ?>
    </section>

    <?php if ($cart['lines']): ?>
    <section class="panel" id="anfrage">
      <div class="panel-head"><h2><?= icon('user') ?>Deine Angaben</h2></div>
      <form class="request-form" method="post" action="<?= e(url('warenkorb.php')) ?>" data-request-form>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="submit">
        <input type="hidden" name="stamp" value="<?= e(form_stamp()) ?>">
        <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <div class="form-grid">
          <label class="field"><span>Name *</span><input type="text" name="name" value="<?= e($fv('customer_name')) ?>" autocomplete="name" required maxlength="160"></label>
          <label class="field"><span>Verein / Firma</span><input type="text" name="organisation" value="<?= e($fv('organisation')) ?>" autocomplete="organization" maxlength="160"></label>
          <label class="field"><span>E-Mail *</span><input type="email" name="email" value="<?= e($fv('email')) ?>" autocomplete="email" required maxlength="190"></label>
          <label class="field"><span>Telefon *</span><input type="tel" name="phone" value="<?= e($fv('phone')) ?>" autocomplete="tel" required maxlength="40"></label>
          <label class="field field-wide"><span>Rechnungsadresse <small>(optional, für Angebot &amp; Rechnung)</small></span><input type="text" name="customer_address" value="<?= e($fv('customer_address')) ?>" autocomplete="street-address" maxlength="255" placeholder="Straße Nr., PLZ Ort"></label>
          <label class="field field-wide"><span>Anlass</span>
            <select name="event_type">
              <option value="">Bitte wählen</option>
              <?php foreach (event_types() as $t): ?><option<?= $fv('event_type') === $t ? ' selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
            </select>
          </label>
        </div>
        <fieldset class="handover">
          <legend>Übergabe</legend>
          <label class="radio-card"><input type="radio" name="handover" value="pickup"<?= $fv('handover', 'pickup') === 'pickup' ? ' checked' : '' ?>>
            <span><?= icon('handover') ?><b>Selbstabholung</b><small>Standard – am Standort des Geräts</small></span></label>
          <label class="radio-card"><input type="radio" name="handover" value="delivery"<?= $fv('handover') === 'delivery' ? ' checked' : '' ?>>
            <span><?= icon('truck') ?><b>Lieferung anfragen</b><small>nach Absprache, ggf. mit Aufpreis</small></span></label>
        </fieldset>
        <label class="field" data-delivery-field<?= $fv('handover') === 'delivery' ? '' : ' hidden' ?>><span>Lieferadresse</span><input type="text" name="delivery_address" value="<?= e($fv('delivery_address')) ?>" maxlength="255" placeholder="Straße, PLZ Ort der Veranstaltung"></label>
        <label class="field"><span>Nachricht</span><textarea name="message" rows="4" maxlength="3000" placeholder="Was planst du? Wie viele Gäste, drinnen oder draußen, Fragen zur Bedienung …"><?= e($fv('message')) ?></textarea></label>
        <label class="check"><input type="checkbox" name="consent" value="1" required>
          <span>Ich habe die <a href="<?= e(url('info.php')) ?>#bedingungen" target="_blank">Mietbedingungen</a> und den <a href="<?= e(url('datenschutz.php')) ?>" target="_blank">Datenschutzhinweis</a> gelesen. *</span></label>
        <button class="btn btn-primary btn-lg btn-block" type="submit"<?= $canSubmit ? '' : ' disabled' ?>><?= icon('sparkle') ?>Unverbindlich anfragen &amp; reservieren</button>
        <?php if (!$period): ?><p class="form-hint is-warn"><?= icon('alert') ?>Bitte wähle oben zuerst deinen Zeitraum.</p>
        <?php elseif ($cart['conflicts']): ?><p class="form-hint is-warn"><?= icon('alert') ?>Bitte löse zuerst die markierten Konflikte.</p><?php endif; ?>
      </form>
    </section>
    <?php endif; ?>
  </div>

  <aside class="cart-aside">
    <div class="summary">
      <h2>Übersicht</h2>
      <?php if ($period): ?>
        <p class="summary-period"><?= icon('calendar') ?><span><?= e(period_de($period['from'], $period['to'])) ?><br><small><?= plural($period['days'], 'Miettag', 'Miettage') ?></small></span></p>
      <?php endif; ?>
      <dl>
        <div><dt><?= plural(cart_count(), 'Gerät', 'Geräte') ?></dt><dd><?= $period ? money($cart['total']) : money($cart['total']) . ' / Tag' ?></dd></div>
        <?php if ($cart['deposit'] > 0): ?><div><dt>Kaution <small>(bei Abholung)</small></dt><dd><?= money($cart['deposit']) ?></dd></div><?php endif; ?>
        <div><dt>Übergabe-Puffer</dt><dd>kostenlos</dd></div>
      </dl>
      <div class="summary-total"><span>Mietpreis gesamt</span><b><?= $period ? money($cart['total']) : '–' ?></b></div>
      <p class="muted small"><?= e(pricing_hint()) ?> <?= e(setting('price_note')) ?></p>
      <?php if ($cart['locations']): ?>
        <h3>Abholung</h3>
        <ul class="summary-locs">
          <?php foreach ($cart['locations'] as $name => $n): ?><li><?= icon('pin') ?><?= e($name) ?> <span class="muted">(<?= plural($n, 'Gerät', 'Geräte') ?>)</span></li><?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <?php if ($cart['lines'] && $canSubmit): ?><a class="btn btn-primary btn-block summary-cta" href="#anfrage">Weiter zur Anfrage<?= icon('arrow-r') ?></a><?php endif; ?>
    </div>
  </aside>
</div>
<?php view('footer'); ?>
