<?php

namespace App\Console\Commands;

use App\Models\Gemeinde;
use App\Models\Kreis;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EnrichKreisNamen extends Command
{
    protected $signature = 'kreise:namen';

    protected $description = 'Leitet fehlende Kreisnamen aus Gemeinde (kreisfreie Städte) und Kürzel-Bedeutung (Landkreise) ab.';

    public function handle(): int
    {
        $gcount = DB::table('gemeinden')->select('kreis_id', DB::raw('count(*) c'))
            ->groupBy('kreis_id')->pluck('c', 'kreis_id');

        $gesetzt = 0;
        $leer = 0;

        Kreis::with('kennzeichenKuerzel')->chunkById(200, function ($kreise) use (&$gesetzt, &$leer, $gcount) {
            foreach ($kreise as $kr) {
                $anzahl = (int) ($gcount[$kr->id] ?? 0);

                $name = $anzahl === 1
                    ? Gemeinde::where('kreis_id', $kr->id)->value('name')   // kreisfreie Stadt
                    : $this->landkreisName($kr);                            // Landkreis aus Kürzel-Bedeutung

                if ($name) {
                    $kr->name = $name;
                    $kr->saveQuietly();
                    $gesetzt++;
                } else {
                    $leer++;
                }
            }
        });

        $this->info("Kreisnamen gesetzt: {$gesetzt} | bewusst leer (kein verlässlicher Beleg): {$leer}");

        return self::SUCCESS;
    }

    /** Leitet den Landkreis-Namen aus den Kürzel-Bedeutungen ab (nur belastbare „…kreis/Region/Verband"-Teile). */
    private function landkreisName(Kreis $kr): ?string
    {
        $kandidaten = [];
        foreach ($kr->kennzeichenKuerzel as $k) {
            if (! $k->bedeutung) {
                continue;
            }
            foreach (explode(',', $k->bedeutung) as $part) {
                $part = trim($part);
                if (preg_match('/(kreis|Region|Verband|Städteregion)/iu', $part) && ! preg_match('/^Stadt /u', $part)) {
                    $kandidaten[] = $this->saeubern($part);
                }
            }
        }
        if (! $kandidaten) {
            return null;
        }

        $haeufig = array_count_values(array_filter($kandidaten));
        arsort($haeufig);

        return array_key_first($haeufig) ?: null;
    }

    /** Entfernt Qualifizierer wie „… ohne die Stadt X" und Klammerzusätze. */
    private function saeubern(string $name): string
    {
        $name = preg_replace('/\s+ohne\s+die\s+Stadt.*$/iu', '', $name);
        $name = preg_replace('/\s*\([^)]*\)\s*$/u', '', $name);

        return trim($name);
    }
}
