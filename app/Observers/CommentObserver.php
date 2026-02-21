<?php

namespace App\Observers;

use App\Models\Comment;
use App\Models\Task;
use App\Notifications\TaskCommentedNotification;

class CommentObserver
{
    public function created(Comment $comment): void
    {
        // Only notify for task comments
        if ($comment->commentable_type !== Task::class) {
            return;
        }

        $task = $comment->commentable;

        if (!$task) {
            return;
        }

        // Collect all users to notify
        $notifiables = $task->watchers;

        if ($task->assignee && !$notifiables->contains($task->assignee)) {
            $notifiables->push($task->assignee);
        }

        if ($task->reporter && !$notifiables->contains($task->reporter)) {
            $notifiables->push($task->reporter);
        }

        // Don't notify the commenter
        $notifiables = $notifiables->filter(fn ($user) => $user->id !== $comment->user_id);

        foreach ($notifiables as $user) {
            $user->notify(
                new TaskCommentedNotification($task, $comment)
            );
        }
    }
}
