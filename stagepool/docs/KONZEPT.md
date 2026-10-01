# Konzept

## Idee

Mehrere Personen besitzen Event-Technik (Licht, Nebel, Flammen, Funken, Ton, Mischpulte, Mikrofone, Laser …). Statt dass alles ungenutzt im Keller steht, wird es gemeinsam als **Mini-Verleih für kleine Veranstaltungen** angeboten: Geburtstage, Vereinsfeste, Hochzeiten, Partys, Schulaufführungen. Großveranstaltungen gehören ausdrücklich nicht dazu.

## Nutzerreise

1. **Entdecken:** Katalog mit Kategorien, Suche, Standort-Filter
2. **Zeitraum wählen:** Von/Bis im Filter, auf der Produktseite oder per Klick im Belegungskalender. Ab dann zeigt der Katalog nur noch, was im ganzen Zeitraum frei ist, inklusive Preis für den Zeitraum.
3. **Zusammenstellen:** Geräte (mit Anzahl) in den Anfragekorb legen. Ein Korb entspricht einer Veranstaltung mit einem Zeitraum.
4. **Anfragen:** Kontaktdaten, Anlass, Übergabe (Selbstabholung oder Lieferung nach Absprache), Nachricht. Mit dem Absenden sind die Geräte **reserviert**.
5. **Bestätigung** durch das Team: jetzt **fest gebucht**
6. **Abholung** am Standort: Status „Ausgegeben“
7. **Rückgabe:** Status „Zurückgegeben“, die Geräte sind sofort wieder frei

## Verfügbarkeitslogik

- Belegende Status: `requested` (angefragt), `offered` (Angebot gesendet), `confirmed` (bestätigt), `picked_up` (ausgegeben)
- Freigebende Status: `returned`, `rejected`, `cancelled`
- Eine Buchung vom Tag **S** bis **E** belegt die Tage **S − Puffer … E + Puffer** (Puffer = Einstellung `buffer_days`, Standard 1).
  Zwischen zwei Ausleihen desselben Exemplars liegt also immer mindestens ein Puffertag.
- **Sperrzeiten** (`blocks`) gelten für ein Gerät, einen Standort oder alles, jeweils ohne zusätzlichen Puffer.
- **Mengen:** Jedes Gerät hat eine Anzahl. Für jeden Tag wird die belegte Menge summiert; frei ist `Anzahl − belegt`. Für einen Zeitraum zählt der knappste Tag.
- Beim Absenden einer Anfrage wird unter einer exklusiven Sperre (`storage/booking.lock`) erneut geprüft. So können zwei gleichzeitige Anfragen nicht dasselbe Gerät buchen.
- **Preis (Staffel):** Tagespreis × Anzahl × (1 + (Miettage − 1) × 50 %). Der Prozentsatz ist einstellbar (`extra_day_percent`), Miettage zählen von Start- bis Endtag inklusive. Puffertage sind kostenlos. Im Backend lässt sich der Gesamtpreis manuell überschreiben.
- Regeln: Vorlauf (`lead_days`, frühester Start), Mindest- und Höchstdauer (`min_days`, `max_days`). Im Backend gelten diese Grenzen nicht.

Kalenderfarben: *frei*, *teilweise frei*, *reserviert* (nur angefragt), *gebucht* (mindestens ein bestätigter oder ausgegebener Anteil), *Puffer*, *gesperrt*.

## Datenmodell

| Tabelle | Inhalt |
|---|---|
| `settings` | Schlüssel/Wert-Einstellungen |
| `users` | Team-Zugänge (Rolle `admin`/`member`), zugleich Besitzer von Geräten |
| `login_attempts` | Fehlversuche (gehashte IP) für die Login-Sperre |
| `locations` | Standorte |
| `categories` | Kategorien mit Icon und Farbe |
| `products` | Geräte: Kategorie, Standort, Besitzer, Preis/Tag, Kaution, Anzahl, Texte, Foto, sichtbar |
| `product_stock` | Bestandsposten je Gerät: Eigentümer, Standort, Stückzahl (Summe = `products.quantity`) |
| `booking_allocations` | Zuteilung: welche Bestandsposten mit welcher Stückzahl eine Buchungsposition bedienen |
| `bookings` | Anfragen/Buchungen: Code, Token für den Kundenlink, Status, Zeitraum, Kunde, Summen |
| `booking_items` | Positionen inkl. Preis-Schnappschuss (spätere Preisänderungen wirken nicht rückwirkend) |
| `booking_log` | Verlauf (wer hat wann was geändert) |
| `blocks` | Sperrzeiten (`scope` = `all`/`location`/`product`) |
| `documents` | Angebote und Rechnungen: fortlaufende Nummer, Schnappschuss der Positionen (JSON), Summen, Status, bezahlt am |
| `transactions` | Buchhaltung: Einnahmen/Ausgaben mit Kategorie, Betrag, enthaltener USt., Zahlart, „ausgelegt von“, Beleg-Datei, Verknüpfung zu Rechnung/Buchung/Gerät |

