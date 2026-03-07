<?php

namespace App\Filament\Pages;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
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
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;

class TasksKanbanBoard extends KanbanBoard
{
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';

    protected static string $model = Task::class;

    protected static string $statusEnum = TaskStatus::class;

    protected static string $recordTitleAttribute = 'title';

    protected static string $recordStatusAttribute = 'status';

    protected static ?string $navigationLabel = 'Kanban Board';

    protected static ?string $title = 'Tasks Board';

    protected static ?string $navigationGroup = 'Project Management';

    protected static ?int $navigationSort = 5;

    protected string $editModalWidth = '3xl';

    protected bool $editModalSlideOver = true;

    #[Url]
    public ?int $departmentFilter = null;

    #[Url]
    public ?int $projectFilter = null;

    #[Url]
    public ?int $sprintFilter = null;

    #[Url]
    public ?int $assigneeFilter = null;

    protected ?Workflow $cachedWorkflow = null;

    public function mount(): void
    {
        parent::mount();
    }

    protected function getActiveWorkflow(): Workflow
    {
        if ($this->cachedWorkflow) {
            return $this->cachedWorkflow;
        }

        if ($this->projectFilter) {
            $project = Project::with('workflow.statuses')->find($this->projectFilter);
            if ($project) {
                return $this->cachedWorkflow = $project->getWorkflow();
            }
        }

        return $this->cachedWorkflow = Workflow::getDefault() ?? Workflow::with('statuses')->first();
    }

