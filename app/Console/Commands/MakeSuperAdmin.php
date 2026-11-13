<?php

namespace App\Console\Commands;

use App\Enums\RoleKey;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\PermissionSynchronizer;
use App\Services\Audit\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Creates (or promotes) a Super Admin. This is the only way to create the first staff account:
 * public registration can never create staff. The password is read from a hidden prompt and
 * never passed as an argument, so it does not land in shell history or process lists.
 */
class MakeSuperAdmin extends Command
{
    protected $signature = 'cefacom:make-super-admin {email : Email address of the new Super Admin} {--name= : Display name (asked if omitted)}';

    protected $description = 'Create a Super Admin account';

    public function handle(PermissionSynchronizer $synchronizer, AuditLogger $audit): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        if (Validator::make(['email' => $email], ['email' => 'required|email:rfc|max:254'])->fails()) {
            $this->error('That is not a valid email address.');

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error('An account with that email already exists. Choose a different address for staff.');

            return self::FAILURE;
        }

        $name = trim((string) ($this->option('name') ?: $this->ask('Full name')));
        $password = (string) $this->secret('Password');
        $confirmation = (string) $this->secret('Confirm password');

        $validator = Validator::make(
            ['name' => $name, 'password' => $password, 'password_confirmation' => $confirmation],
            ['name' => ['required', 'string', 'max:120'], 'password' => ['required', 'confirmed', Password::defaults()]],
        );
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $synchronizer->sync();

        $user = DB::transaction(function () use ($name, $email, $password, $audit) {
            $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
            $user->email_verified_at = now();
            $user->save();
            $user->roles()->attach(Role::where('key', RoleKey::SuperAdmin->value)->firstOrFail()->id, ['created_at' => now()]);
            $audit->record(null, 'staff.super_admin_created', $user, [], ['email' => $email, 'roles' => [RoleKey::SuperAdmin->value]]);

            return $user;
        });

        $this->info("Super Admin created: {$user->email}");

        return self::SUCCESS;
    }
}
