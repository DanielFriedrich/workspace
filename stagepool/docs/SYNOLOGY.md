# Installation auf einer Synology (DSM 7, Web Station, PHP 8.2, nginx)

Diese Anleitung geht davon aus, dass die Pakete **Web Station**, **PHP 8.2** und – für MySQL – **MariaDB 10** (plus optional **phpMyAdmin**) im Paket-Zentrum installiert sind.

> **Erst einmal klären, was fehlt:** Nach dem Hochladen im Browser `http://<NAS>/<ordner>/diagnose.php` öffnen. Die Seite zeigt rot, was noch eingerichtet werden muss (PHP-Erweiterungen, Schreibrechte, Sessions, Datenbank, Ordner-Schutz) und enthält einen Datenbank-Test.

---

## 1. Dateien ablegen

1. In der **File Station** im freigegebenen Ordner `web` einen Ordner anlegen, z. B. `web/stagepool`.
2. Das ZIP dorthin hochladen und mit Rechtsklick → **Extrahieren** entpacken. Wichtig: Die Dateien (`index.php`, `admin/`, `inc/` …) müssen **direkt** in `web/stagepool` liegen, nicht in einem weiteren Unterordner.

## 2. Schreibrechte für den Webserver

Der Webserver läuft auf der Synology als Benutzer **`http`**. Er muss in drei Ordnern schreiben dürfen:

- `web/stagepool/config`
- `web/stagepool/storage` (inkl. Unterordner)
- `web/stagepool/uploads` (inkl. Unterordner)

Je Ordner in der File Station: Rechtsklick → **Eigenschaften** → **Berechtigung** → **Erstellen** → Benutzer oder Gruppe **`http`** → Lesen **und Schreiben** erlauben → „Auf diesen Ordner, Unterordner und Dateien anwenden“ → Speichern.

## 3. PHP-Profil (Web Station → Skriptspracheneinstellungen)

PHP-8.2-Profil bearbeiten (oder ein neues anlegen) und unter **Erweiterungen** aktivieren:

| Erweiterung | wofür |
|---|---|
| `pdo_mysql` | MySQL/MariaDB (oder `pdo_sqlite` für SQLite) |
| `mbstring` (falls in der Liste) | Umlaute |
| `iconv` | Umlaute im PDF |
| `gd` | Fotos automatisch verkleinern (empfohlen) |
| `openssl` | E-Mail-Versand per SMTP mit Verschlüsselung |

Unter **Kerneinstellungen** (falls vorhanden) `upload_max_filesize` auf `12M` und `post_max_size` auf `16M` setzen.

Ist **open_basedir** aktiviert, muss der Pfad des Web-Ordners enthalten sein (Standard passt in der Regel).

## 4. Webdienst & Webportal

1. **Web Station → Webdienst → Erstellen → Nativer Skriptsprachen-Website**: Server **nginx**, PHP-Profil aus Schritt 3, Dokument-Stammverzeichnis `web/stagepool`.
2. **Webportal** anlegen: z. B. **namenbasiert** mit eurem Hostnamen oder **portbasiert** (z. B. Port 8080).

## 5. Datenbank (MariaDB 10)

1. In **phpMyAdmin** (oder per SQL) eine Datenbank anlegen, z. B. `stagepool` (Kollation `utf8mb4_unicode_ci`), und einen Benutzer mit allen Rechten darauf.
2. Im Installer eintragen:
   - **Server:** `127.0.0.1`
   - **Port:** `3307` ← MariaDB 10 auf der Synology läuft **nicht** auf dem Standard-Port 3306
   - alternativ **Socket:** `/run/mysqld/mysqld10.sock` (dann Server/Port egal)
3. Mit „Datenbank testen“ in `diagnose.php` lässt sich das vorher ausprobieren.

**Ohne MariaDB:** Im Installer „SQLite“ wählen (Erweiterung `pdo_sqlite` aktivieren) – dann ist keine Datenbank nötig.

## 6. Installation

