<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Comment extends Model {
	use HasFactory;
	use LogsActivity;
	use SoftDeletes;

	/**
	 * The attributes that are mass assignable.
	 */
	protected $fillable = [
		'commentable_type',
		'commentable_id',
		'user_id',
		'body',
	];

	/**
	 * The attributes that should be cast.
	 */
	protected function casts(): array {
		return [
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
			->logOnly(['body'])
			->logOnlyDirty()
			->dontSubmitEmptyLogs();
	}

	/**
	 * Get the parent commentable model (task, etc.).
	 */
	public function commentable(): MorphTo {
		return $this->morphTo();
	}

	/**
	 * Get the user who created the comment.
	 */
	public function user(): BelongsTo {
		return $this->belongsTo(User::class);
	}

	/**
	 * Get the mentions in this comment.
	 */
	public function mentions(): HasMany {
		return $this->hasMany(CommentMention::class);
	}

	/**
	 * Scope a query to only include comments by a specific user.
	 */
	public function scopeByUser($query, int $userId) {
		return $query->where('user_id', $userId);
	}

	/**
	 * Scope a query to order comments by most recent first.
	 */
	public function scopeLatest($query) {
		return $query->orderBy('created_at', 'desc');
	}

	/**
	 * Scope a query to order comments by oldest first.
	 */
	public function scopeOldest($query) {
		return $query->orderBy('created_at', 'asc');
	}
}
