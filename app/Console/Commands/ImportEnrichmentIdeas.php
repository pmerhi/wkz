<?php

namespace App\Console\Commands;

use App\Models\EnrichmentIdea;
use App\Support\IdeenDedup;
use Illuminate\Console\Command;

class ImportEnrichmentIdeas extends Command
{
    protected $signature = 'enrichment:import
        {--path= : JSON-Datei mit Ideen}
        {--lauf= : Kennung des Recherche-Laufs}
        {--aehnlich= : Jaccard-Schwelle für Ähnlichkeits-Dedup (0 = aus, Standard 0.6)}';

    protected $description = 'Schreibt neue Recherche-Ideen ins Dashboard (Dedup per Fingerprint + Ähnlichkeit, vorhandene bleiben unangetastet).';

    public function handle(): int
    {
        $path = $this->option('path');
        if (! $path || ! is_file($path)) {
            $this->error('Datei nicht gefunden: '.$path);

            return self::FAILURE;
        }

        $ideen = json_decode((string) file_get_contents($path), true);
        if (! is_array($ideen)) {
            $this->error('Ungültiges JSON (erwartet: Array von Ideen).');

            return self::FAILURE;
        }

        $lauf = $this->option('lauf') ?: 'lauf-'.now()->format('Y-m-d');
        $schwelle = $this->option('aehnlich') !== null
            ? (float) $this->option('aehnlich')
            : IdeenDedup::SCHWELLE;

        // Bestand einmal laden (id + Titel + normalisierte Tokens) für Ähnlichkeits-Vergleich.
        // Akzeptierte Ideen dieses Laufs werden live ergänzt → fängt auch Intra-Batch-Doppler.
        $bestand = EnrichmentIdea::query()
            ->get(['id', 'titel'])
            ->map(fn (EnrichmentIdea $i): array => [
                'id' => $i->id,
                'titel' => $i->titel,
                'tokens' => IdeenDedup::tokens((string) $i->titel),
            ])
            ->all();

        $neu = 0;
        $dup = 0;
        $aehnlich = 0;

        foreach ($ideen as $i) {
            $titel = trim((string) ($i['titel'] ?? ''));
            if ($titel === '') {
                continue;
            }
            $fp = IdeenDedup::fingerprint($titel);

            // Stufe 1: exakter Titel-Fingerprint (Kuratierungs-Status bleibt erhalten).
            if (EnrichmentIdea::where('fingerprint', $fp)->exists()) {
                $dup++;

                continue;
            }

            // Stufe 2: inhaltliche Ähnlichkeit gegen Bestand + bereits akzeptierte Ideen.
            $tokens = IdeenDedup::tokens($titel);
            if ($schwelle > 0) {
                $treffer = IdeenDedup::aehnlichste($tokens, $bestand, $schwelle);
                if ($treffer !== null) {
                    $aehnlich++;
                    $this->line(sprintf(
                        '  ~ Quasi-Doppler (%.2f) übersprungen: "%s"  ≈  #%s "%s"',
                        $treffer['score'], $titel, $treffer['id'], $treffer['titel']
                    ));

                    continue;
                }
            }

            $idee = EnrichmentIdea::create([
                'titel' => $titel,
                'kategorie' => $i['kategorie'] ?? 'Sonstiges',
                'beschreibung' => $i['beschreibung'] ?? null,
                'umsetzung' => $i['umsetzung'] ?? null,
                'quelle' => $i['quelle'] ?? null,
                'wettbewerber' => $i['wettbewerber'] ?? null,
                'notiz' => $i['notiz'] ?? null,
                'seo_wert' => $this->clamp($i['seo_wert'] ?? 3),
                'relevanz' => $this->clamp($i['relevanz'] ?? 3),
                'aufwand' => $this->clamp($i['aufwand'] ?? 3),
                'status' => 'neu',
                'quelle_lauf' => $lauf,
                'fingerprint' => $fp,
            ]);
            $neu++;

            // Live in den Bestand aufnehmen → nächste Idee desselben Laufs wird dagegen geprüft.
            $bestand[] = ['id' => $idee->id, 'titel' => $titel, 'tokens' => $tokens];
        }

        $this->info("Neue Ideen: {$neu} | exakte Duplikate: {$dup} | Quasi-Doppler: {$aehnlich} | Lauf: {$lauf}");

        return self::SUCCESS;
    }

    private function clamp($v): int
    {
        return max(1, min(5, (int) $v));
    }
}
