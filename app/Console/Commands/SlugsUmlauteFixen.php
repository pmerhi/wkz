<?php

namespace App\Console\Commands;

use App\Models\KennzeichenKuerzel;
use App\Models\RatgeberArtikel;
use App\Support\Slug;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Korrigiert bestehende Slugs auf die deutsche Umlaut-Konvention (ä→ae, ö→oe, ü→ue, ß→ss).
 * Betrifft Kürzel (z.B. GÖ: go→goe) und Ratgeber-Artikel (z.B. grunes→gruenes).
 *
 *   php artisan oz:slugs-umlaute --apply
 */
class SlugsUmlauteFixen extends Command
{
    protected $signature = 'oz:slugs-umlaute {--apply : Änderungen schreiben (sonst nur Vorschau)}';

    protected $description = 'Setzt Slugs auf die korrekte Umlaut-Transliteration (ae/oe/ue/ss)';

    public function handle(): int
    {
        $apply = $this->option('apply');
        $this->info($apply ? 'ÄNDERUNGEN WERDEN GESCHRIEBEN' : 'VORSCHAU (nur Anzeige, --apply zum Schreiben)');

        $this->fixKuerzel($apply);
        $this->fixRatgeber($apply);

        return self::SUCCESS;
    }

    private function fixKuerzel(bool $apply): void
    {
        $this->line("\n== Kennzeichen-Kürzel ==");
        $belegt = KennzeichenKuerzel::pluck('slug', 'id')->all();   // aktuelle Slugs
        $n = 0;
        foreach (KennzeichenKuerzel::orderBy('code')->get() as $k) {
            $soll = Slug::de($k->code) ?: Str::lower($k->code);
            if ($soll === $k->slug) continue;
            // Kollision vermeiden (anderer Datensatz hat $soll bereits)
            $final = $soll; $i = 2;
            while (in_array($final, $belegt, true) && array_search($final, $belegt, true) != $k->id) {
                $final = $soll.'-'.$i++;
            }
            $this->line(sprintf('  %-6s %s → %s', $k->code, $k->slug, $final));
            if ($apply) { $k->slug = $final; $k->save(); }
            $belegt[$k->id] = $final;
            $n++;
        }
        $this->info("  Kürzel zu ändern: $n");
    }

    private function fixRatgeber(bool $apply): void
    {
        $this->line("\n== Ratgeber-Artikel ==");
        $n = 0;
        foreach (RatgeberArtikel::orderBy('slug')->get() as $a) {
            $neu = $this->slugMitUmlauten($a->slug, $a->titel);
            if ($neu === $a->slug) continue;
            // eindeutig?
            if (RatgeberArtikel::where('slug', $neu)->where('id', '!=', $a->id)->exists()) {
                $this->warn("  ! Kollision, übersprungen: {$a->slug} → {$neu}");
                continue;
            }
            $this->line(sprintf('  %s → %s', $a->slug, $neu));
            if ($apply) { $a->slug = $neu; $a->save(); }
            $n++;
        }
        $this->info("  Ratgeber zu ändern: $n");
    }

    /**
     * Ersetzt im Slug die „gestrippten" Umlaut-Wörter durch die korrekte ae/oe/ue-Form.
     * Quelle der Wörter ist der Titel: stripped = Str::slug(Wort), korrekt = Slug::de(Wort).
     */
    private function slugMitUmlauten(string $slug, string $titel): string
    {
        foreach (preg_split('/[\s\-–—\/]+/u', $titel) as $wort) {
            if (! preg_match('/[äöüÄÖÜ]/u', $wort)) continue;        // ß ist in beiden gleich (ss)
            $stripped = Str::slug($wort);
            $korrekt  = Slug::de($wort);
            if ($stripped === '' || $stripped === $korrekt) continue;
            // ganzes Slug-Segment ersetzen (zwischen Bindestrichen / Rand)
            $slug = preg_replace('/(?<![a-z0-9])'.preg_quote($stripped, '/').'(?![a-z0-9])/', $korrekt, $slug);
        }
        return $slug;
    }
}
