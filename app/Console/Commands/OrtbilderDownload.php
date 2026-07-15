<?php

namespace App\Console\Commands;

use App\Models\Ortbild;
use Illuminate\Console\Command;

/**
 * Lädt alle im Admin ausgewählten Hero-/Footer-Bilder lokal nach public/img/orte/.
 * Im Normalfall passiert der Download bereits automatisch bei der Auswahl im Admin;
 * dieser Befehl dient zum Nachladen/Reparieren (mit --force alle neu).
 */
class OrtbilderDownload extends Command
{
    protected $signature = 'ortbilder:download {--force : Auch bereits geladene neu herunterladen}';

    protected $description = 'Lädt ausgewählte Hero-/Footer-Ortsbilder lokal nach public/img/orte/.';

    public function handle(): int
    {
        $bilder = Ortbild::with('gemeinde')
            ->whereIn('rolle', Ortbild::AUSGEWAEHLT)
            ->get();

        if ($bilder->isEmpty()) {
            $this->warn('Keine ausgewählten Hero-/Footer-Bilder vorhanden. Erst im Admin auswählen.');
            return self::SUCCESS;
        }

        $geladen = 0;
        foreach ($bilder as $bild) {
            $slug = $bild->gemeinde?->slug ?? ('gemeinde-'.$bild->gemeinde_id);
            if ($bild->herunterladen($this->option('force'))) {
                $this->line("  ✓ {$slug} ({$bild->rolle})");
                $geladen++;
            } else {
                $this->error("  {$slug} ({$bild->rolle}): Download fehlgeschlagen / keine Quelle.");
            }
        }

        $this->info("Fertig. {$geladen} Bild(er) heruntergeladen.");
        return self::SUCCESS;
    }
}
