<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use App\Filament\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use App\Models\Workflow;
use App\Services\PresenceService;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ViewTask extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithRecord;

    protected static string $resource = TaskResource::class;

    protected static string $view = 'filament.resources.task-resource.pages.view-task';

    public ?array $detailsData = [];
    public ?array $descriptionData = [];
    public ?array $watchersData = [];
    public bool $isEditingDescription = false;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->trackPresence();

        $this->detailsForm->fill([
            'status' => $this->record->status,
            'priority' => $this->record->priority,
            'type' => $this->record->type,
            'assigned_to' => $this->record->assigned_to,
            'product_manager_id' => $this->record->product_manager_id,
            'due_date' => $this->record->due_date,
            'story_points' => $this->record->story_points,
            'branch' => $this->record->branch,
        ]);

        $this->descriptionForm->fill([
            'description' => $this->record->description,
        ]);

        $this->watchersForm->fill([
            'watchers' => $this->record->watchers->pluck('id')->toArray(),
        ]);
    }

    public function trackPresence(): void
    {
        if (auth()->check()) {
            PresenceService::track('task', $this->record->id, auth()->id());
        }
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

    public function getTitle(): string|Htmlable
    {
        return $this->record->title;
    }

    public function getSubheading(): ?string
    {
        return $this->record->identifier;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function detailsForm(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('status')
                    ->options(fn () => $this->record?->project?->getStatusOptions() ?? Workflow::getDefault()?->getStatusOptions() ?? [])
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(fn () => $this->saveDetails()),
                Select::make('priority')
                    ->options(TaskPriority::class)
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(fn () => $this->saveDetails()),
                Select::make('type')
                    ->options(TaskType::class)
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(fn () => $this->saveDetails()),
                Select::make('assigned_to')
                    ->label('Assignee')
                    ->relationship('assignee', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn () => $this->saveDetails()),
                Select::make('product_manager_id')
                    ->label('Product Manager')
                    ->relationship('productManager', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn () => $this->saveDetails()),
                DateTimePicker::make('due_date')
                    ->label('Due Date')
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(fn () => $this->saveDetails()),
                TextInput::make('story_points')
                    ->label('Story Points')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn () => $this->saveDetails()),
                TextInput::make('branch')
                    ->label('Branch')
                    ->placeholder('feature/task-123')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn () => $this->saveDetails()),
            ])
            ->statePath('detailsData')
            ->model($this->record);
    }

    public function descriptionForm(Form $form): Form
    {
        return $form
            ->schema([
                RichEditor::make('description')
                    ->hiddenLabel()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'bulletList',
                        'orderedList',
                        'link',
                        'h2',
                        'h3',
                    ]),
            ])
            ->statePath('descriptionData')
            ->model($this->record);
    }

    public function watchersForm(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('watchers')
                    ->label('Watchers')
                    ->multiple()
                    ->options(\App\Models\User::pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn () => $this->saveWatchers()),
            ])
            ->statePath('watchersData');
    }

    public function saveDetails(): void
    {
        $data = $this->detailsForm->getState();
        $this->record->update($data);

        $this->dispatch('task-updated');

        Notification::make()
            ->title('Saved')
            ->success()
            ->duration(2000)
            ->send();
    }

    public function saveWatchers(): void
    {
        $data = $this->watchersForm->getState();
        $this->record->watchers()->sync($data['watchers'] ?? []);

        Notification::make()
            ->title('Watchers updated')
            ->success()
            ->duration(2000)
            ->send();
    }

    public function startEditingDescription(): void
    {
        $this->isEditingDescription = true;
    }

    public function saveDescription(): void
    {
        $data = $this->descriptionForm->getState();
        $this->record->update($data);
        $this->isEditingDescription = false;

        $this->dispatch('task-updated');

        Notification::make()
            ->title('Description updated')
            ->success()
            ->send();
    }

    public function cancelEditingDescription(): void
    {
        $this->descriptionForm->fill([
            'description' => $this->record->description,
        ]);
        $this->isEditingDescription = false;
    }

    public function uploadAttachment(): void
    {
        // This will be handled by the SpatieMediaLibraryFileUpload component
    }

    protected function getForms(): array
    {
        return [
            'detailsForm',
            'descriptionForm',
            'watchersForm',
        ];
    }
}
