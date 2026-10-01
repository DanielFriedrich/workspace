<?php
/** Kunden-Download von Angebot/Rechnung über den persönlichen Status-Link. */
require __DIR__ . '/inc/bootstrap.php';

$b = db_one('SELECT id, token FROM #__bookings WHERE code = ?', array(strtoupper((string) input('code'))));
$d = $b ? doc_load((int) input('id')) : null;
if (!$b || !$d || (int) $d['booking_id'] !== (int) $b['id'] || !hash_equals($b['token'], (string) input('t')) || $d['status'] === 'cancelled') {
    http_response_code(404);
    exit('Beleg nicht gefunden.');
}
doc_output($d, input('download') === '');
