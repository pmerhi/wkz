<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bundesland extends Model
{
    protected $table = 'bundeslaender';
    protected $guarded = [];

    public function zulassungsstellen(): HasMany
    {
        return $this->hasMany(Zulassungsstelle::class);
    }

    public function gemeinden(): HasMany
    {
        return $this->hasMany(Gemeinde::class);
    }
}
