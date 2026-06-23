<?php

namespace App\Http\Controllers;

use App\Models\Bundesland;
use App\Models\KennzeichenKuerzel;
use App\Models\RatgeberArtikel;
use App\Models\Zulassungsstelle;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    private const TYPES = ['static', 'stellen', 'kennzeichen', 'bundesland', 'ratgeber'];

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
            'stellen'     => Zulassungsstelle::indexable()->orderBy('name')->get()
                                ->map(fn ($s) => ['loc' => url('/zulassungsstelle/'.$s->slug), 'lastmod' => $s->updated_at?->toAtomString()])->all(),
            'kennzeichen' => KennzeichenKuerzel::indexable()->orderBy('code')->get()
                                ->map(fn ($k) => ['loc' => url('/kennzeichen/'.$k->slug), 'lastmod' => $k->updated_at?->toAtomString()])->all(),
            'bundesland'  => Bundesland::has('zulassungsstellen')->orderBy('name')->get()
                                ->map(fn ($b) => ['loc' => url('/bundesland/'.$b->slug), 'lastmod' => $b->updated_at?->toAtomString()])->all(),
            'ratgeber'    => RatgeberArtikel::whereNotNull('published_at')->orderBy('slug')->get()
                                ->map(fn ($a) => ['loc' => url('/ratgeber/'.$a->slug), 'lastmod' => $a->updated_at?->toAtomString()])->all(),
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

    private function staticUrls(): array
    {
        return [
            ['loc' => url('/')],
            ['loc' => url('/zulassungsstelle')],
            ['loc' => url('/kennzeichen')],
            ['loc' => url('/altkennzeichen')],
            ['loc' => url('/ratgeber')],
            ['loc' => url('/ueber-uns')],
        ];
    }
}
