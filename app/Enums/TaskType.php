<?php

namespace App\Enums;

enum TaskType: string {
	case TASK = 'task';
	case BUG = 'bug';
	case STORY = 'story';
	case EPIC = 'epic';

	/**
	 * Get all available values as an array.
	 */
	public static function values(): array {
		return array_column(self::cases(), 'value');
	}

	/**
	 * Get a human-readable label for the type.
	 */
	public function label(): string {
		return match($this) {
			self::TASK => 'Task',
			self::BUG => 'Bug',
			self::STORY => 'Story',
			self::EPIC => 'Epic',
		};
	}

	/**
	 * Get an icon associated with the type.
	 */
	public function icon(): string {
		return match($this) {
			self::TASK => 'heroicon-o-check-circle',
			self::BUG => 'heroicon-o-bug-ant',
			self::STORY => 'heroicon-o-book-open',
			self::EPIC => 'heroicon-o-bolt',
		};
	}

	/**
	 * Get a colour associated with the type.
	 */
	public function colour(): string {
		return match($this) {
			self::TASK => 'info',
			self::BUG => 'danger',
			self::STORY => 'success',
			self::EPIC => 'warning',
		};
	}
}
