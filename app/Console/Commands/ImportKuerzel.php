<?php

namespace App\Console\Commands;

use App\Models\KennzeichenKuerzel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImportKuerzel extends Command
{
    protected $signature = 'import:kuerzel {--dry : Nur anzeigen, nicht schreiben}';

    protected $description = 'Importiert deutsche Kfz-Unterscheidungszeichen aus Wikidata (P395, CC0).';

    public function handle(): int
    {
        // Aktuell gültige Unterscheidungszeichen in Deutschland (P17 = Deutschland),
        // ohne historische/ausgelaufene (P582 = Enddatum gesetzt => ausgeschlossen).
        $sparql = <<<'SPARQL'
SELECT ?code ?itemLabel WHERE {
  ?item p:P395 ?stmt.
  ?stmt ps:P395 ?code.
  ?item wdt:P17 wd:Q183.
  FILTER NOT EXISTS { ?stmt pq:P582 ?end. }
  SERVICE wikibase:label { bd:serviceParam wikibase:language "de,en". }
}
ORDER BY ?code
SPARQL;

        $this->info('Frage Wikidata ab …');
        $response = Http::timeout(60)
            ->withHeaders([
                'User-Agent' => 'WunschkennzeichenPortal/1.0 (Datenimport; kontakt@example.de)',
                'Accept'     => 'application/sparql-results+json',
            ])
            ->get('https://query.wikidata.org/sparql', [
                'query'  => $sparql,
                'format' => 'json',
            ]);

        if (! $response->successful()) {
            $this->error('Wikidata-Abfrage fehlgeschlagen: HTTP '.$response->status());
            return self::FAILURE;
        }

        $bindings = $response->json('results.bindings') ?? [];
        $this->info('Treffer: '.count($bindings));

        // Nach Code aggregieren (Bedeutungen sammeln, Duplikate vermeiden)
        $rows = [];
        foreach ($bindings as $b) {
            $code = trim($b['code']['value'] ?? '');
            if ($code === '' || ! preg_match('/^[A-ZÄÖÜ]{1,3}$/u', $code)) {
                continue;
            }
            $ort = trim($b['itemLabel']['value'] ?? '');
            // Wikidata-Q-IDs ohne Label überspringen
            if ($ort !== '' && preg_match('/^Q\d+$/', $ort)) {
                $ort = '';
            }
            $rows[$code] ??= [];
            if ($ort !== '' && ! in_array($ort, $rows[$code], true)) {
                $rows[$code][] = $ort;
            }
        }
        ksort($rows);
        $this->info('Eindeutige Kürzel: '.count($rows));

        if ($this->option('dry')) {
            foreach (array_slice($rows, 0, 15, true) as $code => $orte) {
                $this->line(sprintf('  %-3s  %s', $code, $this->pickPrimary($orte)));
            }
            $this->comment('Dry-Run — nichts geschrieben.');
            return self::SUCCESS;
        }

        $created = 0; $updated = 0;
        foreach ($rows as $code => $orte) {
            $bedeutung = $this->pickPrimary($orte) ?: null;
            $existing = KennzeichenKuerzel::where('code', $code)->first();
            if ($existing) {
                $existing->update(['bedeutung' => $bedeutung]);
                $updated++;
            } else {
                KennzeichenKuerzel::create([
                    'code'      => $code,
                    'slug'      => $this->uniqueSlug($code),
                    'bedeutung' => $bedeutung,
                ]);
                $created++;
            }
        }

        $this->info("Fertig. Neu: $created, aktualisiert: $updated, gesamt: ".KennzeichenKuerzel::count());
        return self::SUCCESS;
    }

    /** Wählt aus vielen Gemeindenamen den repräsentativen Bezirk (Landkreis/Stadt). */
    private function pickPrimary(array $orte): string
    {
        if (empty($orte)) {
            return '';
        }
        $patterns = ['/^Städteregion /u', '/^Landkreis /u', '/^Kreis /u', '/kreis$/u'];
        foreach ($patterns as $p) {
            foreach ($orte as $o) {
                if (preg_match($p, $o)) {
                    return $o;
                }
            }
        }
        return $orte[0];
    }

    private function uniqueSlug(string $code): string
    {
        $base = \App\Support\Slug::de($code) ?: strtolower($code);
        $slug = $base; $i = 2;
        while (KennzeichenKuerzel::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }
        return $slug;
    }
}
