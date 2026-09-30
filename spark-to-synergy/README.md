# ✨ Spark to Synergy

**Vom Funken zur Synergie – ein Lernspiel von variado. Die ErLebenswerkstatt**

Inspiriert von *Cell to Singularity*: Arbeite dich auf einem runden Spielbrett vom äußeren Ring bis ins Zentrum vor. Löse Aufgaben aus **Mensch, Natur und Technik**, sammle ✨ Funken und ✦ Synergiepunkte und halte dabei alles im Gleichgewicht, wie beim Bamboleo. Wer die Synergie erreicht, bekommt ein Finale mit Feuerwerk und kann am Gewinnspiel teilnehmen.

Das Spielkonzept steht in [`docs/KONZEPT.md`](docs/KONZEPT.md).

## Schnellstart

**Ohne Server** (zum Ausprobieren): `index.html` im Browser öffnen. Das Gewinnspiel-Formular öffnet dann eine vorausgefüllte E-Mail an `info@variado.de`.

**Mit Server** (Teilnahmen werden gespeichert, Node.js ≥ 18, keine Abhängigkeiten):

```bash
cd spark-to-synergy
ADMIN_TOKEN=ein-geheimes-passwort node server.js
# → http://localhost:4173
```

* Teilnahmen landen in `data/participants.jsonl` (eine Zeile pro Person, keine doppelten E-Mails).
* CSV-Export für die Auslosung: `http://localhost:4173/api/participants.csv?token=ein-geheimes-passwort`
* Port ändern: `PORT=8080 node server.js`

## Auf variado.de einbinden

Das Spiel ist eine statische Seite (HTML/CSS/JS, keine Bibliotheken) und lässt sich z. B. in WordPress per iframe einbetten:

```html
<iframe src="https://spiel.variado.de/" style="width:100%;height:100vh;border:0" title="Spark to Synergy"></iframe>
```

Soll das Gewinnspiel über einen eigenen Endpunkt laufen, trägt man in `js/content.js` unter `meta.submitUrl` die URL ein (erwartet `POST` mit JSON `{name, email, newsletter, code, durationSec, mistakes}`). Ohne erreichbaren Endpunkt greift automatisch der E-Mail-Fallback.

## Inhalte anpassen

Alle Texte, Fragen und Zahlen stehen in **`js/content.js`**:

* `nodes`: 15 Kernknoten (Bereich, Ring, Icon, Titel, Aufgabe)
* `bridges`: 6 Sonderaufgaben an den Überschneidungen
* `final`: die letzte Aufgabe im Zentrum
* `economy`: Kosten, Produktion, Synergie-Tore und Balance-Regeln
* `meta`: Kontakt-E-Mail, Datenschutz-Link, Endpunkt

Aufgabentypen: `choice` (eine richtige Antwort), `order` (Reihenfolge, `items` in richtiger Reihenfolge angeben) und `reflect` (keine falsche Antwort). Ein `|` im Titel markiert die Umbruchstelle auf dem Spielbrett (z. B. `'Erlebnis|pädagogik'`).

## Für Moderation & Tests

In der Browser-Konsole:

```js
S2S.debug.addFunken(10000)  // Funken hinzufügen
S2S.debug.solveAll()        // alles lösen → Zentrum ist bereit
```

Der Spielstand liegt im `localStorage` des Browsers. Mit ↺ oben rechts startet man neu.

## Datenschutz-Hinweis

Das Formular fragt eine ausdrückliche Einwilligung ab und verlinkt auf `https://www.variado.de/datenschutz` (in `content.js` anpassbar). Bitte vor dem Live-Gang die Teilnahmebedingungen und die Datenschutzerklärung um das Gewinnspiel ergänzen.
