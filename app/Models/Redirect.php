<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $table = 'redirects';
    protected $guarded = [];

    protected $casts = [
        'aktiv'       => 'boolean',
        'status_code' => 'integer',
    ];
}
