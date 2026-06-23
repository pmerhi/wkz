<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrawlSeite extends Model
{
    protected $table = 'crawl_seite';
    protected $guarded = [];

    protected $casts = [
        'abgerufen_am' => 'datetime',
    ];

    public function wettbewerber(): BelongsTo
    {
        return $this->belongsTo(Wettbewerber::class);
    }
}
