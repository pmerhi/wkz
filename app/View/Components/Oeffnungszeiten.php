<?php

namespace App\View\Components;

use Carbon\Carbon;
use Illuminate\View\Component;

/**
 * Öffnungszeiten-Widget mit Live-Status (geöffnet/geschlossen) und Balken-Timeline.
 * Serverseitig (Europe/Berlin) gerendert; JS aktualisiert Status + „Jetzt"-Marker live.
 */
class Oeffnungszeiten extends Component
{
    /** Reihenfolge + Beschriftung der Wochentage. */
    private const TAGE = [
        'Monday' => ['Montag', 'Mo'], 'Tuesday' => ['Dienstag', 'Di'], 'Wednesday' => ['Mittwoch', 'Mi'],
        'Thursday' => ['Donnerstag', 'Do'], 'Friday' => ['Freitag', 'Fr'], 'Saturday' => ['Samstag', 'Sa'], 'Sunday' => ['Sonntag', 'So'],
    ];

    /** Deutsche Kurzlabel → englischer Schlüssel (falls `day` fehlt). */
    private const KURZ = ['mo' => 'Monday', 'di' => 'Tuesday', 'mi' => 'Wednesday', 'do' => 'Thursday', 'fr' => 'Friday', 'sa' => 'Saturday', 'so' => 'Sunday'];

    public array $week = [];
    public int $axisStart = 360;   // 06:00
    public int $axisEnd = 1140;    // 19:00
    public array $status = ['offen' => false, 'text' => 'Keine Angaben', 'kurz' => '', 'klasse' => 'unbekannt'];
    public ?string $dataJson = null;
    public bool $hatDaten = false;
    public ?float $nowPct = null;
    public array $axisLabels = [];
    public ?array $heute = null;     // heutige Zeile (für die „Heute"-Vorschau)

    public function __construct($data)
    {
        $byDay = $this->parse(is_array($data) ? $data : []);
        if (! $byDay) return;
        $this->hatDaten = true;

        // Achse aus den Daten (auf volle Stunden, geclamped 5–22 Uhr).
        $alleO = $alleC = [];
        foreach ($byDay as $fenster) {
            foreach ($fenster as $f) { $alleO[] = $f[0]; $alleC[] = $f[1]; }
        }
        $this->axisStart = max(300, ((int) floor(min($alleO) / 60)) * 60);
        $this->axisEnd   = min(1320, ((int) ceil(max($alleC) / 60)) * 60);
        if ($this->axisEnd - $this->axisStart < 240) $this->axisEnd = $this->axisStart + 240;

        $now = Carbon::now('Europe/Berlin');
        $heuteKey = $now->format('l');
        $nowMin = $now->hour * 60 + $now->minute;

        foreach (self::TAGE as $key => [$label, $kurz]) {
            $fenster = $byDay[$key] ?? [];
            $offenJetzt = false;
            if ($key === $heuteKey) {
                foreach ($fenster as $f) {
                    if ($nowMin >= $f[0] && $nowMin < $f[1]) $offenJetzt = true;
                }
            }
            $row = [
                'label' => $label, 'kurz' => $kurz, 'heute' => $key === $heuteKey,
                'offen' => $offenJetzt,
                'fenster' => array_map(fn ($f) => [
                    'von' => $this->hhmm($f[0]), 'bis' => $this->hhmm($f[1]),
                    'left' => $this->pct($f[0]), 'width' => max(1.5, $this->pct($f[1]) - $this->pct($f[0])),
                ], $fenster),
            ];
            $this->week[] = $row;
            if ($row['heute']) $this->heute = $row;
        }

        if ($nowMin >= $this->axisStart && $nowMin <= $this->axisEnd) {
            $this->nowPct = $this->pct($nowMin);
        }
        // Stunden-Achsenbeschriftung (alle 2–3 Stunden, je nach Spanne).
        $step = ($this->axisEnd - $this->axisStart) > 540 ? 180 : 120;
        for ($m = $this->axisStart; $m <= $this->axisEnd; $m += $step) {
            $this->axisLabels[] = ['left' => $this->pct($m), 'text' => intdiv($m, 60).' Uhr'];
        }

        $this->status = $this->berechneStatus($byDay, $heuteKey, $nowMin);
        $this->dataJson = json_encode([
            'tage' => $byDay, 'start' => $this->axisStart, 'end' => $this->axisEnd,
        ], JSON_UNESCAPED_UNICODE);
    }

