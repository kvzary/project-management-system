<?php

namespace App\Notifications;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Task $task,
        protected ?string $assignedBy = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $assignedByText = $this->assignedBy ? " by {$this->assignedBy}" : '';

        return FilamentNotification::make()
            ->title('Task Assigned')
            ->icon('heroicon-o-clipboard-document-check')
            ->iconColor('info')
            ->body("You have been assigned to task: {$this->task->title}{$assignedByText}")
            ->actions([
                Action::make('view')
                    ->label('View Task')
                    ->url(TaskResource::getUrl('edit', ['record' => $this->task]))
                    ->markAsRead(),
                Action::make('mark_as_read')
                    ->label('Mark as Read')
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
