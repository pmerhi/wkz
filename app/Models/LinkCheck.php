<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ergebnis der Prüfung eines externen Links (Command `links:check`).
 */
class LinkCheck extends Model
{
    protected $table = 'link_checks';
    protected $guarded = [];

    protected $casts = [
        'ok'          => 'boolean',
        'status'      => 'integer',
        'geprueft_at' => 'datetime',
    ];

    public static function hashFor(string $url): string
    {
        return sha1($url);
    }
}
