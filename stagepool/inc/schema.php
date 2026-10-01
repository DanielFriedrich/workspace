<?php
if (!defined('SP_APP')) { exit; }

/**
 * Datenbankschema für MySQL/MariaDB und SQLite.
 * Datumswerte werden als Text (JJJJ-MM-TT bzw. JJJJ-MM-TT HH:MM:SS) gespeichert,
 * damit dieselben Abfragen in beiden Datenbanken funktionieren.
 */
function schema_statements($driver)
{
    $mysql = $driver !== 'sqlite';
    $id = $mysql ? 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $fk = $mysql ? 'INT UNSIGNED' : 'INTEGER';
    $tail = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
    $text = $mysql ? 'MEDIUMTEXT' : 'TEXT';

    $s = array();
    $s[] = "CREATE TABLE IF NOT EXISTS #__settings (
        name VARCHAR(64) NOT NULL PRIMARY KEY,
        value $text
    )$tail";

    $s[] = "CREATE TABLE IF NOT EXISTS #__users (
        id $id,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(60) NOT NULL DEFAULT '',
        role VARCHAR(20) NOT NULL DEFAULT 'member',
        password_hash VARCHAR(255) NOT NULL,
        active TINYINT NOT NULL DEFAULT 1,
        last_login VARCHAR(19) NULL,
        created_at VARCHAR(19) NOT NULL
    )$tail";
    $s[] = "CREATE UNIQUE INDEX #__users_email ON #__users (email)";

    $s[] = "CREATE TABLE IF NOT EXISTS #__login_attempts (
        id $id,
        ip_hash VARCHAR(64) NOT NULL,
        created_at VARCHAR(19) NOT NULL
    )$tail";
    $s[] = "CREATE INDEX #__login_attempts_ip ON #__login_attempts (ip_hash, created_at)";

    $s[] = "CREATE TABLE IF NOT EXISTS #__locations (
        id $id,
        name VARCHAR(120) NOT NULL,
        street VARCHAR(160) NOT NULL DEFAULT '',
        zip VARCHAR(12) NOT NULL DEFAULT '',
        city VARCHAR(120) NOT NULL DEFAULT '',
        contact VARCHAR(160) NOT NULL DEFAULT '',
        notes TEXT NULL,
        sort INT NOT NULL DEFAULT 0,
        active TINYINT NOT NULL DEFAULT 1
    )$tail";

    $s[] = "CREATE TABLE IF NOT EXISTS #__categories (
        id $id,
        name VARCHAR(120) NOT NULL,
        slug VARCHAR(140) NOT NULL,
        icon VARCHAR(40) NOT NULL DEFAULT 'box',
        color VARCHAR(9) NOT NULL DEFAULT '#ff2e93',
        sort INT NOT NULL DEFAULT 0
    )$tail";
    $s[] = "CREATE UNIQUE INDEX #__categories_slug ON #__categories (slug)";

    $s[] = "CREATE TABLE IF NOT EXISTS #__products (
        id $id,
        name VARCHAR(160) NOT NULL,
        category_id $fk NULL,
        location_id $fk NULL,
        owner_id $fk NULL,
        short_desc VARCHAR(255) NOT NULL DEFAULT '',
        description $text NULL,
        specs TEXT NULL,
        price_day DECIMAL(10,2) NOT NULL DEFAULT 0,
        price_internal DECIMAL(10,2) NOT NULL DEFAULT 0,
        price_auto TINYINT NOT NULL DEFAULT 0,
        deposit DECIMAL(10,2) NOT NULL DEFAULT 0,
        quantity INT NOT NULL DEFAULT 1,
        image VARCHAR(190) NOT NULL DEFAULT '',
        internal_note TEXT NULL,
        active TINYINT NOT NULL DEFAULT 1,
        featured TINYINT NOT NULL DEFAULT 0,
        sort INT NOT NULL DEFAULT 0,
        created_at VARCHAR(19) NOT NULL,
        updated_at VARCHAR(19) NOT NULL
    )$tail";
    $s[] = "CREATE INDEX #__products_cat ON #__products (category_id)";
    $s[] = "CREATE INDEX #__products_loc ON #__products (location_id)";

    $s[] = "CREATE TABLE IF NOT EXISTS #__bookings (
        id $id,
        code VARCHAR(20) NOT NULL,
        token VARCHAR(64) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'requested',
        start_date VARCHAR(10) NOT NULL,
        end_date VARCHAR(10) NOT NULL,
        customer_name VARCHAR(160) NOT NULL,
        email VARCHAR(190) NOT NULL DEFAULT '',
        phone VARCHAR(60) NOT NULL DEFAULT '',
        organisation VARCHAR(160) NOT NULL DEFAULT '',
        event_type VARCHAR(80) NOT NULL DEFAULT '',
        handover VARCHAR(20) NOT NULL DEFAULT 'pickup',
        delivery_address VARCHAR(255) NOT NULL DEFAULT '',
        message TEXT NULL,
        customer_address VARCHAR(255) NOT NULL DEFAULT '',
        price_tier VARCHAR(10) NOT NULL DEFAULT 'customer',
        team_user_id $fk NULL,
        total DECIMAL(10,2) NOT NULL DEFAULT 0,
        deposit_total DECIMAL(10,2) NOT NULL DEFAULT 0,
        price_override TINYINT NOT NULL DEFAULT 0,
        admin_note TEXT NULL,
        source VARCHAR(20) NOT NULL DEFAULT 'web',
        created_at VARCHAR(19) NOT NULL,
        updated_at VARCHAR(19) NOT NULL
    )$tail";
    $s[] = "CREATE UNIQUE INDEX #__bookings_code ON #__bookings (code)";
    $s[] = "CREATE INDEX #__bookings_dates ON #__bookings (status, start_date, end_date)";

    $s[] = "CREATE TABLE IF NOT EXISTS #__booking_items (
        id $id,
        booking_id $fk NOT NULL,
        product_id $fk NULL,
        product_name VARCHAR(160) NOT NULL,
        qty INT NOT NULL DEFAULT 1,
        price_day DECIMAL(10,2) NOT NULL DEFAULT 0,
        days INT NOT NULL DEFAULT 1,
        line_total DECIMAL(10,2) NOT NULL DEFAULT 0,
        deposit DECIMAL(10,2) NOT NULL DEFAULT 0
    )$tail";
    $s[] = "CREATE INDEX #__booking_items_booking ON #__booking_items (booking_id)";
    $s[] = "CREATE INDEX #__booking_items_product ON #__booking_items (product_id)";

    $s[] = "CREATE TABLE IF NOT EXISTS #__booking_log (
        id $id,
        booking_id $fk NOT NULL,
        user_id $fk NULL,
        action VARCHAR(40) NOT NULL,
        note TEXT NULL,
        created_at VARCHAR(19) NOT NULL
    )$tail";
    $s[] = "CREATE INDEX #__booking_log_booking ON #__booking_log (booking_id)";

    $s[] = "CREATE TABLE IF NOT EXISTS #__blocks (
        id $id,
        scope VARCHAR(20) NOT NULL DEFAULT 'product',
        ref_id $fk NULL,
        start_date VARCHAR(10) NOT NULL,
        end_date VARCHAR(10) NOT NULL,
        reason VARCHAR(255) NOT NULL DEFAULT '',
        created_by $fk NULL,
        created_at VARCHAR(19) NOT NULL
    )$tail";
    $s[] = "CREATE INDEX #__blocks_dates ON #__blocks (start_date, end_date)";

    // Bestand je Eigentümer und Standort
    $s[] = "CREATE TABLE IF NOT EXISTS #__product_stock (
        id $id,
        product_id $fk NOT NULL,
        owner_id $fk NULL,
        location_id $fk NULL,
        quantity INT NOT NULL DEFAULT 0,
        note VARCHAR(255) NOT NULL DEFAULT '',
        sort INT NOT NULL DEFAULT 0
    )$tail";
    $s[] = "CREATE INDEX #__product_stock_product ON #__product_stock (product_id)";

    // Zuteilung: welche Bestandsposten bei einer Buchung rausgehen
    $s[] = "CREATE TABLE IF NOT EXISTS #__booking_allocations (
        id $id,
        booking_id $fk NOT NULL,
        booking_item_id $fk NOT NULL,
        stock_id $fk NOT NULL,
        qty INT NOT NULL DEFAULT 1
    )$tail";
    $s[] = "CREATE INDEX #__booking_allocations_booking ON #__booking_allocations (booking_id)";
    $s[] = "CREATE INDEX #__booking_allocations_stock ON #__booking_allocations (stock_id)";

    // Angebote & Rechnungen (Snapshot der Positionen als JSON, damit Belege unveränderlich bleiben)
    $s[] = "CREATE TABLE IF NOT EXISTS #__documents (
        id $id,
        booking_id $fk NULL,
        type VARCHAR(10) NOT NULL,
        number VARCHAR(30) NOT NULL,
        doc_date VARCHAR(10) NOT NULL,
        due_date VARCHAR(10) NULL,
        service_from VARCHAR(10) NULL,
        service_to VARCHAR(10) NULL,
        customer_name VARCHAR(160) NOT NULL DEFAULT '',
        customer_address VARCHAR(255) NOT NULL DEFAULT '',
        customer_email VARCHAR(190) NOT NULL DEFAULT '',
        items $text NULL,
        subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
        adjustment DECIMAL(10,2) NOT NULL DEFAULT 0,
        total DECIMAL(10,2) NOT NULL DEFAULT 0,
        vat_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
        deposit_total DECIMAL(10,2) NOT NULL DEFAULT 0,
        note TEXT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'open',
        sent_at VARCHAR(19) NULL,
        paid_at VARCHAR(10) NULL,
        created_by $fk NULL,
        created_at VARCHAR(19) NOT NULL
    )$tail";
    $s[] = "CREATE UNIQUE INDEX #__documents_number ON #__documents (type, number)";
    $s[] = "CREATE INDEX #__documents_booking ON #__documents (booking_id)";

    // Buchhaltung: Einnahmen und Ausgaben
    $s[] = "CREATE TABLE IF NOT EXISTS #__transactions (
        id $id,
        type VARCHAR(10) NOT NULL,
        tx_date VARCHAR(10) NOT NULL,
        category VARCHAR(80) NOT NULL DEFAULT '',
        description VARCHAR(255) NOT NULL DEFAULT '',
        counterparty VARCHAR(160) NOT NULL DEFAULT '',
        amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        vat_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        payment_method VARCHAR(30) NOT NULL DEFAULT '',
        paid_by $fk NULL,
        document_id $fk NULL,
        booking_id $fk NULL,
        product_id $fk NULL,
        receipt VARCHAR(190) NOT NULL DEFAULT '',
        note TEXT NULL,
        created_by $fk NULL,
        created_at VARCHAR(19) NOT NULL,
        updated_at VARCHAR(19) NOT NULL
    )$tail";
    $s[] = "CREATE INDEX #__transactions_date ON #__transactions (type, tx_date)";
    $s[] = "CREATE INDEX #__transactions_doc ON #__transactions (document_id)";

    return $s;
}

