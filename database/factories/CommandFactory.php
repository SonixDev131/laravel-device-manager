<?php

namespace Database\Factories;

use App\Enums\CommandStatus;
use App\Models\Computer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommandFactory extends Factory
{
    public function definition(): array
    {
        return [
            'machine_id' => Computer::factory(), // Tự động tạo Computer và sử dụng ID
            'command_type' => $this->faker->randomElement(['SHUTDOWN', 'RESTART', 'INSTALL', 'UPDATE']),
            'payload' => null,
            'status' => CommandStatus::PENDING,
            'completed_at' => null,
            'created_at' => $this->faker->dateTime(),
            'updated_at' => $this->faker->dateTime(),
        ];
    }
}
