<?php
if (!defined('SP_APP')) { exit; }

/**
 * Preisstufen & Bestand
 * ---------------------
 * Preise: Jedes Gerät hat einen internen Teampreis (price_internal) und einen
 * Endkundenpreis (price_day). Der Endkundenpreis kann automatisch aus dem
 * internen Preis + Aufschlag (Einstellung customer_markup) berechnet werden.
 * Eingeloggte Teammitglieder sehen und mieten zum internen Preis.
 *
 * Bestand: Ein Gerät (z. B. „LED-PAR RGBW“) kann aus mehreren Bestandsposten
 * bestehen – je Eigentümer und Standort eine Zeile mit Stückzahl. Für die
 * Kunden zählt nur die Gesamtzahl. Bei jeder Buchung wird automatisch
 * zugeteilt, welche Exemplare (wessen, von welchem Standort) rausgehen.
 */

/* ------------------------------------------------------------------
 * Preise
 * ------------------------------------------------------------------ */

function customer_markup()
{
    return max(0, (float) str_replace(',', '.', setting('customer_markup', '25')));
}

/** Endkundenpreis aus internem Preis (auf 0,50 € gerundet). */
function markup_price($internal)
{
    return round((float) $internal * (1 + customer_markup() / 100) * 2) / 2;
}

/** Interner Preis; ist keiner hinterlegt, wird er aus dem Endkundenpreis zurückgerechnet. */
function internal_price(array $p)
{
    if (isset($p['price_internal']) && (float) $p['price_internal'] > 0) {
        return (float) $p['price_internal'];
    }
    return round((float) $p['price_day'] / (1 + customer_markup() / 100), 2);
}

/** Preisstufe des aktuellen Besuchers: eingeloggtes Team = intern. */
function price_tier()
{
    return PHP_SAPI !== 'cli' && current_user() ? 'internal' : 'customer';
}

function unit_price(array $p, $tier = null)
{
    $tier = $tier === null ? price_tier() : $tier;
    return $tier === 'internal' ? internal_price($p) : (float) $p['price_day'];
}

function tier_label($tier)
{
    return $tier === 'internal' ? 'Interne Vermietung (Teampreis)' : 'Endkunde';
}

/** Nach Änderung des Aufschlags alle automatisch berechneten Endkundenpreise anpassen. */
function reprice_auto_products()
{
    $n = 0;
    foreach (db_all('SELECT id, price_internal FROM #__products WHERE price_auto = 1 AND price_internal > 0') as $p) {
        db_update('products', array('price_day' => markup_price($p['price_internal'])), 'id = ?', array($p['id']));
        $n++;
    }
    return $n;
}

/* ------------------------------------------------------------------
 * Bestand
 * ------------------------------------------------------------------ */

/** Bestandsposten eines Geräts mit Eigentümer- und Standortnamen. */
function stock_rows($productId)
{
    return db_all(
        'SELECT s.*, u.name AS owner_name, l.name AS location_name, l.city AS location_city
           FROM #__product_stock s
           LEFT JOIN #__users u ON u.id = s.owner_id
           LEFT JOIN #__locations l ON l.id = s.location_id
          WHERE s.product_id = ? ORDER BY s.sort, s.id',
        array((int) $productId)
    );
}

/** Alle Bestandsposten, gruppiert nach Gerät. */
function stock_map()
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }
    $map = array();
    foreach (db_all('SELECT s.*, u.name AS owner_name, l.name AS location_name, l.city AS location_city
                       FROM #__product_stock s
                       LEFT JOIN #__users u ON u.id = s.owner_id
                       LEFT JOIN #__locations l ON l.id = s.location_id
                      WHERE s.quantity > 0 ORDER BY s.sort, s.id') as $r) {
        $map[(int) $r['product_id']][] = $r;
    }
    return $map;
}

/** Standorte eines Geräts: [location_id => ['name' => …, 'qty' => …]] */
function product_locations($productId)
{
    $map = stock_map();
    $out = array();
    if (isset($map[(int) $productId])) {
        foreach ($map[(int) $productId] as $r) {
            $lid = (int) $r['location_id'];
            if (!isset($out[$lid])) {
                $out[$lid] = array('name' => $r['location_name'] ?: 'nach Absprache', 'city' => $r['location_city'], 'qty' => 0);
            }
            $out[$lid]['qty'] += (int) $r['quantity'];
        }
    }
    return $out;
}

