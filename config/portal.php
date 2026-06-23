<?php

return [
    // Externe Reservierungs-App (eigene Domain). Platzhalter — finale Domain offen.
    // Funnel-CTA leitet hierhin (getrackt). UTM-Parameter werden ergänzt.
    'reservation_url' => env('RESERVATION_URL', 'https://reservierung.example'),

    // Basis-Domain des Portals (für absolute URLs in Sitemap/Canonical/Schema)
    'site_name' => 'Wunschkennzeichen-Portal',

    // Matomo (selbst gehostet). Leer = Tracking deaktiviert.
    'matomo_url'     => env('MATOMO_URL'),          // z.B. https://stats.example.de
    'matomo_site_id' => env('MATOMO_SITE_ID'),      // z.B. 1

    // SEO / E-E-A-T
    'og_image'    => env('OG_IMAGE'),               // absolute URL zum Default-Teaserbild
    'author_name' => 'Redaktion Wunschkennzeichen-Portal',
];
