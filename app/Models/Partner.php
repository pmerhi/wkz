<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    protected $table = 'partner';
    protected $guarded = [];

    protected $casts = [
        'aktiv' => 'boolean',
    ];

    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class);
    }
}
