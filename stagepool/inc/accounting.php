<?php
if (!defined('SP_APP')) { exit; }

/**
 * Buchhaltung (einfache Einnahmen-Überschuss-Rechnung)
 * Einnahmen entstehen automatisch, wenn eine Rechnung als bezahlt markiert
 * wird, oder werden manuell erfasst. Ausgaben werden manuell erfasst,
 * optional mit Beleg (PDF/Foto) und der Person, die bezahlt bzw. ausgelegt hat.
 */

function tx_types()
{
    return array('income' => 'Einnahme', 'expense' => 'Ausgabe');
}

function tx_categories($type)
{
    $raw = setting($type === 'income' ? 'income_categories' : 'expense_categories');
    $list = array();
    foreach (preg_split('/\R/', (string) $raw) as $line) {
        $line = trim($line);
        if ($line !== '') {
            $list[] = $line;
        }
    }
    return $list ?: array('Sonstiges');
}

function payment_methods()
{
    return array('Überweisung', 'Bar', 'PayPal', 'Karte', 'Lastschrift', 'Privat ausgelegt');
}

/** Beleg-Upload (PDF oder Bild) in den geschützten Ordner storage/receipts. */
function receipt_upload($field)
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 10 * 1024 * 1024) {
        flash('error', 'Der Beleg konnte nicht hochgeladen werden (max. 10 MB).');
        return false;
    }
    $head = (string) file_get_contents($f['tmp_name'], false, null, 0, 5);
    if ($head === '%PDF-') {
        $ext = 'pdf';
    } else {
        $info = @getimagesize($f['tmp_name']);
        $types = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif');
        if (defined('IMAGETYPE_WEBP')) {
            $types[IMAGETYPE_WEBP] = 'webp';
        }
        if (!$info || !isset($types[$info[2]])) {
            flash('error', 'Belege bitte als PDF, JPG, PNG oder WebP hochladen.');
            return false;
        }
        $ext = $types[$info[2]];
    }
    $dir = SP_ROOT . '/storage/receipts';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $name = date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
        flash('error', 'Der Beleg konnte nicht gespeichert werden. Schreibrechte für /storage prüfen.');
        return false;
    }
    @chmod($dir . '/' . $name, 0640);
    return $name;
}

function receipt_path($name)
{
    return preg_match('/^[\w.-]+$/', (string) $name) ? SP_ROOT . '/storage/receipts/' . $name : null;
}

function receipt_delete($name)
{
    $p = receipt_path($name);
    if ($p && is_file($p)) {
        @unlink($p);
    }
}

/** Kennzahlen eines Jahres: Summen, Monate, Kategorien, ausgelegt pro Person. */
function accounting_summary($year)
{
    $from = $year . '-01-01';
    $to = $year . '-12-31';
    $rows = db_all('SELECT t.*, u.name AS paid_by_name FROM #__transactions t LEFT JOIN #__users u ON u.id = t.paid_by WHERE t.tx_date >= ? AND t.tx_date <= ?', array($from, $to));
    $sum = array('income' => 0.0, 'expense' => 0.0, 'vat_in' => 0.0, 'vat_out' => 0.0);
    $months = array();
    for ($m = 1; $m <= 12; $m++) {
        $months[$m] = array('income' => 0.0, 'expense' => 0.0);
    }
    $cats = array('income' => array(), 'expense' => array());
    $persons = array();
    foreach ($rows as $r) {
        $type = $r['type'] === 'income' ? 'income' : 'expense';
        $amount = (float) $r['amount'];
        $sum[$type] += $amount;
        $sum[$type === 'income' ? 'vat_out' : 'vat_in'] += (float) $r['vat_amount'];
        $months[(int) substr($r['tx_date'], 5, 2)][$type] += $amount;
        $c = $r['category'] !== '' ? $r['category'] : 'Ohne Kategorie';
        $cats[$type][$c] = (isset($cats[$type][$c]) ? $cats[$type][$c] : 0) + $amount;
        if ($type === 'expense' && $r['paid_by']) {
            $persons[$r['paid_by_name']] = (isset($persons[$r['paid_by_name']]) ? $persons[$r['paid_by_name']] : 0) + $amount;
        }
    }
    arsort($cats['income']);
    arsort($cats['expense']);
    arsort($persons);
    $open = db_one("SELECT COUNT(*) AS n, COALESCE(SUM(total), 0) AS s FROM #__documents WHERE type = 'invoice' AND status IN ('open', 'sent')");
    return array('sum' => $sum, 'months' => $months, 'cats' => $cats, 'persons' => $persons, 'count' => count($rows),
        'open_invoices' => (int) $open['n'], 'open_amount' => (float) $open['s']);
}