/** Legt alle Tabellen an (bereits vorhandene bleiben unverändert). */
/** Ergänzt fehlende Spalten (für Updates bestehender Installationen). */
function schema_add_column($table, $column, $definition)
{
    try {
        db()->query(db_sql('SELECT ' . $column . ' FROM #__' . $table . ' LIMIT 1'));
        return false;
    } catch (PDOException $ex) {
        db()->exec(db_sql('ALTER TABLE #__' . $table . ' ADD COLUMN ' . $column . ' ' . $definition));
        return true;
    }
}

/** Bringt die Datenbank auf den aktuellen Stand. Wird automatisch aufgerufen. */
function schema_migrate()
{
    schema_install(db_driver());
    schema_add_column('bookings', 'customer_address', "VARCHAR(255) NOT NULL DEFAULT ''");
    // v3: Preisstufen, Bestand je Eigentümer/Standort
    schema_add_column('products', 'price_internal', 'DECIMAL(10,2) NOT NULL DEFAULT 0');
    schema_add_column('products', 'price_auto', 'TINYINT NOT NULL DEFAULT 0');
    schema_add_column('bookings', 'price_tier', "VARCHAR(10) NOT NULL DEFAULT 'customer'");
    schema_add_column('bookings', 'team_user_id', db_driver() === 'sqlite' ? 'INTEGER NULL' : 'INT UNSIGNED NULL');
    schema_seed_stock();
    setting_set('schema_version', (string) SP_SCHEMA_VERSION);
}

