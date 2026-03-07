<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDepartmentScope;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkflowStatus;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TeamTaskTrendsWidget extends ChartWidget
{
    use HasDepartmentScope;

    protected static ?string $heading = 'Team Task Trends';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

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
            '' => 'All Statuses',
            'completed' => 'Completed',
            'overdue' => 'Overdue',
            'active' => 'Active',
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
        $departmentIds = $this->getDepartmentIds();

        $start = Carbon::create($year)->startOfYear();
        $end = Carbon::create($year)->endOfYear();

        $weeks = [];
        $current = $start->copy()->startOfWeek();
        while ($current <= $end) {
            $weeks[] = $current->copy();
            $current->addWeek();
        }

        $labels = collect($weeks)->map(fn ($w) => 'W'.$w->weekOfYear)->toArray();
        $weekNumbers = collect($weeks)->map(fn ($w) => $w->weekOfYear)->toArray();

        $usersQuery = User::whereHas('assignedTasks')
            ->when(! empty($departmentIds), fn ($q) => $q->whereHas('departments', fn ($dq) => $dq->whereIn('departments.id', $departmentIds)));
        if ($assigneeId) {
            $usersQuery->where('id', $assigneeId);
        }
        $users = $usersQuery->get();

        // Bulk-load all data in 3 aggregated queries instead of 52 × 3 × N individual queries.
        $completedByUserWeek = Task::query()
            ->selectRaw('assigned_to, '.$this->weekExpr('updated_at').' as week, COUNT(*) as count')
            ->whereIn('status', $completedSlugs)
            ->whereBetween('updated_at', [$start, $end])
            ->when($assigneeId, fn ($q) => $q->where('assigned_to', $assigneeId))
            ->when(! empty($departmentIds), fn ($q) => $q->whereHas('project', fn ($pq) => $pq->whereIn('department_id', $departmentIds)))
            ->groupBy('assigned_to', 'week')
            ->get()
            ->groupBy('assigned_to')
            ->map(fn ($rows) => $rows->pluck('count', 'week'));

        $overdueByUserWeek = Task::query()
            ->selectRaw('assigned_to, '.$this->weekExpr('due_date').' as week, COUNT(*) as count')
            ->whereNotIn('status', $completedSlugs)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$start, $end])
            ->when($assigneeId, fn ($q) => $q->where('assigned_to', $assigneeId))
            ->when(! empty($departmentIds), fn ($q) => $q->whereHas('project', fn ($pq) => $pq->whereIn('department_id', $departmentIds)))
            ->groupBy('assigned_to', 'week')
            ->get()
            ->groupBy('assigned_to')
            ->map(fn ($rows) => $rows->pluck('count', 'week'));

        $activeByUserWeek = Task::query()
            ->selectRaw('assigned_to, '.$this->weekExpr('created_at').' as week, COUNT(*) as count')
            ->whereNotIn('status', $completedSlugs)
            ->whereBetween('created_at', [$start, $end])
            ->when($assigneeId, fn ($q) => $q->where('assigned_to', $assigneeId))
            ->when(! empty($departmentIds), fn ($q) => $q->whereHas('project', fn ($pq) => $pq->whereIn('department_id', $departmentIds)))
            ->groupBy('assigned_to', 'week')
            ->get()
            ->groupBy('assigned_to')
            ->map(fn ($rows) => $rows->pluck('count', 'week'));

        $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'];
        $datasets = [];
        $colorIndex = 0;

        foreach ($users as $user) {
            $color = $colors[$colorIndex % count($colors)];
            $colorIndex++;

            $userCompleted = $completedByUserWeek->get($user->id, collect());
            $userOverdue = $overdueByUserWeek->get($user->id, collect());
            $userActive = $activeByUserWeek->get($user->id, collect());

            if (! $statusType || $statusType === 'completed') {
                $datasets[] = [
                    'label' => $user->name.' (Completed)',
                    'data' => collect($weekNumbers)->map(fn ($week) => (int) ($userCompleted[$week] ?? 0))->toArray(),
                    'borderColor' => $color,
                    'backgroundColor' => $color.'33',
                    'fill' => false,
                    'tension' => 0.3,
                ];
            }

            if (! $statusType || $statusType === 'overdue') {
                $datasets[] = [
                    'label' => $user->name.' (Overdue)',
                    'data' => collect($weekNumbers)->map(fn ($week) => (int) ($userOverdue[$week] ?? 0))->toArray(),
                    'borderColor' => $color,
                    'backgroundColor' => $color.'33',
                    'borderDash' => [5, 5],
                    'fill' => false,
                    'tension' => 0.3,
                ];
            }

            if (! $statusType || $statusType === 'active') {
                $datasets[] = [
                    'label' => $user->name.' (Active)',
                    'data' => collect($weekNumbers)->map(fn ($week) => (int) ($userActive[$week] ?? 0))->toArray(),
                    'borderColor' => $color,
                    'backgroundColor' => $color.'33',
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

    private function weekExpr(string $column): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "CAST(strftime('%W', {$column}) AS INTEGER)"
            : "WEEK({$column}, 1)";
    }
}
