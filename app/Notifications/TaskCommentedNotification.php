<?php

namespace App\Notifications;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Comment;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskCommentedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Task $task,
        protected Comment $comment
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $greeting = ($this->task?->project?->name ? "{$this->task->project->name} → {$this->task->title}" : "{$this->task->title}");

        return (new MailMessage)
            ->greeting("{$greeting}")
            ->subject('New Comment on: '.$this->task->title)
            ->line("{$this->comment->user->name} commented on a task you are involved in.")
            ->line("**{$this->task->title}**")
            ->line($this->comment->content)
            ->action('View Task', TaskResource::getUrl('view', ['record' => $this->task]))
            ->line('Thank you for using our project management system.')
            ->salutation('Thanks, '.PHP_EOL.config('app.name'));
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('New Comment')
            ->icon('heroicon-o-chat-bubble-left-ellipsis')
            ->iconColor('success')
            ->body("{$this->comment->user->name} commented on: {$this->task->title}")
            ->actions([
                Action::make('view')
                    ->label('View Task')
                    ->url(TaskResource::getUrl('view', ['record' => $this->task]))
                    ->markAsRead(),
                Action::make('mark_as_read')
                    ->label('Mark as Read')
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
