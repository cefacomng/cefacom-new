<?php

namespace App\Models;

use App\Enums\PermissionKey;
use App\Enums\RoleKey;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

/**
 * A person who signs in. A user with no roles is a customer; a user with at least one role is staff.
 * Staff and customers are separate experiences (/admin and /app).
 */
#[Fillable(['name', 'business_name', 'email', 'phone', 'password', 'email_preferences'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @var Collection<int, string>|null Per-instance permission cache; never shared between requests. */
    private ?Collection $permissionKeys = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'email_preferences' => 'array',
        ];
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withPivot('created_at');
    }

    public function isStaff(): bool
    {
        return $this->roles->isNotEmpty();
    }

    public function isCustomer(): bool
    {
        return ! $this->isStaff();
    }

    public function isSuperAdmin(): bool
    {
        return $this->roles->contains('key', RoleKey::SuperAdmin->value);
    }

    public function hasPermission(PermissionKey|string $permission): bool
    {
        $key = $permission instanceof PermissionKey ? $permission->value : $permission;

        return $this->isSuperAdmin() || $this->permissionKeys()->contains($key);
    }

    /** @return Collection<int, string> */
    public function permissionKeys(): Collection
    {
        return $this->permissionKeys ??= $this->roles()->with('permissions')->get()
            ->flatMap(fn (Role $role) => $role->permissions->pluck('key'))
            ->unique()
            ->values();
    }

    /** @return $this */
    public function refresh()
    {
        $this->permissionKeys = null;

        return parent::refresh();
    }

    /** Clears cached role and permission data after any change to this user's roles. */
    public function forgetAccessCache(): void
    {
        $this->permissionKeys = null;
        $this->unsetRelation('roles');
    }

    public function homePath(): string
    {
        return $this->isStaff() ? '/admin' : '/app';
    }

    /** @return array{low_balance_alerts: bool, campaign_reports: bool, product_updates: bool} */
    public function emailPreferenceValues(): array
    {
        return [...self::DEFAULT_EMAIL_PREFERENCES, ...array_intersect_key($this->email_preferences ?? [], self::DEFAULT_EMAIL_PREFERENCES)];
    }

    public const DEFAULT_EMAIL_PREFERENCES = [
        'low_balance_alerts' => true,
        'campaign_reports' => true,
        'product_updates' => false,
    ];
}