/** Kurztext für Karten: „Werkstatt Mitte“ oder „Werkstatt Mitte +1“. */
function product_location_label(array $p)
{
    $locs = product_locations($p['id']);
    if (!$locs) {
        return $p['location_name'] ?: 'Standort nach Absprache';
    }
    $first = reset($locs);
    return $first['name'] . (count($locs) > 1 ? ' +' . (count($locs) - 1) : '');
}

/**
 * Speichert die Bestandsposten eines Geräts.
 * $rows = [['id' => 0|id, 'owner_id' => …, 'location_id' => …, 'quantity' => …, 'note' => …], …]
 * Gibt eine Liste von Hinweisen zurück.
 */
function stock_save($productId, array $rows)
{
    $notes = array();
    $keep = array();
    $sort = 0;
    foreach ($rows as $r) {
        $id = (int) $r['id'];
        $qty = max(0, (int) $r['quantity']);
        $data = array(
            'owner_id' => (int) $r['owner_id'] ?: null, 'location_id' => (int) $r['location_id'] ?: null,
            'quantity' => $qty, 'note' => trim((string) $r['note']), 'sort' => ($sort++) * 10,
        );
        if ($id) {
            $exists = db_value('SELECT COUNT(*) FROM #__product_stock WHERE id = ? AND product_id = ?', array($id, (int) $productId));
            if (!$exists) {
                continue;
            }
            if ($qty === 0 && !(int) db_value('SELECT COUNT(*) FROM #__booking_allocations WHERE stock_id = ?', array($id))) {
                db_exec('DELETE FROM #__product_stock WHERE id = ?', array($id));
                continue;
            }
            if ($qty === 0) {
                $notes[] = 'Ein Bestandsposten hat Buchungen und bleibt mit 0 Stück erhalten.';
            }
            db_update('product_stock', $data, 'id = ?', array($id));
            $keep[] = $id;
        } elseif ($qty > 0) {
            $data['product_id'] = (int) $productId;
            $keep[] = db_insert('product_stock', $data);
        }
    }
    stock_sync_product($productId);
    return $notes;
}

/** Gesamtstückzahl und „Haupt“-Standort/-Eigentümer am Gerät aktualisieren. */
function stock_sync_product($productId)
{
    $rows = db_all('SELECT * FROM #__product_stock WHERE product_id = ? ORDER BY quantity DESC, sort, id', array((int) $productId));
    $total = 0;
    foreach ($rows as $r) {
        $total += (int) $r['quantity'];
    }
    $main = $rows ? $rows[0] : null;
    db_update('products', array(
        'quantity' => max(0, $total),
        'location_id' => $main ? $main['location_id'] : null,
        'owner_id' => $main ? $main['owner_id'] : null,
    ), 'id = ?', array((int) $productId));
}

/* ------------------------------------------------------------------
 * Zuteilung: Welche Exemplare gehen bei einer Buchung raus?
 * ------------------------------------------------------------------ */

