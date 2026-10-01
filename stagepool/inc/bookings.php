<?php
if (!defined('SP_APP')) { exit; }

/** Anlegen und Ändern von Buchungen (Website und Backend). */

function booking_new_code()
{
    do {
        $code = 'SP-' . random_code(5);
    } while (db_value('SELECT COUNT(*) FROM #__bookings WHERE code = ?', array($code)));
    return $code;
}

function booking_log($bookingId, $action, $note = '')
{
    $u = function_exists('current_user') && PHP_SAPI !== 'cli' ? current_user() : null;
    db_insert('booking_log', array(
        'booking_id' => (int) $bookingId, 'user_id' => $u ? (int) $u['id'] : null,
        'action' => $action, 'note' => $note, 'created_at' => now(),
    ));
}

/**
 * Schreibt die Positionen einer Buchung neu und berechnet die Summen.
 * $wanted = [product_id => qty]
 */
function booking_write_items($bookingId, array $wanted, $from, $to)
{
    $days = days_inclusive($from, $to);
    $existing = array();
    foreach (db_all('SELECT * FROM #__booking_items WHERE booking_id = ?', array((int) $bookingId)) as $it) {
        $existing[(int) $it['product_id']] = $it;
    }
    db_exec('DELETE FROM #__booking_items WHERE booking_id = ?', array((int) $bookingId));
    $total = 0.0;
    $deposit = 0.0;
    if ($wanted) {
        $ids = array_map('intval', array_keys($wanted));
        $products = array();
        foreach (db_all('SELECT id, name, price_day, deposit FROM #__products WHERE id IN (' . db_in($ids) . ')', $ids) as $p) {
            $products[(int) $p['id']] = $p;
        }
        foreach ($wanted as $pid => $qty) {
            $pid = (int) $pid;
            $qty = (int) $qty;
            if ($qty <= 0) {
                continue;
            }
            if (isset($products[$pid])) {
                $name = $products[$pid]['name'];
                $price = (float) $products[$pid]['price_day'];
                $dep = (float) $products[$pid]['deposit'];
            } elseif (isset($existing[$pid])) {
                $name = $existing[$pid]['product_name'];
                $price = (float) $existing[$pid]['price_day'];
                $dep = $existing[$pid]['qty'] ? (float) $existing[$pid]['deposit'] / $existing[$pid]['qty'] : 0;
            } else {
                continue;
            }
            // Bestehende Positionen behalten ihren vereinbarten Tagespreis
            if (isset($existing[$pid])) {
                $price = (float) $existing[$pid]['price_day'];
            }
            $line = round($price * $qty * $days, 2);
            $total += $line;
            $deposit += $dep * $qty;
            db_insert('booking_items', array(
                'booking_id' => (int) $bookingId, 'product_id' => $pid, 'product_name' => $name, 'qty' => $qty,
                'price_day' => $price, 'days' => $days, 'line_total' => $line, 'deposit' => round($dep * $qty, 2),
            ));
        }
    }
    return array('total' => round($total, 2), 'deposit' => round($deposit, 2));
}

/** Legt eine Buchung an. $data enthält die Kundendaten, $wanted die Geräte. */
function booking_create(array $data, array $wanted, $from, $to, $status = 'requested', $source = 'web')
{
    $code = booking_new_code();
    $row = array_merge(array(
        'code' => $code, 'token' => bin2hex(random_bytes(16)), 'status' => $status,
        'start_date' => $from, 'end_date' => $to, 'customer_name' => '', 'email' => '', 'phone' => '',
        'organisation' => '', 'event_type' => '', 'handover' => 'pickup', 'delivery_address' => '', 'message' => '',
        'total' => 0, 'deposit_total' => 0, 'price_override' => 0, 'admin_note' => '', 'source' => $source,
        'created_at' => now(), 'updated_at' => now(),
    ), $data);
    $id = db_insert('bookings', $row);
    $sums = booking_write_items($id, $wanted, $from, $to);
    db_update('bookings', array('total' => $sums['total'], 'deposit_total' => $sums['deposit']), 'id = ?', array($id));
    booking_log($id, 'created', $source === 'web' ? 'Anfrage über die Website' : 'Im Backend angelegt');
    return $id;
}

/** Statuswechsel mit Protokoll. */
function booking_set_status($id, $status, $note = '')
{
    $statuses = booking_statuses();
    if (!isset($statuses[$status])) {
        return false;
    }
    db_update('bookings', array('status' => $status, 'updated_at' => now()), 'id = ?', array((int) $id));
    booking_log($id, 'status:' . $status, $note);
    return true;
}

function booking_items_wanted(array $booking)
{
    $wanted = array();
    foreach ($booking['items'] as $it) {
        if ($it['product_id']) {
            $wanted[(int) $it['product_id']] = (isset($wanted[(int) $it['product_id']]) ? $wanted[(int) $it['product_id']] : 0) + (int) $it['qty'];
        }
    }
    return $wanted;
}

function log_action_label($action)
{
    if (strpos($action, 'status:') === 0) {
        return 'Status → ' . status_label(substr($action, 7));
    }
    $map = array('created' => 'Angelegt', 'updated' => 'Bearbeitet', 'mail' => 'E-Mail gesendet', 'note' => 'Notiz', 'customer_cancel' => 'Vom Kunden storniert');
    return isset($map[$action]) ? $map[$action] : $action;
}
