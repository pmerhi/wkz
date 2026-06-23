<?php

namespace App\Support;

use App\Models\Bundesland;
use App\Models\Gemeinde;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Ordnet Ort/PLZ der AGS-Struktur zu (Gemeinde + Kreis).
 * Primär exaktes PLZ-Match, sonst Ortsname mit PLZ-Region-Disambiguierung.
 * Wiederverwendbar für alle Wettbewerber-Extraktoren (AGS-Prinzip).
 */
class AgsMatcher
{
    private array $plzIndex = [];   // plz => [gemeinde_id, kreis_id]
    private array $gIndex = [];     // normName => [[id, kreis_id, bundesland_id], ...]
    private array $plzLandIds = []; // plz-Anfangsziffer => [bundesland_id, ...]

    public function __construct()
    {
        foreach (DB::table('plz_gemeinde')->select('plz', 'gemeinde_id', 'kreis_id')->get() as $r) {
            $this->plzIndex[$r->plz] ??= [$r->gemeinde_id, $r->kreis_id];
        }
        foreach (Gemeinde::select('id', 'name', 'kreis_id', 'bundesland_id')->cursor() as $g) {
            $n = $this->norm($g->name);
            if ($n !== '') $this->gIndex[$n][] = [$g->id, $g->kreis_id, $g->bundesland_id];
        }
        $byName = Bundesland::pluck('id', 'name');
        $zones = [
            '0' => ['Sachsen', 'Sachsen-Anhalt', 'Thüringen', 'Brandenburg'],
            '1' => ['Berlin', 'Brandenburg', 'Mecklenburg-Vorpommern'],
            '2' => ['Hamburg', 'Schleswig-Holstein', 'Niedersachsen', 'Bremen'],
            '3' => ['Niedersachsen', 'Hessen', 'Nordrhein-Westfalen', 'Thüringen'],
            '4' => ['Nordrhein-Westfalen', 'Niedersachsen'],
            '5' => ['Nordrhein-Westfalen', 'Rheinland-Pfalz', 'Saarland'],
            '6' => ['Hessen', 'Rheinland-Pfalz', 'Saarland', 'Baden-Württemberg', 'Bayern'],
            '7' => ['Baden-Württemberg', 'Rheinland-Pfalz'],
            '8' => ['Bayern', 'Baden-Württemberg'],
            '9' => ['Bayern', 'Thüringen'],
        ];
        foreach ($zones as $d => $names) {
            $this->plzLandIds[$d] = array_values(array_filter(array_map(fn ($n) => $byName[$n] ?? null, $names)));
        }
    }

    /** @return array{0:int,1:?int}|null  [gemeinde_id, kreis_id] */
    public function match(?string $ort, ?string $plz): ?array
    {
        if ($plz && isset($this->plzIndex[$plz])) {
            return $this->plzIndex[$plz];
        }
        $cands = $this->gIndex[$this->norm($ort)] ?? [];
        if (! $cands) return null;
        if (count($cands) === 1) return [$cands[0][0], $cands[0][1]];

        $allowed = ($plz && isset($this->plzLandIds[$plz[0]])) ? $this->plzLandIds[$plz[0]] : [];
        if ($allowed) {
            foreach ($cands as $c) {
                if (in_array($c[2], $allowed, true)) return [$c[0], $c[1]];
            }
        }
        return [$cands[0][0], $cands[0][1]];
    }

    public function norm(?string $s): string
    {
        if (! $s) return '';
        $s = Str::lower($s);
        $s = preg_split('/[(,]/', $s)[0];
        $s = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $s);
        $s = preg_replace('/\b(landkreis|kreis|staedteregion|stadt|kreisfreie|hansestadt|landeshauptstadt)\b/u', '', $s);
        $s = preg_replace('/[^a-z0-9]+/u', '', $s);
        return trim($s);
    }
}
