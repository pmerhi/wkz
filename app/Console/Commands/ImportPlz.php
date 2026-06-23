<?php

namespace App\Console\Commands;

use App\Models\Gemeinde;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportPlz extends Command
{
    protected $signature = 'import:plz';

    protected $description = 'Importiert PLZ↔AGS aus Wikidata (P281+P439) für exaktes PLZ→Gemeinde-Matching.';

    public function handle(): int
    {
        $sparql = <<<'SPARQL'
SELECT ?ags ?plz WHERE {
  ?i wdt:P439 ?ags.
  ?i wdt:P281 ?plz.
  ?i wdt:P17 wd:Q183.
}
SPARQL;

        $this->info('Lade PLZ↔AGS aus Wikidata …');
        try {
            $res = Http::timeout(120)
                ->withHeaders(['User-Agent' => 'WunschkennzeichenPortal/1.0', 'Accept' => 'application/sparql-results+json'])
                ->get('https://query.wikidata.org/sparql', ['query' => $sparql, 'format' => 'json']);
        } catch (\Throwable $e) {
            $this->error('Wikidata nicht erreichbar.'); return self::FAILURE;
        }
        if (! $res->successful()) { $this->error('HTTP '.$res->status()); return self::FAILURE; }

        // Gemeinde-Index nach AGS
        $gByAgs = Gemeinde::select('id', 'ags', 'kreis_id')->get()->keyBy('ags');

        $rows = [];
        foreach ($res->json('results.bindings') ?? [] as $b) {
            $ags = trim($b['ags']['value'] ?? '');
            $plz = trim($b['plz']['value'] ?? '');
            if (strlen($ags) !== 8 || ! preg_match('/^\d{5}$/', $plz)) continue;
            $g = $gByAgs[$ags] ?? null;
            if (! $g) continue;
            $rows[$plz.'|'.$g->id] = ['plz' => $plz, 'gemeinde_id' => $g->id, 'kreis_id' => $g->kreis_id];
        }
        $rows = array_values($rows);
        $this->info('PLZ↔Gemeinde-Paare: '.count($rows));

        DB::table('plz_gemeinde')->truncate();
        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('plz_gemeinde')->insert($chunk);
        }
        $this->info('Gespeichert: '.DB::table('plz_gemeinde')->count());
        return self::SUCCESS;
    }
}
