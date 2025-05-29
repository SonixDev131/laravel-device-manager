<?php

declare(strict_types=1);

use App\Enums\CommandType;
use App\Models\Computer;
use App\Models\Room;
use App\Models\User;
use App\Services\RabbitMQService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\mock;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

describe('Room Command Controller', function () {
    beforeEach(function () {
        // Create a user, room, and computer for testing
        $this->user = User::factory()->create();
        $this->room = Room::factory()->create();
        $this->computer = Computer::factory()->create(['room_id' => $this->room->id]);

        // Mock RabbitMQ service
        $this->rabbitMqMock = mock(RabbitMQService::class);
        $this->rabbitMqMock->shouldReceive('isAvailable')->andReturn(true);
        $this->rabbitMqMock->shouldReceive('publishToAgent')->andReturn(true);
        $this->rabbitMqMock->shouldReceive('publishToRoom')->andReturn(true);

        $this->app->instance(RabbitMQService::class, $this->rabbitMqMock);
    });

    it('publishes block website command successfully', function () {
        actingAs($this->user);

        $response = post(route('rooms.commands.publish', $this->room->id), [
            'command_type' => CommandType::BLOCK_WEBSITE->value,
            'target_type' => 'single',
            'computer_id' => $this->computer->id,
            'payload' => [
                'urls' => ['facebook.com', 'twitter.com'],
            ],
        ]);

        // Assert response and database state
        $response->assertRedirect(route('rooms.show', $this->room->id));

        // Check command was created with correct data
        $this->assertDatabaseHas('commands', [
            'type' => CommandType::BLOCK_WEBSITE->value,
            'computer_id' => $this->computer->id,
            'status' => 'sent',
        ]);
    });

    it('validates required website URLs', function () {
        actingAs($this->user);

        $response = post(route('rooms.commands.publish', $this->room->id), [
            'command_type' => CommandType::BLOCK_WEBSITE->value,
            'target_type' => 'single',
            'computer_id' => $this->computer->id,
            'payload' => [],
        ]);

        $response->assertSessionHasErrors('payload.urls');
    });

    it('validates website URL format', function () {
        actingAs($this->user);

        $response = post(route('rooms.commands.publish', $this->room->id), [
            'command_type' => CommandType::BLOCK_WEBSITE->value,
            'target_type' => 'single',
            'computer_id' => $this->computer->id,
            'payload' => [
                'urls' => ['invalid', 'http://example.com'],
            ],
        ]);

        $response->assertSessionHasErrors('payload.urls.0');
        $response->assertSessionHasErrors('payload.urls.1');
    });
});
