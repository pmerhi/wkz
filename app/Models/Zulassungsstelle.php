<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Zulassungsstelle extends Model
{
    protected $table = 'zulassungsstellen';
    protected $guarded = [];

    protected $casts = [
        'oeffnungszeiten'             => 'array',
        'last_imported_at'            => 'datetime',
        'oeffnungszeiten_geprueft_at' => 'datetime',
        'oeffnungszeiten_geaendert'   => 'boolean',
        'lat'                         => 'decimal:7',
        'lng'                         => 'decimal:7',
    ];

    /** Land-Segment der URL (Bundesland-Slug, sonst „deutschland"). */
    public function getLandSlugAttribute(): string
    {
        return $this->bundesland?->slug ?: 'deutschland';
    }

    /** Pfad der Detailseite: /zulassungsstelle/{ort}/ (einsegmentig, wie altes Projekt). */
    public function getPfadAttribute(): string
    {
        return '/zulassungsstelle/'.$this->slug;
    }

    /** Absolute URL der Detailseite. */
    public function url(): string
    {
        return url($this->pfad);
    }

    /** Genug Substanz für Indexierung? (nicht nur Name + Geo; keine Kind-Stelle) */
    public function getIsIndexableAttribute(): bool
    {
        return ! $this->parent_id && ($this->strasse || $this->plz || $this->oeffnungszeiten
            || $this->telefon || $this->termin_url);
    }

    /** Query-Scope: nur indexierbare Primär-Stellen (substanzreich, kein Kind). */
    public function scopeIndexable($query)
    {
        return $query->whereNull('parent_id')->where(function ($q) {
            $q->whereNotNull('strasse')->orWhereNotNull('plz')
              ->orWhereNotNull('oeffnungszeiten')->orWhereNotNull('telefon')
              ->orWhereNotNull('termin_url');
        });
    }

    /** Nur Primär-Ämter (eigene Seite). */
    public function scopePrimaer($query)
    {
        return $query->whereNull('parent_id');
    }

    /** Primär-Amt (bei Kind-Stellen). */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** Weitere Zulassungsstellen am Ort (Außenstellen). */
    public function kinder(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
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
