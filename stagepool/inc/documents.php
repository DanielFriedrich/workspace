<?php
if (!defined('SP_APP')) { exit; }

/**
 * Angebote & Rechnungen
 * ---------------------
 * Beim Erstellen wird ein Schnappschuss der Buchung gespeichert (Positionen,
 * Kunde, Summen). So bleibt ein Beleg unverändert, auch wenn die Buchung oder
 * Gerätepreise später geändert werden. Das PDF wird bei jedem Abruf aus diesem
 * Schnappschuss erzeugt.
 */

function doc_types()
{
    return array('offer' => 'Angebot', 'invoice' => 'Rechnung');
}

function doc_statuses()
{
    return array(
        'open'      => 'Erstellt',
        'sent'      => 'Versendet',
        'paid'      => 'Bezahlt',
        'accepted'  => 'Angenommen',
        'cancelled' => 'Storniert',
    );
}

function doc_status_badge($status)
{
    $all = doc_statuses();
    $cls = array('open' => 'requested', 'sent' => 'offered', 'paid' => 'returned', 'accepted' => 'returned', 'cancelled' => 'cancelled');
    return '<span class="status status-' . e(isset($cls[$status]) ? $cls[$status] : 'requested') . '">' . e(isset($all[$status]) ? $all[$status] : $status) . '</span>';
}

function vat_rate()
{
    return max(0, (float) str_replace(',', '.', setting('vat_rate', '0')));
}

/** Enthaltene Umsatzsteuer aus einem Bruttobetrag. */
function vat_included($gross, $rate)
{
    return $rate > 0 ? round($gross - $gross / (1 + $rate / 100), 2) : 0.0;
}

/** Nächste fortlaufende Nummer, z. B. RE-2026-007. */
function doc_next_number($type)
{
    $prefix = preg_replace('/[^A-Za-z0-9]/', '', setting($type === 'invoice' ? 'invoice_prefix' : 'offer_prefix', $type === 'invoice' ? 'RE' : 'AN'));
    $base = ($prefix !== '' ? $prefix . '-' : '') . date('Y') . '-';
    $max = 0;
    foreach (db_all('SELECT number FROM #__documents WHERE type = ? AND number LIKE ?', array($type, $base . '%')) as $r) {
        $max = max($max, (int) substr($r['number'], strlen($base)));
    }
    return $base . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
}

/** Erzeugt Angebot oder Rechnung aus einer Buchung. Gibt die Beleg-ID zurück. */
function doc_create(array $b, $type, $note = '')
{
    $items = array();
    $subtotal = 0.0;
    foreach ($b['items'] as $it) {
        $items[] = array(
            'name' => $it['product_name'], 'qty' => (int) $it['qty'], 'days' => (int) $it['days'],
            'price_day' => (float) $it['price_day'], 'line' => (float) $it['line_total'],
        );
        $subtotal += (float) $it['line_total'];
    }
    $subtotal = round($subtotal, 2);
    $total = round((float) $b['total'], 2);
    $name = $b['customer_name'] . ($b['organisation'] ? "\n" . $b['organisation'] : '');
    $days = $type === 'invoice' ? setting_int('payment_days', 14) : setting_int('offer_valid_days', 14);

    $lock = booking_lock();
    $id = db_insert('documents', array(
        'booking_id' => (int) $b['id'], 'type' => $type, 'number' => doc_next_number($type),
        'doc_date' => today(), 'due_date' => date_add_days(today(), max(0, $days)),
        'service_from' => $b['start_date'], 'service_to' => $b['end_date'],
        'customer_name' => $name, 'customer_address' => (string) $b['customer_address'], 'customer_email' => (string) $b['email'],
        'items' => json_encode($items), 'subtotal' => $subtotal, 'adjustment' => round($total - $subtotal, 2), 'total' => $total,
        'vat_rate' => vat_rate(), 'deposit_total' => (float) $b['deposit_total'], 'note' => $note, 'status' => 'open',
        'sent_at' => null, 'paid_at' => null, 'created_by' => current_user() ? (int) current_user()['id'] : null, 'created_at' => now(),
    ));
    booking_unlock($lock);
    $doc = doc_load($id);
    booking_log($b['id'], 'doc', doc_title($doc) . ' erstellt');
    return $id;
}

