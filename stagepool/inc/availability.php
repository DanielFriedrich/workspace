<?php
if (!defined('SP_APP')) { exit; }

/**
 * Verfügbarkeitslogik
 * -------------------
 * - Eine Buchung mit Status "angefragt", "bestätigt" oder "ausgegeben" belegt
 *   ihre Geräte vom Start- bis zum Enddatum PLUS Puffertage davor und danach
 *   (Einstellung buffer_days, Standard 1 Tag für Abholung/Rückgabe).
 * - Sperrzeiten (blocks) sperren alle Geräte, einen Standort oder ein einzelnes
 *   Gerät komplett – ohne zusätzlichen Puffer.
 * - Geräte können mehrfach vorhanden sein (quantity). Frei ist, was an jedem
 *   Tag des Zeitraums noch übrig ist.
 */

function buffer_days()
{
    return max(0, setting_int('buffer_days', 1));
}

/** Lädt alle relevanten Buchungspositionen und Sperrzeiten für einen Zeitraum. */
function avail_load($from, $to, $excludeBookingId = 0)
{
    $buf = buffer_days();
    $statuses = blocking_statuses();
    $params = array_merge($statuses, array(date_add_days($to, $buf), date_add_days($from, -$buf), (int) $excludeBookingId));
    $items = db_all(
        'SELECT bi.product_id, bi.qty, b.id AS booking_id, b.code, b.status, b.start_date, b.end_date, b.customer_name
           FROM #__booking_items bi
           JOIN #__bookings b ON b.id = bi.booking_id
          WHERE b.status IN (' . db_in($statuses) . ')
            AND b.start_date <= ? AND b.end_date >= ? AND b.id <> ?',
        $params
    );
    $byProduct = array();
    foreach ($items as $it) {
        $byProduct[(int) $it['product_id']][] = $it;
    }
    $blocks = db_all('SELECT * FROM #__blocks WHERE start_date <= ? AND end_date >= ?', array($to, $from));
    // Stückzahl je Gerät und Standort (für Standort-Sperren)
    $stock = array();
    foreach (db_all('SELECT product_id, location_id, SUM(quantity) AS qty FROM #__product_stock GROUP BY product_id, location_id') as $r) {
        $stock[(int) $r['product_id']][(int) $r['location_id']] = (int) $r['qty'];
    }
    return array('items' => $byProduct, 'blocks' => $blocks, 'buffer' => $buf, 'stock' => $stock);
}

/**
 * Sperrzeiten, die ein Produkt betreffen. 'cap' = gesperrte Stückzahl
 * (null = komplett gesperrt; bei Standort-Sperren nur die Exemplare dort).
 */
function avail_blocks_for(array $product, array $blocks, array $stockByLocation = null)
{
    $out = array();
    foreach ($blocks as $bl) {
        if ($bl['scope'] === 'all' || ($bl['scope'] === 'product' && (int) $bl['ref_id'] === (int) $product['id'])) {
            $bl['cap'] = null;
            $out[] = $bl;
        } elseif ($bl['scope'] === 'location') {
            $lid = (int) $bl['ref_id'];
            if ($stockByLocation) {
                if (!empty($stockByLocation[$lid])) {
                    $bl['cap'] = (int) $stockByLocation[$lid];
                    $out[] = $bl;
                }
            } elseif ($lid === (int) $product['location_id'] && $product['location_id']) {
                $bl['cap'] = null;
                $out[] = $bl;
            }
        }
    }
    return $out;
}

/**
 * Tagesraster für Kalender und Belegungsplan.
 * Rückgabe: [product_id][Y-m-d] => [state, free, qty, entries]
 * state: free | partial | buffer | requested | booked | blocked
 */
