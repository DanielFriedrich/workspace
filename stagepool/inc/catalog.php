<?php
if (!defined('SP_APP')) { exit; }

/** Produkte, Kategorien, Standorte – gemeinsame Abfragen und Bausteine. */

function catalog_products($onlyActive = true)
{
    return db_all(
        'SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon, c.color AS category_color,
                l.name AS location_name, l.city AS location_city, u.name AS owner_name
           FROM #__products p
           LEFT JOIN #__categories c ON c.id = p.category_id
           LEFT JOIN #__locations l ON l.id = p.location_id
           LEFT JOIN #__users u ON u.id = p.owner_id'
        . ($onlyActive ? ' WHERE p.active = 1' : '')
        . ' ORDER BY c.sort, c.name, p.sort, p.name'
    );
}

function product_find($id, $onlyActive = true)
{
    return db_one(
        'SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon, c.color AS category_color,
                l.name AS location_name, l.street AS location_street, l.zip AS location_zip, l.city AS location_city,
                l.contact AS location_contact
           FROM #__products p
           LEFT JOIN #__categories c ON c.id = p.category_id
           LEFT JOIN #__locations l ON l.id = p.location_id
          WHERE p.id = ?' . ($onlyActive ? ' AND p.active = 1' : ''),
        array((int) $id)
    );
}

function categories_all()
{
    return db_all('SELECT * FROM #__categories ORDER BY sort, name');
}

function locations_all($onlyActive = true)
{
    return db_all('SELECT * FROM #__locations' . ($onlyActive ? ' WHERE active = 1' : '') . ' ORDER BY sort, name');
}

function users_all()
{
    return db_all('SELECT id, name FROM #__users WHERE active = 1 ORDER BY name');
}

function accent($p)
{
    $c = isset($p['category_color']) ? $p['category_color'] : '';
    return preg_match('/^#[0-9a-fA-F]{6}$/', (string) $c) ? $c : '#ff2e93';
}

/** Bild oder gestalteter Platzhalter mit Kategorie-Icon. */
function product_media(array $p, $class = '')
{
    $icon = !empty($p['category_icon']) ? $p['category_icon'] : 'box';
    if (!empty($p['image']) && is_file(SP_ROOT . '/uploads/products/' . $p['image'])) {
        return '<div class="media ' . e($class) . '"><img src="' . e(upload_url($p['image'])) . '" alt="' . e($p['name']) . '" loading="lazy"></div>';
    }
    return '<div class="media media-placeholder ' . e($class) . '" aria-hidden="true">'
        . '<span class="ph-beam"></span><span class="ph-glow"></span>' . icon($icon, 'ph-icon') . '</div>';
}

/** Verfügbarkeitshinweis für eine Produktkarte. */
function availability_pill($free, $qty)
{
    if ($free === null) {
        return '';
    }
    if ($free <= 0) {
        return '<span class="pill pill-no">' . icon('ban') . 'belegt</span>';
    }
    if ($qty > 1) {
        return '<span class="pill pill-ok">' . icon('check') . $free . ' von ' . $qty . ' frei</span>';
    }
    return '<span class="pill pill-ok">' . icon('check') . 'verfügbar</span>';
}

/** Rücksprung-URL der aktuellen Seite. */
function current_url()
{
    return isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : url('');
}

/**
 * Monatskalender (Produktseite). $grid = avail_grid()-Ergebnis für dieses Produkt.
 */
function render_month($year, $month, array $grid, $selectable = true)
{
    $first = sprintf('%04d-%02d-01', $year, $month);
    $daysInMonth = (int) date('t', strtotime($first));
    $offset = ((int) date('N', strtotime($first))) - 1; // Montag = 0
    $earliest = earliest_start();
    $today = today();
    $h = '<div class="month"><div class="month-title">' . e(month_name($month)) . ' ' . $year . '</div>';
    $h .= '<div class="month-grid"><span class="wd">Mo</span><span class="wd">Di</span><span class="wd">Mi</span><span class="wd">Do</span><span class="wd">Fr</span><span class="wd">Sa</span><span class="wd">So</span>';
    for ($i = 0; $i < $offset; $i++) {
        $h .= '<span class="day day-empty"></span>';
    }
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $ymd = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $cell = isset($grid[$ymd]) ? $grid[$ymd] : null;
        $state = $cell ? $cell['state'] : 'free';
        $classes = 'day st-' . $state;
        if ($ymd < $earliest) {
            $classes .= ' day-past';
        }
        if ($ymd === $today) {
            $classes .= ' day-today';
        }
        $title = $cell ? cell_title($ymd, $cell) : '';
        $canPick = $selectable && $ymd >= $earliest && in_array($state, array('free', 'partial'), true);
        if ($canPick) {
            $h .= '<button type="button" class="' . $classes . '" data-date="' . $ymd . '" data-free="' . (int) $cell['free'] . '" title="' . e($title) . '">' . $d . '</button>';
        } else {
            $h .= '<span class="' . $classes . '" data-date="' . $ymd . '" title="' . e($title) . '">' . $d . '</span>';
        }
    }
    return $h . '</div></div>';
}

