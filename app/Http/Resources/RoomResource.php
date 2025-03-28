<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
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
            'computers' => ComputerResource::collection($this->whenLoaded('computers')),
        ];
    }
}
