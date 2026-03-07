<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
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
}
