<?php

namespace Tests\Feature;

use App\Enums\PermissionKey;
use App\Enums\RoleKey;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\StaffAccessService;
use App\Services\Access\StaffRuleViolation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    private StaffAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StaffAccessService::class);
    }

    private function denied(callable $attempt): void
    {
        try {
            $attempt();
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);

            return;
        }
        $this->fail('Expected an AuthorizationException');
    }

    public function test_a_super_admin_can_make_someone_staff_and_it_is_audited(): void
    {
        $boss = $this->superAdmin();
        $person = User::factory()->create();

        $this->service->assignRole($boss, $person, $this->role(RoleKey::AdminStaff));

        $this->assertTrue($person->refresh()->isStaff());
        $entry = AuditLog::where('action', 'staff.role_assigned')->firstOrFail();
        $this->assertSame($boss->id, $entry->actor_id);
        $this->assertSame($person->id, $entry->target_id);
        $this->assertSame([], $entry->before['roles']);
        $this->assertSame(['admin_staff'], $entry->after['roles']);
    }

    public function test_assigning_a_role_someone_already_has_changes_and_logs_nothing(): void
    {
        $boss = $this->superAdmin();
        $staff = User::factory()->staff()->create();

        $this->service->assignRole($boss, $staff, $this->role(RoleKey::AdminStaff));

        $this->assertSame(0, AuditLog::count());
    }

    public function test_staff_without_the_manage_staff_permission_cannot_change_roles_or_permissions(): void
    {
        $actor = $this->staffWith(PermissionKey::ViewCustomers);
        $person = User::factory()->create();
        $staff = User::factory()->staff()->create();
        $role = $this->role(RoleKey::AdminStaff);

        $this->denied(fn () => $this->service->assignRole($actor, $person, $role));
        $this->denied(fn () => $this->service->revokeRole($actor, $staff, $role));
        $this->denied(fn () => $this->service->setRolePermissions($actor, $role, [PermissionKey::ViewCustomers]));
        $this->denied(fn () => $this->service->createRole($actor, 'Sneaky', [PermissionKey::ViewCustomers]));

        $this->assertFalse($person->refresh()->isStaff());
        $this->assertSame(0, AuditLog::count());
    }

    public function test_customers_cannot_change_anything(): void
    {
        $customer = User::factory()->create();
        $other = User::factory()->create();

        $this->denied(fn () => $this->service->assignRole($customer, $other, $this->role(RoleKey::AdminStaff)));
    }

    public function test_a_staff_manager_cannot_grant_the_super_admin_role_to_anyone_including_themselves(): void
    {
        $manager = $this->staffWith(PermissionKey::ManageStaff);
        $person = User::factory()->create();
        $super = $this->role(RoleKey::SuperAdmin);

        $this->denied(fn () => $this->service->assignRole($manager, $person, $super));
        $this->denied(fn () => $this->service->assignRole($manager, $manager, $super));

        $this->assertFalse($person->refresh()->isSuperAdmin());
        $this->assertFalse($manager->refresh()->isSuperAdmin());
    }

    public function test_nobody_can_change_their_own_roles(): void
    {
        $boss = $this->superAdmin();
        $this->superAdmin();

        $this->denied(fn () => $this->service->revokeRole($boss, $boss, $this->role(RoleKey::SuperAdmin)));
        $this->denied(fn () => $this->service->assignRole($boss, $boss, $this->role(RoleKey::AdminStaff)));
        $this->assertTrue($boss->refresh()->isSuperAdmin());
    }

    public function test_a_staff_manager_cannot_modify_a_super_admin(): void
    {
        $manager = $this->staffWith(PermissionKey::ManageStaff);
        $boss = $this->superAdmin();

        $this->denied(fn () => $this->service->assignRole($manager, $boss, $this->role(RoleKey::AdminStaff)));
        $this->denied(fn () => $this->service->revokeRole($manager, $boss, $this->role(RoleKey::SuperAdmin)));
        $this->assertTrue($boss->refresh()->isSuperAdmin());
    }

    public function test_two_super_admins_cannot_lock_everyone_out(): void
    {
        $a = $this->superAdmin();
        $b = $this->superAdmin();
        $super = $this->role(RoleKey::SuperAdmin);

        $this->service->revokeRole($a, $b, $super);
        $this->assertFalse($b->refresh()->isSuperAdmin());

        // The demoted person can no longer demote the remaining Super Admin.
        $this->denied(fn () => $this->service->revokeRole($b, $a, $super));
        $this->assertTrue($a->refresh()->isSuperAdmin());
        $this->assertSame(1, $super->users()->count());
    }

    public function test_the_last_super_admin_guard_refuses_a_revocation_that_would_leave_none(): void
    {
        // Normal paths cannot reach this (the actor is always another Super Admin); the guard exists for
        // simultaneous revocations. Exercise it directly by removing the actor's role mid-flight.
        $a = $this->superAdmin();
        $b = $this->superAdmin();
        $super = $this->role(RoleKey::SuperAdmin);

        \Illuminate\Support\Facades\DB::table('user_roles')->where('user_id', $a->id)->where('role_id', $super->id)->delete();
        $a->forgetAccessCache();
        $a->roles()->attach($this->role(RoleKey::AdminStaff)->id, ['created_at' => now()]);
        // $a still *appears* to be a Super Admin to the in-memory authorisation check:
        $stale = User::find($a->id);
        $stale->setRelation('roles', collect([$super]));

        $this->expectException(StaffRuleViolation::class);
        $this->service->revokeRole($stale, $b, $super);
    }

    public function test_revoking_a_role_takes_effect_immediately_and_is_audited(): void
    {
        $staff = $this->staffWith(PermissionKey::ViewCustomers);
        $role = $staff->roles()->first();
        $this->assertTrue($staff->hasPermission(PermissionKey::ViewCustomers));

        $this->service->revokeRole($this->superAdmin(), $staff, $role);

        $this->assertFalse($staff->hasPermission(PermissionKey::ViewCustomers));
        $this->assertFalse($staff->isStaff());
        $this->assertSame(1, AuditLog::where('action', 'staff.role_revoked')->count());
    }

    public function test_a_super_admin_can_create_a_restricted_role_and_assign_it(): void
    {
        $boss = $this->superAdmin();

        $role = $this->service->createRole($boss, 'Support Agent', [PermissionKey::ViewCustomers, PermissionKey::ViewCampaigns]);
        $agent = User::factory()->create();
        $this->service->assignRole($boss, $agent, $role);

        $this->assertSame('support_agent', $role->key);
        $this->assertTrue($agent->refresh()->hasPermission(PermissionKey::ViewCampaigns));
        $this->assertFalse($agent->hasPermission(PermissionKey::ChangePricing));
        $this->assertSame(['campaigns.view', 'customers.view'], AuditLog::where('action', 'staff.role_created')->firstOrFail()->after['permissions']);
    }

    public function test_role_names_cannot_collide_with_built_in_or_existing_roles_or_be_empty(): void
    {
        $boss = $this->superAdmin();
        $this->service->createRole($boss, 'Finance Reviewer', [PermissionKey::ViewPayments]);

        foreach (['Super Admin', 'super_admin', 'Admin Staff', 'Finance Reviewer', 'finance reviewer', '   ', '!!!', str_repeat('x', 81)] as $name) {
            try {
                $this->service->createRole($boss, $name, []);
                $this->fail("Role name accepted: {$name}");
            } catch (StaffRuleViolation) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_permissions_of_a_role_can_be_replaced_and_the_change_is_audited_with_before_and_after(): void
    {
        $boss = $this->superAdmin();
        $role = $this->role(RoleKey::AdminStaff);
        $staff = User::factory()->staff()->create();

        $this->service->setRolePermissions($boss, $role, [PermissionKey::ViewCustomers, PermissionKey::ViewPayments]);
        $this->assertTrue($staff->refresh()->hasPermission(PermissionKey::ViewPayments));

        $this->service->setRolePermissions($boss, $role, [PermissionKey::ViewCustomers]);
        $this->assertFalse($staff->refresh()->hasPermission(PermissionKey::ViewPayments));

        $last = AuditLog::where('action', 'staff.role_permissions_changed')->latest('id')->firstOrFail();
        $this->assertSame(['customers.view', 'payments.view'], $last->before['permissions']);
        $this->assertSame(['customers.view'], $last->after['permissions']);
        $this->assertSame(2, AuditLog::where('action', 'staff.role_permissions_changed')->count());
    }

    public function test_an_unchanged_permission_set_writes_no_audit_entry(): void
    {
        $boss = $this->superAdmin();
        $role = $this->role(RoleKey::AdminStaff);
        $this->service->setRolePermissions($boss, $role, [PermissionKey::ViewCustomers]);
        $before = AuditLog::count();

        $this->service->setRolePermissions($boss, $role, [PermissionKey::ViewCustomers]);

        $this->assertSame($before, AuditLog::count());
    }

    public function test_a_staff_manager_cannot_build_or_edit_a_role_with_permissions_they_do_not_hold(): void
    {
        $manager = $this->staffWith(PermissionKey::ManageStaff, PermissionKey::ViewCustomers);
        $role = $this->role(RoleKey::AdminStaff);

        $this->service->setRolePermissions($manager, $role, [PermissionKey::ViewCustomers]);
        $created = $this->service->createRole($manager, 'Viewers', [PermissionKey::ViewCustomers]);
        $this->assertSame('viewers', $created->key);

        $this->denied(fn () => $this->service->setRolePermissions($manager, $role, [PermissionKey::ChangePricing]));
        $this->denied(fn () => $this->service->createRole($manager, 'Pricing', [PermissionKey::ChangePricing]));
    }

    public function test_a_staff_manager_cannot_assign_a_role_stronger_than_themselves(): void
    {
        $boss = $this->superAdmin();
        $manager = $this->staffWith(PermissionKey::ManageStaff, PermissionKey::ViewCustomers);
        $strong = $this->service->createRole($boss, 'Pricing Owner', [PermissionKey::ChangePricing]);
        $weak = $this->service->createRole($boss, 'Customer Viewer', [PermissionKey::ViewCustomers]);
        $person = User::factory()->create();

        $this->denied(fn () => $this->service->assignRole($manager, $person, $strong));
        $this->assertFalse($person->refresh()->isStaff());

        $this->service->assignRole($manager, $person, $weak);
        $this->assertTrue($person->refresh()->hasPermission(PermissionKey::ViewCustomers));
    }

    public function test_super_admin_permissions_cannot_be_edited(): void
    {
        $this->expectException(StaffRuleViolation::class);
        $this->service->setRolePermissions($this->superAdmin(), $this->role(RoleKey::SuperAdmin), [PermissionKey::ViewCustomers]);
    }
}
