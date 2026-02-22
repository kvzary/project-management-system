<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Project extends Model {
	use HasFactory;
	use LogsActivity;
	use SoftDeletes;

	/**
	 * The attributes that are mass assignable.
	 */
	protected $fillable = [
		'name',
		'description',
		'key',
		'status',
		'owner_id',
		'workflow_id',
		'product_manager_id',
		'branch',
		'pull_request_url',
	];

	/**
	 * The attributes that should be cast.
	 */
	protected function casts(): array {
		return [
			'status'		=> ProjectStatus::class,
			'created_at'	=> 'datetime',
			'updated_at'	=> 'datetime',
			'deleted_at'	=> 'datetime',
		];
	}

	/**
	 * Get the activity log options.
	 */
	public function getActivitylogOptions(): LogOptions {
		return LogOptions::defaults()
			->logOnly(['name', 'description', 'key', 'status', 'owner_id'])
			->logOnlyDirty()
			->dontSubmitEmptyLogs();
	}

	/**
	 * Get the project's workflow (eager loads statuses).
	 */
	public function workflow(): BelongsTo {
		return $this->belongsTo(Workflow::class)->with('statuses');
	}

	/**
	 * Get the effective workflow (project's or default).
	 */
	public function getWorkflow(): Workflow {
		if ($this->relationLoaded('workflow') && $this->workflow) {
			return $this->workflow;
		}

		if ($this->workflow_id) {
			$this->load('workflow.statuses');
			return $this->workflow;
		}

		return Workflow::getDefault();
	}

	/**
	 * Get status options from the project's workflow.
	 */
	public function getStatusOptions(): array {
		return $this->getWorkflow()->getStatusOptions();
	}

	/**
	 * Get the project owner.
	 */
	public function owner(): BelongsTo {
		return $this->belongsTo(User::class, 'owner_id');
	}

	/**
	 * Get the project members.
	 */
	public function members(): BelongsToMany {
		return $this->belongsToMany(User::class, 'project_user')
			->withPivot('role')
			->withTimestamps();
	}

	/**
	 * Get the project tasks.
	 */
	public function tasks(): HasMany {
		return $this->hasMany(Task::class);
	}

	/**
	 * Get the project sprints.
	 */
	public function sprints(): HasMany {
		return $this->hasMany(Sprint::class);
	}

	/**
	 * Get the project's product manager.
	 */
	public function productManager(): BelongsTo {
		return $this->belongsTo(User::class, 'product_manager_id');
	}

	/**
	 * Get the project creators.
	 */
	public function creators(): BelongsToMany {
		return $this->belongsToMany(User::class, 'project_creators')
			->withTimestamps();
	}

	/**
	 * Get the comments for the project.
	 */
	public function comments(): MorphMany {
		return $this->morphMany(Comment::class, 'commentable');
	}

	/**
	 * Scope a query to only include active projects.
	 */
	public function scopeActive($query) {
		return $query->where('status', ProjectStatus::ACTIVE);
	}

	/**
	 * Scope a query to only include archived projects.
	 */
	public function scopeArchived($query) {
		return $query->where('status', ProjectStatus::ARCHIVED);
	}

	/**
	 * Scope a query to only include projects on hold.
	 */
	public function scopeOnHold($query) {
		return $query->where('status', ProjectStatus::ON_HOLD);
	}

	/**
	 * Determine if the project is active.
	 */
	public function isActive(): bool {
		return $this->status === ProjectStatus::ACTIVE;
	}

	/**
	 * Scope a query to only include completed projects.
	 */
	public function scopeCompleted($query) {
		return $query->where('status', ProjectStatus::COMPLETED);
	}

	/**
	 * Determine if the project is archived.
	 */
	public function isArchived(): bool {
		return $this->status === ProjectStatus::ARCHIVED;
	}

	/**
	 * Determine if the project is on hold.
	 */
	public function isOnHold(): bool {
		return $this->status === ProjectStatus::ON_HOLD;
	}

	/**
	 * Determine if the project is completed.
	 */
	public function isCompleted(): bool {
		return $this->status === ProjectStatus::COMPLETED;
	}
}
