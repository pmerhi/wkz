<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HaertungTest extends TestCase
{
    use RefreshDatabase;

    /** Nur als Admin markierte Konten kommen ins Filament-Backend. */
    public function test_nur_admins_duerfen_ins_panel(): void
    {
        $panel = Filament::getPanel('admin');

        $admin = User::create(['name' => 'A', 'email' => 'a@example.com',
            'password' => 'geheim1234', 'is_admin' => true]);
        $gast  = User::create(['name' => 'G', 'email' => 'g@example.com',
            'password' => 'geheim1234']);

        $this->assertTrue($admin->canAccessPanel($panel));
        $this->assertFalse($gast->canAccessPanel($panel), 'Neue Konten duerfen standardmaessig nicht ins Admin.');
        $this->assertFalse($gast->refresh()->is_admin, 'is_admin muss in der DB auf false stehen.');
    }

    /** Sicherheits-Header liegen auf jeder Antwort. */
    public function test_sicherheits_header_sind_gesetzt(): void
    {
        $res = $this->get('/');

        $res->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $csp = $res->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
    }

    /** HSTS nur ueber HTTPS – sonst wuerde der Header ins Leere laufen. */
    public function test_hsts_nur_ueber_https(): void
    {
        $this->get('http://localhost/')->assertHeaderMissing('Strict-Transport-Security');
        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    /**
     * Eine URL, ein Inhalt: ohne Slash 301 auf die Slash-Variante.
     * Geht bewusst direkt ueber den HTTP-Kernel – Tests::prepareUrlForRequest
     * ruft sonst immer schon die kanonische URL auf.
     */
    public function test_ohne_slash_301_auf_slash(): void
    {
        $kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);

        foreach (['/formulare', '/kfz-abmeldung', '/ueber-uns'] as $pfad) {
            $res = $kernel->handle(\Illuminate\Http\Request::create(config('app.url').$pfad));
            $this->assertSame(301, $res->getStatusCode(), $pfad.' muss 301 liefern.');
            $this->assertSame(config('app.url').$pfad.'/', $res->headers->get('location'));
        }

        // Und die kanonische Variante liefert den Inhalt aus.
        $this->get('/formulare')->assertOk();
    }

    /**
     * Die Slash-Variante darf NICHT erneut umgeleitet werden – sonst Endlosschleife.
     * Der Test-Client trimmt Trailing-Slashes weg (MakesHttpRequests::prepareUrlForRequest),
     * deshalb hier direkt gegen die Middleware statt ueber $this->get().
     */
    public function test_middleware_leitet_nicht_in_die_schleife(): void
    {
        $mw   = new \App\Http\Middleware\RedirectToTrailingSlash();
        $next = fn () => new \Illuminate\Http\Response('ok');

        $durchgelassen = [
            '/formulare/', '/', '/robots.txt', '/sitemap.xml',
            '/formulare/vollmacht.pdf', '/admin/login', '/livewire/update', '/up',
        ];
        foreach ($durchgelassen as $pfad) {
            $res = $mw->handle(\Illuminate\Http\Request::create($pfad), $next);
            $this->assertSame(200, $res->getStatusCode(), $pfad.' darf nicht umgeleitet werden.');
        }

        // POST bleibt unangetastet (sonst geht der Request-Body verloren).
        $post = $mw->handle(\Illuminate\Http\Request::create('/kennzeichen-quiz/score', 'POST'), $next);
        $this->assertSame(200, $post->getStatusCode());

        // Und der eigentliche Fall leitet weiter.
        $res = $mw->handle(\Illuminate\Http\Request::create('/formulare'), $next);
        $this->assertSame(301, $res->getStatusCode());
        $this->assertStringEndsWith('/formulare/', $res->headers->get('location'));

        // Query bleibt beim Umleiten erhalten.
        $mitQuery = $mw->handle(\Illuminate\Http\Request::create('/zulassungsstelle?q=bonn'), $next);
        $this->assertSame(301, $mitQuery->getStatusCode());
        $this->assertStringEndsWith('/zulassungsstelle/?q=bonn', $mitQuery->headers->get('location'));
    }

    /** Dateien und Sonderpfade duerfen NICHT umgeleitet werden. */
    public function test_dateien_und_sonderpfade_bleiben_unveraendert(): void
    {
        $this->get('/robots.txt')->assertOk();
        $this->get('/sitemap.xml')->assertOk();
        $this->get('/up')->assertOk();
        $this->get('/formulare/vollmacht.pdf')->assertOk();
    }

    /** Karten-Assets kommen lokal, nicht mehr von unpkg. */
    public function test_leaflet_kommt_nicht_vom_cdn(): void
    {
        $this->assertFileExists(public_path('vendor/leaflet/leaflet.js'));
        $this->assertFileExists(public_path('vendor/leaflet/leaflet.css'));

        $view = file_get_contents(resource_path('views/components/standort-karte.blade.php'));
        $this->assertStringNotContainsString('unpkg.com', $view);
        $this->assertStringContainsString("asset('vendor/leaflet/leaflet.js')", $view);
    }
}
