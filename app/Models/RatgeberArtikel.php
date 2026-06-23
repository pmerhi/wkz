<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class RatgeberArtikel extends Model
{
    protected $table = 'ratgeber_artikel';
    protected $guarded = [];

    protected $casts = [
        'stand_datum'  => 'date',
        'published_at' => 'datetime',
    ];

    public function kategorie(): BelongsTo
    {
        return $this->belongsTo(Kategorie::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'ratgeber_artikel_tag');
    }

    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'metable');
    }
}
