<?php

declare(strict_types=1);

use App\Models\Room;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;

test('generates python installation script for windows when requested', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('rooms.installation-script.generate'), [
            'os_type' => 'windows',
            'server_url' => 'https://example.com',
            'room_id' => $room->id,
            'auto_register' => true,
            'use_python' => true,
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['script'])
        ->assertJson(fn (AssertableJson $json) => $json->has('script')
            ->whereType('script', 'string')
        );

    $script = $response->json('script');
    expect($script)
        ->toContain('#!/usr/bin/env python3')
        ->toContain('SERVER_URL = "https://example.com/api"')
        ->toContain('AUTO_REGISTER = "True"');
});

test('generates powershell installation script for windows by default', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('rooms.installation-script.generate'), [
            'os_type' => 'windows',
            'server_url' => 'https://example.com',
            'room_id' => $room->id,
            'auto_register' => true,
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['script']);

    $script = $response->json('script');
    expect($script)
        ->toContain('$ErrorActionPreference = "Stop"')
        ->toContain('$apiUrl = "https://example.com/api"')
        ->toContain('$autoRegister = $true');
});

test('generates bash installation script for linux', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('rooms.installation-script.generate'), [
            'os_type' => 'linux',
            'server_url' => 'https://example.com',
            'auto_register' => true,
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['script']);

    $script = $response->json('script');
    expect($script)
        ->toContain('#!/bin/bash')
        ->toContain('API_URL="https://example.com/api"')
        ->toContain('AUTO_REGISTER=true');
});

test('creates installation token and links to room when room_id is provided', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();

    $this->actingAs($user)
        ->postJson(route('rooms.installation-script.generate'), [
            'os_type' => 'windows',
            'server_url' => 'https://example.com',
            'room_id' => $room->id,
            'auto_register' => true,
            'use_python' => true,
        ]);

    $this->assertDatabaseHas('installation_tokens', [
        'room_id' => $room->id,
    ]);
});
