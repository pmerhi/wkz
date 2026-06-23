<?php

namespace App\Console\Commands;

use App\Models\KennzeichenKuerzel;
use App\Models\Zulassungsstelle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Schließt die interne Verlinkung Kürzel ↔ Zulassungsstelle geografisch (AGS-Prinzip):
 *
 *  Hebel 1 – Kreis-Konsistenz: Alle Zulassungsstellen eines Kreises bedienen dieselben
 *            Unterscheidungszeichen. Die aus bereits verknüpften Stellen abgeleitete
 *            Kürzel-Menge je `kreis_id` wird auf alle Stellen desselben Kreises übertragen
 *            (verwaiste Stellen erhalten so ihre Kürzel). Zusätzlich wird die
 *            Kürzel↔Kreis-Pivot-Tabelle gefüllt.
 *
 *  Hebel 2 – Altkennzeichen: Wieder eingeführte Altkennzeichen werden denselben Stellen
 *            zugeordnet wie das aktuelle Kürzel desselben Kreises (Gleichheit der
 *            konsolidierten Bedeutung „Kreis, Bundesland").
 *
 * Idempotent (syncWithoutDetaching), rein lokal, keine externen Quellen.
 */
class LinkKuerzelGeografisch extends Command
{
    protected $signature = 'link:kuerzel-geografisch {--dry : Nur zeigen, was passieren würde}';

    protected $description = 'Schließt Kürzel↔Stelle-Verlinkung über Kreis-Konsistenz und Altkennzeichen-Bedeutung.';

    public function handle(): int
    {
        $dry = $this->option('dry');

        // --- Hebel 1: kreis_id => Menge der Kürzel-IDs (aus verknüpften Stellen) ---
        $kreisCodes = [];
        foreach (DB::table('kennzeichen_kuerzel_zulassungsstelle as p')
            ->join('zulassungsstellen as s', 's.id', '=', 'p.zulassungsstelle_id')
            ->whereNotNull('s.kreis_id')
            ->select('s.kreis_id', 'p.kennzeichen_kuerzel_id')->get() as $r) {
            $kreisCodes[$r->kreis_id][$r->kennzeichen_kuerzel_id] = true;
        }

        $stellenErgaenzt = 0; $neueLinks = 0; $pivotKreis = 0;
        foreach (Zulassungsstelle::whereNotNull('kreis_id')->with('kennzeichenKuerzel:id')->get() as $s) {
            $soll = array_keys($kreisCodes[$s->kreis_id] ?? []);
            if (! $soll) continue;
            $ist = $s->kennzeichenKuerzel->pluck('id')->all();
            $fehlt = array_diff($soll, $ist);
            if (! $fehlt) continue;
            $neueLinks += count($fehlt);
            $stellenErgaenzt++;
            if (! $dry) $s->kennzeichenKuerzel()->syncWithoutDetaching($soll);
        }

        // Kürzel↔Kreis-Pivot aus der Kreis→Kürzel-Ableitung füllen.
        foreach ($kreisCodes as $kreisId => $codeSet) {
            foreach (array_keys($codeSet) as $kuerzelId) {
                $exists = DB::table('kennzeichen_kuerzel_kreis')
                    ->where('kennzeichen_kuerzel_id', $kuerzelId)->where('kreis_id', $kreisId)->exists();
                if ($exists) continue;
                $pivotKreis++;
                if (! $dry) {
                    DB::table('kennzeichen_kuerzel_kreis')->insert([
                        'kennzeichen_kuerzel_id' => $kuerzelId, 'kreis_id' => $kreisId,
                    ]);
                }
            }
        }

        // --- Hebel 2: Altkennzeichen an Stellen des passenden aktuellen Kürzels hängen ---
        // bedeutung => Stelle-IDs (aus aktuellen, verknüpften Kürzeln; nach Hebel 1 = ganzer Kreis)
        $bedToStellen = [];
        foreach (KennzeichenKuerzel::where('ist_altkennzeichen', false)
            ->whereNotNull('bedeutung')->with('zulassungsstellen:id')->get() as $k) {
            if ($k->zulassungsstellen->isEmpty()) continue;
            foreach ($k->zulassungsstellen as $s) {
                $bedToStellen[$k->bedeutung][$s->id] = true;
            }
        }
        // Bei --dry sind Hebel-1-Effekte noch nicht persistiert; Ableitung bleibt konservativ.

        $altVerknuepft = 0; $altLinks = 0;
        foreach (KennzeichenKuerzel::where('ist_altkennzeichen', true)
            ->whereNotNull('bedeutung')->with('zulassungsstellen:id')->get() as $alt) {
            $ziel = array_keys($bedToStellen[$alt->bedeutung] ?? []);
            if (! $ziel) continue;
            $ist = $alt->zulassungsstellen->pluck('id')->all();
            $fehlt = array_diff($ziel, $ist);
            if (! $fehlt) continue;
            $altVerknuepft++;
            $altLinks += count($fehlt);
            if (! $dry) $alt->zulassungsstellen()->syncWithoutDetaching($ziel);
        }

        $this->info(($dry ? '[DRY] ' : '')."Hebel 1 – Stellen ergänzt: $stellenErgaenzt (+$neueLinks Links) · Kürzel↔Kreis-Pivot: +$pivotKreis");
        $this->info(($dry ? '[DRY] ' : '')."Hebel 2 – Altkennzeichen verknüpft: $altVerknuepft (+$altLinks Links)");
        $this->newLine();
        $this->line('Verwaiste Stellen jetzt: '.Zulassungsstelle::doesntHave('kennzeichenKuerzel')->count()
            .' · Kürzel ohne Stelle: '.KennzeichenKuerzel::doesntHave('zulassungsstellen')->count());
        return self::SUCCESS;
    }
}
