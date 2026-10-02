# Installation & Hosting

Diese Anleitung führt Schritt für Schritt durch die Einrichtung auf einem normalen Webhosting-Paket (z. B. IONOS, Strato, all-inkl, Hetzner Webhosting, netcup, Hostinger).

---

> **Synology (Web Station)?** Siehe die eigene Anleitung [SYNOLOGY.md](SYNOLOGY.md).
>
> **Etwas klappt nicht?** `https://eure-domain.de/diagnose.php` aufrufen – die Seite zeigt, was auf dem Server fehlt (PHP-Erweiterungen, Schreibrechte, Sessions, Datenbank, Ordner-Schutz) und testet die Datenbankverbindung. Nach der Installation ist sie nur für Admins erreichbar.

## 1. Voraussetzungen

| | Minimum | Hinweis |
|---|---|---|
| PHP | **7.3 bis 8.3** (z. B. 7.4, 8.1, 8.2) | Im Hosting-Panel auswählbar |
| PHP-Erweiterungen | `pdo`, `pdo_mysql` **oder** `pdo_sqlite`, `json`, `session`, `hash`, `mbstring` | bei allen gängigen Hostern Standard |
| optional | `gd` | verkleinert große Fotos automatisch auf 1600 px |
| Datenbank | MySQL 5.6+ / MariaDB 10.1+ **oder** SQLite | SQLite braucht keinen Datenbankserver |
| Webserver | Apache mit `.htaccess` oder nginx + PHP-FPM | für nginx siehe Abschnitt 8 und `docs/nginx.conf.example` |
| Speicherplatz | ca. 1 MB + Fotos | |

**MySQL oder SQLite?**
- **MySQL/MariaDB** ist der Standard bei Hostern und lässt sich bequem mit phpMyAdmin sichern. Empfohlen.
- **SQLite** speichert alles in einer Datei unter `storage/`. Ideal, wenn das Paket keine Datenbank enthält. Für einen kleinen Verleih reicht das völlig aus.

---

## 2. Vorbereitung beim Hoster

1. **Domain/Subdomain anlegen**, z. B. `verleih.eure-domain.de`, und auf einen eigenen Ordner zeigen lassen (z. B. `/verleih`).
   Eine Installation in einem Unterordner (`eure-domain.de/verleih/`) funktioniert genauso.
