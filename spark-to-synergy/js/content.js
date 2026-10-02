/*
 * Spark to Synergy – Spielinhalte
 * ------------------------------------------------------------
 * Alle Texte, Aufgaben und Zahlenwerte des Spiels stehen hier.
 * Wer Fragen austauschen oder ergänzen möchte, muss nur diese Datei ändern.
 *
 * Jeder Knoten hat einen Aufgaben-Pool (tasks). Beim ersten Spielen kommt die erste Aufgabe,
 * bei jedem weiteren Durchgang die nächste – so bleibt das Spiel auch beim zweiten Mal spannend.
 *
 * Aufgabentypen (alle mit „fact“ = Text für die „Wusstest du?“-Box nach dem Lösen):
 *   quiz     – eine richtige Antwort                       options, answer (Index)
 *   multi    – mehrere richtige Antworten                  options, answers [Indizes], visual: 'bulb' (optional)
 *   order    – Reihenfolge antippen                        items (in richtiger Reihenfolge)
 *   pairs    – Paare zuordnen                              left, right (gleicher Index = Paar)
 *   sort     – in Kategorien einsortieren                  cats, items [[Text, Kategorie-Index], …]
 *   estimate – mit Schieberegler schätzen                  min, max, step, start, answer, tolerance, unit
 *   breathe  – geführte Atemübung                          breaths
 *   reflect  – Reflexion ohne falsche Antwort              options (Auswahl) ODER minLength (eigener Satz)
 *
 * Ringe: Index 0 = äußerster Ring (Start), Index 4 = innerster Ring vor der Synergie.
 * In Ring 0 und 1 hat jeder Bereich zwei Knoten (slot 'a' links, 'b' rechts) – wie Äste eines Baums.
 * Ein „|“ im Titel markiert die Umbruchstelle auf dem Spielbrett: 'Kräuter|wanderung' trennt mit Bindestrich,
 * 'Land der| offenen Fernen' (Leerzeichen nach |) bricht ohne Bindestrich um.
 */
