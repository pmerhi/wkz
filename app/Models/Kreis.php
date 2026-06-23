<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kreis extends Model
{
    protected $table = 'kreise';
    protected $guarded = [];

    public function bundesland(): BelongsTo
    {
        return $this->belongsTo(Bundesland::class);
    }

    public function gemeinden(): HasMany
    {
        return $this->hasMany(Gemeinde::class);
    }

    public function zulassungsstellen(): HasMany
    {
        return $this->hasMany(Zulassungsstelle::class);
    }

    public function kennzeichenKuerzel(): BelongsToMany
    {
        return $this->belongsToMany(
            KennzeichenKuerzel::class,
            'kennzeichen_kuerzel_kreis',
            'kreis_id',
            'kennzeichen_kuerzel_id'
        );
    }
}