function avail_grid(array $products, $from, $to, $excludeBookingId = 0, $data = null)
{
    if ($data === null) {
        $data = avail_load($from, $to, $excludeBookingId);
    }
    $buf = $data['buffer'];
    $days = date_range($from, $to);
    $grid = array();
    foreach ($products as $p) {
        $pid = (int) $p['id'];
        $qty = max(1, (int) $p['quantity']);
        $items = isset($data['items'][$pid]) ? $data['items'][$pid] : array();
        $blocks = avail_blocks_for($p, $data['blocks'], isset($data['stock'][$pid]) ? $data['stock'][$pid] : null);
        foreach ($items as $k => $it) {
            $items[$k]['b_from'] = date_add_days($it['start_date'], -$buf);
            $items[$k]['b_to'] = date_add_days($it['end_date'], $buf);
        }
        foreach ($days as $d) {
            $blockedBy = null;
            $reduce = 0;
            $reducedLocs = array();
            foreach ($blocks as $bl) {
                if ($bl['start_date'] <= $d && $bl['end_date'] >= $d) {
                    if ($bl['cap'] === null) {
                        $blockedBy = $bl;
                        break;
                    }
                    if (!isset($reducedLocs[(int) $bl['ref_id']])) {
                        $reducedLocs[(int) $bl['ref_id']] = true;
                        $reduce += $bl['cap'];
                        $blockedBy = $blockedBy ?: array('reason' => $bl['reason'], 'partial' => true);
                    }
                }
            }
            $cap = $blockedBy && empty($blockedBy['partial']) ? 0 : max(0, $qty - $reduce);
            if ($cap <= 0 && $blockedBy) {
                $blockedBy['partial'] = false;
            }
            $usedReal = 0;
            $usedBuf = 0;
            $firm = false;
            $entries = array();
            foreach ($items as $it) {
                if ($it['b_from'] <= $d && $it['b_to'] >= $d) {
                    $usedBuf += (int) $it['qty'];
                    $isReal = $it['start_date'] <= $d && $it['end_date'] >= $d;
                    if ($isReal) {
                        $usedReal += (int) $it['qty'];
                        if (in_array($it['status'], array('confirmed', 'picked_up'), true)) {
                            $firm = true;
                        }
                    }
                    $entries[] = array(
                        'booking_id' => (int) $it['booking_id'], 'code' => $it['code'], 'status' => $it['status'],
                        'customer' => $it['customer_name'], 'qty' => (int) $it['qty'], 'buffer' => !$isReal,
                    );
                }
            }
            if ($blockedBy && empty($blockedBy['partial'])) {
                $state = 'blocked';
                $free = 0;
            } else {
                $free = max(0, $cap - $usedBuf);
                if ($usedBuf >= $cap) {
                    $state = $usedReal >= $cap ? ($firm ? 'booked' : 'requested') : 'buffer';
                } elseif ($usedBuf > 0 || $cap < $qty) {
                    $state = 'partial';
                } else {
                    $state = 'free';
                }
            }
            $grid[$pid][$d] = array(
                'state' => $state, 'free' => $free, 'qty' => $qty, 'entries' => $entries,
                'block' => $blockedBy ? $blockedBy['reason'] : '',
            );
        }
    }
    return $grid;
}

/** Wie viele Exemplare sind im GANZEN Zeitraum frei? [product_id => Anzahl] */
function avail_free(array $products, $from, $to, $excludeBookingId = 0)
{
    $grid = avail_grid($products, $from, $to, $excludeBookingId);
    $out = array();
    foreach ($products as $p) {
        $pid = (int) $p['id'];
        $min = max(1, (int) $p['quantity']);
        if (isset($grid[$pid])) {
            foreach ($grid[$pid] as $cell) {
                $min = min($min, $cell['free']);
            }
        }
        $out[$pid] = $min;
    }
    return $out;
}

/**
 * Prüft gewünschte Mengen [product_id => qty] für einen Zeitraum.
 * Gibt eine Liste von Konflikten zurück (leer = alles verfügbar).
 */
