<?php

namespace App\Filament\Resources\Concerns;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

/**
 * Drop this trait into any Resource to enforce role-based permissions.
 * The resource key is derived from the class name automatically
 * (e.g. WorkflowResource → 'workflow').
 *
 * Falls back to `true` if the permission hasn't been seeded yet,
 * so existing users are not locked out before permissions are assigned.
 */
trait HasResourcePermissions
{
    protected static function permissionKey(): string
    {
        return strtolower(str_replace('resource', '', strtolower(class_basename(static::class))));
    }

    private static function checkPermission(string $action): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->isSystemAdmin()) {
            return true;
        }

        try {
            return $user->hasPermissionTo(static::permissionKey() . '.' . $action);
        } catch (PermissionDoesNotExist) {
            // Permission not seeded yet — allow by default so nothing breaks.
            return true;
        }
    }

    public static function canViewAny(): bool
    {
        return static::checkPermission('view');
    }

    public static function canCreate(): bool
    {
        return static::checkPermission('create');
    }

    public static function canEdit(Model $record): bool
    {
        return static::checkPermission('edit');
    }

    public static function canDelete(Model $record): bool
    {
        return static::checkPermission('delete');
    }

    public static function canDeleteAny(): bool
    {
        return static::checkPermission('delete');
    }
}
