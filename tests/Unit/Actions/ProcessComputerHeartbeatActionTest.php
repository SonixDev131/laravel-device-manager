<?php

declare(strict_types=1);

use App\Actions\ProcessComputerHeartbeatAction;
use App\Enums\ComputerStatus;
use App\Models\Computer;
use App\Models\Room;
use Carbon\Carbon;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    // Reset any Carbon mocking between tests
    Carbon::setTestNow();
});

test('it updates computer status from heartbeat', function () {
    // Create a computer
    $computer = Computer::factory()->create([
        'status' => ComputerStatus::OFFLINE->value,
    ]);

    // Process a heartbeat
    $action = new ProcessComputerHeartbeatAction;
    $result = $action->handle(
        $computer->id,
        $computer->room_id,
        'online',
        ['cpu_usage' => 30, 'memory_usage' => 45]
    );

    expect($result['success'])->toBeTrue();
    expect($result['status_changed'])->toBeTrue();

    // Reload the computer
    $computer->refresh();

    expect($computer->status)->toBe(ComputerStatus::ONLINE);
    expect($computer->system_metrics)->toBe(['cpu_usage' => 30, 'memory_usage' => 45]);
});

test('it auto-registers a new computer when receiving heartbeat from unknown computer', function () {
    // Create a room
    $room = Room::factory()->create();

    // Computer ID that doesn't exist yet
    $computerId = '01234567-89ab-cdef-0123-456789abcdef';

    // Process a heartbeat with detailed metrics
    $action = new ProcessComputerHeartbeatAction;
    $metrics = [
        'hostname' => 'TestPC123',
        'mac_address' => '00:11:22:33:44:55',
        'ip_address' => '192.168.1.100',
        'cpu_usage' => 20,
        'memory_usage' => 35,
        'disk_usage' => 55,
        'uptime' => 3600,
        'platform' => 'Windows',
        'platform_version' => '10.0.19044',
    ];

    $result = $action->handle(
        $computerId,
        $room->id,
        'online',
        $metrics
    );

    expect($result['success'])->toBeTrue();

    // Check that the computer was created
    $computer = Computer::query()->find($computerId);
    expect($computer)->not->toBeNull();
    expect($computer->name)->toBe('TestPC123');
    expect($computer->mac_address)->toBe('00:11:22:33:44:55');
    expect($computer->ip_address)->toBe('192.168.1.100');
    expect($computer->status)->toBe(ComputerStatus::ONLINE);

    // Verify that all metrics were stored
    expect($computer->system_metrics)->toBe($metrics);
});

test('it fails when receiving heartbeat for non-existent room', function () {
    // Non-existent IDs
    $computerId = '01234567-89ab-cdef-0123-456789abcdef';
    $roomId = '98765432-10fe-dcba-9876-543210fedcba';

    // Process a heartbeat
    $action = new ProcessComputerHeartbeatAction;
    $result = $action->handle(
        $computerId,
        $roomId,
        'online',
        ['hostname' => 'TestPC123']
    );

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe('Room not found');

    // Check that no computer was created
    $computer = Computer::query()->find($computerId);
    expect($computer)->toBeNull();
});

test('it preserves maintenance status but updates last_seen_at', function () {
    // Set up a fixed time for testing
    $timeNow = now();
    Carbon::setTestNow($timeNow);

    // Create a computer in maintenance mode with old last_seen_at
    $computer = Computer::factory()->create([
        'status' => ComputerStatus::MAINTENANCE->value,
        'last_seen_at' => $timeNow->copy()->subMinutes(30),
    ]);

    $oldTimestamp = $computer->last_seen_at->timestamp;

    // Process a heartbeat
    $action = new ProcessComputerHeartbeatAction;
    $metrics = ['cpu_usage' => 25, 'memory_usage' => 65];

    $result = $action->handle(
        $computer->id,
        $computer->room_id,
        'online',
        $metrics
    );

    expect($result['success'])->toBeTrue();
    expect($result['status_changed'])->toBeFalse();

    // Reload the computer
    $computer->refresh();

    // Status should still be maintenance
    expect($computer->status)->toBe(ComputerStatus::MAINTENANCE);

    // But last_seen_at should be updated
    expect($computer->last_seen_at->timestamp)->toBe($timeNow->timestamp);

    // And metrics should be stored
    expect($computer->system_metrics)->toBe($metrics);
});
