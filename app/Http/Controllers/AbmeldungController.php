<?php

namespace App\Http\Controllers;

use App\Models\AbEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AbmeldungController extends Controller
{
    /** Serverseitig getrackte Weiterleitung zum eigenen Abmeldeservice (adblock-feste Conversion). */
    public function __invoke(Request $request): RedirectResponse
    {
        $label    = $request->query('label') ? substr((string) $request->query('label'), 0, 191) : null;
        $campaign = substr((string) $request->query('c', 'abmeldung'), 0, 64);

        // Bots nicht als Conversion zählen (einfacher Filter).
        if (! $this->istBot($request)) {
            AbEvent::create([
                'experiment' => 'abmeldung',
                'variant'    => 'a',
                'event'      => 'conversion',
                'label'      => $label,
                'campaign'   => $campaign,
                'created_at' => now(),
            ]);
        }

        $ziel = config('abmeldung.url').'?'.http_build_query([
            'cId'          => config('abmeldung.cid'),
            'gclid'        => (string) $request->query('gclid', ''),
            'utm_source'   => 'portal',
            'utm_medium'   => 'cta',
            'utm_campaign' => $campaign,
        ]);

        return redirect()->away($ziel, 302);
    }

    private function istBot(Request $request): bool
    {
        return (bool) preg_match('/bot|crawl|spider|slurp|bingpreview|facebookexternalhit/i', (string) $request->userAgent());
    }
}
