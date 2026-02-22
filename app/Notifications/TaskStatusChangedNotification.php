<?php

namespace App\Notifications;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Task $task,
        protected string $oldStatus,
        protected string $newStatus,
        protected ?string $changedBy = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $changedByText = $this->changedBy ? " by {$this->changedBy}" : '';

        return (new MailMessage)
            ->subject('Task Status Updated: ' . $this->task->title)
            ->line("A task's status has been updated{$changedByText}.")
            ->line("**{$this->task->title}**")
            ->line("{$this->formatStatus($this->oldStatus)} → {$this->formatStatus($this->newStatus)}")
            ->action('View Task', TaskResource::getUrl('edit', ['record' => $this->task]))
            ->line('Thank you for using our project management system.');
    }

    public function toDatabase(object $notifiable): array
    {
        $changedByText = $this->changedBy ? " by {$this->changedBy}" : '';

        return FilamentNotification::make()
            ->title('Task Status Updated')
            ->icon('heroicon-o-arrow-path')
            ->iconColor('warning')
            ->body("{$this->task->title} moved from {$this->formatStatus($this->oldStatus)} to {$this->formatStatus($this->newStatus)}{$changedByText}")
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

    protected function formatStatus(string $status): string
    {
        return ucwords(str_replace('_', ' ', $status));
    }
}
