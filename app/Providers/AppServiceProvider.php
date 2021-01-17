<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // We were using Passport before Sanctum. Unfortunately, both Passport
        // and Sanctum creates a "personal_access_tokens" table that caused
        // issues with migrations. So I've exported Sanctum's default migration
        // to our own migrations to be executed after the Passport uninstall
        // migration. This means we need to disable the stock Sanctum
        // migration.
        Sanctum::ignoreMigrations();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (config('app.force_https')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
