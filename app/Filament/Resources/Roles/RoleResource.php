<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Support\AppPermissions;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        $permissionSections = [];
        foreach (AppPermissions::RESOURCES as $key => $label) {
            $permissionSections[] = Fieldset::make($label)
                ->schema([
                    CheckboxList::make("perms_{$key}")
                        ->hiddenLabel()
                        ->options(AppPermissions::optionsFor($key))
                        ->columns(4)
                        ->gridDirection('row'),
                ]);
        }

        return $schema
            ->components([
                Section::make('Role')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Lowercase with underscores, e.g. project_manager'),
                        Select::make('users')
                            ->multiple()
                            ->relationship('users', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Users with this role'),
                    ])->columns(2),
                Section::make('Permissions')
                    ->description('Choose what users with this role can do across each service.')
                    ->schema($permissionSections)
                    ->columns(1),
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
                        TextColumn::make('users_count')
                            ->counts('users')
                            ->badge()
                            ->color('primary')
                            ->label('')
                            ->suffix(fn ($state) => ' '.((int) $state === 1 ? 'user' : 'users'))
                            ->alignEnd()
                            ->grow(false),
                    ]),
                    TextColumn::make('permissions_count')
                        ->counts('permissions')
                        ->icon('heroicon-o-shield-check')
                        ->iconColor('gray')
                        ->color('gray')
                        ->size('xs')
                        ->label('')
                        ->suffix(fn ($state) => ' '.((int) $state === 1 ? 'permission' : 'permissions')),
                ])->space(2),
            ])
            ->contentGrid([
                'sm' => 1,
                'md' => 2,
                'xl' => 3,
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (Role $record, DeleteAction $action) {
                        if ($record->users()->exists()) {
                            $action->cancel();
                            Notification::make()
                                ->title('Cannot delete role')
                                ->body('Remove all users from this role before deleting it.')
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
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

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->isSystemAdmin() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isSystemAdmin() ?? false;
    }
}
