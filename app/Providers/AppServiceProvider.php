<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Css;
use App\Repositories\BarangRepository;
use App\Repositories\Contracts\BarangRepositoryInterface;
use App\Services\AI\GroqVisionService;
use App\Services\Recommendation\ImageRecommendationService;
use App\Models\Barang;
use App\Observers\BarangObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository binding
        $this->app->bind(
            BarangRepositoryInterface::class,
            BarangRepository::class,
        );

        // Service singleton (reuse same instance per request)
        $this->app->singleton(GroqVisionService::class);
        $this->app->singleton(ImageRecommendationService::class);
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

        // Observer: invalidasi cache konteks katalog AI saat data barang berubah
        Barang::observe(BarangObserver::class);

        FilamentAsset::register([
            Css::make('custom', asset('css/custom.css')),
        ]);
    }
}
