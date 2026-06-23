<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = '/'.ltrim($request->getPathInfo(), '/');

        $redirect = Redirect::query()
            ->where('aktiv', true)
            ->where('from_path', $path)
            ->first();

        if ($redirect) {
            return redirect($redirect->to_path, $redirect->status_code);
        }

        return $next($request);
    }
}
