# variado Firmenpakete – Landingpage

Angebotsseite für Unternehmenskunden: sechs Pakete mit Filter nach Gruppengröße und Ziel,
Familien-Unternehmens-Retreat, Baukasten mit 12 Modulen, Galerie, Eckdaten, Team, FAQ und Anfrageformular.
Aufbau und Stil wie `feuer.php` (Fraunces + Work Sans, Morph-Fotos, dunkler Hero, Galerie mit Lightbox).

## Dateien

| Datei | Zweck |
|---|---|
| `unternehmen_pakete.php` | Die Seite für variado.de. Bindet `header_dynamic.php`, `footer.php` und das Formular ein. Alle Styles mit Präfix `vu-`, damit nichts mit dem Theme kollidiert. |
| `kontaktformular_unternehmen.php` | Anfrageformular nach dem Muster von `kontaktformular_4elemente.php` (PHPMailer, Honeypot, Datenschutz-Checkbox). |
| `img/` | Logo und Fotos (aus dem Unternehmensflyer, web-optimiert). |
| `index.html` | Statische Vorschau der Seite (ohne PHP, Formular sendet nicht). |

## Einbau auf variado.de

1. `unternehmen_pakete.php` und `kontaktformular_unternehmen.php` ins Web-Root legen (dort, wo `feuer.php` und `PHPMailer/` liegen).
2. Inhalt von `img/` nach `images/angebote/unternehmen/` hochladen (Pfad steht oben in der Seite als `$img`).
3. In `kontaktformular_unternehmen.php` das SMTP-Passwort wie in den anderen Formularen eintragen (`$mail->Password`).

Das Formular schickt die Anfrage an anfrage@variado.de (Antworten gehen direkt an die anfragende Person)
und eine Bestätigung ohne Freitext an die anfragende Person.
