<?php

namespace App\Filament\Resources\Projects;

use App\Enums\ProjectStatus;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Models\Department;
use App\Models\Project;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static string|\UnitEnum|null $navigationGroup = 'Project Management';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Project Information')
                    ->schema([
                        Select::make('department_id')
                            ->label('Department')
                            ->options(function () {
                                $user = auth()->user();
                                if ($user->isSystemAdmin()) {
                                    return Department::orderBy('name')->pluck('name', 'id');
                                }

                                // Non-admins can only create projects in their own departments
                                return $user->departments()->orderBy('name')->pluck('name', 'departments.id');
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, Set $set) {
                                if ($operation === 'create') {
                                    $set('key', strtoupper(Str::slug($state, '_')));
                                }
                            }),
                        TextInput::make('key')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                            ->placeholder('e.g., PROJ')
                            ->helperText('Unique project identifier (e.g., PROJ-123)'),
                        Select::make('owner_id')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Project Owner'),
                        Select::make('status')
                            ->options(ProjectStatus::class)
                            ->default(ProjectStatus::ACTIVE)
                            ->required()
                            ->native(false),
                        Select::make('workflow_id')
                            ->relationship('workflow', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Workflow')
                            ->helperText('Determines the available task statuses for this project'),
                    ])->columns(2),
                Section::make('Description')
                    ->schema([
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
                    ]),
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
                        Select::make('members')
                            ->relationship('members', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->label('Team Members'),
                    ])
                    ->columns(3)
                    ->collapsible(),
                Section::make('Development')
                    ->schema([
                        TextInput::make('branch')
                            ->label('Branch')
                            ->placeholder('feature/epic-name')
                            ->helperText('Main branch for this epic/project'),
                        TextInput::make('pull_request_url')
                            ->label('Pull Request')
                            ->url()
                            ->placeholder('https://github.com/org/repo/pull/123')
                            ->helperText('Link to the main pull request'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    // Top row: key badge + status badge
                    Split::make([
                        TextColumn::make('key')
                            ->badge()
                            ->color('primary')
                            ->searchable()
                            ->grow(false),
                        TextColumn::make('status')
                            ->badge()
                            ->sortable()
                            ->alignEnd(),
                    ]),

                    // Project name
                    TextColumn::make('name')
                        ->weight('bold')
                        ->searchable()
                        ->size('sm'),

                    // Description preview
                    TextColumn::make('description')
                        ->html()
                        ->limit(90)
                        ->color('gray')
                        ->size('xs')
                        ->lineClamp(2),

                    // Department badge
                    TextColumn::make('department.name')
                        ->badge()
                        ->color('info')
                        ->size('xs'),

                    // Footer: stats + owner
                    Split::make([
                        Stack::make([
                            TextColumn::make('tasks_count')
                                ->counts('tasks')
                                ->icon('heroicon-o-clipboard-document-check')
                                ->iconColor('gray')
                                ->label('')
                                ->color('gray')
                                ->size('xs')
                                ->suffix(fn ($state) => ' '.((int) $state === 1 ? 'task' : 'tasks')),
                            TextColumn::make('sprints_count')
                                ->counts('sprints')
                                ->icon('heroicon-o-arrow-path')
                                ->iconColor('gray')
                                ->label('')
                                ->color('gray')
                                ->size('xs')
                                ->suffix(fn ($state) => ' '.((int) $state === 1 ? 'sprint' : 'sprints')),
                        ]),
                        TextColumn::make('owner.name')
                            ->icon('heroicon-o-user-circle')
                            ->iconColor('gray')
                            ->color('gray')
                            ->size('xs')
                            ->alignEnd(),
                    ]),
                ])->space(2),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->filters([
                Filter::make('hide_completed')
                    ->label('Hide Completed')
                    ->query(fn (Builder $query) => $query->whereNotIn('status', [ProjectStatus::COMPLETED->value]))
                    ->default(true),
                SelectFilter::make('department_id')
                    ->label('Department')
                    ->options(function () {
                        $user = auth()->user();
                        if ($user->isSystemAdmin()) {
                            return Department::orderBy('name')->pluck('name', 'id');
                        }

                        return $user->departments()->orderBy('name')->pluck('name', 'departments.id');
                    })
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options(ProjectStatus::class),
                SelectFilter::make('owner')
                    ->relationship('owner', 'name'),
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
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'view' => ViewProject::route('/{record}'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        if ($user->isSystemAdmin()) {
            return true;
        }

        $departmentIds = $user->departments()->pluck('departments.id');

        if ($departmentIds->contains($record->department_id)) {
            return true;
        }

        // Cross-department: user is assigned to at least one task in this project
        return $record->tasks()->where('assigned_to', $user->id)->exists();
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        if ($user->isSystemAdmin()) {
            return true;
        }

        // Only members of the project's own department may edit it
        return $user->departments()->where('departments.id', $record->department_id)->exists();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);

        $user = auth()->user();

        if ($user->isSystemAdmin()) {
            return $query;
        }

        $departmentIds = $user->departments()->pluck('departments.id');

        return $query->where(function (Builder $q) use ($departmentIds, $user) {
            // Projects in the user's departments
            $q->whereIn('department_id', $departmentIds)
            // OR projects where the user is assigned to at least one task
                ->orWhereHas('tasks', fn ($tq) => $tq->where('assigned_to', $user->id));
        });
    }
}
