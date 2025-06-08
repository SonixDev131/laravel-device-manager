<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Command;
use App\Models\Computer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Screenshot>
 */
class ScreenshotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $takenAt = $this->faker->dateTimeBetween('-30 days', 'now');
        $fileExtension = $this->faker->randomElement(['png', 'jpg', 'jpeg']);
        $fileName = 'screenshot_'.$takenAt->format('YmdHis').'_'.$this->faker->uuid().'.'.$fileExtension;

        return [
            'command_id' => Command::factory(),
            'computer_id' => Computer::factory(),
            'file_path' => 'screenshots/'.$takenAt->format('Y/m/d').'/'.$fileName,
            'file_name' => $fileName,
            'file_size' => $this->faker->numberBetween(100000, 5000000), // 100KB to 5MB
            'mime_type' => $this->faker->randomElement(['image/png', 'image/jpeg']),
            'taken_at' => $takenAt,
        ];
    }

    /**
     * Create a screenshot for a specific command and computer
     */
    public function forCommandAndComputer(string $commandId, string $computerId): static
    {
        return $this->state(fn (array $attributes) => [
            'command_id' => $commandId,
            'computer_id' => $computerId,
        ]);
    }

    /**
     * Create a screenshot with a specific file size
     */
    public function withFileSize(int $size): static
    {
        return $this->state(fn (array $attributes) => [
            'file_size' => $size,
        ]);
    }

    /**
     * Create a PNG screenshot
     */
    public function png(): static
    {
        return $this->state(function (array $attributes) {
            $fileName = str_replace(['.jpg', '.jpeg'], '.png', $attributes['file_name']);

            return [
                'file_name' => $fileName,
                'file_path' => str_replace($attributes['file_name'], $fileName, $attributes['file_path']),
                'mime_type' => 'image/png',
            ];
        });
    }

    /**
     * Create a JPEG screenshot
     */
    public function jpeg(): static
    {
        return $this->state(function (array $attributes) {
            $fileName = str_replace(['.png'], '.jpg', $attributes['file_name']);

            return [
                'file_name' => $fileName,
                'file_path' => str_replace($attributes['file_name'], $fileName, $attributes['file_path']),
                'mime_type' => 'image/jpeg',
            ];
        });
    }
}
