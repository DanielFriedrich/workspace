/*
 * Spark to Synergy – Spielinhalte
 * ------------------------------------------------------------
 * Alle Texte, Aufgaben und Zahlenwerte des Spiels stehen hier.
 * Wer Fragen austauschen oder ergänzen möchte, muss nur diese Datei ändern.
 *
 * Aufgabentypen:
 *   choice  – Multiple Choice, genau eine Antwort ist richtig (correct = Index)
 *   order   – Elemente in die richtige Reihenfolge bringen (items = richtige Reihenfolge)
 *   reflect – Reflexionsfrage: es gibt kein Falsch, jede Antwort zählt
 *
 * Ringe: Index 0 = äußerster Ring (Start), Index 4 = innerster Ring vor der Synergie.
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

  sectors: {
    mensch:  { label: 'Mensch',  color: '#E2A46F', angle: 0,   motto: 'durch Kreativität, Gemeinschaft und Lernen wachsen' },
    technik: { label: 'Technik', color: '#4AA3D0', angle: 120, motto: 'Innovation als Mittel für eine nachhaltige Welt' },
    natur:   { label: 'Natur',   color: '#6FBF73', angle: 240, motto: 'die Welt mit Respekt, Wissen und Achtsamkeit betrachten' }
  },

  rings: [
    { name: 'Staunen',      text: 'Wahrnehmen, neugierig werden, Fragen stellen.' },
    { name: 'Verstehen',    text: 'Zusammenhänge erkennen – wie funktioniert das?' },
    { name: 'Erleben',      text: 'Mit Kopf, Herz und Hand ausprobieren.' },
    { name: 'Gestalten',    text: 'Selbst wirksam werden und Neues erschaffen.' },
    { name: 'Verantworten', text: 'Bewusst handeln – für Mensch, Natur und Zukunft.' }
  ],

  // Wirtschaft (angelehnt an Cell to Singularity: Knoten sind "Generatoren")
  economy: {
    coreCost:     [10, 90, 600, 3500, 15000],   // Funken-Kosten je Ring
    coreOutput:   [0.5, 2, 6, 16, 40],       // Funken pro Sekunde je Knoten
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
    /* ---------------- MENSCH ---------------- */
    {
      id: 'M1', sector: 'mensch', ring: 0, icon: '👀', title: 'Neugier',
      blurb: 'Jede Entdeckung beginnt mit einem Funken Neugier.',
      task: {
        type: 'reflect',
        q: 'Worüber hast du zuletzt so richtig gestaunt?',
        options: ['Über etwas in der Natur', 'Über eine technische Erfindung', 'Über einen Menschen', 'Über mich selbst'],
        explain: 'Es gibt keine falsche Antwort! Bei variado gilt: „Alle Fragen sind gute Fragen.“ Staunen ist der erste Schritt zum Lernen – der Funke, mit dem alles beginnt.'
      }
    },
    {
      id: 'M2', sector: 'mensch', ring: 1, icon: '🤝', title: 'Gemeinschaft',
      blurb: 'Das große Puzzle ist nur gemeinsam zu lösen.',
      task: {
        type: 'choice',
        q: 'Eure Gruppe soll gemeinsam über einen wackeligen Balken balancieren. Was hilft am meisten?',
        options: [
          'Die Schnellste geht vor, der Rest schaut zu',
          'Absprechen, sich gegenseitig sichern und jede Stimme hören',
          'Jede Person versucht es allein',
          'Möglichst wenig reden, damit sich alle konzentrieren'
        ],
        correct: 1,
        explain: 'Gemeinschaft heißt: jede Stimme zählt. Genau deshalb ist variado eine Genossenschaft – Abstimmung, Aushandlung und Konsens machen eine Gruppe stark.'
      }
    },
    {
      id: 'M3', sector: 'mensch', ring: 2, icon: '🤹', title: 'Kreativität',
      blurb: 'Zirkus, Theater, Musik – Ausdruck öffnet neue Perspektiven.',
      task: {
        type: 'choice',
        q: 'Beim Jonglieren fällt der Ball zum zehnten Mal herunter. Welche Haltung bringt dich weiter?',
        options: [
          'Aufhören – Jonglieren ist einfach nicht mein Ding',
          'Jeder Fehlversuch ist Training: dranbleiben und kleine Fortschritte feiern',
          'Nur noch mit einem Ball üben, damit nichts mehr fällt',
          'Heimlich üben, damit niemand die Fehler sieht'
        ],
        correct: 1,
        explain: 'In der Zirkuspädagogik gehört Fallenlassen dazu. Wer Fehler als Teil des Lernens sieht, bleibt kreativ und wächst über sich hinaus.'
      }
    },
    {
      id: 'M4', sector: 'mensch', ring: 3, icon: '🌱', title: 'Resilienz',
      blurb: 'Stark werden, ohne hart zu werden.',
      task: {
        type: 'choice',
        q: 'Was stärkt die Resilienz – also die innere Widerstandskraft – am meisten?',
        options: [
          'Schwierigen Situationen konsequent aus dem Weg gehen',
          'Gefühle unterdrücken und einfach weitermachen',
          'Gute Beziehungen, das Vertrauen in die eigenen Fähigkeiten und Herausforderungen als lösbar sehen',
          'Immer alles allein schaffen wollen'
        ],
        correct: 2,
        explain: 'Resilienz wächst durch tragfähige Beziehungen und Erfolgserlebnisse. Darum heißt es bei variado auch: „Beziehung vor Regel“.'
      }
    },
    {
      id: 'M5', sector: 'mensch', ring: 4, icon: '💪', title: 'Selbst|wirksamkeit',
      blurb: 'Erleben, dass das eigene Handeln etwas bewirkt.',
      task: {
        type: 'choice',
        q: 'Was bedeutet „Selbstwirksamkeit“?',
        options: [
          'Möglichst viel für sich selbst herausholen',
          'Die Überzeugung, durch eigenes Handeln etwas bewirken zu können',
          'Alles ohne Hilfe erledigen',
          'Sich selbst möglichst gut darstellen'
        ],
        correct: 1,
        explain: 'Die ErLebenswerkstatt will genau das: Menschen erleben lassen, wie es ist, selbst Veränderung zu bewirken. Eigenverantwortung ist das „E“ in NEO-BIG.'
      }
    },

    /* ---------------- NATUR ---------------- */
    {
      id: 'N1', sector: 'natur', ring: 0, icon: '🌈', title: 'Licht & Farben',
      blurb: 'Natürliche Phänomene zeigen, was die Welt zusammenhält.',
      task: {
        type: 'choice',
        q: 'Wie entsteht ein Regenbogen?',
        options: [
          'Wolken färben das Sonnenlicht ein',
          'Sonnenlicht wird in Regentropfen gebrochen und in seine Farben zerlegt',
          'Der Himmel spiegelt die Farben der Landschaft',
          'Regentropfen leuchten von selbst'
        ],
        correct: 1,
        explain: 'Weißes Licht besteht aus allen Farben. In jedem Tropfen wird es gebrochen, gespiegelt und aufgefächert. Tipp: Einen Regenbogen siehst du nur mit der Sonne im Rücken – mehr dazu im Workshop „Wunderwelt Licht“.'
      }
    },
    {
      id: 'N2', sector: 'natur', ring: 1, icon: '💧', title: 'Kreisläufe',
      blurb: 'In der Natur geht nichts verloren.',
      task: {
        type: 'order',
        q: 'Bringe den Wasserkreislauf in die richtige Reihenfolge:',
        items: [
          'Die Sonne lässt Wasser verdunsten',
          'Wasserdampf kühlt ab und bildet Wolken',
          'Es regnet oder schneit',
          'Wasser versickert und fließt zurück in Flüsse und Meere'
        ],
        explain: 'Verdunstung → Kondensation → Niederschlag → Abfluss. Seit Milliarden Jahren ist es dasselbe Wasser – vielleicht hat dein Trinkwasser schon einen Dinosaurier erfrischt.'
      }
    },
    {
      id: 'N3', sector: 'natur', ring: 2, icon: '🔥', title: 'Feuer',
      blurb: 'Das Element, das uns Menschen dorthin gebracht hat, wo wir heute sind.',
      task: {
        type: 'choice',
        q: 'Welche drei Dinge braucht ein Feuer (Verbrennungsdreieck)?',
        options: [
          'Holz, Streichhölzer und Wind',
          'Brennstoff, Sauerstoff und ausreichend Wärme (Zündtemperatur)',
          'Wärme, Wasser und Sauerstoff',
          'Brennstoff, Kohlendioxid und Licht'
        ],
        correct: 1,
        explain: 'Fehlt eines der drei, erlischt das Feuer. Genau darauf beruht jedes Löschen: Wasser kühlt, eine Decke nimmt den Sauerstoff. Im Angebot „Im Zeichen des Feuers“ erlebst du das hautnah.'
      }
    },
    {
      id: 'N4', sector: 'natur', ring: 3, icon: '🐝', title: 'Artenvielfalt',
      blurb: 'Ein Ökosystem ist ein Netz – jeder Knoten zählt.',
      task: {
        type: 'choice',
        q: 'Warum ist Artenvielfalt so wichtig?',
        options: [
          'Weil viele Arten einfach schöner aussehen',
          'Vielfältige Ökosysteme sind stabiler und können Störungen besser ausgleichen',
          'Weil jede Art nur eine einzige Aufgabe hat',
          'Sie ist eigentlich nicht wichtig, solange es genug Nutzpflanzen gibt'
        ],
        correct: 1,
        explain: 'Wie bei einem Netz mit vielen Knoten: Reißt ein Faden, halten die anderen. Bienen und andere Bestäuber sichern z. B. einen großen Teil unserer Nahrungspflanzen.'
      }
    },
    {
      id: 'N5', sector: 'natur', ring: 4, icon: '🌍', title: 'Bewahren',
      blurb: 'Die Welt für kommende Generationen erhalten.',
      task: {
        type: 'choice',
        q: 'Was beschreibt Nachhaltigkeit am besten?',
        options: [
          'Möglichst wenig Geld ausgeben',
          'So leben und wirtschaften, dass auch künftige Generationen gut leben können',
          'Nur noch Bio-Produkte kaufen',
          'Die Natur vollständig vom Menschen trennen'
        ],
        correct: 1,
        explain: 'Nachhaltigkeit ist das „N“ in NEO-BIG. Sie verbindet Umwelt, Soziales und Wirtschaft – und sie beginnt mit kleinen Schritten, mit Aufklärung, die nicht anklagt, sondern sensibilisiert.'
      }
    },

    /* ---------------- TECHNIK ---------------- */
    {
      id: 'T1', sector: 'technik', ring: 0, icon: '🔧', title: 'Werkzeug',
      blurb: 'Technik beginnt mit dem ersten Werkzeug.',
      task: {
        type: 'choice',
        q: 'Warum lässt sich ein schwerer Stein mit einer langen Stange leichter anheben?',
        options: [
          'Die Stange macht den Stein leichter',
          'Ein längerer Hebelarm bedeutet: weniger Kraft für dieselbe Wirkung',
          'Metall zieht Steine an',
          'Das stimmt gar nicht – die Länge ist egal'
        ],
        correct: 1,
        explain: 'Das Hebelgesetz: Kraft × Kraftarm = Last × Lastarm. Schon Archimedes sagte: „Gebt mir einen festen Punkt, und ich hebe die Welt aus den Angeln.“'
      }
    },
    {
      id: 'T2', sector: 'technik', ring: 1, icon: '⚡', title: 'Energie',
      blurb: 'Energie geht nie verloren – sie wandelt sich nur um.',
      task: {
        type: 'choice',
        q: 'Ein Pizzaofen hat Klappen zur Luftregulierung. Was passiert, wenn du mehr Luft hineinlässt?',
        options: [
          'Das Feuer wird kleiner, weil es abkühlt',
          'Das Feuer bekommt mehr Sauerstoff und brennt heißer',
          'Es passiert gar nichts',
          'Der Ofen wird sofort kalt'
        ],
        correct: 1,
        explain: 'Mehr Sauerstoff = intensivere Verbrennung = mehr Wärme. Technik heißt, solche Zusammenhänge zu verstehen und gezielt zu steuern – vom Pizzaofen bis zum Kraftwerk.'
      }
    },
    {
      id: 'T3', sector: 'technik', ring: 2, icon: '💻', title: 'Algorithmus',
      blurb: 'Computer denken nicht – sie folgen genauen Anleitungen.',
      task: {
        type: 'order',
        q: 'Ein Algorithmus ist eine Schritt-für-Schritt-Anleitung. Sortiere den Algorithmus „Tee kochen“:',
        items: [
          'Wasser in den Wasserkocher füllen',
          'Wasserkocher einschalten und warten, bis das Wasser kocht',
          'Heißes Wasser über den Teebeutel in die Tasse gießen',
          'Tee ziehen lassen und Teebeutel herausnehmen'
        ],
        explain: 'Genau so arbeitet jedes Programm: klare Schritte in der richtigen Reihenfolge. Wer Algorithmen versteht, nutzt Technik nicht nur, sondern gestaltet sie mit.'
      }
    },
    {
      id: 'T4', sector: 'technik', ring: 3, icon: '♻️', title: 'Ressourcen',
      blurb: 'Welche Ressourcen verbraucht Technik wirklich?',
      task: {
        type: 'choice',
        q: 'Wann entsteht bei einem Smartphone der größte Teil der CO₂-Emissionen seines Lebens?',
        options: [
          'Beim täglichen Aufladen',
          'Bei der Herstellung – Rohstoffabbau und Produktion',
          'Beim Verschicken von Nachrichten',
          'Bei der Entsorgung'
        ],
        correct: 1,
        explain: 'Meist stammen rund drei Viertel der Emissionen aus der Herstellung. Deshalb ist das nachhaltigste Handy oft das, das man schon hat: länger nutzen, reparieren, weitergeben.'
      }
    },
    {
      id: 'T5', sector: 'technik', ring: 4, icon: '⚖️', title: 'Verantwortung',
      blurb: 'Technik soll dem Menschen dienen – nicht ihn dominieren.',
      task: {
        type: 'choice',
        q: 'Welche Haltung zu Technik vertritt variado?',
        options: [
          'Was technisch möglich ist, sollte auch gebaut werden',
          'Technik ist grundsätzlich schlecht für Mensch und Natur',
          'Technik als Werkzeug nutzen, das Probleme nachhaltig löst – verstehen, hinterfragen, mitgestalten',
          'Technik sollte man nur benutzen, nicht verstehen müssen'
        ],
        correct: 2,
        explain: 'Technik ist kein Selbstzweck: Wie effizient ist sie? Welche Ressourcen braucht sie? Ist sie wirklich lebensdienlich? Wer das fragt, macht Technik zum Mittel für eine bessere Zukunft.'
      }
    }
  ],

  // Brücken = Sonderaufgaben an den Überschneidungen zweier Bereiche
  bridges: [
    {
      id: 'MN1', sectors: ['mensch', 'natur'], ring: 1, requires: ['M2', 'N2'], icon: '🌲', title: 'Waldbaden',
      blurb: 'Mensch ∩ Natur: Natur reguliert.',
      task: {
        type: 'choice',
        q: 'Was zeigen Studien zum achtsamen Aufenthalt im Wald („Waldbaden“)?',
        options: [
          'Keine messbaren Effekte',
          'Stresswerte wie Puls, Blutdruck und Cortisol können sinken',
          'Man wird davon müde und unkonzentriert',
          'Es wirkt nur bei Sonnenschein'
        ],
        correct: 1,
        explain: 'Die Natur reguliert: Schon kurze Zeit im Grünen senkt nachweislich Stress. Wir stammen aus der Natur und tragen sie als Erbe in uns.'
      }
    },
    {
      id: 'TN1', sectors: ['technik', 'natur'], ring: 1, requires: ['T2', 'N2'], icon: '🦎', title: 'Bionik',
      blurb: 'Natur ∩ Technik: von der Natur lernen.',
      task: {
        type: 'choice',
        q: 'Welches Vorbild aus der Natur hatte der Erfinder des Klettverschlusses?',
        options: [
          'Spinnennetze',
          'Kletten, die im Fell seines Hundes hängen blieben',
          'Die Zunge eines Chamäleons',
          'Die Schuppen eines Fisches'
        ],
        correct: 1,
        explain: 'Georges de Mestral schaute sich die Kletten unter dem Mikroskop an und entdeckte winzige Häkchen. Bionik zeigt: Die Natur hatte viele gute Ideen zuerst.'
      }
    },
    {
      id: 'MT1', sectors: ['mensch', 'technik'], ring: 1, requires: ['M2', 'T2'], icon: '📱', title: 'Digitale Balance',
      blurb: 'Mensch ∩ Technik: Wer steuert wen?',
      task: {
        type: 'choice',
        q: 'Was hilft am meisten für einen gesunden Umgang mit dem Smartphone?',
        options: [
          'Immer erreichbar sein, um nichts zu verpassen',
          'Bewusste Pausen, weniger Benachrichtigungen und echte Zeit mit anderen',
          'Das Handy nur nachts benutzen',
          'Möglichst viele Apps parallel nutzen'
        ],
        correct: 1,
        explain: 'Die Frage ist: Hat der Mensch noch Macht über die Technik – oder diktiert sie sein Leben? Bewusste Regeln geben dir die Kontrolle zurück.'
      }
    },
    {
      id: 'MN2', sectors: ['mensch', 'natur'], ring: 3, requires: ['M4', 'N4'], icon: '🧗', title: 'Erlebnis|pädagogik',
      blurb: 'Mensch ∩ Natur: Lernen durch Erleben.',
      task: {
        type: 'choice',
        q: 'Warum bleibt Gelerntes bei Erlebnissen in der Natur oft besonders lange hängen?',
        options: [
          'Weil man draußen weniger lernen muss',
          'Weil Kopf, Herz und Hand gleichzeitig angesprochen werden',
          'Weil frische Luft das Gedächtnis ersetzt',
          'Weil es keine Regeln gibt'
        ],
        correct: 1,
        explain: 'Ganzheitliches Lernen verbindet Körper, Gefühl und Verstand. Genau so versteht sich variado als ErLebenswerkstatt – handlungsorientiert und erfahrungsbasiert.'
      }
    },
    {
      id: 'TN2', sectors: ['technik', 'natur'], ring: 3, requires: ['T4', 'N4'], icon: '☀️', title: 'Sonnen|energie',
      blurb: 'Natur ∩ Technik: Energie aus der Natur – klug genutzt.',
      task: {
        type: 'choice',
        q: 'Was macht eine Solarzelle?',
        options: [
          'Sie speichert Wärme wie ein Ofen',
          'Sie wandelt Licht direkt in elektrischen Strom um',
          'Sie erzeugt Strom aus Wind',
          'Sie funktioniert nur bei direkter Mittagssonne'
        ],
        correct: 1,
        explain: 'Photovoltaik nutzt den photoelektrischen Effekt: Licht setzt in Halbleitern Ladungen frei. Sonnenlicht, das eine Stunde lang auf die Erde fällt, enthält mehr Energie, als die Menschheit in einem Jahr verbraucht.'
      }
    },
    {
      id: 'MT2', sectors: ['mensch', 'technik'], ring: 3, requires: ['M4', 'T4'], icon: '🛠️', title: 'Maker-|Team',
      blurb: 'Mensch ∩ Technik: Innovation braucht Vielfalt.',
      task: {
        type: 'choice',
        q: 'Ein Team soll einen kleinen Roboter bauen. Wann entsteht meist die beste Lösung?',
        options: [
          'Wenn die technisch stärkste Person alles allein entscheidet',
          'Wenn unterschiedliche Stärken, Ideen und Perspektiven zusammenkommen',
          'Wenn alle genau gleich denken',
          'Wenn man die Aufgabe so schnell wie möglich erledigt'
        ],
        correct: 1,
        explain: 'Offenheit und Diversität – das „O“ in NEO-BIG. Jede Person bringt besondere Ressourcen mit. Du bist das fehlende Puzzleteil!'
      }
    }
  ],

  final: {
    title: 'Die Synergie',
    q: 'Letzte Aufgabe: Was bedeutet „Synergie“ bei variado?',
    options: [
      'Technik ersetzt nach und nach Mensch und Natur',
      'Mensch, Natur und Technik verbinden sich so, dass etwas Neues entsteht – mehr als die Summe seiner Teile',
      'Jeder der drei Bereiche wird für sich allein perfektioniert',
      'Die Natur hat immer Vorrang vor Mensch und Technik'
    ],
    correct: 1,
    explain: 'Genau! Ein Gleichgewicht, das Menschen nützt, die Umwelt bewahrt und Technik bewusst einsetzt. Mensch, Natur, Technik – kein Gegensatz, sondern Teile eines perfekten Puzzles.'
  }
};
