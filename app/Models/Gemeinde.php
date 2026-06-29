<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gemeinde extends Model
{
    protected $table = 'gemeinden';
    protected $guarded = [];

    public function kreis(): BelongsTo
    {
        return $this->belongsTo(Kreis::class);
    }

    public function bundesland(): BelongsTo
    {
        return $this->belongsTo(Bundesland::class);
    }

    public function zulassungsstellen(): HasMany
    {
        return $this->hasMany(Zulassungsstelle::class);
    }

    /** Kennzeichen-Kürzel der Gemeinde (über den Kreis). */
    public function kennzeichenKuerzel()
    {
        return $this->kreis ? $this->kreis->kennzeichenKuerzel : collect();
    }

    /** Für die Gemeinde zuständige (Primär-)Zulassungsstelle: eigene, sonst die des Kreises. */
    public function zustaendigeStelle(): ?Zulassungsstelle
    {
        return Zulassungsstelle::whereNull('parent_id')
            ->where(fn ($q) => $q->where('gemeinde_id', $this->id)->orWhere('kreis_id', $this->kreis_id))
            ->orderByRaw('CASE WHEN gemeinde_id = ? THEN 0 ELSE 1 END', [$this->id])
            ->first();
    }

    /** Kanonischer Pfad der Ort-/Wunschkennzeichen-Seite (wie altes Projekt: /wunschkennzeichen/{ort}/). */
    public function pfad(): string
    {
        return '/wunschkennzeichen/'.$this->slug;
    }

    public function url(): string
    {
        return url($this->pfad());
    }
}
