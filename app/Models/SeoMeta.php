<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMeta extends Model
{
    protected $table = 'seo_meta';
    protected $guarded = [];

    protected $casts = [
        'noindex' => 'boolean',
    ];

    public function metable(): MorphTo
    {
        return $this->morphTo();
    }
}
