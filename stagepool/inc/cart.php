<?php
if (!defined('SP_APP')) { exit; }

/**
 * Warenkorb und gewählter Zeitraum werden in der Session gehalten.
 * Ein Warenkorb = eine Veranstaltung = ein Zeitraum.
 */

function period_get()
{
    if (!empty($_SESSION['sp_period']['from']) && !empty($_SESSION['sp_period']['to'])) {
        $p = $_SESSION['sp_period'];
        // Abgelaufene Zeiträume automatisch verwerfen
        if ($p['from'] < today()) {
            unset($_SESSION['sp_period']);
            return null;
        }
        $p['days'] = days_inclusive($p['from'], $p['to']);
        return $p;
    }
    return null;
}

/** Setzt den Zeitraum. Gibt eine Fehlermeldung oder null zurück. */
function period_set($from, $to)
{
    $from = parse_date($from);
    $to = parse_date($to);
    if ($from && !$to) {
        $to = $from;
    }
    $error = validate_period($from, $to);
    if ($error) {
        return $error;
    }
    $_SESSION['sp_period'] = array('from' => $from, 'to' => $to);
    return null;
}

function period_clear()
{
    unset($_SESSION['sp_period']);
}

function cart_items()
{
    return isset($_SESSION['sp_cart']) && is_array($_SESSION['sp_cart']) ? $_SESSION['sp_cart'] : array();
}

function cart_set($productId, $qty)
{
    $productId = (int) $productId;
    $qty = (int) $qty;
    if ($qty <= 0) {
        unset($_SESSION['sp_cart'][$productId]);
    } else {
        $_SESSION['sp_cart'][$productId] = min($qty, 99);
    }
}

function cart_add($productId, $qty = 1)
{
    $items = cart_items();
    $current = isset($items[(int) $productId]) ? $items[(int) $productId] : 0;
    cart_set($productId, $current + max(1, (int) $qty));
}

function cart_clear()
{
    unset($_SESSION['sp_cart']);
}

function cart_count()
{
    return array_sum(cart_items());
}

/** Lädt Produktdaten zum Warenkorb inkl. Preisberechnung und Verfügbarkeit. */
function cart_details()
{
    $items = cart_items();
    $period = period_get();
    $result = array('lines' => array(), 'total' => 0.0, 'deposit' => 0.0, 'days' => $period ? $period['days'] : 0,
        'period' => $period, 'conflicts' => 0, 'locations' => array());
    if (!$items) {
        return $result;
    }
    $ids = array_map('intval', array_keys($items));
    $products = db_all(
        'SELECT p.*, c.name AS category_name, c.icon AS category_icon, c.color AS category_color,
                l.name AS location_name, l.city AS location_city
           FROM #__products p
           LEFT JOIN #__categories c ON c.id = p.category_id
           LEFT JOIN #__locations l ON l.id = p.location_id
          WHERE p.id IN (' . db_in($ids) . ') AND p.active = 1',
        $ids
    );
    $free = $period ? avail_free($products, $period['from'], $period['to']) : array();
    $found = array();
    foreach ($products as $p) {
        $pid = (int) $p['id'];
        $found[$pid] = true;
        $qty = min($items[$pid], max(1, (int) $p['quantity']));
        if ($qty !== $items[$pid]) {
            cart_set($pid, $qty);
        }
        $days = $period ? $period['days'] : 1;
        $line = $period ? rental_price($p['price_day'], $days, $qty) : (float) $p['price_day'] * $qty;
        $avail = $period ? $free[$pid] : null;
        $ok = $avail === null || $avail >= $qty;
        if (!$ok) {
            $result['conflicts']++;
        }
        $result['lines'][] = array('product' => $p, 'qty' => $qty, 'line' => $line, 'free' => $avail, 'ok' => $ok,
            'deposit' => (float) $p['deposit'] * $qty);
        $result['total'] += $line;
        $result['deposit'] += (float) $p['deposit'] * $qty;
        $locKey = $p['location_name'] ? $p['location_name'] : 'Nach Absprache';
        if (!isset($result['locations'][$locKey])) {
            $result['locations'][$locKey] = 0;
        }
        $result['locations'][$locKey] += $qty;
    }
    // Nicht mehr vorhandene / deaktivierte Geräte entfernen
    foreach ($ids as $pid) {
        if (!isset($found[$pid])) {
            cart_set($pid, 0);
        }
    }
    return $result;
}
