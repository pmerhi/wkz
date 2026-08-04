<?php

namespace Tests\Feature;

use App\Models\Bundesland;
use App\Models\KennzeichenKuerzel;
use App\Models\Partner;
use App\Models\Placement;
use App\Models\RatgeberArtikel;
use App\Models\Zulassungsstelle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    private function seedData(): array
    {
        $land = Bundesland::create(['name' => 'Teststaat', 'slug' => 'teststaat']);
        $stelle = Zulassungsstelle::create(['name' => 'Test-Zulassungsstelle', 'slug' => 'test-stelle', 'ort' => 'Teststadt', 'bundesland_id' => $land->id]);
        $kuerzel = KennzeichenKuerzel::create(['code' => 'TT', 'slug' => 'tt', 'bedeutung' => 'Teststadt']);
        $stelle->kennzeichenKuerzel()->attach($kuerzel);
        $artikel = RatgeberArtikel::create(['titel' => 'Testartikel', 'slug' => 'test-artikel', 'body' => '## Hallo', 'published_at' => now()]);

        return compact('land', 'stelle', 'kuerzel', 'artikel');
    }

    public function test_startseite_und_verzeichnis(): void
    {
        $this->seedData();
        $this->get('/')->assertOk()->assertSee('Wunschkennzeichen');
        $this->get('/zulassungsstelle')->assertOk();
        $this->get('/kennzeichen')->assertOk();
        $this->get('/altkennzeichen')->assertOk()->assertSee('FAQPage', false);
        $this->get('/kfz-ratgeber')->assertOk();
        $this->get('/ueber-uns')->assertOk();
        $this->get('/kfz-abmeldung')->assertOk();
        // Suche: Ergebnisseite ist noindex
        $this->get('/zulassungsstelle?q=test')->assertOk()->assertSee('noindex', false);
    }

    public function test_detailseiten_und_schema(): void
    {
        $this->seedData();
        $this->get('/zulassungsstelle/teststaat')->assertOk()->assertSee('Teststadt');
        $this->get('/zulassungsstelle/test-stelle')->assertOk()->assertSee('GovernmentOffice', false);
        $this->get('/kennzeichen/tt')->assertOk()->assertSee('TT');
        $this->get('/kfz-ratgeber/test-artikel')->assertOk()->assertSee('Article', false);
    }

    /** Alt-URLs des Vorgängerprojekts: ein 301-Sprung auf die kanonische URL, keine Ketten. */
    public function test_alt_urls_leiten_dauerhaft_um(): void
    {
        $this->seedData();

        // Zweisegmentige Zulassungsstellen-URL → einsegmentig.
        $this->get('/zulassungsstelle/teststaat/test-stelle')
            ->assertRedirect(url('/zulassungsstelle/test-stelle'))->assertStatus(301);
        // Flaches Bundesland-Schema → Zulassungsstellen-Listing.
        $this->get('/bundesland/teststaat')
            ->assertRedirect(url('/zulassungsstelle/teststaat'))->assertStatus(301);
        // /ratgeber/* → /kfz-ratgeber/*
        $this->get('/ratgeber')->assertRedirect(url('/kfz-ratgeber'))->assertStatus(301);
        $this->get('/ratgeber/test-artikel')
            ->assertRedirect(url('/kfz-ratgeber/test-artikel'))->assertStatus(301);
        // Alte Ratgeber-Kategorien des Vorgängerprojekts.
        $this->get('/kfz-zulassung')->assertRedirect(url('/kfz-ratgeber'))->assertStatus(301);

        // Ziel jedes Redirects muss selbst 200 liefern (keine Ketten, keine Sackgassen).
        foreach (['/zulassungsstelle/test-stelle', '/zulassungsstelle/teststaat',
                  '/kfz-ratgeber', '/kfz-ratgeber/test-artikel'] as $ziel) {
            $this->get($ziel)->assertOk();
        }
    }

    public function test_rechtseiten_sind_noindex(): void
    {
        $this->get('/impressum')->assertOk()->assertSee('noindex', false);
        $this->get('/datenschutz')->assertOk()->assertSee('noindex', false);
    }

    public function test_technik_sitemap_robots_404(): void
    {
        $this->seedData();
        $this->get('/sitemap.xml')->assertOk()->assertSee('<sitemapindex', false);
        $this->get('/sitemap-ratgeber.xml')->assertOk()->assertSee('<urlset', false);
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin');
        // Unbekannter Stellen-Slug: bewusst zurück ins Verzeichnis statt 404
        // (PageController::zulassungsstelle, Fall 5).
        $this->get('/zulassungsstelle/gibtsnicht')->assertRedirect('/zulassungsstelle');
        // Ein wirklich unbekannter Pfad muss weiterhin 404 liefern.
        $this->get('/gibtsnicht')->assertNotFound();
    }

    public function test_affiliate_redirect_zaehlt_klick(): void
    {
        $partner = Partner::create(['name' => 'Testpartner', 'aktiv' => true]);
        $placement = Placement::create(['partner_id' => $partner->id, 'name' => 'Block', 'typ' => 'block', 'ziel_url' => 'https://example.com', 'aktiv' => true]);

        $this->get('/go/'.$placement->id)->assertRedirect('https://example.com');
        $this->assertSame(1, $placement->clicks()->count());
    }
}
