<?php
/**
 * Beispiel-Konfiguration. Normalerweise schreibt der Installations-Assistent
 * (/install) die Datei config/config.php automatisch. Für eine manuelle
 * Einrichtung diese Datei nach config/config.php kopieren und anpassen.
 */
return array(
    'db' => array(
        // MySQL / MariaDB
        'driver' => 'mysql',
        'host'   => 'localhost',
        'port'   => 3306,
        'name'   => 'datenbankname',
        'user'   => 'benutzer',
        'pass'   => 'passwort',
        'prefix' => 'sp_',
        // Alternativ SQLite (keine Datenbank nötig):
        // 'driver' => 'sqlite', 'path' => 'storage/stagepool.sqlite', 'prefix' => 'sp_',
    ),
    // Zufälliger, geheimer Schlüssel (mind. 32 Zeichen) – z. B. von https://www.random.org/strings/
    'secret'    => 'BITTE-ERSETZEN-durch-eine-lange-zufaellige-zeichenkette',
    'timezone'  => 'Europe/Berlin',
    'debug'     => false,
    // Nur nötig, wenn die automatische Erkennung nicht passt:
    'base_path' => '',   // z. B. '/verleih/' bei Installation in einem Unterordner
    'site_url'  => '',   // z. B. 'https://verleih.example.de' für Links in E-Mails (z. B. bei Cronjobs/Proxies)
);
