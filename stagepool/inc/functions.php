<?php
if (!defined('SP_APP')) { exit; }

/* ------------------------------------------------------------------
 * Konfiguration & Einstellungen
 * ------------------------------------------------------------------ */

function cfg($key, $default = null)
{
    $cfg = isset($GLOBALS['sp_config']) ? $GLOBALS['sp_config'] : array();
    foreach (explode('.', $key) as $part) {
        if (!is_array($cfg) || !array_key_exists($part, $cfg)) {
            return $default;
        }
        $cfg = $cfg[$part];
    }
    return $cfg;
}

/** Standardwerte für alle Einstellungen (werden im Backend gepflegt). */
function settings_defaults()
{
    return array(
        'site_name'       => 'Stagepool',
        'site_tagline'    => 'Licht, Sound & Effekte für kleine Events',
        'hero_text'       => 'Scheinwerfer, Nebel, Funken, Boxen & Mikros aus unserem gemeinsamen Pool – für Geburtstage, Vereinsfeste, Hochzeiten und Partys. Zeitraum wählen, Technik zusammenstellen, unverbindlich anfragen.',
        'contact_email'   => '',
        'contact_phone'   => '',
        'notify_email'    => '',
        'buffer_days'     => '1',
        'lead_days'       => '1',
        'min_days'        => '1',
        'max_days'        => '21',
        'price_note'      => 'Privatvermietung, alle Preise sind Endpreise.',
        'extra_day_percent' => '50',
        'customer_markup' => '25',
        // Firma, Angebote & Rechnungen
        'company_name'    => '',
        'company_address' => '',
        'tax_number'      => '',
        'vat_id'          => '',
        'vat_rate'        => '0',
        'tax_note'        => 'Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.',
        'bank_holder'     => '',
        'bank_name'       => '',
        'iban'            => '',
        'bic'             => '',
        'offer_prefix'    => 'AN',
        'invoice_prefix'  => 'RE',
        'offer_valid_days'=> '14',
        'payment_days'    => '14',
        'offer_text'      => "vielen Dank für deine Anfrage. Gern bieten wir dir die folgende Technik für deine Veranstaltung an. Die Geräte sind bis zur Annahme für dich reserviert.",
        'invoice_text'    => "vielen Dank, dass du unsere Technik gemietet hast. Wir berechnen dir die folgenden Leistungen:",
        'doc_footer'      => '',
        'expense_categories' => "Anschaffung Equipment\nLager & Ausstattung (Regale, Kisten)\nMiete\nVersicherung\nReparatur & Wartung\nVerbrauchsmaterial (Fluid, Granulat …)\nFahrtkosten\nGebühren & Software\nMarketing\nSonstiges",
        'income_categories'  => "Vermietung\nLieferung & Service\nVerkauf (Verbrauchsmaterial)\nSonstiges",
        'pickup_info'     => "Im Normalfall holst du die Technik selbst am angegebenen Standort ab und bringst sie nach dem Event wieder zurück. Den genauen Termin stimmen wir nach der Bestätigung mit dir ab.\n\nLieferung und Aufbau sind nach Absprache möglich – schreib uns dazu einfach eine Nachricht in der Anfrage.",
        'terms'           => "## Mietbedingungen (Entwurf)\n\n- Die Anfrage ist unverbindlich. Die Geräte werden ab Anfrage für dich reserviert und mit unserer Bestätigung fest gebucht.\n- Abgerechnet wird pro Miettag (Abholtag bis Rückgabetag). Ein Puffertag vor und nach dem Zeitraum wird für Abholung und Rückgabe automatisch freigehalten.\n- Bei Abholung kann eine Kaution fällig werden. Sie wird bei vollständiger und unbeschädigter Rückgabe erstattet.\n- Der Mieter haftet für Schäden und Verlust während der Mietzeit.\n- Flammen-, Funken- und Lasergeräte geben wir nur nach Einweisung heraus. Die Genehmigungen am Veranstaltungsort liegen in der Verantwortung des Mieters.",
        'impressum'       => "## Impressum\n\nAngaben gemäß § 5 DDG\n\nName / Firma\nStraße Hausnummer\nPLZ Ort\n\nE-Mail: info@example.de\n\n**Bitte im Backend unter Einstellungen → Rechtliches anpassen.**",
        'privacy'         => "## Datenschutzerklärung\n\nWir verarbeiten die Daten aus deiner Anfrage (Name, E-Mail, Telefon, Nachricht) ausschließlich zur Bearbeitung der Mietanfrage (Art. 6 Abs. 1 lit. b DSGVO).\n\nDiese Website verwendet nur ein technisch notwendiges Session-Cookie (Warenkorb). Es werden keine Tracking- oder Analyse-Dienste eingesetzt; Schriftarten werden lokal ausgeliefert.\n\n**Bitte im Backend unter Einstellungen → Rechtliches vervollständigen.**",
        'mail_mode'       => 'mail',
        'mail_from'       => '',
        'mail_from_name'  => '',
        'smtp_host'       => '',
        'smtp_port'       => '587',
        'smtp_secure'     => 'tls',
        'smtp_user'       => '',
        'smtp_pass'       => '',
    );
}

