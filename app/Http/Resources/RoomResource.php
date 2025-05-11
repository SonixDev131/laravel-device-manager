<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'grid_rows' => $this->grid_rows,
            'grid_cols' => $this->grid_cols,
            'computers' => $this->when($this->relationLoaded('computers'), function () {
                return $this->computers->map(function ($computer) {
                    return [
                        'id' => $computer->id,
                        'hostname' => $computer->hostname,
                        'mac_address' => $computer->mac_address,
                        'pos_row' => $computer->pos_row,
                        'pos_col' => $computer->pos_col,
                        'created_at' => $computer->created_at,
                        'updated_at' => $computer->updated_at,
                        'room_id' => $computer->room_id,
                        'status' => $computer->status,
                        'system_metrics' => $computer->latestMetric ? [
                            'cpu_usage' => $computer->latestMetric->cpu_usage,
                            'memory_total' => $computer->latestMetric->memory_total,
                            'memory_used' => $computer->latestMetric->memory_used,
                            'disk_total' => $computer->latestMetric->disk_total,
                            'disk_used' => $computer->latestMetric->disk_used,
                            'uptime' => $computer->latestMetric->uptime,
                            'platform' => $computer->latestMetric->platform,
                            'platform_version' => $computer->latestMetric->platform_version,
                            'hostname' => $computer->latestMetric->hostname,
                        ] : null,
                    ];
                });
            }),
        ];
    }
}
