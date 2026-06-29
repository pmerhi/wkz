<?php

namespace App\Console\Commands;

use App\Models\Bundesland;
use App\Models\Gemeinde;
use App\Models\OrtAlias;
use App\Models\Zulassungsstelle;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Baut die Alias-Tabelle für alte /wunschkennzeichen/{ort}/-Slugs auf, die nicht
 * 1:1 auf einen heutigen Gemeinde-Slug passen. Eingabe: Textdatei mit einem Slug je Zeile.
 *
 *   php artisan oz:ort-aliasse /tmp/old_wk_slugs.txt
 */
class OrtAliasseAufbauen extends Command
{
    protected $signature = 'oz:ort-aliasse {datei : Datei mit alten Slugs (eine je Zeile)} {--frisch : Tabelle vorher leeren}';

    protected $description = 'Erzeugt Ort-Aliasse (alte Slugs → kanonische /wunschkennzeichen/-URL) mit Region-Prüfung';

    public function handle(): int
    {
        $datei = $this->argument('datei');
        if (! is_file($datei)) {
            $this->error("Datei nicht gefunden: $datei");
            return self::FAILURE;
        }
        if ($this->option('frisch')) {
            OrtAlias::query()->delete();
        }

        $alt = array_values(array_unique(array_filter(array_map('trim', file($datei)))));

        $gemSlug = Gemeinde::whereNotNull('slug')->pluck('id', 'slug')->all();   // slug => id
        // Namens-Index: normalisierter Name => [gemeinde_ids]
        $byName = [];
        Gemeinde::whereNotNull('slug')->get(['id', 'name', 'slug'])->each(function ($g) use (&$byName) {
            $byName[Str::slug($g->name)][] = $g->id;
        });
        $stl = Zulassungsstelle::whereNull('parent_id')->pluck('id', 'slug')->all();

        $exact = 0; $angelegt = 0; $offen = [];
        $stats = ['suffix' => 0, 'prefix' => 0, 'stelle' => 0, 'gemeinde-suffix' => 0];

        foreach ($alt as $o) {
            if (isset($gemSlug[$o])) { $exact++; continue; }   // existiert als echte Seite

            [$zielSlug, $quelle, $geprueft] = $this->aufloesen($o, $gemSlug, $byName, $stl);

            if (! $zielSlug) { $offen[] = $o; continue; }

            OrtAlias::updateOrCreate(
                ['slug' => $o],
                ['ziel' => '/wunschkennzeichen/'.$zielSlug, 'quelle' => $quelle, 'geprueft' => $geprueft]
            );
            $angelegt++;
            $stats[$quelle] = ($stats[$quelle] ?? 0) + 1;
        }

        $this->info("Alte Slugs gesamt: ".count($alt));
        $this->info("Exakt vorhanden (eigene Seite): $exact");
        $this->info("Aliasse angelegt: $angelegt  ".json_encode($stats));
        $geprueftAnz = OrtAlias::where('geprueft', true)->count();
        $this->info("davon region-geprüft: $geprueftAnz, ungeprüft: ".($angelegt - $geprueftAnz));
        $this->warn("Ohne Treffer (Ortsteile o.ä.): ".count($offen));
        if ($offen) {
            file_put_contents(storage_path('app/ort-aliasse-offen.txt'), implode("\n", $offen));
            $this->line('  → storage/app/ort-aliasse-offen.txt');
        }
        // Review-CSV der ungeprüften Aliasse
        $ungeprueft = OrtAlias::where('geprueft', false)->orderBy('slug')->get();
        $csv = "alt_slug;ziel;quelle\n";
        foreach ($ungeprueft as $a) $csv .= "{$a->slug};{$a->ziel};{$a->quelle}\n";
        file_put_contents(storage_path('app/ort-aliasse-review.csv'), $csv);
        $this->line('Review ungeprüfter Aliasse → storage/app/ort-aliasse-review.csv');

        return self::SUCCESS;
    }

    /** @return array{0:?string,1:string,2:bool} [zielSlug, quelle, geprueft] */
    private function aufloesen(string $o, array $gemSlug, array $byName, array $stl): array
    {
        $parts = explode('-', $o);

        // Heuristik 1: letzte Tokens abschneiden, bis ein Gemeinde-Slug bleibt (Region-Suffix).
        for ($n = count($parts) - 1; $n >= 1; $n--) {
            $cand = implode('-', array_slice($parts, 0, $n));
            $suffix = implode('-', array_slice($parts, $n));
            if (isset($gemSlug[$cand])) {
                // Region-Konsistenz: ist der Name eindeutig? sonst per Suffix disambiguieren.
                $namensSlug = Str::slug(Gemeinde::find($gemSlug[$cand])->name);
                $kandidaten = $byName[$namensSlug] ?? [$gemSlug[$cand]];
                if (count($kandidaten) === 1) {
                    return [$cand, 'suffix', true];                    // eindeutiger Name → sicher
                }
                // Mehrere gleichnamige: passt der Suffix (Kreis/Bundesland) zu genau einem?
                $treffer = $this->perRegion($kandidaten, $suffix);
                if ($treffer) {
                    return [Gemeinde::find($treffer)->slug, 'suffix', true];
                }
                return [$cand, 'suffix', false];                       // unsicher → ungeprüft
            }
        }

        // Heuristik 2: o ist Prefix genau einer Gemeinde (bad-homburg → bad-homburg-vor-der-hoehe).
        $pref = array_values(array_filter(array_keys($gemSlug), fn ($s) => str_starts_with($s, $o.'-')));
        if (count($pref) === 1) {
            return [$pref[0], 'prefix', true];
        }

        // Heuristik 3: alter Slug ist (noch) ein Stelle-Slug → deren Gemeinde.
        if (isset($stl[$o])) {
            $g = Zulassungsstelle::find($stl[$o])->gemeinde;
            if ($g && $g->slug) {
                return [$g->slug, 'stelle', $g->slug !== null];
            }
        }

        return [null, 'keine', false];
    }

    /** Wählt unter gleichnamigen Gemeinden die, deren Kreis-/Bundesland-Name den Suffix-Token enthält. */
    private function perRegion(array $gemeindeIds, string $suffix): ?int
    {
        $token = str_replace('-', ' ', $suffix);
        $treffer = [];
        foreach (Gemeinde::with(['kreis', 'bundesland'])->whereIn('id', $gemeindeIds)->get() as $g) {
            $heu = Str::slug(($g->kreis?->name ?? '').' '.($g->bundesland?->name ?? ''));
            foreach (explode('-', $suffix) as $t) {
                if ($t !== '' && str_contains($heu, $t)) { $treffer[] = $g->id; break; }
            }
        }
        $treffer = array_unique($treffer);
        return count($treffer) === 1 ? $treffer[0] : null;
    }
}