/** Legende für Kalender und Belegungsplan. */
function calendar_legend($admin = false)
{
    $items = array('free' => 'frei', 'partial' => 'teilweise frei', 'requested' => 'reserviert (angefragt)', 'booked' => 'gebucht', 'buffer' => 'Übergabe-Puffer', 'blocked' => 'gesperrt');
    $h = '<ul class="legend">';
    foreach ($items as $k => $label) {
        $h .= '<li><span class="lg st-' . $k . '"></span>' . e($label) . '</li>';
    }
    return $h . '</ul>';
}

/**
 * Belegungsplan (Zeitleiste Geräte × Tage) – öffentlich und im Backend.
 */
function render_timeline(array $products, $from, $days, $admin = false)
{
    $to = date_add_days($from, $days - 1);
    $grid = avail_grid($products, $from, $to);
    $dates = date_range($from, $to);
    $today = today();
    $earliest = earliest_start();
    $h = '<div class="timeline" style="--days:' . count($dates) . '" data-timeline' . ($admin ? ' data-admin' : '') . '>';
    // Kopfzeile: Monate
    $h .= '<div class="tl-row tl-head tl-months"><div class="tl-name"></div>';
    $curMonth = null;
    $span = 0;
    $monthCells = '';
    foreach ($dates as $i => $d) {
        $m = substr($d, 0, 7);
        if ($m !== $curMonth) {
            if ($curMonth !== null) {
                $monthCells .= '<div class="tl-month" style="grid-column: span ' . $span . '">' . e(month_name((int) substr($curMonth, 5, 2))) . '</div>';
            }
            $curMonth = $m;
            $span = 0;
        }
        $span++;
    }
    $monthCells .= '<div class="tl-month" style="grid-column: span ' . $span . '">' . e(month_name((int) substr($curMonth, 5, 2))) . '</div>';
    $h .= '<div class="tl-cells">' . $monthCells . '</div></div>';
    // Kopfzeile: Tage
    $h .= '<div class="tl-row tl-head"><div class="tl-name">Gerät</div><div class="tl-cells">';
    foreach ($dates as $d) {
        $w = (int) date('N', strtotime($d));
        $h .= '<div class="tl-day' . ($w >= 6 ? ' is-weekend' : '') . ($d === $today ? ' is-today' : '') . '"><small>' . weekday_short($d) . '</small>' . (int) substr($d, 8, 2) . '</div>';
    }
    $h .= '</div></div>';
    $lastCat = false;
    foreach ($products as $p) {
        if ($p['category_name'] !== $lastCat) {
            $lastCat = $p['category_name'];
            $h .= '<div class="tl-group" style="--accent:' . e(accent($p)) . '">' . icon($p['category_icon'] ?: 'box') . e($lastCat ?: 'Ohne Kategorie') . '</div>';
        }
        $pid = (int) $p['id'];
        $link = $admin ? url('admin/produkt.php', array('id' => $pid)) : url('produkt.php', array('id' => $pid));
        $h .= '<div class="tl-row" data-product="' . $pid . '"><div class="tl-name"><a href="' . e($link) . '">' . e($p['name']) . '</a>'
            . ((int) $p['quantity'] > 1 ? '<span class="tl-qty">' . (int) $p['quantity'] . '×</span>' : '')
            . ($p['location_name'] ? '<small>' . e($p['location_name']) . '</small>' : '') . '</div><div class="tl-cells">';
        foreach ($dates as $d) {
            $cell = $grid[$pid][$d];
            $w = (int) date('N', strtotime($d));
            $cls = 'tl-cell st-' . $cell['state'] . ($w >= 6 ? ' is-weekend' : '') . ($d === $today ? ' is-today' : '') . ($d < $earliest ? ' is-past' : '');
            $label = '';
            if ($admin && $cell['entries']) {
                $first = $cell['entries'][0];
                $prev = date_add_days($d, -1);
                $isStart = $d === $from || empty($grid[$pid][$prev]['entries']) || $grid[$pid][$prev]['entries'][0]['booking_id'] !== $first['booking_id'];
                if ($isStart && !$first['buffer']) {
                    $label = '<em>' . e($first['code']) . '</em>';
                }
                $h .= '<a class="' . $cls . '" href="' . e(url('admin/buchung.php', array('id' => $first['booking_id']))) . '" title="' . e(cell_title($d, $cell, true)) . '">' . $label . '</a>';
                continue;
            }
            if ($admin && $cell['state'] === 'blocked') {
                $h .= '<a class="' . $cls . '" href="' . e(url('admin/sperrzeiten.php')) . '" title="' . e(cell_title($d, $cell, true)) . '"></a>';
                continue;
            }
            $h .= '<span class="' . $cls . '" data-date="' . $d . '" title="' . e(cell_title($d, $cell, $admin)) . '">'
                . ($cell['state'] === 'partial' ? '<i>' . $cell['free'] . '</i>' : '') . '</span>';
        }
        $h .= '</div></div>';
    }
    return $h . '</div>';
}
