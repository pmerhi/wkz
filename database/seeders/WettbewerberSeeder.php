<?php

namespace Database\Seeders;

use App\Models\Wettbewerber;
use Illuminate\Database\Seeder;

class WettbewerberSeeder extends Seeder
{
    /** Top 10 distinkte Wettbewerber (Marco-Recherche, Stand 2026-06-22). */
    public function run(): void
    {
        $rows = [
            ['name' => 'Wunschkennzeichen-reservieren.jetzt', 'domain' => 'wunschkennzeichen-reservieren.jetzt', 'url' => 'https://wunschkennzeichen-reservieren.jetzt/', 'typ' => 'funnel', 'rang' => 1,
             'betreiber' => 'Schmid Digital GmbH, Nattheim (USt DE336391102, HRB 741188 Ulm)',
             'dedup_hinweis' => 'Eigene Betreiber-GmbH, eigene USt/HRB, eigene Marke, 2267 Trustpilot-Reviews. Keine bekannten Spiegel.'],

            ['name' => 'zulassung.de', 'domain' => 'zulassung.de', 'url' => 'https://www.zulassung.de/', 'typ' => 'funnel', 'rang' => 2,
             'betreiber' => 'Klickjäger GmbH, Herzebrock-Clarholz (USt DE338112289, HRB 44077 Bielefeld)',
             'dedup_hinweis' => 'Eigene Betreiber-GmbH. Nutzt kennzeichen.click/Kennzeichen Services (Schwerte) als White-Label-Backend, ist aber als Firma/Marke eigenständig.'],

            ['name' => 'KennzeichenKing', 'domain' => 'kennzeichenking.de', 'url' => 'https://www.kennzeichenking.de/', 'typ' => 'funnel', 'rang' => 3,
             'betreiber' => 'blackbird GmbH, Schönefeld (USt DE321129073, HRB 17431 Cottbus)',
             'dedup_hinweis' => 'ECHTE DUBLETTE: wunschkennzeichen-reservierung.de = derselbe Betreiber (gleiche USt DE321129073, gleiche Anschrift, gleiches Template) → als Spiegel gezählt, hier repräsentativ geführt.'],

            ['name' => 'Wunschkennzeichen.de', 'domain' => 'wunschkennzeichen.de', 'url' => 'https://wunschkennzeichen.de/', 'typ' => 'mix', 'rang' => 4,
             'betreiber' => 'Wunschkennzeichen Deutschland GmbH, Kempen (USt DE209910016, HRB 9681 Krefeld)',
             'dedup_hinweis' => 'Eigene Betreiber-GmbH, eigene Anschrift/USt. Shop + Reservierung. Keine bekannten Spiegel.'],

            ['name' => 'kennzeichen.click (inkl. ADAC-Shop Wunschkennzeichen)', 'domain' => 'kennzeichen.click', 'url' => 'https://www.kennzeichen.click/', 'typ' => 'funnel', 'rang' => 5,
             'betreiber' => 'kennzeichen.click GmbH, Schwerte (USt DE334336160, HRB 13027 Hagen, GF Marcel Ruhnau)',
             'dedup_hinweis' => 'Repräsentant des White-Label-Netzwerks "Kennzeichen Services" (Schwerte). Spiegel/Frontends: wunschkennzeichen.adac-shop.de, wunschkennzeichen.ikfz.net (Deutsche Auto Digital GmbH) — nicht separat gezählt.'],

            ['name' => 'Kennzeichen deutschlandweit', 'domain' => 'kennzeichen-deutschlandweit.de', 'url' => 'https://www.kennzeichen-deutschlandweit.de/', 'typ' => 'mix', 'rang' => 6,
             'betreiber' => 'Einzelfirma Jürgen Artur Weindl, Burgau (USt DE304714653)',
             'dedup_hinweis' => 'Eigener Einzelunternehmer-Betreiber, eigene Anschrift/USt, eigenes Shop-Template. Keine bekannten Spiegel.'],

            ['name' => 'Kfzkennzeichen.online', 'domain' => 'kfzkennzeichen.online', 'url' => 'https://kfzkennzeichen.online/', 'typ' => 'mix', 'rang' => 7,
             'betreiber' => 'web.klick GmbH, Berlin (USt DE357754506, HRB 246537 B Charlottenburg)',
             'dedup_hinweis' => 'Eigene GmbH, eigene Anschrift/USt/HRB. Keine bekannten Spiegel.'],

            ['name' => 'KennzeichenMax', 'domain' => 'kennzeichenmax.de', 'url' => 'https://www.kennzeichenmax.de/', 'typ' => 'shop', 'rang' => 8,
             'betreiber' => 'Mobego GmbH, Bremen (USt DE300874676, HRB 30429 Bremen)',
             'dedup_hinweis' => 'Eigene GmbH, eigene Anschrift/USt/HRB. Klassischer Schilder-Shop mit Wunschkennzeichen-Konfigurator. Keine bekannten Spiegel.'],

            ['name' => 'Günstige-Kennzeichen.de', 'domain' => 'guenstige-kennzeichen.de', 'url' => 'https://www.guenstige-kennzeichen.de/', 'typ' => 'shop', 'rang' => 9,
             'betreiber' => 'Turbo Online GmbH, Ahrensburg (USt DE299642209, HRB 20689 HL Lübeck)',
             'dedup_hinweis' => 'Eigene GmbH, eigene Anschrift/USt/HRB. Keine bekannten Spiegel.'],

            ['name' => 'KFZ-Kennzeichen.net', 'domain' => 'kfz-kennzeichen.net', 'url' => 'https://kfz-kennzeichen.net/', 'typ' => 'funnel', 'rang' => 10,
             'betreiber' => 'Meng GmbH, Buxtehude (USt DE293562966, HRB 209945 Tostedt)',
             'dedup_hinweis' => 'Eigener Frontend-Betreiber mit eigener Firma/Marke. Nutzt kennzeichen.click (Schwerte) als Backend, ist aber rechtlich eigenständig → als eigener Marktteilnehmer geführt.'],

            ['name' => 'Gutschild.de', 'domain' => 'gutschild.de', 'url' => 'https://www.gutschild.de/', 'typ' => 'mix', 'rang' => 11,
             'betreiber' => 'GUTSCHILD GmbH, Weiterstadt (USt DE369264988, HRB 106627 Darmstadt, GF Reinhardt/Donhauser)',
             'dedup_hinweis' => 'Nutzer-Wunsch. Eigene GmbH, eigene Anschrift/USt/HRB. Shop (Kennzeichen/3D/Fun) + Wunschkennzeichen-Reservierung + Ratgeber + Zulassungsstellen-Verzeichnis. Keine Netzwerk-/Schwerte-Signale. Distinkt.'],

            ['name' => 'Straßenverkehrsamt.de (STVA)', 'domain' => 'strassenverkehrsamt.de', 'url' => 'https://www.strassenverkehrsamt.de/', 'typ' => 'mix', 'rang' => 12,
             'betreiber' => 'STVA Deutschland GmbH, Berlin (USt DE815288929, HRB 133514B, GF Thomas Schulz)',
             'dedup_hinweis' => 'Nutzer-Wunsch. Serviceportal + Zulassungsstellen-Verzeichnis + Wunschkennzeichen-Info + Versicherung + Magazin. Andere Firma als web.klick GmbH (#7), kein Schwerte-Netzwerk. Distinkt.'],

            ['name' => 'Zulassungsstelle.de', 'domain' => 'zulassungsstelle.de', 'url' => 'https://zulassungsstelle.de/', 'typ' => 'verzeichnis', 'rang' => 13,
             'betreiber' => 'DKK24 KFZ Zulassungsservice (Einzelunternehmen), Inh. Wahidullah Dad khah, Hamburg (USt DE276024738)',
             'dedup_hinweis' => 'Verzeichnis-Marktführer ("alle 863 Zulassungsstellen") + Wunschkennzeichen + Versicherung. Eigener Inhaber/USt, kein Schwerte-Netzwerk. Distinkt. (Von Marco urspr. nicht in Top-Funnel-Liste, da Verzeichnistyp.)'],
        ];

        foreach ($rows as $row) {
            Wettbewerber::updateOrCreate(['domain' => $row['domain']], $row);
        }
    }
}
