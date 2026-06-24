<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizScore extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    protected $casts = [
        'score'      => 'integer',
        'richtige'   => 'integer',
        'created_at' => 'datetime',
    ];

    /** Top-Liste für einen Zeitraum: 'tag' | 'woche' | 'monat' | 'jahr' | 'gesamt'. */
    public static function topliste(string $zeitraum, int $limit = 10)
    {
        $q = static::query()->orderByDesc('score')->orderBy('created_at')->limit($limit);

        $ab = match ($zeitraum) {
            'tag'   => now()->startOfDay(),
            'woche' => now()->startOfWeek(),
            'monat' => now()->startOfMonth(),
            'jahr'  => now()->startOfYear(),
            default => null,
        };
        if ($ab) {
            $q->where('created_at', '>=', $ab);
        }

        return $q->get(['name', 'score', 'richtige', 'created_at']);
    }
}
