<?php

namespace Tests\Feature;

use App\Models\Bundesland;
use App\Models\Gemeinde;
use App\Models\KennzeichenKuerzel;
use App\Models\Kreis;
use App\Models\Zulassungsstelle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservierungTest extends TestCase
{
    use RefreshDatabase;

    /** Ortskürzel allein: vorbelegen, aber keine Suche auslösen (Kombi ist ja unvollständig). */
    public function test_kuerzel_allein_wird_durchgereicht_ohne_suche(): void
    {
        $ziel = $this->get('/go/reservierung?c=zst&label=zst:test&symbol=SE')
            ->assertStatus(302)->headers->get('location');

        $this->assertStringContainsString('symbol=SE', $ziel);
        $this->assertStringContainsString('cId='.config('portal.reservation_cid'), $ziel);
        $this->assertStringNotContainsString('search=1', $ziel);
        $this->assertStringNotContainsString('kennzeichen=', $ziel);
    }

    /** Vollständige Kombi aus dem Generator: zusätzlich Suche auslösen. */
    public function test_vollstaendige_kombi_loest_suche_aus(): void
    {
        $ziel = $this->get('/go/reservierung?symbol=SE&letters=AB&numbers=123')
            ->assertStatus(302)->headers->get('location');

        $this->assertStringContainsString('symbol=SE', $ziel);
        $this->assertStringContainsString('kennzeichen=SE-AB-123', urldecode($ziel));
        $this->assertStringContainsString('search=1', $ziel);
    }

    /** Die CTA-Blöcke der Kürzel-, Orts- und Stellenseiten müssen das Kürzel mitgeben. */
    public function test_cta_bloecke_geben_das_kuerzel_mit(): void
    {
        $land   = Bundesland::create(['name' => 'Schleswig-Holstein', 'slug' => 'schleswig-holstein']);
        $kreis  = Kreis::create(['name' => 'Segeberg', 'ags' => '01060', 'bundesland_id' => $land->id]);
        $k      = KennzeichenKuerzel::create(['code' => 'SE', 'slug' => 'se', 'bedeutung' => 'Segeberg']);
        $kreis->kennzeichenKuerzel()->attach($k);
        $stelle = Zulassungsstelle::create(['name' => 'Zulassungsstelle Norderstedt', 'slug' => 'norderstedt',
            'ort' => 'Norderstedt', 'bundesland_id' => $land->id, 'kreis_id' => $kreis->id]);
        $stelle->kennzeichenKuerzel()->attach($k);
        Gemeinde::create(['name' => 'Norderstedt', 'slug' => 'norderstedt', 'ags' => '01060039',
            'kreis_id' => $kreis->id, 'bundesland_id' => $land->id]);

        foreach (['/kennzeichen/se', '/zulassungsstelle/norderstedt', '/wunschkennzeichen/norderstedt'] as $url) {
            $this->get($url)->assertOk()->assertSee('symbol=SE', false);
        }
    }

    /** Kein CTA darf an /go/reservierung vorbei direkt auf die Zieldomain zeigen (sonst fehlt die cId). */
    public function test_kein_cta_umgeht_die_getrackte_weiterleitung(): void
    {
        $this->get('/altkennzeichen')->assertOk()
            ->assertSee('/go/reservierung', false)
            ->assertDontSee(config('portal.reservation_url'), false);
    }
}
