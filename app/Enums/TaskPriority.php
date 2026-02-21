<?php

namespace App\Enums;

enum TaskPriority: string {
	case LOWEST = 'lowest';
	case LOW = 'low';
	case MEDIUM = 'medium';
	case HIGH = 'high';
	case HIGHEST = 'highest';

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
			self::LOWEST => 'Lowest',
			self::LOW => 'Low',
			self::MEDIUM => 'Medium',
			self::HIGH => 'High',
			self::HIGHEST => 'Highest',
		};
	}

	/**
	 * Get a colour associated with the priority.
	 */
	public function colour(): string {
		return match($this) {
			self::LOWEST => 'gray',
			self::LOW => 'info',
			self::MEDIUM => 'warning',
			self::HIGH => 'danger',
			self::HIGHEST => 'danger',
		};
	}

	/**
	 * Get a numeric value for sorting purposes.
	 */
	public function value(): int {
		return match($this) {
			self::LOWEST => 1,
			self::LOW => 2,
			self::MEDIUM => 3,
			self::HIGH => 4,
			self::HIGHEST => 5,
		};
	}
}
