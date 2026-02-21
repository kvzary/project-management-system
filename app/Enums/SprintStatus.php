<?php

namespace App\Enums;

enum SprintStatus: string {
	case PLANNING = 'planning';
	case ACTIVE = 'active';
	case COMPLETED = 'completed';

	/**
	 * Get all available values as an array.
	 */
	public static function values(): array {
		return array_column(self::cases(), 'value');
	}

	/**
	 * Get a human-readable label for the status.
	 */
	public function label(): string {
		return match($this) {
			self::PLANNING => 'Planning',
			self::ACTIVE => 'Active',
			self::COMPLETED => 'Completed',
		};
	}

	/**
	 * Get a colour associated with the status.
	 */
	public function colour(): string {
		return match($this) {
			self::PLANNING => 'gray',
			self::ACTIVE => 'success',
			self::COMPLETED => 'info',
		};
	}

	/**
	 * Determine if tasks can be added to this sprint.
	 */
	public function canAddTasks(): bool {
		return in_array($this, [self::PLANNING, self::ACTIVE]);
	}
}