Datumswerte werden als Text `JJJJ-MM-TT` gespeichert. Dadurch funktionieren dieselben Abfragen in MySQL und SQLite.

## Technik

- PHP 7.3+ ohne Framework und ohne Composer; jede Seite ist eine eigene PHP-Datei (keine Rewrite-Regeln nötig)
- PDO mit Prepared Statements, Tabellen-Präfix konfigurierbar
- Sessions für Warenkorb und Login (Cookie `HttpOnly`, `SameSite=Lax`, bei HTTPS `Secure`)
- Progressive Enhancement: Alles funktioniert ohne JavaScript. JS ergänzt das Hinzufügen ohne Neuladen, die Kalenderauswahl per Klick und die Live-Preise.
- E-Mails: `mail()`, eingebauter SMTP-Client (STARTTLS/SSL, AUTH LOGIN) oder Protokolldatei

## Design

Inspiriert von Club-, Festival- und Bühnentechnik-Seiten: **dunkle Bühne**, farbige **Lichtkegel** im Hero (sanft animiert, abschaltbar über „Bewegung reduzieren“), Neon-Verläufe Magenta → Violett → Cyan, kräftige Display-Schrift (*Unbounded*) mit gut lesbarer Grotesk (*Manrope*). Geräte ohne Foto bekommen einen leuchtenden Platzhalter in der Farbe ihrer Kategorie, sodass der Katalog auch ohne Fotos stimmig wirkt. Ein Laufband zeigt die Kategorien wie auf einem Festival-Line-up.

Mobile first: alle Seiten funktionieren ab 360 px Breite. Der Belegungskalender scrollt horizontal, die Gerätenamen bleiben dabei stehen.

## Preislisten & Bestand

- `products.price_day` = Endkundenpreis, `products.price_internal` = Teampreis (0 = zurückgerechnet aus Endkundenpreis / (1 + Aufschlag)). `price_auto = 1`: Endkundenpreis = Teampreis × (1 + `customer_markup` %), gerundet auf 0,50 €.
- Preisstufe einer Anfrage: eingeloggtes Teammitglied → `internal`, sonst `customer` (`bookings.price_tier`, `team_user_id`). Positionen speichern den angewandten Tagespreis.
- Verfügbarkeit wird pro Gerät über die Gesamtzahl gerechnet; Standort-Sperren reduzieren die Kapazität um die Stückzahl an diesem Standort.
- Zuteilung (`booking_allocate`): je Position die Bestandsposten mit ausreichend freien Exemplaren (inkl. Puffer und Sperren), bevorzugt bereits genutzte Standorte. Wird bei jeder Änderung der Positionen neu berechnet und kann manuell überschrieben werden.

## Belege & Buchhaltung

- Angebote/Rechnungen speichern beim Erstellen einen unveränderlichen Schnappschuss; das PDF wird daraus bei jedem Abruf erzeugt (FPDF, Standardschrift Helvetica, Windows-1252 inkl. €).
- Rechnungsnummern sind pro Jahr fortlaufend (`RE-2026-001`), vergeben unter derselben Dateisperre wie Buchungen. Stornierte Rechnungen bleiben erhalten.
- „Bezahlt“ erzeugt genau eine Einnahme-Buchung, die mit der Rechnung verknüpft ist (Zurücknehmen/Stornieren entfernt sie wieder).
- Belege der Buchhaltung liegen in `storage/receipts/` und werden nur über das Backend ausgeliefert.
- Datenbank-Updates laufen automatisch über `SP_SCHEMA_VERSION` / `schema_migrate()`.

## Mögliche Erweiterungen

- Mehrere Fotos pro Gerät / Galerie
- Pakete („Party-Set S“: 2 Boxen + Mikro + 4 PARs)
- Kalender-Export (iCal) für das Team
- Automatischer Verfall unbestätigter Anfragen nach X Tagen
- Übergabeprotokoll / Schadensdokumentation mit Fotos
