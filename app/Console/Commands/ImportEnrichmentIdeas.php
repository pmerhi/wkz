<?php

namespace App\Console\Commands;

use App\Models\EnrichmentIdea;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportEnrichmentIdeas extends Command
{
    protected $signature = 'enrichment:import {--path= : JSON-Datei mit Ideen} {--lauf= : Kennung des Recherche-Laufs}';

    protected $description = 'Schreibt neue Recherche-Ideen ins Dashboard (Dedup per Fingerprint, vorhandene bleiben unangetastet).';

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
        $neu = 0;
        $dup = 0;

        foreach ($ideen as $i) {
            $titel = trim((string) ($i['titel'] ?? ''));
            if ($titel === '') {
                continue;
            }
            $fp = substr(sha1(Str::lower($titel)), 0, 64);

            // Vorhandene Idee NICHT überschreiben (Kuratierungs-Status bleibt erhalten).
            if (EnrichmentIdea::where('fingerprint', $fp)->exists()) {
                $dup++;
                continue;
            }

            EnrichmentIdea::create([
                'titel'        => $titel,
                'kategorie'    => $i['kategorie'] ?? 'Sonstiges',
                'beschreibung' => $i['beschreibung'] ?? null,
                'umsetzung'    => $i['umsetzung'] ?? null,
                'quelle'       => $i['quelle'] ?? null,
                'wettbewerber' => $i['wettbewerber'] ?? null,
                'notiz'        => $i['notiz'] ?? null,
                'seo_wert'     => $this->clamp($i['seo_wert'] ?? 3),
                'relevanz'     => $this->clamp($i['relevanz'] ?? 3),
                'aufwand'      => $this->clamp($i['aufwand'] ?? 3),
                'status'       => 'neu',
                'quelle_lauf'  => $lauf,
                'fingerprint'  => $fp,
            ]);
            $neu++;
        }

        $this->info("Neue Ideen: {$neu} | Duplikate übersprungen: {$dup} | Lauf: {$lauf}");

        return self::SUCCESS;
    }

    private function clamp($v): int
    {
        return max(1, min(5, (int) $v));
    }
}
