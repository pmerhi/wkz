<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alle Seiten-URLs enden mit „/" (siehe TrailingSlashUrlGenerator). Ohne diese
 * Weiterleitung liefern /formulare und /formulare/ beide 200 – zwei URLs, ein
 * Inhalt. Das Canonical fängt es zwar ab, ein 301 ist aber eindeutig.
 *
 * Ausgenommen sind dieselben Fälle wie im URL-Generator: Wurzel, Admin/Livewire
 * und alles mit Dateiendung (robots.txt, sitemap.xml, *.pdf, Assets).
 */
class RedirectToTrailingSlash
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe()) {
            return $next($request);
        }

        $pfad = $request->getPathInfo();
        $trim = ltrim($pfad, '/');

        $ueberspringen = $trim === ''
            || str_ends_with($pfad, '/')
            || str_starts_with($trim, 'admin')
            || str_starts_with($trim, 'livewire')
            || str_starts_with($trim, 'up')
            || str_contains(basename($trim), '.');

        if ($ueberspringen) {
            return $next($request);
        }

        $ziel = $request->getSchemeAndHttpHost().$pfad.'/';
        if ($query = $request->getQueryString()) {
            $ziel .= '?'.$query;
        }

        return redirect($ziel, 301);
    }
}
