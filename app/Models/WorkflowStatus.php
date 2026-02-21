<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class WorkflowStatus extends Model {
	protected $fillable = [
		'workflow_id',
		'slug',
		'name',
		'color',
		'position',
		'is_completed',
	];

	protected function casts(): array {
		return [
			'is_completed' => 'boolean',
			'position' => 'integer',
		];
	}

	protected static function booted(): void {
		$clearCache = fn () => Cache::forget('workflow:completed_slugs');

		static::saved($clearCache);
		static::deleted($clearCache);
	}

	public function workflow(): BelongsTo {
		return $this->belongsTo(Workflow::class);
	}

	public static function colorOptions(): array {
		return [
			'gray' => 'Gray',
			'info' => 'Blue',
			'success' => 'Green',
			'warning' => 'Amber',
			'danger' => 'Red',
			'primary' => 'Primary',
		];
	}

	public function getCssBorderColorClass(): string {
		return match ($this->color) {
			'info' => 'border-t-blue-500',
			'success' => 'border-t-green-500',
			'warning' => 'border-t-amber-500',
			'danger' => 'border-t-red-500',
			'primary' => 'border-t-sky-500',
			default => 'border-t-gray-400',
		};
	}

	public function getCssDotColorClass(): string {
		return match ($this->color) {
			'info' => 'bg-blue-500',
			'success' => 'bg-green-500',
			'warning' => 'bg-amber-500',
			'danger' => 'bg-red-500',
			'primary' => 'bg-sky-500',
			default => 'bg-gray-500',
		};
	}
}
