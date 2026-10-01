<?php
// Anfrageformular Firmenpakete – nach dem Muster von kontaktformular_4elemente.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

if (!function_exists('vu_h')) {
    function vu_h($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
}

$vuPakete = [
    'Noch offen, bitte beraten',
    'Teamtag „Gemeinsam stark“',
    'Team-Klausur „Zusammenwachsen“',
    '„Kraftquelle Rhön“ – Achtsamkeit & Resilienz',
    'LeaderShip „Kurs halten“',
    'Zukunftswerkstatt „Strategie & Innovation“',
    'Team-Festival „Großgruppe in Bewegung“',
    'Familien-Unternehmens-Retreat',
    'Eigenes Programm aus Bausteinen',
];
$vuGroessen = ['10–30 Personen', '30–80 Personen', '80–150 Personen', '150–300 Personen'];
$vuDauern   = ['Halber Tag', '1 Tag', '2 Tage', '3 Tage oder mehr', 'Noch offen'];

$errors  = [];
$success = false;
$old = [
    'firma' => '', 'name' => '', 'email' => '', 'phone' => '',
    'groesse' => '', 'paket' => $vuPakete[0], 'zeitraum' => '', 'dauer' => '1 Tag',
    'familie' => false, 'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vu_form'])) {
    foreach (['firma', 'name', 'email', 'phone', 'groesse', 'paket', 'zeitraum', 'dauer', 'message'] as $k) {
        $old[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    $old['familie'] = isset($_POST['familie']);

    // Spamschutz: Honeypot und Mindest-Ausfüllzeit von 4 Sekunden
    if (!empty($_POST['email_confirm'])) {
        $errors[] = 'Spam-Erkennung ausgelöst.';
    }
    $started = (int) ($_POST['vu_ts'] ?? 0);
    if ($started > 0 && time() - $started < 4) {
        $errors[] = 'Das ging sehr schnell. Bitte sendet das Formular noch einmal ab.';
    }

    if ($old['firma'] === '')  { $errors[] = 'Bitte gebt den Namen Eures Unternehmens ein.'; }
    if ($old['name'] === '')   { $errors[] = 'Bitte gebt eine Ansprechperson an.'; }
    if ($old['email'] === '' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Bitte gebt eine gültige E-Mail-Adresse ein.';
    }
    if (!in_array($old['groesse'], $vuGroessen, true)) { $errors[] = 'Bitte wählt die Gruppengröße aus.'; }
    if (!in_array($old['paket'], $vuPakete, true))     { $old['paket'] = $vuPakete[0]; }
    if (!in_array($old['dauer'], $vuDauern, true))     { $old['dauer'] = 'Noch offen'; }
    if (empty($_POST['datenschutz'])) { $errors[] = 'Bitte bestätigt die Datenschutzerklärung.'; }

    if (empty($errors)) {
        $requestDateTime = date('d.m.Y H:i:s');
        $d = array_map('vu_h', $old);
        $familie = $old['familie'] ? 'Ja' : 'Nein';
        $details = "
            <p>
            Unternehmen: {$d['firma']}<br>
            Ansprechperson: {$d['name']}<br>
            E-Mail: {$d['email']}<br>
            Telefon: {$d['phone']}<br>
            </p>
            <p>
            <b>Programm</b><br>
            Gruppengröße: {$d['groesse']}<br>
            Interesse an: {$d['paket']}<br>
            Wunschzeitraum: {$d['zeitraum']}<br>
            Dauer: {$d['dauer']}<br>
            Familien mitbringen: {$familie}<br>
            </p>";

        try {
            // 1) Anfrage an variado
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.strato.de';
            $mail->SMTPAuth = true;
            $mail->Username = 'info@variado.de';
            $mail->Password = ':-)'; // wie in den übrigen Formularen eintragen
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = PHPMailer::ENCODING_BASE64;

            $mail->setFrom('info@variado.de', 'variado-Anfrage');
            $mail->addAddress('anfrage@variado.de');
            $mail->addReplyTo($old['email'], $old['name']);
            $mail->isHTML(true);
            $mail->Subject = 'Firmenanfrage: ' . $old['paket'] . ' – ' . $old['firma'];
            $mail->Body = "Neue Anfrage über die Seite Firmenpakete.<br>
                {$details}
                <p><b>Ziele / Nachricht</b><br>" . nl2br($d['message']) . "</p>
                <p><i>Datenschutzerklärung bestätigt.</i><br>Anfragezeitpunkt: {$requestDateTime}</p>";
            $mail->send();

            // 2) Bestätigung an die anfragende Person (ohne Freitext, damit das Formular nicht zum Versand fremder Inhalte taugt)
            $confirm = clone $mail;
            $confirm->clearAllRecipients();
            $confirm->clearReplyTos();
            $confirm->setFrom('info@variado.de', 'variado');
            $confirm->addAddress($old['email'], $old['name']);
            $confirm->addReplyTo('anfrage@variado.de', 'variado');
            $confirm->Subject = 'variado – Eure Anfrage ist bei uns angekommen';
            $confirm->Body = "Hallo {$d['name']},<br><br>
                vielen Dank für Eure Anfrage. Wir melden uns innerhalb von zwei Werktagen für ein kostenloses Vorgespräch.<br>
                Hier noch einmal Eure Angaben (Korrekturen gern einfach als Antwort auf diese E-Mail):
                {$details}
                <p>Anfragezeitpunkt: {$requestDateTime}</p>
                Liebe Grüße<br>
                Euer variado-Team<br>
                anfrage@variado.de · +49 176 77860490";
            $confirm->send();

            $success = true;
            $old = array_merge($old, ['firma' => '', 'name' => '', 'email' => '', 'phone' => '', 'zeitraum' => '', 'message' => '', 'familie' => false]);
        } catch (Exception $e) {
            $errors[] = 'Eure Anfrage konnte leider nicht gesendet werden. Bitte schreibt uns direkt an anfrage@variado.de.';
            error_log('Firmenanfrage Mailer Error: ' . $mail->ErrorInfo);
        }
    }
}
?>
<!--vu-preview-skip-->
<?php if ($success): ?>
  <div class="vu-msg vu-msg--ok">Danke schön! Eure Anfrage ist bei uns angekommen. Eine Bestätigung haben wir Euch per E-Mail geschickt.</div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
  <div class="vu-msg vu-msg--err">Bitte prüft noch einmal:
    <ul><?php foreach ($errors as $error): ?><li><?= vu_h($error) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>
<!--/vu-preview-skip-->
<form method="post" action="#anfrage" class="vu-form" id="vuForm" novalidate>
  <input type="hidden" name="vu_form" value="1">
  <input type="hidden" name="vu_ts" value="<?= time() ?>">

  <div class="vu-f"><label for="vu-firma">Unternehmen*</label>
    <input type="text" name="firma" id="vu-firma" autocomplete="organization" required value="<?= vu_h($old['firma']) ?>"></div>
  <div class="vu-f"><label for="vu-name">Ansprechperson*</label>
    <input type="text" name="name" id="vu-name" autocomplete="name" required value="<?= vu_h($old['name']) ?>"></div>
  <div class="vu-f"><label for="vu-email">E-Mail*</label>
    <input type="email" name="email" id="vu-email" autocomplete="email" required value="<?= vu_h($old['email']) ?>"></div>
  <div class="vu-f"><label for="vu-phone">Telefon</label>
    <input type="tel" name="phone" id="vu-phone" autocomplete="tel" value="<?= vu_h($old['phone']) ?>"></div>

  <div class="vu-f"><label for="vu-groesse">Gruppengröße*</label>
    <select name="groesse" id="vu-groesse" required>
      <option value="">Bitte wählen</option>
      <?php foreach ($vuGroessen as $o): ?><option<?= $old['groesse'] === $o ? ' selected' : '' ?>><?= vu_h($o) ?></option><?php endforeach; ?>
    </select></div>
  <div class="vu-f"><label for="vu-paket">Interesse an</label>
    <select name="paket" id="vu-paket">
      <?php foreach ($vuPakete as $o): ?><option<?= $old['paket'] === $o ? ' selected' : '' ?>><?= vu_h($o) ?></option><?php endforeach; ?>
    </select></div>
  <div class="vu-f"><label for="vu-zeitraum">Wunschzeitraum</label>
    <input type="text" name="zeitraum" id="vu-zeitraum" placeholder="z. B. Mai 2027" value="<?= vu_h($old['zeitraum']) ?>"></div>
  <div class="vu-f"><label for="vu-dauer">Dauer</label>
    <select name="dauer" id="vu-dauer">
      <?php foreach ($vuDauern as $o): ?><option<?= $old['dauer'] === $o ? ' selected' : '' ?>><?= vu_h($o) ?></option><?php endforeach; ?>
    </select></div>

  <div class="vu-f vu-f--full"><label for="vu-message">Was soll sich in Eurem Team bewegen?</label>
    <textarea name="message" id="vu-message" placeholder="Anlass, Ziele, besondere Wünsche …"><?= vu_h($old['message']) ?></textarea></div>

  <label class="vu-check vu-f--full" for="vu-familie"><input type="checkbox" name="familie" id="vu-familie" value="1"<?= $old['familie'] ? ' checked' : '' ?>><span>Wir möchten Familien mitbringen (Familien-Unternehmens-Retreat).</span></label>

  <!-- Honeypot-Feld (unsichtbar) -->
  <div class="vu-hp" aria-hidden="true"><label for="email_confirm">Bitte leer lassen</label><input type="text" name="email_confirm" id="email_confirm" tabindex="-1" autocomplete="off"></div>

  <label class="vu-check vu-f--full" for="vu-datenschutz"><input type="checkbox" name="datenschutz" id="vu-datenschutz" value="1" required><span>Ich habe die <a href="https://www.variado.de/datenschutzerklaerung.php" target="_blank" rel="noopener">Datenschutzerklärung</a> zur Kenntnis genommen. Ich stimme zu, dass meine Angaben zur Beantwortung meiner Anfrage elektronisch erhoben und gespeichert werden. Diese Einwilligung kann ich jederzeit per E-Mail an info@variado.de widerrufen.*</span></label>

  <div class="vu-form-foot">
    <small>*Pflichtfelder</small>
    <button type="submit" class="vu-btn vu-btn--blue">Anfrage senden</button>
  </div>
</form>