/** Freie Stückzahl je Bestandsposten im Zeitraum (inkl. Puffer und Standort-Sperren). */
function stock_free($productId, $from, $to, $excludeBookingId = 0)
{
    $buf = buffer_days();
    $stocks = db_all('SELECT * FROM #__product_stock WHERE product_id = ? ORDER BY sort, id', array((int) $productId));
    $statuses = blocking_statuses();
    $allocs = db_all(
        'SELECT a.stock_id, a.qty, b.start_date, b.end_date FROM #__booking_allocations a
           JOIN #__bookings b ON b.id = a.booking_id
          WHERE b.status IN (' . db_in($statuses) . ') AND b.id <> ? AND b.start_date <= ? AND b.end_date >= ?',
        array_merge($statuses, array((int) $excludeBookingId, date_add_days($to, $buf), date_add_days($from, -$buf)))
    );
    $blocks = db_all('SELECT * FROM #__blocks WHERE start_date <= ? AND end_date >= ?', array($to, $from));
    $free = array();
    foreach ($stocks as $s) {
        $sid = (int) $s['id'];
        $min = (int) $s['quantity'];
        foreach (date_range($from, $to) as $d) {
            $blocked = false;
            foreach ($blocks as $bl) {
                if ($bl['start_date'] <= $d && $bl['end_date'] >= $d
                    && ($bl['scope'] === 'all' || ($bl['scope'] === 'product' && (int) $bl['ref_id'] === (int) $productId)
                        || ($bl['scope'] === 'location' && (int) $bl['ref_id'] === (int) $s['location_id'] && $s['location_id']))) {
                    $blocked = true;
                    break;
                }
            }
            if ($blocked) {
                $min = 0;
                break;
            }
            $used = 0;
            foreach ($allocs as $a) {
                if ((int) $a['stock_id'] === $sid && date_add_days($a['start_date'], -$buf) <= $d && date_add_days($a['end_date'], $buf) >= $d) {
                    $used += (int) $a['qty'];
                }
            }
            $min = min($min, max(0, (int) $s['quantity'] - $used));
        }
        $free[$sid] = array('free' => $min, 'stock' => $s);
    }
    return $free;
}

/**
 * Teilt alle Positionen einer Buchung automatisch auf Bestandsposten auf.
 * Bevorzugt Standorte, die in der Buchung schon vorkommen (weniger Abholwege).
 */
function booking_allocate($bookingId)
{
    $b = db_one('SELECT id, start_date, end_date FROM #__bookings WHERE id = ?', array((int) $bookingId));
    if (!$b) {
        return;
    }
    db_exec('DELETE FROM #__booking_allocations WHERE booking_id = ?', array((int) $bookingId));
    $usedLocations = array();
    $items = db_all('SELECT * FROM #__booking_items WHERE booking_id = ? AND product_id IS NOT NULL ORDER BY qty DESC, id', array((int) $bookingId));
    foreach ($items as $it) {
        $free = stock_free($it['product_id'], $b['start_date'], $b['end_date'], $b['id']);
        if (!$free) {
            continue;
        }
        uasort($free, function ($x, $y) use ($usedLocations) {
            $px = isset($usedLocations[(int) $x['stock']['location_id']]) ? 1 : 0;
            $py = isset($usedLocations[(int) $y['stock']['location_id']]) ? 1 : 0;
            if ($px !== $py) {
                return $py - $px;
            }
            return $y['free'] - $x['free'];
        });
        $remaining = (int) $it['qty'];
        foreach ($free as $sid => $f) {
            if ($remaining <= 0) {
                break;
            }
            $take = min($remaining, $f['free']);
            if ($take > 0) {
                db_insert('booking_allocations', array('booking_id' => (int) $bookingId, 'booking_item_id' => (int) $it['id'], 'stock_id' => $sid, 'qty' => $take));
                $usedLocations[(int) $f['stock']['location_id']] = true;
                $remaining -= $take;
            }
        }
        // Überbuchung (bewusst gespeichert): Rest dem größten Posten zuordnen
        if ($remaining > 0) {
            $biggest = null;
            foreach ($free as $sid => $f) {
                if ($biggest === null || (int) $f['stock']['quantity'] > (int) $free[$biggest]['stock']['quantity']) {
                    $biggest = $sid;
                }
            }
            db_insert('booking_allocations', array('booking_id' => (int) $bookingId, 'booking_item_id' => (int) $it['id'], 'stock_id' => $biggest, 'qty' => $remaining));
        }
    }
}

/** Zuteilungen einer Buchung: [booking_item_id => [Zeilen mit Eigentümer/Standort]] */
function booking_allocations($bookingId)
{
    $out = array();
    foreach (db_all(
        'SELECT a.*, s.owner_id, s.location_id, u.name AS owner_name, l.name AS location_name, l.street, l.zip, l.city
           FROM #__booking_allocations a
           JOIN #__product_stock s ON s.id = a.stock_id
           LEFT JOIN #__users u ON u.id = s.owner_id
           LEFT JOIN #__locations l ON l.id = s.location_id
          WHERE a.booking_id = ? ORDER BY a.id',
        array((int) $bookingId)
    ) as $r) {
        $out[(int) $r['booking_item_id']][] = $r;
    }
    return $out;
}

