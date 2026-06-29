<?php

namespace App\Providers;

use App\Support\AbTesting;
use App\Support\TrailingSlashUrlGenerator;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AbTesting::class, fn () => new AbTesting(config('experiments', [])));

        // URL-Generator austauschen, damit alle Seiten-URLs mit „/" enden
        // (wie im alten Projekt). Siehe TrailingSlashUrlGenerator.
        $this->app->extend('url', function (UrlGenerator $url, $app) {
            $routes = $app['router']->getRoutes();

            $neu = new TrailingSlashUrlGenerator(
                $routes,
                $app->rebinding('request', function ($app, $request) {
                    $app['url']->setRequest($request);
                }),
                $app['config']['app.asset_url']
            );

            $neu->setSessionResolver(fn () => $app['session'] ?? null);
            $neu->setKeyResolver(fn () => $app->make('config')->get('app.key'));

            // Namespace für Controller-Actions vom alten Generator übernehmen.
            $neu->setRootControllerNamespace(
                $app->getNamespace().'Http\\Controllers'
            );

            return $neu;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
