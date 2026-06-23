<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Zulassungsstelle extends Model
{
    protected $table = 'zulassungsstellen';
    protected $guarded = [];

    protected $casts = [
        'oeffnungszeiten'  => 'array',
        'last_imported_at' => 'datetime',
        'lat'              => 'decimal:7',
        'lng'              => 'decimal:7',
    ];

    /** Land-Segment der URL (Bundesland-Slug, sonst „deutschland"). */
    public function getLandSlugAttribute(): string
    {
        return $this->bundesland?->slug ?: 'deutschland';
    }

    /** Pfad der Detailseite: /zulassungsstelle/{land}/{ort}. */
    public function getPfadAttribute(): string
    {
        return '/zulassungsstelle/'.$this->land_slug.'/'.$this->slug;
    }

    /** Absolute URL der Detailseite. */
    public function url(): string
    {
        return url($this->pfad);
    }

    /** Genug Substanz für Indexierung? (nicht nur Name + Geo) */
    public function getIsIndexableAttribute(): bool
    {
        return $this->strasse || $this->plz || $this->oeffnungszeiten
            || $this->telefon || $this->termin_url;
    }

    /** Query-Scope: nur indexierbare (substanzreiche) Stellen. */
    public function scopeIndexable($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('strasse')->orWhereNotNull('plz')
              ->orWhereNotNull('oeffnungszeiten')->orWhereNotNull('telefon')
              ->orWhereNotNull('termin_url');
        });
    }

    public function bundesland(): BelongsTo
    {
        return $this->belongsTo(Bundesland::class);
    }

    public function gemeinde(): BelongsTo
    {
        return $this->belongsTo(Gemeinde::class);
    }

    public function kreis(): BelongsTo
    {
        return $this->belongsTo(Kreis::class);
    }

    public function kennzeichenKuerzel(): BelongsToMany
    {
        return $this->belongsToMany(
            KennzeichenKuerzel::class,
            'kennzeichen_kuerzel_zulassungsstelle',
            'zulassungsstelle_id',
            'kennzeichen_kuerzel_id'
        );
    }

    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'metable');
    }
}