/** Abholorte einer Buchung aus den Zuteilungen: [Name => Adresse] */
function booking_pickup_locations($bookingId)
{
    $locs = array();
    foreach (booking_allocations($bookingId) as $rows) {
        foreach ($rows as $r) {
            if ($r['location_name']) {
                $locs[$r['location_name']] = trim($r['location_name'] . ', ' . $r['street'] . ', ' . trim($r['zip'] . ' ' . $r['city']), ', ');
            }
        }
    }
    return $locs;
}

/**
 * Mietumsatz je Eigentümer (ausgegebene und zurückgegebene Buchungen mit Start im Jahr).
 * Manuelle Preisanpassungen werden anteilig berücksichtigt.
 */
function owner_revenue($year)
{
    $bookings = db_all("SELECT id, total, price_tier FROM #__bookings WHERE status IN ('picked_up', 'returned') AND start_date >= ? AND start_date <= ?",
        array($year . '-01-01', $year . '-12-31'));
    $out = array();
    foreach ($bookings as $b) {
        $items = db_all('SELECT id, qty, line_total FROM #__booking_items WHERE booking_id = ?', array($b['id']));
        $sum = 0.0;
        foreach ($items as $it) {
            $sum += (float) $it['line_total'];
        }
        $factor = $sum > 0 ? (float) $b['total'] / $sum : 0;
        $allocs = booking_allocations($b['id']);
        foreach ($items as $it) {
            $value = (float) $it['line_total'] * $factor;
            $rows = isset($allocs[(int) $it['id']]) ? $allocs[(int) $it['id']] : array();
            $qty = 0;
            foreach ($rows as $r) {
                $qty += (int) $r['qty'];
            }
            if (!$rows || $qty <= 0) {
                $rows = array(array('owner_name' => 'Ohne Zuordnung', 'qty' => 1));
                $qty = 1;
            }
            foreach ($rows as $r) {
                $name = $r['owner_name'] ?: 'Gemeinsamer Pool';
                if (!isset($out[$name])) {
                    $out[$name] = array('total' => 0.0, 'internal' => 0.0, 'bookings' => array());
                }
                $share = $value * (int) $r['qty'] / $qty;
                $out[$name]['total'] += $share;
                if ($b['price_tier'] === 'internal') {
                    $out[$name]['internal'] += $share;
                }
                $out[$name]['bookings'][(int) $b['id']] = true;
            }
        }
    }
    uasort($out, function ($a, $b) {
        return $b['total'] <=> $a['total'];
    });
    return $out;
}

/** Steht (mindestens ein Exemplar) des Geräts an diesem Standort? */
function product_at_location(array $p, $locationId)
{
    $locs = product_locations($p['id']);
    return $locs ? isset($locs[(int) $locationId]) : (int) $p['location_id'] === (int) $locationId;
}

function product_has_owner(array $p, $ownerId)
{
    $map = stock_map();
    if (!empty($map[(int) $p['id']])) {
        foreach ($map[(int) $p['id']] as $r) {
            if ((int) $r['owner_id'] === (int) $ownerId) {
                return true;
            }
        }
        return false;
    }
    return (int) $p['owner_id'] === (int) $ownerId;
}

/** Eigentümer-Kurzliste, z. B. „Daniel 4 · Lisa 4“. */
function product_owner_label(array $p)
{
    $map = stock_map();
    if (empty($map[(int) $p['id']])) {
        return $p['owner_name'] ?: '–';
    }
    $own = array();
    foreach ($map[(int) $p['id']] as $r) {
        $n = $r['owner_name'] ?: 'Pool';
        $own[$n] = (isset($own[$n]) ? $own[$n] : 0) + (int) $r['quantity'];
    }
    if (count($own) === 1) {
        return key($own);
    }
    $parts = array();
    foreach ($own as $n => $q) {
        $parts[] = $n . ' ' . $q;
    }
    return implode(' · ', $parts);
}
