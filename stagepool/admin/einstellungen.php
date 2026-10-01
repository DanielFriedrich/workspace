<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require SP_ROOT . '/inc/admin.php';
require_admin();

$sections = array(
    'allgemein' => array('Allgemein', 'grid', array(
        'site_name'     => array('Name der Plattform', 'text'),
        'site_tagline'  => array('Untertitel / Claim', 'text'),
        'hero_text'     => array('Begrüßungstext auf der Startseite', 'textarea'),
        'contact_email' => array('Kontakt-E-Mail (öffentlich)', 'email'),
        'contact_phone' => array('Kontakt-Telefon (öffentlich)', 'text'),
        'notify_email'  => array('Benachrichtigung bei neuen Anfragen an (mehrere mit Komma)', 'text'),
    )),
    'regeln' => array('Buchungsregeln', 'calendar', array(
        'buffer_days' => array('Puffertage vor und nach jeder Buchung', 'number', 'Werden automatisch mitgeblockt (Abholung/Rückgabe). Standard: 1'),
        'lead_days'   => array('Vorlauf in Tagen', 'number', 'Frühester Mietbeginn ab heute. 0 = gleicher Tag, 1 = ab morgen'),
        'min_days'    => array('Mindestmietdauer (Tage)', 'number'),
        'max_days'    => array('Maximale Mietdauer online (Tage)', 'number'),
        'customer_markup' => array('Aufschlag Endkundenpreis auf internen Teampreis (%)', 'number', 'Für Geräte mit „Endkundenpreis automatisch“. Beispiel 25: intern 20 € → Endkunde 25 €. Änderungen passen diese Preise sofort an.'),
        'extra_day_percent' => array('Preis jedes weiteren Miettags (% vom Tagespreis)', 'number', '50 = 1 Tag 100 €, 2 Tage 150 €, 3 Tage 200 €. 100 = jeder Tag voller Preis.'),
        'price_note'  => array('Hinweis zu Preisen', 'text'),
    )),
    'firma' => array('Firma & Rechnungen', 'tag', array(
        'company_name'     => array('Name / Firma (Absender auf Angeboten & Rechnungen)', 'text'),
        'company_address'  => array('Anschrift', 'textarea', 'Straße Nr. und PLZ Ort, je eine Zeile'),
        'tax_number'       => array('Steuernummer', 'text', 'Pflichtangabe auf Rechnungen (oder USt-IdNr.)'),
        'vat_id'           => array('USt-IdNr. (falls vorhanden)', 'text'),
        'vat_rate'         => array('Umsatzsteuersatz in % (0 = Kleinunternehmer)', 'number', 'Bei 19 % sind alle Preise Bruttopreise; die enthaltene Steuer wird auf der Rechnung ausgewiesen.'),
        'tax_note'         => array('Steuerhinweis bei 0 %', 'text'),
        'bank_holder'      => array('Kontoinhaber', 'text'),
        'iban'             => array('IBAN', 'text'),
        'bic'              => array('BIC', 'text'),
        'bank_name'        => array('Bank', 'text'),
        'offer_prefix'     => array('Präfix Angebotsnummern', 'text', 'z. B. AN → AN-2026-001'),
        'invoice_prefix'   => array('Präfix Rechnungsnummern', 'text', 'z. B. RE → RE-2026-001 (fortlaufend pro Jahr)'),
        'offer_valid_days' => array('Angebot gültig (Tage)', 'number'),
        'payment_days'     => array('Zahlungsziel (Tage)', 'number'),
        'offer_text'       => array('Einleitungstext Angebot', 'textarea'),
        'invoice_text'     => array('Einleitungstext Rechnung', 'textarea'),
        'doc_footer'       => array('Zusätzliche Fußzeile (optional)', 'textarea', 'z. B. Website oder Hinweise'),
    )),
    'buchhaltung' => array('Buchhaltung', 'grid', array(
        'expense_categories' => array('Kategorien für Ausgaben', 'textarea', 'Eine Kategorie pro Zeile'),
        'income_categories'  => array('Kategorien für Einnahmen', 'textarea', 'Eine Kategorie pro Zeile'),
    )),
    'texte' => array('Texte', 'edit', array(
        'pickup_info' => array('Abholung & Lieferung', 'textarea'),
        'terms'       => array('Mietbedingungen', 'textarea-lg'),
    )),
    'recht' => array('Rechtliches', 'lock', array(
        'impressum' => array('Impressum', 'textarea-lg'),
        'privacy'   => array('Datenschutzerklärung', 'textarea-lg'),
    )),
    'mail' => array('E-Mail-Versand', 'mail', array(
        'mail_mode'      => array('Versandart', 'select', '', array('mail' => 'PHP mail() – Standard beim Hoster', 'smtp' => 'SMTP-Postfach (empfohlen)', 'log' => 'Nur protokollieren (Test, kein Versand)')),
        'mail_from'      => array('Absender-Adresse', 'email', 'Sollte zur Domain bzw. zum SMTP-Postfach passen'),
        'mail_from_name' => array('Absender-Name', 'text'),
        'smtp_host'      => array('SMTP-Server', 'text', 'z. B. smtp.ionos.de, smtp.strato.de, w0123.kasserver.com'),
        'smtp_port'      => array('SMTP-Port', 'number', '587 (TLS) oder 465 (SSL)'),
        'smtp_secure'    => array('Verschlüsselung', 'select', '', array('tls' => 'STARTTLS (Port 587)', 'ssl' => 'SSL/TLS (Port 465)', 'none' => 'keine')),
        'smtp_user'      => array('SMTP-Benutzer', 'text'),
        'smtp_pass'      => array('SMTP-Passwort', 'password', 'Leer lassen = unverändert'),
    )),
);

