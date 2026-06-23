<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RatgeberThema extends Model
{
    protected $table = 'ratgeber_thema';
    protected $guarded = [];

    protected $casts = [
        'keywords'      => 'array',
        'interne_links' => 'array',
        'vorhanden'     => 'boolean',
    ];
}