    /** Rohdaten → ['Monday' => [[opensMin, closesMin], …], …] (sortiert). */
    private function parse(array $data): array
    {
        $out = [];
        foreach ($data as $z) {
            if (! is_array($z) || ! isset($z['opens'], $z['closes'])) continue;
            $key = $this->tagKey($z);
            if (! $key) continue;
            $o = $this->toMin($z['opens']); $c = $this->toMin($z['closes']);
            if ($o === null || $c === null || $c <= $o) continue;
            $out[$key][] = [$o, $c];
        }
        foreach ($out as &$f) { sort($f); }
        return $out;
    }

    private function tagKey(array $z): ?string
    {
        $d = (string) ($z['day'] ?? '');
        if (isset(self::TAGE[$d])) return $d;
        $l = mb_strtolower(substr((string) ($z['label'] ?? $d), 0, 2));
        return self::KURZ[$l] ?? null;
    }

    private function toMin(?string $t): ?int
    {
        if (! $t || ! preg_match('/(\d{1,2})[:.](\d{2})/', $t, $m)) return null;
        return min(1439, (int) $m[1] * 60 + (int) $m[2]);
    }

    private function hhmm(int $min): string
    {
        return sprintf('%02d:%02d', intdiv($min, 60), $min % 60);
    }

    private function pct(int $min): float
    {
        $span = max(1, $this->axisEnd - $this->axisStart);
        return round(max(0, min(100, ($min - $this->axisStart) / $span * 100)), 2);
    }

    /** Live-Status: jetzt offen (bis …) bzw. geschlossen (öffnet …). */
    private function berechneStatus(array $byDay, string $heuteKey, int $nowMin): array
    {
        $keys = array_keys(self::TAGE);
        $heuteIdx = array_search($heuteKey, $keys, true);

        // Heute offen?
        foreach ($byDay[$heuteKey] ?? [] as $f) {
            if ($nowMin >= $f[0] && $nowMin < $f[1]) {
                $bald = $f[1] - $nowMin <= 60;
                return [
                    'offen' => true,
                    'text'  => ($bald ? 'Schließt bald' : 'Jetzt geöffnet').' · bis '.$this->hhmm($f[1]).' Uhr',
                    'kurz'  => 'Jetzt geöffnet',
                    'klasse' => $bald ? 'bald' : 'offen',
                ];
            }
        }
        // Nächste Öffnung suchen (heute später, sonst Folgetage).
        for ($i = 0; $i < 7; $i++) {
            $idx = ($heuteIdx + $i) % 7;
            $key = $keys[$idx];
            foreach ($byDay[$key] ?? [] as $f) {
                if ($i === 0 && $f[0] <= $nowMin) continue;
                $wann = $i === 0 ? 'heute' : ($i === 1 ? 'morgen' : self::TAGE[$key][0]);
                return [
                    'offen' => false,
                    'text'  => 'Geschlossen · öffnet '.$wann.' um '.$this->hhmm($f[0]).' Uhr',
                    'kurz'  => 'Geschlossen',
                    'klasse' => 'zu',
                ];
            }
        }
        return ['offen' => false, 'text' => 'Geschlossen', 'kurz' => 'Geschlossen', 'klasse' => 'zu'];
    }

    public function render()
    {
        return view('components.oeffnungszeiten');
    }
}
