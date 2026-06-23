<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EigeneStelle extends Model
{
    protected $table = 'eigene_stelle';
    protected $guarded = [];

    protected $casts = [
        'oeffnungszeiten' => 'array',
        'fetched_at'      => 'datetime',
    ];
}
