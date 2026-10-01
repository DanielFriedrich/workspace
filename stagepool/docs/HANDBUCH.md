# Handbuch fürs Team

Das Backend erreicht ihr unter **`/admin/`** (Link „Team-Login“ unten auf jeder Seite).

---

## Der Ablauf einer Ausleihe

```
Kunde fragt an ──▶ Angefragt ──▶ Bestätigt ──▶ Ausgegeben ──▶ Zurückgegeben
   (reserviert)        │              │                         (wieder frei)
                       ▼              ▼
                   Abgelehnt      Storniert        (Geräte sofort wieder frei)
```

| Status | Bedeutung | Blockt Geräte? |
|---|---|---|
| **Angefragt** | Kunde hat angefragt, Geräte sind *reserviert* | ja |
| **Bestätigt** | ihr habt zugesagt, *fest gebucht* | ja |
| **Ausgegeben** | Technik wurde abgeholt | ja |
| **Zurückgegeben** | alles zurück, Ausleihe abgeschlossen | nein |
| **Abgelehnt / Storniert** | findet nicht statt | nein |

Zu jeder Buchung blockt das System zusätzlich **einen Puffertag davor und danach** (einstellbar), damit Abholung und Rückgabe nicht mit anderen Buchungen kollidieren.

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

**Kundenlink:** Rechts steht der persönliche Link des Kunden. Darüber sieht er den Status und kann bis zum Mietbeginn selbst stornieren.

**Verlauf:** Jede Änderung wird mit Zeitpunkt und Person protokolliert.

---

## Buchung manuell anlegen

Für Anfragen per Telefon, WhatsApp oder direkt im Gespräch: **Übersicht → Buchung manuell anlegen** (oder „Neue Buchung“ in der Buchungsliste).
Zeitraum, Status (meist „Bestätigt“), Kunde und Geräte eintragen und speichern. Die E-Mail-Adresse ist hier optional.

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
| **Team** | Buchungen, Geräte, Kategorien, Standorte, Sperrzeiten |
| **Admin** | zusätzlich Team-Zugänge und Einstellungen |

Unter **Mein Konto** ändert jede Person ihren Namen, ihre Telefonnummer und ihr Passwort.

---

## Einstellungen (Admin)

| Bereich | Inhalt |
|---|---|
| Allgemein | Name, Claim, Startseitentext, Kontaktdaten, wer bei neuen Anfragen eine Mail bekommt |
| Buchungsregeln | Puffertage, Vorlauf (frühester Mietbeginn), Mindest- und Höchstdauer, Preishinweis |
| Texte | Abholung & Lieferung, Mietbedingungen |
| Rechtliches | Impressum, Datenschutzerklärung |
| E-Mail-Versand | mail()/SMTP/Protokoll, Absender, Testmail |

Formatierung in Textfeldern: Leerzeile = neuer Absatz, `## Überschrift`, `- Listenpunkt`, `**fett**`, Links als `[Text](https://…)`.

---

## Tipps

- Anfragen zügig bestätigen oder ablehnen, denn solange sie offen sind, blockieren sie die Geräte.
- Bei Flammen-, Funken- und Lasergeräten in der persönlichen Nachricht auf die Einweisung hinweisen.
- Kaution bei Abholung kassieren und in der internen Notiz vermerken.
