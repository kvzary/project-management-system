<?php

namespace App\Providers\Filament;

use App\Filament\Plugins\DepartmentNavigationPlugin;
use App\Filament\Widgets\ProjectProgressWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\TasksByPriorityWidget;
use App\Filament\Widgets\TeamTaskTrendsWidget;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->favicon(asset('/images/pms-favicon-nobg-48x48-cropped.png'))
            ->login()
            ->passwordReset()
            ->emailVerification()
            ->profile(isSimple: false)
            ->colors([
                'primary' => Color::Sky,
                'gray' => Color::Slate,
                'danger' => Color::Rose,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->darkMode()
            ->databaseNotifications(isLazy: false)
            ->databaseNotificationsPolling('30s')
            ->plugins([
                FilamentShieldPlugin::make(),
                DepartmentNavigationPlugin::make(),
                \Relaticle\Flowforge\FlowforgePlugin::make(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            // ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                StatsOverviewWidget::class,
                TeamTaskTrendsWidget::class,
                TasksByPriorityWidget::class,
                // \App\Filament\Widgets\TeamProductivityWidget::class,
                ProjectProgressWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                'panels::head.end',
                fn (): string => Vite::useHotFile(public_path('hot'))
                    ->useBuildDirectory('build')
                    ->withEntryPoints(['resources/js/filament-fix.js', 'resources/js/app.js'])
                    ->toHtml(),
            );
    }
}
