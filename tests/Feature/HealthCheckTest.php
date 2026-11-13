<?php

namespace Tests\Feature;

use App\Services\System\HealthChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_infrastructure_checks_pass_against_real_mysql_and_redis(): void
    {
        config(['queue.default' => 'redis']);
        (new \App\Services\Access\PermissionSynchronizer)->sync();

        $results = collect((new HealthChecker)->run())->keyBy('name');

        foreach (['database', 'redis', 'queue', 'permissions', 'horizon'] as $name) {
            $this->assertTrue($results[$name]['ok'], "{$name} failed: ".$results[$name]['detail']);
        }
    }

    public function test_queue_check_fails_when_queue_is_not_redis(): void
    {
        config(['queue.default' => 'sync']);

        $results = collect((new HealthChecker)->run())->keyBy('name');

        $this->assertFalse($results['queue']['ok']);
    }

    public function test_failure_details_do_not_leak_connection_secrets(): void
    {
        $original = config('database.connections.mysql');
        config(['database.connections.mysql.password' => 'super-secret-value', 'database.connections.mysql.host' => '203.0.113.1', 'database.connections.mysql.options' => [\PDO::ATTR_TIMEOUT => 1]]);
        \Illuminate\Support\Facades\DB::purge('mysql');

        try {
            $results = collect((new HealthChecker)->run())->keyBy('name');
        } finally {
            // Put the real connection back so the test framework's own cleanup can reach the database.
            config(['database.connections.mysql' => $original]);
            \Illuminate\Support\Facades\DB::purge('mysql');
        }

        $this->assertFalse($results['database']['ok']);
        $this->assertStringNotContainsString('super-secret-value', $results['database']['detail']);
        $this->assertStringNotContainsString('203.0.113.1', $results['database']['detail']);
    }

    public function test_permissions_check_fails_until_roles_and_permissions_are_synced(): void
    {
        $results = collect((new HealthChecker)->run())->keyBy('name');
        $this->assertFalse($results['permissions']['ok']);

        (new \App\Services\Access\PermissionSynchronizer)->sync();
        $results = collect((new HealthChecker)->run())->keyBy('name');
        $this->assertTrue($results['permissions']['ok']);
    }

    public function test_cefacom_check_command_succeeds_when_healthy(): void
    {
        config(['queue.default' => 'redis']);
        (new \App\Services\Access\PermissionSynchronizer)->sync();

        $this->artisan('cefacom:check')->assertSuccessful();
    }
}
