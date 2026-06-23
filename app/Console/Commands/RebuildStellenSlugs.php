<?php

namespace App\Console\Commands;

use App\Models\Zulassungsstelle;
use App\Support\Slug;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Baut die Zulassungsstellen-Slugs nach Wettbewerber-Vorbild neu auf:
 * kurze, ortsbasierte Slugs, die je Bundesland eindeutig sind
 * (URL-Schema /zulassungsstelle/{bundesland}/{ort}).
 *
 *  1. Backfill bundesland_id über die PLZ (plz_gemeinde → gemeinde.bundesland_id).
 *  2. Slug = normalisierter Ort; Kollisionen je Bundesland erhalten einen
 *     sprechenden Diskriminator (Stadtteil/Behördenname, sonst Straße, sonst Zähler).
 */
class RebuildStellenSlugs extends Command
{
    protected $signature = 'stellen:slugs-neu {--dry : Nur zeigen, was passieren würde}';

    protected $description = 'Erzeugt ortsbasierte, je Bundesland eindeutige Zulassungsstellen-Slugs.';

    /** Generische Wörter, die als Diskriminator nichts beitragen. */
    private const GENERIC = [
        'kfz', 'zulassungsstelle', 'zulassungsbehoerde', 'zulassungsbehorde', 'strassenverkehrsamt',
        'strassenverkehrsbehoerde', 'buergerbuero', 'buergeramt', 'stadt', 'landkreis', 'kreis',
        'landratsamt', 'amt', 'fuer', 'und', 'der', 'die', 'das', 'ot',
    ];

    public function handle(): int
    {
        $dry = $this->option('dry');

        // 1) Bundesland per PLZ nachtragen
        $backfilled = $this->backfillBundesland($dry);

        // 2) Slugs je Bundesland eindeutig neu vergeben.
        // Pre-Pass: temporäre, global eindeutige Slugs setzen, damit das Neu-Vergeben
        // unter dem zusammengesetzten Unique-Index (bundesland_id, slug) kollisionsfrei läuft.
        if (! $dry) {
            foreach (Zulassungsstelle::orderBy('id')->get() as $s) {
                $s->slug = 'tmp-'.$s->id;
                $s->saveQuietly();
            }
        }

        $used = [];        // landKey => [slug => true]
        $changed = 0;
        foreach (Zulassungsstelle::orderBy('id')->get() as $s) {
            $landKey = $s->bundesland_id ?? 0;
            $base = $this->baseSlug($s);
            $slug = $this->uniqueInLand($base, $s, $used[$landKey] ?? []);
            $used[$landKey][$slug] = true;
            if ($slug !== $s->slug) {
                $changed++;
                if (! $dry) {
                    $s->slug = $slug;
                    $s->saveQuietly();
                }
            }
        }

        $this->info(($dry ? '[DRY] ' : '')."Bundesland nachgetragen: $backfilled · Slugs neu/aktualisiert: $changed · Stellen: ".Zulassungsstelle::count());
        if (! $dry) {
            $this->comment('Beispiele: '.Zulassungsstelle::whereNotNull('bundesland_id')->inRandomOrder()->take(4)->get()
                ->map(fn ($s) => $s->land_slug.'/'.$s->slug)->implode('  ·  '));
        }
        return self::SUCCESS;
    }

    private function backfillBundesland(bool $dry): int
    {
        if (! DB::getSchemaBuilder()->hasTable('plz_gemeinde')) {
            return 0;
        }
        // PLZ → bundesland_id (eindeutig je PLZ)
        $plzLand = DB::table('plz_gemeinde as pg')
            ->join('gemeinden as g', 'g.id', '=', 'pg.gemeinde_id')
            ->whereNotNull('g.bundesland_id')
            ->select('pg.plz', 'g.bundesland_id')
            ->get()->groupBy('plz')
            ->map(fn ($rows) => $rows->pluck('bundesland_id')->unique()->count() === 1 ? $rows[0]->bundesland_id : null)
            ->filter();

        // Ort → Bundesland (nur eindeutige Gemeindenamen) als zweite Quelle.
        $ortLand = [];
        foreach (DB::table('gemeinden')->whereNotNull('bundesland_id')->get(['name', 'bundesland_id']) as $g) {
            $key = $this->norm($g->name);
            if ($key === '') continue;
            $ortLand[$key] = isset($ortLand[$key]) && $ortLand[$key] !== $g->bundesland_id ? null : $g->bundesland_id;
        }

        $n = 0;
        foreach (Zulassungsstelle::whereNull('bundesland_id')->get() as $s) {
            $bl = ($s->plz ? ($plzLand[$s->plz] ?? null) : null)
                ?? $this->ortToLand($s->ort, $ortLand);
            if (! $bl) continue;
            $n++;
            if (! $dry) { $s->bundesland_id = $bl; $s->saveQuietly(); }
        }
        return $n;
    }

    /** Ort → Bundesland: exakt, sonst eindeutiger Präfix-Treffer (z. B. „Freiburg" → „Freiburg im Breisgau"). */
    private function ortToLand(?string $ort, array $ortLand): ?int
    {
        $key = $this->norm($ort);
        if ($key === '') return null;
        if (isset($ortLand[$key])) return $ortLand[$key];
        $treffer = [];
        foreach ($ortLand as $name => $bl) {
            if ($bl !== null && str_starts_with($name, $key)) $treffer[$bl] = true;
        }
        return count($treffer) === 1 ? array_key_first($treffer) : null;
    }

    private function norm(?string $s): string
    {
        $s = Str::lower((string) $s);
        $s = preg_split('/[(,]/', $s)[0];
        $s = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $s);
        return preg_replace('/[^a-z0-9]+/u', '', $s);
    }

    /** Ortsbasierter Basis-Slug (Fallback: Behördenname). */
    private function baseSlug(Zulassungsstelle $s): string
    {
        $base = Slug::de((string) $s->ort);
        if ($base === '') {
            $base = Slug::de($this->stripGeneric((string) $s->name));
        }
        return $base !== '' ? $base : 'zulassungsstelle';
    }

    /** Macht den Basis-Slug innerhalb des Bundeslands eindeutig. */
    private function uniqueInLand(string $base, Zulassungsstelle $s, array $taken): string
    {
        if (! isset($taken[$base])) return $base;

        // Diskriminator-Priorität: sauberer kurzer Stadtteil → PLZ → Straßenname → Zähler.
        $rest = Slug::de($this->stripGeneric(str_ireplace((string) $s->ort, '', (string) $s->name)));
        $cleanRest = preg_match('/^[a-z][a-z-]{1,17}$/', $rest) ? $rest : null;   // nur kurze, alphabetische Stadtteile

        $kandidaten = array_filter([
            $cleanRest,
            $s->plz ? (string) $s->plz : null,
            Slug::de($this->streetName($s->strasse)) ?: null,
        ]);
        foreach ($kandidaten as $disc) {
            if ($disc !== $base && ! isset($taken[$base.'-'.$disc])) {
                return $base.'-'.$disc;
            }
        }
        // numerischer Fallback
        $i = 2;
        while (isset($taken[$base.'-'.$i])) $i++;
        return $base.'-'.$i;
    }

    private function stripGeneric(string $s): string
    {
        $s = Str::slug(Slug::umlaute($s), ' ');
        $words = array_filter(explode(' ', $s), fn ($w) => $w !== '' && ! in_array($w, self::GENERIC, true));
        return implode(' ', $words);
    }

    private function streetName(?string $s): string
    {
        $s = Str::slug(Slug::umlaute((string) $s), ' ');
        return trim(preg_replace('/\d.*$/', '', $s));
    }
}
