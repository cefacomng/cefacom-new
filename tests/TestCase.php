<?php

namespace Tests;

use App\Enums\PermissionKey;
use App\Enums\RoleKey;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\PermissionSynchronizer;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Safety net: never let a test (especially RefreshDatabase) touch a non-test database.
        $database = config('database.connections.'.config('database.default').'.database');
        if (! is_string($database) || ! str_ends_with($database, '_test')) {
            $this->fail('Refusing to run tests against database "'.$database.'". Test databases must end in _test.');
        }
    }

    private static int $roleCounter = 0;

    protected function role(RoleKey $key): Role
    {
        (new PermissionSynchronizer)->sync();

        return Role::where('key', $key->value)->firstOrFail();
    }

    /** A verified staff member on their own restricted role holding exactly the given permissions. */
    protected function staffWith(PermissionKey ...$permissions): User
    {
        (new PermissionSynchronizer)->sync();

        $role = Role::create(['key' => 'test_role_'.(++self::$roleCounter), 'name' => 'Test role '.self::$roleCounter]);
        $role->permissions()->sync(Permission::whereIn('key', array_map(fn (PermissionKey $p) => $p->value, $permissions))->pluck('id')->all());

        $user = User::factory()->create();
        $user->roles()->attach($role->id, ['created_at' => now()]);
        $user->forgetAccessCache();

        return $user;
    }

    protected function superAdmin(): User
    {
        (new PermissionSynchronizer)->sync();

        return User::factory()->superAdmin()->create();
    }
}
