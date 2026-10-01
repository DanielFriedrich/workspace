<?php
if (!defined('SP_APP')) { exit; }

/**
 * E-Mail-Versand: PHP mail(), SMTP (mit STARTTLS/SSL) oder nur Protokoll
 * (storage/logs/mail.log – praktisch zum Testen).
 */

function mail_header_encode($text)
{
    return preg_match('/[^\x20-\x7e]/', $text) ? '=?UTF-8?B?' . base64_encode($text) . '?=' : $text;
}

function mail_clean_address($addr)
{
    $addr = trim(str_replace(array("\r", "\n"), '', (string) $addr));
    return filter_var($addr, FILTER_VALIDATE_EMAIL) ? $addr : '';
}

/**
 * Verschickt eine Text-E-Mail. $attachments = [['name' => 'x.pdf', 'data' => '…', 'type' => 'application/pdf'], …]
 */
function send_mail($to, $subject, $body, $replyTo = '', array $attachments = array())
{
    $to = mail_clean_address($to);
    if ($to === '') {
        return false;
    }
    $host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']) : 'localhost';
    $from = mail_clean_address(setting('mail_from'));
    if ($from === '') {
        $from = 'noreply@' . preg_replace('/^www\./', '', $host);
    }
    $fromName = setting('mail_from_name', setting('site_name', 'Stagepool'));
    $replyTo = mail_clean_address($replyTo);

    $headers = array(
        'Date: ' . date('r'),
        'From: ' . mail_header_encode(str_replace(array('"', "\r", "\n"), '', $fromName)) . ' <' . $from . '>',
        'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . preg_replace('/^.*@/', '', $from) . '>',
        'MIME-Version: 1.0',
        'X-Mailer: Stagepool',
    );
    if ($replyTo !== '') {
        $headers[] = 'Reply-To: ' . $replyTo;
    }
    $encodedSubject = mail_header_encode($subject);
    $textPart = rtrim(chunk_split(base64_encode(str_replace("\r\n", "\n", $body))));
    if ($attachments) {
        $boundary = 'sp-' . bin2hex(random_bytes(12));
        $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
        $parts = array("--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . $textPart);
        foreach ($attachments as $att) {
            $name = str_replace(array('"', "\r", "\n"), '', $att['name']);
            $parts[] = "--$boundary\r\nContent-Type: " . (isset($att['type']) ? $att['type'] : 'application/octet-stream') . '; name="' . $name . "\"\r\n"
                . "Content-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"" . $name . "\"\r\n\r\n"
                . rtrim(chunk_split(base64_encode($att['data'])));
        }
        $encodedBody = implode("\r\n", $parts) . "\r\n--$boundary--";
    } else {
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: base64';
        $encodedBody = $textPart;
    }
    $logBody = $body;
    foreach ($attachments as $att) {
        $logBody .= "\n[Anhang: " . $att['name'] . ', ' . round(strlen($att['data']) / 1024) . ' KB]';
        if (setting('mail_mode') === 'log' && preg_match('/^[\w.-]+$/', $att['name'])) {
            @file_put_contents(SP_ROOT . '/storage/logs/' . date('Ymd-His') . '-' . $att['name'], $att['data']);
        }
    }

    $mode = setting('mail_mode', 'mail');
    if ($mode === 'log') {
        return mail_log($to, $subject, $logBody, 'protokolliert');
    }
    if ($mode === 'smtp') {
        $ok = smtp_send($from, $to, array_merge(array('To: ' . $to, 'Subject: ' . $encodedSubject), $headers), $encodedBody);
    } else {
        $ok = @mail($to, $encodedSubject, $encodedBody, implode("\r\n", $headers), '-f' . $from);
        if (!$ok) {
            $ok = @mail($to, $encodedSubject, $encodedBody, implode("\r\n", $headers));
        }
    }
    if (!$ok) {
        mail_log($to, $subject, $logBody, 'FEHLER beim Versand (' . $mode . ')');
    }
    return $ok;
}

function mail_log($to, $subject, $body, $state)
{
    $file = SP_ROOT . '/storage/logs/mail.log';
    $entry = '=== ' . now() . ' · ' . $state . " ===\nAn: " . $to . "\nBetreff: " . $subject . "\n\n" . $body . "\n\n";
    @file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
    return $state === 'protokolliert';
}

/** Minimaler SMTP-Client (AUTH LOGIN, STARTTLS oder SSL). */
function smtp_send($from, $to, array $headers, $body)
{
    $host = setting('smtp_host');
    $port = (int) setting('smtp_port', '587');
    $secure = setting('smtp_secure', 'tls');
    if ($host === '') {
        return false;
    }
    $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $ctx = stream_context_create(array('ssl' => array('verify_peer' => true, 'verify_peer_name' => true)));
    $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        error_log('[Stagepool SMTP] Verbindung fehlgeschlagen: ' . $errstr);
        return false;
    }
    stream_set_timeout($fp, 20);
    $read = function () use ($fp) {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };
    $cmd = function ($command, $expect) use ($fp, $read) {
        if ($command !== null) {
            fwrite($fp, $command . "\r\n");
        }
        $resp = $read();
        if ((int) substr($resp, 0, 3) !== $expect) {
            throw new RuntimeException('SMTP: ' . trim($resp));
        }
        return $resp;
    };
    $ehloHost = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
    try {
        $cmd(null, 220);
        $cmd('EHLO ' . $ehloHost, 250);
        if ($secure === 'tls') {
            $cmd('STARTTLS', 220);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('SMTP: STARTTLS fehlgeschlagen');
            }
            $cmd('EHLO ' . $ehloHost, 250);
        }
        if (setting('smtp_user') !== '') {
            $cmd('AUTH LOGIN', 334);
            $cmd(base64_encode(setting('smtp_user')), 334);
            $cmd(base64_encode(setting('smtp_pass')), 235);
        }
        $cmd('MAIL FROM:<' . $from . '>', 250);
        $cmd('RCPT TO:<' . $to . '>', 250);
        $cmd('DATA', 354);
        $data = implode("\r\n", $headers) . "\r\n\r\n" . preg_replace("/\r?\n/", "\r\n", $body);
        $data = preg_replace('/^\./m', '..', $data);
        fwrite($fp, $data . "\r\n.\r\n");
        $cmd(null, 250);
        fwrite($fp, "QUIT\r\n");
        fclose($fp);
        return true;
    } catch (Exception $ex) {
        error_log('[Stagepool] ' . $ex->getMessage());
        @fclose($fp);
        return false;
    }
}

