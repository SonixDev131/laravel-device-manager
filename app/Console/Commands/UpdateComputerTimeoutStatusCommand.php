<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Computer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateComputerTimeoutStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'computers:update-timeout-status 
                            {--timeout=3 : Timeout in minutes after which computers are considered offline}
                            {--dry-run : Show what would be updated without actually updating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update computer status based on timeout (auto-offline computers that haven\'t sent heartbeat)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $timeoutMinutes = (int) $this->option('timeout');
        $isDryRun = $this->option('dry-run');

        $this->info('Starting computer timeout status update...');
        $this->info("Timeout threshold: {$timeoutMinutes} minutes");

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Get all computers
        $computers = Computer::query()->get();
        $totalComputers = $computers->count();
        $updatedCount = 0;

        $this->info("Processing {$totalComputers} computers...");

        foreach ($computers as $computer) {
            $hasTimedOut = $computer->hasTimedOut($timeoutMinutes);
            $currentStatus = $computer->status->value;

            if ($isDryRun) {
                // Just show what would happen
                if ($hasTimedOut && $computer->status->value === 'online') {
                    $this->line("Would change {$computer->hostname} ({$computer->id}) from ONLINE to OFFLINE");
                    $updatedCount++;
                } elseif (! $hasTimedOut && $computer->status->value === 'offline') {
                    $this->line("Would change {$computer->hostname} ({$computer->id}) from OFFLINE to ONLINE");
                    $updatedCount++;
                }
            } else {
                // Actually update
                $statusChanged = $computer->updateStatusBasedOnTimeout($timeoutMinutes);

                if ($statusChanged) {
                    $newStatus = $computer->status->value;
                    $this->line("Updated {$computer->hostname} ({$computer->id}): {$currentStatus} → {$newStatus}");
                    $updatedCount++;

                    Log::info('Computer status updated due to timeout', [
                        'computer_id' => $computer->id,
                        'hostname' => $computer->hostname,
                        'old_status' => $currentStatus,
                        'new_status' => $newStatus,
                        'last_heartbeat_at' => $computer->last_heartbeat_at?->toISOString(),
                        'timeout_minutes' => $timeoutMinutes,
                    ]);
                }
            }
        }

        if ($isDryRun) {
            $this->info("DRY RUN COMPLETE: {$updatedCount} computers would be updated out of {$totalComputers} total");
        } else {
            $this->info("COMPLETE: Updated {$updatedCount} computers out of {$totalComputers} total");
        }

        return Command::SUCCESS;
    }
}
