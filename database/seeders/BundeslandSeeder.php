<?php

namespace Database\Seeders;

use App\Models\Bundesland;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BundeslandSeeder extends Seeder
{
    /** Alle 16 Bundesländer als verbindliche Stammdaten. */
    public function run(): void
    {
        $laender = [
            'Baden-Württemberg', 'Bayern', 'Berlin', 'Brandenburg', 'Bremen',
            'Hamburg', 'Hessen', 'Mecklenburg-Vorpommern', 'Niedersachsen',
            'Nordrhein-Westfalen', 'Rheinland-Pfalz', 'Saarland', 'Sachsen',
            'Sachsen-Anhalt', 'Schleswig-Holstein', 'Thüringen',
        ];

        foreach ($laender as $name) {
            Bundesland::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }
}
