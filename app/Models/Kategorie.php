<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategorie extends Model
{
    protected $table = 'kategorien';
    protected $guarded = [];

    public function ratgeberArtikel(): HasMany
    {
        return $this->hasMany(RatgeberArtikel::class, 'kategorie_id');
    }
}
