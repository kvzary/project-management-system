<?php

namespace App\Filament\Resources;

use App\Enums\ProjectStatus;
use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'Project Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Project Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                if ($operation === 'create') {
                                    $set('key', strtoupper(Str::slug($state, '_')));
                                }
                            }),
                        Forms\Components\TextInput::make('key')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                            ->placeholder('e.g., PROJ')
                            ->helperText('Unique project identifier (e.g., PROJ-123)'),
                        Forms\Components\Select::make('owner_id')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Project Owner'),
                        Forms\Components\Select::make('status')
                            ->options(ProjectStatus::class)
                            ->default(ProjectStatus::ACTIVE)
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('workflow_id')
                            ->relationship('workflow', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Workflow')
                            ->helperText('Determines the available task statuses for this project'),
                    ])->columns(2),
                Forms\Components\Section::make('Description')
                    ->schema([
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
                    ]),
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
                        Forms\Components\Select::make('members')
                            ->relationship('members', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->label('Team Members'),
                    ])
                    ->columns(3)
                    ->collapsible(),
                Forms\Components\Section::make('Development')
                    ->schema([
                        Forms\Components\TextInput::make('branch')
                            ->label('Branch')
                            ->placeholder('feature/epic-name')
                            ->helperText('Main branch for this epic/project'),
                        Forms\Components\TextInput::make('pull_request_url')
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
                Tables\Columns\TextColumn::make('key')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner.name')
                    ->sortable()
                    ->searchable()
                    ->label('Owner'),
                Tables\Columns\TextColumn::make('tasks_count')
                    ->counts('tasks')
                    ->label('Tasks')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sprints_count')
                    ->counts('sprints')
                    ->label('Sprints')
                    ->sortable(),
                Tables\Columns\TextColumn::make('members_count')
                    ->counts('members')
                    ->label('Members')
                    ->icon('heroicon-o-users')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(ProjectStatus::class),
                Tables\Filters\SelectFilter::make('owner')
                    ->relationship('owner', 'name'),
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
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'view' => Pages\ViewProject::route('/{record}'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
