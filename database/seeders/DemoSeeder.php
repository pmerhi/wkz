<?php

namespace Database\Seeders;

use App\Models\Bundesland;
use App\Models\Kategorie;
use App\Models\KennzeichenKuerzel;
use App\Models\RatgeberArtikel;
use App\Models\Tag;
use App\Models\Zulassungsstelle;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $oeffnung = [
            ['day' => 'Monday',    'label' => 'Montag',     'opens' => '08:00', 'closes' => '13:00'],
            ['day' => 'Tuesday',   'label' => 'Dienstag',   'opens' => '08:00', 'closes' => '13:00'],
            ['day' => 'Wednesday', 'label' => 'Mittwoch',   'opens' => '08:00', 'closes' => '13:00'],
            ['day' => 'Thursday',  'label' => 'Donnerstag', 'opens' => '08:00', 'closes' => '18:00'],
            ['day' => 'Friday',    'label' => 'Freitag',    'opens' => '08:00', 'closes' => '12:00'],
        ];

        $berlin = Bundesland::create(['name' => 'Berlin', 'slug' => 'berlin']);
        $bayern = Bundesland::create(['name' => 'Bayern', 'slug' => 'bayern']);

        $zstBerlin = Zulassungsstelle::create([
            'name' => 'Kfz-Zulassungsbehörde Berlin', 'slug' => 'berlin',
            'traeger' => 'Land Berlin', 'strasse' => 'Ferdinand-Schultze-Str. 55',
            'plz' => '13055', 'ort' => 'Berlin', 'bundesland_id' => $berlin->id,
            'lat' => 52.5419, 'lng' => 13.4895, 'telefon' => '+49 30 90269-3300',
            'website' => 'https://service.berlin.de', 'termin_url' => 'https://service.berlin.de/dienstleistung/121484/',
            'oeffnungszeiten' => $oeffnung, 'quelle' => 'Demo-Daten', 'last_imported_at' => now(),
        ]);

        $zstMuenchen = Zulassungsstelle::create([
            'name' => 'Kfz-Zulassungsstelle München', 'slug' => 'muenchen',
            'traeger' => 'Landeshauptstadt München', 'strasse' => 'Eichstätter Str. 2',
            'plz' => '80686', 'ort' => 'München', 'bundesland_id' => $bayern->id,
            'lat' => 48.1430, 'lng' => 11.5197, 'telefon' => '+49 89 233-96000',
            'website' => 'https://stadt.muenchen.de', 'termin_url' => 'https://stadt.muenchen.de/buergerservice/',
            'oeffnungszeiten' => $oeffnung, 'quelle' => 'Demo-Daten', 'last_imported_at' => now(),
        ]);

        $b  = KennzeichenKuerzel::create(['code' => 'B',  'slug' => 'b',  'bedeutung' => 'Berlin']);
        $m  = KennzeichenKuerzel::create(['code' => 'M',  'slug' => 'm',  'bedeutung' => 'München']);
        $la = KennzeichenKuerzel::create(['code' => 'LA', 'slug' => 'la', 'bedeutung' => 'Landshut']);

        $zstBerlin->kennzeichenKuerzel()->attach($b);
        $zstMuenchen->kennzeichenKuerzel()->attach($m);

        $kat = Kategorie::create(['name' => 'Zulassung', 'slug' => 'zulassung']);
        $tag = Tag::create(['name' => 'i-Kfz', 'slug' => 'i-kfz']);

        $artikel = RatgeberArtikel::create([
            'titel' => 'Auto abmelden — online via i-Kfz und vor Ort',
            'slug' => 'auto-abmelden', 'kategorie_id' => $kat->id,
            'intro' => 'So meldest du dein Auto online über i-Kfz oder vor Ort bei der Zulassungsstelle ab.',
            'body' => "## Online abmelden\n\nDie Abmeldung ist über das i-Kfz-Portal deiner Zulassungsbehörde möglich. Seit September 2023 genügen die Sicherheitscodes auf Fahrzeugschein und Kennzeichen-Plaketten.\n\n## Vor Ort abmelden\n\nAlternativ bei der zuständigen Zulassungsstelle — ggf. mit Termin.\n\n*Hinweis: Platzhalter-Demo-Inhalt, finale Redaktion durch Rita (WP-4).*",
            'stand_datum' => '2026-06-22', 'quelle' => 'Demo', 'published_at' => now(),
        ]);
        $artikel->tags()->attach($tag);
    }
}
