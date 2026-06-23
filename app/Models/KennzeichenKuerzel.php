<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class KennzeichenKuerzel extends Model
{
    protected $table = 'kennzeichen_kuerzel';
    protected $guarded = [];

    protected $casts = [
        'ist_altkennzeichen' => 'boolean',
    ];

    /** Indexierbar, wenn eine Bedeutung vorliegt oder eine Stelle zugeordnet ist. */
    public function scopeIndexable($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('bedeutung')->orHas('zulassungsstellen');
        });
    }

    /** Wieder eingeführte Altkennzeichen (Kennzeichenliberalisierung ab 2012). */
    public function scopeAltkennzeichen($query)
    {
        return $query->where('ist_altkennzeichen', true);
    }

    public function zulassungsstellen(): BelongsToMany
    {
        return $this->belongsToMany(
            Zulassungsstelle::class,
            'kennzeichen_kuerzel_zulassungsstelle',
            'kennzeichen_kuerzel_id',
            'zulassungsstelle_id'
        );
    }

    public function kreise(): BelongsToMany
    {
        return $this->belongsToMany(
            Kreis::class,
            'kennzeichen_kuerzel_kreis',
            'kennzeichen_kuerzel_id',
            'kreis_id'
        );
    }
}
