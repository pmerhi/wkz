<?php

use App\Http\Controllers\GoController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');

// Interner Migrations-Vorschlag (noindex) – temporäre Übersichtsseite.
Route::view('/url-migration-vorschlag', 'pages.migration');

Route::get('/zulassungsstelle', [PageController::class, 'zulassungsstelleIndex'])->name('zst.index');
// Autocomplete-Endpoint VOR der {slug}-Route registrieren, sonst fängt {slug} ihn ab.
Route::get('/zulassungsstelle/vorschlaege', [PageController::class, 'zulassungsstelleSuggest'])->name('zst.suggest');
// Einsegmentige URL (wie altes Projekt): /zulassungsstelle/{ort}/ – dispatcht auf Stelle ODER Bundesland-Listing.
Route::get('/zulassungsstelle/{slug}', [PageController::class, 'zulassungsstelle'])->name('zst.show');
// Alte zweisegmentige URL /zulassungsstelle/{land}/{ort}/ → 301 auf einsegmentig.
Route::get('/zulassungsstelle/{land}/{slug}', fn (string $land, string $slug) => redirect(url('/zulassungsstelle/'.$slug), 301))
    ->name('zst.land.show');

Route::get('/kennzeichen', [PageController::class, 'kuerzelIndex'])->name('kuerzel.index');
Route::get('/altkennzeichen', [PageController::class, 'altkennzeichen'])->name('altkennzeichen');
Route::get('/kennzeichen-quiz', [PageController::class, 'kennzeichenQuiz'])->name('kennzeichen.quiz');
Route::post('/kennzeichen-quiz/score', [PageController::class, 'quizSpeichern'])->name('kennzeichen.quiz.score');
Route::get('/kennzeichen-quiz/highscores', [PageController::class, 'quizHighscores'])->name('kennzeichen.quiz.highscores');
Route::get('/kennzeichen-quiz/hall-of-fame', [PageController::class, 'quizRangliste'])->name('kennzeichen.quiz.halloffame');
// Programmatic Ort-Seiten-Hub VOR der {slug}-Kürzel-Route registrieren.
Route::get('/kennzeichen/ort', [PageController::class, 'ortHub'])->name('ort.hub');
Route::get('/kennzeichen/ort/bundesland/{slug}', [PageController::class, 'ortHubLand'])->name('ort.hub.land');
// Alte interne Ort-URL → 301 auf die kanonische /wunschkennzeichen/{ort}/ (wie altes Projekt).
Route::get('/kennzeichen/ort/{slug}', fn (string $slug) => redirect(url('/wunschkennzeichen/'.$slug), 301))->name('ort.alt');
Route::get('/kennzeichen/{slug}', [PageController::class, 'kuerzel'])->name('kuerzel.show');

// Kanonische Ort-/Wunschkennzeichen-Seite (alte indexierte Geld-URLs).
Route::get('/wunschkennzeichen/{slug}', [PageController::class, 'kennzeichenOrt'])->name('ort.show');
// Bundesland-Listing liegt jetzt unter /zulassungsstelle/{land}; alte URL 301-weiterleiten.
Route::get('/bundesland/{slug}', fn (string $slug) => redirect(url('/zulassungsstelle/'.$slug), 301))->name('bundesland.show');

Route::get('/kfz-ratgeber', [PageController::class, 'ratgeberIndex'])->name('ratgeber.index');
// Autocomplete-Endpoint VOR der {slug}-Route registrieren, sonst fängt {slug} ihn ab.
Route::get('/kfz-ratgeber/vorschlaege', [PageController::class, 'ratgeberSuggest'])->name('ratgeber.suggest');
Route::get('/kfz-ratgeber/{slug}', [PageController::class, 'ratgeberShow'])->name('ratgeber.show');
// Alte /ratgeber/-URLs → 301 auf /kfz-ratgeber/ (Struktur wie altes Projekt).
Route::get('/ratgeber', fn () => redirect(url('/kfz-ratgeber'), 301));
Route::get('/ratgeber/{slug}', fn (string $slug) => redirect(url('/kfz-ratgeber/'.$slug), 301))->where('slug', '.*');

// Alte Ratgeber-Pfade des Vorgängerprojekts → 301 auf passenden /kfz-ratgeber/-Artikel.
foreach (['kfz-zulassung', 'kfz-kennzeichen', 'tipps-fuer-fahrzeughalter', 'kfz-ummeldung-abmeldung'] as $altPfad) {
    // Nackter Kategorie-Index (z. B. /kfz-zulassung/) → 301 auf die Ratgeber-Übersicht.
    Route::get('/'.$altPfad, fn () => redirect(url('/kfz-ratgeber'), 301));
    Route::get('/'.$altPfad.'/{slug}', [PageController::class, 'altRatgeberRedirect'])->where('slug', '.*');
}

Route::get('/ueber-uns', [PageController::class, 'ueberUns'])->name('ueber-uns');

// Kfz-Formulare: Übersicht + PDF-Download.
Route::get('/formulare', [\App\Http\Controllers\FormularController::class, 'index'])->name('formulare.index');
Route::get('/formulare/{slug}.pdf', [\App\Http\Controllers\FormularController::class, 'download'])
    ->where('slug', '[a-z0-9-]+')->name('formulare.download');

// Reservierungs-Conversion VOR der {placement}-Route registrieren.
Route::get('/go/reservierung', \App\Http\Controllers\ReservierungController::class)->name('go.reservierung');
Route::get('/go/{placement}', GoController::class)->name('go');

// Sitemap-Index + Kind-Sitemaps je Typ (Diagnostik in der Search Console)
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemap-{typ}.xml', [SitemapController::class, 'child'])
    ->whereIn('typ', ['static', 'stellen', 'kennzeichen', 'ort', 'bundesland', 'ratgeber'])
    ->name('sitemap.child');

Route::get('/robots.txt', function () {
    $body = "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /go/\n\nSitemap: ".url('/sitemap.xml')."\n";
    return response($body, 200, ['Content-Type' => 'text/plain']);
})->name('robots');

Route::get('/impressum', [PageController::class, 'legal'])->defaults('page', 'impressum')->name('impressum');
Route::get('/datenschutz', [PageController::class, 'legal'])->defaults('page', 'datenschutz')->name('datenschutz');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/agb', [PageController::class, 'agb'])->name('agb');