function doc_load($id)
{
    $d = db_one('SELECT * FROM #__documents WHERE id = ?', array((int) $id));
    if ($d) {
        $d['items'] = json_decode((string) $d['items'], true) ?: array();
    }
    return $d;
}

function doc_title(array $d)
{
    $t = doc_types();
    return $t[$d['type']] . ' ' . $d['number'];
}

function doc_filename(array $d)
{
    $t = doc_types();
    return $t[$d['type']] . '-' . preg_replace('/[^A-Za-z0-9-]/', '', $d['number']) . '.pdf';
}

function booking_documents($bookingId)
{
    return db_all('SELECT * FROM #__documents WHERE booking_id = ? ORDER BY id', array((int) $bookingId));
}

/** Verschickt den Beleg als PDF-Anhang an den Kunden. */
function doc_send(array $d, $message = '')
{
    if (!$d['customer_email']) {
        return false;
    }
    $first = strtok($d['customer_name'], "\n");
    if ($d['type'] === 'offer') {
        $text = "Hallo " . $first . ",\n\nanbei erhältst du unser Angebot " . $d['number'] . " für den Zeitraum "
            . period_de($d['service_from'], $d['service_to']) . " über " . money($d['total']) . ".\n\n"
            . ($message !== '' ? $message . "\n\n" : '')
            . "Das Angebot ist gültig bis " . date_de($d['due_date']) . ". Bis dahin bleibt die Technik für dich reserviert. "
            . "Zur Annahme genügt eine kurze Antwort auf diese E-Mail.";
    } else {
        $text = "Hallo " . $first . ",\n\nanbei erhältst du die Rechnung " . $d['number'] . " über " . money($d['total']) . ".\n\n"
            . ($message !== '' ? $message . "\n\n" : '')
            . ($d['status'] === 'paid' ? "Der Betrag ist bereits bezahlt – vielen Dank!" : "Bitte überweise den Betrag bis " . date_de($d['due_date']) . " unter Angabe der Rechnungsnummer.")
            . "\n\nVielen Dank, dass du bei uns gemietet hast!";
    }
    $b = $d['booking_id'] ? db_one('SELECT code, token FROM #__bookings WHERE id = ?', array($d['booking_id'])) : null;
    if ($b) {
        $text .= "\n\nAlle Details zu deiner Buchung:\n" . booking_status_link($b);
    }
    $text .= mail_signature();
    $ok = send_mail($d['customer_email'], doc_title($d) . ' – ' . setting('site_name'), $text, setting('contact_email'),
        array(array('name' => doc_filename($d), 'data' => doc_pdf($d), 'type' => 'application/pdf')));
    if ($ok) {
        db_update('documents', array('sent_at' => now(), 'status' => $d['status'] === 'open' ? 'sent' : $d['status']), 'id = ?', array($d['id']));
        if ($d['booking_id']) {
            booking_log($d['booking_id'], 'mail', doc_title($d) . ' per E-Mail an ' . $d['customer_email']);
        }
    }
    return $ok;
}

