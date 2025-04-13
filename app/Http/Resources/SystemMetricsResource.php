<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SystemMetricsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Default values if metrics are missing
        $default = [
            'cpu_usage' => null,
            'memory_usage' => null,
            'disk_usage' => null,
            'uptime' => null,
            'platform' => null,
            'platform_version' => null,
            'hostname' => null,
        ];

        // Combine with actual metrics, with fallbacks
        $metrics = $this->resource ? array_merge($default, $this->resource) : $default;

        return [
            'cpu_usage' => $metrics['cpu_usage'],
            'memory_usage' => $metrics['memory_usage'],
            'disk_usage' => $metrics['disk_usage'],
            'uptime' => $metrics['uptime'],
            'uptime_formatted' => $this->formatUptime($metrics['uptime']),
            'platform' => $metrics['platform'],
            'platform_version' => $metrics['platform_version'],
            'hostname' => $metrics['hostname'],
            'last_updated' => $this->when(isset($this->last_seen_at), function () {
                return $this->last_seen_at?->diffForHumans();
            }),
        ];
    }

    /**
     * Format uptime in a human-readable format
     */
    private function formatUptime(?int $seconds): ?string
    {
        if ($seconds === null) {
            return null;
        }

        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = $days.'d';
        }
        if ($hours > 0 || $days > 0) {
            $parts[] = $hours.'h';
        }
        if ($minutes > 0 || $hours > 0 || $days > 0) {
            $parts[] = $minutes.'m';
        }

        return empty($parts) ? '< 1m' : implode(' ', $parts);
    }
}
