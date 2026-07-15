<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Städte-/Wahrzeichenbild einer Gemeinde. Kandidaten werden per
 * `ortbilder:fetch` über Openverse (nur kommerziell nutzbare CC-Lizenzen)
 * recherchiert; im Admin wird je Gemeinde ein Hero- und zwei Footer-Bilder
 * ausgewählt. Attribution (TASL) wird mitgespeichert – siehe docs/bildnachweise.md.
 *
 * Auswahl (rolle = hero/footer/footer2) lädt das Bild automatisch lokal herunter;
 * Abwahl löscht die lokale Datei wieder (Event unten + OrtbildResource::waehle()).
 */
class Ortbild extends Model
{
    protected $table = 'ortbilder';
    protected $guarded = [];

    protected $casts = [
        'bearbeitet' => 'boolean',
        'width'      => 'integer',
        'height'     => 'integer',
    ];

    public const ROLLEN = ['kandidat', 'hero', 'footer', 'footer2', 'abgelehnt'];
    public const AUSGEWAEHLT = ['hero', 'footer', 'footer2'];

    protected static function booted(): void
    {
        // Wird eine Auswahl aufgehoben (hero/footer/footer2 → kandidat/abgelehnt),
        // die lokal heruntergeladene Datei entfernen.
        static::updated(function (Ortbild $bild) {
            if ($bild->wasChanged('rolle')
                && in_array($bild->getOriginal('rolle'), self::AUSGEWAEHLT)
                && ! in_array($bild->rolle, self::AUSGEWAEHLT)) {
                $bild->lokaleDateiLoeschen();
            }
        });

        // Beim Löschen des Datensatzes auch die Datei entfernen.
        static::deleting(fn (Ortbild $bild) => $bild->lokaleDateiLoeschen());
    }

    public function gemeinde(): BelongsTo
    {
        return $this->belongsTo(Gemeinde::class);
    }

    /** Anzeige-URL: lokal heruntergeladenes Bild bevorzugt, sonst Direktlink zur Quelle. */
    public function bildUrl(): ?string
    {
        if ($this->src) {
            return Str::startsWith($this->src, ['http://', 'https://', '//']) ? $this->src : asset($this->src);
        }
        return $this->external_url;
    }

    /**
     * Leichtgewichtige Vorschau-URL (Admin/Listen). Für Wikimedia wird der stabile
     * Special:FilePath-Endpoint mit fester Breite genutzt (der direkte /thumb/-Pfad
     * akzeptiert nur bestimmte Größen; der Openverse-Thumb-Endpoint liefert HTTP 424).
     */
    public function vorschauUrl(int $breite = 320): ?string
    {
        // Lokale Datei (nach Download) direkt; sonst skalierte Vorschau der Quelle.
        if ($this->src) return $this->bildUrl();
        return self::thumbFuer($this->external_url, $breite);
    }

    /** Erzeugt eine skalierte Vorschau-URL aus einer Original-Bild-URL. */
    public static function thumbFuer(?string $url, int $breite = 320): ?string
    {
        if (! $url) return null;
        if (Str::contains($url, 'upload.wikimedia.org')) {
            $datei = basename(parse_url($url, PHP_URL_PATH) ?? '');
            if ($datei !== '') {
                return "https://commons.wikimedia.org/wiki/Special:FilePath/{$datei}?width={$breite}";
            }
        }
        return $url; // andere Provider (z. B. rawpixel) liefern direkt nutzbare URLs
    }

    public function altText(): string
    {
        return $this->titel
            ?: trim(($this->wahrzeichen ? $this->wahrzeichen.' in ' : 'Ansicht von ').($this->gemeinde?->name ?? ''));
    }

    /** Erwarteter lokaler Pfad (relativ zu public/) für die aktuelle Rolle. */
    public function lokalerPfad(): string
    {
        $slug = $this->gemeinde?->slug ?? ('gemeinde-'.$this->gemeinde_id);
        $ext  = strtolower(pathinfo(parse_url((string) $this->external_url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)) ?: 'jpg';
        return "img/orte/{$slug}-{$this->rolle}.{$ext}";
    }

    /**
     * Lädt das Bild in web-tauglicher Größe nach public/img/orte/ und setzt src.
     * Hero größer (1600px) als Footer (1000px). Gibt true bei Erfolg zurück.
     */
    public function herunterladen(bool $force = false): bool
    {
        if (! $this->external_url || ! in_array($this->rolle, self::AUSGEWAEHLT)) {
            return false;
        }
        $this->loadMissing('gemeinde');
        $pfad = $this->lokalerPfad();
        if (! $force && $this->src === $pfad && is_file(public_path($pfad))) {
            return true; // schon vorhanden
        }

        // Web-taugliche Größe laden statt Original; Wikimedia verlangt einen UA mit Kontakt.
        $breite = $this->rolle === 'hero' ? 1600 : 1000;
        $resp = Http::timeout(60)->retry(2, 800, throw: false)
            ->withHeaders(['User-Agent' => 'WKR-Portal/1.0 (+'.config('app.url').'; kontakt: patrick@merhi.de)'])
            ->get(self::thumbFuer($this->external_url, $breite));
        if (! $resp->ok()) {
            return false;
        }

        $ziel = public_path('img/orte');
        if (! is_dir($ziel)) {
            mkdir($ziel, 0755, true);
        }
        file_put_contents(public_path($pfad), $resp->body());

        // Gespeicherte Maße an die tatsächlich geladene Datei angleichen (CLS).
        $attr = ['src' => $pfad];
        if ($masse = @getimagesize(public_path($pfad))) {
            $attr['width'] = $masse[0];
            $attr['height'] = $masse[1];
        }
        $this->forceFill($attr)->saveQuietly();
        return true;
    }

    /** Entfernt die lokal heruntergeladene Datei und leert src. */
    public function lokaleDateiLoeschen(): void
    {
        if ($this->src && ! Str::startsWith($this->src, ['http://', 'https://', '//'])) {
            $voll = public_path($this->src);
            if (is_file($voll)) {
                @unlink($voll);
            }
        }
        if ($this->src !== null) {
            $this->forceFill(['src' => null])->saveQuietly();
        }
    }
}
