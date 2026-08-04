<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalTest extends TestCase
{
    use RefreshDatabase;

    /** Die Rechtstexte müssen im Repo liegen, sonst fehlen sie nach dem Deploy. */
    public function test_impressum_und_datenschutz_rendern_inhalt(): void
    {
        $this->get('/impressum')->assertOk()
            ->assertSee('netTraders GmbH')
            ->assertSee('HRB 13086')
            ->assertSee('kennzeichen.click GmbH')
            ->assertDontSee('Entwurf folgt aus Arbeitspaket');

        $this->get('/datenschutz')->assertOk()
            ->assertDontSee('Entwurf folgt aus Arbeitspaket')
            // 1:1 aus der gelieferten datenschutz.html – Struktur muss ankommen.
            ->assertSee('Datenschutzerklärung')
            ->assertSee('1. Datenschutz auf einen Blick')
            ->assertSee('7. Eigene Dienste')
            // Nur der Body-Inhalt uebernommen: kein zweites <html>/<head> in der Seite.
            ->assertDontSee('<html lang="de">'."\n".'<html', false)
            ->assertDontSee('meine-datenschutzerklaerung', false)
            // Hosting-Angabe
            ->assertSee('DomainFactory GmbH')
            ->assertSee('Neuturmstrasse 5')
            // Herausgenommene Abschnitte duerfen nicht zurueckkehren.
            ->assertDontSee('Kommentarfunktion')
            ->assertDontSee('Registrierung auf dieser Website')
            ->assertDontSee('Soziale Medien')
            ->assertDontSee('Pinterest')
            ->assertDontSee('Google Analytics')
            ->assertDontSee('Hotjar')
            ->assertDontSee('Active Campaign')
            ->assertDontSee('Google Maps')
            ->assertDontSee('Amazon')
            ->assertDontSee('finanzamt24')
            // Fremd-CDN und verwaiste Opt-out-Skripte sind raus.
            ->assertDontSee('cdnjs.cloudflare.com', false)
            ->assertDontSee('fpOptout', false);
    }
}
