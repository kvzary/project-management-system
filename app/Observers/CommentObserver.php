<?php

namespace App\Observers;

use App\Models\Comment;
use App\Models\CommentMention;
use App\Models\Task;
use App\Notifications\TaskCommentedNotification;
use App\Notifications\UserMentionedNotification;
use App\Services\MentionParser;

class CommentObserver
{
    public function created(Comment $comment): void
    {
        $alreadyNotified = collect();

        // Task-specific notifications (assignee, reporter, watchers)
        if ($comment->commentable_type === Task::class) {
            $task = $comment->commentable;

            if ($task) {
                $notifiables = $task->watchers;

                if ($task->assignee && !$notifiables->contains($task->assignee)) {
                    $notifiables->push($task->assignee);
                }

                if ($task->reporter && !$notifiables->contains($task->reporter)) {
                    $notifiables->push($task->reporter);
                }

                $notifiables = $notifiables->filter(fn ($user) => $user->id !== $comment->user_id);

                foreach ($notifiables as $user) {
                    $user->notify(new TaskCommentedNotification($task, $comment));
                    $alreadyNotified->push($user->id);
                }
            }
        }

        // Mention notifications (task and project comments)
        $this->handleMentions($comment, $alreadyNotified->all());
    }

    private function handleMentions(Comment $comment, array $excludeIds): void
    {
        $mentionedUsers = MentionParser::extract($comment->body);

        if ($mentionedUsers->isEmpty()) {
            return;
        }

        // Persist mention records
        $records = $mentionedUsers->map(fn ($user) => [
            'comment_id' => $comment->id,
            'user_id'    => $user->id,
            'created_at' => now(),
        ])->all();

        CommentMention::upsert($records, ['comment_id', 'user_id'], []);

        // Notify users not already notified via the task observer path, and not the commenter
        $toNotify = $mentionedUsers->filter(
            fn ($user) => $user->id !== $comment->user_id
                && !in_array($user->id, $excludeIds, true)
        );

        foreach ($toNotify as $user) {
            $user->notify(new UserMentionedNotification($comment));
        }
    }
}
