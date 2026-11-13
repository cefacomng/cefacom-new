<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\User;
use App\Enums\PermissionKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MakeSuperAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_verified_super_admin_with_a_hashed_password_and_syncs_permissions(): void
    {
        $this->artisan('cefacom:make-super-admin', ['email' => 'Boss@Example.com'])
            ->expectsQuestion('Full name', 'Chief Admin')
            ->expectsQuestion('Password', 'a-strong-pass-42')
            ->expectsQuestion('Confirm password', 'a-strong-pass-42')
            ->expectsOutputToContain('Super Admin created: boss@example.com')
            ->assertSuccessful();

        $user = User::where('email', 'boss@example.com')->firstOrFail();
        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertTrue(Hash::check('a-strong-pass-42', $user->password));
        $this->assertSame(count(PermissionKey::cases()), Permission::count());
        $this->assertSame('staff.super_admin_created', AuditLog::firstOrFail()->action);
    }

    public function test_the_password_never_appears_in_the_audit_log(): void
    {
        $this->artisan('cefacom:make-super-admin', ['email' => 'boss@example.com', '--name' => 'Chief'])
            ->expectsQuestion('Password', 'a-strong-pass-42')->expectsQuestion('Confirm password', 'a-strong-pass-42')->assertSuccessful();

        $this->assertStringNotContainsString('a-strong-pass-42', json_encode(AuditLog::all()));
    }

    public function test_it_refuses_an_existing_email_so_a_customer_account_is_never_silently_promoted(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->artisan('cefacom:make-super-admin', ['email' => 'ada@example.com'])->assertFailed();

        $this->assertTrue(User::firstWhere('email', 'ada@example.com')->isCustomer());
    }

    public function test_it_refuses_weak_or_mismatched_passwords_and_bad_emails(): void
    {
        $this->artisan('cefacom:make-super-admin', ['email' => 'boss@example.com', '--name' => 'Chief'])
            ->expectsQuestion('Password', 'weak')->expectsQuestion('Confirm password', 'weak')->assertFailed();
        $this->artisan('cefacom:make-super-admin', ['email' => 'boss@example.com', '--name' => 'Chief'])
            ->expectsQuestion('Password', 'a-strong-pass-42')->expectsQuestion('Confirm password', 'different-pass-43')->assertFailed();
        $this->artisan('cefacom:make-super-admin', ['email' => 'not-an-email'])->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_sync_permissions_is_safe_to_repeat_and_never_removes_configured_permissions(): void
    {
        $this->artisan('cefacom:sync-permissions')->assertSuccessful();
        $role = \App\Models\Role::where('key', 'admin_staff')->firstOrFail();
        $role->permissions()->attach(Permission::where('key', 'customers.view')->firstOrFail());

        $this->artisan('cefacom:sync-permissions')->expectsOutputToContain('Permissions created: 0, roles created: 0')->assertSuccessful();

        $this->assertSame(['customers.view'], $role->permissions()->pluck('key')->all());
    }
}