function settings_all($reload = false)
{
    static $cache = null;
    if ($cache !== null && !$reload) {
        return $cache;
    }
    $cache = settings_defaults();
    if (db_ready()) {
        try {
            foreach (db_all('SELECT name, value FROM #__settings') as $row) {
                $cache[$row['name']] = $row['value'];
            }
        } catch (Exception $e) {
            // Tabelle fehlt noch (Installation) – Standardwerte nutzen.
        }
    }
    return $cache;
}

function setting($key, $default = '')
{
    $all = settings_all();
    return isset($all[$key]) && $all[$key] !== '' ? $all[$key] : $default;
}

function setting_int($key, $default = 0)
{
    return (int) setting($key, (string) $default);
}

function setting_set($key, $value)
{
    $exists = db_value('SELECT COUNT(*) FROM #__settings WHERE name = ?', array($key));
    if ($exists) {
        db_exec('UPDATE #__settings SET value = ? WHERE name = ?', array((string) $value, $key));
    } else {
        db_exec('INSERT INTO #__settings (name, value) VALUES (?, ?)', array($key, (string) $value));
    }
}

/* ------------------------------------------------------------------
 * URLs & Ausgabe
 * ------------------------------------------------------------------ */

function is_https()
{
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }
    return isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443;
}

/** Pfad der Anwendung relativ zur Domain, z. B. "/" oder "/verleih/". */
function base_path()
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $configured = cfg('base_path', '');
    if ($configured !== '' && $configured !== null) {
        $base = '/' . trim($configured, '/') . '/';
        return $base = ($base === '//' ? '/' : $base);
    }
    $base = '/';
    if (!empty($_SERVER['SCRIPT_FILENAME']) && !empty($_SERVER['SCRIPT_NAME'])) {
        $script = str_replace('\\', '/', (string) realpath($_SERVER['SCRIPT_FILENAME']));
        $root = str_replace('\\', '/', (string) realpath(dirname(__DIR__)));
        if ($root !== '' && strpos($script, $root . '/') === 0) {
            $relative = substr($script, strlen($root)); // z. B. /admin/index.php
            $name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
            if (substr($name, -strlen($relative)) === $relative) {
                $base = substr($name, 0, -strlen($relative)) . '/';
            }
        }
    }
    $base = preg_replace('#/+#', '/', $base);
    return $base;
}

/** Absolute URL inkl. Domain (für E-Mails). */
function absolute_url($path = '', $params = array())
{
    $configured = rtrim((string) cfg('site_url', ''), '/');
    if ($configured !== '') {
        $rel = url($path, $params);
        return $configured . '/' . ltrim(substr($rel, strlen(base_path())), '/');
    }
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    return (is_https() ? 'https://' : 'http://') . $host . url($path, $params);
}

function url($path = '', $params = array())
{
    $u = base_path() . ltrim($path, '/');
    $params = array_filter($params, function ($v) {
        return $v !== null && $v !== '' && $v !== false;
    });
    if ($params) {
        $u .= (strpos($u, '?') === false ? '?' : '&') . http_build_query($params);
    }
    return $u;
}

