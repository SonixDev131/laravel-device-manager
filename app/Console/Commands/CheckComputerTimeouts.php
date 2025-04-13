<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\UpdateComputerTimeoutStatusAction;
use Illuminate\Console\Command;

final class CheckComputerTimeouts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'computers:check-timeouts {--timeout=3 : Timeout in minutes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for computers that have timed out and update their status';

    /**
     * Execute the console command.
     */
    public function handle(UpdateComputerTimeoutStatusAction $action): int
    {
        $timeoutInMinutes = (int) $this->option('timeout');

        $this->info("Checking for computer timeouts (timeout: {$timeoutInMinutes} minutes)...");

        $stats = $action->handle($timeoutInMinutes);

        $this->table(
            ['Total', 'Changed', 'Online', 'Offline'],
            [[
                $stats['total'],
                $stats['changed'],
                $stats['online'],
                $stats['offline'],
            ]]
        );

        $this->info('Computer timeout check completed.');

        return Command::SUCCESS;
    }
}