2. **PHP-Version** für diese Domain auf 7.3 oder höher stellen.
3. **SSL-Zertifikat** (Let's Encrypt) aktivieren, damit die Seite über `https://` läuft.
4. Bei MySQL: **Datenbank anlegen** und notieren:
   - Server (Host), z. B. `localhost`, `rdbms.strato.de` oder `db1234.hosting-data.io`
   - Datenbankname
   - Benutzername
   - Passwort

---

## 3. Dateien hochladen

1. ZIP entpacken (oder den Ordner `stagepool/` aus dem Repository nehmen).
2. **Den Inhalt** des Ordners `stagepool/` per FTP/SFTP (z. B. mit FileZilla) in das Zielverzeichnis der Domain laden.
   Wichtig: auch die versteckten Dateien `.htaccess` und `.user.ini` mit hochladen (in FileZilla: *Server → Anzeige versteckter Dateien erzwingen*).
3. **Schreibrechte** prüfen. Diese Ordner muss PHP beschreiben dürfen:
   - `config/`
   - `storage/`, `storage/logs/` und `storage/receipts/`
   - `uploads/products/`

   Meist passt das automatisch. Falls der Installer meckert: per FTP-Programm Rechte `755` setzen, notfalls `775`. Nie `777`, außer der Hoster schreibt es vor.

---

## 4. Installations-Assistent

1. Im Browser `https://verleih.eure-domain.de/install/` öffnen.
2. **Server-Check:** Alle Pflichtpunkte müssen grün sein.
3. **Datenbank:** MySQL-Daten eintragen oder SQLite wählen.
   Das *Tabellen-Präfix* (Standard `sp_`) erlaubt es, mehrere Anwendungen in einer Datenbank zu betreiben.
4. **Admin-Zugang:** Name, E-Mail und Passwort (mind. 8 Zeichen) für euren ersten Zugang.
5. **Demo-Daten:** zum Ausprobieren empfohlen (30 Beispielgeräte, 2 Standorte, Beispielbuchungen). Die könnt ihr danach im Backend anpassen oder löschen.
6. *Jetzt installieren* klicken. Fertig.

Der Assistent schreibt `config/config.php`, legt alle Tabellen an und sperrt sich danach selbst über `storage/installed.lock`. Den Ordner `install/` dürft ihr danach löschen.

> **Fehler „SQLSTATE[HY000] [2002]“?** Den Datenbank-Server prüfen. Statt `localhost` hilft oft `127.0.0.1` oder der vom Hoster genannte Hostname.

---

## 5. Erste Schritte im Backend

Unter `https://verleih.eure-domain.de/admin/` anmelden und dann:

1. **Einstellungen → Allgemein:** Name der Plattform, Kontakt-E-Mail, Telefon, Benachrichtigungs-Adresse(n)
2. **Einstellungen → Firma & Rechnungen:** Name, Anschrift, Steuernummer, Bankverbindung (erscheinen auf Angeboten und Rechnungen)
3. **Einstellungen → Rechtliches:** **Impressum** und **Datenschutzerklärung** vollständig ausfüllen (in Deutschland Pflicht!)
4. **Einstellungen → Buchungsregeln:** Puffertage (Standard 1), Aufschlag Endkundenpreis (Standard 25 %), Preis weiterer Miettage (Standard 50 %), Vorlauf, Mindest- und Höchstdauer
5. **Einstellungen → E-Mail-Versand** einrichten und eine Testmail senden (siehe Abschnitt 6)
6. **Standorte** anlegen, an denen die Geräte stehen
7. **Team:** Zugänge für alle anlegen, die Material einbringen
8. **Geräte** anlegen oder die Demo-Geräte anpassen (Fotos hochladen!)

Bedienung im Detail: [HANDBUCH.md](HANDBUCH.md).

---

## 6. E-Mail-Versand

Die Plattform verschickt E-Mails bei neuen Anfragen (an Kunde und Team), bei Statusänderungen sowie Angebote und Rechnungen als PDF-Anhang. Im Modus „Nur protokollieren“ werden die PDFs zusätzlich in `storage/logs/` abgelegt.

| Modus | Wann verwenden |
|---|---|
| **PHP mail()** | Funktioniert bei den meisten Hostern sofort. Absender sollte eine Adresse eurer Domain sein. |
| **SMTP** (empfohlen) | Versand über ein echtes Postfach. Landet seltener im Spam. |
| **Nur protokollieren** | Zum Testen: E-Mails landen in `storage/logs/mail-….log` (Dateiname mit zufälligem Zusatz). |

Typische SMTP-Daten (bitte beim Hoster prüfen):

| Hoster | Server | Port / Verschlüsselung |
|---|---|---|
| IONOS | `smtp.ionos.de` | 587 / STARTTLS |
| Strato | `smtp.strato.de` | 465 / SSL |
| all-inkl | `w0xxxxxx.kasserver.com` | 587 / STARTTLS |
| Gmail (App-Passwort) | `smtp.gmail.com` | 587 / STARTTLS |

Als **Absender-Adresse** dieselbe Adresse wie das SMTP-Postfach eintragen. Fehlgeschlagene Mails werden in `storage/logs/mail-….log` (Dateiname mit zufälligem Zusatz) protokolliert.

---

## 7. Sicherheit

Bereits eingebaut:
- `.htaccess` sperrt `inc/`, `config/`, `storage/` und `docs/` für Zugriffe aus dem Web.
- In `uploads/` wird kein PHP ausgeführt.
- Passwörter werden mit `password_hash()` gespeichert; nach 6 Fehlversuchen ist der Login 15 Minuten gesperrt.
- Alle Formulare sind gegen CSRF geschützt.

Bitte prüfen:
- Ruft `https://verleih.eure-domain.de/config/config.php` und `…/storage/` auf. Beides muss **„403 Forbidden“** (oder eine leere Seite) liefern.
- HTTPS erzwingen: In den meisten Hosting-Panels gibt es dafür einen Schalter. Alternativ diese Zeilen oben in die `.htaccess` einfügen:
  ```apache
  RewriteEngine On
  RewriteCond %{HTTPS} !=on
  RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
  ```

---

## 8. nginx statt Apache

Stagepool läuft auch mit **nginx + PHP-FPM** (getestet mit nginx 1.24 und PHP 8.3, Code geprüft für PHP 7.3 bis 8.3).

nginx liest keine `.htaccess`-Dateien. Die Schutzregeln (interne Ordner sperren, keine PHP-Ausführung in `uploads/`) müssen deshalb in die Server-Konfiguration. Eine fertige, getestete Vorlage liegt unter **`docs/nginx.conf.example`**:

1. Vorlage nach `/etc/nginx/sites-available/stagepool` kopieren.
2. `server_name`, `root` und den PHP-FPM-Socket anpassen (z. B. `/run/php/php8.2-fpm.sock`).
3. Aktivieren: `ln -s /etc/nginx/sites-available/stagepool /etc/nginx/sites-enabled/`, dann `nginx -t && systemctl reload nginx`.
4. HTTPS z. B. mit `certbot --nginx -d verleih.example.de`.
5. Schreibrechte für den PHP-FPM-Benutzer (meist `www-data`): `chown -R www-data:www-data config storage uploads`.
6. Prüfen: `https://…/config/config.php`, `https://…/storage/` und `https://…/inc/functions.php` müssen **404** liefern.

Bei Managed-Hostern mit nginx (ohne eigenen Server-Zugang) bitte den Support bitten, die Sperr-Regeln aus der Vorlage zu übernehmen.

**PHP-Module** (Debian/Ubuntu-Paketnamen für PHP 8.2): `php8.2-fpm php8.2-mysql` (oder `php8.2-sqlite3`) `php8.2-mbstring`, empfohlen `php8.2-gd`. `json`, `hash`, `session` und `iconv` sind in PHP 8 fest eingebaut.

**Upload-Größe:** In der nginx-Vorlage ist `client_max_body_size 16m` gesetzt. Die Datei `.user.ini` (12 MB Upload) wertet PHP-FPM selbst aus; alternativ `upload_max_filesize`/`post_max_size` in der `php.ini` setzen.

---

## 9. Backup

Regelmäßig sichern:
- **Datenbank:** per phpMyAdmin → *Exportieren* (MySQL) bzw. die Datei `storage/stagepool-….sqlite` (SQLite)
- **Fotos:** Ordner `uploads/products/`
- **Buchhaltungsbelege:** Ordner `storage/receipts/` (wichtig – Aufbewahrungspflicht!)
- **Konfiguration:** `config/config.php`

Viele Hoster machen zusätzlich automatische Backups. Trotzdem lohnt es sich, vor Updates selbst zu sichern.

---

## 10. Updates einspielen

1. Backup machen (siehe oben).
2. Neue Dateien hochladen und vorhandene überschreiben, **außer** `config/config.php`, `storage/` und `uploads/products/`.
3. Einmal eine beliebige Seite aufrufen. Neue Tabellen und Spalten werden dabei **automatisch** ergänzt, alle Daten bleiben erhalten.

---

## 11. Passwort vergessen / Notfall

- Ein anderer Admin kann unter **Team** ein neues Passwort setzen.
- Ist kein Admin mehr erreichbar: per FTP die Datei `storage/installed.lock` löschen und `/install/` aufrufen. Dieselben Datenbank-Daten und **dieselbe Admin-E-Mail** eintragen. Der Assistent setzt nur das Passwort neu, alle Daten bleiben erhalten. Demo-Daten werden nur in eine leere Datenbank geschrieben.

---

## 12. Fehlerhilfe

| Problem | Lösung |
|---|---|
| Weiße Seite / „Da ist etwas schiefgelaufen“ | Vor der Installation zeigt die Fehlerseite die Ursache direkt an; danach steht sie in `storage/logs/php-error-….log`. Außerdem `diagnose.php` aufrufen. Zum Debuggen in `config/config.php` vorübergehend `'debug' => true` setzen. |
| Installer zeigt rote Punkte bei Ordnern | Schreibrechte setzen (Abschnitt 3) |
| CSS/Design fehlt, Links falsch | Bei Unterordner-Installation in `config/config.php` `'base_path' => '/verleih/'` setzen |
| Links in E-Mails falsch (z. B. hinter Proxy) | `'site_url' => 'https://verleih.eure-domain.de'` in `config/config.php` |
| Foto-Upload schlägt fehl | Datei kleiner als 10 MB? `.user.ini` mit hochgeladen? Schreibrechte für `uploads/products/`? |
| E-Mails kommen nicht an | Spam-Ordner prüfen, auf SMTP umstellen, Testmail in den Einstellungen senden, `storage/logs/mail-….log` (Dateiname mit zufälligem Zusatz) prüfen |
| „Die Sitzung ist abgelaufen“ | Seite neu laden. Tritt das dauerhaft auf, blockiert der Browser evtl. Cookies. |

---

## 13. Manuelle Konfiguration (für Profis)

Statt des Assistenten kann `config/config.sample.php` nach `config/config.php` kopiert und angepasst werden. Die Tabellen werden dann beim ersten Aufruf von `/install/` angelegt, oder ihr nutzt die Statements aus `inc/schema.php`.
