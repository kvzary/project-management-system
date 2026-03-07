<?php

namespace App\Filament\Widgets\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasDepartmentScope
{
    /**
     * Returns the current user's department IDs.
     * Empty array means the user is an admin with no department filter.
     *
     * @return array<int>
     */
    protected function getDepartmentIds(): array
    {
        $user = auth()->user();

        if ($user->isSystemAdmin()) {
            return [];
        }

        return $user->departments()->pluck('departments.id')->toArray();
    }

    protected function scopeTasksToDepartments(Builder $query, array $departmentIds): Builder
    {
        if (empty($departmentIds)) {
            return $query;
        }

        return $query->whereHas('project', fn ($q) => $q->whereIn('department_id', $departmentIds));
    }

    protected function scopeProjectsToDepartments(Builder $query, array $departmentIds): Builder
    {
        if (empty($departmentIds)) {
            return $query;
        }

        return $query->whereIn('department_id', $departmentIds);
    }
}
