<?php

namespace Rajibbinalam\BagistoCourier\Console\Commands;

use Illuminate\Console\Command;
use Rajibbinalam\BagistoCourier\Actions\SyncCourierStatusAction;

/**
 * php artisan courier:sync-status
 *
 * Add to Bagisto's app/Console/Kernel.php schedule, e.g.:
 *   $schedule->command('courier:sync-status')->everyFiveMinutes();
 *
 * Or run directly via a server cron entry (see README "Cron Setup").
 */
class SyncCourierStatusCommand extends Command
{
    protected $signature = 'courier:sync-status';

    protected $description = 'Poll all active couriers and sync tracking status for pending orders.';

    public function handle(SyncCourierStatusAction $action): int
    {
        $this->info('Syncing courier order statuses...');

        $count = $action->executeAll();

        $this->info("Done. Checked {$count} order(s).");

        return self::SUCCESS;
    }
}
