<?php

namespace App\Console\Commands;

use App\Models\KennzeichenKuerzel;
use App\Models\Zulassungsstelle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LinkKuerzelStellen extends Command
{
    protected $signature = 'link:kuerzel-stellen
        {--fresh : Bestehende Zuordnungen vorher entfernen}
        {--local : Nur lokale Heuristik (ohne Wikidata-Gemeindeliste)}';

    protected $description = 'Verknüpft Kennzeichen-Kürzel mit Zulassungsstellen über die Wikidata-Gemeindeliste (Ort der Stelle ↔ Gemeinden eines Kürzels).';

    public function handle(): int
    {
        if (Zulassungsstelle::count() === 0) {
            $this->warn('Keine Zulassungsstellen — zuerst `php artisan import:stellen` ausführen.');
            return self::SUCCESS;
        }

        $codeToId = KennzeichenKuerzel::pluck('id', 'code');           // 'B' => 1
        $municToCodes = $this->option('local') ? null : $this->fetchMunicipalityMap();

        if ($municToCodes === null && ! $this->option('local')) {
            $this->warn('Wikidata nicht erreichbar — fallback auf lokale Heuristik (bedeutung).');
        }

        $links = 0; $stellenMitLink = 0;
        foreach (Zulassungsstelle::all() as $stelle) {
            if ($this->option('fresh')) {
                $stelle->kennzeichenKuerzel()->detach();
            }

            $ort = $this->normCity($stelle->ort);
            $matchIds = [];

            if ($municToCodes !== null && $ort !== '' && isset($municToCodes[$ort])) {
                foreach ($municToCodes[$ort] as $code) {
                    if (isset($codeToId[$code])) {
                        $matchIds[$codeToId[$code]] = true;
                    }
                }
            }

            // Fallback / Ergänzung: exakte Gleichheit gegen bedeutung
            if (empty($matchIds)) {
                foreach (KennzeichenKuerzel::whereNotNull('bedeutung')->get() as $k) {
                    if ($ort !== '' && $ort === $this->normCity($k->bedeutung)) {
                        $matchIds[$k->id] = true;
                    }
                }
            }

            if ($matchIds) {
                $stelle->kennzeichenKuerzel()->syncWithoutDetaching(array_keys($matchIds));
                $links += count($matchIds);
                $stellenMitLink++;
            }
        }

        $this->info("Fertig. Verknüpfungen: $links bei $stellenMitLink Stellen (von ".Zulassungsstelle::count().").");
        $this->comment('Stichprobenartig im Admin prüfen.');
        return self::SUCCESS;
    }

    /** Holt aus Wikidata: normalisierter Gemeindename => Liste der Kürzel-Codes. */
    private function fetchMunicipalityMap(): ?array
    {
        $sparql = <<<'SPARQL'
SELECT ?code ?itemLabel WHERE {
  ?item p:P395 ?stmt.
  ?stmt ps:P395 ?code.
  ?item wdt:P17 wd:Q183.
  FILTER NOT EXISTS { ?stmt pq:P582 ?end. }
  SERVICE wikibase:label { bd:serviceParam wikibase:language "de,en". }
}
SPARQL;

        $this->info('Lade Wikidata-Gemeindeliste …');
        try {
            $res = Http::timeout(60)
                ->withHeaders([
                    'User-Agent' => 'WunschkennzeichenPortal/1.0',
                    'Accept'     => 'application/sparql-results+json',
                ])
                ->get('https://query.wikidata.org/sparql', ['query' => $sparql, 'format' => 'json']);
        } catch (\Throwable $e) {
            return null;
        }
        if (! $res->successful()) {
            return null;
        }

        $map = [];
        foreach ($res->json('results.bindings') ?? [] as $b) {
            $code = trim($b['code']['value'] ?? '');
            $label = trim($b['itemLabel']['value'] ?? '');
            if ($code === '' || $label === '' || preg_match('/^Q\d+$/', $label)) {
                continue;
            }
            $norm = $this->normCity($label);
            if ($norm === '') {
                continue;
            }
            $map[$norm] ??= [];
            $map[$norm][$code] = true;
        }
        // Sets zu Listen
        $map = array_map(fn ($codes) => array_keys($codes), $map);
        $this->info('Gemeinden im Index: '.count($map));
        return $map;
    }

    /** Normalisiert einen Ortsnamen: Teil vor "(" / "," , Umlaute, nur a-z0-9. */
    private function normCity(?string $s): string
    {
        if (! $s) return '';
        $s = Str::lower($s);
        $s = preg_split('/[(,]/', $s)[0];                       // "Halle (Saale)" -> "halle"
        $s = str_replace(['ä','ö','ü','ß'], ['ae','oe','ue','ss'], $s);
        $s = preg_replace('/\b(landkreis|kreis|staedteregion|stadt|kreisfreie|hansestadt|landeshauptstadt)\b/u', '', $s);
        $s = preg_replace('/[^a-z0-9]+/u', '', $s);
        return trim($s);
    }
}
