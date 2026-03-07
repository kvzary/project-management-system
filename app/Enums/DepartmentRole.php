<?php

namespace App\Enums;

enum DepartmentRole: string
{
    case MANAGER = 'manager';
    case MEMBER = 'member';

    public function label(): string
    {
        return match ($this) {
            self::MANAGER => 'Manager',
            self::MEMBER => 'Member',
        };
    }

    public static function options(): array
    {
        return [
            self::MANAGER->value => self::MANAGER->label(),
            self::MEMBER->value => self::MEMBER->label(),
        ];
    }
}
