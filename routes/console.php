<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Öffnungszeiten-Quellseiten wöchentlich auf Änderungen prüfen (nur Stellen, die
// seit 7 Tagen nicht geprüft wurden). Braucht laufenden `php artisan schedule:work`
// bzw. einen Server-Cron auf `php artisan schedule:run`.
Schedule::command('oz:pruefen --stale=7')
    ->weekly()->mondays()->at('04:00')
    ->withoutOverlapping();
