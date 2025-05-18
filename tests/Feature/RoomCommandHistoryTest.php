<?php

declare(strict_types=1);

use App\Models\Command;
use App\Models\Computer;
use App\Models\Room;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->room = Room::factory()->create();
    $this->computer = Computer::factory()->create(['room_id' => $this->room->id]);
});

it('returns command history for a room', function () {
    // Create test commands
    $roomCommand = Command::factory()->create([
        'room_id' => $this->room->id,
        'computer_id' => null,
        'type' => 'LOCK',
        'status' => 'completed',
        'is_group_command' => true,
    ]);

    $computerCommand = Command::factory()->create([
        'room_id' => null,
        'computer_id' => $this->computer->id,
        'type' => 'RESTART',
        'status' => 'pending',
        'is_group_command' => false,
    ]);

    // Test the endpoint
    actingAs($this->user)
        ->get(route('rooms.commands.index', $this->room))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'status',
                    'created_at',
                    'completed_at',
                    'target',
                    'is_group_command',
                ],
            ],
        ])
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $computerCommand->id)
        ->assertJsonPath('data.1.id', $roomCommand->id);
});

it('returns empty array when no commands exist', function () {
    actingAs($this->user)
        ->get(route('rooms.commands.index', $this->room))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('requires authentication', function () {
    get(route('rooms.commands.index', $this->room))
        ->assertRedirect(route('login'));
});