`http://<NAS oder Hostname>/install/index.php` (bzw. mit Port oder Ordner) öffnen und den Assistenten ausfüllen. Danach unter `/admin/index.php` anmelden.

## 7. Interne Ordner schützen (wichtig)

nginx auf der Synology wertet die mitgelieferten `.htaccess`-Dateien **nicht** aus. Ob die internen Ordner geschützt sind, zeigt `diagnose.php` (Abschnitt „Schutz der internen Ordner“) und eine Warnung im Backend-Dashboard.

Stagepool schützt sich teilweise selbst: Zugangsdaten liegen in einer PHP-Datei (die nginx ausführt statt ausliefert), Log-Dateien und die SQLite-Datei haben zufällige, nicht erratbare Namen, und die Diagnose ist nach der Installation nur für Admins erreichbar. Für vollen Schutz von Belegen und Datenbank-Datei sollten aber die Sperr-Regeln aktiv sein:

**Variante A – per SSH (DSM 7):**
1. In der Systemsteuerung → Terminal & SNMP → **SSH aktivieren**, dann `ssh admin@<NAS>`.
2. Den Konfigurationsordner des Webportals finden:
   `sudo grep -rl "stagepool" /usr/local/etc/nginx/sites-enabled/ /etc/nginx/sites-enabled/ 2>/dev/null`
   In der gefundenen Datei steht eine Zeile `include /usr/local/etc/nginx/conf.d/<ID>/user.conf*;` – die `<ID>` ist der Ordnername.
3. Die Datei `docs/synology-nginx-user.conf` dorthin kopieren:
   `sudo cp /volume1/web/stagepool/docs/synology-nginx-user.conf /usr/local/etc/nginx/conf.d/<ID>/user.conf.stagepool`
4. Prüfen und neu laden: `sudo nginx -t && sudo synosystemctl reload nginx`
5. `diagnose.php` erneut öffnen – „storage/ ist von außen gesperrt“ muss grün sein.

Die genauen Pfade können je nach DSM-Version abweichen. Die Datei überlebt Updates von Web Station meist, nach größeren DSM-Updates bitte den Test in `diagnose.php` wiederholen.

**Variante B – ohne SSH:** Bis die Regeln aktiv sind, die Plattform nur im Heimnetz nutzen (kein Portfreigabe/QuickConnect nach außen).

## 8. HTTPS

DSM → Systemsteuerung → **Sicherheit → Zertifikat** (Let's Encrypt) und das Zertifikat dem Webportal zuweisen. Für den Zugriff von außen ggf. Portfreigabe 443 im Router.

## Häufige Fehler

| Meldung | Ursache / Lösung |
|---|---|
| `SQLSTATE[HY000] [2002]` | Falscher Datenbank-Port/Socket → Server `127.0.0.1`, Port `3307` oder Socket `/run/mysqld/mysqld10.sock` |
| `SQLSTATE[HY000] [1045] Access denied` | Benutzer/Passwort falsch oder Benutzer darf nur von `localhost` statt `127.0.0.1` – in phpMyAdmin den Benutzer für Host `%` oder `127.0.0.1` anlegen |
| `could not find driver` | `pdo_mysql` bzw. `pdo_sqlite` im PHP-Profil aktivieren |
| Installer zeigt rote Ordner | Schreibrechte für Benutzer `http` (Schritt 2) |
| „Die Sitzung ist abgelaufen“ bei jedem Absenden | Sessions funktionieren nicht – `diagnose.php` zeigt den Session-Ordner; PHP-Profil prüfen |
| 404 auf `/install/` | Webportal zeigt auf den falschen Ordner, oder die Dateien liegen in einem Unterordner – `install/index.php` direkt aufrufen |
| 502 Bad Gateway | PHP-Profil im Webdienst nicht zugewiesen oder PHP-8.2-Paket gestoppt |
| Weiße Seite / „Da ist etwas schiefgelaufen“ | Vor der Installation zeigt die Seite jetzt die genaue Ursache; danach steht sie in `storage/logs/php-error-….log` |
