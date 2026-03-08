<?php

namespace App\Filament\Resources\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Filament\Resources\Tasks\Pages\CreateTask;
use App\Filament\Resources\Tasks\Pages\EditTask;
use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Filament\Resources\Tasks\Pages\ViewTask;
use App\Models\Department;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workflow;
use App\Models\WorkflowStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Project Management';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Task Details')
                    ->schema([
                        Select::make('project_id')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Project (optional)')
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('sprint_id', null);
                                $set('status', null);
                            }),
                        Select::make('sprint_id')
                            ->relationship(
                                'sprint',
                                'name',
                                fn (Builder $query, Get $get) => $query->where('project_id', $get('project_id'))
                            )
                            ->searchable()
                            ->preload()
                            ->label('Sprint (Optional)'),
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        RichEditor::make('description')
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'bulletList',
                                'orderedList',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'redo',
                                'undo',
                            ]),
                    ])->columns(2),
                Section::make('Classification')
                    ->schema([
                        Select::make('type')
                            ->options(TaskType::class)
                            ->default(TaskType::TASK)
                            ->required()
                            ->native(false),
                        Select::make('status')
                            ->options(function (Get $get) {
                                if ($projectId = $get('project_id')) {
                                    $project = Project::with('workflow.statuses')->find($projectId);
                                    if ($project) {
                                        return $project->getStatusOptions();
                                    }
                                }

                                return Workflow::getDefault()?->getStatusOptions() ?? [];
                            })
                            ->default('todo')
                            ->required()
                            ->native(false),
                        Select::make('priority')
                            ->options(TaskPriority::class)
                            ->default(TaskPriority::MEDIUM)
                            ->required()
                            ->native(false),
                        TextInput::make('story_points')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->label('Story Points'),
                    ])->columns(4),
                Section::make('Assignment')
                    ->schema([
                        Select::make('reporter_id')
                            ->relationship('reporter', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => auth()->id()),
                        Select::make('assignees')
                            ->relationship('assignees', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->label('Assignees'),
                        DateTimePicker::make('due_date')
                            ->native(false)
                            ->displayFormat('Y-m-d H:i'),
                    ])->columns(3),
                Section::make('People')
                    ->schema([
                        Select::make('product_manager_id')
                            ->relationship('productManager', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Product Manager'),
                        Select::make('creators')
                            ->relationship('creators', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->label('Creators'),
                    ])->columns(2)
                    ->collapsible(),
                Section::make('Development')
                    ->schema([
                        TextInput::make('branch')
                            ->label('GitHub Branch')
                            ->placeholder('feature/task-123')
                            ->helperText('Branch name that will link to the project repository'),
                    ])
                    ->collapsible()
                    ->collapsed(),
                Section::make('Hierarchy')
                    ->schema([
                        Select::make('parent_id')
                            ->relationship('parent', 'title')
                            ->searchable()
                            ->preload()
                            ->label('Parent Task'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    // Meta: type icon + identifier
                    Split::make([
                        TextColumn::make('type')
                            ->formatStateUsing(fn () => '')
                            ->icon(fn ($state) => match ($state?->value) {
                                'bug' => 'heroicon-o-bug-ant',
                                'story' => 'heroicon-o-bookmark',
                                'epic' => 'heroicon-o-bolt',
                                'subtask' => 'heroicon-o-minus',
                                default => 'heroicon-o-check-circle',
                            })
                            ->iconColor(fn ($state) => match ($state?->value) {
                                'bug' => 'danger',
                                'story' => 'success',
                                'epic' => 'warning',
                                'subtask' => 'info',
                                default => 'primary',
                            })
                            ->grow(false),
                        TextColumn::make('identifier')
                            ->color('gray')
                            ->size('xs'),
                    ]),

                    // Title
                    TextColumn::make('title')
                        ->searchable()
                        ->weight('bold')
                        ->lineClamp(2),

                    // Status + priority
                    Split::make([
                        TextColumn::make('status')
                            ->badge()
                            ->formatStateUsing(fn ($record) => $record->status_label)
                            ->color(fn ($record) => $record->status_color)
                            ->grow(false),
                        TextColumn::make('priority')
                            ->badge()
                            ->alignEnd()
                            ->grow(false),
                    ]),

                    // Assignee + due date
                    Split::make([
                        TextColumn::make('assignee_name')
                            ->getStateUsing(fn ($record) => $record->assignees->first()?->name)
                            ->placeholder('Unassigned')
                            ->icon('heroicon-o-user')
                            ->iconColor('gray')
                            ->color('gray')
                            ->size('xs'),
                        TextColumn::make('due_date')
                            ->date('M d')
                            ->placeholder('—')
                            ->icon('heroicon-o-calendar')
                            ->iconColor('gray')
                            ->color(fn ($record) => $record->due_date?->isPast() && ! $record->isCompleted() ? 'danger' : 'gray')
                            ->size('xs')
                            ->alignEnd()
                            ->grow(false),
                    ]),
                ])->space(2),
            ])
            ->contentGrid(['sm' => 1, 'md' => 2, 'xl' => 3])
            ->groups([
                Group::make('project_id')
                    ->label('Project')
                    ->collapsible()
                    ->titlePrefixedWithLabel(false)
                    ->getTitleFromRecordUsing(
                        fn ($record) => $record->project
                            ? $record->project->name.' ('.$record->project->key.')'
                            : 'No Project'
                    )
                    ->orderQueryUsing(
                        fn (Builder $query, string $direction) => $query->orderByRaw('project_id IS NULL')->orderBy('project_id', 'asc')->orderBy('created_at', 'desc')
                    ),
            ])
            ->defaultGroup('project_id')
            ->filters([
                Filter::make('hide_done')
                    ->label('Hide Done')
                    ->query(fn (Builder $query) => $query->whereNotIn('status', [TaskStatus::DONE->value]))
                    ->default(true),
                SelectFilter::make('department')
                    ->label('Department')
                    ->options(function () {
                        $user = auth()->user();
                        if ($user->isSystemAdmin()) {
                            return Department::orderBy('name')->pluck('name', 'id');
                        }

                        return $user->departments()->orderBy('name')->pluck('name', 'departments.id');
                    })
                    ->query(fn (Builder $query, array $data) => $data['value']
                        ? $query->whereHas('project', fn ($pq) => $pq->where('department_id', $data['value']))
                        : $query)
                    ->searchable()
                    ->preload(),
                SelectFilter::make('project')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('sprint')
                    ->relationship('sprint', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('type')
                    ->options(TaskType::class),
                SelectFilter::make('status')
                    ->options(fn () => WorkflowStatus::query()
                        ->select('slug', 'name')
                        ->distinct('slug')
                        ->pluck('name', 'slug')
                        ->toArray()),
                SelectFilter::make('priority')
                    ->options(TaskPriority::class),
                SelectFilter::make('assignee')
                    ->relationship('assignee', 'name')
                    ->searchable()
                    ->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn ($record) => static::getUrl('view', ['record' => $record]));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTasks::route('/'),
            'create' => CreateTask::route('/create'),
            'view' => ViewTask::route('/{record}'),
            'edit' => EditTask::route('/{record}/edit'),
        ];
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        if ($user->isSystemAdmin()) {
            return true;
        }

        if ((int) $record->assigned_to === $user->id || (int) $record->reporter_id === $user->id) {
            return true;
        }

        if (! $record->project_id) {
            return false;
        }

        $departmentIds = $user->departments()->pluck('departments.id');

        return Project::where('id', $record->project_id)
            ->whereIn('department_id', $departmentIds)
            ->exists();
    }

    public static function canEdit(Model $record): bool
    {
        // Assignees and reporters can edit tasks assigned to them cross-department
        return static::canView($record);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['project.workflow.statuses', 'assignees'])
            ->withoutGlobalScopes([SoftDeletingScope::class]);

        $user = auth()->user();

        if ($user->isSystemAdmin()) {
            return $query;
        }

        $departmentIds = $user->departments()->pluck('departments.id');

        return $query->where(function (Builder $q) use ($departmentIds, $user) {
            // Tasks in projects belonging to the user's departments
            $q->whereHas('project', fn ($pq) => $pq->whereIn('department_id', $departmentIds))
            // OR tasks assigned to the user (cross-department)
                ->orWhere('assigned_to', $user->id)
            // OR tasks reported by the user
                ->orWhere('reporter_id', $user->id);
        });
    }
}
