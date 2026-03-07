<?php

namespace App\Filament\Widgets;

use App\Enums\ProjectStatus;
use App\Filament\Widgets\Concerns\HasDepartmentScope;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\WorkflowStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    use HasDepartmentScope;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $completedSlugs = WorkflowStatus::where('is_completed', true)->pluck('slug')->toArray();
        $departmentIds = $this->getDepartmentIds();

        $totalTasks = $this->scopeTasksToDepartments(Task::query(), $departmentIds)->count();
        $completedTasks = $this->scopeTasksToDepartments(Task::query(), $departmentIds)->whereIn('status', $completedSlugs)->count();
        $overdueTasks = $this->scopeTasksToDepartments(Task::overdue(), $departmentIds)->count();
        $completedThisWeek = $this->scopeTasksToDepartments(Task::query(), $departmentIds)
            ->whereIn('status', $completedSlugs)
            ->where('updated_at', '>=', now()->startOfWeek())
            ->count();
        $unassignedTasks = $this->scopeTasksToDepartments(Task::unassigned(), $departmentIds)
            ->whereNotIn('status', $completedSlugs)
            ->count();
        $sprintQuery = Sprint::where('status', 'active');
        if (! empty($departmentIds)) {
            $sprintQuery->whereHas('project', fn ($q) => $q->whereIn('department_id', $departmentIds));
        }
        $activeSprints = $sprintQuery->count();

        return [
            Stat::make('Active Projects', $this->scopeProjectsToDepartments(Project::where('status', ProjectStatus::ACTIVE), $departmentIds)->count())
                ->description('Total active projects')
                ->descriptionIcon('heroicon-m-folder-open')
                ->color('success'),

            Stat::make('Open Tasks', $totalTasks - $completedTasks)
                ->description($completedTasks . ' completed total')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info'),

            Stat::make('Overdue Tasks', $overdueTasks)
                ->description('Past due date')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdueTasks > 0 ? 'danger' : 'success'),

            Stat::make('Completed This Week', $completedThisWeek)
                ->description('Since ' . now()->startOfWeek()->format('M d'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Unassigned Tasks', $unassignedTasks)
                ->description('Open tasks with no assignee')
                ->descriptionIcon('heroicon-m-user-minus')
                ->color($unassignedTasks > 0 ? 'warning' : 'success'),

            Stat::make('Active Sprints', $activeSprints)
                ->description('Currently running sprints')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),
        ];
    }
}
