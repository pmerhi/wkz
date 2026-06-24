<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EnrichmentIdea extends Model
{
    protected $guarded = [];

    protected $casts = [
        'seo_wert' => 'integer',
        'relevanz' => 'integer',
        'aufwand'  => 'integer',
        'score'    => 'float',
    ];

    public const STATUS = ['neu', 'geprueft', 'umsetzen', 'abgelehnt', 'umgesetzt'];

    protected static function booted(): void
    {
        // Score automatisch berechnen: Wert × Relevanz ÷ Aufwand.
        static::saving(function (EnrichmentIdea $idea) {
            $aufwand = max(1, (int) $idea->aufwand);
            $idea->score = round(((int) $idea->seo_wert * (int) $idea->relevanz) / $aufwand, 2);
            if (! $idea->fingerprint) {
                $idea->fingerprint = substr(sha1(Str::lower(trim((string) $idea->titel))), 0, 64);
            }
        });
    }
}
