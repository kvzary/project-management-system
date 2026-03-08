<?php

namespace App\Notifications;

use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Services\MentionParser;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserMentionedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Comment $comment,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $commenter = $this->comment->user->name;
        $subject = $this->resolveSubject();

        return (new MailMessage)
            ->greeting($subject['label'])
            ->subject("{$commenter} mentioned you in: {$subject['title']}")
            ->line("{$commenter} mentioned you in a comment.")
            ->line(MentionParser::plainText($this->comment->body))
            ->action('View', $subject['url'])
            ->salutation('Thanks, '.PHP_EOL.config('app.name'));
    }

    public function toDatabase(object $notifiable): array
    {
        $commenter = $this->comment->user->name;
        $subject = $this->resolveSubject();

        return FilamentNotification::make()
            ->title("{$commenter} mentioned you")
            ->icon('heroicon-o-at-symbol')
            ->iconColor('info')
            ->body("In: {$subject['title']}")
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->url($subject['url'])
                    ->markAsRead(),
                Action::make('mark_as_read')
                    ->label('Mark as Read')
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    private function resolveSubject(): array
    {
        $commentable = $this->comment->commentable;

        if ($commentable instanceof Task) {
            return [
                'label' => $commentable->project?->name.' → '.$commentable->title,
                'title' => $commentable->title,
                'url' => TaskResource::getUrl('view', ['record' => $commentable]),
            ];
        }

        if ($commentable instanceof Project) {
            return [
                'label' => $commentable->name,
                'title' => $commentable->name,
                'url' => ProjectResource::getUrl('view', ['record' => $commentable]),
            ];
        }

        return ['label' => 'a record', 'title' => 'a record', 'url' => '/admin'];
    }
}
