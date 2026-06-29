<?php

namespace App\Console\Commands;

use App\Models\Bundesland;
use App\Models\Zulassungsstelle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class EnrichBundesland extends Command
{
    protected $signature = 'enrich:bundesland
        {--all : Auch Stellen mit bereits gesetztem Bundesland überschreiben}
        {--dry : Nur Trefferquote zeigen, nicht schreiben}';

    protected $description = 'Leitet das Bundesland je Zulassungsstelle aus Wikidata (Gemeinde → Bundesland) ab.';

    public function handle(): int
    {
        $query = Zulassungsstelle::query();
        if (! $this->option('all')) {
            $query->whereNull('bundesland_id');
        }
        $stellen = $query->get();

        $orte = $stellen->pluck('ort')->filter()->unique()->values()->all();
        if (empty($orte)) {
            $this->info('Keine Stellen mit Ort zu verarbeiten.');
            return self::SUCCESS;
        }

        $map = $this->fetchOrtToBundesland($orte);
        if ($map === null) {
            $this->error('Wikidata nicht erreichbar.');
            return self::FAILURE;
        }
        $this->info('Orte mit Bundesland-Treffer: '.count($map));

        $set = 0; $miss = 0; $landIds = [];
        foreach ($stellen as $stelle) {
            $ort = $this->normCity($stelle->ort);
            $land = $ort !== '' ? ($map[$ort] ?? null) : null;
            if (! $land) { $miss++; continue; }

            if ($this->option('dry')) { $set++; continue; }

            $landIds[$land] ??= Bundesland::firstOrCreate(
                ['slug' => \App\Support\Slug::de($land)],
                ['name' => $land]
            )->id;
            $stelle->update(['bundesland_id' => $landIds[$land]]);
            $set++;
        }

        $this->info("Fertig. Gesetzt: $set, ohne Treffer: $miss (von ".$stellen->count().").");
        if (! $this->option('dry')) {
            $this->comment('Bundesländer gesamt: '.Bundesland::count());
        }
        return self::SUCCESS;
    }

    /** Die 16 Bundesländer als Wikidata-QID => Name. */
    private const STATES = [
        'Q985'  => 'Baden-Württemberg', 'Q980' => 'Bayern', 'Q64' => 'Berlin',
        'Q1208' => 'Brandenburg', 'Q1209' => 'Bremen', 'Q1055' => 'Hamburg',
        'Q1199' => 'Hessen', 'Q1196' => 'Mecklenburg-Vorpommern', 'Q1197' => 'Niedersachsen',
        'Q1198' => 'Nordrhein-Westfalen', 'Q1200' => 'Rheinland-Pfalz', 'Q1201' => 'Saarland',
        'Q1202' => 'Sachsen', 'Q1206' => 'Sachsen-Anhalt', 'Q1194' => 'Schleswig-Holstein',
        'Q1205' => 'Thüringen',
    ];

    /** Wikidata gezielt für die übergebenen Orte: normCity => Bundesland-Name. */
    private function fetchOrtToBundesland(array $orte): ?array
    {
        $map = [];
        $anyOk = false;
        $landValues = collect(array_keys(self::STATES))->map(fn ($q) => 'wd:'.$q)->implode(' ');

        foreach (array_chunk($orte, 15) as $i => $chunk) {
            $values = collect($chunk)
                ->map(fn ($o) => '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $o).'"@de')
                ->implode(' ');

            // Leicht: Ziel-?land auf die 16 Bundesländer beschränkt, kein Label-Service.
            $sparql = <<<SPARQL
SELECT ?name ?land WHERE {
  VALUES ?name { $values }
  VALUES ?land { $landValues }
  ?ort rdfs:label ?name .
  ?ort wdt:P17 wd:Q183 .
  ?ort wdt:P131/wdt:P131?/wdt:P131? ?land .
}
SPARQL;

            // Retries gegen den zeitweise instabilen WDQS-Endpoint
            $res = null;
            for ($try = 1; $try <= 4; $try++) {
                try {
                    $res = Http::timeout(90)
                        ->withHeaders([
                            'User-Agent' => 'WunschkennzeichenPortal/1.0',
                            'Accept'     => 'application/sparql-results+json',
                        ])
                        ->asForm()
                        ->post('https://query.wikidata.org/sparql', ['query' => $sparql, 'format' => 'json']);
                } catch (\Throwable $e) {
                    $res = null;
                }
                if ($res && $res->successful()) {
                    break;
                }
                if ($try < 4) {
                    sleep(3);
                }
            }
            if (! $res || ! $res->successful()) {
                $this->warn('  Batch '.($i + 1).': fehlgeschlagen (HTTP '.($res ? $res->status() : '–').')');
                continue;
            }
            $anyOk = true;

            foreach ($res->json('results.bindings') ?? [] as $b) {
                $ort = $this->normCity($b['name']['value'] ?? '');
                $uri = $b['land']['value'] ?? '';
                $qid = preg_match('#(Q\d+)$#', $uri, $m) ? $m[1] : null;
                $land = $qid ? (self::STATES[$qid] ?? null) : null;
                if ($ort === '' || ! $land) {
                    continue;
                }
                $map[$ort] ??= $land;
            }
            $this->line('  Batch '.($i + 1).': '.count($map).' kumuliert');
        }

        return $anyOk ? $map : null;
    }

    private function normCity(?string $s): string
    {
        if (! $s) return '';
        $s = Str::lower($s);
        $s = preg_split('/[(,]/', $s)[0];
        $s = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $s);
        $s = preg_replace('/\b(landkreis|kreis|staedteregion|stadt|kreisfreie|hansestadt|landeshauptstadt)\b/u', '', $s);
        $s = preg_replace('/[^a-z0-9]+/u', '', $s);
        return trim($s);
    }
}
