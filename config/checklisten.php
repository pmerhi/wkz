<?php

/*
 * "Was muss ich mitbringen?" – Unterlagen-Checklisten je Anliegen.
 * Datengetrieben; eine Komponente rendert alle als aufklappbare Listen.
 */
return [
    'neuzulassung' => [
        'titel' => 'Neuzulassung / Auto anmelden',
        'items' => [
            'Personalausweis oder Reisepass (mit Meldebescheinigung)',
            'Zulassungsbescheinigung Teil II (Fahrzeugbrief)',
            'eVB-Nummer der Kfz-Versicherung',
            'SEPA-Lastschriftmandat für die Kfz-Steuer',
            'Gültiger HU-Nachweis (bei Gebrauchtfahrzeugen)',
            'Bei Firmen: Handelsregisterauszug / Gewerbeanmeldung',
        ],
    ],
    'ummeldung' => [
        'titel' => 'Ummeldung (Umzug oder Halterwechsel)',
        'items' => [
            'Personalausweis oder Reisepass',
            'Zulassungsbescheinigung Teil I und Teil II',
            'eVB-Nummer der Versicherung',
            'Bei Halterwechsel: Kaufvertrag',
            'Bisheriges Kennzeichen (falls nicht mitgenommen wird)',
            'Gültiger HU-Nachweis',
        ],
    ],
    'abmeldung' => [
        'titel' => 'Abmeldung / Außerbetriebsetzung',
        'items' => [
            'Personalausweis oder Reisepass',
            'Zulassungsbescheinigung Teil I',
            'Beide Kennzeichen (mit Stempelplaketten)',
        ],
    ],
    'wunschkennzeichen' => [
        'titel' => 'Wunschkennzeichen zuteilen',
        'items' => [
            'Reservierungsnachweis bzw. reservierte Kombination',
            'Alle Unterlagen des jeweiligen Vorgangs (An-/Ummeldung)',
            'Geprägte Schilder mit der Wunsch-Kombination',
        ],
    ],
];