function asset($path)
{
    $file = SP_ROOT . '/assets/' . $path;
    $v = is_file($file) ? filemtime($file) : SP_VERSION;
    return url('assets/' . $path) . '?v=' . $v;
}

function upload_url($file)
{
    return url('uploads/products/' . rawurlencode($file));
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect($path, $params = array())
{
    $target = (strpos($path, '/') === 0 || strpos($path, 'http') === 0) ? $path : url($path, $params);
    header('Location: ' . $target);
    exit;
}

/** Nur interne Rücksprung-Adressen zulassen. */
function safe_return($candidate, $fallback)
{
    $candidate = (string) $candidate;
    if ($candidate !== '' && strpos($candidate, base_path()) === 0 && strpos($candidate, '//') !== 0 && !preg_match('#[\r\n]#', $candidate)) {
        return $candidate;
    }
    return url($fallback);
}

function view($name, $vars = array())
{
    extract($vars, EXTR_SKIP);
    include SP_ROOT . '/inc/views/' . $name . '.php';
}

function json_out($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function sp_exception_handler($ex)
{
    error_log('[Stagepool] ' . get_class($ex) . ': ' . $ex->getMessage() . ' in ' . $ex->getFile() . ':' . $ex->getLine());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!doctype html><meta charset="utf-8"><title>Fehler</title>'
        . '<body style="font-family:system-ui;background:#07070c;color:#eee;display:grid;place-items:center;min-height:100vh;margin:0">'
        . '<div style="max-width:32rem;padding:2rem;text-align:center"><h1>Da ist etwas schiefgelaufen.</h1>'
        . '<p>Bitte versuche es gleich noch einmal. Der Fehler wurde protokolliert.</p></div></body>';
    exit;
}

/* ------------------------------------------------------------------
 * Request-Helfer, Flash-Nachrichten, CSRF
 * ------------------------------------------------------------------ */

function is_post()
{
    return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST';
}

function input($key, $default = '')
{
    if (isset($_POST[$key])) {
        return is_array($_POST[$key]) ? $_POST[$key] : trim((string) $_POST[$key]);
    }
    if (isset($_GET[$key])) {
        return is_array($_GET[$key]) ? $_GET[$key] : trim((string) $_GET[$key]);
    }
    return $default;
}

function flash($type, $message)
{
    $_SESSION['sp_flash'][] = array('type' => $type, 'msg' => $message);
}

function flashes()
{
    $list = isset($_SESSION['sp_flash']) ? $_SESSION['sp_flash'] : array();
    unset($_SESSION['sp_flash']);
    return $list;
}

function csrf_token()
{
    if (empty($_SESSION['sp_csrf'])) {
        $_SESSION['sp_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['sp_csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function csrf_check()
{
    $sent = isset($_POST['_token']) ? (string) $_POST['_token'] : (isset($_GET['_token']) ? (string) $_GET['_token'] : '');
    if ($sent === '' || !hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        flash('error', 'Die Sitzung ist abgelaufen. Bitte versuche es noch einmal.');
        $back = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('');
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        if (parse_url($back, PHP_URL_HOST) && parse_url($back, PHP_URL_HOST) !== $host) {
            $back = url('');
        }
        header('Location: ' . $back);
        exit;
    }
}

/* ------------------------------------------------------------------
 * Formatierung
 * ------------------------------------------------------------------ */

function money($amount, $short = false)
{
    $amount = (float) $amount;
    if ($short && abs($amount - round($amount)) < 0.001) {
        return number_format($amount, 0, ',', '.') . ' €';
    }
    return number_format($amount, 2, ',', '.') . ' €';
}

/**
 * Staffelpreis: Der erste Miettag kostet den vollen Tagespreis, jeder weitere
 * Tag nur einen Anteil davon (Einstellung extra_day_percent, Standard 50 %).
 * Beispiel 100 €/Tag: 1 Tag = 100 €, 2 Tage = 150 €, 3 Tage = 200 €.
 */
function extra_day_percent()
{
    return max(0, min(100, (float) str_replace(',', '.', setting('extra_day_percent', '50'))));
}

function rental_factor($days)
{
    return 1 + max(0, (int) $days - 1) * extra_day_percent() / 100;
}

function rental_price($priceDay, $days, $qty = 1)
{
    return round((float) $priceDay * rental_factor($days) * (int) $qty, 2);
}

/** Kurze Erklärung der Preisstaffel für Hinweise. */
function pricing_hint()
{
    $pct = extra_day_percent();
    if ($pct >= 100) {
        return 'Abgerechnet wird pro Miettag.';
    }
    return '1. Miettag voller Preis, jeder weitere Tag nur ' . str_replace('.', ',', (string) round($pct, 1)) . ' % davon.';
}

function parse_money($value)
{
    $value = trim(str_replace(array('€', ' '), '', (string) $value));
    if (strpos($value, ',') !== false) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
    }
    return round((float) $value, 2);
}

function today()
{
    return date('Y-m-d');
}

/** Prüft ein Datum im Format JJJJ-MM-TT, gibt es normalisiert oder null zurück. */
function parse_date($value)
{
    $value = trim((string) $value);
    if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $value, $m)) {
        $value = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
        return null;
    }
    if (!checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
        return null;
    }
    return $value;
}

function date_add_days($ymd, $days)
{
    $d = new DateTime($ymd);
    $d->modify(($days >= 0 ? '+' : '') . (int) $days . ' days');
    return $d->format('Y-m-d');
}

function days_inclusive($from, $to)
{
    $a = new DateTime($from);
    $b = new DateTime($to);
    return (int) $a->diff($b)->format('%r%a') + 1;
}

function date_range($from, $to)
{
    $out = array();
    $d = $from;
    $guard = 0;
    while ($d <= $to && $guard < 800) {
        $out[] = $d;
        $d = date_add_days($d, 1);
        $guard++;
    }
    return $out;
}

function weekday_short($ymd)
{
    $names = array('So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa');
    return $names[(int) date('w', strtotime($ymd))];
}

function month_name($month)
{
    $names = array(1 => 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember');
    return $names[(int) $month];
}

function date_de($ymd, $withWeekday = false)
{
    if (!$ymd) {
        return '';
    }
    $ts = strtotime($ymd);
    $s = date('d.m.Y', $ts);
    return $withWeekday ? weekday_short($ymd) . ', ' . $s : $s;
}

function datetime_de($dt)
{
    return $dt ? date('d.m.Y, H:i', strtotime($dt)) . ' Uhr' : '';
}

function period_de($from, $to)
{
    if ($from === $to) {
        return weekday_short($from) . ' ' . date('d.m.Y', strtotime($from));
    }
    $sameYear = substr($from, 0, 4) === substr($to, 0, 4);
    return weekday_short($from) . ' ' . date($sameYear ? 'd.m.' : 'd.m.Y', strtotime($from))
        . ' – ' . weekday_short($to) . ' ' . date('d.m.Y', strtotime($to));
}

function plural($n, $one, $many)
{
    return $n . ' ' . ((int) $n === 1 ? $one : $many);
}

function now()
{
    return date('Y-m-d H:i:s');
}

function lower($s)
{
    return function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
}

function excerpt($text, $len = 120)
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)));
    if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $len) {
        return rtrim(mb_substr($text, 0, $len - 1, 'UTF-8')) . '…';
    }
    return strlen($text) > $len ? rtrim(substr($text, 0, $len - 1)) . '…' : $text;
}

