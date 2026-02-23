<?php

namespace App\Providers\Filament;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Illuminate\Session\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\Vite;

class AdminPanelProvider extends PanelProvider {
	public function panel(Panel $panel): Panel {
		return $panel
			->default()
			->id('admin')
			->path('admin')
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
			->databaseNotifications()
			->databaseNotificationsPolling('30s')
			->plugins([
				FilamentShieldPlugin::make(),
			])
			->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
			->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
			->pages([
				Pages\Dashboard::class,
			])
			// ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
			->widgets([
				\App\Filament\Widgets\StatsOverviewWidget::class,
				\App\Filament\Widgets\TeamTaskTrendsWidget::class,
				\App\Filament\Widgets\TasksByPriorityWidget::class,
				// \App\Filament\Widgets\TeamProductivityWidget::class,
				\App\Filament\Widgets\ProjectProgressWidget::class,
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
					->withEntryPoints(['resources/js/filament-fix.js', 'resources/js/app.js', 'resources/css/app.css'])
					->toHtml(),
			);
	}
}
