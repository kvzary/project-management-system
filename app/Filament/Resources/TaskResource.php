<?php

namespace App\Filament\Resources;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Filament\Resources\TaskResource\Pages;
use App\Models\Department;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workflow;
use App\Models\WorkflowStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Project Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Task Details')
                    ->schema([
                        Forms\Components\Select::make('project_id')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Project (optional)')
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('sprint_id', null);
                                $set('status', null);
                            }),
                        Forms\Components\Select::make('sprint_id')
                            ->relationship(
                                'sprint',
                                'name',
                                fn (Builder $query, Forms\Get $get) => $query->where('project_id', $get('project_id'))
                            )
                            ->searchable()
                            ->preload()
                            ->label('Sprint (Optional)'),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('description')
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
                Forms\Components\Section::make('Classification')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options(TaskType::class)
                            ->default(TaskType::TASK)
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('status')
                            ->options(function (Forms\Get $get) {
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
                        Forms\Components\Select::make('priority')
                            ->options(TaskPriority::class)
                            ->default(TaskPriority::MEDIUM)
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('story_points')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->label('Story Points'),
                    ])->columns(4),
                Forms\Components\Section::make('Assignment')
                    ->schema([
                        Forms\Components\Select::make('reporter_id')
                            ->relationship('reporter', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => auth()->id()),
                        Forms\Components\Select::make('assigned_to')
                            ->relationship('assignee', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Assigned To'),
                        Forms\Components\DateTimePicker::make('due_date')
                            ->native(false)
                            ->displayFormat('Y-m-d H:i'),
                    ])->columns(3),
                Forms\Components\Section::make('People')
                    ->schema([
                        Forms\Components\Select::make('product_manager_id')
                            ->relationship('productManager', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Product Manager'),
                        Forms\Components\Select::make('creators')
                            ->relationship('creators', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->label('Creators'),
                    ])->columns(2)
                    ->collapsible(),
                Forms\Components\Section::make('Development')
                    ->schema([
                        Forms\Components\TextInput::make('branch')
                            ->label('GitHub Branch')
                            ->placeholder('feature/task-123')
                            ->helperText('Branch name that will link to the project repository'),
                    ])
                    ->collapsible()
                    ->collapsed(),
                Forms\Components\Section::make('Hierarchy')
                    ->schema([
                        Forms\Components\Select::make('parent_id')
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
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('project.name')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('sprint.name')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->placeholder('No sprint'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->status_label)
                    ->color(fn ($record) => $record->status_color),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignee.name')
                    ->sortable()
                    ->searchable()
                    ->label('Assigned To')
                    ->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('reporter.name')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('story_points')
                    ->numeric()
                    ->sortable()
                    ->toggleable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->toggleable()
                    ->color(fn ($record) => $record->due_date && $record->due_date < now() && ! $record->isCompleted() ? 'danger' : null),
                Tables\Columns\TextColumn::make('watchers_count')
                    ->counts('watchers')
                    ->label('Watchers')
                    ->icon('heroicon-o-eye')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('hide_done')
                    ->label('Hide Done')
                    ->query(fn (Builder $query) => $query->whereNotIn('status', [TaskStatus::DONE->value]))
                    ->default(true),
                Tables\Filters\SelectFilter::make('department')
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
                Tables\Filters\SelectFilter::make('project')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('sprint')
                    ->relationship('sprint', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('type')
                    ->options(TaskType::class),
                Tables\Filters\SelectFilter::make('status')
                    ->options(fn () => WorkflowStatus::query()
                        ->select('slug', 'name')
                        ->distinct('slug')
                        ->pluck('name', 'slug')
                        ->toArray()),
                Tables\Filters\SelectFilter::make('priority')
                    ->options(TaskPriority::class),
                Tables\Filters\SelectFilter::make('assignee')
                    ->relationship('assignee', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'view' => Pages\ViewTask::route('/{record}'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
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
            ->with(['project.workflow.statuses'])
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
