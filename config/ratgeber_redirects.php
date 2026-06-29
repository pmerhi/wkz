<?php

/*
 * Zuordnung alter Ratgeber-Slugs (Vorgängerprojekt unter /kfz-zulassung/, /kfz-kennzeichen/,
 * /tipps-fuer-fahrzeughalter/, /kfz-ummeldung-abmeldung/) auf den passenden bestehenden
 * /kfz-ratgeber/-Artikel. Schlüssel = alter Slug, Wert = unser Artikel-Slug.
 *
 * Nicht aufgeführte alte Slugs werden 1:1 verwendet (gleicher Slug existiert bereits oder
 * wurde als neuer Artikel importiert). Unbekannte → /kfz-ratgeber (Übersicht).
 */

return [
    // — Themen-Varianten → bestehender Artikel —
    'altkennzeichen-infografik'                            => 'altkennzeichen',
    'anhaenger'                                            => 'anhaenger-zulassen',
    'auto-abmelden-anderer-kreis'                          => 'auto-abmelden',
    'auto-abmelden-gleicher-kreis'                         => 'auto-abmelden',
    'auto-erben'                                           => 'auto-erben-ummelden',
    'auto-verkaufen'                                       => 'auto-verkaufen-abmelden',
    'betriebserlaubnis-einzelbetriebserlaubnis'           => 'betriebserlaubnis',
    'betriebserlaubnis-fahrzeugteile'                     => 'betriebserlaubnis',
    'betriebserlaubnis-nicht-zulassungspflichtig'         => 'betriebserlaubnis',
    'bussgeldkatalog-2021'                                => 'bussgeldkatalog',
    'einzugsermaechtigung-kfz-steuer'                     => 'kfz-steuer-lastschrift',
    'fakten-zur-kfz-versicherung'                         => 'kfz-versicherung-vergleich',
    'finanziertes-fahrzeug'                               => 'finanziertes-fahrzeug-zulassen',
    'gebrauchtwagen-nicht-eu-import'                      => 'auto-import-nicht-eu',
    'gebrauchtwagen-zulassung'                            => 'gebrauchtwagen-zulassen',
    'gebrauchtwagengarantie-sachmaengelhaftung'          => 'gebrauchtwagengarantie',
    'gebrauchtwagenverkauf-im-internet'                  => 'auto-verkaufen-abmelden',
    'gegen-betrug-beim-autokauf-und-autoverkauf-absichern' => 'betrug-autokauf',
    'i-kfz'                                               => 'i-kfz-online-zulassung',
    'internationale-kfzkennzeichen'                      => 'eu-kennzeichen',
    'kennzeichen-verloren-oder-gestohlen'               => 'kennzeichen-verloren-gestohlen',
    'kfz-steuer-halbieren'                              => 'kfz-steuer-berechnen',
    'kfz-versicherungsvergleich'                        => 'kfz-versicherung-vergleich',
    'namensaenderung'                                   => 'namensaenderung-fahrzeugschein',
    'neuwagen-eu-import'                                => 'gebrauchtwagen-eu-import',
    'neuwagen-nicht-eu-import'                          => 'auto-import-nicht-eu',
    'neuwagen-zulassung'                               => 'neuwagen-zulassen',
    'oldtimer'                                         => 'oldtimer-zulassen',
    'oldtimer-kennzeichen'                            => 'h-kennzeichen',
    'oldtimer-saisonkennzeichen'                      => 'saisonkennzeichen',
    'online-abmeldung-von-fahrzeugen'                => 'auto-abmelden',
    'tipps-zum-kfz-gutachten'                        => 'kfz-gutachten',
    'verbandskasten-und-warnweste'                  => 'verbandskasten-pflicht',
    'verbotene-kennzeichen'                         => 'verbotene-wunschkennzeichen',
    'weitere-kennzeichen'                          => 'behoerdenkennzeichen',

    // — Echte Lücken: werden als eigene Artikel importiert (Slug bleibt gleich) —
    // benzinpreise-in-deutschland, elektromobilitaet-begriffe, internationaler-zulassungsschein,
    // pkw-erstatzteile-verkaufen-ankaufen, selbstleuchtendes-kennzeichen, us-streitkraefte-in-deutschland
];
