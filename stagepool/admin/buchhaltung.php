<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require SP_ROOT . '/inc/admin.php';
require_login();

$year = (int) input('jahr', date('Y'));
if ($year < 2000 || $year > 2100) {
    $year = (int) date('Y');
}

/* ---------- Beleg-Datei anzeigen ---------- */
if (input('datei') !== '') {
    $t = db_one('SELECT receipt FROM #__transactions WHERE id = ?', array((int) input('datei')));
    $path = $t ? receipt_path($t['receipt']) : null;
    if (!$path || !is_file($path)) {
        http_response_code(404);
        exit('Beleg nicht gefunden.');
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $types = array('pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp');
    header('Content-Type: ' . (isset($types[$ext]) ? $types[$ext] : 'application/octet-stream'));
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: inline; filename="Beleg-' . (int) input('datei') . '.' . $ext . '"');
    header('Cache-Control: private, no-store');
    readfile($path);
    exit;
}

/* ---------- CSV-Export (z. B. für Steuerberatung) ---------- */
if (input('export') === 'csv') {
    $rows = db_all('SELECT t.*, u.name AS paid_by_name, d.number AS doc_number FROM #__transactions t
                      LEFT JOIN #__users u ON u.id = t.paid_by LEFT JOIN #__documents d ON d.id = t.document_id
                     WHERE t.tx_date >= ? AND t.tx_date <= ? ORDER BY t.tx_date, t.id', array($year . '-01-01', $year . '-12-31'));
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="buchhaltung-' . $year . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM, damit Excel Umlaute erkennt
    fputcsv($out, array('Datum', 'Art', 'Kategorie', 'Beschreibung', 'Partner', 'Betrag', 'davon USt', 'Zahlart', 'Bezahlt/ausgelegt von', 'Rechnung', 'Beleg vorhanden', 'Notiz'), ';');
    foreach ($rows as $r) {
        $sign = $r['type'] === 'expense' ? -1 : 1;
        fputcsv($out, array(
            date_de($r['tx_date']), $r['type'] === 'income' ? 'Einnahme' : 'Ausgabe', $r['category'], $r['description'], $r['counterparty'],
            number_format($sign * (float) $r['amount'], 2, ',', ''), number_format((float) $r['vat_amount'], 2, ',', ''),
            $r['payment_method'], $r['paid_by_name'], $r['doc_number'], $r['receipt'] ? 'ja' : 'nein', $r['note'],
        ), ';');
    }
    fclose($out);
    exit;
}

/* ---------- Speichern / Löschen ---------- */
if (is_post()) {
    csrf_check();
    $action = input('action');
    $id = (int) input('id');
    $existing = $id ? db_one('SELECT * FROM #__transactions WHERE id = ?', array($id)) : null;
    if ($action === 'delete' && $existing) {
        if ($existing['document_id']) {
            flash('error', 'Diese Einnahme gehört zu einer Rechnung. Bitte dort die Zahlung zurücknehmen (Rechnung „wieder aktivieren“ oder stornieren).');
        } else {
            receipt_delete($existing['receipt']);
            db_exec('DELETE FROM #__transactions WHERE id = ?', array($id));
            flash('success', 'Eintrag gelöscht.');
        }
        redirect('admin/buchhaltung.php', array('jahr' => $year));
    }
    if ($action === 'save') {
        $type = input('type') === 'income' ? 'income' : 'expense';
        $date = parse_date(input('tx_date'));
        $amount = abs(parse_money(input('amount')));
        $data = array(
            'type' => $type, 'tx_date' => $date ?: today(), 'category' => (string) input('category'),
            'description' => (string) input('description'), 'counterparty' => (string) input('counterparty'),
            'amount' => $amount, 'vat_amount' => abs(parse_money(input('vat_amount'))),
            'payment_method' => (string) input('payment_method'), 'paid_by' => (int) input('paid_by') ?: null,
            'product_id' => (int) input('product_id') ?: null, 'note' => (string) input('note'), 'updated_at' => now(),
        );
        $isLinked = $existing && $existing['document_id'];
        if (!$date || (!$isLinked && ($amount <= 0 || $data['description'] === ''))) {
            flash('error', 'Bitte Datum, Betrag und Beschreibung angeben.');
            redirect('admin/buchhaltung.php', array('jahr' => $year, 'id' => $id ?: '', 'neu' => $id ? '' : $type));
        }
        if ($isLinked) {
            // Verknüpfte Rechnungszahlungen: nur Zusatzinfos änderbar
            $data = array('category' => $data['category'], 'note' => $data['note'], 'payment_method' => $data['payment_method'], 'tx_date' => $data['tx_date'], 'updated_at' => now());
        }
        if ($existing) {
            db_update('transactions', $data, 'id = ?', array($id));
        } else {
            $data['created_at'] = now();
            $data['created_by'] = (int) current_user()['id'];
            $data['receipt'] = '';
            $data['document_id'] = null;
            $data['booking_id'] = null;
            $id = db_insert('transactions', $data);
        }
        $file = receipt_upload('receipt');
        if ($file) {
            if ($existing) {
                receipt_delete($existing['receipt']);
            }
            db_update('transactions', array('receipt' => $file), 'id = ?', array($id));
        } elseif (input('remove_receipt') === '1' && $existing) {
            receipt_delete($existing['receipt']);
            db_update('transactions', array('receipt' => ''), 'id = ?', array($id));
        }
        flash('success', ($type === 'income' ? 'Einnahme' : 'Ausgabe') . ' gespeichert.');
        redirect('admin/buchhaltung.php', array('jahr' => substr($data['tx_date'] ?: $date, 0, 4)));
    }
}

/* ---------- Daten ---------- */
$sum = accounting_summary($year);
$fType = (string) input('art');
$fCat = (string) input('kategorie');
$fMonth = (int) input('monat');
$where = array('t.tx_date >= ? AND t.tx_date <= ?');
$params = array($year . '-01-01', $year . '-12-31');
if (isset(tx_types()[$fType])) {
    $where[] = 't.type = ?';
    $params[] = $fType;
}
if ($fCat !== '') {
    $where[] = 't.category = ?';
    $params[] = $fCat;
}
if ($fMonth >= 1 && $fMonth <= 12) {
    $where[] = 't.tx_date >= ? AND t.tx_date <= ?';
    $params[] = sprintf('%04d-%02d-01', $year, $fMonth);
    $params[] = sprintf('%04d-%02d-31', $year, $fMonth);
}
$rows = db_all('SELECT t.*, u.name AS paid_by_name, d.number AS doc_number, p.name AS product_name FROM #__transactions t
                  LEFT JOIN #__users u ON u.id = t.paid_by LEFT JOIN #__documents d ON d.id = t.document_id LEFT JOIN #__products p ON p.id = t.product_id
                 WHERE ' . implode(' AND ', $where) . ' ORDER BY t.tx_date DESC, t.id DESC', $params);

$edit = input('id') !== '' ? db_one('SELECT * FROM #__transactions WHERE id = ?', array((int) input('id'))) : null;
$formType = $edit ? $edit['type'] : (input('neu') === 'income' ? 'income' : 'expense');
$ev = function ($k, $d = '') use ($edit) {
    return $edit && isset($edit[$k]) ? $edit[$k] : $d;
};
$allCats = array_values(array_unique(array_merge(tx_categories('expense'), tx_categories('income'))));
$maxMonth = 1;
foreach ($sum['months'] as $m) {
    $maxMonth = max($maxMonth, $m['income'], $m['expense']);
}
$result = $sum['sum']['income'] - $sum['sum']['expense'];
$base = array('jahr' => $year);

admin_header('Buchhaltung', 'buchhaltung');
?>
<div class="toolbar-admin">
  <form class="filters" method="get">
    <select name="jahr" data-autosubmit aria-label="Jahr"><?php for ($y = (int) date('Y') + 1; $y >= (int) date('Y') - 6; $y--): ?><?= opt($y, $year, 'Jahr ' . $y) ?><?php endfor; ?></select>
  </form>
  <div class="filters">
    <a class="btn btn-ghost btn-sm" href="<?= e(url('admin/buchhaltung.php', array('jahr' => $year, 'export' => 'csv'))) ?>"><?= icon('upload') ?>CSV-Export <?= $year ?></a>
    <a class="btn btn-ghost btn-sm" href="<?= e(url('admin/buchhaltung.php', array('jahr' => $year, 'neu' => 'income'))) ?>#eintrag"><?= icon('plus') ?>Einnahme</a>
    <a class="btn btn-primary btn-sm" href="<?= e(url('admin/buchhaltung.php', array('jahr' => $year, 'neu' => 'expense'))) ?>#eintrag"><?= icon('plus') ?>Ausgabe erfassen</a>
  </div>
</div>

<div class="kpis">
  <div class="kpi"><span>Einnahmen <?= $year ?></span><b><?= money($sum['sum']['income'], true) ?></b><i class="kpi-dot is-income"></i></div>
  <div class="kpi"><span>Ausgaben <?= $year ?></span><b><?= money($sum['sum']['expense'], true) ?></b><i class="kpi-dot is-expense"></i></div>
  <div class="kpi<?= $result >= 0 ? ' kpi-good' : ' kpi-bad' ?>"><span>Ergebnis (Überschuss)</span><b><?= ($result > 0 ? '+' : '') . money($result, true) ?></b><?= icon($result >= 0 ? 'check' : 'alert') ?></div>
  <a class="kpi" href="<?= e(url('admin/belege.php', array('status' => 'unbezahlt'))) ?>"><span>Offene Rechnungen</span><b><?= money($sum['open_amount'], true) ?></b><?= icon('clock') ?><?php if ($sum['open_invoices']): ?><em><?= plural($sum['open_invoices'], 'Rechnung', 'Rechnungen') ?></em><?php endif; ?></a>
</div>

<div class="dash-grid acc-grid">
  <section class="card-admin acc-chart-card">
    <div class="card-admin-head">
      <h2><?= icon('timeline') ?>Einnahmen &amp; Ausgaben pro Monat</h2>
      <ul class="chart-legend" aria-label="Legende"><li><i class="is-income"></i>Einnahmen</li><li><i class="is-expense"></i>Ausgaben</li></ul>
    </div>
    <div class="bar-chart" role="img" aria-label="Säulendiagramm der Einnahmen und Ausgaben pro Monat <?= $year ?>; Werte in der Tabelle darunter">
      <?php foreach ($sum['months'] as $m => $v): ?>
        <a class="bar-col" href="<?= e(url('admin/buchhaltung.php', array_merge($base, array('monat' => $m)))) ?>#liste" tabindex="0">
          <span class="bars">
            <span class="bar is-income" style="height:<?= round($v['income'] / $maxMonth * 100, 1) ?>%"></span>
            <span class="bar is-expense" style="height:<?= round($v['expense'] / $maxMonth * 100, 1) ?>%"></span>
          </span>
          <span class="bar-label"><?= e(function_exists('mb_substr') ? mb_substr(month_name($m), 0, 3, 'UTF-8') : substr(month_name($m), 0, 3)) ?></span>
          <span class="bar-tip"><b><?= e(month_name($m)) ?> <?= $year ?></b><span><i class="is-income"></i>Einnahmen <?= money($v['income'], true) ?></span><span><i class="is-expense"></i>Ausgaben <?= money($v['expense'], true) ?></span><span>Ergebnis <?= money($v['income'] - $v['expense'], true) ?></span></span>
        </a>
      <?php endforeach; ?>
    </div>
    <details class="table-toggle">
      <summary>Als Tabelle anzeigen</summary>
      <table class="table">
        <thead><tr><th>Monat</th><th class="num">Einnahmen</th><th class="num">Ausgaben</th><th class="num">Ergebnis</th></tr></thead>
        <tbody><?php foreach ($sum['months'] as $m => $v): ?><tr><td><?= e(month_name($m)) ?></td><td class="num"><?= money($v['income']) ?></td><td class="num"><?= money($v['expense']) ?></td><td class="num"><?= money($v['income'] - $v['expense']) ?></td></tr><?php endforeach; ?></tbody>
      </table>
    </details>
  </section>

  <section class="card-admin">
    <h2><?= icon('tag') ?>Ausgaben nach Kategorie</h2>
    <?php if (!$sum['cats']['expense']): ?><p class="muted empty-line">Noch keine Ausgaben in <?= $year ?>.</p><?php else: $maxCat = max($sum['cats']['expense']); ?>
      <ul class="hbars">
        <?php foreach ($sum['cats']['expense'] as $c => $v): ?>
          <li><a href="<?= e(url('admin/buchhaltung.php', array_merge($base, array('kategorie' => $c, 'art' => 'expense')))) ?>#liste"><span class="hbar-label"><?= e($c) ?></span><span class="hbar-val"><?= money($v, true) ?></span><span class="hbar"><span style="width:<?= round($v / $maxCat * 100, 1) ?>%"></span></span></a></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <?php if ($sum['persons']): ?>
      <h3>Ausgelegt von</h3>
      <ul class="person-list">
        <?php foreach ($sum['persons'] as $name => $v): ?><li><span><?= icon('user') ?><?= e($name) ?></span><b><?= money($v) ?></b></li><?php endforeach; ?>
      </ul>
      <p class="muted small">Ausgaben, die eine Person aus eigener Tasche bezahlt hat – hilfreich für die interne Abrechnung.</p>
    <?php endif; ?>
  </section>
</div>

<?php $owners = owner_revenue($year); if ($owners): $ownerSum = 0; foreach ($owners as $o) { $ownerSum += $o['total']; } ?>
<section class="card-admin">
  <div class="card-admin-head"><h2><?= icon('users') ?>Mietumsatz nach Eigentümer <?= $year ?></h2></div>
  <p class="muted small">Aus ausgegebenen und zurückgegebenen Buchungen (Mietbeginn <?= $year ?>), aufgeteilt nach den zugeteilten Exemplaren. Rabatte werden anteilig verrechnet. Grundlage für die interne Abrechnung, unabhängig davon, ob schon bezahlt wurde.</p>
  <div class="table-wrap table-wrap-flat">
  <table class="table">
    <thead><tr><th>Eigentümer</th><th class="num">Buchungen</th><th class="num">davon intern</th><th class="num">Mietumsatz</th><th class="num">Anteil</th></tr></thead>
    <tbody>
      <?php foreach ($owners as $name => $o): ?>
        <tr><td><?= icon('user') ?> <?= e($name) ?></td><td class="num"><?= count($o['bookings']) ?></td><td class="num"><?= money($o['internal']) ?></td><td class="num"><b><?= money($o['total']) ?></b></td><td class="num"><?= $ownerSum > 0 ? round($o['total'] / $ownerSum * 100) : 0 ?> %</td></tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot><tr><th colspan="3">Gesamt</th><th class="num"><?= money($ownerSum) ?></th><th></th></tr></tfoot>
  </table>
  </div>
</section>
<?php endif; ?>

<div class="admin-cols">
  <section class="card-admin" id="liste">
    <div class="card-admin-head">
      <h2><?= icon('list') ?>Buchungen <?= $fMonth ? e(month_name($fMonth)) . ' ' : '' ?><?= $year ?></h2>
      <form class="filters" method="get">
        <input type="hidden" name="jahr" value="<?= $year ?>">
        <select name="art" data-autosubmit><option value="">Alle</option><?= opt('income', $fType, 'Einnahmen') ?><?= opt('expense', $fType, 'Ausgaben') ?></select>
        <select name="monat" data-autosubmit><option value="">Ganzes Jahr</option><?php for ($m = 1; $m <= 12; $m++): ?><?= opt($m, $fMonth ?: '', month_name($m)) ?><?php endfor; ?></select>
        <select name="kategorie" data-autosubmit><option value="">Alle Kategorien</option><?php foreach ($allCats as $c): ?><?= opt($c, $fCat, $c) ?><?php endforeach; ?></select>
      </form>
    </div>
    <div class="table-wrap table-wrap-flat">
    <table class="table table-admin">
      <thead><tr><th>Datum</th><th>Kategorie</th><th>Beschreibung</th><th>Bezahlt von</th><th class="num">Betrag</th></tr></thead>
      <tbody>
      <?php if (!$rows): ?><tr><td colspan="5" class="muted">Keine Einträge. Bezahlte Rechnungen erscheinen hier automatisch, Ausgaben erfasst du rechts.</td></tr><?php endif; ?>
      <?php $listSum = 0; foreach ($rows as $r): $sign = $r['type'] === 'income' ? 1 : -1; $listSum += $sign * (float) $r['amount']; ?>
        <tr class="row-link" data-href="<?= e(url('admin/buchhaltung.php', array('jahr' => $year, 'id' => $r['id']))) ?>#eintrag">
          <td><?= e(date_de($r['tx_date'])) ?></td>
          <td><span class="tx-type <?= $r['type'] === 'income' ? 'is-income' : 'is-expense' ?>"><?= $r['type'] === 'income' ? 'Einnahme' : 'Ausgabe' ?></span><br><small class="muted"><?= e($r['category']) ?></small></td>
          <td><b><?= e($r['description']) ?></b><?= $r['counterparty'] ? '<br><small class="muted">' . e($r['counterparty']) . '</small>' : '' ?><?= $r['product_name'] ? '<br><small class="muted">' . icon('box') . e($r['product_name']) . '</small>' : '' ?>
            <span class="tx-links">
            <?php if ($r['receipt']): ?><a class="icon-btn icon-btn-sm" href="<?= e(url('admin/buchhaltung.php', array('datei' => $r['id']))) ?>" target="_blank" title="Beleg ansehen" aria-label="Beleg ansehen"><?= icon('eye') ?></a><?php endif; ?>
            <?php if ($r['document_id']): ?><a class="icon-btn icon-btn-sm" href="<?= e(url('admin/beleg.php', array('id' => $r['document_id']))) ?>" title="Rechnung <?= e($r['doc_number']) ?>" aria-label="Rechnung ansehen"><?= icon('tag') ?></a><?php endif; ?>
            </span>
          </td>
          <td><?= e($r['paid_by_name'] ?: $r['payment_method']) ?></td>
          <td class="num"><b class="<?= $sign > 0 ? 'amount-in' : 'amount-out' ?>"><?= ($sign > 0 ? '+' : '−') . money($r['amount']) ?></b></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <?php if ($rows): ?><tfoot><tr><th colspan="4">Summe der Auswahl</th><th class="num"><?= money($listSum) ?></th></tr></tfoot><?php endif; ?>
    </table>
    </div>
  </section>

  <aside class="admin-aside">
    <form class="card-admin" id="eintrag" method="post" enctype="multipart/form-data">
      <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
      <h2><?= icon($edit ? 'edit' : 'plus') ?><?= $edit ? 'Eintrag bearbeiten' : ($formType === 'income' ? 'Einnahme erfassen' : 'Ausgabe erfassen') ?></h2>
      <?php $locked = $edit && $edit['document_id']; ?>
      <?php if ($locked): ?><p class="muted small">Zahlung zu Rechnung <?= e(db_value('SELECT number FROM #__documents WHERE id = ?', array($edit['document_id']))) ?> – Betrag und Beschreibung kommen aus der Rechnung.</p><?php endif; ?>
      <fieldset class="segmented" data-tx-type>
        <label><input type="radio" name="type" value="expense"<?= $formType === 'expense' ? ' checked' : '' ?><?= $locked ? ' disabled' : '' ?>><span>Ausgabe</span></label>
        <label><input type="radio" name="type" value="income"<?= $formType === 'income' ? ' checked' : '' ?><?= $locked ? ' disabled' : '' ?>><span>Einnahme</span></label>
      </fieldset>
      <div class="form-grid">
        <label class="field"><span>Datum *</span><input type="date" name="tx_date" value="<?= e($ev('tx_date', today())) ?>" required></label>
        <label class="field"><span>Betrag (brutto, €) *</span><input type="text" name="amount" inputmode="decimal" value="<?= $edit ? e(number_format((float) $edit['amount'], 2, ',', '')) : '' ?>" placeholder="0,00"<?= $locked ? ' disabled' : ' required' ?>></label>
      </div>
      <label class="field"><span>Beschreibung *</span><input type="text" name="description" value="<?= e($ev('description')) ?>" placeholder="z. B. 4 Schwerlastregale, Haftpflicht 2026" maxlength="255"<?= $locked ? ' disabled' : ' required' ?>></label>
      <label class="field"><span>Kategorie</span>
        <select name="category">
          <?php foreach (array('expense', 'income') as $tt): ?>
            <optgroup label="<?= $tt === 'expense' ? 'Ausgaben' : 'Einnahmen' ?>" data-for="<?= $tt ?>">
              <?php foreach (tx_categories($tt) as $c): ?><?= opt($c, $ev('category'), $c) ?><?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field"><span>Händler / Partner</span><input type="text" name="counterparty" value="<?= e($ev('counterparty')) ?>" placeholder="z. B. Baumarkt, Versicherung, Vermieter"<?= $locked ? ' disabled' : '' ?>></label>
      <div class="form-grid">
        <label class="field"><span>Zahlart</span><select name="payment_method"><option value="">–</option><?php foreach (payment_methods() as $m): ?><?= opt($m, $ev('payment_method'), $m) ?><?php endforeach; ?></select></label>
        <label class="field"><span>Bezahlt / ausgelegt von</span><select name="paid_by"<?= $locked ? ' disabled' : '' ?>><option value="">Gemeinsame Kasse</option><?php foreach (users_all() as $u): ?><?= opt($u['id'], $ev('paid_by'), $u['name']) ?><?php endforeach; ?></select></label>
      </div>
      <details class="more-fields"<?= $edit && ((float) $edit['vat_amount'] > 0 || $edit['product_id']) ? ' open' : '' ?>>
        <summary>Weitere Angaben</summary>
        <label class="field"><span>Enthaltene USt. (€, optional)</span><input type="text" name="vat_amount" inputmode="decimal" value="<?= $edit ? e(number_format((float) $edit['vat_amount'], 2, ',', '')) : '' ?>"<?= $locked ? ' disabled' : '' ?>></label>
        <label class="field"><span>Gehört zu Gerät (z. B. Anschaffung)</span><select name="product_id"<?= $locked ? ' disabled' : '' ?>><option value="">–</option><?php foreach (catalog_products(false) as $p): ?><?= opt($p['id'], $ev('product_id'), $p['name']) ?><?php endforeach; ?></select></label>
      </details>
      <label class="field"><span>Beleg (PDF oder Foto)</span><input type="file" name="receipt" accept="application/pdf,image/jpeg,image/png,image/webp"></label>
      <?php if ($edit && $edit['receipt']): ?>
        <p class="small"><a class="link-btn" href="<?= e(url('admin/buchhaltung.php', array('datei' => $edit['id']))) ?>" target="_blank"><?= icon('eye') ?>Vorhandenen Beleg ansehen</a></p>
        <label class="check"><input type="checkbox" name="remove_receipt" value="1"><span>Beleg entfernen</span></label>
      <?php endif; ?>
      <label class="field"><span>Notiz</span><textarea name="note" rows="2"><?= e($ev('note')) ?></textarea></label>
      <div class="form-actions">
        <?php if ($edit): ?><a class="btn btn-ghost btn-sm" href="<?= e(url('admin/buchhaltung.php', $base)) ?>">Abbrechen</a><?php endif; ?>
        <button class="btn btn-primary btn-sm" type="submit"><?= icon('check') ?>Speichern</button>
      </div>
    </form>
    <?php if ($edit && !$edit['document_id']): ?>
      <form class="card-admin danger-zone" method="post" data-confirm="Eintrag samt Beleg löschen?">
        <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>"><input type="hidden" name="jahr" value="<?= $year ?>">
        <button class="btn btn-danger btn-sm" type="submit"><?= icon('trash') ?>Eintrag löschen</button>
      </form>
    <?php endif; ?>
    <p class="muted small">Belege werden geschützt gespeichert (nicht öffentlich abrufbar). Kategorien lassen sich unter Einstellungen → Firma &amp; Rechnungen anpassen.</p>
  </aside>
</div>
<?php admin_footer(); ?>
