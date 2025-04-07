<?php

declare(strict_types=1);

use App\Models\InstallationToken;
use App\Models\Room;
use App\Services\Installation\InstallationTokenService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('creates token without room id', function () {
    $service = new InstallationTokenService;

    $token = $service->createToken();

    expect($token)->toBeString()->toHaveLength(64);
    expect(InstallationToken::query()->where('token', $token)->exists())->toBeFalse();
});

test('creates token with room id and stores in database', function () {
    $room = Room::factory()->create();
    $service = new InstallationTokenService;

    $token = $service->createToken($room->id);

    expect($token)->toBeString()->toHaveLength(64);

    $storedToken = InstallationToken::query()->where('token', $token)->first();
    expect($storedToken)->not->toBeNull();
    expect($storedToken->room_id)->toBe($room->id);
    expect($storedToken->expires_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('throws exception when room does not exist', function () {
    $service = new InstallationTokenService;

    expect(fn () => $service->createToken('non-existent-id'))
        ->toThrow(ModelNotFoundException::class);
});
