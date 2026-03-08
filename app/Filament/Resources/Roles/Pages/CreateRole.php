<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['guard_name'] = 'web';

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncPermissions();
    }

    private function syncPermissions(): void
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
