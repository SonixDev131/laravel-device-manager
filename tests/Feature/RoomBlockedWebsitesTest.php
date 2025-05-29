<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CommandStatus;
use App\Enums\CommandType;
use App\Models\Command;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\{actingAs, get};

uses(RefreshDatabase::class);

describe('Room Blocked Websites', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->room = Room::factory()->create();
    });

    it('returns empty array when no websites are blocked', function () {
        actingAs($this->user);

        $response = get(route('rooms.blocked-websites.index', $this->room->id));

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
            ]);
    });

    it('returns blocked websites from latest completed command', function () {
        actingAs($this->user);

        // Create an old command
        Command::factory()->create([
            'room_id' => $this->room->id,
            'type' => CommandType::BLOCK_WEBSITE->value,
            'status' => CommandStatus::COMPLETED->value,
            'params' => ['urls' => ['old-site.com']],
            'created_at' => now()->subDays(1),
        ]);

        // Create a newer command
        Command::factory()->create([
            'room_id' => $this->room->id,
            'type' => CommandType::BLOCK_WEBSITE->value,
            'status' => CommandStatus::COMPLETED->value,
            'params' => ['urls' => ['facebook.com', 'twitter.com']],
            'created_at' => now(),
        ]);

        $response = get(route('rooms.blocked-websites.index', $this->room->id));

        $response->assertStatus(200)
            ->assertJson([
                'data' => ['facebook.com', 'twitter.com'],
            ]);
    });

    it('ignores failed or pending commands', function () {
        actingAs($this->user);

        // Create a completed command
        Command::factory()->create([
            'room_id' => $this->room->id,
            'type' => CommandType::BLOCK_WEBSITE->value,
            'status' => CommandStatus::COMPLETED->value,
            'params' => ['urls' => ['allowed-site.com']],
            'created_at' => now()->subHour(),
        ]);

        // Create a newer failed command
        Command::factory()->create([
            'room_id' => $this->room->id,
            'type' => CommandType::BLOCK_WEBSITE->value,
            'status' => CommandStatus::FAILED->value,
            'params' => ['urls' => ['failed-site.com']],
            'created_at' => now(),
        ]);

        $response = get(route('rooms.blocked-websites.index', $this->room->id));

        $response->assertStatus(200)
            ->assertJson([
                'data' => ['allowed-site.com'],
            ]);
    });
});
