<?php

namespace App\Notifications;

use App\Filament\Resources\TaskResource;
use App\Models\Comment;
use App\Models\Task;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
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
        return ['database'];
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
                    ->url(TaskResource::getUrl('edit', ['record' => $this->task]))
                    ->markAsRead(),
                Action::make('mark_as_read')
                    ->label('Mark as Read')
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