/** Legt für Geräte ohne Bestandsposten einen Posten an und teilt bestehende Buchungen zu. */
function schema_seed_stock()
{
    foreach (db_all('SELECT p.id, p.owner_id, p.location_id, p.quantity FROM #__products p
                      WHERE NOT EXISTS (SELECT 1 FROM #__product_stock s WHERE s.product_id = p.id)') as $p) {
        $sid = db_insert('product_stock', array('product_id' => (int) $p['id'], 'owner_id' => $p['owner_id'], 'location_id' => $p['location_id'],
            'quantity' => max(1, (int) $p['quantity']), 'note' => '', 'sort' => 0));
        foreach (db_all('SELECT bi.id, bi.booking_id, bi.qty FROM #__booking_items bi
                          WHERE bi.product_id = ? AND NOT EXISTS (SELECT 1 FROM #__booking_allocations a WHERE a.booking_item_id = bi.id)', array($p['id'])) as $it) {
            db_insert('booking_allocations', array('booking_id' => (int) $it['booking_id'], 'booking_item_id' => (int) $it['id'], 'stock_id' => $sid, 'qty' => (int) $it['qty']));
        }
    }
}

function schema_install($driver)
{
    foreach (schema_statements($driver) as $sql) {
        try {
            db()->exec(db_sql($sql));
        } catch (PDOException $ex) {
            // Indizes existieren bereits (MySQL kennt kein CREATE INDEX IF NOT EXISTS).
            if (stripos($sql, 'CREATE TABLE') === 0) {
                throw $ex;
            }
        }
    }
}

function default_categories()
{
    return array(
        array('Scheinwerfer', 'scheinwerfer', 'spot', '#ffc53d'),
        array('Effektlicht', 'effektlicht', 'effect', '#b56cff'),
        array('Laser', 'laser', 'laser', '#22d3ee'),
        array('Nebel & Haze', 'nebel', 'fog', '#9fb3c8'),
        array('Flammen', 'flammen', 'flame', '#ff6a2b'),
        array('Funken', 'funken', 'spark', '#ffe066'),
        array('Lautsprecher', 'lautsprecher', 'speaker', '#ff2e93'),
        array('Mischpulte & DJ', 'mischpulte', 'mixer', '#4d7cff'),
        array('Mikrofone', 'mikrofone', 'mic', '#3ddc97'),
        array('Zubehör & Strom', 'zubehoer', 'cable', '#a0a0b8'),
    );
}

function install_default_categories()
{
    if ((int) db_value('SELECT COUNT(*) FROM #__categories') > 0) {
        return;
    }
    $i = 0;
    foreach (default_categories() as $c) {
        db_insert('categories', array('name' => $c[0], 'slug' => $c[1], 'icon' => $c[2], 'color' => $c[3], 'sort' => ($i++) * 10));
    }
}

/** Beispieldaten, damit die Plattform sofort ausprobiert werden kann. */
function install_demo_data($ownerId)
{
    install_default_categories();
    $cat = array();
    foreach (db_all('SELECT id, slug FROM #__categories') as $r) {
        $cat[$r['slug']] = (int) $r['id'];
    }
    $loc1 = db_insert('locations', array('name' => 'Werkstatt Mitte', 'street' => 'Musterstraße 12', 'zip' => '12345', 'city' => 'Musterstadt', 'contact' => 'Abholung nach Absprache', 'notes' => 'Hof hinten links, Rolltor.', 'sort' => 10, 'active' => 1));
    $loc2 = db_insert('locations', array('name' => 'Lager Süd', 'street' => 'Am Bahnhof 3', 'zip' => '12347', 'city' => 'Musterstadt', 'contact' => 'Abholung abends ab 18 Uhr', 'notes' => '', 'sort' => 20, 'active' => 1));

    // name, Kategorie, Standort, Kurztext, Preis/Tag, Kaution, Anzahl, Specs, featured
    $products = array(
        array('LED-PAR Scheinwerfer RGBW', 'scheinwerfer', $loc1, 'Kompakter Allrounder für farbiges Raum- und Bühnenlicht, DMX oder Auto-Modus.', 8, 30, 8, "Leistung: 12 × 8 W RGBW\nSteuerung: DMX, Sound-to-Light, Auto\nAbstrahlwinkel: 25°\nGewicht: 1,6 kg", 1),
        array('Akku-Uplight RGBWA+UV', 'scheinwerfer', $loc1, 'Kabellose Wandfluter – perfekt für Hochzeiten und Locations ohne Steckdosen.', 12, 40, 6, "Akku: ca. 10 h\nFarben: RGBWA+UV\nFernbedienung: IR inklusive\nCase mit Ladefunktion", 1),
        array('Moving Head Spot 60 W', 'scheinwerfer', $loc2, 'Bewegter Spot mit Gobos und Prisma für echte Show-Momente.', 25, 150, 2, "LED: 60 W\nGobos: 7 + offen\nPrisma: 3-fach\nSteuerung: DMX (9/11 Kanäle)", 0),
        array('LED-Bar 8 × 10 W', 'scheinwerfer', $loc2, 'Lichtleiste für Bühnenkanten, Theken und Backdrops.', 15, 50, 2, "Länge: 1 m\nSegmente: 8 einzeln steuerbar\nSteuerung: DMX, Auto", 0),
        array('Verfolger / Profilscheinwerfer', 'scheinwerfer', $loc1, 'Gezielter Spot für Reden, Tanz und Bühnenauftritte.', 20, 100, 1, "Leistung: 150 W LED\nZoom: 15–30°\nIris und Farbrad", 0),
        array('Derby-Effekt mit Strobe', 'effektlicht', $loc1, 'Klassischer Partyeffekt mit Lichtstrahlen und Stroboskop.', 10, 40, 2, "Steuerung: Auto, Sound, DMX\nStrobe: weiß LED", 0),
        array('Spiegelkugel 30 cm mit Motor', 'effektlicht', $loc2, 'Der Klassiker – inklusive Pinspot und Drehmotor.', 8, 20, 1, "Durchmesser: 30 cm\nInklusive Pinspot\nMotor: 1 U/min", 0),
        array('UV-Schwarzlicht LED-Bar', 'effektlicht', $loc1, 'Lässt Neon-Deko, Outfits und Bodypaint leuchten.', 6, 20, 4, "Leistung: 18 × 3 W UV\nLänge: 1 m", 0),
        array('Stroboskop 1500 W', 'effektlicht', $loc2, 'Kraftvolle Blitze für Drops und Höhepunkte.', 10, 40, 1, "Blitzfrequenz: regelbar\nSteuerung: DMX, manuell", 0),
        array('RGB-Showlaser 1 W', 'laser', $loc2, 'Animationen, Tunnel und Strahlen für die Tanzfläche.', 35, 300, 1, "Leistung: 1 W RGB\nSteuerung: DMX, ILDA, Auto\nHinweis: Betrieb nur nach Einweisung", 1),
        array('Sternenhimmel-Laser grün', 'laser', $loc1, 'Tausende Lichtpunkte für Decke und Wände.', 8, 30, 2, "Leistung: 100 mW\nModi: Auto, Sound", 0),
        array('Nebelmaschine 1500 W', 'nebel', $loc1, 'Schnelle, dichte Nebelstöße – macht Lichtstrahlen sichtbar.', 20, 50, 2, "Leistung: 1500 W\nAufheizzeit: ca. 6 min\nFluid: 1 Liter inklusive", 1),
        array('Hazer (Dunstnebel)', 'nebel', $loc2, 'Feiner, gleichmäßiger Dunst für sichtbare Beams über Stunden.', 25, 80, 1, "Typ: Öl-Hazer\nLaufzeit: bis 12 h pro Füllung\nSteuerung: DMX, Timer", 0),
        array('Bodennebelmaschine (Low Fog)', 'nebel', $loc1, 'Wolkentanz-Effekt für Hochzeiten und Bühnen.', 40, 150, 1, "Typ: Eis-Bodennebel\nHinweis: Crushed Ice wird benötigt", 1),
        array('Flammenprojektor DMX', 'flammen', $loc2, 'Bis zu 3 m hohe Flammensäulen – echtes Bühnenfeuer.', 60, 300, 2, "Flammenhöhe: bis 3 m\nSteuerung: DMX\nAusgabe nur mit Einweisung und Genehmigung des Veranstaltungsorts", 1),
        array('Kaltfunkenmaschine 600 W', 'funken', $loc2, 'Funkenfontänen ohne Hitze – für Einmarsch, Hochzeit und Bühne.', 75, 300, 2, "Funkenhöhe: 1–5 m\nSteuerung: DMX, Funk\nGranulat wird nach Verbrauch berechnet", 1),
        array('Aktivlautsprecher 12" (Paar)', 'lautsprecher', $loc1, 'Kräftiger Sound für bis zu 150 Gäste.', 40, 150, 2, "Leistung: 2 × 1000 W Peak\nEingänge: XLR/Klinke, Bluetooth\nInklusive Kabel", 1),
        array('Subwoofer 15" aktiv', 'lautsprecher', $loc1, 'Druckvoller Bass als Ergänzung zu den Topteilen.', 35, 150, 2, "Leistung: 1200 W Peak\nFrequenz: 40–150 Hz", 0),
        array('Akku-Lautsprecher mit Bluetooth', 'lautsprecher', $loc2, 'Mobile Musik für Garten, Picknick und Empfang.', 15, 50, 3, "Akku: ca. 8 h\nInklusive Funkmikrofon", 0),
        array('Monitorbox 10"', 'lautsprecher', $loc2, 'Bühnenmonitor, damit die Band sich selbst hört.', 12, 50, 2, "Leistung: 300 W\nBauform: Keil", 0),
        array('Digitalmischpult 16 Kanäle', 'mischpulte', $loc1, 'Steuerbar per Tablet, mit Effekten und Szenenspeicher.', 45, 200, 1, "Kanäle: 16\nSteuerung: Tablet/App\nEffekte: Hall, Delay, EQ", 0),
        array('DJ-Controller 4 Decks', 'mischpulte', $loc2, 'Plug & Play mit Laptop – für eure eigene DJ-Session.', 30, 150, 1, "Decks: 4\nAnschlüsse: USB, XLR, Cinch", 0),
        array('Kompaktmixer 8 Kanäle', 'mischpulte', $loc1, 'Einfacher Mixer für Reden, Musik und kleine Bands.', 15, 50, 1, "Kanäle: 8 (4 Mic)\nEffekte: integriert", 0),
        array('Funkmikrofon-Set (2 × Hand)', 'mikrofone', $loc1, 'Zwei Funkmikrofone für Reden, Spiele und Gesang.', 25, 100, 2, "Reichweite: ca. 60 m\nBatterien inklusive", 1),
        array('Gesangsmikrofon dynamisch', 'mikrofone', $loc2, 'Robustes Bühnenmikrofon inklusive Kabel und Klemme.', 5, 20, 6, "Charakteristik: Niere\nKabel: 6 m XLR", 0),
        array('Headset-Funkmikrofon', 'mikrofone', $loc1, 'Freie Hände für Moderation, Fitness und Theater.', 18, 80, 2, "Reichweite: ca. 50 m\nHautfarbenes Headset", 0),
        array('Lautsprecherstativ (Paar)', 'zubehoer', $loc1, 'Stabile Stative inklusive Tasche.', 6, 0, 3, "Höhe: bis 2 m\nTraglast: 35 kg", 0),
        array('Kabeltrommel 50 m + Verteiler', 'zubehoer', $loc2, 'Strom genau dort, wo du ihn brauchst.', 5, 0, 4, "Länge: 50 m\nVerteiler: 6-fach", 0),
        array('DMX-Controller 192 Kanäle', 'zubehoer', $loc1, 'Licht von Hand steuern, Szenen und Chaser speichern.', 8, 30, 1, "Kanäle: 192\nSzenen: 240", 0),
        array('T-Bar Lichtstativ 3 m', 'zubehoer', $loc2, 'Für 4 Scheinwerfer, mit Querstange.', 8, 20, 2, "Höhe: bis 3 m\nTraglast: 30 kg", 0),
    );
    $ids = array();
    $i = 0;
    foreach ($products as $p) {
        $desc = $p[3] . "\n\nIdeal für kleine Veranstaltungen. Alle Geräte werden geprüft und sauber übergeben. Bei Fragen zur Bedienung helfen wir dir gern bei der Abholung.";
        $ids[] = db_insert('products', array(
            'name' => $p[0], 'category_id' => $cat[$p[1]], 'location_id' => $p[2], 'owner_id' => $ownerId,
            'short_desc' => $p[3], 'description' => $desc, 'specs' => $p[7],
            'price_day' => $p[4], 'price_internal' => round($p[4] * 0.8 * 2) / 2, 'price_auto' => 0,
            'deposit' => $p[5], 'quantity' => $p[6], 'image' => '',
            'internal_note' => '', 'active' => 1, 'featured' => $p[8], 'sort' => ($i++) * 10,
            'created_at' => now(), 'updated_at' => now(),
        ));
    }

    // Zweites Teammitglied als Miteigentümerin (Demo, Login mit Zufallspasswort)
    $lisa = (int) db_value('SELECT id FROM #__users WHERE email = ?', array('lisa.demo@example.org'));
    if (!$lisa) {
        $lisa = db_insert('users', array('name' => 'Lisa (Demo)', 'email' => 'lisa.demo@example.org', 'phone' => '', 'role' => 'member', 'active' => 1,
            'password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT), 'created_at' => now()));
    }
    schema_seed_stock();
    // Beispiel: Die 8 LED-PARs gehören zwei Personen und stehen an zwei Standorten,
    // die Gesangsmikrofone gehören zwei Personen am selben Standort.
    db_exec('UPDATE #__product_stock SET quantity = 4 WHERE product_id = ?', array($ids[0]));
    db_insert('product_stock', array('product_id' => $ids[0], 'owner_id' => $lisa, 'location_id' => $loc2, 'quantity' => 4, 'note' => 'Lisas PARs', 'sort' => 10));
    db_exec('UPDATE #__product_stock SET quantity = 3 WHERE product_id = ?', array($ids[24]));
    db_insert('product_stock', array('product_id' => $ids[24], 'owner_id' => $lisa, 'location_id' => $loc2, 'quantity' => 3, 'note' => '', 'sort' => 10));
    stock_sync_product($ids[0]);
    stock_sync_product($ids[24]);

    // Beispielbuchungen relativ zu heute
    $t = today();
    $demo = array(
        array('requested', 5, 7, 'Lena Beispiel', 'Geburtstag', array(0 => 4, 11 => 1, 16 => 1)),
        array('confirmed', 12, 13, 'Musikverein Musterstadt', 'Vereinsfest', array(16 => 2, 17 => 1, 23 => 2)),
        array('picked_up', -1, 1, 'Tom Muster', 'Party', array(5 => 1, 9 => 1, 18 => 1)),
        array('returned', -9, -8, 'Anna Test', 'Hochzeit', array(1 => 6, 13 => 1)),
        array('requested', 19, 20, 'Schule am Park', 'Theater / Schule', array(4 => 1, 25 => 2, 23 => 1)),
    );
    foreach ($demo as $d) {
        $from = date_add_days($t, $d[1]);
        $to = date_add_days($t, $d[2]);
        $days = days_inclusive($from, $to);
        $bid = db_insert('bookings', array(
            'code' => 'SP-' . random_code(5), 'token' => bin2hex(random_bytes(16)), 'status' => $d[0],
            'start_date' => $from, 'end_date' => $to, 'customer_name' => $d[3], 'email' => 'demo@example.org',
            'phone' => '0123 456789', 'organisation' => '', 'event_type' => $d[4], 'handover' => 'pickup',
            'delivery_address' => '', 'message' => 'Beispielbuchung (Demo-Daten)', 'total' => 0, 'deposit_total' => 0,
            'price_override' => 0, 'admin_note' => '', 'source' => 'demo', 'created_at' => now(), 'updated_at' => now(),
        ));
        $total = 0;
        $dep = 0;
        foreach ($d[5] as $idx => $qty) {
            $p = $products[$idx];
            $line = rental_price($p[4], $days, $qty);
            $total += $line;
            $dep += $p[5] * $qty;
            db_insert('booking_items', array('booking_id' => $bid, 'product_id' => $ids[$idx], 'product_name' => $p[0], 'qty' => $qty, 'price_day' => $p[4], 'days' => $days, 'line_total' => $line, 'deposit' => $p[5] * $qty));
        }
        db_update('bookings', array('total' => $total, 'deposit_total' => $dep), 'id = ?', array($bid));
        booking_allocate($bid);
        db_insert('booking_log', array('booking_id' => $bid, 'user_id' => null, 'action' => 'created', 'note' => 'Demo-Daten', 'created_at' => now()));
    }

    // Buchhaltung: Rechnung für die abgeschlossene Demo-Buchung + Beispiel-Ausgaben
    $done = db_one("SELECT id FROM #__bookings WHERE status = 'returned' ORDER BY id LIMIT 1");
    if ($done) {
        $docId = doc_create(booking_load($done['id']), 'invoice', '');
        doc_mark_paid(doc_load($docId), date_add_days($t, -5), 'Überweisung');
    }
    $expenses = array(
        array(-40, 'Lager & Ausstattung (Regale, Kisten)', '4 Schwerlastregale für das Lager', 'Baumarkt', 289.96),
        array(-35, 'Versicherung', 'Inhaltsversicherung Equipment (Jahresbeitrag)', 'Versicherung AG', 186.00),
        array(-20, 'Verbrauchsmaterial (Fluid, Granulat …)', 'Nebelfluid 5 l + Funkengranulat', 'Musikhaus', 64.80),
        array(-12, 'Lager & Ausstattung (Regale, Kisten)', '10 Transportkisten mit Deckel', 'Onlineshop', 119.90),
        array(-2, 'Miete', 'Lagermiete (Demo)', 'Vermieter', 150.00),
    );
    foreach ($expenses as $x) {
        db_insert('transactions', array(
            'type' => 'expense', 'tx_date' => date_add_days($t, $x[0]), 'category' => $x[1], 'description' => $x[2], 'counterparty' => $x[3],
            'amount' => $x[4], 'vat_amount' => 0, 'payment_method' => 'Privat ausgelegt', 'paid_by' => $ownerId, 'document_id' => null,
            'booking_id' => null, 'product_id' => null, 'receipt' => '', 'note' => 'Demo-Daten', 'created_by' => $ownerId,
            'created_at' => now(), 'updated_at' => now(),
        ));
    }

    db_insert('blocks', array('scope' => 'location', 'ref_id' => $loc2, 'start_date' => date_add_days($t, 24), 'end_date' => date_add_days($t, 30), 'reason' => 'Urlaub – keine Übergaben möglich', 'created_by' => $ownerId, 'created_at' => now()));
    db_insert('blocks', array('scope' => 'product', 'ref_id' => $ids[12], 'start_date' => date_add_days($t, 3), 'end_date' => date_add_days($t, 4), 'reason' => 'Wartung', 'created_by' => $ownerId, 'created_at' => now()));
}
