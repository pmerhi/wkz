<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtraktKuerzel extends Model
{
    protected $table = 'extrakt_kuerzel';
    protected $guarded = [];

    public function wettbewerber(): BelongsTo { return $this->belongsTo(Wettbewerber::class); }
    public function kreis(): BelongsTo { return $this->belongsTo(Kreis::class); }
}
