<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;


class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('')
            ->renderHook(
                'panels::topbar.start',
                fn() => '
                    <div style="display:flex;align-items:center;gap:10px">
                        <img src="' . asset('images/majelis.png') . '" style="height:32px">
                        <span style="font-weight:600;font-size:16px">
                            Majelis Rental
                        </span>
                    </div>
                '
            )

            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Teal,
                'danger' => Color::Red,
                'success' => Color::Lime,
                'warning' => Color::Orange,
                'info' => Color::Slate,
            ])

            ->renderHook(
                'panels::head.end',
                fn() => '<link rel="stylesheet" href="/custom.css">'
            )

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->userMenuItems([
                'logout' => Action::make('logout')
                    ->label('Logout')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->requiresConfirmation() // 🔥 ini kuncinya
                    ->modalHeading('Konfirmasi Logout')
                    ->modalDescription('Apakah Anda yakin ingin logout?')
                    ->modalSubmitActionLabel('Ya, Logout')
                    ->action(function () {
                        Auth::logout();
                        request()->session()->invalidate();
                        request()->session()->regenerateToken();

                        return redirect('/');
                    }),
            ]);
    }
}
