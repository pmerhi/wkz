<?php

namespace App\Console\Commands;

use App\Models\Zulassungsstelle;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Entfernt OSM/Konsolidat-Dubletten: eine OSM-Stelle, die ein eindeutiges
 * Konsolidat-Pendant (gleiche PLZ + Straßenname) hat, wird dort eingemischt
 * (Geo etc.) und entfernt. Genuine Filialen (mehrere Pendants) bleiben unberührt.
 */
class DedupeStellen extends Command
{
    protected $signature = 'dedupe:stellen {--dry}';

    protected $description = 'Führt OSM/Konsolidat-Dubletten der eigenen Stellen zusammen.';

    public function handle(): int
    {
        $dry = $this->option('dry');

        // Konsolidat-Stellen nach (PLZ | Straßenname ohne Hausnummer) indexieren
        $index = [];
        foreach (Zulassungsstelle::where('quelle', 'like', '%freigegeben%')->get() as $s) {
            if (! $s->plz) continue;
            $index[$s->plz.'|'.$this->streetName($s->strasse)][] = $s;
        }

        $merged = 0; $skipAmbig = 0; $keptUnique = 0;
        foreach (Zulassungsstelle::where('quelle', 'like', 'OpenStreetMap%')->get() as $osm) {
            if (! $osm->plz) { $keptUnique++; continue; }
            $cands = $index[$osm->plz.'|'.$this->streetName($osm->strasse)] ?? [];
            if (count($cands) === 0) { $keptUnique++; continue; }
            if (count($cands) > 1) { $skipAmbig++; continue; }

            $target = $cands[0];
            // Geo + fehlende Felder aus der OSM-Stelle ins Konsolidat übernehmen
            $u = [];
            foreach (['lat', 'lng', 'termin_url', 'website', 'telefon', 'email'] as $f) {
                if (empty($target->$f) && ! empty($osm->$f)) $u[$f] = $osm->$f;
            }
            if (! is_array($target->oeffnungszeiten) && is_array($osm->oeffnungszeiten)) {
                $u['oeffnungszeiten'] = $osm->oeffnungszeiten;
            }
            if (! $dry) {
                if ($u) $target->update($u);
                $osm->delete();   // Pivot (Kürzel) per cascade entfernt
            }
            $merged++;
        }

        $this->info(($dry ? '[DRY] ' : '')."Zusammengeführt: $merged · eindeutig behalten: $keptUnique · mehrdeutig übersprungen: $skipAmbig · Stellen gesamt: ".Zulassungsstelle::count());
        return self::SUCCESS;
    }

    /** Straßenname ohne Hausnummer, normalisiert. */
    private function streetName(?string $s): string
    {
        $s = Str::lower((string) $s);
        $s = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $s);
        $s = preg_replace('/stra(ss|ß)e|str\.?/u', 'str', $s);
        $s = preg_replace('/\d.*$/u', '', $s);
        return preg_replace('/[^a-z0-9]+/u', '', $s);
    }
}
