<?php

namespace App\Console\Commands;

use App\Models\Zulassungsstelle;
use App\Support\Oeffnungszeiten;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Übernimmt geprüfte offizielle Öffnungszeiten aus der Staging-Tabelle in die
 * Live-Daten der Zulassungsstellen (kanonisiert, mit Quellen-Vermerk).
 */
class OffizielleOzUebernehmen extends Command
{
    protected $signature = 'oz:offiziell-uebernehmen
        {--status=ok : nur Staging-Einträge mit diesem Status}
        {--id= : nur diese Stellen-ID}
        {--dry : nur anzeigen}';

    protected $description = 'Übernimmt offizielle Öffnungszeiten aus dem Staging in die Live-Daten.';

    public function handle(): int
    {
        $dry = $this->option('dry');
        $rows = DB::table('offizielle_oeffnungszeiten')
            ->where('status', $this->option('status'))
            ->where('uebernommen', false)
            ->whereNotNull('oeffnungszeiten');
        if ($id = $this->option('id')) {
            $rows->where('zulassungsstelle_id', $id);
        }

        $n = 0;
        foreach ($rows->get() as $r) {
            $stelle = Zulassungsstelle::find($r->zulassungsstelle_id);
            if (! $stelle) {
                continue;
            }
            $zeiten = Oeffnungszeiten::kanonisieren(json_decode($r->oeffnungszeiten, true) ?: []);
            if (! $zeiten) {
                continue;
            }
            $host = parse_url($r->quelle_url, PHP_URL_HOST) ?: 'offiziell';

            if ($dry) {
                $this->line("  {$stelle->name}: ".count($zeiten).' Einträge ← '.$host);
            } else {
                $stelle->oeffnungszeiten = $zeiten;
                $stelle->quelle = trim(($stelle->quelle ?: '').' · Öffnungszeiten: offiziell ('.$host.')');
                $stelle->oeffnungszeiten_geaendert = false;
                $stelle->save();
                DB::table('offizielle_oeffnungszeiten')->where('id', $r->id)->update(['uebernommen' => true, 'updated_at' => now()]);
            }
            $n++;
        }

        $this->info(($dry ? '[DRY] ' : '')."Übernommen: $n Stellen.");
        return self::SUCCESS;
    }
}
