<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\HasResourcePermissions;
use App\Filament\Resources\WorkflowResource\Pages;
use App\Models\Workflow;
use App\Models\WorkflowStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class WorkflowResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = Workflow::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Workflow Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_default')
                            ->label('Default Workflow')
                            ->helperText('Only one workflow can be the default. Setting this will unset the current default.'),
                    ])->columns(2),
                Forms\Components\Section::make('Statuses')
                    ->schema([
                        Forms\Components\Repeater::make('statuses')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, ?string $state, ?string $old, Forms\Get $get) {
                                        if (!$get('slug') || $get('slug') === Str::slug($old ?? '', '_')) {
                                            $set('slug', Str::slug($state ?? '', '_'));
                                        }
                                    }),
                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->rules(['alpha_dash'])
                                    ->helperText('Used internally. Auto-generated from name.'),
                                Forms\Components\Select::make('color')
                                    ->options(WorkflowStatus::colorOptions())
                                    ->required()
                                    ->default('gray')
                                    ->native(false),
                                Forms\Components\Toggle::make('is_completed')
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
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('name')
                            ->searchable()
                            ->sortable()
                            ->weight('bold'),
                        Tables\Columns\IconColumn::make('is_default')
                            ->boolean()
                            ->label('')
                            ->trueIcon('heroicon-o-check-badge')
                            ->falseIcon('')
                            ->trueColor('success')
                            ->tooltip('Default workflow')
                            ->alignEnd()
                            ->grow(false),
                    ]),
                    Tables\Columns\TextColumn::make('description')
                        ->limit(80)
                        ->placeholder('No description')
                        ->color('gray')
                        ->size('xs'),
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('statuses_count')
                            ->counts('statuses')
                            ->icon('heroicon-o-tag')
                            ->iconColor('gray')
                            ->color('gray')
                            ->size('xs')
                            ->label('')
                            ->suffix(fn ($state) => ' '.((int) $state === 1 ? 'status' : 'statuses')),
                        Tables\Columns\TextColumn::make('projects_count')
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
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListWorkflows::route('/'),
            'create' => Pages\CreateWorkflow::route('/create'),
            'edit' => Pages\EditWorkflow::route('/{record}/edit'),
        ];
    }
}
