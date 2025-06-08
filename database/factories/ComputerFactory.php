<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ComputerStatus;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class ComputerFactory extends Factory
{
    public function definition(): array
    {
        $isOnline = $this->faker->boolean(20); // 20% chance to be online

        return [
            'id' => Str::uuid()->toString(),
            'room_id' => Room::factory(),
            'hostname' => $this->faker->word.'-PC-'.$this->faker->numberBetween(1, 100),
            'mac_address' => $this->faker->macAddress(),
            'pos_row' => $this->faker->numberBetween(1, 5),
            'pos_col' => $this->faker->numberBetween(1, 5),
            'status' => $isOnline ? ComputerStatus::ONLINE->value : ComputerStatus::OFFLINE->value,
            'last_heartbeat_at' => $isOnline ? now() : null,
        ];
    }
}
