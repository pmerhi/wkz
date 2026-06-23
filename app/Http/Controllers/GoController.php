<?php

namespace App\Http\Controllers;

use App\Models\Click;
use App\Models\Placement;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class GoController extends Controller
{
    public function __invoke(Request $request, Placement $placement): RedirectResponse
    {
        abort_unless($placement->aktiv, 404);

        Click::create([
            'placement_id' => $placement->id,
            'clicked_at'   => now(),
            'referrer'     => $request->headers->get('referer'),
            'user_agent'   => substr((string) $request->userAgent(), 0, 512),
        ]);

        // Externe Affiliate-Weiterleitung (302, nicht indexierbar).
        return redirect()->away($placement->ziel_url, 302);
    }
}
