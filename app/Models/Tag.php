<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $table = 'tags';
    protected $guarded = [];

    public function ratgeberArtikel(): BelongsToMany
    {
        return $this->belongsToMany(RatgeberArtikel::class, 'ratgeber_artikel_tag');
    }
}
