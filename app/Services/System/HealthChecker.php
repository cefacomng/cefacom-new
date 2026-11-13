<?php

namespace App\Services\System;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Verifies the infrastructure the application depends on.
 * Failure details contain only the exception class, never connection strings or secrets.
 */
class HealthChecker
{
    /** @return list<array{name: string, ok: bool, detail: string}> */
    public function run(): array
    {
        return [
            $this->check('database', function () {
                $row = DB::selectOne('select version() as v');

                return 'MySQL '.$row->v;
            }),
            $this->check('redis', function () {
                Redis::connection()->ping();

                return 'PING ok';
            }),
            $this->check('queue', function () {
                $default = config('queue.default');
                if ($default !== 'redis') {
                    throw new \RuntimeException('Queue connection must be redis, got '.$default);
                }

                return 'redis';
            }),
            $this->check('permissions', function () {
                $missing = (new \App\Services\Access\PermissionSynchronizer)->missing();
                if ($missing !== []) {
                    throw new \RuntimeException('Missing roles/permissions; run php artisan cefacom:sync-permissions');
                }

                return 'synced';
            }),
            $this->check('horizon', function () {
                if (! class_exists(\Laravel\Horizon\Horizon::class)) {
                    throw new \RuntimeException('Laravel Horizon is not installed');
                }

                return 'installed';
            }),
        ];
    }

    /**
     * @param  callable(): string  $probe
     * @return array{name: string, ok: bool, detail: string}
     */
    private function check(string $name, callable $probe): array
    {
        try {
            return ['name' => $name, 'ok' => true, 'detail' => $probe()];
        } catch (Throwable $e) {
            return ['name' => $name, 'ok' => false, 'detail' => $e::class];
        }
    }
}
