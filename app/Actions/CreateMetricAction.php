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
     *   mac_address?: string,
     *   metrics: array{
     *     cpu_usage: float,
     *     memory_total: int,
     *     memory_used: int,
     *     disk_total: int,
     *     disk_used: int,
     *     uptime: int,
     *     platform: string,
     *     platform_version: string,
     *     hostname: string,
     *     firewall_status: array{
     *       Domain: string,
     *       Private: string,
     *       Public: string,
     *     },
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
            // Check if the computer exists first to avoid foreign key constraint violations
            $computer = Computer::find($data['computer_id']);

            if (! $computer) {
                // Check if room exists
                $room = Room::find($data['room_id']);
                if (! $room) {
                    Log::warning('Metrics received for non-existent room - skipping computer registration', [
                        'computer_id' => $data['computer_id'],
                        'room_id' => $data['room_id'],
                        'hostname' => $data['metrics']['hostname'] ?? 'unknown',
                        'message' => 'Agent may be misconfigured with wrong room_id. Available rooms should be checked.',
                    ]);

                    return null;
                }

                // Auto-register the computer if it doesn't exist
                $computer = Computer::create([
                    'id' => $data['computer_id'],
                    'room_id' => $data['room_id'],
                    'hostname' => $data['metrics']['hostname'],
                    'mac_address' => $data['mac_address'] ?? 'UNKNOWN',
                    'pos_row' => 1, // Default position
                    'pos_col' => 1, // Default position
                    'status' => ComputerStatus::from($data['status']),
                    'last_heartbeat_at' => now(),
                ]);

                Log::info('Auto-registered new computer from metrics', [
                    'computer_id' => $data['computer_id'],
                    'hostname' => $data['metrics']['hostname'],
                    'room_id' => $data['room_id'],
                ]);
            } else {
                // Update existing computer status
                $computer->status = ComputerStatus::from($data['status']);
                $computer->last_heartbeat_at = now();
                $computer->save();
            }

            // Now create the metric with confidence that the computer exists
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
                'firewall_status' => json_encode($data['metrics']['firewall_status'], JSON_FORCE_OBJECT),
            ]);

            Log::info('firewall_status received', ['firewall_status' => $data['metrics']['firewall_status']]);

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
            'firewall_status',
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
