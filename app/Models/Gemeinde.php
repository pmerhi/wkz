<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gemeinde extends Model
{
    protected $table = 'gemeinden';
    protected $guarded = [];

    public function kreis(): BelongsTo
    {
        return $this->belongsTo(Kreis::class);
    }

    public function bundesland(): BelongsTo
    {
        return $this->belongsTo(Bundesland::class);
    }

    public function zulassungsstellen(): HasMany
    {
        return $this->hasMany(Zulassungsstelle::class);
    }
}
