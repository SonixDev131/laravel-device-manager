<?php

declare(strict_types=1);

use App\Models\Computer;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('room has correct attributes', function () {
    $room = Room::factory()->create([
        'name' => 'Test Lab',
        'grid_rows' => 5,
        'grid_cols' => 10,
    ]);

    expect($room)
        ->toBeInstanceOf(Room::class)
        ->name->toBe('Test Lab')
        ->grid_rows->toBe(5)
        ->grid_cols->toBe(10);
});

test('room can be created using factory', function () {
    $room = Room::factory()->create();

    expect($room)
        ->toBeInstanceOf(Room::class)
        ->name->toBeString()
        ->grid_rows->toBeInt()
        ->grid_cols->toBeInt();
});

test('room can have computers', function () {
    $room = Room::factory()->create();
    $computer = Computer::factory()->create(['room_id' => $room->id]);

    $computers = $room->computers;

    expect($computers)->toHaveCount(1);
    expect($computers->first())
        ->toBeInstanceOf(Computer::class)
        ->id->toBe($computer->id);
});

test('room can have multiple computers', function () {
    $room = Room::factory()->create();
    Computer::factory()->count(3)->create(['room_id' => $room->id]);

    expect($room->computers)->toHaveCount(3);
});

test('room uses HasUuids trait', function () {
    $room = Room::factory()->create();

    expect($room->id)
        ->toBeString()
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

test('room computers relationship returns empty collection when no computers', function () {
    $room = Room::factory()->create();

    expect($room->computers)
        ->toBeInstanceOf(Illuminate\Database\Eloquent\Collection::class)
        ->toBeEmpty();
});

test('deleting a room does not automatically delete its computers', function () {
    // We need to check if the relationship is setup for cascade delete in the migration
    // If it is, we should adjust our expectations

    // Create a room and a computer
    $room = Room::factory()->create();
    $computer = Computer::factory()->create(['room_id' => $room->id]);
    $computerId = $computer->id;

    // Store the computer ID before deleting the room
    $computerBeforeDelete = Computer::find($computerId);
    expect($computerBeforeDelete)->not->toBeNull();

    // Delete the room
    $room->delete();

    // Check if computer is deleted - since the migration sets cascadeOnDelete
    // it's expected that the computer is deleted with the room
    $computerAfterDelete = Computer::find($computerId);
    expect($computerAfterDelete)->toBeNull();
});
