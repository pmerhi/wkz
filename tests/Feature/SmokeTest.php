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
        $this->get('/ratgeber')->assertOk();
        $this->get('/ueber-uns')->assertOk();
        // Suche: Ergebnisseite ist noindex
        $this->get('/zulassungsstelle?q=test')->assertOk()->assertSee('noindex', false);
    }

    public function test_detailseiten_und_schema(): void
    {
        $this->seedData();
        $this->get('/zulassungsstelle/teststaat')->assertOk()->assertSee('Teststadt');
        $this->get('/zulassungsstelle/teststaat/test-stelle')->assertOk()->assertSee('GovernmentOffice', false);
        // Altes flaches Schema wird auf die kanonische URL weitergeleitet bzw. existiert nicht mehr.
        $this->get('/bundesland/teststaat')->assertRedirect('/zulassungsstelle/teststaat');
        $this->get('/kennzeichen/tt')->assertOk()->assertSee('TT');
        $this->get('/ratgeber/test-artikel')->assertOk()->assertSee('Article', false);
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
        $this->get('/zulassungsstelle/gibtsnicht')->assertNotFound();
    }

    public function test_affiliate_redirect_zaehlt_klick(): void
    {
        $partner = Partner::create(['name' => 'Testpartner', 'aktiv' => true]);
        $placement = Placement::create(['partner_id' => $partner->id, 'name' => 'Block', 'typ' => 'block', 'ziel_url' => 'https://example.com', 'aktiv' => true]);

        $this->get('/go/'.$placement->id)->assertRedirect('https://example.com');
        $this->assertSame(1, $placement->clicks()->count());
    }
}
