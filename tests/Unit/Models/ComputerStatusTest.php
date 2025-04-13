<?php

declare(strict_types=1);

use App\Enums\ComputerStatus;
use App\Models\Computer;
use Carbon\Carbon;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    // Reset any Carbon mocking between tests
    Carbon::setTestNow();
});

test('computer status can be set and retrieved', function () {
    $computer = Computer::factory()->create([
        'status' => ComputerStatus::ONLINE->value,
    ]);

    expect($computer->status)->toBe(ComputerStatus::ONLINE);

    $computer->status = ComputerStatus::OFFLINE;
    $computer->save();

    $refreshedComputer = Computer::find($computer->id);
    expect($refreshedComputer->status)->toBe(ComputerStatus::OFFLINE);
});

test('computer with null last_seen_at is considered timed out', function () {
    $computer = Computer::factory()->create([
        'status' => ComputerStatus::ONLINE->value,
        'last_seen_at' => null,
    ]);

    expect($computer->hasTimedOut())->toBeTrue();
});

test('computer with recent last_seen_at is not considered timed out', function () {
    $timeNow = now();
    Carbon::setTestNow($timeNow);

    $computer = Computer::factory()->create([
        'status' => ComputerStatus::ONLINE->value,
        'last_seen_at' => $timeNow->subMinute(), // 1 minute ago
    ]);

    expect($computer->hasTimedOut(3))->toBeFalse(); // 3 minute timeout
});

test('computer with old last_seen_at is considered timed out', function () {
    $timeNow = now();
    Carbon::setTestNow($timeNow);

    $computer = Computer::factory()->create([
        'status' => ComputerStatus::ONLINE->value,
        'last_seen_at' => $timeNow->subMinutes(5), // 5 minutes ago
    ]);

    expect($computer->hasTimedOut(3))->toBeTrue(); // 3 minute timeout
});

test('updateStatusBasedOnTimeout method changes online to offline when timed out', function () {
    $timeNow = now();
    Carbon::setTestNow($timeNow);

    $computer = Computer::factory()->create([
        'status' => ComputerStatus::ONLINE->value,
        'last_seen_at' => $timeNow->subMinutes(5), // 5 minutes ago
    ]);

    $changed = $computer->updateStatusBasedOnTimeout(3); // 3 minute timeout

    expect($changed)->toBeTrue();
    expect($computer->status)->toBe(ComputerStatus::OFFLINE);
});

test('updateStatusBasedOnTimeout method changes offline to online when not timed out', function () {
    $timeNow = now();
    Carbon::setTestNow($timeNow);

    $computer = Computer::factory()->create([
        'status' => ComputerStatus::OFFLINE->value,
        'last_seen_at' => $timeNow->subMinute(), // 1 minute ago
    ]);

    $changed = $computer->updateStatusBasedOnTimeout(3); // 3 minute timeout

    expect($changed)->toBeTrue();
    expect($computer->status)->toBe(ComputerStatus::ONLINE);
});

test('updateStatusBasedOnTimeout method does not modify maintenance status', function () {
    $timeNow = now();
    Carbon::setTestNow($timeNow);

    $computer = Computer::factory()->create([
        'status' => ComputerStatus::MAINTENANCE->value,
        'last_seen_at' => $timeNow->subMinutes(5), // 5 minutes ago
    ]);

    $changed = $computer->updateStatusBasedOnTimeout(3); // 3 minute timeout

    expect($changed)->toBeFalse();
    expect($computer->status)->toBe(ComputerStatus::MAINTENANCE);
});

test('updateFromHeartbeat method updates status and last_seen_at timestamp', function () {
    $timeNow = now();
    Carbon::setTestNow($timeNow);

    $computer = Computer::factory()->create([
        'status' => ComputerStatus::OFFLINE->value,
        'last_seen_at' => $timeNow->subMinutes(10), // 10 minutes ago
    ]);

    $changed = $computer->updateFromHeartbeat('online');

    expect($changed)->toBeTrue();
    expect($computer->status)->toBe(ComputerStatus::ONLINE);
    expect($computer->last_seen_at->timestamp)->toBe($timeNow->timestamp);
});

test('updateFromHeartbeat method handles shutting_down status', function () {
    $computer = Computer::factory()->create([
        'status' => ComputerStatus::ONLINE->value,
    ]);

    $changed = $computer->updateFromHeartbeat('shutting_down');

    expect($changed)->toBeTrue();
    expect($computer->status)->toBe(ComputerStatus::SHUTTING_DOWN);
});

test('updateFromHeartbeat method does not change maintenance status', function () {
    $timeNow = now();
    Carbon::setTestNow($timeNow);

    $computer = Computer::factory()->create([
        'status' => ComputerStatus::MAINTENANCE->value,
        'last_seen_at' => $timeNow->subMinutes(5),
    ]);

    $oldTimestamp = $computer->last_seen_at->timestamp;
    $changed = $computer->updateFromHeartbeat('online');

    expect($changed)->toBeFalse();
    expect($computer->status)->toBe(ComputerStatus::MAINTENANCE);
    expect($computer->last_seen_at->timestamp)->not->toBe($oldTimestamp);
});
