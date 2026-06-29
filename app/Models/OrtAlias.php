<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrtAlias extends Model
{
    protected $table = 'ort_aliasse';

    protected $fillable = ['slug', 'ziel', 'quelle', 'geprueft'];

    protected $casts = ['geprueft' => 'boolean'];
}
