<?php

namespace App\Console\Commands;

use App\Models\EnrichmentIdea;
use App\Support\IdeenDedup;
use Illuminate\Console\Command;

class DedupEnrichmentIdeas extends Command
{
    protected $signature = 'enrichment:dedup
        {--aehnlich= : Jaccard-Schwelle (Standard 0.6)}
        {--anwenden : Doppler tatsächlich auflösen (sonst nur Report)}';

    protected $description = 'Findet inhaltliche Doppler im Ideen-Bestand und löst sie auf (behält je Cluster die beste, markiert Reste als abgelehnt).';

    /** Status-Rang: höher = wichtiger, wird im Cluster behalten. */
    private const RANG = ['umgesetzt' => 5, 'umsetzen' => 4, 'geprueft' => 3, 'neu' => 2, 'abgelehnt' => 1];

    public function handle(): int
    {
        $schwelle = $this->option('aehnlich') !== null
            ? (float) $this->option('aehnlich')
            : IdeenDedup::SCHWELLE;
        $anwenden = (bool) $this->option('anwenden');

        // Bereits abgelehnte zählen nicht als Vergleichsbasis (sind raus aus der Kuratierung).
        $ideen = EnrichmentIdea::query()
            ->where('status', '!=', 'abgelehnt')
            ->orderBy('id')
            ->get(['id', 'titel', 'status', 'score', 'quelle_lauf']);

        $items = $ideen->map(fn (EnrichmentIdea $i): array => [
            'id' => $i->id,
            'tokens' => IdeenDedup::tokens((string) $i->titel),
        ])->values()->all();

        // Union-Find über alle Paare >= Schwelle → Cluster bilden.
        $n = count($items);
        $parent = range(0, max(0, $n - 1));
        $find = function (int $x) use (&$parent, &$find): int {
            while ($parent[$x] !== $x) {
                $parent[$x] = $parent[$parent[$x]];
                $x = $parent[$x];
            }

            return $x;
        };

        $paare = 0;
        for ($a = 0; $a < $n; $a++) {
            for ($b = $a + 1; $b < $n; $b++) {
                if (IdeenDedup::jaccard($items[$a]['tokens'], $items[$b]['tokens']) >= $schwelle) {
                    $parent[$find($b)] = $find($a);
                    $paare++;
                }
            }
        }

        // Cluster sammeln (nur die mit > 1 Mitglied).
        $cluster = [];
        for ($a = 0; $a < $n; $a++) {
            $cluster[$find($a)][] = $ideen[$a];
        }
        $cluster = array_values(array_filter($cluster, fn (array $c): bool => count($c) > 1));

        if ($cluster === []) {
            $this->info("Keine Doppler ab Jaccard {$schwelle} gefunden.");

            return self::SUCCESS;
        }

        $this->info(sprintf('%d Doppler-Cluster gefunden (Schwelle %.2f):', count($cluster), $schwelle));
        $abgelehnt = 0;

        foreach ($cluster as $c) {
            // Behalten: höchster Status-Rang, dann höchster Score, dann niedrigste id (Original).
            usort($c, function ($x, $y): int {
                return [self::RANG[$y->status] ?? 0, (float) $y->score, -$y->id]
                   <=> [self::RANG[$x->status] ?? 0, (float) $x->score, -$x->id];
            });
            $behalten = array_shift($c);

            $this->newLine();
            $this->line(sprintf('  ✔ BEHALTEN  #%d [%s/%s] %s', $behalten->id, $behalten->status, $behalten->quelle_lauf, $behalten->titel));
            foreach ($c as $weg) {
                $this->line(sprintf('  ✗ Doppler   #%d [%s/%s] %s', $weg->id, $weg->status, $weg->quelle_lauf, $weg->titel));
                if ($anwenden) {
                    $weg->status = 'abgelehnt';
                    $weg->notiz = trim(($weg->notiz ? $weg->notiz."\n" : '')
                        .'Quasi-Doppler zu #'.$behalten->id.' – automatisch dedupliziert ('.now()->toDateString().').');
                    $weg->save();
                    $abgelehnt++;
                }
            }
        }

        $this->newLine();
        if ($anwenden) {
            $this->info("Aufgelöst: {$abgelehnt} Ideen als 'abgelehnt' markiert (Verweis in Notiz).");
        } else {
            $this->warn('Dry-Run – nichts geändert. Zum Auflösen: php artisan enrichment:dedup --anwenden');
        }

        return self::SUCCESS;
    }
}
