<?php

namespace App\Console\Commands;

use App\Models\Gemeinde;
use App\Support\Slug;
use Illuminate\Console\Command;

class GenerateGemeindeSlugs extends Command
{
    protected $signature = 'gemeinden:slugs';

    protected $description = 'Erzeugt eindeutige SEO-Slugs für alle Gemeinden (Kollisionen via Kreis, dann AGS).';

    public function handle(): int
    {
        $used = [];
        $count = 0;

        // chunkById ordnet nach id – kein zusätzliches orderBy, sonst bricht der Cursor.
        Gemeinde::with('kreis:id,name')->chunkById(2000, function ($gemeinden) use (&$used, &$count) {
            foreach ($gemeinden as $g) {
                $base = Slug::de($g->name);

                $slug = $base;
                if (isset($used[$slug])) {
                    // 1. Disambiguierung: Kreisname ohne Verwaltungs-Präfixe
                    $kreis = Slug::de($this->kreisKurz($g->kreis?->name));
                    if ($kreis !== '') {
                        $slug = $base.'-'.$kreis;
                    }
                }
                if (isset($used[$slug]) || $slug === '') {
                    // 2. Disambiguierung: AGS (immer eindeutig)
                    $slug = $base.'-'.$g->ags;
                }

                $used[$slug] = true;
                $g->slug = $slug;
                $g->saveQuietly();
                $count++;
            }
        });

        $this->info("Slugs erzeugt: {$count}");

        return self::SUCCESS;
    }

    /** Entfernt Verwaltungs-Präfixe/-Suffixe aus Kreisnamen für kompaktere Slugs. */
    private function kreisKurz(?string $name): string
    {
        return trim(preg_replace('/\b(Landkreis|Kreis|Stadtkreis|Region|Regionalverband|kreisfreie Stadt)\b/iu', '', (string) $name));
    }
}
