<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Support\AppPermissions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('grantAll')
                ->label('Grant All Permissions')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Grant all permissions?')
                ->modalDescription('This will give this role full access to every service in the app.')
                ->action(function () {
                    $this->record->syncPermissions(AppPermissions::all());

                    $this->refreshFormData(
                        collect(AppPermissions::RESOURCES)
                            ->keys()
                            ->map(fn ($k) => "perms_{$k}")
                            ->toArray()
                    );

                    Notification::make()
                        ->title('All permissions granted')
                        ->success()
                        ->send();
                }),
            Action::make('revokeAll')
                ->label('Revoke All')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Revoke all permissions?')
                ->action(function () {
                    $this->record->syncPermissions([]);

                    $this->refreshFormData(
                        collect(AppPermissions::RESOURCES)
                            ->keys()
                            ->map(fn ($k) => "perms_{$k}")
                            ->toArray()
                    );

                    Notification::make()
                        ->title('All permissions revoked')
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $existing = $this->record->permissions->pluck('name')->toArray();

        foreach (array_keys(AppPermissions::RESOURCES) as $resource) {
            $data["perms_{$resource}"] = array_values(
                array_filter($existing, fn ($p) => str_starts_with($p, "{$resource}."))
            );
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $permissions = collect($this->data)
            ->filter(fn ($v, $k) => str_starts_with($k, 'perms_'))
            ->flatten()
            ->filter()
            ->values()
            ->toArray();

        $this->record->syncPermissions($permissions);
    }
}
