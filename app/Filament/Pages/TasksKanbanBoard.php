<?php

namespace App\Filament\Pages;

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use App\Models\Department;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use App\Models\Workflow;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardPage;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Components\CardFlex;

class TasksKanbanBoard extends BoardPage
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-view-columns';

    protected static ?string $navigationLabel = 'Kanban Board';

    protected static ?string $title = 'Tasks Board';

    protected static string|\UnitEnum|null $navigationGroup = 'Project Management';

    protected static ?int $navigationSort = 5;

    public function board(Board $board): Board
    {
        $projectId = $this->tableFilters['project_id']['value'] ?? null;

        $workflow = null;

        if ($projectId) {
            $project = Project::with('workflow.statuses')->find($projectId);
            $workflow = $project?->getWorkflow();
        }

        $workflow ??= Workflow::getDefault() ?? Workflow::with('statuses')->first();

        $columns = $workflow->statuses->map(
            fn ($status) => Column::make($status->slug)
                ->label($status->name)
                ->color($status->color)
        )->toArray();

        return $board
            ->query(
                Task::query()
                    ->with(['project', 'assignee', 'sprint'])
                    ->whereNull('deleted_at')
            )
            ->columnIdentifier('status')
            ->positionIdentifier('position')
            ->columns($columns)
            ->cardSchema(function ($schema) {
                $record = $schema->getRecord();

                return $schema->components([
                    CardFlex::make([
                        TextEntry::make('identifier')
                            ->hiddenLabel()
                            ->badge()
                            ->color('gray')
                            ->getStateUsing(fn () => $record->identifier),
                        TextEntry::make('priority')
                            ->hiddenLabel()
                            ->badge()
                            ->color(fn () => $record->priority?->getColor())
                            ->getStateUsing(fn () => $record->priority?->getLabel() ?? '—'),
                        TextEntry::make('type')
                            ->hiddenLabel()
                            ->badge()
                            ->color(fn () => $record->type?->colour() ?? 'gray')
                            ->getStateUsing(fn () => $record->type?->label() ?? '—'),
                    ]),
                    CardFlex::make([
                        TextEntry::make('assignee.name')
                            ->hiddenLabel()
                            ->icon('heroicon-m-user-circle')
                            ->placeholder('Unassigned'),
                        TextEntry::make('due_date')
                            ->hiddenLabel()
                            ->icon('heroicon-m-calendar-days')
                            ->date('M d')
                            ->hidden(fn () => $record->due_date === null),
                    ]),
                ]);
            })
            ->filters([
                SelectFilter::make('department_id')
                    ->label('Department')
                    ->options(function () {
                        $user = auth()->user();

                        return $user->isSystemAdmin()
                            ? Department::orderBy('name')->pluck('name', 'id')
                            : $user->departments()->orderBy('name')->pluck('name', 'departments.id');
                    })
                    ->query(fn (Builder $query, array $data) => filled($data['value'])
                        ? $query->whereHas('project', fn ($q) => $q->where('department_id', $data['value']))
                        : $query),
                SelectFilter::make('project_id')
                    ->label('Project')
                    ->options(fn () => Project::orderBy('name')->pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => filled($data['value'])
                        ? $query->where('project_id', $data['value'])
                        : $query),
                SelectFilter::make('sprint_id')
                    ->label('Sprint')
                    ->options(fn () => Sprint::orderBy('name')->pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => filled($data['value'])
                        ? $query->where('sprint_id', $data['value'])
                        : $query),
                SelectFilter::make('assigned_to')
                    ->label('Assignee')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => filled($data['value'])
                        ? $query->where('assigned_to', $data['value'])
                        : $query),
            ])
            ->filtersFormColumns(4)
            ->recordActions([
                Action::make('editTask')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil-square')
                    ->slideOver()
                    ->fillForm(function (Task $record): array {
                        return array_merge($record->toArray(), [
                            'assignees' => $record->assignees()->pluck('users.id')->toArray(),
                        ]);
                    })
                    ->schema($this->getEditTaskFormSchema())
                    ->action(function (Task $record, array $data): void {
                        $assignees = $data['assignees'] ?? null;
                        unset($data['assignees'], $data['assigned_to']);

                        if ($assignees !== null) {
                            $record->syncAssignees($assignees);
                        }

                        $record->update($data);
                    }),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createTask')
                ->label('New Task')
                ->icon('heroicon-o-plus')
                ->schema($this->getCreateTaskFormSchema())
                ->action(function (array $data): void {
                    $assignees = $data['assignees'] ?? [];
                    unset($data['assignees']);

                    $status = $data['status'] ?? Workflow::getDefault()?->statuses?->first()?->slug ?? 'todo';
                    $data['reporter_id'] = auth()->id();
                    $data['assigned_to'] = $assignees[0] ?? null;
                    $data['position'] = $this->getBoardPositionInColumn($status, 'bottom');

                    $task = Task::create($data);

                    if (! empty($assignees)) {
                        $task->assignees()->sync($assignees);
                    }
                }),
        ];
    }

    protected function getCreateTaskFormSchema(): array
    {
        return [
            Select::make('project_id')
                ->label('Project')
                ->options(Project::pluck('name', 'id'))
                ->required()
                ->searchable()
                ->live(),
            Select::make('sprint_id')
                ->label('Sprint')
                ->options(fn (Get $get) => Sprint::where('project_id', $get('project_id'))->pluck('name', 'id'))
                ->searchable(),
            TextInput::make('title')
                ->required()
                ->maxLength(255),
            RichEditor::make('description')
                ->toolbarButtons(['bold', 'bulletList', 'orderedList', 'italic', 'link']),
            Select::make('type')
                ->options(TaskType::class)
                ->default(TaskType::TASK)
                ->required()
                ->native(false),
            Select::make('status')
                ->options(function (Get $get) {
                    $projectId = $get('project_id');

                    if ($projectId) {
                        $project = Project::find($projectId);

                        if ($project) {
                            return $project->getStatusOptions();
                        }
                    }

                    return Workflow::getDefault()?->getStatusOptions() ?? [];
                })
                ->required()
                ->native(false),
            Select::make('priority')
                ->options(TaskPriority::class)
                ->default(TaskPriority::MEDIUM)
                ->required()
                ->native(false),
            Select::make('assignees')
                ->label('Assignees')
                ->multiple()
                ->options(User::pluck('name', 'id'))
                ->searchable(),
            TextInput::make('story_points')
                ->numeric()
                ->minValue(0)
                ->maxValue(100),
            DateTimePicker::make('due_date')
                ->native(false),
        ];
    }

    protected function getEditTaskFormSchema(): array
    {
        return [
            TextInput::make('title')
                ->required()
                ->maxLength(255),
            RichEditor::make('description')
                ->toolbarButtons(['bold', 'bulletList', 'orderedList', 'italic', 'link']),
            Select::make('type')
                ->options(TaskType::class)
                ->required()
                ->native(false),
            Select::make('status')
                ->options(function (Get $get) {
                    return Workflow::getDefault()?->getStatusOptions() ?? [];
                })
                ->required()
                ->native(false),
            Select::make('priority')
                ->options(TaskPriority::class)
                ->required()
                ->native(false),
            TextInput::make('story_points')
                ->numeric()
                ->minValue(0)
                ->maxValue(100),
            Select::make('assignees')
                ->label('Assignees')
                ->multiple()
                ->options(User::pluck('name', 'id'))
                ->searchable(),
            DateTimePicker::make('due_date')
                ->native(false),
        ];
    }
}
