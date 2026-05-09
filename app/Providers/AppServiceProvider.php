<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Css;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Paksa pakai APP_URL
        if (config('app.url')) {
            URL::forceRootUrl(config('app.url'));
        }

        // Paksa HTTPS hanya jika APP_URL memakai https
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        FilamentAsset::register([
            Css::make('custom', asset('css/custom.css')),
        ]);
    }
}