window.S2S_CONTENT = {
  meta: {
    title: 'Spark to Synergy',
    subtitle: 'Vom Funken zur Synergie – Mensch · Natur · Technik',
    org: 'variado. Die ErLebenswerkstatt',
    website: 'https://www.variado.de',
    contactEmail: 'info@variado.de',
    // Endpunkt für die Gewinnspiel-Teilnahme (siehe server.js). Leer lassen = nur E-Mail-Fallback.
    submitUrl: 'api/participants',
    privacyUrl: 'https://www.variado.de/datenschutz'
  },

  // Anordnung wie im variado-Logo: Technik oben links, Mensch oben rechts, Natur unten
  sectors: {
    mensch:  { label: 'Mensch',  color: '#E2A46F', angle: 60,  motto: 'durch Kreativität, Gemeinschaft und Lernen wachsen' },
    technik: { label: 'Technik', color: '#4AA3D0', angle: 300, motto: 'Innovation als Mittel für eine nachhaltige Welt' },
    natur:   { label: 'Natur',   color: '#6FBF73', angle: 180, motto: 'die Welt mit Respekt, Wissen und Achtsamkeit betrachten' }
  },

  rings: [
    { name: 'Wahrnehmen',   text: 'Staunen, neugierig werden, Fragen stellen.' },
    { name: 'Verstehen',    text: 'Zusammenhänge erkennen – wie funktioniert das?' },
    { name: 'Erleben',      text: 'Mit Kopf, Herz und Hand ausprobieren.' },
    { name: 'Gestalten',    text: 'Selbst wirksam werden und Neues erschaffen.' },
    { name: 'Verantworten', text: 'Bewusst handeln – für Mensch, Natur und Zukunft.' }
  ],

  // Wirtschaft (angelehnt an Cell to Singularity: Knoten sind „Generatoren“)
  economy: {
    coreCost:     [10, 80, 600, 3500, 15000],  // Funken-Kosten je Ring
    coreOutput:   [0.5, 2, 6, 16, 40],         // Funken pro Sekunde je Knoten
    bridgeCost:   { 1: 350, 3: 6000 },
    bridgeOutput: { 1: 4, 3: 25 },
    bridgeSynergy: 2,                          // Synergiepunkte pro gelöster Brücke
    harmonySynergy: 1,                         // Synergiepunkte, wenn ein Ring in allen 3 Bereichen fertig ist
    ringGate: [0, 0, 0, 2, 6],                 // benötigte Synergiepunkte, um einen Ring zu betreten
    maxUpgrade: 5,
    upgradeCostFactor: 1.75,
    upgradeOutputStep: 0.6,
    tapBase: 1,
    tapShare: 0.12,                            // Anteil der Produktion/s, der pro Klick dazukommt
    balanceFactor: [1.25, 1.0, 0.6],           // Produktionsfaktor bei Unterschied 0 / 1 / 2 Knoten
    maxImbalance: 2                            // größer darf der Unterschied nie werden (Bamboleo kippt)
  },

  nodes: [
    /* ================= TECHNIK ================= */
    {
      id: 'T0a', sector: 'technik', ring: 0, slot: 'a', requires: [], icon: '🔧', title: 'Was ist Technik?',
      blurb: 'Technik beginnt dort, wo Menschen etwas erschaffen, das es in der Natur so nicht gibt.',
      tasks: [
        {
          type: 'quiz',
          q: 'Bei variado ist Technik alles, was Menschen hergestellt haben, was es so in der Natur nicht gibt und was eine Funktion erfüllt. Was davon ist Technik?',
          options: ['Ein Pizza-Ofen mit Klappen, die den Luftstrom regeln', 'Ein Ameisenhaufen', 'Ein Regenbogen', 'Ein Bergkristall'],
          answer: 0,
          fact: 'Der Ameisenhaufen ist raffiniert gebaut, aber von Tieren. Vom Pizza-Ofen über den Rasenmäher bis zum Computer verrichtet Technik eine Form von Arbeit. Bei variado fragen wir immer auch, welchen Einfluss sie auf Mensch und Umwelt hat.'
        },
        {
          type: 'quiz',
          q: 'Warum lässt sich ein schwerer Stein mit einer langen Stange leichter anheben?',
          options: ['Ein längerer Hebelarm bedeutet: weniger Kraft für dieselbe Wirkung', 'Die Stange macht den Stein leichter', 'Metall zieht Steine an', 'Die Länge ist egal, es kommt nur auf die Kraft an'],
          answer: 0,
          fact: 'Das Hebelgesetz: Kraft × Kraftarm = Last × Lastarm. Schon Archimedes sagte: „Gebt mir einen festen Punkt, und ich hebe die Welt aus den Angeln.“'
        }
      ]
    },
    {
      id: 'T0b', sector: 'technik', ring: 0, slot: 'b', requires: [], icon: '⏳', title: 'Zeitreise',
      blurb: 'Jede Erfindung baut auf der vorherigen auf.',
      tasks: [
        {
          type: 'order',
          q: 'Bringe diese Erfindungen in ihre zeitliche Reihenfolge, von der ältesten zur jüngsten.',
          items: ['Faustkeil', 'Rad', 'Buchdruck mit beweglichen Lettern', 'Dampfmaschine', 'Computer'],
          fact: 'Faustkeil: über 1,5 Millionen Jahre alt. Rad: um 3500 v. Chr. Buchdruck: um 1450 durch Gutenberg. Dampfmaschine: 18. Jahrhundert. Der erste funktionsfähige programmierbare Computer (Zuse Z3): 1941.'
        },
        {
          type: 'pairs',
          q: 'Einfache Maschinen stecken überall. Wo begegnet dir welche?',
          left: ['Hebel', 'Schiefe Ebene', 'Rad und Achse', 'Flaschenzug'],
          right: ['Wippe auf dem Spielplatz', 'Rollstuhlrampe', 'Schubkarre', 'Baukran'],
          fact: 'Alle einfachen Maschinen tauschen Kraft gegen Weg: Man braucht weniger Kraft, muss dafür aber einen längeren Weg zurücklegen. Die Arbeit bleibt gleich – die goldene Regel der Mechanik.'
        }
      ]
    },
    {
      id: 'T1a', sector: 'technik', ring: 1, slot: 'a', requires: ['T0a'], icon: '⚡', title: 'Energie wandeln',
      blurb: 'Energie geht nie verloren – sie wandelt sich nur um.',
      tasks: [
        {
          type: 'pairs',
          q: 'Welches Gerät wandelt welche Energie um? Tippe erst links, dann rechts.',
          left: ['Solarzelle', 'Windrad', 'Wasserkocher', 'LED-Lampe'],
          right: ['Licht → Strom', 'Bewegung → Strom', 'Strom → Wärme', 'Strom → Licht'],
          fact: 'Energie geht nie verloren, sie wird nur umgewandelt. Das ist der Energieerhaltungssatz. In variado-Workshops zu erneuerbaren Energien baut und misst ihr solche Wandler selbst.'
        },
        {
          type: 'quiz',
          q: 'Ein Pizza-Ofen hat Klappen zur Luftregulierung. Was passiert, wenn du mehr Luft hineinlässt?',
          options: ['Das Feuer bekommt mehr Sauerstoff und brennt heißer', 'Das Feuer wird kleiner, weil es abkühlt', 'Es passiert gar nichts', 'Der Ofen wird sofort kalt'],
          answer: 0,
          fact: 'Mehr Sauerstoff bedeutet eine intensivere Verbrennung und mehr Wärme. Technik heißt, solche Zusammenhänge zu verstehen und gezielt zu steuern – vom Pizza-Ofen bis zum Kraftwerk.'
        }
      ]
    },
    {
      id: 'T1b', sector: 'technik', ring: 1, slot: 'b', requires: ['T0b'], icon: '🌬️', title: 'Erneuerbar?',
      blurb: 'Welche Energie steht uns dauerhaft zur Verfügung?',
      tasks: [
        {
          type: 'sort',
          q: 'Sortiere die Energiequellen.',
          cats: ['Erneuerbar', 'Fossil'],
          items: [['Sonnenlicht', 0], ['Wind', 0], ['Erdgas', 1], ['Wasserkraft', 0], ['Braunkohle', 1], ['Erdwärme', 0], ['Erdöl', 1]],
          fact: 'Kohle, Öl und Gas sind über Jahrmillionen entstanden. Wir verbrauchen sie viel schneller, als sie sich bilden, und setzen dabei CO₂ frei. Sonne, Wind, Wasser und Erdwärme stehen dauerhaft zur Verfügung.'
        },
        {
          type: 'multi',
          q: 'Was spart im Alltag Energie? Wähle drei.',
          options: ['Beim Kochen den Deckel auf den Topf legen', 'Den Kühlschrank direkt neben die Heizung stellen', 'Stoßlüften statt Fenster dauerhaft kippen', 'Den Wasserkocher immer randvoll machen', 'Geräte ganz ausschalten statt Stand-by', 'Licht in leeren Räumen brennen lassen'],
          answers: [0, 2, 4],
          fact: 'Die günstigste und sauberste Energie ist die, die wir gar nicht erst brauchen. Mit Deckel braucht Kochen deutlich weniger Energie, und Stoßlüften tauscht die Luft aus, ohne die Wände auszukühlen.'
        }
      ]
    },
    {
      id: 'T2', sector: 'technik', ring: 2, requires: ['T1a', 'T1b'], icon: '🧭', title: 'Werkzeug, nicht Chef',
      blurb: 'Technik soll dem Menschen dienen – nicht ihn dominieren.',
      tasks: [
        {
          type: 'quiz',
          q: 'Eine Gruppe plant einen Tag im Wald und überlegt, Tablets mitzunehmen. Welche Frage passt zur variado-Haltung?',
          options: ['Unterstützt die Technik das Erleben – oder verdrängt sie es?', 'Welches Gerät ist das neueste?', 'Wie fotografieren wir den Wald am schnellsten ab?', 'Wie vermeiden wir Technik komplett?'],
          answer: 0,
          fact: 'Technik ist bei variado ein Werkzeug. Bei einer Naturwanderung können Beobachtungen digital festgehalten und danach gemeinsam ausgewertet werden. Das Erleben selbst findet draußen statt.'
        },
        {
          type: 'order',
          q: 'Ein Algorithmus ist eine Schritt-für-Schritt-Anleitung. Sortiere den Algorithmus „Tee kochen“.',
          items: ['Wasser in den Wasserkocher füllen', 'Wasserkocher einschalten und warten, bis das Wasser kocht', 'Heißes Wasser über den Teebeutel in die Tasse gießen', 'Tee ziehen lassen und den Beutel herausnehmen'],
          fact: 'Genau so arbeitet jedes Programm: klare Schritte in der richtigen Reihenfolge. Wer Algorithmen versteht, nutzt Technik nicht nur, sondern gestaltet sie mit.'
        }
      ]
    },
    {
      id: 'T3', sector: 'technik', ring: 3, requires: ['T2'], icon: '♻️', title: 'Ressourcen',
      blurb: 'Welche Ressourcen verbraucht Technik wirklich?',
      tasks: [
        {
          type: 'quiz',
          q: 'Wann entsteht bei einem Smartphone der größte Teil der CO₂-Emissionen seines Lebens?',
          options: ['Bei der Herstellung – Rohstoffabbau und Produktion', 'Beim täglichen Aufladen', 'Beim Verschicken von Nachrichten', 'Bei der Entsorgung'],
          answer: 0,
          fact: 'Meist stammt der allergrößte Teil der Emissionen aus der Herstellung. Deshalb ist das nachhaltigste Handy oft das, das man schon hat: länger nutzen, reparieren, weitergeben.'
        },
        {
          type: 'multi',
          q: 'Was verlängert das Leben deines Handys? Wähle drei.',
          options: ['Reparieren lassen statt neu kaufen', 'Jedes Jahr das neueste Modell kaufen', 'Schutzhülle und Displayschutz verwenden', 'Das Handy im heißen Auto liegen lassen', 'Den Akku möglichst zwischen etwa 20 und 80 % laden', 'Alte Geräte in den Hausmüll werfen'],
          answers: [0, 2, 4],
          fact: 'In jedem Smartphone stecken Dutzende Rohstoffe, darunter seltene Metalle. Alte Geräte gehören zur Sammelstelle, damit diese Rohstoffe wiederverwertet werden können.'
        }
      ]
    },
    {
      id: 'T4', sector: 'technik', ring: 4, requires: ['T3'], icon: '💡', title: 'Wunderwelt Licht',
      blurb: 'Ein Stromkreis, eine Lampe – und ein Aha-Moment.',
      tasks: [
        {
          type: 'multi', visual: 'bulb',
          q: 'Die Lampe soll leuchten. Welche Materialien schließen den Stromkreis? Wähle alle aus, die Strom leiten.',
          options: ['Kupferdraht', 'Holzstäbchen', 'Alufolie', 'Gummiband', 'Büroklammer aus Metall', 'Glasstab'],
          answers: [0, 2, 4],
          fact: 'Metalle haben frei bewegliche Elektronen und leiten deshalb Strom. Holz, Gummi und Glas sind Isolatoren. Übrigens: Eine LED macht aus Strom deutlich mehr Licht als eine alte Glühbirne, die vor allem Wärme erzeugt.'
        },
        {
          type: 'quiz',
          q: 'Welche Haltung zu Technik vertritt variado?',
          options: ['Technik als Werkzeug nutzen, das Probleme nachhaltig löst – verstehen, hinterfragen, mitgestalten', 'Was technisch möglich ist, sollte auch gebaut werden', 'Technik ist grundsätzlich schlecht für Mensch und Natur', 'Technik muss man nur benutzen, nicht verstehen'],
          answer: 0,
          fact: 'Wie effizient ist eine Technik? Welche Ressourcen braucht sie? Ist sie wirklich lebensdienlich? Wer das fragt, macht Technik zum Mittel für eine bessere Zukunft.'
        }
      ]
    },

    /* ================= NATUR ================= */
    {
      id: 'N0a', sector: 'natur', ring: 0, slot: 'a', requires: [], icon: '🔥', title: 'Im Zeichen| des Feuers',
      blurb: 'Das Element, das uns Menschen dorthin gebracht hat, wo wir heute sind.',
      tasks: [
        {
          type: 'multi',
          q: 'Was braucht ein Feuer, um zu brennen? Wähle die drei Zutaten.',
          options: ['Brennstoff, z. B. Holz', 'Wasser', 'Sauerstoff', 'Sand', 'Wärme bis zur Zündtemperatur', 'Dunkelheit'],
          answers: [0, 2, 4],
          fact: 'Das ist das Verbrennungsdreieck. Fehlt eine Seite, erlischt das Feuer: Sand erstickt es, weil kein Sauerstoff mehr herankommt, Wasser kühlt es unter die Zündtemperatur.'
        },
        {
          type: 'order',
          q: 'Du entfachst ein Lagerfeuer. Bringe die Schritte in die richtige Reihenfolge.',
          items: ['Trockenen Zunder sammeln, z. B. Birkenrinde oder trockenes Gras', 'Dünnes Anzündholz locker über den Zunder schichten', 'Funken oder Flamme in den Zunder bringen', 'Behutsam Luft zufächeln', 'Nach und nach dickere Scheite nachlegen'],
          fact: 'Feuer wächst von klein nach groß. Birkenrinde ist ein besonderer Zunder: Sie enthält Öle und brennt deshalb sogar, wenn sie etwas feucht ist.'
        }
      ]
    },
    {
      id: 'N0b', sector: 'natur', ring: 0, slot: 'b', requires: [], icon: '💧', title: 'Wasserkreislauf',
      blurb: 'In der Natur geht nichts verloren.',
      tasks: [
        {
          type: 'order',
          q: 'Bringe den Wasserkreislauf in die richtige Reihenfolge.',
          items: ['Die Sonne erwärmt Meere und Seen', 'Wasser verdunstet und steigt auf', 'In der kühlen Höhe bilden sich Wolken', 'Es regnet oder schneit', 'Bäche und Flüsse tragen das Wasser zurück'],
          fact: 'Angetrieben wird der Kreislauf von der Sonne. Das Wasser, das du heute trinkst, war schon unzählige Male in diesem Kreislauf unterwegs.'
        },
        {
          type: 'quiz',
          q: 'Wie entsteht ein Regenbogen?',
          options: ['Sonnenlicht wird in Regentropfen gebrochen und in seine Farben zerlegt', 'Wolken färben das Sonnenlicht ein', 'Der Himmel spiegelt die Farben der Landschaft', 'Regentropfen leuchten von selbst'],
          answer: 0,
          fact: 'Weißes Licht besteht aus allen Farben. In jedem Tropfen wird es gebrochen, gespiegelt und aufgefächert. Einen Regenbogen siehst du nur mit der Sonne im Rücken.'
        }
      ]
    },
    {
      id: 'N1a', sector: 'natur', ring: 1, slot: 'a', requires: ['N0a'], icon: '⛰️', title: 'Land der| offenen Fernen',
      blurb: 'Die Rhön – Heimat der Thüringer Hütte.',
      tasks: [
        {
          type: 'quiz',
          q: 'Die Rhön, die Heimat der Thüringer Hütte, ist seit 1991 von der UNESCO ausgezeichnet. Als was?',
          options: ['Biosphärenreservat', 'Nationalpark', 'Weltkulturerbe', 'Geopark'],
          answer: 0,
          fact: 'In einem Biosphärenreservat sollen Mensch und Natur gemeinsam wirtschaften, statt dass Natur nur abgesperrt wird. Die Rhön ist geprägt von offener Kulturlandschaft und natürlichen Wäldern.'
        },
        {
          type: 'estimate',
          q: 'Schätze: Wie hoch ist die Wasserkuppe, der höchste Berg der Rhön?',
          min: 500, max: 1500, step: 10, start: 700, answer: 950, tolerance: 40, unit: 'Meter',
          fact: 'Die Wasserkuppe ist 950 Meter hoch. Sie gilt als Wiege des Segelflugs: Schon in den 1920er-Jahren starteten hier Segelflieger, weil der Wind über die kahlen Kuppen gleichmäßig aufsteigt.'
        }
      ]
    },
    {
      id: 'N1b', sector: 'natur', ring: 1, slot: 'b', requires: ['N0b'], icon: '🌿', title: 'Kräuter|wanderung',
      blurb: 'Pflanzen haben raffinierte Strategien.',
      tasks: [
        {
          type: 'pairs',
          q: 'Welche Eigenschaft gehört zu welcher Pflanze?',
          left: ['Brennnessel', 'Löwenzahn', 'Klette', 'Spitzwegerich'],
          right: ['Brennhaare, die bei Berührung abbrechen', 'Samen fliegen an Schirmchen mit dem Wind', 'Früchte haken sich im Fell von Tieren fest', 'Blätter werden traditionell auf Insektenstiche gerieben'],
          fact: 'Pflanzen haben raffinierte Strategien, und manche davon hat sich der Mensch abgeschaut. Die Klette begegnet dir bald wieder.'
        },
        {
          type: 'sort',
          q: 'Laubbaum oder Nadelbaum? Sortiere die heimischen Bäume.',
          cats: ['Laubbaum', 'Nadelbaum'],
          items: [['Eiche', 0], ['Fichte', 1], ['Buche', 0], ['Kiefer', 1], ['Ahorn', 0], ['Lärche', 1], ['Tanne', 1]],
          fact: 'Die Lärche ist eine Besonderheit: Sie ist der einzige heimische Nadelbaum, der im Herbst seine Nadeln abwirft und im Frühjahr frisch hellgrün austreibt.'
        }
      ]
    },
    {
      id: 'N2', sector: 'natur', ring: 2, requires: ['N1a', 'N1b'], icon: '🌳', title: 'Netzwerk Wald',
      blurb: 'Im Wald hängt alles mit allem zusammen.',
      tasks: [
        {
          type: 'quiz',
          q: 'Warum ist Artenvielfalt so wichtig für ein Ökosystem?',
          options: ['Viele Arten machen es stabiler: Fällt eine aus, können andere ihre Rolle teilweise übernehmen', 'Mehr Arten bedeuten mehr Konkurrenz, das Ökosystem wird schwächer', 'Artenvielfalt ist vor allem schön anzusehen', 'Für ein Ökosystem zählen nur die großen Tiere'],
          answer: 0,
          fact: 'Im Wald arbeiten Pilze, Bodenlebewesen, Pflanzen und Tiere eng zusammen. In diesem Netzwerk zählt jedes Teil. Genauso denkt variado über Mensch, Natur und Technik.'
        },
        {
          type: 'quiz',
          q: 'Bäume tauschen unter der Erde Wasser und Nährstoffe aus. Wie machen sie das?',
          options: ['Über feine Pilzgeflechte, die mit ihren Wurzeln verbunden sind', 'Über unterirdische Bäche', 'Über Regenwürmer, die Nährstoffe tragen', 'Gar nicht – jeder Baum lebt für sich'],
          answer: 0,
          fact: 'Diese Partnerschaft heißt Mykorrhiza: Der Pilz liefert dem Baum Wasser und Mineralien, der Baum gibt dem Pilz Zucker. Manche nennen das Netz auch „Wood Wide Web“.'
        }
      ]
    },
    {
      id: 'N3', sector: 'natur', ring: 3, requires: ['N2'], icon: '☀️', title: 'Die Kraft| der Sonne',
      blurb: 'Ohne die Sonne gäbe es kein Leben auf der Erde.',
      tasks: [
        {
          type: 'estimate',
          q: 'Schätze: Wie viele Minuten braucht das Licht der Sonne bis zur Erde?',
          min: 1, max: 30, step: 1, start: 15, answer: 8, tolerance: 1, unit: 'Minuten',
          fact: 'Es sind rund 8 Minuten und 20 Sekunden. Das Licht legt etwa 150 Millionen Kilometer mit knapp 300.000 Kilometern pro Sekunde zurück. Wenn du die Sonne siehst, siehst du sie also so, wie sie vor gut 8 Minuten war.'
        },
        {
          type: 'multi',
          q: 'Was braucht eine Pflanze für die Photosynthese? Wähle drei.',
          options: ['Licht', 'Sauerstoff', 'Wasser', 'Zucker', 'Kohlendioxid aus der Luft', 'Dunkelheit'],
          answers: [0, 2, 4],
          fact: 'Aus Licht, Wasser und Kohlendioxid baut die Pflanze Zucker – und gibt dabei Sauerstoff ab. Fast alles Leben auf der Erde hängt an diesem einen Vorgang.'
        }
      ]
    },
    {
      id: 'N4', sector: 'natur', ring: 4, requires: ['N3'], icon: '🐝', title: 'Bewahren',
      blurb: 'Die Welt für kommende Generationen erhalten.',
      tasks: [
        {
          type: 'multi',
          q: 'Was hilft Wildbienen und anderen Insekten im Garten oder auf dem Schulgelände? Wähle drei.',
          options: ['Heimische Wildblumen säen', 'Einen Schottergarten anlegen', 'Ecken mit Totholz und Laub liegen lassen', 'Laub mit dem Laubbläser entfernen', 'Seltener mähen', 'Pflanzen mit gefüllten Zierblüten setzen'],
          answers: [0, 2, 4],
          fact: 'Gefüllte Zierblüten sehen prächtig aus, bieten aber kaum Nektar und Pollen. Kleine Schritte bewirken viel: Schon eine Wildblumenecke wird zur Tankstelle für Insekten.'
        },
        {
          type: 'quiz',
          q: 'Was beschreibt Nachhaltigkeit am besten?',
          options: ['So leben und wirtschaften, dass auch künftige Generationen gut leben können', 'Möglichst wenig Geld ausgeben', 'Nur noch Bio-Produkte kaufen', 'Die Natur vollständig vom Menschen trennen'],
          answer: 0,
          fact: 'Nachhaltigkeit ist das „N“ in NEO-BIG. Sie beginnt mit kleinen Schritten – und mit Aufklärung, die nicht anklagt, sondern sensibilisiert.'
        }
      ]
    },

    /* ================= MENSCH ================= */
    {
      id: 'M0a', sector: 'mensch', ring: 0, slot: 'a', requires: [], icon: '🪶', title: 'Ankommen',
      blurb: 'Bevor es losgeht: einen Moment innehalten.',
      tasks: [
        {
          type: 'breathe', breaths: 3,
          q: 'Bevor es weitergeht, nimm dir einen Moment. Folge dem Kreis: drei ruhige Atemzüge.',
          fact: 'Kurzes Innehalten wirkt auch mitten im Alltag. Im Glücks-Ratgeber heißt es: Richte die Aufmerksamkeit kurz nach innen und frage dich, welche Haltung du gerade einnehmen möchtest.'
        },
        {
          type: 'reflect',
          q: 'Worüber hast du zuletzt so richtig gestaunt?',
          options: ['Über etwas in der Natur', 'Über eine technische Erfindung', 'Über einen Menschen', 'Über mich selbst'],
          fact: 'Es gibt keine falsche Antwort! Bei variado gilt: Alle Fragen sind gute Fragen. Staunen ist der erste Funke, mit dem Lernen beginnt.'
        }
      ]
    },
    {
      id: 'M0b', sector: 'mensch', ring: 0, slot: 'b', requires: [], icon: '🤝', title: 'Teamgeist',
      blurb: 'Das große Puzzle ist nur gemeinsam zu lösen.',
      tasks: [
        {
          type: 'multi',
          q: 'Was macht ein gutes Team aus? Wähle drei.',
          options: ['Klare Kommunikation', 'Jede*r arbeitet für sich', 'Gegenseitiges Vertrauen', 'Wer am lautesten ist, entscheidet', 'Gelungene Kooperation', 'Fehler werden versteckt'],
          answers: [0, 2, 4],
          fact: 'Kommunikation, Vertrauen und Kooperation stellt variado in Teamtagen auf die Probe, zum Beispiel beim Bau einer Naturmurmelbahn. Danach werden die Erfahrungen gemeinsam reflektiert.'
        },
        {
          type: 'quiz',
          q: 'Eure Gruppe soll gemeinsam über einen wackeligen Balken balancieren. Was hilft am meisten?',
          options: ['Absprechen, sich gegenseitig sichern und jede Stimme hören', 'Die Schnellste geht vor, der Rest schaut zu', 'Jede Person versucht es allein', 'Möglichst wenig reden, damit sich alle konzentrieren'],
          answer: 0,
          fact: 'Gemeinschaft heißt: Jede Stimme zählt. Genau deshalb ist variado eine Genossenschaft – Abstimmung, Aushandlung und Konsens machen eine Gruppe stark.'
        }
      ]
    },
    {
      id: 'M1a', sector: 'mensch', ring: 1, slot: 'a', requires: ['M0a'], icon: '✨', title: 'Goldene Regeln',
      blurb: 'Innere Orientierung für ein gelingendes Leben.',
      tasks: [
        {
          type: 'pairs',
          q: 'Welcher Satz gehört zu welcher Goldenen Regel?',
          left: ['Dankbarkeit', 'Wachstum & Lernen', 'Akzeptanz', 'Integrität'],
          right: ['Bringt Frieden in die Vergangenheit, Sinn in die Gegenwart und Hoffnung für morgen', 'Werde jeden Tag mindestens 1 % besser, Fehler helfen dir dabei', 'Akzeptiere, was du nicht ändern kannst, und ändere, was du ändern kannst', 'Bleib dir selbst treu und vergleiche dich nur mit dir selbst'],
          fact: 'Die Goldenen Regeln stammen aus dem Glücks-Ratgeber für den Schulalltag, den variado gemeinsam mit Annette und Tabea Lüders herausgebracht hat.'
        },
        {
          type: 'pairs',
          q: 'Noch mehr Goldene Regeln: Welcher Gedanke gehört wozu?',
          left: ['Ziele & Vision', 'Mindset & Disziplin', 'Glaube & Vertrauen', 'Selbstverantwortung'],
          right: ['Ohne Ziel kein Weg – Ziele geben Richtung, Vision gibt Sinn', 'Deine Gedanken steuern dein Handeln und erschaffen dein Leben', 'Glaube an dich selbst und vertraue dem Leben', 'Niemand anderes denkt, fühlt oder handelt für dich'],
          fact: 'Die Goldenen Regeln beruhen auf einem Vergleich vieler Perspektiven – von Naturvölkern über Philosophie und Psychologie bis zum Leistungssport. Im Kern einigen sie sich auf dieselben Grundprinzipien.'
        }
      ]
    },
    {
      id: 'M1b', sector: 'mensch', ring: 1, slot: 'b', requires: ['M0b'], icon: '🧠', title: 'Unruhe| verstehen',
      blurb: 'Hinter Verhalten steckt oft ein Signal.',
      tasks: [
        {
          type: 'quiz',
          q: 'Eine Gruppe wird plötzlich unruhig. Was steckt laut Systemblick oft dahinter?',
          options: ['Ein Signal aus dem System: Es fehlen z. B. Pausen, Bewegung oder Beziehung', 'Die Gruppe will absichtlich stören', 'Es gibt zu wenige Regeln', 'Unruhe hat keine Ursache'],
          answer: 0,
          fact: 'Unruhe ist ein Signal und kein Angriff. Nicht mehr Kontrolle bringt die Lösung, sondern mehr Balance im System aus Mensch, Natur und Technik.'
        },
        {
          type: 'order',
          q: 'Coyote-Teaching dreht die übliche Reihenfolge des Lernens um. Bringe die Schritte in die richtige Reihenfolge.',
          items: ['Überblick schaffen', 'Zusammenhänge sichtbar machen', 'Fragen zulassen', 'Schritt für Schritt ins Detail gehen'],
          fact: 'Erst sehen wir die ganze Blume, dann Blätter, Farben und Strukturen, schließlich Zellen und Härchen – und am Ende wieder die Blume, nur mit viel mehr Verständnis. Wissen entsteht nicht durch Antworten, sondern durch Neugier.'
        }
      ]
    },
    {
      id: 'M2', sector: 'mensch', ring: 2, requires: ['M1a', 'M1b'], icon: '🛡️', title: 'Wir sind| NEO-BIG',
      blurb: 'Die Werte, die variado zusammenhalten.',
      tasks: [
        {
          type: 'order',
          q: 'Die Werte von variado bilden das Wort NEO-BIG. Bringe sie in die richtige Reihenfolge.',
          items: ['Nachhaltigkeit', 'Eigenverantwortung', 'Offenheit & Diversität', 'Bildung & Entwicklung', 'Innovation', 'Gemeinschaft'],
          fact: 'Bei variado ergänzen sich die Puzzleteile. Jeder Beitrag ist individuell und wichtig für das große Ganze: Mit deinen Stärken, Interessen und deinem Engagement bist du das fehlende Teil.'
        },
        {
          type: 'quiz',
          q: 'Was bedeutet Partizipation in einer Gruppe?',
          options: ['Echte Spielräume öffnen: In einem klaren Rahmen entscheiden alle mit', 'Alle dürfen alles entscheiden, es gibt keinen Rahmen', 'Die Leitung entscheidet und erklärt es danach', 'Nur die Erfahrensten dürfen mitreden'],
          answer: 0,
          fact: 'Wer mitentscheidet, trägt mit. Beteiligung stärkt Selbstwirksamkeit und Motivation – und wo mitentschieden wird, wird seltener blockiert.'
        }
      ]
    },
    {
      id: 'M3', sector: 'mensch', ring: 3, requires: ['M2'], icon: '🤹', title: 'Kreativität',
      blurb: 'Zirkus, Theater, Musik – Ausdruck öffnet neue Perspektiven.',
      tasks: [
        {
          type: 'quiz',
          q: 'Beim Jonglieren fällt der Ball zum zehnten Mal herunter. Welche Haltung bringt dich weiter?',
          options: ['Jeder Fehlversuch ist Training: dranbleiben und kleine Fortschritte feiern', 'Aufhören – Jonglieren ist einfach nicht mein Ding', 'Nur noch mit einem Ball üben, damit nichts mehr fällt', 'Heimlich üben, damit niemand die Fehler sieht'],
          answer: 0,
          fact: 'In der Zirkuspädagogik gehört Fallenlassen dazu. Wer Fehler als Teil des Lernens sieht, bleibt kreativ und wächst über sich hinaus.'
        },
        {
          type: 'quiz',
          q: 'Was stärkt die Resilienz – also die innere Widerstandskraft – am meisten?',
          options: ['Gute Beziehungen, Vertrauen in die eigenen Fähigkeiten und Herausforderungen als lösbar sehen', 'Schwierigen Situationen konsequent aus dem Weg gehen', 'Gefühle unterdrücken und einfach weitermachen', 'Immer alles allein schaffen wollen'],
          answer: 0,
          fact: 'Resilienz wächst durch tragfähige Beziehungen und Erfolgserlebnisse. Darum heißt es bei variado auch: Beziehung vor Regel.'
        }
      ]
    },
    {
      id: 'M4', sector: 'mensch', ring: 4, requires: ['M3'], icon: '❤️', title: 'Dankbarkeit',
      blurb: 'Ein Satz am Tag kann den Blick verändern.',
      tasks: [
        {
          type: 'reflect', minLength: 10,
          q: 'Wofür bist du heute dankbar? Schreib einen kurzen Satz.',
          placeholder: 'Heute bin ich dankbar für …',
          note: 'Deine Antwort bleibt bei dir und wird nicht gespeichert.',
          fact: 'Wer dankbar ist, lebt glücklicher und geht besser mit anderen um. Schon ein Satz am Tag kann den Blick verändern.'
        },
        {
          type: 'reflect', minLength: 10,
          q: 'Was hast du zuletzt selbst bewirkt, worauf du stolz bist? Schreib einen kurzen Satz.',
          placeholder: 'Ich habe …',
          note: 'Deine Antwort bleibt bei dir und wird nicht gespeichert.',
          fact: 'Das nennt man Selbstwirksamkeit: die Erfahrung, durch eigenes Handeln etwas zu bewirken. Genau dafür gibt es die ErLebenswerkstatt.'
        }
      ]
    }
  ],

  // Brücken an den Grenzen zweier Bereiche. Ring 1: Sonderaufgaben, Ring 3: Brücken zur Mitte.
  bridges: [
    {
      id: 'MT1', sectors: ['mensch', 'technik'], ring: 1, requires: ['M1a', 'T1b'], icon: '🖥️', title: 'Bildschirm|pause',
      blurb: 'Mensch ∩ Technik: Wer steuert wen?',
      tasks: [
        {
          type: 'quiz',
          q: 'Du arbeitest lange am Bildschirm. Welche einfache Regel entlastet deine Augen?',
          options: ['20-20-20: Alle 20 Minuten 20 Sekunden lang in etwa 6 Meter Entfernung schauen', 'Den Bildschirm heller stellen, dann geht es schneller', 'Durchhalten, Pausen kosten nur Zeit', 'Näher an den Bildschirm rücken'],
          answer: 0,
          fact: 'Technik soll dem Menschen dienen, und dazu gehören Pausen. Kinder wie Erwachsene brauchen Rhythmus, Pausen und Übergänge.'
        },
        {
          type: 'quiz',
          q: 'Was hilft am meisten für einen gesunden Umgang mit dem Smartphone?',
          options: ['Bewusste Pausen, weniger Benachrichtigungen und echte Zeit mit anderen', 'Immer erreichbar sein, um nichts zu verpassen', 'Das Handy nur nachts benutzen', 'Möglichst viele Apps parallel nutzen'],
          answer: 0,
          fact: 'Hat der Mensch noch Macht über die Technik – oder diktiert sie sein Leben? Bewusste Regeln geben dir die Kontrolle zurück.'
        }
      ]
    },
    {
      id: 'MN1', sectors: ['mensch', 'natur'], ring: 1, requires: ['M1b', 'N1a'], icon: '👁️', title: 'Sinnes|spaziergang',
      blurb: 'Mensch ∩ Natur: Natur mit allen Sinnen.',
      tasks: [
        {
          type: 'pairs',
          q: 'Mit welchem Sinn nimmst du das draußen wahr?',
          left: ['Vogelgezwitscher', 'Weiches Moos', 'Duft von Harz', 'Abendrot'],
          right: ['Hören', 'Fühlen', 'Riechen', 'Sehen'],
          fact: 'Natur mit allen Sinnen zu erleben, ist ein Kern von variado. Und Natur reguliert: Sie beruhigt Körper und Nervensystem, ganz ohne Erklärung.'
        },
        {
          type: 'quiz',
          q: 'Was zeigen Studien zum achtsamen Aufenthalt im Wald („Waldbaden“)?',
          options: ['Stresswerte wie Puls, Blutdruck und Cortisol können sinken', 'Es gibt keine messbaren Effekte', 'Man wird davon müde und unkonzentriert', 'Es wirkt nur bei Sonnenschein'],
          answer: 0,
          fact: 'Natur wirkt sogar, wenn sie nur kurz erfahrbar ist: Ein Blick ins Grüne oder eine Pflanze im Raum reichen, um innere Bilder zu aktivieren und Stress zu senken.'
        }
      ]
    },
    {
      id: 'TN1', sectors: ['technik', 'natur'], ring: 1, requires: ['N1b', 'T1a'], icon: '🦎', title: 'Bionik',
      blurb: 'Natur ∩ Technik: von der Natur lernen.',
      tasks: [
        {
          type: 'pairs',
          q: 'Welches Vorbild aus der Natur steckt hinter welcher Erfindung?',
          left: ['Klette', 'Lotusblatt', 'Eisvogel', 'Haifischhaut'],
          right: ['Klettverschluss', 'Selbstreinigende Oberflächen', 'Nase des japanischen Schnellzugs Shinkansen', 'Rillenfolien gegen Strömungswiderstand'],
          fact: 'Der Schweizer Ingenieur Georges de Mestral erfand den Klettverschluss, nachdem er Kletten aus dem Fell seines Hundes gezupft hatte. Die Natur hatte Millionen Jahre Zeit zum Ausprobieren.'
        },
        {
          type: 'pairs',
          q: 'Noch mehr Bionik: Welches Vorbild passt zu welcher Idee?',
          left: ['Löwenzahn-Schirmchen', 'Bienenwabe', 'Fledermaus', 'Ahornsamen'],
          right: ['Fallschirm', 'Leichtbau-Platten mit Sechseck-Kammern', 'Orientierung per Ultraschall-Echo', 'Rotor, der sich drehend langsam zu Boden schraubt'],
          fact: 'Sechsecke sind eine der stabilsten und sparsamsten Formen: Bienen bauen damit mit wenig Wachs viel Stauraum. Leichtbau in Flugzeugen nutzt dasselbe Prinzip.'
        }
      ]
    },
    {
      id: 'MT2', sectors: ['mensch', 'technik'], ring: 3, requires: ['M3', 'T3'], icon: '🚀', title: 'Vom Problem| zur Erfindung',
      blurb: 'Technik & Mensch: Innovation, die Menschen dient.',
      tasks: [
        {
          type: 'order',
          q: 'Bringe die Schritte einer Erfindung in eine sinnvolle Reihenfolge.',
          items: ['Ein Problem im Alltag entdecken', 'Menschen fragen, was sie wirklich brauchen', 'Ideen sammeln, auch verrückte', 'Einen einfachen Prototyp bauen', 'Testen, Rückmeldung holen, verbessern'],
          fact: 'Kreativität und Persönlichkeit treffen auf Produktentwicklung. So entsteht Technik, die Menschen wirklich dient, statt sie zu dominieren.'
        },
        {
          type: 'quiz',
          q: 'Ein Team soll einen kleinen Roboter bauen. Wann entsteht meist die beste Lösung?',
          options: ['Wenn unterschiedliche Stärken, Ideen und Perspektiven zusammenkommen', 'Wenn die technisch stärkste Person allein entscheidet', 'Wenn alle genau gleich denken', 'Wenn man die Aufgabe so schnell wie möglich erledigt'],
          answer: 0,
          fact: 'Offenheit und Diversität – das „O“ in NEO-BIG. Jede Person bringt besondere Ressourcen mit. Du bist das fehlende Puzzleteil!'
        }
      ]
    },
    {
      id: 'MN2', sectors: ['mensch', 'natur'], ring: 3, requires: ['M3', 'N3'], icon: '🔗', title: 'Unser Erbe',
      blurb: 'Mensch & Natur: Wir tragen die Natur in uns.',
      tasks: [
        {
          type: 'quiz',
          q: 'Warum spricht variado bei der Verbindung zur Natur von einem Erbe?',
          options: ['Weil wir der Natur entstammen und sie immer als Anteil in uns tragen', 'Weil Wälder in Familien vererbt werden', 'Weil Natur etwas von früher ist, das wir heute nicht mehr brauchen', 'Weil nur ältere Menschen Natur schätzen'],
          answer: 0,
          fact: 'Wenn Menschen zurück zur Natur finden, wird dieses Bewusstsein wieder wach. Hier beginnt die Brücke zwischen Mensch und Natur.'
        },
        {
          type: 'quiz',
          q: 'Warum bleibt Gelerntes bei Erlebnissen in der Natur oft besonders lange hängen?',
          options: ['Weil Kopf, Herz und Hand gleichzeitig angesprochen werden', 'Weil man draußen weniger lernen muss', 'Weil frische Luft das Gedächtnis ersetzt', 'Weil es keine Regeln gibt'],
          answer: 0,
          fact: 'Ganzheitliches Lernen verbindet Körper, Gefühl und Verstand. Genau so versteht sich variado als ErLebenswerkstatt – handlungsorientiert und erfahrungsbasiert.'
        }
      ]
    },
    {
      id: 'TN2', sectors: ['technik', 'natur'], ring: 3, requires: ['N3', 'T3'], icon: '🏡', title: 'Das Erdhaus',
      blurb: 'Natur & Technik gemeinsam gedacht.',
      tasks: [
        {
          type: 'quiz',
          q: 'An der Thüringer Hütte steht ein Erdhaus, das teilweise mit Erde bedeckt ist. Welchen Vorteil hat das?',
          options: ['Die Erde dämmt: Im Winter bleibt es wärmer, im Sommer angenehm kühl', 'Das Haus wird dadurch leichter', 'Es ist innen immer vollkommen dunkel', 'Die Erde auf dem Dach erzeugt Strom'],
          answer: 0,
          fact: 'Die Temperatur im Boden schwankt viel weniger als die der Luft. Das Erdhaus nutzt die Erde als natürliche Klimaanlage – Natur und Technik gemeinsam gedacht.'
        },
        {
          type: 'quiz',
          q: 'Was macht eine Solarzelle?',
          options: ['Sie wandelt Licht direkt in elektrischen Strom um', 'Sie speichert Wärme wie ein Ofen', 'Sie erzeugt Strom aus Wind', 'Sie funktioniert nur bei direkter Mittagssonne'],
          answer: 0,
          fact: 'Photovoltaik nutzt den photoelektrischen Effekt: Licht setzt in Halbleitern Ladungen frei. Auch bei bewölktem Himmel liefert eine Solarzelle noch Strom, nur weniger.'
        }
      ]
    }
  ],

  final: {
    title: 'Die Synergie',
    q: 'Setze das variado-Puzzle zusammen: Tippe ein Puzzleteil an und dann den Platz, an den es gehört.',
    q2: 'Und zum Schluss: Was bedeutet Synergie bei variado?',
    options: [
      'Mehr als die Summe der Teile: Mensch, Natur und Technik im Gleichgewicht erschaffen etwas Neues',
      'Die Technik übernimmt, was Mensch und Natur nicht schaffen',
      'Jeder Bereich bleibt für sich, dann stört keiner den anderen',
      'Die Natur geht vor, Technik bleibt draußen'
    ],
    answer: 0,
    fact: 'Ein Gleichgewicht, das Menschen nützt, die Umwelt bewahrt und Technik bewusst einsetzt. Mensch, Natur, Technik – kein Gegensatz, sondern Teile eines perfekten Puzzles.'
  }
};
