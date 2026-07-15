<?php

namespace App\Http\Controllers;

use App\Models\Bundesland;
use App\Models\Gemeinde;
use App\Models\KennzeichenKuerzel;
use App\Models\RatgeberArtikel;
use App\Models\Zulassungsstelle;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    private const TYPES = ['static', 'stellen', 'kennzeichen', 'ort', 'bundesland', 'ratgeber'];

    /** Sitemap-Index, verweist auf die Kind-Sitemaps je Typ. */
    public function index(): Response
    {
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach (self::TYPES as $typ) {
            $xml .= '  <sitemap><loc>'.e(url("/sitemap-{$typ}.xml")).'</loc></sitemap>'."\n";
        }
        $xml .= '</sitemapindex>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /** Kind-Sitemap eines Typs (nur indexierbare URLs). */
    public function child(string $typ): Response
    {
        $urls = match ($typ) {
            'static'      => $this->staticUrls(),
            'stellen'     => $this->stellenUrls(),
            'kennzeichen' => KennzeichenKuerzel::indexable()->orderBy('code')->get()
                                ->map(fn ($k) => ['loc' => url('/kennzeichen/'.$k->slug), 'lastmod' => $k->updated_at?->toAtomString()])->all(),
            'ort'         => Gemeinde::whereNotNull('slug')
                                ->whereHas('kreis.kennzeichenKuerzel')
                                ->where(fn ($q) => $q
                                    ->whereHas('zulassungsstellen', fn ($s) => $s->whereNull('parent_id'))
                                    ->orWhereHas('kreis.zulassungsstellen', fn ($s) => $s->whereNull('parent_id')))
                                ->orderBy('name')->get(['id', 'slug', 'updated_at'])
                                ->map(fn ($g) => ['loc' => url('/wunschkennzeichen/'.$g->slug), 'lastmod' => $g->updated_at?->toAtomString()])->all(),
            // Stadtstaaten (Land-Slug == Hauptstelle-Slug) NICHT listen – ihre URL
            // /zulassungsstelle/{slug} ist die Stelle (steht schon in der stellen-Sitemap).
            'bundesland'  => Bundesland::has('zulassungsstellen')
                                ->whereNotIn('slug', Zulassungsstelle::whereNull('parent_id')->pluck('slug'))
                                ->orderBy('name')->get()
                                ->map(fn ($b) => ['loc' => url('/zulassungsstelle/'.$b->slug), 'lastmod' => $b->updated_at?->toAtomString()])->all(),
            'ratgeber'    => RatgeberArtikel::whereNotNull('published_at')->orderBy('slug')->get()
                                ->map(fn ($a) => ['loc' => url('/kfz-ratgeber/'.$a->slug), 'lastmod' => $a->updated_at?->toAtomString()])->all(),
            default       => [],
        };

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>'.e($u['loc']).'</loc>';
            if (! empty($u['lastmod'])) {
                $xml .= '<lastmod>'.e($u['lastmod']).'</lastmod>';
            }
            $xml .= '</url>'."\n";
        }
        $xml .= '</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Zulassungsstellen-URLs: jede indexierbare Stelle auf ihre kanonische Stadt-Hub-URL
     * abgebildet und dedupliziert (Mehrfach-Ämter-Städte erscheinen einmal).
     */
    private function stellenUrls(): array
    {
        $hubPfade = Zulassungsstelle::hubPfadMap();
        $urls = [];
        $gesehen = [];
        foreach (Zulassungsstelle::indexable()->orderBy('name')->get(['slug', 'updated_at']) as $s) {
            $pfad = $hubPfade[$s->slug] ?? '/zulassungsstelle/'.$s->slug;
            if (isset($gesehen[$pfad])) continue;
            $gesehen[$pfad] = true;
            $urls[] = ['loc' => url($pfad), 'lastmod' => $s->updated_at?->toAtomString()];
        }

        return $urls;
    }

    private function staticUrls(): array
    {
        $urls = [
            ['loc' => url('/')],
            ['loc' => url('/zulassungsstelle')],
            ['loc' => url('/kennzeichen')],
            ['loc' => url('/kennzeichen/ort')],
            ['loc' => url('/altkennzeichen')],
            ['loc' => url('/kennzeichen-quiz')],
            ['loc' => url('/kfz-ratgeber')],
            ['loc' => url('/formulare')],
            ['loc' => url('/ueber-uns')],
        ];

        // Ort-Hub je Bundesland (nur Länder mit indexierbaren Ort-Seiten).
        $laender = Bundesland::whereHas('gemeinden', fn ($q) => $q->whereNotNull('slug')->whereHas('kreis.kennzeichenKuerzel'))
            ->orderBy('name')->get(['slug']);
        foreach ($laender as $b) {
            $urls[] = ['loc' => url('/kennzeichen/ort/bundesland/'.$b->slug)];
        }

        return $urls;
    }
}
