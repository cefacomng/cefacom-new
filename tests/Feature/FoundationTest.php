<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_the_foundation_inertia_page(): void
    {
        // PHP tests must not depend on the compiled frontend; the build is verified separately.
        $this->withoutVite();

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Foundation')
                ->where('appName', 'Cefacom')
                ->where('designSystem', true));
    }

    public function test_laravel_health_endpoint_responds(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_foundation_migrations_create_base_tables(): void
    {
        foreach (['users', 'sessions', 'password_reset_tokens', 'jobs', 'failed_jobs', 'roles', 'permissions', 'role_permissions', 'user_roles', 'audit_logs'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table {$table}");
        }
    }

    public function test_money_configuration_is_integer_kobo_naira(): void
    {
        $this->assertSame('NGN', config('cefacom.currency'));
        $this->assertSame(100, config('cefacom.kobo_per_naira'));
        $this->assertSame('Africa/Lagos', config('cefacom.display_timezone'));
    }

    public function test_signed_in_people_are_sent_from_the_home_page_to_their_own_area(): void
    {
        $this->actingAs(\App\Models\User::factory()->create())->get('/')->assertRedirect('/app');
        $this->actingAs(\App\Models\User::factory()->superAdmin()->create())->get('/')->assertRedirect('/admin');
    }

    public function test_the_design_system_page_exists_only_in_local_development_and_tests(): void
    {
        $this->withoutVite();
        $this->get('/design-system')->assertOk();

        $this->app['env'] = 'production';
        $this->get('/design-system')->assertNotFound();
    }
}
