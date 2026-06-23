<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtraktZulassungsstelle extends Model
{
    protected $table = 'extrakt_zulassungsstelle';
    protected $guarded = [];

    protected $casts = [
        'oeffnungszeiten' => 'array',
        'roh'             => 'array',
    ];

    public function wettbewerber(): BelongsTo { return $this->belongsTo(Wettbewerber::class); }
    public function gemeinde(): BelongsTo { return $this->belongsTo(Gemeinde::class); }
    public function kreis(): BelongsTo { return $this->belongsTo(Kreis::class); }
}