/* ------------------------------------------------------------------
 * Texte rund um Buchungen
 * ------------------------------------------------------------------ */

function booking_load($id)
{
    $b = db_one('SELECT * FROM #__bookings WHERE id = ?', array((int) $id));
    if (!$b) {
        return null;
    }
    $b['items'] = db_all(
        'SELECT bi.*, p.location_id, l.name AS location_name, l.street, l.zip, l.city
           FROM #__booking_items bi
           LEFT JOIN #__products p ON p.id = bi.product_id
           LEFT JOIN #__locations l ON l.id = p.location_id
          WHERE bi.booking_id = ? ORDER BY bi.id',
        array((int) $id)
    );
    $allocs = booking_allocations($id);
    foreach ($b['items'] as $k => $it) {
        $names = array();
        if (isset($allocs[(int) $it['id']])) {
            foreach ($allocs[(int) $it['id']] as $a) {
                if ($a['location_name']) {
                    $names[$a['location_name']] = true;
                }
            }
        }
        if ($names) {
            $b['items'][$k]['location_name'] = implode(' / ', array_keys($names));
        }
    }
    return $b;
}

function booking_summary_text(array $b)
{
    $lines = array();
    $lines[] = 'Anfrage-Nr.: ' . $b['code'];
    $lines[] = 'Zeitraum:    ' . period_de($b['start_date'], $b['end_date']) . ' (' . plural(days_inclusive($b['start_date'], $b['end_date']), 'Miettag', 'Miettage') . ')';
    $lines[] = 'Status:      ' . status_label($b['status']);
    if (isset($b['price_tier']) && $b['price_tier'] === 'internal') {
        $lines[] = 'Preisliste:  Interne Vermietung (Teampreise)';
    }
    $lines[] = '';
    $lines[] = 'Geräte:';
    $locs = booking_pickup_locations($b['id']);
    foreach ($b['items'] as $it) {
        $lines[] = sprintf('  %d × %s – %s', $it['qty'], $it['product_name'], money($it['line_total']));
    }
    $lines[] = '';
    $lines[] = 'Mietpreis gesamt: ' . money($b['total']);
    if ((float) $b['deposit_total'] > 0) {
        $lines[] = 'Kaution:          ' . money($b['deposit_total']);
    }
    $lines[] = '';
    if ($b['handover'] === 'delivery') {
        $lines[] = 'Übergabe: Lieferung nach Absprache' . ($b['delivery_address'] ? ' an ' . $b['delivery_address'] : '');
    } else {
        $lines[] = 'Übergabe: Selbstabholung' . ($locs ? ' – ' . implode(' | ', $locs) : '');
    }
    return implode("\n", $lines);
}

