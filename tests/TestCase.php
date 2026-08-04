<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Uri;

abstract class TestCase extends BaseTestCase
{
    /**
     * Laravels Standard macht `trim(url($uri), '/')` und entfernt damit genau den
     * Trailing Slash, den unser UrlGenerator setzt. Tests würden dadurch immer die
     * nicht-kanonische URL treffen und am 301 von RedirectToTrailingSlash hängen
     * bleiben. Hier ohne Trim, damit `$this->get('/formulare')` die kanonische
     * URL `/formulare/` anfragt – so wie jeder interne Link im Frontend.
     */
    protected function prepareUrlForRequest($uri)
    {
        $uri = $uri instanceof Uri ? $uri->value() : $uri;

        if (str_starts_with($uri, '/')) {
            $uri = substr($uri, 1);
        }

        return url($uri);
    }
}
