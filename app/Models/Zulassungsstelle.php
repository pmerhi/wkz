<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;

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

    /** Alle Ämter derselben Stadt (Ort + Bundesland). */
    public function stadtGruppe()
    {
        return static::query()
            ->where('ort', $this->ort)
            ->when($this->bundesland_id, fn ($q) => $q->where('bundesland_id', $this->bundesland_id));
    }

    /** Kanonische Stelle der Stadt = Träger der Hub-URL (Slug == slug(ort,de), sonst kürzester Primär-Slug). */
    public function kanonischeStelle(): self
    {
        $grp = $this->stadtGruppe()->get(['id', 'slug', 'parent_id']);
        $ortSlug = Str::slug((string) $this->ort, '-', 'de');
        $prim = $grp->whereNull('parent_id');

        return $prim->firstWhere('slug', $ortSlug)
            ?? $prim->sortBy(fn ($s) => strlen($s->slug))->first()
            ?? $grp->sortBy(fn ($s) => strlen($s->slug))->first()
            ?? $this;
    }

    /**
     * Kanonischer Pfad für interne Links: Stadt-Hub, wenn die Stadt mehrere Ämter
     * hat, sonst die eigene Detailseite. (Ein Query.)
     */
    public function getHubPfadAttribute(): string
    {
        if (! $this->ort) {
            return $this->pfad;
        }
        $grp = $this->stadtGruppe()->get(['id', 'slug', 'parent_id']);
        if ($grp->count() <= 1) {
            return $this->pfad;
        }
        $ortSlug = Str::slug((string) $this->ort, '-', 'de');
        $prim = $grp->whereNull('parent_id');
        $canon = $prim->firstWhere('slug', $ortSlug)
            ?? $prim->sortBy(fn ($s) => strlen($s->slug))->first()
            ?? $grp->sortBy(fn ($s) => strlen($s->slug))->first();

        return '/zulassungsstelle/'.($canon->slug ?? $this->slug);
    }

    /**
     * Karte slug → kanonischer Hub-Pfad für ALLE Stellen (ein Query, kein N+1).
     * Für Sitemap, Bundesland-Listing usw. Gleiche Regel wie hubPfad.
     */
    public static function hubPfadMap(): array
    {
        $map = [];
        $alle = static::query()->get(['id', 'slug', 'ort', 'bundesland_id', 'parent_id']);
        foreach ($alle->groupBy(fn ($s) => $s->ort.'|'.$s->bundesland_id) as $grp) {
            $ort = $grp->first()->ort;
            $canonSlug = $grp->first()->slug;
            if ($ort && $grp->count() > 1) {
                $ortSlug = Str::slug((string) $ort, '-', 'de');
                $prim = $grp->whereNull('parent_id');
                $canonSlug = ($prim->firstWhere('slug', $ortSlug)
                    ?? $prim->sortBy(fn ($s) => strlen($s->slug))->first()
                    ?? $grp->sortBy(fn ($s) => strlen($s->slug))->first())->slug;
            }
            foreach ($grp as $s) {
                $map[$s->slug] = '/zulassungsstelle/'.$canonSlug;
            }
        }

        return $map;
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

    /** Anzeigename ohne „Kfz-"-Präfix, z. B. „Zulassungsstelle Kaiserslautern (Stadt)". */
    public function anzeigeName(): string
    {
        return preg_replace('/^Kfz-\s*/i', '', (string) $this->name);
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
