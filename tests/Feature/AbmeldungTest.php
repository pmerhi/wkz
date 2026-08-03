<?php

namespace Tests\Feature;

use App\Models\AbEvent;
use App\Models\Bundesland;
use App\Models\KennzeichenKuerzel;
use App\Models\RatgeberArtikel;
use App\Models\Zulassungsstelle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbmeldungTest extends TestCase
{
    use RefreshDatabase;

    public function test_go_abmeldung_leitet_weiter_und_zaehlt_conversion(): void
    {
        $res = $this->get('/go/abmeldung?c=zst&label=zst:test-stelle');

        $res->assertStatus(302);
        $ziel = $res->headers->get('location');

        $this->assertStringStartsWith(config('abmeldung.url').'?', $ziel);
        $this->assertStringContainsString('cId='.config('abmeldung.cid'), $ziel);
        $this->assertStringContainsString('utm_campaign=zst', $ziel);

        $this->assertSame(1, AbEvent::where('experiment', 'abmeldung')->where('event', 'conversion')->count());
    }

    /** Der TrailingSlashUrlGenerator erzeugt /go/abmeldung/ – die Route muss auch so greifen. */
    public function test_route_greift_auch_mit_trailing_slash(): void
    {
        $this->get('/go/abmeldung/?c=nav&label=nav')->assertStatus(302);
        $this->assertSame(1, AbEvent::where('experiment', 'abmeldung')->count());
    }

    public function test_bots_zaehlen_nicht_als_conversion(): void
    {
        $this->withHeaders(['User-Agent' => 'Googlebot/2.1'])->get('/go/abmeldung')->assertStatus(302);

        $this->assertSame(0, AbEvent::where('experiment', 'abmeldung')->count());
    }

    public function test_cta_erscheint_auf_startseite_und_zulassungsstelle(): void
    {
        $land   = Bundesland::create(['name' => 'Teststaat', 'slug' => 'teststaat']);
        $stelle = Zulassungsstelle::create(['name' => 'Test-Zulassungsstelle', 'slug' => 'test-stelle', 'ort' => 'Teststadt', 'bundesland_id' => $land->id]);
        $stelle->kennzeichenKuerzel()->attach(KennzeichenKuerzel::create(['code' => 'TT', 'slug' => 'tt', 'bedeutung' => 'Teststadt']));

        $this->get('/')->assertOk()->assertSee('/go/abmeldung', false);
        $this->get('/zulassungsstelle/test-stelle')->assertOk()->assertSee('/go/abmeldung', false);
        $this->get('/formulare')->assertOk()->assertSee('/go/abmeldung', false);
    }

    public function test_landingpage_rendert_mit_schema_und_cta(): void
    {
        RatgeberArtikel::create(['titel' => 'Auto abmelden', 'slug' => 'auto-abmelden', 'intro' => 'Kurz.', 'body' => '## Hallo', 'published_at' => now()]);

        $this->get('/kfz-abmeldung')->assertOk()
            ->assertSee('Kfz-Abmeldung', false)
            ->assertSee('index,follow', false)
            ->assertSee('"@type":"Service"', false)
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('/go/abmeldung', false)
            // Cluster-Verlinkung: existierende Ratgeber werden verlinkt.
            ->assertSee('/kfz-ratgeber/auto-abmelden', false)
            // Auf der Landingpage selbst kein Selbst-Link im CTA-Block.
            ->assertDontSee('Ablauf, Unterlagen &amp; Kosten ansehen', false);
    }

    public function test_landingpage_ist_intern_verlinkt_und_in_der_sitemap(): void
    {
        // Nav/Footer erzeugen wegen TrailingSlashUrlGenerator /kfz-abmeldung/ – muss 200 liefern.
        $this->get('/kfz-abmeldung/')->assertOk();

        // Nav + Footer verlinken intern (dofollow), nicht direkt auf die externe Domain.
        $this->get('/')->assertOk()->assertSee('href="'.url('/kfz-abmeldung').'"', false);
        $this->get('/sitemap-static.xml')->assertOk()->assertSee(url('/kfz-abmeldung'), false);
    }

    public function test_cta_block_verlinkt_auf_die_landingpage(): void
    {
        RatgeberArtikel::create(['titel' => 'Auto abmelden', 'slug' => 'auto-abmelden', 'body' => '## Hallo', 'published_at' => now()]);

        $this->get('/kfz-ratgeber/auto-abmelden')->assertOk()
            ->assertSee('Ablauf, Unterlagen &amp; Kosten ansehen', false);
    }

    public function test_cta_nur_auf_passenden_ratgebern(): void
    {
        RatgeberArtikel::create(['titel' => 'Auto abmelden', 'slug' => 'auto-abmelden', 'body' => '## Hallo', 'published_at' => now()]);
        RatgeberArtikel::create(['titel' => 'Wiederzulassung', 'slug' => 'wiederzulassung', 'body' => '## Hallo', 'published_at' => now()]);
        RatgeberArtikel::create(['titel' => 'Führerschein-Kosten', 'slug' => 'fuhrerschein-kosten', 'body' => '## Hallo', 'published_at' => now()]);

        // Primär-Slug: großer CTA-Block.
        $this->get('/kfz-ratgeber/auto-abmelden')->assertOk()->assertSee('Unser Abmeldeservice');
        // Sekundär-Slug: nur schlanker Hinweiskasten, kein Block.
        $this->get('/kfz-ratgeber/wiederzulassung')->assertOk()
            ->assertDontSee('Unser Abmeldeservice')
            ->assertSee('c=ratgeber-sek', false);
        // Themenfremd: gar kein Abmelde-CTA im Inhalt (Nav/Footer bleiben unberührt).
        $this->get('/kfz-ratgeber/fuhrerschein-kosten')->assertOk()
            ->assertDontSee('Unser Abmeldeservice')
            ->assertDontSee('label=ratgeber', false);
    }
}
