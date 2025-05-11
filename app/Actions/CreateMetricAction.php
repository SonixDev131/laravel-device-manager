<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ComputerStatus;
use App\Models\Computer;
use App\Models\Metric;
use App\Models\Room;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CreateMetricAction
{
    /**
     * Create a new metric record and update computer status.
     *
     * @param array{
     *   computer_id: string,
     *   room_id: string,
     *   status: string,
     *   last_heartbeat_at: string,
     *   timestamp: int,
     *   metrics: array{
     *     cpu_usage: float,
     *     memory_total: int,
     *     memory_used: int,
     *     disk_total: int,
     *     disk_used: int,
     *     uptime: int,
     *     platform: string,
     *     platform_version: string,
     *     hostname: string
     *   }
     * } $data The metric data
     * @return Metric|null The created metric or null if creation failed
     */
    public function handle(array $data): ?Metric
    {
        if (! $this->validateData($data)) {
            Log::error('Invalid metric data structure', ['data' => $data]);

            return null;
        }

        try {
            // Create the new metric
            $metric = Metric::query()->create([
                'computer_id' => $data['computer_id'],
                'hostname' => $data['metrics']['hostname'],
                'platform' => $data['metrics']['platform'],
                'platform_version' => $data['metrics']['platform_version'],
                'cpu_usage' => $data['metrics']['cpu_usage'],
                'memory_total' => $data['metrics']['memory_total'],
                'memory_used' => $data['metrics']['memory_used'],
                'disk_total' => $data['metrics']['disk_total'],
                'disk_used' => $data['metrics']['disk_used'],
                'uptime' => $data['metrics']['uptime'],
            ]);

            // Update the associated computer's status and metrics
            $computer = Computer::find($data['computer_id']);

            if ($computer) {
                $computer->status = ComputerStatus::from($data['status']);
                $computer->last_heartbeat_at = now();
                $computer->save();
            } else {
                Log::warning('Computer not found when processing metrics', [
                    'computer_id' => $data['computer_id'],
                ]);
            }

            return $metric;
        } catch (Throwable $e) {
            Log::error('Failed to create metric', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return null;
        }
    }

    /**
     * Validate the metric data structure.
     *
     * @param  array<string, mixed>  $data  The data to validate
     * @return bool Whether the data is valid
     */
    private function validateData(array $data): bool
    {
        $requiredKeys = ['computer_id', 'room_id', 'status', 'timestamp', 'metrics'];
        $requiredMetricKeys = [
            'cpu_usage',
            'memory_total',
            'memory_used',
            'disk_total',
            'disk_used',
            'uptime',
            'platform',
            'platform_version',
            'hostname',
        ];

        // Check required top-level keys
        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $data)) {
                return false;
            }
        }

        // Check metrics structure
        if (! is_array($data['metrics'])) {
            return false;
        }

        // Check required metric keys
        foreach ($requiredMetricKeys as $key) {
            if (! array_key_exists($key, $data['metrics'])) {
                return false;
            }
        }

        return true;
    }
}
