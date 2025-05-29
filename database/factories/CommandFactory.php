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
            'type' => $this->faker->randomElement([
                'LOCK', 'MESSAGE', 'SCREENSHOT', 'SHUTDOWN', 'RESTART',
                'LOG_OUT', 'UPDATE', 'CUSTOM', 'FIREWALL_ON',
                'FIREWALL_OFF', 'BLOCK_WEBSITE',
            ]),
            'params' => $this->faker->boolean(50) ? $this->getRandomParams() : null,
            'status' => $this->faker->randomElement(['pending', 'sent', 'in_progress', 'completed', 'failed']),
            'is_group_command' => $this->faker->boolean(30),
            'completed_at' => $this->faker->boolean(40) ? $this->faker->dateTime() : null,
            'error' => $this->faker->boolean(20) ? $this->faker->sentence() : null,
            'created_at' => $this->faker->dateTime(),
            'updated_at' => $this->faker->dateTime(),
        ];
    }

    /**
     * Generate random parameters based on command type
     *
     * @return array<string, mixed>
     */
    private function getRandomParams(): array
    {
        $commandType = $this->faker->randomElement([
            'MESSAGE', 'CUSTOM', 'BLOCK_WEBSITE',
        ]);

        return match ($commandType) {
            'MESSAGE' => ['message' => $this->faker->sentence()],
            'CUSTOM' => ['program' => $this->faker->randomElement(['notepad.exe', 'calc.exe', 'cmd.exe']), 'args' => $this->faker->words(3, true)],
            'BLOCK_WEBSITE' => ['urls' => $this->faker->randomElements([
                'facebook.com', 'twitter.com', 'instagram.com',
                'tiktok.com', 'youtube.com', 'reddit.com',
            ], $this->faker->numberBetween(1, 3))],
            default => ['message' => $this->faker->sentence()],
        };
    }
}
