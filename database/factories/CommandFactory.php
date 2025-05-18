<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Computer;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

final class CommandFactory extends Factory
{
    public function definition(): array
    {
        $isForComputer = $this->faker->boolean(70);

        return [
            'computer_id' => $isForComputer ? Computer::factory() : null,
            'room_id' => $isForComputer ? null : Room::factory(),
            'type' => $this->faker->randomElement(['LOCK', 'MESSAGE', 'SCREENSHOT', 'SHUTDOWN', 'RESTART', 'LOG_OUT', 'UPDATE']),
            'params' => $this->faker->boolean(30) ? ['message' => $this->faker->sentence()] : null,
            'status' => $this->faker->randomElement(['pending', 'sent', 'in_progress', 'completed', 'failed']),
            'is_group_command' => $this->faker->boolean(30),
            'completed_at' => $this->faker->boolean(40) ? $this->faker->dateTime() : null,
            'created_at' => $this->faker->dateTime(),
            'updated_at' => $this->faker->dateTime(),
        ];
    }
}
