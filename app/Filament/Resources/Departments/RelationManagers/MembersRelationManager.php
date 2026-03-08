<?php

namespace App\Filament\Resources\Departments\RelationManagers;

use App\Enums\DepartmentRole;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    protected static ?string $title = 'Members';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('role')
                    ->options(DepartmentRole::options())
                    ->default(DepartmentRole::MEMBER->value)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable(),
                BadgeColumn::make('pivot.role')
                    ->label('Role')
                    ->formatStateUsing(fn ($state) => DepartmentRole::tryFrom($state)?->label() ?? $state)
                    ->colors([
                        'warning' => DepartmentRole::MANAGER->value,
                        'gray' => DepartmentRole::MEMBER->value,
                    ]),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Select::make('role')
                            ->options(DepartmentRole::options())
                            ->default(DepartmentRole::MEMBER->value)
                            ->required(),
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Change Role'),
                DetachAction::make(),
            ])
            ->toolbarActions([
                DetachBulkAction::make(),
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
