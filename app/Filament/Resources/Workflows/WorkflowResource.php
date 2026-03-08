<?php

namespace App\Filament\Resources\Workflows;

use App\Filament\Resources\Concerns\HasResourcePermissions;
use App\Filament\Resources\Workflows\Pages\CreateWorkflow;
use App\Filament\Resources\Workflows\Pages\EditWorkflow;
use App\Filament\Resources\Workflows\Pages\ListWorkflows;
use App\Models\Workflow;
use App\Models\WorkflowStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class WorkflowResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = Workflow::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Workflow Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                        Toggle::make('is_default')
                            ->label('Default Workflow')
                            ->helperText('Only one workflow can be the default. Setting this will unset the current default.'),
                    ])->columns(2),
                Section::make('Statuses')
                    ->schema([
                        Repeater::make('statuses')
                            ->relationship()
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, ?string $state, ?string $old, Get $get) {
                                        if (! $get('slug') || $get('slug') === Str::slug($old ?? '', '_')) {
                                            $set('slug', Str::slug($state ?? '', '_'));
                                        }
                                    }),
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->rules(['alpha_dash'])
                                    ->helperText('Used internally. Auto-generated from name.'),
                                Select::make('color')
                                    ->options(WorkflowStatus::colorOptions())
                                    ->required()
                                    ->default('gray')
                                    ->native(false),
                                Toggle::make('is_completed')
                                    ->label('Marks task as completed')
                                    ->default(false),
                            ])
                            ->columns(['default' => 1, 'sm' => 2, 'xl' => 4])
                            ->reorderable('position')
                            ->orderColumn('position')
                            ->defaultItems(0)
                            ->addActionLabel('Add Status')
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Split::make([
                        TextColumn::make('name')
                            ->searchable()
                            ->sortable()
                            ->weight('bold'),
                        IconColumn::make('is_default')
                            ->boolean()
                            ->label('')
                            ->trueIcon('heroicon-o-check-badge')
                            ->falseIcon('')
                            ->trueColor('success')
                            ->tooltip('Default workflow')
                            ->alignEnd()
                            ->grow(false),
                    ]),
                    TextColumn::make('description')
                        ->limit(80)
                        ->placeholder('No description')
                        ->color('gray')
                        ->size('xs'),
                    Split::make([
                        TextColumn::make('statuses_count')
                            ->counts('statuses')
                            ->icon('heroicon-o-tag')
                            ->iconColor('gray')
                            ->color('gray')
                            ->size('xs')
                            ->label('')
                            ->suffix(fn ($state) => ' '.((int) $state === 1 ? 'status' : 'statuses')),
                        TextColumn::make('projects_count')
                            ->counts('projects')
                            ->icon('heroicon-o-briefcase')
                            ->iconColor('gray')
                            ->color('gray')
                            ->size('xs')
                            ->label('')
                            ->suffix(fn ($state) => ' '.((int) $state === 1 ? 'project' : 'projects'))
                            ->alignEnd(),
                    ]),
                ])->space(2),
            ])
            ->contentGrid([
                'sm' => 1,
                'md' => 2,
                'xl' => 3,
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkflows::route('/'),
            'create' => CreateWorkflow::route('/create'),
            'edit' => EditWorkflow::route('/{record}/edit'),
        ];
    }
}
