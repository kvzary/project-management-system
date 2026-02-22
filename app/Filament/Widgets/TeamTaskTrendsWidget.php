<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use App\Models\User;
use App\Models\WorkflowStatus;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class TeamTaskTrendsWidget extends ChartWidget
{
    protected static ?string $heading = 'Team Task Trends';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.team-task-trends-widget';

    public ?string $filter = null;

    public ?string $assigneeFilter = null;

    public ?string $statusFilter = null;

    protected function getFilters(): ?array
    {
        $years = [];
        for ($year = now()->year; $year >= now()->subYears(2)->year; $year--) {
            $years[(string) $year] = (string) $year;
        }

        return $years;
    }

    public function getAssigneeOptions(): array
    {
        return ['' => 'All Members'] + User::whereHas('assignedTasks')->pluck('name', 'id')->toArray();
    }

    public function getStatusOptions(): array
    {
        return [
            ''          => 'All Statuses',
            'completed' => 'Completed',
            'overdue'   => 'Overdue',
            'active'    => 'Active',
        ];
    }

    public function updatedAssigneeFilter(): void
    {
        $this->updateChartData();
    }

    public function updatedStatusFilter(): void
    {
        $this->updateChartData();
    }

    protected function getData(): array
    {
        $year = $this->filter ?? now()->year;
        $assigneeId = $this->assigneeFilter ?: null;
        $statusType = $this->statusFilter ?: null;

        $completedSlugs = WorkflowStatus::where('is_completed', true)->pluck('slug')->toArray();

        $start = Carbon::create($year)->startOfYear();
        $end = Carbon::create($year)->endOfYear();

        $weeks = [];
        $current = $start->copy()->startOfWeek();
        while ($current <= $end) {
            $weeks[] = $current->copy();
            $current->addWeek();
        }

        $labels = collect($weeks)->map(fn ($w) => 'W' . $w->weekOfYear)->toArray();

        $usersQuery = User::whereHas('assignedTasks');
        if ($assigneeId) {
            $usersQuery->where('id', $assigneeId);
        }
        $users = $usersQuery->get();

        $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'];
        $datasets = [];
        $colorIndex = 0;

        foreach ($users as $user) {
            $color = $colors[$colorIndex % count($colors)];
            $colorIndex++;

            if (!$statusType || $statusType === 'completed') {
                $datasets[] = [
                    'label' => $user->name . ' (Completed)',
                    'data' => collect($weeks)->map(fn ($week) => Task::where('assigned_to', $user->id)
                        ->whereIn('status', $completedSlugs)
                        ->whereBetween('updated_at', [$week->copy()->startOfWeek(), $week->copy()->endOfWeek()])
                        ->count()
                    )->toArray(),
                    'borderColor' => $color,
                    'backgroundColor' => $color . '33',
                    'fill' => false,
                    'tension' => 0.3,
                ];
            }

            if (!$statusType || $statusType === 'overdue') {
                $datasets[] = [
                    'label' => $user->name . ' (Overdue)',
                    'data' => collect($weeks)->map(fn ($week) => Task::where('assigned_to', $user->id)
                        ->whereNotIn('status', $completedSlugs)
                        ->whereNotNull('due_date')
                        ->where('due_date', '<', $week->copy()->endOfWeek())
                        ->count()
                    )->toArray(),
                    'borderColor' => $color,
                    'backgroundColor' => $color . '33',
                    'borderDash' => [5, 5],
                    'fill' => false,
                    'tension' => 0.3,
                ];
            }

            if (!$statusType || $statusType === 'active') {
                $datasets[] = [
                    'label' => $user->name . ' (Active)',
                    'data' => collect($weeks)->map(fn ($week) => Task::where('assigned_to', $user->id)
                        ->whereNotIn('status', $completedSlugs)
                        ->where('created_at', '<=', $week->copy()->endOfWeek())
                        ->count()
                    )->toArray(),
                    'borderColor' => $color,
                    'backgroundColor' => $color . '33',
                    'borderDash' => [2, 2],
                    'fill' => false,
                    'tension' => 0.3,
                ];
            }
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => true,
            'scales' => [
                'x' => [
                    'ticks' => [
                        'maxTicksLimit' => 13,
                        'maxRotation' => 0,
                        'autoSkip' => true,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'boxWidth' => 10,
                        'padding' => 8,
                        'font' => ['size' => 11],
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
