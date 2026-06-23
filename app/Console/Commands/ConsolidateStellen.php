<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Konsolidiert die Wettbewerber-Extrakte über AGS/PLZ zu einem Golden Record je
 * physischer Stelle (Identität = PLZ + normalisierte Straße). INTERN.
 */
class ConsolidateStellen extends Command
{
    protected $signature = 'consolidate:stellen';

    protected $description = 'Führt die Wettbewerber-Extrakte zu konsolidierten Stellen zusammen (AGS, Golden Record).';

    /** Quellen-Priorität nach beobachteter Datenqualität (1 = beste). */
    private const PRIO = [
        'KennzeichenKing'              => 1,
        'Straßenverkehrsamt.de (STVA)' => 2,
        'Gutschild.de'                 => 3,
        'Zulassungsstelle.de'          => 4,
        'KFZ-Kennzeichen.net'          => 5,
    ];

    public function handle(): int
    {
        $rows = DB::table('extrakt_zulassungsstelle as e')
            ->join('wettbewerber as w', 'w.id', '=', 'e.wettbewerber_id')
            ->whereNotNull('e.plz')
            ->select('e.name', 'e.strasse', 'e.plz', 'e.ort', 'e.telefon', 'e.email',
                     'e.website', 'e.oeffnungszeiten', 'e.gemeinde_id', 'e.kreis_id', 'w.name as quelle')
            ->get();
        $this->info('Extrakte (mit PLZ): '.$rows->count());

        // Gruppieren nach Identität
        $groups = [];
        foreach ($rows as $r) {
            $id = $r->strasse
                ? $r->plz.'|'.$this->normStreet($r->strasse)
                : $r->plz.'|'.$this->normName($r->ort).'|ns';
            $groups[$id][] = $r;
        }
        $this->info('Konsolidierte Stellen (Identitäten): '.count($groups));

        DB::table('konsolidierte_stelle')->truncate();

        $batch = [];
        foreach ($groups as $id => $g) {
            // nach Quellen-Priorität sortieren
            usort($g, fn ($a, $b) => (self::PRIO[$a->quelle] ?? 9) <=> (self::PRIO[$b->quelle] ?? 9));

            $batch[] = [
                'identitaet'      => Str::limit($id, 250, ''),
                'name'            => $this->ws($this->first($g, 'name')),
                'strasse'         => $this->ws($this->first($g, 'strasse')),
                'plz'             => $g[0]->plz,
                'ort'             => $this->ws($this->first($g, 'ort')),
                'telefon'         => $this->cleanPhone($this->first($g, 'telefon')),
                'email'           => $this->first($g, 'email'),
                'website'         => $this->first($g, 'website'),
                'oeffnungszeiten' => $this->richestHours($g),
                'gemeinde_id'     => $this->first($g, 'gemeinde_id'),
                'kreis_id'        => $this->first($g, 'kreis_id'),
                'quellen'         => json_encode(array_values(array_unique(array_map(fn ($r) => $r->quelle, $g))), JSON_UNESCAPED_UNICODE),
                'quellen_anzahl'  => count(array_unique(array_map(fn ($r) => $r->quelle, $g))),
                'created_at'      => now(), 'updated_at' => now(),
            ];
            if (count($batch) >= 500) { DB::table('konsolidierte_stelle')->insert($batch); $batch = []; }
        }
        if ($batch) DB::table('konsolidierte_stelle')->insert($batch);

        $total = DB::table('konsolidierte_stelle')->count();
        $multi = DB::table('konsolidierte_stelle')->where('quellen_anzahl', '>=', 2)->count();
        $oeff  = DB::table('konsolidierte_stelle')->whereNotNull('oeffnungszeiten')->count();
        $ags   = DB::table('konsolidierte_stelle')->whereNotNull('gemeinde_id')->count();
        $this->info("Fertig. Konsolidiert: $total · mehrfach belegt (≥2 Quellen): $multi · mit Öffnungszeiten: $oeff · mit AGS: $ags");
        return self::SUCCESS;
    }

    /** Erster nicht-leerer Wert in (nach Priorität sortierter) Gruppe. */
    private function first(array $g, string $field): mixed
    {
        foreach ($g as $r) {
            if (! empty($r->$field)) return $r->$field;
        }
        return null;
    }

    /** Reichste Öffnungszeiten (meiste Tag-Einträge) in der Gruppe. */
    private function richestHours(array $g): ?string
    {
        $best = null; $bestN = 0;
        foreach ($g as $r) {
            if (empty($r->oeffnungszeiten)) continue;
            $arr = json_decode($r->oeffnungszeiten, true);
            $n = is_array($arr) ? count($arr) : 0;
            if ($n > $bestN) { $bestN = $n; $best = $r->oeffnungszeiten; }
        }
        return $best;
    }

    /**
     * Identitäts-Schlüssel aus Straße: Straßenname + ERSTE Hausnummer.
     * Robust gegen Hausnummer-Formate ("2" / "2-4" / "2/4 Haus SF" → gleicher Key),
     * trennt aber verschiedene Straßen (echte Filialen).
     */
    private function normStreet(string $s): string
    {
        $s = Str::lower($s);
        $s = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $s);
        $s = preg_replace('/stra(ss|ß)e|str\.?/u', 'str', $s);
        $num = preg_match('/\d+/', $s, $m) ? $m[0] : '';
        $name = preg_replace('/\d.*$/u', '', $s);           // alles vor der ersten Ziffer
        $name = preg_replace('/[^a-z0-9]+/u', '', $name);
        return $name.$num;
    }

    /** Mehrfach-Leerzeichen zu einem zusammenfassen. */
    private function ws(?string $s): ?string
    {
        return $s ? trim(preg_replace('/\s+/u', ' ', $s)) : null;
    }

    /** Telefon vereinheitlichen: Trenner zu einfachem Leerzeichen. */
    private function cleanPhone(?string $p): ?string
    {
        if (! $p) return null;
        $p = preg_replace('/[^\d+]+/', ' ', $p);
        return trim(preg_replace('/\s+/', ' ', $p)) ?: null;
    }

    private function normName(?string $s): string
    {
        if (! $s) return '';
        $s = Str::lower($s);
        $s = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $s);
        return preg_replace('/[^a-z0-9]+/u', '', $s);
    }
}
