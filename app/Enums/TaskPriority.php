<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TaskPriority: string implements HasColor, HasLabel {
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
	public function getLabel(): string {
		return match($this) {
			self::CRITICAL => 'Critical',
			self::HIGH => 'High',
			self::MEDIUM => 'Medium',
			self::LOW => 'Low',
		};
	}

	/**
	 * Get a Filament colour associated with the priority.
	 */
	public function getColor(): string {
		return match($this) {
			self::CRITICAL => 'danger',
			self::HIGH => 'warning',
			self::MEDIUM => 'info',
			self::LOW => 'gray',
		};
	}

	// Keep old names as aliases so any existing callers don't break
	public function label(): string { return $this->getLabel(); }
	public function colour(): string { return $this->getColor(); }

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
