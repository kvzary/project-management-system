<?php

namespace App\Filament\Pages;

use App\Models\Task;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;

class TeamWorkMonitor extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.team-work-monitor';

    protected static ?string $navigationLabel = 'Work Monitor';

    protected static ?string $title = 'Team Work Monitor';

    protected static ?string $navigationGroup = 'Team';

    protected static ?int $navigationSort = 10;

    #[Url]
    public ?int $selectedUserId = null;

    #[Url]
    public ?string $dateFrom = null;

    #[Url]
    public ?string $dateTo = null;

    public function mount(): void
    {
        if (!$this->dateFrom) {
            $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        }
        if (!$this->dateTo) {
            $this->dateTo = now()->format('Y-m-d');
        }
    }

    public function getUsers(): \Illuminate\Support\Collection
    {
        return User::orderBy('name')->pluck('name', 'id');
    }

    public function updatedSelectedUserId(): void
    {
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Task::query()
                    ->when($this->selectedUserId, fn (Builder $q) => $q->where('assigned_to', $this->selectedUserId))
                    ->when(!$this->selectedUserId, fn (Builder $q) => $q->whereNotNull('assigned_to'))
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
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'done', 'completed' => 'success',
                        'in_progress' => 'info',
                        'in_review' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state)))
                    ->sortable(),
                TextColumn::make('priority')
                    ->badge()
                    ->color(fn ($state) => match ($state?->value ?? $state) {
                        'highest' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low', 'lowest' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('assignee.name')
                    ->label('Assigned To')
                    ->visible(fn () => !$this->selectedUserId)
                    ->sortable(),
                TextColumn::make('due_date')
                    ->date()
                    ->color(fn ($record) => $record->due_date?->isPast() && !in_array($record->status, ['done', 'completed']) ? 'danger' : null)
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'todo' => 'To Do',
                        'in_progress' => 'In Progress',
                        'in_review' => 'In Review',
                        'done' => 'Done',
                    ]),
                SelectFilter::make('project')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->striped();
    }

    public function getSelectedUser(): ?User
    {
        if (!$this->selectedUserId) {
            return null;
        }

        return User::find($this->selectedUserId);
    }

    public function getStats(): array
    {
        $query = Task::query()
            ->whereNull('deleted_at');

        if ($this->selectedUserId) {
            $query->where('assigned_to', $this->selectedUserId);
        } else {
            $query->whereNotNull('assigned_to');
        }

        $dateFrom = $this->dateFrom ? Carbon::parse($this->dateFrom) : now()->subDays(30);
        $dateTo = $this->dateTo ? Carbon::parse($this->dateTo)->endOfDay() : now();

        $totalTasks = (clone $query)->count();

        $completedTasks = (clone $query)
            ->whereIn('status', ['done', 'completed'])
            ->count();

        $inProgressTasks = (clone $query)
            ->where('status', 'in_progress')
            ->count();

        $overdueTasks = (clone $query)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->whereNotIn('status', ['done', 'completed'])
            ->count();

        $tasksCompletedInPeriod = (clone $query)
            ->whereIn('status', ['done', 'completed'])
            ->whereBetween('updated_at', [$dateFrom, $dateTo])
            ->count();

        $tasksCreatedInPeriod = (clone $query)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();

        $totalStoryPoints = (clone $query)->sum('story_points') ?? 0;

        $completedStoryPoints = (clone $query)
            ->whereIn('status', ['done', 'completed'])
            ->sum('story_points') ?? 0;

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

    public function getRecentActivity(): \Illuminate\Support\Collection
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
        $query = Task::query()
            ->whereNull('deleted_at');

        if ($this->selectedUserId) {
            $query->where('assigned_to', $this->selectedUserId);
        } else {
            $query->whereNotNull('assigned_to');
        }

        return $query
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

            $query = Task::query()
                ->whereNull('deleted_at')
                ->whereIn('status', ['done', 'completed'])
                ->whereBetween('updated_at', [$current, $weekEnd]);

            if ($this->selectedUserId) {
                $query->where('assigned_to', $this->selectedUserId);
            }

            $weeks[] = [
                'week' => $current->format('M d'),
                'completed' => $query->count(),
            ];

            $current->addWeek();
        }

        return $weeks;
    }
}
