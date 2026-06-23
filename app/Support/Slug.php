<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Slug-Erzeugung mit deutscher Umlaut-Transliteration (ä→ae, ö→oe, ü→ue, ß→ss) —
 * entspricht der SEO-Konvention und den Wettbewerbern (z. B. wuerzburg, muenchen,
 * baden-wuerttemberg), nicht Laravels Standard (ü→u).
 */
class Slug
{
    private const MAP = ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue', 'ß' => 'ss'];

    /** Umlaute transliterieren, dann Slug bilden. */
    public static function de(?string $s): string
    {
        return Str::slug(self::umlaute((string) $s));
    }

    /** Nur Umlaute transliterieren (z. B. vor weiterer Verarbeitung). */
    public static function umlaute(string $s): string
    {
        return strtr($s, self::MAP);
    }
}
