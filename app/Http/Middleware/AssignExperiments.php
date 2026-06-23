<?php

namespace App\Http\Middleware;

use App\Support\AbTesting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class AssignExperiments
{
    public function __construct(private AbTesting $ab) {}

    public function handle(Request $request, Closure $next): Response
    {
        $assignments = $this->ab->all($request);

        // Für Blade verfügbar machen
        View::share('ab', $assignments);

        // Sticky machen (90 Tage)
        foreach ($assignments as $key => $variant) {
            Cookie::queue('ab_'.$key, $variant, 60 * 24 * 90);
        }

        return $next($request);
    }
}
