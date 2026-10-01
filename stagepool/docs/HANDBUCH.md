# Handbuch fürs Team

Das Backend erreicht ihr unter **`/admin/`** (Link „Team-Login“ unten auf jeder Seite).

---

## Der Ablauf einer Ausleihe

```
Kunde fragt an ──▶ Angefragt ──▶ Angebot gesendet ──▶ Bestätigt ──▶ Ausgegeben ──▶ Zurückgegeben
   (reserviert)        │          (optional, PDF)          │          (Rechnung)       (wieder frei)
                       ▼                                   ▼
                   Abgelehnt                           Storniert      (Geräte sofort wieder frei)
```

| Status | Bedeutung | Blockt Geräte? |
|---|---|---|
| **Angefragt** | Kunde hat angefragt, Geräte sind *reserviert* | ja |
| **Angebot gesendet** | ihr habt ein Angebot (PDF) geschickt, Geräte bleiben reserviert | ja |
| **Bestätigt** | ihr habt zugesagt, *fest gebucht* | ja |
| **Ausgegeben** | Technik wurde abgeholt | ja |
| **Zurückgegeben** | alles zurück, Ausleihe abgeschlossen | nein |
| **Abgelehnt / Storniert** | findet nicht statt | nein |

Zu jeder Buchung blockt das System zusätzlich **einen Puffertag davor und danach** (einstellbar), damit Abholung und Rückgabe nicht mit anderen Buchungen kollidieren.

### Preise

Der erste Miettag kostet den vollen Tagespreis, **jeder weitere Tag 50 %** davon (einstellbar unter *Einstellungen → Buchungsregeln*).
Beispiel 100 € pro Tag: 1 Tag = 100 €, 2 Tage = 150 €, 3 Tage = 200 €. Website, Anfragekorb, Buchungen und Rechnungen rechnen automatisch so.

---

## Übersicht (Dashboard)

- **Offene Anfragen:** warten auf eure Entscheidung
- **Abholungen (nächste 7 Tage):** bestätigte Buchungen, die bald starten
- **Rückgaben:** alles, was gerade verliehen ist. Überfällige Rückgaben sind rot markiert.
- **Aktuelle Sperrzeiten**

In der Seitenleiste zeigt eine gelbe Zahl, wie viele Anfragen offen sind.

---

## Anfragen bearbeiten

1. **Anfragen & Buchungen** öffnen oder auf eine Anfrage im Dashboard klicken.
2. Oben stehen die passenden Aktionen:
   - **Bestätigen** oder **Ablehnen** (bei neuen Anfragen)
   - **Ausgabe erfassen** (wenn die Technik abgeholt wird)
   - **Rückgabe erfassen** (wenn alles zurück ist, ab dann ist das Gerät wieder frei)
   - **Stornieren** / **Wieder öffnen**
3. Beim Klick öffnet sich ein kleines Fenster:
   - *Kunde per E-Mail informieren*: schickt automatisch eine passende Mail
   - *Persönliche Nachricht*: z. B. „Abholung Freitag ab 17 Uhr, bitte Ausweis mitbringen“
4. **Ausführen** klicken.

**Bearbeiten:** Darunter könnt ihr Zeitraum, Geräte, Mengen und Kundendaten ändern. Das System prüft dabei, ob alles frei ist. Bei Überschneidungen erscheint ein Hinweis; mit „Trotz Überschneidung speichern“ könnt ihr bewusst überbuchen.

**Preis anpassen:** Häkchen bei „Gesamtpreis manuell festlegen“ setzen, z. B. für Rabatte, Pauschalen oder Lieferkosten.

**Angebot senden:** Bei neuen Anfragen gibt es den Knopf **„Angebot senden“**. Das erzeugt ein PDF-Angebot (Nummer z. B. `AN-2026-001`) mit allen Geräten und Preisen. Wahlweise geht es direkt per E-Mail mit PDF-Anhang an den Kunden; die Buchung steht dann auf *Angebot gesendet*. Sagt der Kunde zu, klickt ihr **„Angebot angenommen“**.

**Rechnung erstellen:** Nach der Ausgabe (und nach der Rückgabe) erscheint **„Rechnung erstellen“**. Die Rechnung bekommt eine fortlaufende Nummer (z. B. `RE-2026-007`), kann per E-Mail verschickt und/oder als PDF heruntergeladen werden. Hat der Kunde schon bar bezahlt, direkt „Bereits bezahlt“ anhaken.

