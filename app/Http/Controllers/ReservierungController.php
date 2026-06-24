<?php

namespace App\Http\Controllers;

use App\Models\AbEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReservierungController extends Controller
{
    /** Serverseitig getrackte Weiterleitung zum externen Reservierungs-Funnel (adblock-feste Conversion). */
    public function __invoke(Request $request): RedirectResponse
    {
        $variant  = substr((string) $request->query('v', 'a'), 0, 16);
        $label    = $request->query('label') ? substr((string) $request->query('label'), 0, 191) : null;
        $campaign = substr((string) $request->query('c', 'cta'), 0, 64);

        // Bots nicht als Conversion zählen (einfacher Filter).
        if (! $this->istBot($request)) {
            AbEvent::create([
                'experiment' => 'cta_text',
                'variant'    => $variant,
                'event'      => 'conversion',
                'label'      => $label,
                'campaign'   => $campaign,
                'created_at' => now(),
            ]);
        }

        $ziel = config('portal.reservation_url').'?'.http_build_query([
            'utm_source'   => 'portal',
            'utm_medium'   => 'cta',
            'utm_campaign' => $campaign,
            'v'            => $variant,
        ]);

        return redirect()->away($ziel, 302);
    }

    private function istBot(Request $request): bool
    {
        return (bool) preg_match('/bot|crawl|spider|slurp|bingpreview|facebookexternalhit/i', (string) $request->userAgent());
    }
}
