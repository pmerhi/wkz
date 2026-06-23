<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wettbewerber extends Model
{
    protected $table = 'wettbewerber';
    protected $guarded = [];

    protected $casts = [
        'rang' => 'integer',
    ];

    public function crawlSeiten(): HasMany
    {
        return $this->hasMany(CrawlSeite::class);
    }

    public function extrakte(): HasMany
    {
        return $this->hasMany(ExtraktZulassungsstelle::class);
    }
}