function avail_check(array $wanted, $from, $to, $excludeBookingId = 0)
{
    if (!$wanted) {
        return array();
    }
    $ids = array_map('intval', array_keys($wanted));
    $products = db_all('SELECT id, name, quantity, location_id, active FROM #__products WHERE id IN (' . db_in($ids) . ')', $ids);
    $free = avail_free($products, $from, $to, $excludeBookingId);
    $conflicts = array();
    $known = array();
    foreach ($products as $p) {
        $pid = (int) $p['id'];
        $known[$pid] = true;
        if ((int) $wanted[$pid] > $free[$pid]) {
            $conflicts[$pid] = array('name' => $p['name'], 'wanted' => (int) $wanted[$pid], 'free' => $free[$pid]);
        }
    }
    foreach ($ids as $pid) {
        if (!isset($known[$pid])) {
            $conflicts[$pid] = array('name' => 'Gelöschtes Gerät', 'wanted' => (int) $wanted[$pid], 'free' => 0);
        }
    }
    return $conflicts;
}

/** Erster buchbarer Tag (heute + Vorlauf). */
function earliest_start()
{
    return date_add_days(today(), max(0, setting_int('lead_days', 1)));
}

/**
 * Prüft einen Wunschzeitraum. Gibt eine Fehlermeldung oder null zurück.
 * $admin = true erlaubt auch Zeiträume in der Vergangenheit / ohne Vorlauf.
 */
function validate_period($from, $to, $admin = false)
{
    if (!$from || !$to) {
        return 'Bitte gib ein gültiges Start- und Enddatum an.';
    }
    if ($to < $from) {
        return 'Das Enddatum liegt vor dem Startdatum.';
    }
    $days = days_inclusive($from, $to);
    if ($admin) {
        return $days > 366 ? 'Der Zeitraum ist zu lang (max. 1 Jahr).' : null;
    }
    if ($from < earliest_start()) {
        return 'Der früheste mögliche Mietbeginn ist ' . date_de(earliest_start(), true) . '.';
    }
    $min = max(1, setting_int('min_days', 1));
    $max = max($min, setting_int('max_days', 21));
    if ($days < $min) {
        return 'Die Mindestmietdauer beträgt ' . plural($min, 'Tag', 'Tage') . '.';
    }
    if ($days > $max) {
        return 'Online sind maximal ' . $max . ' Miettage möglich. Für längere Zeiträume schreib uns gern direkt.';
    }
    if ($from > date_add_days(today(), 540)) {
        return 'So weit im Voraus können wir leider noch nicht planen.';
    }
    return null;
}

/** Beschriftung eines Kalendertags für Tooltips. */
function cell_title($d, array $cell, $admin = false)
{
    $t = weekday_short($d) . ' ' . date('d.m.', strtotime($d)) . ': ';
    switch ($cell['state']) {
        case 'blocked':
            $t .= 'gesperrt' . ($cell['block'] && $admin ? ' (' . $cell['block'] . ')' : '');
            break;
        case 'booked':
            $t .= 'gebucht';
            break;
        case 'requested':
            $t .= 'reserviert (Anfrage offen)';
            break;
        case 'buffer':
            $t .= 'Übergabe-/Puffertag';
            break;
        case 'partial':
            $t .= $cell['free'] . ' von ' . $cell['qty'] . ' frei';
            break;
        default:
            $t .= $cell['qty'] > 1 ? 'alle ' . $cell['qty'] . ' frei' : 'frei';
    }
    if ($admin && $cell['entries']) {
        $parts = array();
        foreach ($cell['entries'] as $en) {
            $parts[] = $en['code'] . ' ' . $en['customer'] . ' (' . $en['qty'] . '×' . ($en['buffer'] ? ', Puffer' : '') . ')';
        }
        $t .= ' – ' . implode(', ', $parts);
    }
    return $t;
}

function state_label($state)
{
    $l = array('free' => 'frei', 'partial' => 'teilweise frei', 'buffer' => 'Puffertag', 'requested' => 'reserviert', 'booked' => 'gebucht', 'blocked' => 'gesperrt');
    return isset($l[$state]) ? $l[$state] : $state;
}
