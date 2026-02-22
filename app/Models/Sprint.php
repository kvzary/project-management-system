<?php

namespace App\Models;

use App\Enums\SprintStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Sprint extends Model {
	use HasFactory;
	use LogsActivity;
	use SoftDeletes;

	/**
	 * The attributes that are mass assignable.
	 */
	protected $fillable = [
		'project_id',
		'name',
		'goal',
		'start_date',
		'end_date',
		'status',
	];

	/**
	 * The attributes that should be cast.
	 */
	protected function casts(): array {
		return [
			'status'		=> SprintStatus::class,
			'start_date'	=> 'date',
			'end_date'		=> 'date',
			'created_at'	=> 'datetime',
			'updated_at'	=> 'datetime',
		];
	}

	/**
	 * Get the activity log options.
	 */
	public function getActivitylogOptions(): LogOptions {
		return LogOptions::defaults()
			->logOnly(['name', 'goal', 'start_date', 'end_date', 'status'])
			->logOnlyDirty()
			->dontSubmitEmptyLogs();
	}

	/**
	 * Get the project that owns the sprint.
	 */
	public function project(): BelongsTo {
		return $this->belongsTo(Project::class);
	}

	/**
	 * Get the tasks in this sprint.
	 */
	public function tasks(): HasMany {
		return $this->hasMany(Task::class);
	}

	/**
	 * Scope a query to only include active sprints.
	 */
	public function scopeActive($query) {
		return $query->where('status', SprintStatus::ACTIVE);
	}

	/**
	 * Scope a query to only include planning sprints.
	 */
	public function scopePlanning($query) {
		return $query->where('status', SprintStatus::PLANNING);
	}

	/**
	 * Scope a query to only include completed sprints.
	 */
	public function scopeCompleted($query) {
		return $query->where('status', SprintStatus::COMPLETED);
	}

	/**
	 * Scope a query to only include current sprints (active and within date range).
	 */
	public function scopeCurrent($query) {
		return $query->where('status', SprintStatus::ACTIVE)
			->where('start_date', '<=', now())
			->where('end_date', '>=', now());
	}

	/**
	 * Determine if the sprint is active.
	 */
	public function isActive(): bool {
		return $this->status === SprintStatus::ACTIVE;
	}

	/**
	 * Determine if the sprint is in planning.
	 */
	public function isPlanning(): bool {
		return $this->status === SprintStatus::PLANNING;
	}

	/**
	 * Determine if the sprint is completed.
	 */
	public function isCompleted(): bool {
		return $this->status === SprintStatus::COMPLETED;
	}

	/**
	 * Get the duration of the sprint in days.
	 */
	public function getDurationInDays(): int {
		return $this->start_date->diffInDays($this->end_date) + 1;
	}

	/**
	 * Get the remaining days in the sprint.
	 */
	public function getRemainingDays(): int {
		if ($this->end_date->isPast()) {
			return 0;
		}

		return now()->diffInDays($this->end_date, false) + 1;
	}

	/**
	 * Determine if the sprint is overdue.
	 */
	public function isOverdue(): bool {
		return $this->end_date->isPast() && !$this->isCompleted();
	}
}
