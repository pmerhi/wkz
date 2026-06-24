<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * "Wusstest du?"-Fakten – dieselbe Tabelle wie EnrichmentIdea, aber fest auf
 * die Kategorie "Wusstest" gescoped. So bekommen die Fakten im Admin einen
 * eigenen Menüpunkt und verstopfen die normalen Ideen-Funde nicht.
 */
class WusstestFakt extends EnrichmentIdea
{
    protected $table = 'enrichment_ideas';

    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope('wusstest', fn (Builder $q) => $q->where('kategorie', 'Wusstest'));
        static::creating(fn (WusstestFakt $m) => $m->kategorie = 'Wusstest');
    }
}