/** Rechnung als bezahlt markieren – erzeugt automatisch eine Einnahme in der Buchhaltung. */
function doc_mark_paid(array $d, $date, $method)
{
    $date = parse_date($date) ?: today();
    db_update('documents', array('status' => 'paid', 'paid_at' => $date), 'id = ?', array($d['id']));
    db_exec('DELETE FROM #__transactions WHERE document_id = ?', array($d['id']));
    db_insert('transactions', array(
        'type' => 'income', 'tx_date' => $date, 'category' => 'Vermietung',
        'description' => 'Rechnung ' . $d['number'], 'counterparty' => strtok($d['customer_name'], "\n"),
        'amount' => (float) $d['total'], 'vat_amount' => vat_included((float) $d['total'], (float) $d['vat_rate']),
        'payment_method' => $method, 'paid_by' => null, 'document_id' => (int) $d['id'], 'booking_id' => $d['booking_id'],
        'product_id' => null, 'receipt' => '', 'note' => '', 'created_by' => current_user() ? (int) current_user()['id'] : null,
        'created_at' => now(), 'updated_at' => now(),
    ));
    if ($d['booking_id']) {
        booking_log($d['booking_id'], 'doc', 'Rechnung ' . $d['number'] . ' bezahlt am ' . date_de($date));
    }
}

function doc_set_status(array $d, $status)
{
    db_update('documents', array('status' => $status, 'paid_at' => $status === 'paid' ? $d['paid_at'] : null), 'id = ?', array($d['id']));
    if ($status !== 'paid') {
        db_exec('DELETE FROM #__transactions WHERE document_id = ?', array($d['id']));
    }
    if ($d['booking_id']) {
        $all = doc_statuses();
        booking_log($d['booking_id'], 'doc', doc_title($d) . ': ' . $all[$status]);
    }
}

/* ------------------------------------------------------------------
 * PDF
 * ------------------------------------------------------------------ */

/** UTF-8 → Windows-1252 (Zeichensatz der PDF-Standardschriften, inkl. €). */
function pdf_t($s)
{
    $s = (string) $s;
    if (function_exists('iconv')) {
        $r = @iconv('UTF-8', 'windows-1252//TRANSLIT', $s);
        if ($r !== false) {
            return $r;
        }
    }
    if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($s, 'Windows-1252', 'UTF-8');
    }
    $map = array('€' => "\x80", '‚' => "\x82", '„' => "\x84", '…' => "\x85", '‘' => "\x91", '’' => "\x92", '“' => "\x93", '”' => "\x94", '–' => "\x96", '—' => "\x97", '•' => "\x95");
    // Letzter Ausweg ohne iconv/mbstring (utf8_decode ist ab PHP 8.2 veraltet)
    $s = strtr($s, $map);
    return preg_replace_callback('/[\xC0-\xDF][\x80-\xBF]|[\xE0-\xFF][\x80-\xBF]{2,3}/', function ($m) {
        if (strlen($m[0]) === 2) {
            $cp = ((ord($m[0][0]) & 0x1F) << 6) | (ord($m[0][1]) & 0x3F);
            return $cp < 256 ? chr($cp) : '?';
        }
        return '?';
    }, $s);
}

function pdf_money($v)
{
    return number_format((float) $v, 2, ',', '.') . ' €';
}

