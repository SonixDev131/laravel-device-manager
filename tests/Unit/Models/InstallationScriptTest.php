<?php

use App\Models\InstallationToken;
use App\Models\Room;
use App\Models\User;

test('can generate windows installation script', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('rooms.installation-script.generate'), [
            'os_type' => 'windows',
            'server_url' => 'https://example.com',
            'room_id' => $room->id,
            'auto_register' => true,
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['script']);

    $script = $response->json('script');

    // Check script contains expected content
    expect($script)->toContain('Installing Computer Management Agent')
        ->toContain('https://example.com/api')
        ->toContain($room->id);

    // Check that an installation token was created
    expect(InstallationToken::query()->where('room_id', $room->id)->exists())->toBeTrue();
});

test('can generate linux installation script', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('rooms.installation-script.generate'), [
            'os_type' => 'linux',
            'server_url' => 'https://example.com',
            'room_id' => $room->id,
            'auto_register' => true,
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['script']);

    $script = $response->json('script');

    // Check script contains expected content
    expect($script)->toContain('#!/bin/bash')
        ->toContain('Installing Computer Management Agent')
        ->toContain('https://example.com/api')
        ->toContain($room->id);
});

test('can generate mac installation script', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('rooms.installation-script.generate'), [
            'os_type' => 'mac',
            'server_url' => 'https://example.com',
            'room_id' => $room->id,
            'auto_register' => true,
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['script']);

    $script = $response->json('script');

    // Check script contains expected content
    expect($script)->toContain('#!/bin/bash')
        ->toContain('Installing Computer Management Agent')
        ->toContain('https://example.com/api')
        ->toContain('/Applications/ComputerAgent') // Mac-specific path
        ->toContain($room->id);
});

test('validates os_type input', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('rooms.installation-script.generate'), [
            'os_type' => 'invalid',
            'server_url' => 'https://example.com',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['os_type']);
});
