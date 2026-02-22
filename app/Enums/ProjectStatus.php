<?php

namespace App\Enums;

enum ProjectStatus: string {
	case ACTIVE = 'active';
	case ON_HOLD = 'on_hold';
	case COMPLETED = 'completed';
	case ARCHIVED = 'archived';

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
			self::ACTIVE => 'Active',
			self::ON_HOLD => 'On Hold',
			self::COMPLETED => 'Completed',
			self::ARCHIVED => 'Archived',
		};
	}

	/**
	 * Get a colour associated with the status.
	 */
	public function colour(): string {
		return match($this) {
			self::ACTIVE => 'success',
			self::ON_HOLD => 'warning',
			self::COMPLETED => 'info',
			self::ARCHIVED => 'gray',
		};
	}
}
