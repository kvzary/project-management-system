<?php

namespace App\Enums;

enum ProjectStatus: string {
	case ACTIVE = 'active';
	case ARCHIVED = 'archived';
	case ON_HOLD = 'on_hold';

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
			self::ARCHIVED => 'Archived',
			self::ON_HOLD => 'On Hold',
		};
	}

	/**
	 * Get a colour associated with the status.
	 */
	public function colour(): string {
		return match($this) {
			self::ACTIVE => 'success',
			self::ARCHIVED => 'gray',
			self::ON_HOLD => 'warning',
		};
	}
}