    protected function statuses(): Collection
    {
        $workflow = $this->getActiveWorkflow();

        return $workflow->statuses->map(fn ($status) => [
            'id' => $status->slug,
            'title' => $status->name,
            'color' => $status->color,
            'border_color' => $status->getCssBorderColorClass(),
            'dot_color' => $status->getCssDotColorClass(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createTask')
                ->label('Create Task')
                ->icon('heroicon-o-plus')
                ->form($this->getCreateTaskFormSchema())
                ->action(function (array $data): void {
                    $assignees = $data['assignees'] ?? [];
                    unset($data['assignees']);
                    $data['reporter_id'] = auth()->id();
                    $data['assigned_to'] = $assignees[0] ?? null;
                    $data['position'] = Task::where('status', $data['status'] ?? 'todo')->max('position') + 1;
                    $task = Task::create($data);
                    if (! empty($assignees)) {
                        $task->assignees()->sync($assignees);
                    }
                    $this->dispatch('kanban-refresh');
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
                    if ($get('project_id')) {
                        $project = Project::find($get('project_id'));
                        if ($project) {
                            return $project->getStatusOptions();
                        }
                    }

                    return $this->getActiveWorkflow()->getStatusOptions();
                })
                ->default('todo')
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

    public function filtersForm(Form $form): Form
    {
        $user = auth()->user();
        $departmentOptions = $user->isSystemAdmin()
            ? Department::orderBy('name')->pluck('name', 'id')
            : $user->departments()->orderBy('name')->pluck('name', 'departments.id');

        return $form
            ->schema([
                Select::make('departmentFilter')
                    ->label('Department')
                    ->options($departmentOptions)
                    ->placeholder('All Departments')
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn () => $this->projectFilter = null),
                Select::make('projectFilter')
                    ->label('Project')
                    ->options(function (Get $get) use ($user) {
                        $query = Project::query();
                        if ($get('departmentFilter')) {
                            $query->where('department_id', $get('departmentFilter'));
                        } elseif (! $user->isSystemAdmin()) {
                            $deptIds = $user->departments()->pluck('departments.id');
                            $query->whereIn('department_id', $deptIds);
                        }

                        return $query->pluck('name', 'id');
                    })
                    ->placeholder('All Projects')
                    ->searchable()
                    ->live(),
                Select::make('sprintFilter')
                    ->label('Sprint')
                    ->options(fn (Get $get) => $get('projectFilter')
                        ? Sprint::where('project_id', $get('projectFilter'))->pluck('name', 'id')
                        : Sprint::pluck('name', 'id'))
                    ->placeholder('All Sprints')
                    ->searchable()
                    ->live(),
                Select::make('assigneeFilter')
                    ->label('Assignee')
                    ->options(User::pluck('name', 'id'))
                    ->placeholder('All Assignees')
                    ->searchable()
                    ->live(),
            ])
            ->columns(4);
    }

    protected function records(): Collection
    {
        $query = Task::query()
            ->with(['project.workflow.statuses', 'assignee', 'assignees', 'sprint'])
            ->whereNull('deleted_at');

        if ($this->departmentFilter) {
            $query->whereHas('project', fn ($pq) => $pq->where('department_id', $this->departmentFilter));
        }

        if ($this->projectFilter) {
            $query->where('project_id', $this->projectFilter);
        }

        if ($this->sprintFilter) {
            $query->where('sprint_id', $this->sprintFilter);
        }

        if ($this->assigneeFilter) {
            $query->where('assigned_to', $this->assigneeFilter);
        }

        return $query->orderBy('position')->get();
    }

    public function onStatusChanged(int|string $recordId, string $status, array $fromOrderedIds, array $toOrderedIds): void
    {
        Task::find($recordId)->update(['status' => $status]);

        foreach ($toOrderedIds as $index => $id) {
            Task::where('id', $id)->update(['position' => $index]);
        }
    }

    public function onSortChanged(int|string $recordId, string $status, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            Task::where('id', $id)->update(['position' => $index]);
        }
    }

    protected function getEditModalFormSchema(int|string|null $recordId): array
    {
        $task = $recordId ? Task::find($recordId) : null;

        return [
            Section::make('Task Details')
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255),
                    RichEditor::make('description')
                        ->toolbarButtons([
                            'bold',
                            'bulletList',
                            'orderedList',
                            'italic',
                            'link',
                        ]),
                ]),
            Section::make('Classification')
                ->schema([
                    Select::make('type')
                        ->options(TaskType::class)
                        ->required()
                        ->native(false),
                    Select::make('status')
                        ->options(function () use ($task) {
                            if ($task?->project) {
                                return $task->project->getStatusOptions();
                            }

                            return $this->getActiveWorkflow()->getStatusOptions();
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
                ])->columns(2),
            Section::make('Assignment')
                ->schema([
                    Select::make('project_id')
                        ->relationship('project', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),
                    Select::make('sprint_id')
                        ->relationship(
                            'sprint',
                            'name',
                            fn (Builder $query, Get $get) => $query
                                ->where('project_id', $get('project_id'))
                                ->whereNull('deleted_at')
                        )
                        ->searchable()
                        ->preload(),
                    Select::make('assignees')
                        ->label('Assignees')
                        ->multiple()
                        ->options(User::pluck('name', 'id'))
                        ->searchable(),
                    DateTimePicker::make('due_date')
                        ->native(false),
                ])->columns(2),
        ];
    }

    protected function editRecord(int|string $recordId, array $data, array $state): void
    {
        $task = Task::find($recordId);
        $assignees = $data['assignees'] ?? null;
        unset($data['assignees']);

        if ($assignees !== null) {
            $data['assigned_to'] = $assignees[0] ?? null;
            $task->assignees()->sync($assignees);
        }

        $task->update($data);
    }

    public function deleteRecord(int|string $recordId): void
    {
        Task::find($recordId)?->delete();
        $this->dispatch('close-modal', id: 'kanban--edit-record-modal');
    }

    public function getEditModalTitle(): string
    {
        if ($this->editModalRecordId) {
            $task = Task::find($this->editModalRecordId);

            return $task?->title ?? 'Edit Task';
        }

        return 'Edit Task';
    }

    protected function getEditModalRecordData(int|string $recordId, array $data): array
    {
        $task = Task::with('assignees')->find($recordId);
        $taskData = $task->toArray();
        $taskData['assignees'] = $task->assignees->pluck('id')->toArray();

        return $taskData;
    }

    protected function additionalRecordData(\Illuminate\Database\Eloquent\Model $record): Collection
    {
        return collect([
            'project' => $record->project?->name,
            'project_key' => $record->project?->key,
            'assignee' => $record->assignee?->name,
            'priority' => $record->priority,
            'type' => $record->type,
            'story_points' => $record->story_points,
            'due_date' => $record->due_date?->format('M d'),
            'identifier' => $record->identifier,
        ]);
    }
}
