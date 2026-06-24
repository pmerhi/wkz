<?php

/*
 * A/B-Tests. Jeder Eintrag: enabled + Varianten mit Gewichten (Summe beliebig).
 * Zuweisung ist sticky pro Besucher (Cookie ab_<key>, 90 Tage).
 * `cta` definiert den Variantentext zentral – so ist die Behandlung siteweit identisch.
 */
return [
    'cta_text' => [
        'enabled'  => true,
        'variants' => [
            'a' => 50,   // Kontrolle (neutral)
            'b' => 50,   // Dringlichkeit ("in 2 Minuten")
        ],
        'cta' => [
            'a' => 'Wunschkennzeichen prüfen &amp; reservieren →',
            'b' => 'Jetzt in 2 Minuten sichern →',
        ],
    ],
];