/** Erzeugt das PDF und gibt es als String zurück. */
function doc_pdf(array $d)
{
    require_once SP_ROOT . '/inc/pdf.php';
    $isInvoice = $d['type'] === 'invoice';
    $company = setting('company_name', setting('site_name'));
    $addr = trim(setting('company_address'));
    $addrLine = $company . ($addr !== '' ? ' · ' . preg_replace('/\s*\R\s*/', ' · ', $addr) : '');

    $pdf = new StagepoolPdf('P', 'mm', 'A4');
    $pdf->SetTitle(pdf_t(doc_title($d)));
    $pdf->SetAuthor(pdf_t($company));
    $pdf->SetCreator('Stagepool');
    $pdf->AliasNbPages();
    $pdf->SetMargins(20, 20, 20);
    $pdf->SetAutoPageBreak(true, 34);

    // Fußzeile: Anbieter | Kontakt | Bank & Steuer
    $c1 = $company . ($addr !== '' ? "\n" . $addr : '');
    $c2 = trim((setting('contact_email') ? 'E-Mail: ' . setting('contact_email') . "\n" : '') . (setting('contact_phone') ? 'Tel.: ' . setting('contact_phone') . "\n" : '')
        . (setting('tax_number') ? 'Steuernummer: ' . setting('tax_number') . "\n" : '') . (setting('vat_id') ? 'USt-IdNr.: ' . setting('vat_id') : ''));
    $c3 = trim((setting('bank_holder') ? 'Kontoinhaber: ' . setting('bank_holder') . "\n" : '') . (setting('iban') ? 'IBAN: ' . setting('iban') . "\n" : '')
        . (setting('bic') ? 'BIC: ' . setting('bic') . "\n" : '') . (setting('bank_name') ? setting('bank_name') : ''));
    $pdf->footerCols = array_values(array_filter(array($c1, $c2, $c3, setting('doc_footer')), 'strlen'));

    $pdf->AddPage();
    // Farbleiste (Magenta – Violett – Cyan)
    $pdf->SetFillColor(255, 46, 147);
    $pdf->Rect(0, 0, 70, 3, 'F');
    $pdf->SetFillColor(168, 85, 247);
    $pdf->Rect(70, 0, 70, 3, 'F');
    $pdf->SetFillColor(34, 211, 238);
    $pdf->Rect(140, 0, 70, 3, 'F');

    // Kopf
    $pdf->SetXY(20, 16);
    $pdf->SetFont('Helvetica', 'B', 18);
    $pdf->SetTextColor(20, 18, 30);
    $pdf->Cell(110, 9, pdf_t($company), 0, 0, 'L');
    $pdf->SetFont('Helvetica', '', 8.5);
    $pdf->SetTextColor(110, 110, 125);
    $pdf->SetXY(130, 17);
    $pdf->MultiCell(60, 4, pdf_t(setting('site_tagline')), 0, 'R');

    // Absenderzeile + Empfänger (Anschriftfeld nach DIN 5008)
    $pdf->SetXY(20, 45);
    $pdf->SetFont('Helvetica', 'U', 7);
    $pdf->Cell(85, 4, pdf_t($addrLine), 0, 1);
    $pdf->SetFont('Helvetica', '', 10.5);
    $pdf->SetTextColor(20, 18, 30);
    $pdf->SetX(20);
    $recipient = trim($d['customer_name'] . "\n" . preg_replace('/\s*,\s*/', "\n", trim($d['customer_address'])));
    $pdf->MultiCell(85, 5, pdf_t($recipient), 0, 'L');

    // Infoblock rechts
    $info = array(
        array($isInvoice ? 'Rechnungsnummer' : 'Angebotsnummer', $d['number']),
        array($isInvoice ? 'Rechnungsdatum' : 'Datum', date_de($d['doc_date'])),
    );
    if ($d['service_from']) {
        $info[] = array($isInvoice ? 'Leistungszeitraum' : 'Mietzeitraum', date_de($d['service_from']) . ($d['service_to'] !== $d['service_from'] ? ' – ' . date_de($d['service_to']) : ''));
    }
    $info[] = array($isInvoice ? 'Zahlbar bis' : 'Gültig bis', date_de($d['due_date']));
    if ($d['booking_id']) {
        $code = db_value('SELECT code FROM #__bookings WHERE id = ?', array($d['booking_id']));
        if ($code) {
            $info[] = array('Buchung', $code);
        }
    }
    $y = 45;
    foreach ($info as $row) {
        $pdf->SetXY(120, $y);
        $pdf->SetFont('Helvetica', '', 8.5);
        $pdf->SetTextColor(110, 110, 125);
        $pdf->Cell(32, 5, pdf_t($row[0]), 0, 0, 'L');
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetTextColor(20, 18, 30);
        $pdf->Cell(38, 5, pdf_t($row[1]), 0, 0, 'R');
        $y += 5.5;
    }

    // Titel & Einleitung
    $pdf->SetXY(20, max(92, $pdf->GetY() + 10));
    $pdf->SetFont('Helvetica', 'B', 17);
    $pdf->SetTextColor(20, 18, 30);
    $pdf->Cell(0, 9, pdf_t(($isInvoice ? 'Rechnung ' : 'Angebot ') . $d['number']), 0, 1);
    if ($d['status'] === 'cancelled') {
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetTextColor(220, 40, 80);
        $pdf->Cell(0, 6, pdf_t('STORNIERT'), 0, 1);
    }
    $pdf->Ln(2);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetTextColor(40, 38, 52);
    $pdf->MultiCell(0, 5, pdf_t('Hallo ' . strtok($d['customer_name'], "\n") . ','), 0, 'L');
    $pdf->Ln(1);
    $pdf->MultiCell(0, 5, pdf_t(setting($isInvoice ? 'invoice_text' : 'offer_text')), 0, 'L');
    $pdf->Ln(5);

    // Positionen
    $cols = array(array('Pos.', 12, 'L'), array('Gerät', 76, 'L'), array('Anzahl', 16, 'R'), array('Tage', 14, 'R'), array('Preis 1. Tag', 26, 'R'), array('Betrag', 26, 'R'));
    $pdf->SetFillColor(24, 22, 36);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 8.5);
    foreach ($cols as $c) {
        $pdf->Cell($c[1], 8, pdf_t($c[0]), 0, 0, $c[2], true);
    }
    $pdf->Ln();
    $pdf->SetTextColor(20, 18, 30);
    $pct = extra_day_percent();
    foreach ($d['items'] as $i => $it) {
        if ($pdf->GetY() > 245) {
            $pdf->AddPage();
        }
        $y0 = $pdf->GetY();
        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->Cell(12, 6, (string) ($i + 1), 0, 0, 'L');
        $pdf->Cell(76, 6, pdf_t($it['name']), 0, 0, 'L');
        $pdf->Cell(16, 6, (string) $it['qty'], 0, 0, 'R');
        $pdf->Cell(14, 6, (string) $it['days'], 0, 0, 'R');
        $pdf->Cell(26, 6, pdf_t(pdf_money($it['price_day'])), 0, 0, 'R');
        $pdf->SetFont('Helvetica', 'B', 9.5);
        $pdf->Cell(26, 6, pdf_t(pdf_money($it['line'])), 0, 1, 'R');
        if ($it['days'] > 1 && $it['qty'] > 0) {
            $perUnit = $it['line'] / $it['qty'];
            $extraDay = $it['days'] > 1 ? ($perUnit - $it['price_day']) / ($it['days'] - 1) : 0;
            $pdf->SetFont('Helvetica', '', 7.5);
            $pdf->SetTextColor(110, 110, 125);
            $pdf->SetX(32);
            $pdf->Cell(150, 4, pdf_t('1. Tag ' . pdf_money($it['price_day']) . ', jeder weitere Tag ' . pdf_money($extraDay) . ' – ' . pdf_money($perUnit) . ' pro Stück'), 0, 1, 'L');
            $pdf->SetTextColor(20, 18, 30);
        }
        $pdf->SetDrawColor(230, 230, 236);
        $pdf->Line(20, $pdf->GetY() + 1, 190, $pdf->GetY() + 1);
        $pdf->Ln(2.5);
    }

    // Summen
    $pdf->Ln(2);
    $sumRow = function ($label, $value, $bold = false, $size = 9.5) use ($pdf) {
        $pdf->SetX(110);
        $pdf->SetFont('Helvetica', $bold ? 'B' : '', $size);
        $pdf->Cell(54, 6.5, pdf_t($label), 0, 0, 'L');
        $pdf->Cell(26, 6.5, pdf_t($value), 0, 1, 'R');
    };
    $rate = (float) $d['vat_rate'];
    if (abs((float) $d['adjustment']) >= 0.01) {
        $sumRow('Zwischensumme', pdf_money($d['subtotal']));
        $sumRow((float) $d['adjustment'] < 0 ? 'Rabatt / Anpassung' : 'Zuschlag / Anpassung', pdf_money($d['adjustment']));
    }
    $pdf->SetDrawColor(24, 22, 36);
    $pdf->Line(110, $pdf->GetY() + 0.5, 190, $pdf->GetY() + 0.5);
    $pdf->Ln(1.5);
    $sumRow($isInvoice ? 'Rechnungsbetrag' : 'Gesamtbetrag', pdf_money($d['total']), true, 11.5);
    if ($rate > 0) {
        $sumRow('darin enthalten ' . str_replace('.', ',', (string) round($rate, 2)) . ' % USt.', pdf_money(vat_included((float) $d['total'], $rate)), false, 8.5);
        $sumRow('Nettobetrag', pdf_money((float) $d['total'] - vat_included((float) $d['total'], $rate)), false, 8.5);
    }
    $pdf->Ln(6);

    // Hinweise
    $pdf->SetFont('Helvetica', '', 9.5);
    $pdf->SetTextColor(40, 38, 52);
    $lines = array();
    if ($rate <= 0 && setting('tax_note')) {
        $lines[] = setting('tax_note');
    }
    if ($pct < 100) {
        $lines[] = 'Preisstaffel: 1. Miettag voller Tagespreis, jeder weitere Tag ' . str_replace('.', ',', (string) round($pct, 1)) . ' % davon.';
    }
    if ((float) $d['deposit_total'] > 0) {
        $lines[] = $isInvoice
            ? 'Die Kaution von ' . pdf_money($d['deposit_total']) . ' ist nicht Teil dieser Rechnung und wird bei vollständiger und unbeschädigter Rückgabe erstattet.'
            : 'Bei Abholung wird eine Kaution von ' . pdf_money($d['deposit_total']) . ' fällig, die bei vollständiger und unbeschädigter Rückgabe erstattet wird.';
    }
    if ($isInvoice) {
        if ($d['status'] === 'paid') {
            $lines[] = 'Betrag dankend erhalten' . ($d['paid_at'] ? ' am ' . date_de($d['paid_at']) : '') . '.';
        } elseif ($d['status'] !== 'cancelled') {
            $lines[] = 'Bitte überweise den Rechnungsbetrag bis zum ' . date_de($d['due_date']) . ' unter Angabe der Rechnungsnummer'
                . (setting('iban') ? ' auf das Konto IBAN ' . setting('iban') . (setting('bank_holder') ? ' (' . setting('bank_holder') . ')' : '') : '') . '.';
        }
    } else {
        $lines[] = 'Dieses Angebot ist gültig bis ' . date_de($d['due_date']) . '. Bis dahin bleibt die Technik für dich reserviert. Zur Annahme genügt eine kurze Antwort per E-Mail.';
        $lines[] = 'Abholung und Rückgabe stimmen wir gemeinsam ab. Es gelten unsere Mietbedingungen.';
    }
    if (trim((string) $d['note']) !== '') {
        $lines[] = trim($d['note']);
    }
    foreach ($lines as $l) {
        $pdf->MultiCell(0, 5, pdf_t($l), 0, 'L');
        $pdf->Ln(1.5);
    }
    $pdf->Ln(3);
    $pdf->MultiCell(0, 5, pdf_t("Viele Grüße\n" . $company), 0, 'L');

    return $pdf->Output('S');
}

/** PDF an den Browser schicken. */
function doc_output(array $d, $inline = true)
{
    $data = doc_pdf($d);
    header('Content-Type: application/pdf');
    header('Content-Length: ' . strlen($data));
    header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . doc_filename($d) . '"');
    header('Cache-Control: private, no-store');
    echo $data;
    exit;
}
