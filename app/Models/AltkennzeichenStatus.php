<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AltkennzeichenStatus extends Model
{
    protected $table = 'altkennzeichen_status';
    protected $guarded = [];

    protected $casts = [
        'status'      => 'integer',
        'in_klammern' => 'boolean',
    ];
}
