<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Placement extends Model
{
    protected $table = 'placements';
    protected $guarded = [];

    protected $casts = [
        'aktiv' => 'boolean',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(Click::class);
    }
}
