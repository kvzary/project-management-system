<?php

namespace App\Filament\Resources\DepartmentResource\RelationManagers;

use App\Enums\DepartmentRole;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    protected static ?string $title = 'Members';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('role')
                    ->options(DepartmentRole::options())
                    ->default(DepartmentRole::MEMBER->value)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('pivot.role')
                    ->label('Role')
                    ->formatStateUsing(fn ($state) => DepartmentRole::tryFrom($state)?->label() ?? $state)
                    ->colors([
                        'warning' => DepartmentRole::MANAGER->value,
                        'gray' => DepartmentRole::MEMBER->value,
                    ]),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\Select::make('role')
                            ->options(DepartmentRole::options())
                            ->default(DepartmentRole::MEMBER->value)
                            ->required(),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Change Role'),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make(),
            ]);
    }

    public function isReadOnly(): bool
    {
        // Both admins and department managers can manage members
        $user = auth()->user();
        if ($user->isSystemAdmin()) {
            return false;
        }

        // Manager of this specific department
        return ! $this->getOwnerRecord()
            ->members()
            ->where('user_id', $user->id)
            ->where('role', DepartmentRole::MANAGER->value)
            ->exists();
    }
}