function slugify($text)
{
    $text = lower(trim($text));
    $text = strtr($text, array('ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', '&' => '-und-'));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text !== '' ? $text : 'eintrag';
}

function random_code($length = 6)
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $out = '';
    for ($i = 0; $i < $length; $i++) {
        $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $out;
}

/**
 * Sehr kleines Markdown: Absätze, Zeilenumbrüche, ## Überschriften,
 * - Listen, **fett**, *kursiv* und Links. HTML wird immer maskiert.
 */
function md_lite($text)
{
    $text = str_replace(array("\r\n", "\r"), "\n", (string) $text);
    $blocks = preg_split("/\n{2,}/", trim($text));
    $html = '';
    foreach ($blocks as $block) {
        $lines = explode("\n", $block);
        $isList = true;
        foreach ($lines as $l) {
            if (!preg_match('/^\s*[-*]\s+/', $l)) {
                $isList = false;
                break;
            }
        }
        if ($isList) {
            $html .= '<ul>';
            foreach ($lines as $l) {
                $html .= '<li>' . md_inline(preg_replace('/^\s*[-*]\s+/', '', $l)) . '</li>';
            }
            $html .= '</ul>';
            continue;
        }
        if (preg_match('/^(#{2,4})\s+(.*)$/', $lines[0], $m)) {
            $level = strlen($m[1]);
            $html .= '<h' . $level . '>' . md_inline($m[2]) . '</h' . $level . '>';
            array_shift($lines);
            if (!$lines) {
                continue;
            }
        }
        $html .= '<p>' . implode('<br>', array_map('md_inline', $lines)) . '</p>';
    }
    return $html;
}

