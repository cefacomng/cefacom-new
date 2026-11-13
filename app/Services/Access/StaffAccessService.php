<?php

namespace App\Services\Access;

use App\Enums\PermissionKey;
use App\Enums\RoleKey;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The only supported way to change who is staff and what staff may do.
 *
 * Roles are data: besides Super Admin and the default Admin Staff role, a Super Admin (or a staff
 * manager) can create restricted roles such as "Support Agent" holding only some permissions.
 *
 * Rules (enforced here, on the server, regardless of what any screen shows):
 *  - the actor needs the manage-staff permission;
 *  - only a Super Admin may grant or revoke the Super Admin role or change another Super Admin;
 *  - nobody may change their own roles (no self-escalation, no self-lockout);
 *  - the last Super Admin can never be removed;
 *  - Super Admin permissions are implicit and cannot be edited;
 *  - a non-Super-Admin can only assign or build roles made of permissions they hold themselves;
 *  - every change is written to the audit log in the same transaction.
 */
class StaffAccessService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /** @param  list<PermissionKey>  $permissions */
    public function createRole(User $actor, string $name, array $permissions): Role
    {
        $this->authorizeStaffManager($actor);

        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 80) {
            throw new StaffRuleViolation('A role needs a name of up to 80 characters.');
        }

        $key = Str::slug($name, '_');
        if ($key === '' || in_array($key, array_map(fn (RoleKey $k) => $k->value, RoleKey::cases()), true) || Role::where('key', $key)->exists()) {
            throw new StaffRuleViolation('A role with that name already exists.');
        }

        $wanted = $this->permissionKeys($permissions);
        $this->assertActorHolds($actor, $wanted);

        return DB::transaction(function () use ($actor, $name, $key, $wanted) {
            $role = Role::create(['key' => $key, 'name' => $name]);
            $role->permissions()->sync(Permission::whereIn('key', $wanted)->pluck('id')->all());
            $this->audit->record($actor, 'staff.role_created', $role, [], ['name' => $name, 'permissions' => $wanted->sort()->values()->all()]);

            return $role;
        });
    }

    public function assignRole(User $actor, User $target, Role $role): void
    {
        $this->authorizeRoleChange($actor, $target, $role);

        if (! $actor->isSuperAdmin()) {
            $this->assertActorHolds($actor, $role->permissions()->pluck('key'));
        }

        DB::transaction(function () use ($actor, $target, $role) {
            if ($target->roles()->where('roles.id', $role->id)->exists()) {
                return;
            }
            $before = $target->roles()->pluck('key')->all();
            $target->roles()->attach($role->id, ['created_at' => now()]);
            $target->forgetAccessCache();
            $this->audit->record($actor, 'staff.role_assigned', $target, ['roles' => $before], ['roles' => $target->roles()->pluck('key')->all()]);
        });
    }

    public function revokeRole(User $actor, User $target, Role $role): void
    {
        $this->authorizeRoleChange($actor, $target, $role);

        DB::transaction(function () use ($actor, $target, $role) {
            if (! $target->roles()->where('roles.id', $role->id)->exists()) {
                return;
            }

            if ($role->isSuperAdminRole()) {
                // Lock the rows so two simultaneous revocations cannot both pass this check and leave no Super Admin.
                $holders = DB::table('user_roles')->where('role_id', $role->id)->lockForUpdate()->pluck('user_id');
                if ($holders->reject(fn ($id) => (int) $id === $target->getKey())->isEmpty()) {
                    throw new StaffRuleViolation('The last Super Admin cannot be removed.');
                }
            }

            $before = $target->roles()->pluck('key')->all();
            $target->roles()->detach($role->id);
            $target->forgetAccessCache();
            $this->audit->record($actor, 'staff.role_revoked', $target, ['roles' => $before], ['roles' => $target->roles()->pluck('key')->all()]);
        });
    }

    /**
     * Replaces the permissions of a role.
     *
     * @param  list<PermissionKey>  $permissions
     */
    public function setRolePermissions(User $actor, Role $role, array $permissions): void
    {
        $this->authorizeStaffManager($actor);

        if ($role->isSuperAdminRole()) {
            throw new StaffRuleViolation('Super Admin permissions are implicit and cannot be edited.');
        }

        $wanted = $this->permissionKeys($permissions);
        $this->assertActorHolds($actor, $wanted);

        DB::transaction(function () use ($actor, $role, $wanted) {
            $before = $role->permissions()->pluck('key')->sort()->values()->all();
            $role->permissions()->sync(Permission::whereIn('key', $wanted)->pluck('id')->all());
            $after = $role->permissions()->pluck('key')->sort()->values()->all();

            if ($before !== $after) {
                $this->audit->record($actor, 'staff.role_permissions_changed', $role, ['permissions' => $before], ['permissions' => $after]);
            }
        });
    }

    /**
     * @param  list<PermissionKey>  $permissions
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function permissionKeys(array $permissions)
    {
        return collect($permissions)->map(fn (PermissionKey $p) => $p->value)->unique()->values();
    }

    /** @param  iterable<string>  $keys */
    private function assertActorHolds(User $actor, iterable $keys): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        foreach ($keys as $key) {
            if (! $actor->hasPermission($key)) {
                throw new AuthorizationException('You cannot grant permissions you do not hold.');
            }
        }
    }

    private function authorizeStaffManager(User $actor): void
    {
        if (! $actor->hasPermission(PermissionKey::ManageStaff)) {
            throw new AuthorizationException('You do not have permission to manage staff.');
        }
    }

    private function authorizeRoleChange(User $actor, User $target, Role $role): void
    {
        $this->authorizeStaffManager($actor);

        if ($actor->is($target)) {
            throw new AuthorizationException('You cannot change your own roles.');
        }

        if ($role->isSuperAdminRole() && ! $actor->isSuperAdmin()) {
            throw new AuthorizationException('Only a Super Admin can grant or revoke the Super Admin role.');
        }

        // Changing an existing Super Admin at all (for example removing a restricted role from them) is also reserved.
        if ($target->isSuperAdmin() && ! $actor->isSuperAdmin()) {
            throw new AuthorizationException('Only a Super Admin can change another Super Admin.');
        }
    }
}