if (is_post()) {
    csrf_check();
    if (input('action') === 'testmail') {
        $to = current_user()['email'];
        $ok = send_mail($to, 'Testmail von ' . setting('site_name'), "Hallo!\n\nWenn du diese Nachricht liest, funktioniert der E-Mail-Versand.\n\nVersandart: " . setting('mail_mode') . mail_signature());
        flash($ok ? 'success' : 'error', $ok ? 'Testmail an ' . $to . ' verschickt (bzw. protokolliert). Bitte auch den Spam-Ordner prüfen.' : 'Versand fehlgeschlagen. Details stehen in storage/logs/.');
        redirect('admin/einstellungen.php', array('tab' => 'mail'));
    }
    $tab = isset($sections[input('tab')]) ? input('tab') : 'allgemein';
    foreach ($sections[$tab][2] as $key => $def) {
        if (!isset($_POST[$key])) {
            continue;
        }
        $value = trim((string) $_POST[$key]);
        if ($def[1] === 'password' && $value === '') {
            continue;
        }
        if ($def[1] === 'number') {
            $value = (string) max(0, $key === 'vat_rate' ? round((float) str_replace(',', '.', $value), 2) : (int) $value);
        }
        if ($key === 'iban') {
            $value = trim(chunk_split(strtoupper(preg_replace('/\s+/', '', $value)), 4, ' '));
        }
        setting_set($key, $value);
    }
    if ($tab === 'regeln' && isset($_POST['customer_markup'])) {
        settings_all(true);
        $n = reprice_auto_products();
        if ($n) {
            flash('success', plural($n, 'Endkundenpreis wurde', 'Endkundenpreise wurden') . ' an den Aufschlag angepasst.');
        }
    }
    flash('success', 'Einstellungen gespeichert.');
    redirect('admin/einstellungen.php', array('tab' => $tab));
}

$tab = isset($sections[input('tab')]) ? input('tab') : 'allgemein';
$all = settings_all(true);

admin_header('Einstellungen', 'einstellungen');
?>
<nav class="tabs">
  <?php foreach ($sections as $k => $s): ?><a href="<?= e(url('admin/einstellungen.php', array('tab' => $k))) ?>"<?= $tab === $k ? ' aria-current="page"' : '' ?>><?= icon($s[1]) ?><?= e($s[0]) ?></a><?php endforeach; ?>
</nav>
<form class="card-admin settings-form" method="post">
  <?= csrf_field() ?><input type="hidden" name="tab" value="<?= e($tab) ?>">
  <?php foreach ($sections[$tab][2] as $key => $def): $val = isset($all[$key]) ? $all[$key] : ''; ?>
    <label class="field">
      <span><?= e($def[0]) ?></span>
      <?php if ($def[1] === 'textarea' || $def[1] === 'textarea-lg'): ?>
        <textarea name="<?= e($key) ?>" rows="<?= $def[1] === 'textarea-lg' ? 14 : 5 ?>"><?= e($val) ?></textarea>
      <?php elseif ($def[1] === 'select'): ?>
        <select name="<?= e($key) ?>"><?php foreach ($def[3] as $k => $l): ?><?= opt($k, $val, $l) ?><?php endforeach; ?></select>
      <?php elseif ($def[1] === 'password'): ?>
        <input type="password" name="<?= e($key) ?>" value="" autocomplete="new-password" placeholder="<?= $val !== '' ? '•••••••• (gespeichert)' : '' ?>">
      <?php else: ?>
        <input type="<?= e($def[1]) ?>" name="<?= e($key) ?>" value="<?= e($val) ?>"<?= $def[1] === 'number' ? ' min="0" max="365" step="any"' : '' ?>>
      <?php endif; ?>
      <?php if (!empty($def[2])): ?><small class="muted"><?= e($def[2]) ?></small><?php endif; ?>
    </label>
  <?php endforeach; ?>
  <?php if (in_array($tab, array('texte', 'recht'), true)): ?>
    <p class="muted small">Formatierung: Leerzeile = neuer Absatz, „## Überschrift“, „- Listenpunkt“, **fett**, Links als [Text](https://…).</p>
  <?php endif; ?>
  <div class="form-actions"><button class="btn btn-primary" type="submit"><?= icon('check') ?>Speichern</button></div>
</form>
<?php if ($tab === 'mail'): ?>
  <form class="card-admin" method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="testmail">
    <h2><?= icon('mail') ?>Versand testen</h2>
    <p class="muted">Schickt eine Testmail an deine Login-Adresse (<?= e(current_user()['email']) ?>). Im Modus „Nur protokollieren“ landet sie in <code>storage/logs/mail.log</code>.</p>
    <button class="btn btn-ghost" type="submit">Testmail senden</button>
  </form>
<?php endif; ?>
<?php admin_footer(); ?>
