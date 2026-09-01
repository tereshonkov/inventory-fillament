<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\NotIntroducedAssets;
use App\Filament\Widgets\StatsOverview;
use Filament\Forms\Components\DatePicker;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        DatePicker::configureUsing(function (DatePicker $component) {
            $component->native(false)->locale('uk');
        });
        CreateRecord::disableCreateAnother();

        FilamentColor::register([
            'primary' => [
                50 => '245, 245, 245',
                100 => '229, 229, 229',
                200 => '212, 212, 212',
                300 => '163, 163, 163',
                400 => '115, 115, 115',
                500 => '82, 82, 82',
                600 => '38, 38, 38',
                700 => '23, 23, 23',
                800 => '13, 13, 13',
                900 => '8, 8, 8',
                950 => '3, 3, 3',
            ],
        ]);
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->brandName('MVO')
            ->favicon(asset('favicon.svg'))
            ->brandLogo(fn() => view('filament.logo'))
            ->brandLogoHeight('3.5rem')
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->sidebarCollapsibleOnDesktop()
            ->login()
            ->navigationGroups([
                'Облік майна',
                'Довідники',
                'Персонал',
                'Адміністрування',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // AccountWidget::class,
                // FilamentInfoWidget::class,
                StatsOverview::class,
                NotIntroducedAssets::class,
            ])
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
            ]);
    }
}
