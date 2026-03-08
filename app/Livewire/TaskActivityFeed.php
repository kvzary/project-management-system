<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use App\Services\MentionParser;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

class TaskActivityFeed extends Component
{
    public Task $task;

    public string $newComment = '';

    public function mount(Task $task): void
    {
        $this->task = $task;
    }

    #[On('task-updated')]
    #[On('comment-added')]
    public function refreshFeed(): void
    {
        $this->task->refresh();
    }

    public function addComment(): void
    {
        $this->validate([
            'newComment' => 'required|min:1',
        ]);

        $this->task->comments()->create([
            'user_id' => auth()->id(),
            'body' => $this->newComment,
        ]);

        $this->newComment = '';
        $this->dispatch('comment-added');
    }

    public function addCommentWithBody(string $body): void
    {
        $body = trim($body);

        if (mb_strlen($body) < 1) {
            return;
        }

        $this->task->comments()->create([
            'user_id' => auth()->id(),
            'body' => $body,
        ]);

        $this->dispatch('comment-added');
    }

    public function deleteComment(int $commentId): void
    {
        $comment = $this->task->comments()->find($commentId);

        if ($comment && $comment->user_id === auth()->id()) {
            $comment->delete();
        }
    }

    public function getFeedProperty(): Collection
    {
        // Get comments
        $comments = $this->task->comments()
            ->with('user')
            ->get()
            ->map(function ($comment) {
                return [
                    'type' => 'comment',
                    'id' => $comment->id,
                    'user' => $comment->user,
                    'content' => MentionParser::render($comment->body),
                    'created_at' => $comment->created_at,
                    'can_delete' => $comment->user_id === auth()->id(),
                ];
            });

        // Get activity logs
        $activities = Activity::where('subject_type', Task::class)
            ->where('subject_id', $this->task->id)
            ->with('causer')
            ->get()
            ->map(function ($activity) {
                $changes = $this->formatActivityChanges($activity);

                return [
                    'type' => 'activity',
                    'id' => 'activity-'.$activity->id,
                    'user' => $activity->causer,
                    'event' => $activity->event ?? $activity->description,
                    'changes' => $changes,
                    'created_at' => $activity->created_at,
                    'can_delete' => false,
                ];
            });

        // Merge and sort by date descending
        return $comments->concat($activities)
            ->sortByDesc('created_at')
            ->values();
    }

    protected function formatActivityChanges(Activity $activity): array
    {
        $changes = [];
        $attributes = $activity->properties['attributes'] ?? [];
        $old = $activity->properties['old'] ?? [];

        foreach ($attributes as $key => $value) {
            if (isset($old[$key]) && $old[$key] !== $value) {
                $changes[] = [
                    'field' => $this->formatFieldName($key),
                    'old' => $this->formatValue($key, $old[$key]),
                    'new' => $this->formatValue($key, $value),
                ];
            } elseif (! isset($old[$key])) {
                $changes[] = [
                    'field' => $this->formatFieldName($key),
                    'old' => null,
                    'new' => $this->formatValue($key, $value),
                ];
            }
        }

        return $changes;
    }

    protected function formatFieldName(string $field): string
    {
        return match ($field) {
            'assigned_to' => 'Assignee',
            'reporter_id' => 'Reporter',
            'product_manager_id' => 'Product Manager',
            'project_id' => 'Project',
            'sprint_id' => 'Sprint',
            'story_points' => 'Story Points',
            'due_date' => 'Due Date',
            default => ucfirst(str_replace('_', ' ', $field)),
        };
    }

    protected function formatValue(string $field, mixed $value): string
    {
        if ($value === null) {
            return 'None';
        }

        // Handle user IDs
        if (in_array($field, ['assigned_to', 'reporter_id', 'product_manager_id'])) {
            $user = User::find($value);

            return $user?->name ?? 'Unknown';
        }

        // Handle project
        if ($field === 'project_id') {
            $project = Project::find($value);

            return $project?->name ?? 'Unknown';
        }

        // Handle sprint
        if ($field === 'sprint_id') {
            $sprint = Sprint::find($value);

            return $sprint?->name ?? 'Backlog';
        }

        return (string) $value;
    }

    public function render()
    {
        return view('livewire.task-activity-feed', [
            'feed' => $this->feed,
        ]);
    }
}
