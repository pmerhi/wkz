<?php

namespace Tests\Feature;

use App\Models\Bundesland;
use App\Models\Gemeinde;
use App\Models\KennzeichenKuerzel;
use App\Models\Kreis;
use App\Models\Zulassungsstelle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Die /wunschkennzeichen/{ort}/-Seiten sind die Money-Pages des Portals und hängen an
 * genau einem starken Inlink: dem von „ihrer" Zulassungsstellenseite. Beide Templates
 * (Detailseite und Stadt-Hub) müssen diesen Link führen – der Hub hatte ihn anfangs nicht,
 * wodurch u. a. Berlin, Hamburg und Stuttgart aus der internen Verlinkung fielen.
 */
class OrtVerlinkungTest extends TestCase
{
    use RefreshDatabase;

    private int $ags = 10000;

    /** Kreis mit Kürzel + Gemeinden anlegen; $aemter Ämter am Ort → 1 = Detailseite, >1 = Hub. */
    private function bezirk(string $ort, string $slug, int $aemter, int $weitereOrte = 0): Bundesland
    {
        $land  = Bundesland::firstOrCreate(['slug' => 'teststaat'], ['name' => 'Teststaat']);
        $kreis = Kreis::create(['ags' => (string) ++$this->ags, 'name' => $ort, 'bundesland_id' => $land->id]);
        $kuerzel = KennzeichenKuerzel::create(['code' => strtoupper(substr($slug, 0, 2)), 'slug' => $slug.'-kz', 'bedeutung' => $ort]);
        $kreis->kennzeichenKuerzel()->attach($kuerzel);

        $gemeinde = fn (string $name, string $s) => Gemeinde::create([
            'ags' => str_pad((string) ++$this->ags, 8, '0', STR_PAD_LEFT), 'name' => $name, 'slug' => $s,
            'kreis_id' => $kreis->id, 'bundesland_id' => $land->id,
        ]);

        $gemeinde($ort, $slug);
        for ($i = 1; $i <= $weitereOrte; $i++) {
            $gemeinde('Umland '.$i, $slug.'-umland-'.$i);
        }

        for ($i = 1; $i <= $aemter; $i++) {
            $stelle = Zulassungsstelle::create([
                'name' => 'Zulassungsstelle '.$ort.' '.$i, 'slug' => $i === 1 ? $slug : $slug.'-'.$i,
                'ort' => $ort, 'bundesland_id' => $land->id, 'kreis_id' => $kreis->id,
            ]);
            $stelle->kennzeichenKuerzel()->attach($kuerzel);
        }

        return $land;
    }

    public function test_detailseite_verlinkt_die_ort_seiten_des_bezirks(): void
    {
        $this->bezirk('Testheim', 'testheim', aemter: 1, weitereOrte: 2);

        $this->get('/zulassungsstelle/testheim/')->assertOk()
            ->assertSee(url('/wunschkennzeichen/testheim'), false)
            ->assertSee(url('/wunschkennzeichen/testheim-umland-1'), false)
            ->assertSee(url('/wunschkennzeichen/testheim-umland-2'), false);
    }

    public function test_hub_verlinkt_die_eigene_ort_seite_und_das_umland(): void
    {
        $this->bezirk('Hubstadt', 'hubstadt', aemter: 2, weitereOrte: 2);

        $this->get('/zulassungsstelle/hubstadt/')->assertOk()
            ->assertSee('Standorte in Hubstadt')          // wirklich das Hub-Template
            ->assertSee(url('/wunschkennzeichen/hubstadt'), false)
            ->assertSee(url('/wunschkennzeichen/hubstadt-umland-1'), false)
            ->assertSee(url('/wunschkennzeichen/hubstadt-umland-2'), false);
    }

    /** Kreisfreie Stadt mit mehreren Ämtern: kein Umland → der Einzellink ist der einzige Ausgang. */
    public function test_hub_ohne_umland_verlinkt_trotzdem_die_eigene_ort_seite(): void
    {
        $this->bezirk('Stadtstaat', 'stadtstaat', aemter: 3, weitereOrte: 0);

        $this->get('/zulassungsstelle/stadtstaat/')->assertOk()
            ->assertSee('Standorte in Stadtstaat')
            ->assertSee(url('/wunschkennzeichen/stadtstaat'), false);
    }

    /** Der Gemeinde-Block darf nicht mehr bei 60 Einträgen kappen (früheres limit(60)). */
    public function test_gemeinde_block_kappt_nicht_bei_60(): void
    {
        $this->bezirk('Grosskreis', 'grosskreis', aemter: 1, weitereOrte: 80);

        $res = $this->get('/zulassungsstelle/grosskreis/')->assertOk();
        $res->assertSee(url('/wunschkennzeichen/grosskreis-umland-80'), false);

        // Alle 80 Umland-Orte müssen im Block stehen, nicht nur die ersten 60.
        preg_match_all('#href="[^"]*/wunschkennzeichen/grosskreis-umland-\d+/?"#', $res->getContent(), $treffer);
        $this->assertCount(80, $treffer[0]);
    }
}
