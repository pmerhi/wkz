<?php

namespace App\Console\Commands;

use App\Models\Zulassungsstelle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Ergänzt fehlende Koordinaten (lat/lng) der Zulassungsstellen per Nominatim
 * (OpenStreetMap). Richtlinienkonform: max. 1 Anfrage/Sekunde, eigener
 * User-Agent mit Kontakt, strukturierte Suche (Straße/PLZ/Ort, Deutschland).
 */
class StellenGeocoden extends Command
{
    protected $signature = 'stellen:geocoden
        {--limit=0 : maximal so viele (0 = alle ohne Koordinaten)}
        {--sleep=1100 : Pause zwischen Anfragen in ms (Policy: >=1000)}
        {--dry : nur anzeigen, nichts speichern}';

    protected $description = 'Geocodiert Zulassungsstellen ohne Koordinaten via Nominatim (OSM).';

    public function handle(): int
    {
        $dry = $this->option('dry');
        $sleep = max(1000, (int) $this->option('sleep')) * 1000; // µs

        $query = Zulassungsstelle::whereNull('parent_id')
            ->whereNull('lat')
            ->whereNotNull('plz')->whereNotNull('ort');
        if (($limit = (int) $this->option('limit')) > 0) {
            $query->limit($limit);
        }

        $ok = 0; $grob = 0; $fehlschlag = 0;

        foreach ($query->cursor() as $s) {
            // 1. Versuch: präzise mit Straße. 2. Versuch: nur PLZ+Ort (grob).
            $treffer = $this->suche(['street' => $s->strasse, 'postalcode' => $s->plz, 'city' => $s->ort]);
            $praezise = (bool) $treffer;
            if (! $treffer) {
                $treffer = $this->suche(['postalcode' => $s->plz, 'city' => $s->ort]);
            }

            if (! $treffer) {
                $fehlschlag++;
                $this->warn("  ✗ {$s->name} ({$s->plz} {$s->ort}): kein Treffer");
                usleep($sleep);
                continue;
            }

            [$lat, $lng] = $treffer;
            if ($dry) {
                $this->line(sprintf('  %-40s → %s, %s %s', mb_substr($s->name, 0, 40), $praezise ? 'präzise' : 'grob', $lat, $lng));
            } else {
                $s->lat = $lat;
                $s->lng = $lng;
                $s->save();
            }
            $praezise ? $ok++ : $grob++;
            usleep($sleep);
        }

        $this->info(($dry ? '[DRY] ' : '')."Geocodiert präzise: $ok · grob (nur PLZ/Ort): $grob · ohne Treffer: $fehlschlag");
        return self::SUCCESS;
    }

    /** Strukturierte Nominatim-Suche; gibt [lat, lng] in DE-Bounds zurück oder null. */
    private function suche(array $params): ?array
    {
        $params = array_filter($params) + [
            'country' => 'Deutschland',
            'format'  => 'jsonv2',
            'limit'   => 1,
        ];

        try {
            $res = Http::withHeaders([
                'User-Agent' => 'WunschkennzeichenPortal-Geocoder/1.0 (patrick@merhi.de)',
            ])->timeout(20)->get('https://nominatim.openstreetmap.org/search', $params);

            if (! $res->successful()) {
                return null;
            }
            $hit = $res->json(0);
            if (! $hit || ! isset($hit['lat'], $hit['lon'])) {
                return null;
            }
            $lat = (float) $hit['lat'];
            $lng = (float) $hit['lon'];
            // Plausibilität: innerhalb Deutschlands.
            if ($lat < 47 || $lat > 55.1 || $lng < 5.5 || $lng > 15.5) {
                return null;
            }

            return [round($lat, 7), round($lng, 7)];
        } catch (\Throwable $e) {
            return null;
        }
    }
}
