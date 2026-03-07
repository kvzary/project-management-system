<?php

namespace App\Models;

use App\Enums\DepartmentRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'name',
        'description',
        'color',
    ];

    /**
     * All members (managers + regular members).
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'department_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Only managers of this department.
     */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'department_user')
            ->withPivot('role')
            ->wherePivot('role', DepartmentRole::MANAGER->value)
            ->withTimestamps();
    }

    /**
     * Projects that belong to this department.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
