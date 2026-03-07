<?php

namespace App\Filament\Resources;

use App\Enums\SprintStatus;
use App\Filament\Resources\Concerns\HasResourcePermissions;
use App\Filament\Resources\SprintResource\Pages;
use App\Models\Department;
use App\Models\Sprint;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SprintResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = Sprint::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Project Management';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Sprint Information')
                    ->schema([
                        Forms\Components\Select::make('project_id')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Sprint 1'),
                        Forms\Components\Select::make('status')
                            ->options(SprintStatus::class)
                            ->default(SprintStatus::PLANNING)
                            ->required()
                            ->native(false),
                    ])->columns(3),
                Forms\Components\Section::make('Timeline')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->native(false)
                            ->displayFormat('Y-m-d'),
                        Forms\Components\DatePicker::make('end_date')
                            ->required()
                            ->native(false)
                            ->displayFormat('Y-m-d')
                            ->after('start_date'),
                    ])->columns(2),
                Forms\Components\Section::make('Sprint Goal')
                    ->schema([
                        Forms\Components\RichEditor::make('goal')
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'bulletList',
                                'orderedList',
                                'italic',
                                'redo',
                                'undo',
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    // Sprint name + status
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('name')
                            ->searchable()
                            ->weight('bold'),
                        Tables\Columns\TextColumn::make('status')
                            ->badge()
                            ->sortable()
                            ->alignEnd(),
                    ]),

                    // Project badge
                    Tables\Columns\TextColumn::make('project.name')
                        ->searchable()
                        ->badge()
                        ->color('primary'),

                    // Dates + task count
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('start_date')
                            ->date('M d, Y')
                            ->color('gray')
                            ->size('xs')
                            ->icon('heroicon-o-calendar')
                            ->iconColor('gray'),
                        Tables\Columns\TextColumn::make('end_date')
                            ->date('M d, Y')
                            ->color(fn ($record) => $record->end_date < now() && $record->status !== SprintStatus::COMPLETED ? 'danger' : 'gray')
                            ->size('xs')
                            ->icon('heroicon-o-flag')
                            ->iconColor('gray'),
                        Tables\Columns\TextColumn::make('tasks_count')
                            ->counts('tasks')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->iconColor('gray')
                            ->color('gray')
                            ->size('xs')
                            ->suffix(fn ($state) => ' '.((int) $state === 1 ? 'task' : 'tasks'))
                            ->alignEnd(),
                    ]),
                ])->space(2),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->filters([
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
                Tables\Filters\SelectFilter::make('status')
                    ->options(SprintStatus::class),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('start_date', 'desc')
            ->recordUrl(fn ($record) => static::getUrl('view', ['record' => $record]));
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSprints::route('/'),
            'create' => Pages\CreateSprint::route('/create'),
            'view' => Pages\ViewSprint::route('/{record}'),
            'edit' => Pages\EditSprint::route('/{record}/edit'),
        ];
    }
}
