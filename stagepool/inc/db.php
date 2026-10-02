<?php
if (!defined('SP_APP')) { exit; }

/**
 * Schlanke PDO-Schicht für MySQL/MariaDB und SQLite.
 * In SQL-Strings steht "#__" für das Tabellen-Präfix (z. B. "sp_").
 */

function db_connect(array $c)
{
    $options = array(
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    );
    if ($c['driver'] === 'sqlite') {
        $path = $c['path'];
        if ($path !== '' && $path[0] !== '/' && !preg_match('#^[A-Za-z]:[\\\\/]#', $path)) {
            $path = SP_ROOT . '/' . $path;
        }
        $pdo = new PDO('sqlite:' . $path, null, null, $options);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 5000');
        return $pdo;
    }
    // Verbindung per Socket (z. B. Synology MariaDB 10: /run/mysqld/mysqld10.sock) oder per Host/Port
    $socket = !empty($c['socket']) ? $c['socket'] : (isset($c['host']) && strpos($c['host'], '/') === 0 ? $c['host'] : '');
    if ($socket !== '') {
        $dsn = 'mysql:unix_socket=' . $socket . ';dbname=' . $c['name'] . ';charset=utf8mb4';
    } else {
        $dsn = 'mysql:host=' . $c['host'] . ';port=' . (int) (!empty($c['port']) ? $c['port'] : 3306)
            . ';dbname=' . $c['name'] . ';charset=utf8mb4';
    }
    $pdo = new PDO($dsn, $c['user'], $c['pass'], $options);
    $pdo->exec("SET NAMES utf8mb4");
    $pdo->exec("SET time_zone = '" . date('P') . "'");
    return $pdo;
}

function db()
{
    if (!isset($GLOBALS['sp_pdo'])) {
        $c = cfg('db');
        if (!$c) {
            throw new RuntimeException('Keine Datenbank konfiguriert.');
        }
        $GLOBALS['sp_pdo'] = db_connect($c);
    }
    return $GLOBALS['sp_pdo'];
}

function db_ready()
{
    return isset($GLOBALS['sp_pdo']) || cfg('db') !== null;
}

function db_driver()
{
    return cfg('db.driver', 'mysql');
}

function db_sql($sql)
{
    return str_replace('#__', (string) cfg('db.prefix', 'sp_'), $sql);
}

function db_query($sql, array $params = array())
{
    $stmt = db()->prepare(db_sql($sql));
    $stmt->execute($params);
    return $stmt;
}

function db_all($sql, array $params = array())
{
    return db_query($sql, $params)->fetchAll();
}

function db_one($sql, array $params = array())
{
    $row = db_query($sql, $params)->fetch();
    return $row ? $row : null;
}

function db_value($sql, array $params = array())
{
    $v = db_query($sql, $params)->fetchColumn();
    return $v === false ? null : $v;
}

function db_exec($sql, array $params = array())
{
    return db_query($sql, $params)->rowCount();
}

function db_insert($table, array $data)
{
    $cols = array_keys($data);
    $sql = 'INSERT INTO #__' . $table . ' (' . implode(', ', $cols) . ') VALUES ('
        . implode(', ', array_fill(0, count($cols), '?')) . ')';
    db_query($sql, array_values($data));
    return (int) db()->lastInsertId();
}

function db_update($table, array $data, $where, array $whereParams = array())
{
    $sets = array();
    foreach (array_keys($data) as $col) {
        $sets[] = $col . ' = ?';
    }
    $sql = 'UPDATE #__' . $table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $where;
    return db_exec($sql, array_merge(array_values($data), $whereParams));
}

/** Platzhalter "?, ?, ?" für IN-Listen. */
function db_in(array $values)
{
    return implode(', ', array_fill(0, max(1, count($values)), '?'));
}

/**
 * Exklusive Sperre für Buchungsvorgänge (verhindert Doppelbuchungen bei
 * gleichzeitigen Anfragen). Funktioniert für MySQL und SQLite gleich.
 */
function booking_lock()
{
    $file = SP_ROOT . '/storage/booking.lock';
    $fh = @fopen($file, 'c');
    if ($fh) {
        flock($fh, LOCK_EX);
    }
    return $fh;
}

function booking_unlock($fh)
{
    if ($fh) {
        flock($fh, LOCK_UN);
        fclose($fh);
    }
}
