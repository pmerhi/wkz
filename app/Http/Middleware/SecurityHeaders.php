<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sicherheits-Header für alle Antworten.
 *
 * Bewusst KEINE vollständige CSP mit script-src/style-src: Layout und Komponenten
 * arbeiten durchgehend mit Inline-Styles und Inline-Skripten, eine strikte CSP
 * bräuchte Nonces auf jedem Block. Die hier gesetzten Direktiven wirken trotzdem
 * (Clickjacking, Base-Tag-Hijacking, Formular-Exfiltration, Plugins).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $header = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options'        => 'SAMEORIGIN',
            'Referrer-Policy'        => 'strict-origin-when-cross-origin',
            'Permissions-Policy'     => 'geolocation=(), microphone=(), camera=(), payment=(), usb=()',
            'Content-Security-Policy' => implode('; ', [
                "frame-ancestors 'self'",
                "base-uri 'self'",
                "form-action 'self'",
                "object-src 'none'",
            ]),
        ];

        // HSTS nur über HTTPS senden. Ohne includeSubDomains/preload, damit die
        // Entscheidung nach spätestens einem Jahr wieder revidierbar bleibt.
        if ($request->secure()) {
            $header['Strict-Transport-Security'] = 'max-age=31536000';
        }

        foreach ($header as $name => $wert) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $wert);
            }
        }

        return $response;
    }
}