function md_inline($text)
{
    $t = e($text);
    $t = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $t);
    $t = preg_replace('/(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])/', '<em>$1</em>', $t);
    $t = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\s)]+|mailto:[^\s)]+)\)/', '<a href="$2" rel="noopener">$1</a>', $t);
    $t = preg_replace('/(^|[\s(])(https?:\/\/[^\s<)]+)/', '$1<a href="$2" rel="noopener">$2</a>', $t);
    return $t;
}

/* ------------------------------------------------------------------
 * Buchungsstatus
 * ------------------------------------------------------------------ */

function booking_statuses()
{
    return array(
        'requested' => array('label' => 'Angefragt',      'hint' => 'Reserviert – wartet auf Bestätigung', 'blocks' => true),
        'offered'   => array('label' => 'Angebot gesendet', 'hint' => 'Angebot verschickt – Geräte bleiben reserviert', 'blocks' => true),
        'confirmed' => array('label' => 'Bestätigt',      'hint' => 'Fest gebucht',                       'blocks' => true),
        'picked_up' => array('label' => 'Ausgegeben',     'hint' => 'Technik ist beim Mieter',            'blocks' => true),
        'returned'  => array('label' => 'Zurückgegeben',  'hint' => 'Abgeschlossen, Geräte wieder frei',   'blocks' => false),
        'rejected'  => array('label' => 'Abgelehnt',      'hint' => 'Anfrage abgelehnt, Geräte frei',      'blocks' => false),
        'cancelled' => array('label' => 'Storniert',      'hint' => 'Storniert, Geräte frei',              'blocks' => false),
    );
}

function status_label($status)
{
    $all = booking_statuses();
    return isset($all[$status]) ? $all[$status]['label'] : $status;
}

function status_badge($status)
{
    return '<span class="status status-' . e($status) . '">' . e(status_label($status)) . '</span>';
}

function blocking_statuses()
{
    $out = array();
    foreach (booking_statuses() as $key => $s) {
        if ($s['blocks']) {
            $out[] = $key;
        }
    }
    return $out;
}

function event_types()
{
    return array('Geburtstag', 'Hochzeit', 'Party', 'Firmen- / Teamevent', 'Vereinsfest', 'Konzert / Auftritt', 'Theater / Schule', 'Sonstiges');
}

/** Farbpalette für Kategorien (Akzentfarbe der Karten). */
function category_colors()
{
    return array(
        '#ffc53d' => 'Bernstein',
        '#ff6a2b' => 'Flamme',
        '#ff2e93' => 'Magenta',
        '#b56cff' => 'Violett',
        '#4d7cff' => 'Blau',
        '#22d3ee' => 'Cyan',
        '#3ddc97' => 'Grün',
        '#ffe066' => 'Funke',
        '#9fb3c8' => 'Nebel',
        '#a0a0b8' => 'Silber',
    );
}
