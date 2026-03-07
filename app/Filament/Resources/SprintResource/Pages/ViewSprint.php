<?php

namespace App\Filament\Resources\SprintResource\Pages;

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use App\Filament\Pages\TasksKanbanBoard;
use App\Filament\Resources\SprintResource;
use App\Filament\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkflowStatus;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class ViewSprint extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = SprintResource::class;

    protected static string $view = 'filament.resources.sprint-resource.pages.view-sprint';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->record->load(['project', 'tasks.assignee']);
    }

    public function getTitle(): string|Htmlable
    {
        return $this->record->name;
    }

    public function getBreadcrumbs(): array
    {
        return [
            SprintResource::getUrl('index') => 'Sprints',
            $this->record->name,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_kanban')
                ->label('View on Kanban')
                ->icon('heroicon-o-view-columns')
                ->color('gray')
                ->url(TasksKanbanBoard::getUrl(['sprintFilter' => $this->record->id])),

            Action::make('create_task')
                ->label('Create Task')
                ->icon('heroicon-o-plus')
                ->form([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255),
                    Select::make('type')
                        ->options(TaskType::class)
                        ->default(TaskType::TASK)
                        ->required()
                        ->native(false),
                    Select::make('priority')
                        ->options(TaskPriority::class)
                        ->default(TaskPriority::MEDIUM)
                        ->required()
                        ->native(false),
                    Select::make('assigned_to')
                        ->label('Assignee')
                        ->options(User::orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload(),
                    TextInput::make('story_points')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100),
                    DateTimePicker::make('due_date')
                        ->native(false),
                ])
                ->action(function (array $data): void {
                    Task::create(array_merge($data, [
                        'sprint_id' => $this->record->id,
                        'project_id' => $this->record->project_id,
                        'reporter_id' => auth()->id(),
                        'status' => 'todo',
                    ]));
                })
                ->slideOver(),

            Action::make('edit')
                ->label('Edit Sprint')
                ->icon('heroicon-o-pencil')
                ->url(SprintResource::getUrl('edit', ['record' => $this->record])),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Task::query()
                    ->where('sprint_id', $this->record->id)
                    ->with(['assignee', 'project'])
                    ->whereNull('deleted_at')
            )
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->limit(50)
                    ->url(fn (Task $record) => TaskResource::getUrl('view', ['record' => $record])),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->status_label)
                    ->color(fn ($record) => $record->status_color)
                    ->sortable(),
                TextColumn::make('priority')
                    ->badge()
                    ->sortable(),
                TextColumn::make('assignee.name')
                    ->label('Assignee')
                    ->placeholder('Unassigned')
                    ->sortable(),
                TextColumn::make('story_points')
                    ->label('SP')
                    ->alignCenter()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->due_date?->isPast() && ! $record->isCompleted() ? 'danger' : null),
            ])
            ->defaultSort('created_at', 'asc')
            ->striped()
            ->paginated(false);
    }

    public function getStats(): array
    {
        $completedSlugs = WorkflowStatus::where('is_completed', true)->pluck('slug')->toArray();

        $tasks = $this->record->tasks()->whereNull('deleted_at');

        $total = (clone $tasks)->count();
        $completed = (clone $tasks)->whereIn('status', $completedSlugs)->count();
        $inProgress = (clone $tasks)->where('status', 'in_progress')->count();
        $todo = (clone $tasks)->whereNotIn('status', $completedSlugs)->where('status', '!=', 'in_progress')->count();

        $totalPoints = (clone $tasks)->sum('story_points') ?? 0;
        $completedPoints = (clone $tasks)->whereIn('status', $completedSlugs)->sum('story_points') ?? 0;

        $progress = $total > 0 ? round(($completed / $total) * 100) : 0;

        return compact('total', 'completed', 'inProgress', 'todo', 'totalPoints', 'completedPoints', 'progress');
    }
}
