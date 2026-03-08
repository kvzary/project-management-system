<?php

namespace App\Filament\Plugins;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Department;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Panel;

class DepartmentNavigationPlugin implements Plugin
{
    public function getId(): string
    {
        return 'department-navigation';
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void
    {
        if (! auth()->check()) {
            return;
        }

        $user = auth()->user();

        $departments = $user->isSystemAdmin()
            ? Department::orderBy('name')->get()
            : $user->departments()->orderBy('name')->get();

        if ($departments->count() < 2 && ! $user->isSystemAdmin()) {
            return;
        }

        $items = $departments->map(function (Department $dept) {
            $url = ProjectResource::getUrl('index').'?'.http_build_query([
                'tableFilters' => ['department_id' => ['value' => $dept->id]],
            ]);

            return NavigationItem::make($dept->name)
                ->group('Departments')
                ->icon('heroicon-o-building-office-2')
                ->url($url)
                ->isActiveWhen(fn () => (request()->query('tableFilters')['department_id']['value'] ?? null) == $dept->id);
        })->toArray();

        Filament::registerNavigationItems($items);
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
