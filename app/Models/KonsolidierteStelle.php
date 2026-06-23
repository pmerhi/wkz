<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KonsolidierteStelle extends Model
{
    protected $table = 'konsolidierte_stelle';
    protected $guarded = [];

    protected $casts = [
        'oeffnungszeiten' => 'array',
        'quellen'         => 'array',
        'quellen_anzahl'  => 'integer',
    ];

    public function gemeinde(): BelongsTo { return $this->belongsTo(Gemeinde::class); }
    public function kreis(): BelongsTo { return $this->belongsTo(Kreis::class); }
}