Rechts unter **Angebote & Rechnungen** seht ihr alle Belege der Buchung. Tipp: Rabatte oder Lieferkosten *vorher* über „Gesamtpreis manuell festlegen“ eintragen – der Beleg übernimmt den Betrag und weist die Differenz als Anpassung aus.

**Kundenlink:** Rechts steht der persönliche Link des Kunden. Darüber sieht er den Status und kann bis zum Mietbeginn selbst stornieren.

**Verlauf:** Jede Änderung wird mit Zeitpunkt und Person protokolliert.

---

## Buchung manuell anlegen

Für Anfragen per Telefon, WhatsApp oder direkt im Gespräch: **Übersicht → Buchung manuell anlegen** (oder „Neue Buchung“ in der Buchungsliste).
Zeitraum, Status (meist „Bestätigt“), Kunde und Geräte eintragen und speichern. Die E-Mail-Adresse ist hier optional.

---

## Angebote & Rechnungen

Liste aller Belege mit Filter nach Art, Status und Jahr. Oben seht ihr die Rechnungssumme des Jahres und was noch offen ist; überfällige Rechnungen sind rot markiert.

In einem Beleg könnt ihr:
- das **PDF ansehen/herunterladen**,
- ihn **per E-Mail senden** (auch an eine andere Adresse),
- bei Rechnungen die **Zahlung erfassen** (Datum + Zahlart) – sie wird automatisch als Einnahme in der Buchhaltung verbucht,
- Angebote als **angenommen** markieren (bestätigt auch die Buchung),
- Rechnungen **stornieren** (die Nummer bleibt vergeben, wie es das Steuerrecht verlangt).

Positionen und Beträge eines Belegs sind nach dem Erstellen fest. Für Korrekturen: Rechnung stornieren, Buchung anpassen, neue Rechnung erstellen.

Absender, Steuernummer, Bankverbindung, Nummern-Präfixe, Zahlungsziel und Texte stellt ihr unter **Einstellungen → Firma & Rechnungen** ein. Seid ihr umsatzsteuerpflichtig, tragt den Steuersatz (z. B. 19) ein – dann wird die enthaltene Umsatzsteuer ausgewiesen; bei 0 erscheint der Kleinunternehmer-Hinweis nach § 19 UStG.

---

## Buchhaltung

Eine einfache Einnahmen-Überschuss-Übersicht für den Pool:

- **Einnahmen** entstehen automatisch, sobald eine Rechnung bezahlt ist. Weitere Einnahmen (z. B. bar ohne Rechnung) erfasst ihr über **„+ Einnahme“**.
- **Ausgaben** über **„Ausgabe erfassen“**: Datum, Betrag, Beschreibung, Kategorie (Miete, Anschaffung Equipment, Regale & Kisten, Versicherung, Reparatur …), Händler, Zahlart und **wer bezahlt bzw. ausgelegt hat**. Optional lässt sich ein Gerät verknüpfen (z. B. bei der Anschaffung).
- **Belege** (PDF oder Handyfoto der Quittung) direkt hochladen. Sie werden geschützt gespeichert und sind nur im Backend abrufbar.
- Oben: Einnahmen, Ausgaben und Ergebnis des Jahres, ein Monatsdiagramm (Klick auf einen Monat filtert die Liste) und die Ausgaben nach Kategorie.
- **„Ausgelegt von“** zeigt, wer wie viel aus eigener Tasche bezahlt hat – praktisch für die interne Abrechnung.
- **CSV-Export** pro Jahr für Excel oder die Steuerberatung (inkl. Kennzeichnung, ob ein Beleg vorliegt).

Die Kategorien passt ihr unter **Einstellungen → Buchhaltung** an (eine pro Zeile).

> Hinweis: Das ersetzt keine Steuerberatung. Für die Steuererklärung bitte den CSV-Export und die Belege an die Steuerberatung geben bzw. selbst prüfen.

---

## Belegungsplan

Zeigt alle Geräte über 2 bis 8 Wochen. Farben:

- 🟩 frei · 🟨 teilweise frei (Zahl = freie Stück) · 🟧 reserviert (angefragt) · 🟥 gebucht · ▨ Puffertag · ▩ gesperrt

