<?php

namespace App\Filament\Pages;

use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkflowStatus;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class TeamWorkMonitor extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected string $view = 'filament.pages.team-work-monitor';

    protected static ?string $navigationLabel = 'Work Monitor';

    protected static ?string $title = 'Team Work Monitor';

    protected static string|\UnitEnum|null $navigationGroup = 'Team';

    protected static ?int $navigationSort = 10;

    #[Url]
    public ?int $selectedUserId = null;

    #[Url]
    public ?int $sprintFilter = null;

    #[Url]
    public ?string $statusCardFilter = null;

    #[Url]
    public ?string $dateFrom = null;

    #[Url]
    public ?string $dateTo = null;

    public function mount(): void
    {
        if (! $this->dateFrom) {
            $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        }
        if (! $this->dateTo) {
            $this->dateTo = now()->format('Y-m-d');
        }
    }

    public function getUsers(): Collection
    {
        return User::orderBy('name')->pluck('name', 'id');
    }

    public function getSprints(): Collection
    {
        return Sprint::query()
            ->when($this->selectedUserId, fn ($q) => $q->whereHas('tasks', fn ($tq) => $tq->where('assigned_to', $this->selectedUserId)))
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    public function updatedSelectedUserId(): void
    {
        $this->sprintFilter = null;
        $this->resetTable();
    }

    public function updatedSprintFilter(): void
    {
        $this->resetTable();
    }

    public function setStatusCardFilter(string $value): void
    {
        $this->statusCardFilter = $this->statusCardFilter === $value ? null : $value;
        $this->resetTable();
        $this->dispatch('scroll-to-tasks');
    }

    public function table(Table $table): Table
    {
        $completedSlugs = WorkflowStatus::where('is_completed', true)->pluck('slug')->toArray();

        return $table
            ->query(
                Task::query()
                    ->when($this->selectedUserId, fn (Builder $q) => $q->where('assigned_to', $this->selectedUserId))
                    ->when(! $this->selectedUserId, fn (Builder $q) => $q->whereNotNull('assigned_to'))
                    ->when($this->sprintFilter, fn (Builder $q) => $q->where('sprint_id', $this->sprintFilter))
                    ->when($this->statusCardFilter === '_overdue', fn (Builder $q) => $q
                        ->whereNotNull('due_date')
                        ->where('due_date', '<', now())
                        ->whereNotIn('status', $completedSlugs))
                    ->when($this->statusCardFilter === '_completed', fn (Builder $q) => $q->whereIn('status', $completedSlugs))
                    ->when($this->statusCardFilter === 'in_progress', fn (Builder $q) => $q->where('status', 'in_progress'))
                    ->with(['project', 'assignee', 'sprint'])
                    ->whereNull('deleted_at')
            )
            ->columns([
                TextColumn::make('identifier')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->limit(40)
                    ->searchable()
                    ->tooltip(fn ($record) => $record->title),
                TextColumn::make('project.name')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('sprint.name')
                    ->label('Sprint')
                    ->placeholder('—')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->status_label)
                    ->color(fn ($record) => $record->status_color)
                    ->sortable(),
                TextColumn::make('priority')
                    ->badge()
                    ->color(fn ($state) => match ($state?->value ?? $state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('assignee.name')
                    ->label('Assigned To')
                    ->visible(fn () => ! $this->selectedUserId)
                    ->sortable(),
                TextColumn::make('story_points')
                    ->label('SP')
                    ->alignCenter()
                    ->placeholder('—')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->date()
                    ->icon(fn ($record) => $record->due_date?->isPast() && ! $record->isCompleted() ? 'heroicon-s-exclamation-circle' : null)
                    ->iconColor('danger')
                    ->color(fn ($record) => $record->due_date?->isPast() && ! $record->isCompleted() ? 'danger' : null)
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(fn () => WorkflowStatus::query()
                        ->select('slug', 'name')
                        ->distinct('slug')
                        ->pluck('name', 'slug')
                        ->toArray()),
                SelectFilter::make('sprint')
                    ->relationship('sprint', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('project')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('due_date', 'asc')
            ->striped();
    }

    public function getSelectedUser(): ?User
    {
        if (! $this->selectedUserId) {
            return null;
        }

        return User::find($this->selectedUserId);
    }

    public function getStats(): array
    {
        $completedSlugs = WorkflowStatus::where('is_completed', true)->pluck('slug')->toArray();

        $query = Task::query()
            ->whereNull('deleted_at')
            ->when($this->selectedUserId, fn ($q) => $q->where('assigned_to', $this->selectedUserId), fn ($q) => $q->whereNotNull('assigned_to'))
            ->when($this->sprintFilter, fn ($q) => $q->where('sprint_id', $this->sprintFilter));

        $dateFrom = $this->dateFrom ? Carbon::parse($this->dateFrom) : now()->subDays(30);
        $dateTo = $this->dateTo ? Carbon::parse($this->dateTo)->endOfDay() : now();

        $totalTasks = (clone $query)->count();

        $completedTasks = (clone $query)->whereIn('status', $completedSlugs)->count();

        $inProgressTasks = (clone $query)->where('status', 'in_progress')->count();

        $overdueTasks = (clone $query)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->whereNotIn('status', $completedSlugs)
            ->count();

        $tasksCompletedInPeriod = (clone $query)
            ->whereIn('status', $completedSlugs)
            ->whereBetween('updated_at', [$dateFrom, $dateTo])
            ->count();

        $tasksCreatedInPeriod = (clone $query)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();

        $totalStoryPoints = (clone $query)->sum('story_points') ?? 0;

        $completedStoryPoints = (clone $query)->whereIn('status', $completedSlugs)->sum('story_points') ?? 0;

        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;

        return [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'in_progress_tasks' => $inProgressTasks,
            'overdue_tasks' => $overdueTasks,
            'tasks_completed_in_period' => $tasksCompletedInPeriod,
            'tasks_created_in_period' => $tasksCreatedInPeriod,
            'completion_rate' => $completionRate,
            'total_story_points' => $totalStoryPoints,
            'completed_story_points' => $completedStoryPoints,
        ];
    }

    public function getWorkloadByUser(): Collection
    {
        return User::query()
            ->withCount(['assignedTasks as task_count' => fn ($q) => $q
                ->whereNull('deleted_at')
                ->when($this->sprintFilter, fn ($sq) => $sq->where('sprint_id', $this->sprintFilter))])
            ->get(['id', 'name', 'email'])
            ->filter(fn ($user) => $user->task_count > 0)
            ->sortByDesc('task_count')
            ->values();
    }

    public function getRecentActivity(): Collection
    {
        $query = Task::query()
            ->whereNull('deleted_at')
            ->with(['project', 'assignee']);

        if ($this->selectedUserId) {
            $query->where('assigned_to', $this->selectedUserId);
        }

        return $query
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($task) => [
                'task' => $task,
                'identifier' => $task->identifier,
                'title' => $task->title,
                'status' => $task->status,
                'updated_at' => $task->updated_at,
                'project' => $task->project?->name,
            ]);
    }

    public function getTasksByStatus(): array
    {
        return Task::query()
            ->whereNull('deleted_at')
            ->when($this->selectedUserId, fn ($q) => $q->where('assigned_to', $this->selectedUserId), fn ($q) => $q->whereNotNull('assigned_to'))
            ->when($this->sprintFilter, fn ($q) => $q->where('sprint_id', $this->sprintFilter))
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    public function getWeeklyProgress(): array
    {
        $weeks = [];
        $dateFrom = $this->dateFrom ? Carbon::parse($this->dateFrom) : now()->subDays(30);
        $dateTo = $this->dateTo ? Carbon::parse($this->dateTo) : now();

        $current = $dateFrom->copy()->startOfWeek();

        while ($current <= $dateTo) {
            $weekEnd = $current->copy()->endOfWeek();

            $completedSlugs = WorkflowStatus::where('is_completed', true)->pluck('slug')->toArray();

            $query = Task::query()
                ->whereNull('deleted_at')
                ->whereIn('status', $completedSlugs)
                ->whereBetween('updated_at', [$current, $weekEnd])
                ->when($this->selectedUserId, fn ($q) => $q->where('assigned_to', $this->selectedUserId))
                ->when($this->sprintFilter, fn ($q) => $q->where('sprint_id', $this->sprintFilter));

            $weeks[] = [
                'week' => $current->format('M d'),
                'completed' => $query->count(),
            ];

            $current->addWeek();
        }

        return $weeks;
    }
}
