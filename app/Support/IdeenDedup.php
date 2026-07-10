<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Dedup-Helfer für Enrichment-Ideen.
 *
 * Zwei Stufen:
 *  1. exakter Fingerprint (sha1 des kleingeschriebenen Titels) – schnell, identische Titel.
 *  2. Ähnlichkeit über normalisierte Token-Mengen (Jaccard) – fängt Umformulierungen
 *     wie "pro" vs "je", "(UGC)"-Suffixe, Umlaute oder einzelne Zusatzwörter.
 */
class IdeenDedup
{
    /** Standard-Schwelle: ab dieser Jaccard-Ähnlichkeit gilt eine Idee als Doppler. */
    public const SCHWELLE = 0.6;

    /** Füllwörter, die für die inhaltliche Ähnlichkeit nichts beitragen. */
    private const STOP = [
        'der', 'die', 'das', 'und', 'oder', 'je', 'pro', 'ein', 'eine', 'einer', 'einen',
        'zur', 'zum', 'fuer', 'fur', 'im', 'in', 'an', 'am', 'auf', 'mit', 'von', 'vom',
        'des', 'den', 'dem', 'als', 'aus', 'bei', 'nach', 'ueber', 'uber', 'mehr', 'wir',
        'ist', 'sind', 'wie', 'wer', 'was', 'warum', 'auch', 'so', 'du', 'de', 'pro',
        'the', 'of', 'to', 'and', 'or', 'a', 'wusstest',
    ];

    /** Exakter Fingerprint (rückwärtskompatibel zur bisherigen Logik). */
    public static function fingerprint(string $titel): string
    {
        return substr(sha1(Str::lower(trim($titel))), 0, 64);
    }

    /**
     * Normalisierte, sortierte, eindeutige Token-Menge eines Titels.
     *
     * @return list<string>
     */
    public static function tokens(string $titel): array
    {
        $s = mb_strtolower(trim($titel));
        $s = strtr($s, [
            'ä' => 'a', 'ö' => 'o', 'ü' => 'u', 'ß' => 'ss',
            '&amp;' => ' ', '&' => ' ', '-' => ' ', '/' => ' ',
        ]);
        $s = preg_replace('/[^a-z0-9 ]+/u', ' ', $s) ?? '';

        $stop = array_flip(self::STOP);
        $tokens = array_filter(
            preg_split('/\s+/', trim($s)) ?: [],
            fn (string $w): bool => mb_strlen($w) > 2 && ! isset($stop[$w])
        );

        $tokens = array_values(array_unique($tokens));
        sort($tokens);

        return $tokens;
    }

    /**
     * Jaccard-Ähnlichkeit zweier Token-Mengen (0..1).
     *
     * @param  list<string>  $a
     * @param  list<string>  $b
     */
    public static function jaccard(array $a, array $b): float
    {
        if ($a === [] || $b === []) {
            return 0.0;
        }
        $schnitt = count(array_intersect($a, $b));
        $union = count(array_unique(array_merge($a, $b)));

        return $union > 0 ? $schnitt / $union : 0.0;
    }

    /**
     * Sucht im Bestand die ähnlichste Idee zu den gegebenen Tokens.
     *
     * @param  list<string>  $tokens
     * @param  iterable<array{id:int|string,titel:string,tokens:list<string>}>  $bestand
     * @return array{id:int|string,titel:string,score:float}|null  null, wenn nichts >= Schwelle
     */
    public static function aehnlichste(array $tokens, iterable $bestand, float $schwelle = self::SCHWELLE): ?array
    {
        $best = null;
        foreach ($bestand as $kandidat) {
            $score = self::jaccard($tokens, $kandidat['tokens']);
            if ($score >= $schwelle && ($best === null || $score > $best['score'])) {
                $best = ['id' => $kandidat['id'], 'titel' => $kandidat['titel'], 'score' => $score];
            }
        }

        return $best;
    }
}
