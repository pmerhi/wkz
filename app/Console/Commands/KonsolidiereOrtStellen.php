<?php

namespace App\Console\Commands;

use App\Models\Zulassungsstelle;
use App\Support\Slug;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Konsolidiert Zulassungsstellen zum kennzeichenking-Modell: eine Seite je Ort/Bezirk,
 * ohne PLZ im Slug. Stellen werden je (Bundesland, Ort, Zulassungsbezirk = kreis_id)
 * gruppiert; je Gruppe wird ein Primär-Amt gewählt, die übrigen werden als Kinder
 * (`parent_id`) angehängt. Stadt und Landkreis im selben Ort bleiben getrennt (-land).
 */
class KonsolidiereOrtStellen extends Command
{
    protected $signature = 'stellen:konsolidieren-ort {--dry}';

    protected $description = 'Eine Seite je Ort/Bezirk: Primär-Amt + Kinder, saubere Slugs ohne PLZ.';

    public function handle(): int
    {
        $dry = $this->option('dry');

        // Pre-Pass: temporäre, global eindeutige Slugs, damit das Neuvergeben unter dem
        // Unique-Index (bundesland_id, slug) kollisionsfrei läuft.
        if (! $dry) {
            Zulassungsstelle::query()->update(['parent_id' => null]);
            foreach (Zulassungsstelle::orderBy('id')->get(['id']) as $s) {
                Zulassungsstelle::whereKey($s->id)->update(['slug' => 'tmp-'.$s->id]);
            }
        }

        // Nach (Bundesland | Ort) gruppieren.
        $orte = [];
        foreach (Zulassungsstelle::orderBy('id')->get() as $s) {
            $ortKey = $this->ortKey($s->ort) ?: ('id'.$s->id);
            $orte[($s->bundesland_id ?? 0).'|'.$ortKey][] = $s;
        }

        $primaer = 0; $kinder = 0; $seiten = 0;
        $usedSlug = [];   // bundesland_id => [slug => true]

        foreach ($orte as $stellen) {
            $bl = $stellen[0]->bundesland_id ?? 0;
            $base = Slug::de($stellen[0]->ort ?: $stellen[0]->name) ?: 'zulassungsstelle';

            // Bezirks-Subgruppen: Stadt vs. Landkreis (namensbasiert, zuverlässiger als kreis_id).
            $sub = ['stadt' => [], 'land' => []];
            foreach ($stellen as $s) {
                $sub[$this->istLandStelle($s) ? 'land' : 'stadt'][] = $s;
            }
            // Stadt zuerst (bekommt den Basis-Slug), Landkreis danach (-land).
            $subGroups = [];
            if ($sub['stadt']) $subGroups[] = [false, $sub['stadt']];
            if ($sub['land'])  $subGroups[] = [true, $sub['land']];

            foreach ($subGroups as [$istLand, $gruppe]) {
                // Slug der Seite bestimmen (ohne PLZ); Landkreis bekommt -land-Suffix.
                $slug = $this->freierSlug($base.($istLand ? '-land' : ''), $usedSlug[$bl] ?? []);
                $usedSlug[$bl][$slug] = true;
                $seiten++;

                // Primär-Amt wählen, Rest = Kinder.
                usort($gruppe, fn ($a, $b) => $this->score($b) <=> $this->score($a));
                $head = $gruppe[0];
                $this->setze($head, null, $slug, $dry); $primaer++;
                foreach (array_slice($gruppe, 1) as $kind) {
                    $kslug = $this->freierSlug($slug, $usedSlug[$bl] ?? []);
                    $usedSlug[$bl][$kslug] = true;
                    $this->setze($kind, $head->id, $kslug, $dry); $kinder++;
                }
            }
        }

        $this->info(($dry ? '[DRY] ' : '')."Seiten (Primär-Ämter): $primaer · Kind-Stellen: $kinder · Ort-Gruppen: ".count($orte));
        $this->line('Beispiele: '.collect(Zulassungsstelle::whereNull('parent_id')->whereNotNull('bundesland_id')->inRandomOrder()->limit(4)->get())
            ->map(fn ($s) => $s->land_slug.'/'.$s->slug)->implode(' · '));
        return self::SUCCESS;
    }

    private function setze(Zulassungsstelle $s, ?int $parent, string $slug, bool $dry): void
    {
        if ($dry) return;
        $s->parent_id = $parent;
        $s->slug = $slug;
        $s->saveQuietly();
    }

    /** Bewertung für die Primär-Amt-Wahl (höher = eher Hauptamt). */
    private function score(Zulassungsstelle $s): int
    {
        $n = Str::lower((string) $s->name);
        $score = 0;
        if ($s->termin_url) $score += 3;
        if (is_array($s->oeffnungszeiten) && $s->oeffnungszeiten && ! isset($s->oeffnungszeiten['raw'])) $score += 2;
        if (Str::contains($n, ['straßenverkehrsamt', 'strassenverkehrsamt', 'zulassungsbehörde', 'hauptstelle'])) $score += 2;
        if (Str::contains($n, ['außenstelle', 'aussenstelle', 'nebenstelle'])) $score -= 3;
        if (Str::contains((string) $s->quelle, 'Konsolidat')) $score += 1;
        if ($s->telefon) $score += 1;
        return $score - ((int) $s->id) / 1e9;   // stabiler Tiebreak: kleinere id leicht bevorzugt
    }

    /** Einzelne Stelle: Landkreis-/Land-Amt? (namensbasiert) */
    private function istLandStelle(Zulassungsstelle $s): bool
    {
        $t = Str::lower((string) $s->name.' '.$s->ort);
        return (bool) preg_match('/landkreis|landratsamt|\(land\)|-land\b|kreisverwaltung|kreishaus/u', $t);
    }

    /** Erster freier Slug: $base, sonst $base-2, $base-3 … */
    private function freierSlug(string $base, array $used): string
    {
        if (! isset($used[$base])) return $base;
        $i = 2;
        while (isset($used[$base.'-'.$i])) $i++;
        return $base.'-'.$i;
    }

    /** Ort-Gruppenschlüssel inkl. Zusatz (Homberg-Efze ≠ Homberg-Ohm). */
    private function ortKey(?string $ort): string
    {
        return Slug::de($ort);
    }
}
