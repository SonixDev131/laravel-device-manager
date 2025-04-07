<?php

declare(strict_types=1);

use App\Models\Computer;
use App\Models\Room;
use Tests\TestCase;

uses(TestCase::class);

test('to array', function () {
    $computer = Computer::factory()->create()->fresh();

    expect(array_keys($computer->toArray()))->toBe([
        'id',
        'room_id',
        'name',
        'mac_address',
        'ip_address',
        'pos_row',
        'pos_col',
        'active',
        'created_at',
        'updated_at',
    ]);
});

test('relations', function () {
    $computer = Computer::factory()->create()->fresh();

    expect($computer->room)->toBeInstanceOf(Room::class);
});
