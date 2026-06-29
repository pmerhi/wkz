<?php

namespace App\Console\Commands;

use App\Models\Zulassungsstelle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Importiert die vom Extraktions-Agenten erzeugte JSON-Datei mit offiziellen
 * Öffnungszeiten in die Staging-Tabelle `offizielle_oeffnungszeiten` und gibt
 * eine Qualitätsauswertung (Statusverteilung, Abgleich mit Live-Daten) aus.
 */
class OffizielleOzImport extends Command
{
    protected $signature = 'oz:extrakt-import {--file=storage/app/pilot-extrakt.json}';
    protected $description = 'Importiert extrahierte offizielle Öffnungszeiten in die Staging-Tabelle + Auswertung.';

    public function handle(): int
    {
        $pfad = base_path($this->option('file'));
        if (! is_file($pfad)) {
            $this->error("Datei fehlt: $pfad");
            return self::FAILURE;
        }
        $daten = json_decode(file_get_contents($pfad), true);
        if (! is_array($daten)) {
            $this->error('Ungültiges JSON.');
            return self::FAILURE;
        }

        $statusZahl = [];
        $importiert = 0;
        $abweichungen = [];

        foreach ($daten as $e) {
            $stelle = Zulassungsstelle::find($e['id'] ?? null);
            if (! $stelle) {
                continue;
            }
            $status = $e['status'] ?? 'unsicher';
            $zeiten = $e['oeffnungszeiten'] ?? [];
            $statusZahl[$status] = ($statusZahl[$status] ?? 0) + 1;

            DB::table('offizielle_oeffnungszeiten')->updateOrInsert(
                ['zulassungsstelle_id' => $stelle->id],
                [
                    'quelle_url'      => $stelle->oeffnungszeiten_url,
                    'oeffnungszeiten' => $zeiten ? json_encode($zeiten, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                    'status'          => $status,
                    'hinweis'         => $e['hinweis'] ?? null,
                    'roh_auszug'      => $e['roh_auszug'] ?? null,
                    'modell'          => 'claude-agent',
                    'extrahiert_at'   => now(),
                    'updated_at'      => now(),
                    'created_at'      => now(),
                ]
            );
            $importiert++;

            // Abgleich: weicht die offizielle Extraktion von den Live-Daten ab?
            if ($status === 'ok' && $zeiten) {
                $neu = $this->fingerprint($zeiten);
                $alt = $this->fingerprint(is_array($stelle->oeffnungszeiten) ? $stelle->oeffnungszeiten : []);
                if ($neu !== $alt) {
                    $abweichungen[] = $stelle->name;
                }
            }
        }

        $this->info("Importiert: $importiert Stellen in Staging.");
        $this->line('');
        $this->line('Statusverteilung:');
        foreach ($statusZahl as $s => $n) {
            $this->line(sprintf('  %-15s %d', $s, $n));
        }
        $okQuote = ($statusZahl['ok'] ?? 0);
        $this->line('');
        $this->info("Brauchbar (Status ok): $okQuote von $importiert");
        $this->line('Abweichung Offiziell vs. Live-Daten bei '.count($abweichungen).' Stellen'
            .(count($abweichungen) ? ': '.implode(', ', array_slice($abweichungen, 0, 8)) : ''));

        return self::SUCCESS;
    }

    /** Tag+Zeit-Fingerprint zum Vergleich (reihenfolgeunabhängig). */
    private function fingerprint(array $oz): string
    {
        $seg = [];
        foreach ($oz as $e) {
            if (! is_array($e) || ! isset($e['opens'], $e['closes'])) {
                continue;
            }
            $seg[] = ($e['day'] ?? $e['label'] ?? '?').substr((string) $e['opens'], 0, 5).substr((string) $e['closes'], 0, 5);
        }
        sort($seg);

        return implode('|', array_unique($seg));
    }
}
