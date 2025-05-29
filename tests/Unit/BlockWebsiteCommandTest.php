<?php

declare(strict_types=1);

use App\Actions\PublishCommandAction;
use App\Enums\CommandStatus;
use App\Enums\CommandType;
use App\Models\Command;
use App\Models\Computer;
use App\Models\Room;
use App\Services\RabbitMQService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\mock;

uses(RefreshDatabase::class);

describe('BlockWebsite command', function () {
    it('creates a command with proper URL parameters', function () {
        // Mock the RabbitMQ service to avoid actual message publishing
        $rabbitMqMock = mock(RabbitMQService::class);
        $rabbitMqMock->shouldReceive('isAvailable')->andReturn(true);
        $rabbitMqMock->shouldReceive('publishToAgent')->andReturn(true);

        $room = Room::factory()->create();
        $computer = Computer::factory()->create(['room_id' => $room->id]);

        $action = new PublishCommandAction($rabbitMqMock);

        // Define website URLs to block
        $websiteUrls = ['facebook.com', 'twitter.com'];

        // Execute the action
        $result = $action->handle($room, [
            'command_type' => CommandType::BLOCK_WEBSITE->value,
            'target_type' => 'single',
            'computer_id' => $computer->id,
            'payload' => [
                'urls' => $websiteUrls,
            ],
        ]);

        // Assert the command was created successfully
        expect($result)->toBeTrue();

        // Get the created command
        $command = Command::latest()->first();

        // Assert command properties
        expect($command)->not->toBeNull()
            ->and($command->type)->toBe(CommandType::BLOCK_WEBSITE->value)
            ->and($command->status)->toBe(CommandStatus::SENT->value)
            ->and($command->computer_id)->toBe($computer->id)
            ->and($command->params)->toBe(['urls' => $websiteUrls]);
    });

    it('validates website URLs properly', function () {
        // Test validation for different URL formats
        $validUrls = [
            'example.com',
            'sub.domain.example.com',
            'example.co.uk',
            'example-site.com',
        ];

        $invalidUrls = [
            'invalid',
            'http://example.com', // Should not have protocol
            'example',
            '.com',
            'example com',
        ];

        // This test assumes the validation happens in the request class
        // We can test it by directly using the validator with our rules

        $rules = [
            'payload.urls.*' => ['string', 'regex:/^([a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+)(\/[a-zA-Z0-9-._~:\/?#[\]@!$&\'()*+,;=]*)?$/'],
        ];

        foreach ($validUrls as $url) {
            $validator = validator(['payload' => ['urls' => [$url]]], $rules);
            expect($validator->fails())->toBeFalse();
        }

        foreach ($invalidUrls as $url) {
            $validator = validator(['payload' => ['urls' => [$url]]], $rules);
            expect($validator->fails())->toBeTrue();
        }
    });
});
