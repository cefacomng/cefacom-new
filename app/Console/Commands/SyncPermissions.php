<?php

namespace App\Console\Commands;

use App\Services\Access\PermissionSynchronizer;
use Illuminate\Console\Command;

class SyncPermissions extends Command
{
    protected $signature = 'cefacom:sync-permissions';

    protected $description = 'Create any missing roles and permissions (safe to run on every deployment)';

    public function handle(PermissionSynchronizer $synchronizer): int
    {
        $result = $synchronizer->sync();
        $this->info("Permissions created: {$result['permissions_created']}, roles created: {$result['roles_created']}.");

        return self::SUCCESS;
    }
}
