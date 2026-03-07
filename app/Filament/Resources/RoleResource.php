<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Support\AppPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        $permissionSections = [];
        foreach (AppPermissions::RESOURCES as $key => $label) {
            $permissionSections[] = Forms\Components\Fieldset::make($label)
                ->schema([
                    Forms\Components\CheckboxList::make("perms_{$key}")
                        ->hiddenLabel()
                        ->options(AppPermissions::optionsFor($key))
                        ->columns(4)
                        ->gridDirection('row'),
                ]);
        }

        return $form
            ->schema([
                Forms\Components\Section::make('Role')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Lowercase with underscores, e.g. project_manager'),
                        Forms\Components\Select::make('users')
                            ->multiple()
                            ->relationship('users', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Users with this role'),
                    ])->columns(2),
                Forms\Components\Section::make('Permissions')
                    ->description('Choose what users with this role can do across each service.')
                    ->schema($permissionSections)
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Users')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Role $record, Tables\Actions\DeleteAction $action) {
                        if ($record->users()->exists()) {
                            $action->cancel();
                            \Filament\Notifications\Notification::make()
                                ->title('Cannot delete role')
                                ->body('Remove all users from this role before deleting it.')
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSystemAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isSystemAdmin() ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->isSystemAdmin() ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->isSystemAdmin() ?? false;
    }
}
