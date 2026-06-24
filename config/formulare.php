<?php

/*
 * Eigene Kfz-Formulare (Muster) zum Download als PDF.
 * Jedes Formular: titel, beschreibung, intro, abschnitte (felder/checkboxen), unterschriften.
 * Datengetrieben → ein generisches Blade-Template rendert alle.
 */
return [

    'vollmacht' => [
        'titel'        => 'Vollmacht zur Kfz-Zulassung',
        'beschreibung' => 'Bevollmächtige eine andere Person, dein Fahrzeug an-, ab- oder umzumelden.',
        'intro'        => 'Hiermit bevollmächtige ich die unten genannte Person, die angekreuzten '
            .'Vorgänge für das genannte Fahrzeug bei der Zulassungsstelle in meinem Namen vorzunehmen.',
        'abschnitte'   => [
            ['titel' => 'Vollmachtgeber/in (Fahrzeughalter/in)', 'felder' => [
                'Name, Vorname', 'Anschrift (Straße, PLZ, Ort)', 'Geburtsdatum', 'Telefon / E-Mail',
            ]],
            ['titel' => 'Bevollmächtigte Person', 'felder' => [
                'Name, Vorname', 'Anschrift (Straße, PLZ, Ort)', 'Personalausweis-/Reisepass-Nr.',
            ]],
            ['titel' => 'Fahrzeug', 'felder' => [
                'Amtliches Kennzeichen', 'Fahrzeug-Identifizierungsnummer (FIN)',
            ]],
            ['titel' => 'Umfang der Vollmacht', 'checkboxen' => [
                'Neuzulassung / Anmeldung', 'Außerbetriebsetzung / Abmeldung',
                'Umschreibung / Ummeldung', 'Wunschkennzeichen-Reservierung & -Zuteilung',
                'Aushändigung von Papieren, Plaketten und Stempel',
            ]],
        ],
        'unterschriften' => ['Ort, Datum', 'Unterschrift Vollmachtgeber/in'],
    ],

    'sepa-lastschriftmandat-kfz-steuer' => [
        'titel'        => 'SEPA-Lastschriftmandat für die Kfz-Steuer',
        'beschreibung' => 'Ermächtige den Einzug der Kraftfahrzeugsteuer per Lastschrift von deinem Konto.',
        'intro'        => 'Ich ermächtige die zuständige Bundeskasse / Hauptzollamt, die Kraftfahrzeugsteuer '
            .'für das genannte Fahrzeug von meinem Konto mittels SEPA-Lastschrift einzuziehen. Zugleich weise '
            .'ich mein Kreditinstitut an, die Lastschriften einzulösen.',
        'abschnitte'   => [
            ['titel' => 'Kontoinhaber/in', 'felder' => [
                'Name, Vorname', 'Anschrift (Straße, PLZ, Ort)',
            ]],
            ['titel' => 'Bankverbindung', 'felder' => [
                'IBAN', 'BIC', 'Kreditinstitut',
            ]],
            ['titel' => 'Fahrzeug', 'felder' => [
                'Amtliches Kennzeichen', 'Fahrzeug-Identifizierungsnummer (FIN)',
            ]],
        ],
        'unterschriften' => ['Ort, Datum', 'Unterschrift Kontoinhaber/in'],
    ],

    'einverstaendnis-erziehungsberechtigte' => [
        'titel'        => 'Einverständniserklärung beider Erziehungsberechtigten',
        'beschreibung' => 'Erforderlich, wenn ein minderjähriges Kind als Halter eingetragen werden soll.',
        'intro'        => 'Als Erziehungsberechtigte erklären wir unser Einverständnis, dass das unten genannte '
            .'minderjährige Kind als Halter/in des Fahrzeugs in die Zulassungsbescheinigung eingetragen wird.',
        'abschnitte'   => [
            ['titel' => 'Minderjährige/r (künftige/r Halter/in)', 'felder' => [
                'Name, Vorname', 'Geburtsdatum', 'Anschrift',
            ]],
            ['titel' => 'Erziehungsberechtigte/r 1', 'felder' => [
                'Name, Vorname', 'Anschrift', 'Personalausweis-Nr.',
            ]],
            ['titel' => 'Erziehungsberechtigte/r 2', 'felder' => [
                'Name, Vorname', 'Anschrift', 'Personalausweis-Nr.',
            ]],
            ['titel' => 'Fahrzeug', 'felder' => [
                'Amtliches Kennzeichen', 'Fahrzeug-Identifizierungsnummer (FIN)',
            ]],
        ],
        'unterschriften' => ['Ort, Datum · Unterschrift Erziehungsberechtigte/r 1', 'Ort, Datum · Unterschrift Erziehungsberechtigte/r 2'],
    ],

    'eidesstattliche-versicherung-zb1' => [
        'titel'        => 'Eidesstattliche Versicherung – Verlust Zulassungsbescheinigung Teil I',
        'beschreibung' => 'Bei Verlust des Fahrzeugscheins (Zulassungsbescheinigung Teil I).',
        'intro'        => 'Ich versichere an Eides statt, dass die Zulassungsbescheinigung Teil I (Fahrzeugschein) '
            .'für das unten genannte Fahrzeug abhandengekommen / nicht mehr auffindbar ist und sich nicht in den '
            .'Händen Dritter befindet. Mir ist bekannt, dass eine falsche eidesstattliche Versicherung strafbar ist.',
        'abschnitte'   => [
            ['titel' => 'Fahrzeughalter/in', 'felder' => [
                'Name, Vorname', 'Anschrift', 'Geburtsdatum',
            ]],
            ['titel' => 'Fahrzeug', 'felder' => [
                'Amtliches Kennzeichen', 'Fahrzeug-Identifizierungsnummer (FIN)',
            ]],
            ['titel' => 'Angaben zum Verlust', 'felder' => [
                'Wann zuletzt gesehen?', 'Vermuteter Verbleib / Hergang',
            ]],
        ],
        'unterschriften' => ['Ort, Datum', 'Unterschrift Halter/in'],
    ],

    'eidesstattliche-versicherung-zb2' => [
        'titel'        => 'Eidesstattliche Versicherung – Verlust Zulassungsbescheinigung Teil II',
        'beschreibung' => 'Bei Verlust des Fahrzeugbriefs (Zulassungsbescheinigung Teil II).',
        'intro'        => 'Ich versichere an Eides statt, dass die Zulassungsbescheinigung Teil II (Fahrzeugbrief) '
            .'für das unten genannte Fahrzeug abhandengekommen / nicht mehr auffindbar ist und sich nicht in den '
            .'Händen Dritter befindet. Mir ist bekannt, dass eine falsche eidesstattliche Versicherung strafbar ist.',
        'abschnitte'   => [
            ['titel' => 'Fahrzeughalter/in', 'felder' => [
                'Name, Vorname', 'Anschrift', 'Geburtsdatum',
            ]],
            ['titel' => 'Fahrzeug', 'felder' => [
                'Amtliches Kennzeichen', 'Fahrzeug-Identifizierungsnummer (FIN)',
            ]],
            ['titel' => 'Angaben zum Verlust', 'felder' => [
                'Wann zuletzt gesehen?', 'Vermuteter Verbleib / Hergang',
            ]],
        ],
        'unterschriften' => ['Ort, Datum', 'Unterschrift Halter/in'],
    ],

    'antrag-halterauskunft' => [
        'titel'        => 'Antrag auf Halterauskunft',
        'beschreibung' => 'Einfache Registerauskunft zum Halter eines Fahrzeugs bei berechtigtem Interesse.',
        'intro'        => 'Hiermit beantrage ich eine Auskunft aus dem Fahrzeugregister zum Halter des unten '
            .'genannten Fahrzeugs. Das rechtliche bzw. berechtigte Interesse lege ich nachfolgend dar.',
        'abschnitte'   => [
            ['titel' => 'Antragsteller/in', 'felder' => [
                'Name, Vorname / Firma', 'Anschrift', 'Telefon / E-Mail',
            ]],
            ['titel' => 'Fahrzeug, zu dem Auskunft begehrt wird', 'felder' => [
                'Amtliches Kennzeichen', 'Tatzeit / Tatort (falls relevant)',
            ]],
            ['titel' => 'Begründung des berechtigten Interesses', 'felder' => [
                'Grund der Anfrage', '', '',
            ]],
        ],
        'unterschriften' => ['Ort, Datum', 'Unterschrift Antragsteller/in'],
    ],

];