function booking_status_link(array $b)
{
    return absolute_url('status.php', array('code' => $b['code'], 't' => $b['token']));
}

function mail_signature()
{
    $s = "\n\nViele Grüße\n" . setting('site_name');
    if (setting('contact_phone')) {
        $s .= "\nTel. " . setting('contact_phone');
    }
    if (setting('contact_email')) {
        $s .= "\n" . setting('contact_email');
    }
    return $s;
}

/** Empfänger für interne Benachrichtigungen. */
function notify_recipients()
{
    $list = array();
    foreach (preg_split('/[,;\s]+/', setting('notify_email') . ' ' . setting('contact_email')) as $addr) {
        $addr = mail_clean_address($addr);
        if ($addr !== '') {
            $list[strtolower($addr)] = $addr;
        }
    }
    return array_values($list);
}

function mail_booking_created(array $b)
{
    $name = setting('site_name');
    $customer = "Hallo " . $b['customer_name'] . ",\n\n"
        . "vielen Dank für deine Anfrage! Die Geräte sind ab sofort für dich reserviert. "
        . "Wir prüfen alles und melden uns mit einer Bestätigung bei dir.\n\n"
        . booking_summary_text($b) . "\n\n"
        . "Status ansehen oder Anfrage stornieren:\n" . booking_status_link($b)
        . mail_signature();
    send_mail($b['email'], 'Deine Anfrage ' . $b['code'] . ' bei ' . $name, $customer, setting('contact_email'));

    $admin = "Neue Mietanfrage über die Website\n\n"
        . booking_summary_text($b) . "\n\n"
        . "Kunde:   " . $b['customer_name'] . ($b['organisation'] ? ' (' . $b['organisation'] . ')' : '') . "\n"
        . "E-Mail:  " . $b['email'] . "\nTelefon: " . $b['phone'] . "\n"
        . "Anlass:  " . $b['event_type'] . "\n\n"
        . "Nachricht:\n" . ($b['message'] ? $b['message'] : '–') . "\n\n"
        . "Im Backend bearbeiten:\n" . absolute_url('admin/buchung.php', array('id' => $b['id']));
    foreach (notify_recipients() as $to) {
        send_mail($to, 'Neue Anfrage ' . $b['code'] . ' – ' . $b['customer_name'], $admin, $b['email']);
    }
}

function mail_booking_status(array $b, $note = '')
{
    $intro = array(
        'confirmed' => "gute Nachrichten: deine Anfrage ist bestätigt und die Technik ist fest für dich gebucht. "
            . "Wir melden uns zur Abstimmung des Abholtermins – oder du rufst kurz an.",
        'rejected'  => "leider können wir deine Anfrage diesmal nicht bestätigen. Die Reservierung wurde aufgehoben.",
        'cancelled' => "deine Buchung wurde storniert. Die Reservierung ist aufgehoben.",
        'picked_up' => "viel Spaß mit der Technik! Hier noch einmal die Übersicht deiner Ausleihe.",
        'returned'  => "danke fürs Zurückbringen – die Ausleihe ist abgeschlossen. Wir freuen uns auf das nächste Mal!",
    );
    if (!isset($intro[$b['status']])) {
        return false;
    }
    $text = "Hallo " . $b['customer_name'] . ",\n\n" . $intro[$b['status']] . "\n\n"
        . ($note !== '' ? $note . "\n\n" : '')
        . booking_summary_text($b) . "\n\n"
        . (setting('pickup_info') && $b['status'] === 'confirmed' ? strip_tags(setting('pickup_info')) . "\n\n" : '')
        . "Details: " . booking_status_link($b)
        . mail_signature();
    return send_mail($b['email'], status_label($b['status']) . ': Anfrage ' . $b['code'] . ' – ' . setting('site_name'), $text, setting('contact_email'));
}
