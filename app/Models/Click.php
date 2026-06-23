<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Click extends Model
{
    protected $table = 'clicks';
    protected $guarded = [];
    public $timestamps = false;

    protected $casts = [
        'clicked_at' => 'datetime',
    ];

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }
}
