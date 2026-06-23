<?php

use App\Http\Controllers\GoController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');

Route::get('/zulassungsstelle', [PageController::class, 'zulassungsstelleIndex'])->name('zst.index');
Route::get('/zulassungsstelle/{slug}', [PageController::class, 'zulassungsstelle'])->name('zst.show');

Route::get('/kennzeichen', [PageController::class, 'kuerzelIndex'])->name('kuerzel.index');
Route::get('/altkennzeichen', [PageController::class, 'altkennzeichen'])->name('altkennzeichen');
Route::get('/kennzeichen/{slug}', [PageController::class, 'kuerzel'])->name('kuerzel.show');
Route::get('/bundesland/{slug}', [PageController::class, 'bundesland'])->name('bundesland.show');

Route::get('/ratgeber', [PageController::class, 'ratgeberIndex'])->name('ratgeber.index');
Route::get('/ratgeber/{slug}', [PageController::class, 'ratgeberShow'])->name('ratgeber.show');

Route::get('/ueber-uns', [PageController::class, 'ueberUns'])->name('ueber-uns');

Route::get('/go/{placement}', GoController::class)->name('go');

// Sitemap-Index + Kind-Sitemaps je Typ (Diagnostik in der Search Console)
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemap-{typ}.xml', [SitemapController::class, 'child'])
    ->whereIn('typ', ['static', 'stellen', 'kennzeichen', 'bundesland', 'ratgeber'])
    ->name('sitemap.child');

Route::get('/robots.txt', function () {
    $body = "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /go/\n\nSitemap: ".url('/sitemap.xml')."\n";
    return response($body, 200, ['Content-Type' => 'text/plain']);
})->name('robots');

Route::get('/impressum', [PageController::class, 'legal'])->defaults('page', 'impressum')->name('impressum');
Route::get('/datenschutz', [PageController::class, 'legal'])->defaults('page', 'datenschutz')->name('datenschutz');
