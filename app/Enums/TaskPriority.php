<?php

namespace App\Enums;

enum TaskPriority: string {
	case CRITICAL = 'critical';
	case HIGH = 'high';
	case MEDIUM = 'medium';
	case LOW = 'low';

	/**
	 * Get all available values as an array.
	 */
	public static function values(): array {
		return array_column(self::cases(), 'value');
	}

	/**
	 * Get a human-readable label for the priority.
	 */
	public function label(): string {
		return match($this) {
			self::CRITICAL => 'Critical',
			self::HIGH => 'High',
			self::MEDIUM => 'Medium',
			self::LOW => 'Low',
		};
	}

	/**
	 * Get a colour associated with the priority.
	 */
	public function colour(): string {
		return match($this) {
			self::CRITICAL => 'danger',
			self::HIGH => 'warning',
			self::MEDIUM => 'info',
			self::LOW => 'gray',
		};
	}

	/**
	 * Get a numeric value for sorting purposes.
	 */
	public function sortOrder(): int {
		return match($this) {
			self::CRITICAL => 4,
			self::HIGH => 3,
			self::MEDIUM => 2,
			self::LOW => 1,
		};
	}
}
