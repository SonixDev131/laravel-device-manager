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
            'name' => $this->faker->word.'-PC-'.$this->faker->numberBetween(1, 100),
            'mac_address' => $this->faker->macAddress(),
            'ip_address' => $this->faker->ipv4(),
            'pos_row' => $this->faker->numberBetween(1, 10),
            'pos_col' => $this->faker->numberBetween(1, 10),
            'status' => $isOnline ? ComputerStatus::ONLINE->value : ComputerStatus::OFFLINE->value,
            'last_seen_at' => $isOnline ? now() : null,
        ];
    }
}
