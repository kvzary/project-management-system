<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Enums\ProjectStatus;
use App\Filament\Resources\ProjectResource;
use App\Models\Department;
use App\Models\User;
use App\Services\PresenceService;
use Filament\Actions;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ViewProject extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithRecord;

    protected static string $resource = ProjectResource::class;

    protected static string $view = 'filament.resources.project-resource.pages.view-project';

    public ?array $detailsData = [];

    public ?array $descriptionData = [];

    public bool $isEditingDescription = false;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->trackPresence();

        $this->detailsForm->fill([
            'department_id' => $this->record->department_id,
            'status' => $this->record->status,
            'owner_id' => $this->record->owner_id,
            'product_manager_id' => $this->record->product_manager_id,
            'branch' => $this->record->branch,
            'pull_request_url' => $this->record->pull_request_url,
        ]);

        $this->descriptionForm->fill([
            'description' => $this->record->description,
        ]);
    }

    public function trackPresence(): void
    {
        if (auth()->check()) {
            PresenceService::track('project', $this->record->id, auth()->id());
        }
    }

    public function dehydrate(): void
    {
        $this->trackPresence();
    }

    public function getViewers(): \Illuminate\Support\Collection
    {
        $viewerIds = PresenceService::getViewerIds('project', $this->record->id);
        $currentUserId = auth()->id();
        $viewerIds = array_filter($viewerIds, fn ($id) => $id != $currentUserId);

        if (empty($viewerIds)) {
            return collect();
        }

        return User::whereIn('id', $viewerIds)->get();
    }

    public function getViewerCount(): int
    {
        $count = PresenceService::getViewerCount('project', $this->record->id);

        return max(0, $count - 1);
    }

    public function getMemberCount(): int
    {
        return $this->record->members()->count();
    }

    public function getTitle(): string|Htmlable
    {
        return $this->record->name;
    }

    public function getSubheading(): ?string
    {
        return $this->record->key;
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
                Select::make('department_id')
                    ->label('Department')
                    ->options(function () {
                        $user = auth()->user();
                        if ($user->isSystemAdmin()) {
                            return Department::orderBy('name')->pluck('name', 'id');
                        }

                        return $user->departments()->orderBy('name')->pluck('name', 'departments.id');
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn () => $this->saveDetails()),
                Select::make('status')
                    ->options(ProjectStatus::class)
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(fn () => $this->saveDetails()),
                Select::make('owner_id')
                    ->label('Owner')
                    ->relationship('owner', 'name')
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
                TextInput::make('branch')
                    ->label('Branch')
                    ->placeholder('feature/epic-name')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn () => $this->saveDetails()),
                TextInput::make('pull_request_url')
                    ->label('Pull Request')
                    ->url()
                    ->placeholder('https://github.com/...')
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

    public function saveDetails(): void
    {
        abort_unless(ProjectResource::canEdit($this->record), 403);

        $data = $this->detailsForm->getState();
        $this->record->update($data);

        $this->dispatch('project-updated');

        Notification::make()
            ->title('Saved')
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
        abort_unless(ProjectResource::canEdit($this->record), 403);

        $data = $this->descriptionForm->getState();
        $this->record->update($data);
        $this->isEditingDescription = false;

        $this->dispatch('project-updated');

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

    protected function getForms(): array
    {
        return [
            'detailsForm',
            'descriptionForm',
        ];
    }
}
