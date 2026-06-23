<?php

/*
 * A/B-Tests. Jeder Eintrag: enabled + Varianten mit Gewichten (Summe beliebig).
 * Zuweisung ist sticky pro Besucher (Cookie ab_<key>, 90 Tage).
 */
return [
    'cta_text' => [
        'enabled'  => true,
        'variants' => [
            'a' => 50,   // Kontrolle (neutral)
            'b' => 50,   // Dringlichkeit ("in 2 Minuten")
        ],
    ],
];
