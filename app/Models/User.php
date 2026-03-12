<?php

namespace App\Models;

use App\Enums\DepartmentRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialisation.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Determine if the user can access the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * Get the projects owned by the user.
     */
    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    /**
     * Get the projects the user is a member of.
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get the tasks assigned to the user.
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    /**
     * Get the tasks reported by the user.
     */
    public function reportedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'reporter_id');
    }

    /**
     * Get the tasks the user is watching.
     */
    public function watchingTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_watchers')
            ->withTimestamps();
    }

    /**
     * Get the comments created by the user.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get all departments this user belongs to.
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get departments where this user is a manager.
     */
    public function managedDepartments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_user')
            ->withPivot('role')
            ->wherePivot('role', DepartmentRole::MANAGER->value)
            ->withTimestamps();
    }

    /**
     * Determine if this user is a system admin (Spatie role).
     */
    public function isSystemAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Determine if the user belongs to a specific project.
     */
    public function belongsToProject(int $projectId): bool
    {
        return $this->projects()->where('project_id', $projectId)->exists()
            || $this->ownedProjects()->where('id', $projectId)->exists();
    }

    /**
     * Get the user's role in a specific project.
     */
    public function getProjectRole(int $projectId): ?string
    {
        $membership = $this->projects()->where('project_id', $projectId)->first();

        if ($membership) {
            return $membership->pivot->role;
        }

        if ($this->ownedProjects()->where('id', $projectId)->exists()) {
            return 'owner';
        }

        return null;
    }
}
