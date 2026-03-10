<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\TaskCalendarWidget;
use Filament\Pages\Page;

class TaskCalendarPage extends Page
{
    protected string $view = 'filament.pages.task-calendar';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Calendar';

    protected static ?string $title = 'Task Calendar';

    protected static string|\UnitEnum|null $navigationGroup = 'Project Management';

    protected static ?int $navigationSort = 6;

    protected function getHeaderWidgets(): array
    {
        return [
            TaskCalendarWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
