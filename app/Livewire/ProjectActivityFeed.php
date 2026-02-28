<?php

namespace App\Livewire;

use App\Models\Project;
use App\Services\MentionParser;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

class ProjectActivityFeed extends Component
{
    public Project $project;
    public string $newComment = '';

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    #[On('project-updated')]
    #[On('comment-added')]
    public function refreshFeed(): void
    {
        $this->project->refresh();
    }

    public function addComment(): void
    {
        $this->validate([
            'newComment' => 'required|min:1',
        ]);

        $this->project->comments()->create([
            'user_id' => auth()->id(),
            'body' => $this->newComment,
        ]);

        $this->newComment = '';
        $this->dispatch('comment-added');
    }

    public function deleteComment(int $commentId): void
    {
        $comment = $this->project->comments()->find($commentId);

        if ($comment && $comment->user_id === auth()->id()) {
            $comment->delete();
        }
    }

    public function getFeedProperty(): \Illuminate\Support\Collection
    {
        // Get comments
        $comments = $this->project->comments()
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
        $activities = Activity::where('subject_type', Project::class)
            ->where('subject_id', $this->project->id)
            ->with('causer')
            ->get()
            ->map(function ($activity) {
                $changes = $this->formatActivityChanges($activity);
                return [
                    'type' => 'activity',
                    'id' => 'activity-' . $activity->id,
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
            } elseif (!isset($old[$key])) {
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
            'owner_id' => 'Owner',
            'product_manager_id' => 'Product Manager',
            'repository_url' => 'Repository',
            default => ucfirst(str_replace('_', ' ', $field)),
        };
    }

    protected function formatValue(string $field, mixed $value): string
    {
        if ($value === null) {
            return 'None';
        }

        // Handle user IDs
        if (in_array($field, ['owner_id', 'product_manager_id'])) {
            $user = \App\Models\User::find($value);
            return $user?->name ?? 'Unknown';
        }

        return (string) $value;
    }

    public function render()
    {
        return view('livewire.project-activity-feed', [
            'feed' => $this->feed,
        ]);
    }
}
