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

        $query = [
            'cId'          => config('portal.reservation_cid'),
            'gclid'        => (string) $request->query('gclid', ''),
            'utm_source'   => 'portal',
            'utm_medium'   => 'cta',
            'utm_campaign' => $campaign,
            'v'            => $variant,
        ];

        // Wunschkombi aus dem Generator sauber durchreichen (Format des Reservierungs-Portals).
        $symbol  = strtoupper(preg_replace('/[^A-Za-zÄÖÜäöü?]/u', '', (string) $request->query('symbol', '')));
        $letters = strtolower(preg_replace('/[^A-Za-z?]/', '', (string) $request->query('letters', '')));
        $numbers = preg_replace('/[^0-9?]/', '', (string) $request->query('numbers', ''));
        if ($symbol !== '' && ($letters !== '' || $numbers !== '')) {
            $query['symbol']      = $symbol;
            $query['letters']     = $letters;
            $query['numbers']     = $numbers;
            $query['kennzeichen'] = $symbol.'-'.strtoupper($letters).'-'.$numbers;
            $query['search']      = 1;
        }

        $ziel = config('portal.reservation_url').'?'.http_build_query($query);

        return redirect()->away($ziel, 302);
    }

    private function istBot(Request $request): bool
    {
        return (bool) preg_match('/bot|crawl|spider|slurp|bingpreview|facebookexternalhit/i', (string) $request->userAgent());
    }
}
