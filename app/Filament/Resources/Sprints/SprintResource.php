<?php

namespace App\Filament\Resources\Sprints;

use App\Enums\SprintStatus;
use App\Filament\Resources\Concerns\HasResourcePermissions;
use App\Filament\Resources\Sprints\Pages\CreateSprint;
use App\Filament\Resources\Sprints\Pages\EditSprint;
use App\Filament\Resources\Sprints\Pages\ListSprints;
use App\Filament\Resources\Sprints\Pages\ViewSprint;
use App\Models\Department;
use App\Models\Sprint;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SprintResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = Sprint::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static string|\UnitEnum|null $navigationGroup = 'Project Management';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sprint Information')
                    ->schema([
                        Select::make('project_id')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Sprint 1'),
                        Select::make('status')
                            ->options(SprintStatus::class)
                            ->default(SprintStatus::PLANNING)
                            ->required()
                            ->native(false),
                    ])->columns(3),
                Section::make('Timeline')
                    ->schema([
                        DatePicker::make('start_date')
                            ->required()
                            ->native(false)
                            ->displayFormat('Y-m-d'),
                        DatePicker::make('end_date')
                            ->required()
                            ->native(false)
                            ->displayFormat('Y-m-d')
                            ->after('start_date'),
                    ])->columns(2),
                Section::make('Sprint Goal')
                    ->schema([
                        RichEditor::make('goal')
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
                Stack::make([
                    // Sprint name + status
                    Split::make([
                        TextColumn::make('name')
                            ->searchable()
                            ->weight('bold'),
                        TextColumn::make('status')
                            ->badge()
                            ->sortable()
                            ->alignEnd(),
                    ]),

                    // Project badge
                    TextColumn::make('project.name')
                        ->searchable()
                        ->badge()
                        ->color('primary'),

                    // Dates + task count
                    Split::make([
                        TextColumn::make('start_date')
                            ->date('M d, Y')
                            ->color('gray')
                            ->size('xs')
                            ->icon('heroicon-o-calendar')
                            ->iconColor('gray'),
                        TextColumn::make('end_date')
                            ->date('M d, Y')
                            ->color(fn ($record) => $record->end_date < now() && $record->status !== SprintStatus::COMPLETED ? 'danger' : 'gray')
                            ->size('xs')
                            ->icon('heroicon-o-flag')
                            ->iconColor('gray'),
                        TextColumn::make('tasks_count')
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
                SelectFilter::make('status')
                    ->options(SprintStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListSprints::route('/'),
            'create' => CreateSprint::route('/create'),
            'view' => ViewSprint::route('/{record}'),
            'edit' => EditSprint::route('/{record}/edit'),
        ];
    }
}
