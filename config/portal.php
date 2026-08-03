<?php

return [
    // Externe Reservierungs-App. Funnel-CTA leitet hierhin (getrackt, UTM + Partner-cId).
    'reservation_url' => env('RESERVATION_URL', 'https://jetzt.wunschkennzeichen-reservieren.de/wunschkennzeichen'),
    'reservation_cid' => env('RESERVATION_CID', '1085'),   // Partner-ID, immer gleich

    // Basis-Domain des Portals (für absolute URLs in Sitemap/Canonical/Schema)
    'site_name' => 'Wunschkennzeichen-Portal',

    // Öffentliche Adresse für Ausdrucke/Downloads (PDF-Formulare). Bewusst nicht
    // url()/APP_URL, damit auf Dateien, die beim Nutzer landen, nie eine lokale
    // Entwicklungs- oder Staging-URL steht.
    'public_url' => env('PUBLIC_URL', 'https://www.wunschkennzeichen-reservieren.de'),

    // Matomo (selbst gehostet). Leer = Tracking deaktiviert.
    'matomo_url'     => env('MATOMO_URL'),          // z.B. https://stats.example.de
    'matomo_site_id' => env('MATOMO_SITE_ID'),      // z.B. 1

    // SEO / E-E-A-T
    'og_image'    => env('OG_IMAGE'),               // absolute URL zum Default-Teaserbild
    'author_name' => 'Redaktion Wunschkennzeichen-Portal',
];
