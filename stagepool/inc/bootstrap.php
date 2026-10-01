<?php
/**
 * Stagepool – zentraler Einstiegspunkt für alle Seiten.
 * Lädt Konfiguration, Datenbank, Session und Hilfsfunktionen.
 * Kompatibel mit PHP 7.3+.
 */

define('SP_APP', true);
define('SP_ROOT', dirname(__DIR__));
define('SP_VERSION', '1.2.0');
define('SP_SCHEMA_VERSION', 3); // bei Schema-Änderungen erhöhen, siehe inc/schema.php

require SP_ROOT . '/inc/functions.php';
require SP_ROOT . '/inc/db.php';
require SP_ROOT . '/inc/icons.php';
require SP_ROOT . '/inc/availability.php';
require SP_ROOT . '/inc/cart.php';
require SP_ROOT . '/inc/mailer.php';
require SP_ROOT . '/inc/auth.php';
require SP_ROOT . '/inc/catalog.php';
require SP_ROOT . '/inc/bookings.php';
require SP_ROOT . '/inc/documents.php';
require SP_ROOT . '/inc/accounting.php';
require SP_ROOT . '/inc/stock.php';

$configFile = SP_ROOT . '/config/config.php';
if (!is_file($configFile)) {
    if (!defined('SP_INSTALLER')) {
        header('Location: ' . base_path() . 'install/');
        exit;
    }
    $GLOBALS['sp_config'] = array();
} else {
    $GLOBALS['sp_config'] = require $configFile;
}

date_default_timezone_set(cfg('timezone', 'Europe/Berlin'));

// Nach einem Update: Datenbank automatisch auf den neuesten Stand bringen.
if (!defined('SP_INSTALLER') && cfg('db') && (int) setting('schema_version', '0') < SP_SCHEMA_VERSION) {
    require_once SP_ROOT . '/inc/schema.php';
    schema_migrate();
    settings_all(true);
}
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

// Fehlerbehandlung: im Live-Betrieb nichts anzeigen, sondern protokollieren.
if (cfg('debug', false)) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    if (is_dir(SP_ROOT . '/storage/logs') && is_writable(SP_ROOT . '/storage/logs')) {
        ini_set('error_log', SP_ROOT . '/storage/logs/php-error.log');
    }
    set_exception_handler('sp_exception_handler');
}

// Session mit sicheren Cookie-Einstellungen (PHP 7.3: Options-Array inkl. SameSite).
if (session_status() !== PHP_SESSION_ACTIVE && PHP_SAPI !== 'cli') {
    session_name('sp_session');
    session_set_cookie_params(array(
        'lifetime' => 0,
        'path'     => base_path(),
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ));
    session_start();
}

if (PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
}
