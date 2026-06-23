<?php

namespace App\Console\Commands;

use App\Models\Bundesland;
use App\Models\Gemeinde;
use App\Models\KennzeichenKuerzel;
use App\Models\Kreis;
use App\Models\Zulassungsstelle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImportAgs extends Command
{
    protected $signature = 'import:ags';

    protected $description = 'Baut die AGS-Struktur (Kreise/Gemeinden) aus Wikidata (P439) und verknüpft Stellen + Kürzel.';

    /** AGS-Präfix (2-stellig) => Bundesland-Name. */
    private const LAND = [
        '01' => 'Schleswig-Holstein', '02' => 'Hamburg', '03' => 'Niedersachsen',
        '04' => 'Bremen', '05' => 'Nordrhein-Westfalen', '06' => 'Hessen',
        '07' => 'Rheinland-Pfalz', '08' => 'Baden-Württemberg', '09' => 'Bayern',
        '10' => 'Saarland', '11' => 'Berlin', '12' => 'Brandenburg',
        '13' => 'Mecklenburg-Vorpommern', '14' => 'Sachsen', '15' => 'Sachsen-Anhalt',
        '16' => 'Thüringen',
    ];

    public function handle(): int
    {
        $rows = $this->fetchAgs();
        if ($rows === null) {
            $this->error('Wikidata nicht erreichbar.');
            return self::FAILURE;
        }
        $this->info('AGS-Datensätze: '.count($rows));

        // Bundesland-Name => id
        $landId = Bundesland::pluck('id', 'name');

        // Buckets
        $kreisNames = [];   // ags5 => name
        $gemeinden  = [];   // ags8 => name
        foreach ($rows as [$ags, $name]) {
            if (! ctype_digit($ags)) continue;
            if (strlen($ags) === 5) {
                $kreisNames[$ags] ??= $name;
            } elseif (strlen($ags) === 8) {
                $gemeinden[$ags] ??= $name;
            }
        }
        $this->info('Kreise (5-stellig): '.count($kreisNames).' · Gemeinden (8-stellig): '.count($gemeinden));

        // --- Kreise: alle 5-stelligen Präfixe aus Gemeinden ∪ Kreis-Entitäten ---
        $kreisAgs = collect(array_keys($gemeinden))->map(fn ($a) => substr($a, 0, 5))
            ->merge(array_keys($kreisNames))->unique();

        $kreisRows = $kreisAgs->map(fn ($a) => [
            'ags'           => $a,
            'name'          => $kreisNames[$a] ?? null,
            'bundesland_id' => $landId[self::LAND[substr($a, 0, 2)] ?? null] ?? null,
            'created_at'    => now(), 'updated_at' => now(),
        ])->all();
        foreach (array_chunk($kreisRows, 500) as $chunk) {
            Kreis::upsert($chunk, ['ags'], ['name', 'bundesland_id', 'updated_at']);
        }
        $kreisIdByAgs = Kreis::pluck('id', 'ags');

        // --- Gemeinden ---
        $gemRows = [];
        foreach ($gemeinden as $ags => $name) {
            $gemRows[] = [
                'ags'           => $ags,
                'name'          => $name,
                'kreis_id'      => $kreisIdByAgs[substr($ags, 0, 5)] ?? null,
                'bundesland_id' => $landId[self::LAND[substr($ags, 0, 2)] ?? null] ?? null,
                'created_at'    => now(), 'updated_at' => now(),
            ];
        }
        foreach (array_chunk($gemRows, 500) as $chunk) {
            Gemeinde::upsert($chunk, ['ags'], ['name', 'kreis_id', 'bundesland_id', 'updated_at']);
        }
        $this->info('Kreise gesamt: '.Kreis::count().' · Gemeinden gesamt: '.Gemeinde::count());

        $this->linkStellen();
        $this->linkKuerzel();

        return self::SUCCESS;
    }

    /** Zulassungsstellen über Ort → Gemeinde (+ Kreis) verknüpfen. */
    private function linkStellen(): void
    {
        // Gemeinde-Index: normName + (bundesland) => [gemeinde_id, kreis_id]
        $byLandName = [];   // "bundesland_id|norm" => [g,k]
        $byName     = [];   // "norm" => [g,k]
        foreach (Gemeinde::select('id', 'name', 'kreis_id', 'bundesland_id')->cursor() as $g) {
            $n = $this->norm($g->name);
            if ($n === '') continue;
            $byName[$n] ??= [$g->id, $g->kreis_id];
            if ($g->bundesland_id) {
                $byLandName[$g->bundesland_id.'|'.$n] ??= [$g->id, $g->kreis_id];
            }
        }

        $set = 0;
        foreach (Zulassungsstelle::whereNotNull('ort')->get() as $s) {
            $n = $this->norm($s->ort);
            if ($n === '') continue;
            $hit = ($s->bundesland_id ? ($byLandName[$s->bundesland_id.'|'.$n] ?? null) : null)
                 ?? ($byName[$n] ?? null);
            if ($hit) {
                $s->update(['gemeinde_id' => $hit[0], 'kreis_id' => $hit[1]]);
                $set++;
            }
        }
        $this->info("Zulassungsstellen mit Gemeinde/Kreis verknüpft: $set von ".Zulassungsstelle::count());
    }

    /** Kürzel → Kreise ableiten über die bereits verknüpften Zulassungsstellen. */
    private function linkKuerzel(): void
    {
        $links = 0;
        foreach (KennzeichenKuerzel::with('zulassungsstellen:id,kreis_id')->get() as $k) {
            $kreisIds = $k->zulassungsstellen->pluck('kreis_id')->filter()->unique()->values();
            if ($kreisIds->isNotEmpty()) {
                $k->kreise()->syncWithoutDetaching($kreisIds->all());
                $links += $kreisIds->count();
            }
        }
        $this->info("Kürzel↔Kreis-Verknüpfungen: $links");
    }

    private function fetchAgs(): ?array
    {
        $sparql = <<<'SPARQL'
SELECT ?ags ?itemLabel WHERE {
  ?item wdt:P439 ?ags.
  ?item wdt:P17 wd:Q183.
  SERVICE wikibase:label { bd:serviceParam wikibase:language "de". }
}
SPARQL;

        $this->info('Lade AGS aus Wikidata (P439) …');
        try {
            $res = Http::timeout(120)
                ->withHeaders(['User-Agent' => 'WunschkennzeichenPortal/1.0', 'Accept' => 'application/sparql-results+json'])
                ->get('https://query.wikidata.org/sparql', ['query' => $sparql, 'format' => 'json']);
        } catch (\Throwable $e) {
            return null;
        }
        if (! $res->successful()) {
            return null;
        }

        $out = [];
        foreach ($res->json('results.bindings') ?? [] as $b) {
            $ags  = trim($b['ags']['value'] ?? '');
            $name = trim($b['itemLabel']['value'] ?? '');
            if ($ags !== '' && $name !== '' && ! preg_match('/^Q\d+$/', $name)) {
                $out[] = [$ags, $name];
            }
        }
        return $out;
    }

    private function norm(?string $s): string
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
