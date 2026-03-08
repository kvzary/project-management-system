<?php

namespace App\Filament\Resources\Departments;

use App\Filament\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Resources\Departments\Pages\EditDepartment;
use App\Filament\Resources\Departments\Pages\ListDepartments;
use App\Filament\Resources\Departments\Pages\ViewDepartment;
use App\Filament\Resources\Departments\RelationManagers\MembersRelationManager;
use App\Models\Department;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        ColorPicker::make('color')
                            ->helperText('Used to visually identify this department'),
                        Textarea::make('description')
                            ->columnSpanFull()
                            ->rows(3),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Split::make([
                        ColorColumn::make('color')
                            ->label('')
                            ->grow(false),
                        TextColumn::make('name')
                            ->searchable()
                            ->sortable()
                            ->weight('bold'),
                    ]),
                    TextColumn::make('description')
                        ->limit(80)
                        ->placeholder('No description')
                        ->color('gray')
                        ->size('xs'),
                    Split::make([
                        TextColumn::make('members_count')
                            ->counts('members')
                            ->icon('heroicon-o-users')
                            ->iconColor('gray')
                            ->color('gray')
                            ->size('xs')
                            ->label('')
                            ->suffix(fn ($state) => ' '.((int) $state === 1 ? 'member' : 'members')),
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
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn () => auth()->user()->isSystemAdmin()),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()->isSystemAdmin()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [
            MembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDepartments::route('/'),
            'create' => CreateDepartment::route('/create'),
            'view' => ViewDepartment::route('/{record}'),
            'edit' => EditDepartment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if ($user->isSystemAdmin()) {
            return parent::getEloquentQuery();
        }

        // Department managers only see their own departments
        return parent::getEloquentQuery()->whereHas('members', fn ($q) => $q->where('user_id', $user->id)->where('role', 'manager')
        );
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user->isSystemAdmin() || $user->managedDepartments()->exists();
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        if ($user->isSystemAdmin()) {
            return true;
        }

        // Managers can view their own departments; the scoped query ensures the record belongs to them.
        return $record->managers()->where('user_id', $user->id)->exists();
    }

    public static function canCreate(): bool
    {
        return auth()->user()->isSystemAdmin();
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->isSystemAdmin();
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->isSystemAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->isSystemAdmin();
    }
}
