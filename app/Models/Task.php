<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Task extends Model implements HasMedia {
	use HasFactory;
	use InteractsWithMedia;
	use LogsActivity;
	use SoftDeletes;

	protected ?WorkflowStatus $cachedWorkflowStatus = null;
	protected bool $workflowStatusResolved = false;

	protected $fillable = [
		'project_id',
		'sprint_id',
		'parent_id',
		'assigned_to',
		'reporter_id',
		'product_manager_id',
		'title',
		'description',
		'type',
		'status',
		'priority',
		'story_points',
		'due_date',
		'position',
		'branch',
	];

	protected function casts(): array {
		return [
			'type'			=> TaskType::class,
			'priority'		=> TaskPriority::class,
			'due_date'		=> 'datetime',
			'created_at'	=> 'datetime',
			'updated_at'	=> 'datetime',
			'deleted_at'	=> 'datetime',
		];
	}

	public function getActivitylogOptions(): LogOptions {
		return LogOptions::defaults()
			->logOnly([
				'title',
				'description',
				'type',
				'status',
				'priority',
				'story_points',
				'due_date',
				'assigned_to',
				'sprint_id',
			])
			->logOnlyDirty()
			->dontSubmitEmptyLogs();
	}

	public function registerMediaCollections(): void {
		$this->addMediaCollection('attachments')
			->useDisk('public');
	}

	public function project(): BelongsTo {
		return $this->belongsTo(Project::class);
	}

	public function sprint(): BelongsTo {
		return $this->belongsTo(Sprint::class);
	}

	public function parent(): BelongsTo {
		return $this->belongsTo(Task::class, 'parent_id');
	}

	public function children(): HasMany {
		return $this->hasMany(Task::class, 'parent_id');
	}

	public function assignee(): BelongsTo {
		return $this->belongsTo(User::class, 'assigned_to');
	}

	public function assignees(): BelongsToMany {
		return $this->belongsToMany(User::class, 'task_assignees')->withTimestamps();
	}

	/**
	 * Sync the assignees pivot and keep assigned_to pointing at the first assignee.
	 */
	public function syncAssignees(array $userIds): void {
		$this->assignees()->sync($userIds);
		$this->updateQuietly(['assigned_to' => $userIds[0] ?? null]);
	}

	public function reporter(): BelongsTo {
		return $this->belongsTo(User::class, 'reporter_id');
	}

	public function productManager(): BelongsTo {
		return $this->belongsTo(User::class, 'product_manager_id');
	}

	public function creators(): BelongsToMany {
		return $this->belongsToMany(User::class, 'task_creators')
			->withTimestamps();
	}

	public function getBranchUrlAttribute(): ?string {
		if (!$this->branch || !$this->project?->repository_url) {
			return null;
		}

		$repoUrl = rtrim($this->project->repository_url, '/');
		return "{$repoUrl}/tree/{$this->branch}";
	}

	public function comments(): MorphMany {
		return $this->morphMany(Comment::class, 'commentable');
	}

	public function watchers(): BelongsToMany {
		return $this->belongsToMany(User::class, 'task_watchers')
			->withTimestamps();
	}

	/**
	 * Get the WorkflowStatus model for this task's current status (memoized).
	 */
	public function getWorkflowStatus(): ?WorkflowStatus {
		if ($this->workflowStatusResolved) {
			return $this->cachedWorkflowStatus;
		}

		$this->workflowStatusResolved = true;

		if (!$this->status || !$this->project) {
			return $this->cachedWorkflowStatus = null;
		}

		$workflow = $this->project->getWorkflow();
		return $this->cachedWorkflowStatus = $workflow?->findStatusBySlug($this->status);
	}

	/**
	 * Get a human-readable label for the current status.
	 */
	public function getStatusLabelAttribute(): string {
		return $this->getWorkflowStatus()?->name ?? $this->status ?? 'Unknown';
	}

	/**
	 * Get the Filament color for the current status.
	 */
	public function getStatusColorAttribute(): string {
		return $this->getWorkflowStatus()?->color ?? 'gray';
	}

	public function scopeStatus($query, string $status) {
		return $query->where('status', $status);
	}

	/**
	 * Get completed status slugs (cached for the request).
	 */
	protected static function getCompletedSlugs(): array {
		return Cache::remember('workflow:completed_slugs', 3600, function () {
			return WorkflowStatus::where('is_completed', true)
				->pluck('slug')
				->unique()
				->toArray();
		});
	}

	public function scopeCompleted($query) {
		return $query->whereIn('status', static::getCompletedSlugs());
	}

	public function scopeNotCompleted($query) {
		return $query->whereNotIn('status', static::getCompletedSlugs());
	}

	public function scopeType($query, TaskType $type) {
		return $query->where('type', $type);
	}

	public function scopePriority($query, TaskPriority $priority) {
		return $query->where('priority', $priority);
	}

	public function scopeAssignedTo($query, int $userId) {
		return $query->where('assigned_to', $userId);
	}

	public function scopeUnassigned($query) {
		return $query->whereNull('assigned_to');
	}

	public function scopeOverdue($query) {
		return $query->whereNotNull('due_date')
			->where('due_date', '<', now())
			->whereNotIn('status', static::getCompletedSlugs());
	}

	public function scopeBacklog($query) {
		return $query->whereNull('sprint_id');
	}

	public function scopeOrdered($query) {
		return $query->orderBy('position');
	}

	public function scopeParents($query) {
		return $query->whereNull('parent_id');
	}

	/**
	 * Determine if the task is completed.
	 */
	public function isCompleted(): bool {
		$ws = $this->getWorkflowStatus();
		return $ws ? $ws->is_completed : false;
	}

	public function isOverdue(): bool {
		return $this->due_date !== null
			&& $this->due_date->isPast()
			&& !$this->isCompleted();
	}

	public function isSubtask(): bool {
		return $this->parent_id !== null;
	}

	public function hasSubtasks(): bool {
		return $this->children()->exists();
	}

	public function getIdentifierAttribute(): string {
		return $this->project
			? strtoupper($this->project->key) . '-' . $this->id
			: 'TASK-' . $this->id;
	}
}
