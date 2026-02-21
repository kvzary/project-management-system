<?php

namespace App\Enums;

enum ProjectRole: string {
	case ADMIN = 'admin';
	case MEMBER = 'member';
	case VIEWER = 'viewer';

	/**
	 * Get all available values as an array.
	 */
	public static function values(): array {
		return array_column(self::cases(), 'value');
	}

	/**
	 * Get a human-readable label for the role.
	 */
	public function label(): string {
		return match($this) {
			self::ADMIN => 'Admin',
			self::MEMBER => 'Member',
			self::VIEWER => 'Viewer',
		};
	}

	/**
	 * Determine if the role can manage the project.
	 */
	public function canManage(): bool {
		return $this === self::ADMIN;
	}

	/**
	 * Determine if the role can contribute to the project.
	 */
	public function canContribute(): bool {
		return in_array($this, [self::ADMIN, self::MEMBER]);
	}
}
