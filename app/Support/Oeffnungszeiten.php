<?php

namespace App\Support;

/**
 * Gemeinsame Helfer für strukturierte Öffnungszeiten
 * (Format: [['day'=>EN,'label'=>DE,'opens'=>'HH:MM','closes'=>'HH:MM'], …]).
 */
class Oeffnungszeiten
{
    public const TAGE = [
        'Monday' => 'Mo', 'Tuesday' => 'Di', 'Wednesday' => 'Mi', 'Thursday' => 'Do',
        'Friday' => 'Fr', 'Saturday' => 'Sa', 'Sunday' => 'So',
    ];

    private const KURZ = [
        'mo' => 'Monday', 'di' => 'Tuesday', 'mi' => 'Wednesday', 'do' => 'Thursday',
        'fr' => 'Friday', 'sa' => 'Saturday', 'so' => 'Sunday',
    ];

    /** Exakte Dubletten entfernen, Tag normalisieren, nach Wochentag + Startzeit sortieren. */
    public static function kanonisieren(array $flat): array
    {
        $order = array_flip(array_keys(self::TAGE));
        $seen = [];
        $out = [];
        foreach ($flat as $e) {
            if (! is_array($e) || ! isset($e['opens'], $e['closes'])) {
                continue;
            }
            $day = self::tagKey($e);
            if (! $day) {
                continue;
            }
            $opens = substr((string) $e['opens'], 0, 5);
            $closes = substr((string) $e['closes'], 0, 5);
            $key = $day.'|'.$opens.'|'.$closes;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = ['day' => $day, 'label' => self::TAGE[$day], 'opens' => $opens, 'closes' => $closes];
        }
        usort($out, fn ($a, $b) => [$order[$a['day']] ?? 9, $a['opens']] <=> [$order[$b['day']] ?? 9, $b['opens']]);

        return $out;
    }

    /** Flache Liste → ['Mo' => ['08:00–12:00', …], …] für alle 7 Tage. */
    public static function woche(array $flat): array
    {
        $woche = array_fill_keys(array_values(self::TAGE), []);
        foreach (self::kanonisieren($flat) as $e) {
            $woche[$e['label']][] = $e['opens'].'–'.$e['closes'];
        }

        return $woche;
    }

    private static function tagKey(array $e): ?string
    {
        $d = (string) ($e['day'] ?? '');
        if (isset(self::TAGE[$d])) {
            return $d;
        }
        $l = mb_strtolower(substr((string) ($e['label'] ?? $d), 0, 2));

        return self::KURZ[$l] ?? null;
    }
}
