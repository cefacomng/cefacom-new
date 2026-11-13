<?php

namespace App\Services\Access;

use App\Enums\PermissionKey;
use App\Enums\RoleKey;
use App\Models\Permission;
use App\Models\Role;

/**
 * Makes the database contain every permission and role the code knows about.
 * It only creates missing rows. It never attaches permissions to roles, so permissions
 * configured by a Super Admin are never overwritten by a deployment.
 */
class PermissionSynchronizer
{
    /** @return array{permissions_created: int, roles_created: int} */
    public function sync(): array
    {
        $permissions = 0;
        foreach (PermissionKey::cases() as $key) {
            $permission = Permission::firstOrCreate(['key' => $key->value], ['name' => $key->label()]);
            $permissions += $permission->wasRecentlyCreated ? 1 : 0;
        }

        $roles = 0;
        foreach (RoleKey::cases() as $key) {
            $role = Role::firstOrCreate(['key' => $key->value], ['name' => $key->label()]);
            $roles += $role->wasRecentlyCreated ? 1 : 0;
        }

        return ['permissions_created' => $permissions, 'roles_created' => $roles];
    }

    /** @return list<string> Keys the code defines but the database lacks. */
    public function missing(): array
    {
        $have = Permission::pluck('key')->all();
        $haveRoles = Role::pluck('key')->all();

        return [
            ...array_values(array_diff(array_map(fn (PermissionKey $k) => $k->value, PermissionKey::cases()), $have)),
            ...array_values(array_diff(array_map(fn (RoleKey $k) => $k->value, RoleKey::cases()), $haveRoles)),
        ];
    }
}
