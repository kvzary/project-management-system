<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Department;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Select::make('roles')
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->preload()
                            ->label('Roles')
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make('Departments')
                    ->schema([
                        Forms\Components\Select::make('departments')
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
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->label('Roles'),
                Tables\Columns\TextColumn::make('departments.name')
                    ->badge()
                    ->color('info')
                    ->label('Departments')
                    ->separator(','),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->boolean()
                    ->label('Verified')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\Action::make('sendInvite')
                    ->label('Send Invite')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Send Invite')
                    ->modalDescription(fn (User $record) => 'Send a password setup link to '.$record->email.'?')
                    ->modalSubmitActionLabel('Send')
                    ->action(function (User $record) {
                        $record->markEmailAsVerified();

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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