Ein Klick auf eine belegte Zelle öffnet die Buchung. Beim Überfahren mit der Maus seht ihr Kunde und Buchungsnummer.
Öffentlich gibt es denselben Kalender **ohne Kundennamen** unter „Belegungskalender“.

---

## Sperrzeiten

Für Zeiten, in denen etwas nicht verliehen werden soll, ohne dass es eine Buchung gibt:

| Bereich | Beispiel |
|---|---|
| **Standort** | „Ich bin vom 1. bis 14. August im Urlaub“: alle Geräte an diesem Standort sind gesperrt |
| **Einzelnes Gerät** | Reparatur, Wartung, Eigenbedarf |
| **Alles** | Betriebspause für den ganzen Pool |

Der Grund ist nur intern sichtbar. Bereits bestehende Buchungen im Zeitraum bleiben bestehen. Das System weist euch darauf hin, damit ihr sie klären könnt.

---

## Geräte

- **Neues Gerät:** Name, Kategorie, Standort, Preis pro Tag, Kaution, Anzahl, Besitzer, Texte und Foto
- **Anzahl im Pool:** Habt ihr z. B. 8 gleiche Scheinwerfer, legt *ein* Gerät mit Anzahl 8 an. Kunden können dann 1 bis 8 Stück anfragen.
- **Technische Daten:** eine Zeile pro Angabe, Format `Bezeichnung: Wert`
- **Foto:** JPG/PNG/WebP, Querformat 4:3 sieht am besten aus. Ohne Foto erscheint ein leuchtendes Kategorie-Icon.
- **Sichtbar:** ausgeschaltete Geräte erscheinen nicht auf der Website
- **Highlight:** wird im Katalog weiter oben gezeigt
- **Duplizieren:** praktisch für ähnliche Geräte
- **Löschen:** Geräte mit Buchungshistorie werden nur ausgeblendet, damit alte Buchungen vollständig bleiben

In der Geräteliste lässt sich nach Kategorie, Standort und **Besitzer** filtern. So sieht jede Person schnell ihr eigenes Material.

---

## Kategorien & Standorte

- **Kategorien:** Name, Icon und Leuchtfarbe. Sie erscheinen als Filter im Katalog. Löschen geht nur, wenn keine Geräte mehr darin sind.
- **Standorte:** Name, Adresse und ein öffentlicher Abholhinweis. Öffentlich sichtbar sind nur Name, PLZ und Ort; die Straße steht in der Bestätigungsmail. Über „Standort sperren“ legt ihr direkt eine Sperrzeit an (z. B. Urlaub).

Wenn ihr später einen gemeinsamen Lagerort habt: neuen Standort anlegen, Geräte umstellen, alte Standorte deaktivieren.

---

## Team & Rollen

| Rolle | darf |
|---|---|
| **Team** | Buchungen, Angebote & Rechnungen, Buchhaltung, Geräte, Kategorien, Standorte, Sperrzeiten |
| **Admin** | zusätzlich Team-Zugänge und Einstellungen |

Unter **Mein Konto** ändert jede Person ihren Namen, ihre Telefonnummer und ihr Passwort.

---

## Einstellungen (Admin)

| Bereich | Inhalt |
|---|---|
| Allgemein | Name, Claim, Startseitentext, Kontaktdaten, wer bei neuen Anfragen eine Mail bekommt |
| Buchungsregeln | Puffertage, Preis weiterer Miettage (%), Vorlauf, Mindest- und Höchstdauer, Preishinweis |
| Firma & Rechnungen | Absender, Anschrift, Steuernummer, USt-Satz, Bankverbindung, Nummernkreise, Zahlungsziel, Texte |
| Buchhaltung | Kategorien für Einnahmen und Ausgaben |
| Texte | Abholung & Lieferung, Mietbedingungen |
| Rechtliches | Impressum, Datenschutzerklärung |
| E-Mail-Versand | mail()/SMTP/Protokoll, Absender, Testmail |

Formatierung in Textfeldern: Leerzeile = neuer Absatz, `## Überschrift`, `- Listenpunkt`, `**fett**`, Links als `[Text](https://…)`.

---

## Tipps

- Anfragen zügig bestätigen oder ablehnen, denn solange sie offen sind, blockieren sie die Geräte.
- Bei Flammen-, Funken- und Lasergeräten in der persönlichen Nachricht auf die Einweisung hinweisen.
- Kaution bei Abholung kassieren und in der internen Notiz vermerken.
