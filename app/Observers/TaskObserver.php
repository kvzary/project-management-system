<?php

namespace App\Observers;

use App\Models\Task;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskStatusChangedNotification;

class TaskObserver
{
    public function updated(Task $task): void
    {
        $changedBy = auth()->user()?->name;

        // Check if assignee changed
        if ($task->isDirty('assigned_to') && $task->assigned_to) {
            $task->assignee->notify(
                new TaskAssignedNotification($task, $changedBy)
            );
        }

        // Check if status changed
        if ($task->isDirty('status')) {
            $oldStatus = $task->getOriginal('status');
            $newStatus = $task->status;

            // Notify watchers and assignee about status change
            $notifiables = $task->watchers;

            if ($task->assignee && !$notifiables->contains($task->assignee)) {
                $notifiables->push($task->assignee);
            }

            if ($task->reporter && !$notifiables->contains($task->reporter)) {
                $notifiables->push($task->reporter);
            }

            // Don't notify the person who made the change
            $currentUserId = auth()->id();
            $notifiables = $notifiables->filter(fn ($user) => $user->id !== $currentUserId);

            foreach ($notifiables as $user) {
                $user->notify(
                    new TaskStatusChangedNotification($task, $oldStatus, $newStatus, $changedBy)
                );
            }
        }
    }
}
