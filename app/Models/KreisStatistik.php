<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KreisStatistik extends Model
{
    protected $table = 'kreis_statistik';
    protected $guarded = [];

    protected $casts = [
        'einwohner'   => 'integer',
        'flaeche_km2' => 'float',
        'kfz_bestand' => 'integer',
        'pkw_bestand' => 'integer',
        'elektro_pkw' => 'integer',
        'pkw_dichte'  => 'float',
        'stand_jahr'  => 'integer',
        'ladepunkte_normal'  => 'integer',
        'ladepunkte_schnell' => 'integer',
        'auspendler_quote'   => 'float',
        'einpendler_quote'   => 'float',
        'pendler_saldo'      => 'integer',
    ];

    public function kreis(): BelongsTo
    {
        return $this->belongsTo(Kreis::class);
    }

    /** Es liegen überhaupt anzeigbare Daten vor. */
    public function hatDaten(): bool
    {
        return $this->einwohner || $this->kfz_bestand || $this->pkw_bestand || $this->flaeche_km2
            || $this->ladepunkte_normal || $this->ladepunkte_schnell;
    }
}
