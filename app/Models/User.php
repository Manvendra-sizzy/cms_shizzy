<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_EMPLOYEE = 'employee';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'two_factor_enabled_at' => 'datetime',
    ];

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isEmployee(): bool
    {
        return $this->role === self::ROLE_EMPLOYEE;
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(CmsModule::class, 'cms_user_modules', 'user_id', 'cms_module_id')
            ->withTimestamps();
    }

    public function hasModule(string $moduleKey): bool
    {
        return $this->modules()->where('key', $moduleKey)->exists();
    }

    public function appRoleAssignments(): HasMany
    {
        return $this->hasMany(CmsUserRole::class, 'user_id');
    }

    public function hasAppRole(string $roleKey): bool
    {
        return $this->appRoleAssignments()
            ->where('active', true)
            ->whereHas('role', function ($q) use ($roleKey) {
                $q->where('key', $roleKey)->where('active', true);
            })->exists();
    }

    /**
     * System access rules:
     * - Admins: all systems.
     * - Developer role: all or selected systems.
     * - Other role-holders with systems module: all systems.
     */
    public function hasSystemAccess(int $systemId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (! $this->hasAppRole('developer')) {
            return true;
        }

        $developerAssignments = $this->appRoleAssignments()
            ->where('active', true)
            ->whereHas('role', function ($q) {
                $q->where('key', 'developer')->where('active', true);
            })->get();

        foreach ($developerAssignments as $assignment) {
            if ($assignment->all_projects) {
                return true;
            }
            if ($assignment->systems()->where('systems.id', $systemId)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns system IDs visible to the user in systems module.
     * Null means unrestricted.
     */
    public function systemScopeIds(): ?array
    {
        if ($this->isAdmin() || ! $this->hasAppRole('developer')) {
            return null;
        }

        $assignments = $this->appRoleAssignments()
            ->where('active', true)
            ->whereHas('role', function ($q) {
                $q->where('key', 'developer')->where('active', true);
            })->get();

        foreach ($assignments as $assignment) {
            if ($assignment->all_projects) {
                return null;
            }
        }

        return $assignments
            ->flatMap(fn (CmsUserRole $assignment) => $assignment->systems()->pluck('systems.id')->all())
            ->unique()
            ->values()
            ->all();
    }
}
