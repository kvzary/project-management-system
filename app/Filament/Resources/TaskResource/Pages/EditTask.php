<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\User;
use App\Services\PresenceService;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->trackPresence();
    }

    public function trackPresence(): void
    {
        if (auth()->check()) {
            PresenceService::track('task', $this->record->id, auth()->id());
        }
    }

    #[On('presence-ping')]
    public function presencePing(): void
    {
        $this->trackPresence();
    }

    protected function afterSave(): void
    {
        $firstAssignee = $this->record->assignees()->first();
        $this->record->updateQuietly(['assigned_to' => $firstAssignee?->id]);
    }

    public function dehydrate(): void
    {
        $this->trackPresence();
    }

    public function getViewers(): \Illuminate\Support\Collection
    {
        $viewerIds = PresenceService::getViewerIds('task', $this->record->id);
        $currentUserId = auth()->id();

        $viewerIds = array_filter($viewerIds, fn($id) => $id != $currentUserId);

        if (empty($viewerIds)) {
            return collect();
        }

        return User::whereIn('id', $viewerIds)->get();
    }

    public function getViewerCount(): int
    {
        $count = PresenceService::getViewerCount('task', $this->record->id);
        return max(0, $count - 1);
    }

    public function getWatcherCount(): int
    {
        return $this->record->watchers()->count();
    }

    public function getWatchers(): \Illuminate\Support\Collection
    {
        return $this->record->watchers()->limit(10)->get();
    }

    public function getHeader(): ?View
    {
        return view('filament.resources.task-resource.pages.edit-task-header', [
            'record' => $this->record,
            'viewers' => $this->getViewers(),
            'viewerCount' => $this->getViewerCount(),
            'watchers' => $this->getWatchers(),
            'watcherCount' => $this->getWatcherCount(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
