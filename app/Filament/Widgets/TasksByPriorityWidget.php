<?php

namespace App\Filament\Widgets;

use App\Enums\TaskPriority;
use App\Filament\Widgets\Concerns\HasDepartmentScope;
use App\Models\Task;
use App\Models\WorkflowStatus;
use Filament\Widgets\ChartWidget;

class TasksByPriorityWidget extends ChartWidget
{
    use HasDepartmentScope;

    protected static ?string $heading = 'Open Tasks by Priority';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $completedSlugs = WorkflowStatus::where('is_completed', true)->pluck('slug')->toArray();
        $departmentIds = $this->getDepartmentIds();

        $counts = $this->scopeTasksToDepartments(Task::query(), $departmentIds)
            ->whereNotIn('status', $completedSlugs)
            ->selectRaw('priority, count(*) as total')
            ->groupBy('priority')
            ->pluck('total', 'priority');

        $priorities = [
            TaskPriority::CRITICAL,
            TaskPriority::HIGH,
            TaskPriority::MEDIUM,
            TaskPriority::LOW,
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Tasks',
                    'data' => collect($priorities)->map(fn ($p) => $counts[$p->value] ?? 0)->toArray(),
                    'backgroundColor' => ['#ef4444', '#f97316', '#eab308', '#6b7280'],
                ],
            ],
            'labels' => collect($priorities)->map(fn ($p) => $p->label())->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
