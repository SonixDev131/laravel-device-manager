<?php

declare(strict_types=1);

use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Log;
use Mockery;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Tests\TestCase;

uses(TestCase::class);

test('can publish command to specific queue with correct routing key', function () {
    // Arrange
    $channel = Mockery::mock(AMQPChannel::class);
    $connection = Mockery::mock(AMQPStreamConnection::class);

    $connection->shouldReceive('channel')->once()->andReturn($channel);
    $channel->shouldReceive('exchange_declare')->times(2); // For both exchanges
    $channel->shouldReceive('basic_publish')
        ->once()
        ->withArgs(function (AMQPMessage $message, string $exchange, string $routingKey) {
            return $exchange === 'unilab.commands'
                && $routingKey === 'command.room_123.computer_456'
                && json_decode($message->getBody(), true) === 'SHUTDOWN';
        });

    app()->instance(AMQPStreamConnection::class, $connection);

    // Act & Assert
    expect((new RabbitMQService())->sendCommandToComputer('456', '123', 'SHUTDOWN'))->toBeTrue();
});

test('can consume status update message and log it correctly', function () {
    // Arrange
    $channel = Mockery::mock(AMQPChannel::class);
    $connection = Mockery::mock(AMQPStreamConnection::class);

    $connection->shouldReceive('channel')->once()->andReturn($channel);
    $channel->shouldReceive('exchange_declare')->times(2);

    $messageBody = json_encode([
        'command_id' => '789',
        'status' => 'completed',
        'result' => 'success',
    ]);

    $message = new AMQPMessage($messageBody);

    Log::shouldReceive('info')
        ->once()
        ->with('Received status update', ['message' => json_decode($messageBody, true)]);

    app()->instance(AMQPStreamConnection::class, $connection);
    $service = app(RabbitMQService::class);

    // Act & Assert - this will fail as the consume method doesn't exist yet
    expect(fn () => $service->consumeStatusUpdate($message))->toThrow(\Exception::class);
});

test('logs messages correctly when rabbitmq operations fail', function () {
    // Arrange
    $connection = Mockery::mock(AMQPStreamConnection::class);
    $connection->shouldReceive('channel')->andThrow(new \Exception('Connection failed'));

    Log::shouldReceive('error')
        ->once()
        ->with('RabbitMQ connection error: Connection failed');

    app()->instance(AMQPStreamConnection::class, $connection);

    // Act & Assert - constructor should handle the error gracefully
    $service = app(RabbitMQService::class);
    expect($service)->toBeInstanceOf(RabbitMQService::class);
});
