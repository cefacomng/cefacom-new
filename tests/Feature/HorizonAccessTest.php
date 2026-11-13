<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class HorizonAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_super_admins_pass_the_horizon_gate(): void
    {
        $this->assertFalse(Gate::forUser(null)->allows('viewHorizon'));
        $this->assertFalse(Gate::forUser(User::factory()->create())->allows('viewHorizon'));
        $this->assertFalse(Gate::forUser($this->staffWith(...\App\Enums\PermissionKey::cases()))->allows('viewHorizon'));
        $this->assertTrue(Gate::forUser($this->superAdmin())->allows('viewHorizon'));
    }

    public function test_the_horizon_dashboard_is_forbidden_to_guests_and_customers_outside_local(): void
    {
        $this->get('/horizon')->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/horizon')->assertForbidden();
    }
}
