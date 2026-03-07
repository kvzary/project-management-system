<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Pages;
use App\Filament\Resources\DepartmentResource\RelationManagers;
use App\Models\Department;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\ColorPicker::make('color')
                            ->helperText('Used to visually identify this department'),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull()
                            ->rows(3),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('color')
                    ->label(''),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('description')
                    ->limit(60)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('members_count')
                    ->counts('members')
                    ->label('Members')
                    ->icon('heroicon-o-users')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('projects_count')
                    ->counts('projects')
                    ->label('Projects')
                    ->icon('heroicon-o-briefcase')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()->isSystemAdmin()),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->isSystemAdmin()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'view' => Pages\ViewDepartment::route('/{record}'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
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
