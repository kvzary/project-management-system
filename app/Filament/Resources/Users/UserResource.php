<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Department;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Select::make('roles')
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->preload()
                            ->label('Roles')
                            ->columnSpanFull(),
                    ])->columns(2),
                Section::make('Departments')
                    ->schema([
                        Select::make('departments')
                            ->multiple()
                            ->options(Department::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->label('Departments')
                            ->helperText('Assign this user to one or more departments. Use the Departments page to set their role (Manager/Member).')
                            ->saveRelationshipsUsing(function ($component, $record, $state) {
                                // Attach new, detach removed — preserve existing roles
                                $current = $record->departments()->pluck('departments.id')->toArray();
                                $new = array_map('intval', $state ?? []);

                                $toAttach = array_diff($new, $current);
                                $toDetach = array_diff($current, $new);

                                foreach ($toAttach as $id) {
                                    $record->departments()->attach($id, ['role' => 'member']);
                                }
                                $record->departments()->detach($toDetach);
                            })
                            ->dehydrated(false) // handled by saveRelationshipsUsing
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record) {
                                    $component->state(
                                        $record->departments()->pluck('departments.id')->toArray()
                                    );
                                }
                            }),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    // Name + verified badge
                    Split::make([
                        TextColumn::make('name')
                            ->searchable()
                            ->sortable()
                            ->weight('bold'),
                        IconColumn::make('email_verified_at')
                            ->boolean()
                            ->label('')
                            ->trueIcon('heroicon-o-check-badge')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger')
                            ->tooltip(fn ($record) => $record->email_verified_at ? 'Verified' : 'Not verified')
                            ->alignEnd()
                            ->grow(false),
                    ]),
                    // Email
                    TextColumn::make('email')
                        ->searchable()
                        ->color('gray')
                        ->size('xs')
                        ->icon('heroicon-o-envelope')
                        ->iconColor('gray'),
                    // Roles + departments
                    Split::make([
                        TextColumn::make('roles.name')
                            ->badge()
                            ->label(''),
                        TextColumn::make('departments.name')
                            ->badge()
                            ->color('info')
                            ->label('')
                            ->separator(',')
                            ->alignEnd(),
                    ]),
                ])->space(1),
            ])
            ->contentGrid([
                'sm' => 1,
                'md' => 2,
                'xl' => 3,
            ])
            ->recordActions([
                Action::make('sendInvite')
                    ->label('Send Invite')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Send Invite')
                    ->modalDescription(fn (User $record) => 'Send a password setup link to '.$record->email.'?')
                    ->modalSubmitActionLabel('Send')
                    ->action(function (User $record) {
                        $status = Password::sendResetLink(['email' => $record->email]);

                        if ($status === Password::RESET_LINK_SENT) {
                            Notification::make()
                                ->title('Invite sent')
                                ->body($record->email.' will receive an email to set their password.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Failed to send invite')
                                ->body('Please check the email address and try again.')
                                ->danger()
                                ->send();
                        }
                    }),
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
