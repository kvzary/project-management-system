<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    public function mount(): void
    {
        parent::mount();

        if ($projectId = request()->integer('project_id')) {
            $this->form->fill([
                'project_id' => $projectId,
            ]);
        }
    }

    protected function afterCreate(): void
    {
        $firstAssignee = $this->record->assignees()->first();
        $this->record->updateQuietly(['assigned_to' => $firstAssignee?->id]);
    }
}
