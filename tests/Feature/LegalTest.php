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

        $this->get('/datenschutz')->assertOk()->assertDontSee('Entwurf folgt aus Arbeitspaket');
    }
}
