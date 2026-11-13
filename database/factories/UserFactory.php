<?php

namespace Database\Factories;

use App\Enums\RoleKey;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('correct-horse-9'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    /** Gives the user a staff role (creating the role row if the test has not synced roles). */
    public function staff(RoleKey $role = RoleKey::AdminStaff): static
    {
        return $this->afterCreating(function (User $user) use ($role) {
            $row = Role::firstOrCreate(['key' => $role->value], ['name' => $role->label()]);
            $user->roles()->attach($row->id, ['created_at' => now()]);
            $user->forgetAccessCache();
        });
    }

    public function superAdmin(): static
    {
        return $this->staff(RoleKey::SuperAdmin);
    }
}
