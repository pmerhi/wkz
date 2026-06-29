<?php

/*
 * Alte /kennzeichen/{code-id}/-URLs des Vorgängerprojekts, deren Unterscheidungszeichen
 * wir nicht (mehr) führen (auslaufende Altkennzeichen). Zuordnung auf das heute gültige
 * Kürzel des zuständigen Kreises (Quelle: alter Seitentitel „… im Kreis X").
 *
 * Schlüssel = voller alter Slug (inkl. -ID, da gleiche Codes verschiedene Kreise treffen
 * können, z.B. sle-1757 = Euskirchen, sle-329 = Düren). Wert = unser Kürzel-Slug.
 */

return [
    'ah-245'   => 'bor',   // AH  – Kreis Borken
    'bf-273'   => 'st',    // BF  – Kreis Steinfurt
    'boh-43'   => 'bor',   // BOH – Kreis Borken
    'hhm-104'  => 'blk',   // HHM – Burgenlandkreis
    'lp-121'   => 'so',    // LP  – Kreis Soest
    'mel-441'  => 'os',    // MEL – Landkreis Osnabrück
    'nau-362'  => 'hvl',   // NAU – Landkreis Havelland
    'neb-139'  => 'blk',   // NEB – Burgenlandkreis
    'nmb-365'  => 'blk',   // NMB – Burgenlandkreis
    'rn-341'   => 'hvl',   // RN  – Landkreis Havelland
    'sle-1757' => 'eu',    // SLE – Kreis Euskirchen
    'sle-329'  => 'dn',    // SLE – Kreis Düren
    'vit-209'  => 'reg',   // VIT – Landkreis Regen
    'war-299'  => 'hx',    // WAR – Kreis Höxter
    'wat-44'   => 'bo',    // WAT – Bochum
    'wtl-491'  => 'os',    // WTL – Landkreis Osnabrück (Wittlage)
];
