<?php

namespace App\Console\Commands;

use App\Models\KennzeichenKuerzel;
use App\Models\OrtAlias;
use Illuminate\Console\Command;

/**
 * Restliche alte /wunschkennzeichen/{ortsteil}/-Slugs (Ortsteile ohne eigene Gemeinde)
 * verlustfrei umleiten: Kürzel aus dem alten Seitentitel lesen → 301 auf die Kürzel-Seite.
 *
 * Eingabe: TSV „slug<TAB>alter Titel" (ein Eintrag je Zeile).
 *   php artisan oz:ort-aliasse-tail /tmp/ortsteil_titles.tsv
 */
class OrtAliasseTail extends Command
{
    protected $signature = 'oz:ort-aliasse-tail {tsv : TSV slug<TAB>Titel}';

    protected $description = 'Mappt Ortsteil-Slugs per Kürzel im alten Titel auf die Kürzel-Seite (301)';

    public function handle(): int
    {
        $tsv = $this->argument('tsv');
        if (! is_file($tsv)) {
            $this->error("Datei nicht gefunden: $tsv");
            return self::FAILURE;
        }

        // Code => slug (exakte Schreibweise inkl. Umlaut)
        $codes = KennzeichenKuerzel::pluck('slug', 'code')->all();

        $ok = 0; $offen = [];
        foreach (file($tsv) as $zeile) {
            $zeile = rtrim($zeile, "\r\n");
            if ($zeile === '') continue;
            [$slug, $titel] = array_pad(explode("\t", $zeile, 2), 2, '');
            $slug = trim($slug);
            if ($slug === '' || OrtAlias::where('slug', $slug)->exists()) continue;

            // Die Kürzel-Liste steht direkt vor „amtlich" (z.B. „… KY, NP, OPR oder WK amtlich …").
            // Anker verhindert Falschtreffer wie „W" aus „Wunschkennzeichen" oder Namens-Initialen.
            $ziel = null;
            if (preg_match('/((?:[A-ZÄÖÜ]{1,3})(?:(?:,\s*|\s+oder\s+)[A-ZÄÖÜ]{1,3})*)\s+amtlich/u', $titel, $mm)) {
                foreach (preg_split('/,\s*|\s+oder\s+/u', $mm[1]) as $tok) {
                    $tok = trim($tok);
                    if ($tok !== '' && isset($codes[$tok])) { $ziel = $codes[$tok]; break; }
                }
            }
            if (! $ziel) { $offen[] = $slug; continue; }

            OrtAlias::updateOrCreate(
                ['slug' => $slug],
                ['ziel' => '/kennzeichen/'.$ziel, 'quelle' => 'kuerzel', 'geprueft' => true]
            );
            $ok++;
        }

        $this->info("Per Kürzel umgeleitet: $ok");
        $this->warn("Weiterhin offen: ".count($offen));
        if ($offen) {
            file_put_contents(storage_path('app/ort-aliasse-offen2.txt'), implode("\n", $offen));
            $this->line('  → storage/app/ort-aliasse-offen2.txt');
        }
        $this->info("Aliasse gesamt: ".OrtAlias::count());

        return self::SUCCESS;
    }
}
