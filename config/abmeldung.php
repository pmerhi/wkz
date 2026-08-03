<?php

/*
 * Eigener Kfz-Abmeldeservice (externe Subdomain, gleiche Marke).
 * Der CTA zeigt nie direkt auf die Zieldomain, sondern auf die serverseitig
 * getrackte Weiterleitung /go/abmeldung – analog zum Reservierungs-Funnel
 * (adblock-fest, /go/ ist in der robots.txt gesperrt).
 */
return [
    'url' => env('ABMELDUNG_URL', 'https://abmelden.wunschkennzeichen-reservieren.de/kfz-abmeldung'),
    'cid' => env('ABMELDUNG_CID', '1085'),   // Partner-ID, wie beim Reservierungs-Funnel

    // Trust-Signale der Zielseite. Werden dort gepflegt – hier nur zur Anzeige,
    // deshalb bewusst als Config und nicht hart im Template.
    'bewertung' => [
        'wert'   => '4,8',
        'anzahl' => '2.129',
        'quelle' => 'Google',
    ],

    // USPs der Zielseite (1:1 deren Versprechen – nichts dazuerfinden).
    'usps' => [
        'Kein Behördengang nötig',
        'In 2 Minuten erledigt',
        'Digitale Abmeldebestätigung',
        'Geld-zurück-Garantie',
    ],

    // Preis des Services. Die Zielseite gibt ihn nicht im HTML aus, deshalb steht
    // hier null = wird auf /kfz-abmeldung nicht behauptet. Sobald der Preis fix ist,
    // hier eintragen (z. B. '39,90 €') – die Landingpage zeigt ihn dann automatisch.
    'preis' => env('ABMELDUNG_PREIS'),

    /*
     * Ratgeber-Slugs, auf denen der Abmelde-CTA ausgespielt wird.
     * primaer   = großer CTA-Block direkt unter dem Artikel (Suchintention = abmelden)
     * sekundaer = schlanker Hinweiskasten (Abmeldung ist nur Teilaspekt des Themas)
     * Alles andere bleibt CTA-frei, damit der Ratgeber nicht wie eine Anzeigenstrecke wirkt.
     */
    'ratgeber' => [
        'primaer' => [
            'auto-abmelden',
            'auto-stilllegen',
            'auto-verkaufen-abmelden',
            'kennzeichen-entstempeln',
            'fahrzeug-verschrotten',
            'i-kfz-stufe-4',
        ],
        'sekundaer' => [
            'wiederzulassung',
            'halterwechsel-auto',
            'i-kfz-online-zulassung',
            'i-kfz-ablauf-dauer',
            'i-kfz-voraussetzungen',
            'ausfuhrkennzeichen',
            'betrug-autokauf',
            'kfz-steuer-berechnen',
            'kfz-steuer-lastschrift',
            'fahrzeugschein-verloren',
            'kennzeichen-verloren-gestohlen',
            'hauptuntersuchung-tuv',
            'auto-ummelden-kreiswechsel',
        ],
    ],
];
