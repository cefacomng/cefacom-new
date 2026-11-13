<?php

namespace Tests\Feature;

use App\Enums\PermissionKey;
use App\Models\User;
use App\Support\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_a_customer_has_no_staff_standing_and_no_permissions(): void
    {
        $customer = User::factory()->create();

        $this->assertTrue($customer->isCustomer());
        $this->assertFalse($customer->isStaff());
        $this->assertFalse($customer->isSuperAdmin());
        foreach (PermissionKey::cases() as $permission) {
            $this->assertFalse($customer->hasPermission($permission));
            $this->assertFalse(Gate::forUser($customer)->allows($permission->value));
        }
    }

    public function test_a_super_admin_holds_every_permission(): void
    {
        $boss = $this->superAdmin();

        $this->assertTrue($boss->isStaff());
        foreach (PermissionKey::cases() as $permission) {
            $this->assertTrue($boss->hasPermission($permission));
            $this->assertTrue(Gate::forUser($boss)->allows($permission->value));
        }
    }

    public function test_admin_staff_hold_only_the_permissions_granted_to_their_role(): void
    {
        $staff = $this->staffWith(PermissionKey::ViewCustomers, PermissionKey::ViewAuditLogs);

        $this->assertTrue($staff->hasPermission(PermissionKey::ViewCustomers));
        $this->assertTrue($staff->hasPermission('audit_logs.view'));
        $this->assertFalse($staff->hasPermission(PermissionKey::ChangePricing));
        $this->assertFalse($staff->hasPermission(PermissionKey::ManageStaff));
        $this->assertTrue(Gate::forUser($staff)->denies('pricing.change'));
    }

    public function test_a_staff_member_with_a_role_but_no_permissions_can_do_nothing_beyond_the_overview(): void
    {
        $staff = User::factory()->staff()->create();

        $this->assertTrue($staff->isStaff());
        $this->assertSame([], $staff->permissionKeys()->all());
        $this->actingAs($staff)->get('/admin')->assertOk();
        foreach (Navigation::keys('admin') as $key) {
            if ($key !== 'overview') {
                $this->get("/admin/{$key}")->assertForbidden();
            }
        }
    }

    public function test_customers_get_a_404_for_every_admin_url_so_the_area_is_not_revealed(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['/admin', '/admin/customers', '/admin/pricing', '/admin/staff', '/admin/audit-logs', '/admin/account'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }

    public function test_staff_are_sent_to_the_admin_area_from_every_customer_url(): void
    {
        $this->actingAs($this->superAdmin());

        foreach (['/app', '/app/settings', '/app/sms', '/app/wallet'] as $path) {
            $this->get($path)->assertRedirect('/admin');
        }
    }

    public function test_every_admin_section_enforces_exactly_its_own_permission(): void
    {
        foreach (Navigation::admin() as $item) {
            if ($item['key'] === 'overview') {
                continue;
            }
            $permission = Navigation::permissionFor($item['key']);
            $this->assertNotNull($permission, "Section {$item['key']} has no permission mapped");

            $with = $this->staffWith($permission);
            $without = $this->staffWith(...array_values(array_filter(PermissionKey::cases(), fn ($p) => $p !== $permission)));

            $this->actingAs($with)->get($item['href'])->assertOk();
            $this->actingAs($without)->get($item['href'])->assertForbidden();
        }
    }

    public function test_the_admin_navigation_only_contains_sections_the_person_may_open(): void
    {
        $staff = $this->staffWith(PermissionKey::ViewCustomers, PermissionKey::ManageProviders);

        $this->actingAs($staff)->get('/admin')->assertInertia(fn (Assert $page) => $page
            ->where('navigation.admin', fn ($items) => collect($items)->pluck('label')->all() === ['Overview', 'Customers', 'SMS Providers', 'Email Providers'])
            ->missing('navigation.customer'));
    }

    public function test_a_super_admin_sees_all_thirteen_sections(): void
    {
        $this->actingAs($this->superAdmin())->get('/admin')->assertInertia(fn (Assert $page) => $page->has('navigation.admin', 13));
    }

    public function test_customers_receive_customer_navigation_only(): void
    {
        $this->actingAs(User::factory()->create())->get('/app')->assertInertia(fn (Assert $page) => $page
            ->has('navigation.customer', 7)->missing('navigation.admin')
            ->where('auth.user.isStaff', false));
    }

    public function test_guests_receive_no_navigation_or_user(): void
    {
        $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('navigation', null)->where('auth.user', null));
    }

    public function test_shared_user_data_never_includes_secrets_or_ids(): void
    {
        $user = User::factory()->create(['name' => 'Ada']);

        $response = $this->actingAs($user)->get('/app');

        $shared = Assert::fromTestResponse($response)->toArray()['props']['auth']['user'];
        $this->assertSame(['name', 'email', 'businessName', 'isStaff', 'emailVerified', 'roles'], array_keys($shared));
    }

    public function test_unverified_staff_cannot_use_the_admin_area(): void
    {
        $staff = User::factory()->unverified()->staff()->create();

        $this->actingAs($staff)->get('/admin')->assertRedirect(route('verification.notice'));
    }

    public function test_unknown_sections_are_not_found(): void
    {
        $this->actingAs(User::factory()->create())->get('/app/nope')->assertNotFound();
        $this->actingAs($this->superAdmin())->get('/admin/nope')->assertNotFound();
    }
}
