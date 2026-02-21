<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Workflow extends Model {
	protected $fillable = [
		'name',
		'description',
		'is_default',
	];

	protected function casts(): array {
		return [
			'is_default' => 'boolean',
		];
	}

	protected static function booted(): void {
		static::saving(function (Workflow $workflow) {
			if ($workflow->is_default) {
				static::where('id', '!=', $workflow->id ?? 0)
					->where('is_default', true)
					->update(['is_default' => false]);
			}
		});

		static::saved(fn () => Cache::forget('workflow:default'));
		static::deleted(fn () => Cache::forget('workflow:default'));
	}

	public function statuses(): HasMany {
		return $this->hasMany(WorkflowStatus::class)->orderBy('position');
	}

	public function projects(): HasMany {
		return $this->hasMany(Project::class);
	}

	public static function getDefault(): ?self {
		return Cache::remember('workflow:default', 3600, function () {
			return static::with('statuses')->where('is_default', true)->first();
		});
	}

	public function getStatusOptions(): array {
		return $this->statuses->pluck('name', 'slug')->toArray();
	}

	public function getCompletedSlugs(): array {
		return $this->statuses->where('is_completed', true)->pluck('slug')->toArray();
	}

	public function findStatusBySlug(string $slug): ?WorkflowStatus {
		return $this->statuses->firstWhere('slug', $slug);
	}
}
