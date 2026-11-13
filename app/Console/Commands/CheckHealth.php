<?php

namespace App\Console\Commands;

use App\Services\System\HealthChecker;
use Illuminate\Console\Command;

class CheckHealth extends Command
{
    protected $signature = 'cefacom:check';

    protected $description = 'Check database, Redis, queue and Horizon';

    public function handle(HealthChecker $checker): int
    {
        $failed = false;
        foreach ($checker->run() as $result) {
            $this->line(sprintf('%-9s %s  %s', $result['name'], $result['ok'] ? 'OK  ' : 'FAIL', $result['detail']));
            $failed = $failed || ! $result['ok'];
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
