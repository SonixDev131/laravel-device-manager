<?php

declare(strict_types=1);

use App\Services\RabbitMQ\Exchange;
use App\Services\RabbitMQ\RabbitMQConfigInterface;
use App\Services\RabbitMQ\RabbitMQService;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

beforeEach(function () {
    // Mock Log facade for all tests
    Log::shouldReceive('error')->byDefault();
    Log::shouldReceive('warning')->byDefault();
    Log::shouldReceive('info')->byDefault();
});

test('sends command to specific computer successfully', function () {
    // Arrange
    $channel = \Mockery::mock(AMQPChannel::class);
    $connection = \Mockery::mock(AMQPStreamConnection::class);
    $config = \Mockery::mock(RabbitMQConfigInterface::class);

    // Setup exchange mock data
    $config->shouldReceive('getExchanges')
        ->andReturn([
            new Exchange(
                name: 'unilab.commands',
                type: 'topic',
                durable: true,
                autoDelete: false
            ),
        ]);

    $config->shouldReceive('getCommandExchange')
        ->andReturn('unilab.commands');

    $config->shouldReceive('getCommandRoutingKey')
        ->andReturn('command.room_{room}.computer_{computer}');

    // Connection expectations
    $connection->shouldReceive('channel')
        ->once()
        ->andReturn($channel);

    $channel->shouldReceive('exchange_declare')
        ->once();

    $channel->shouldReceive('basic_publish')
        ->once()
        ->withArgs(function (AMQPMessage $message, string $exchange, string $routingKey) {
            return
                $exchange === 'unilab.commands' &&
                $routingKey === 'command.room_123.computer_456' &&
                json_decode($message->getBody(), true) === 'SHUTDOWN';
        });

    // Act
    $service = new RabbitMQService($connection, $config);
    $result = $service->sendCommandToComputer('456', '123', 'SHUTDOWN');

    // Assert
    expect($result)->toBeTrue();
});

test('sends command to room successfully', function () {
    // Arrange
    $channel = \Mockery::mock(AMQPChannel::class);
    $connection = \Mockery::mock(AMQPStreamConnection::class);
    $config = \Mockery::mock(RabbitMQConfigInterface::class);

    // Setup exchange mock data
    $config->shouldReceive('getExchanges')
        ->andReturn([
            new Exchange(
                name: 'unilab.commands',
                type: 'topic',
                durable: true,
                autoDelete: false
            ),
        ]);

    $config->shouldReceive('getCommandExchange')
        ->andReturn('unilab.commands');

    $config->shouldReceive('getRoomBroadcastRoutingKey')
        ->andReturn('command.room_{room}.broadcast');

    // Connection expectations
    $connection->shouldReceive('channel')
        ->once()
        ->andReturn($channel);

    $channel->shouldReceive('exchange_declare')
        ->once();

    $channel->shouldReceive('basic_publish')
        ->once()
        ->withArgs(function (AMQPMessage $message, string $exchange, string $routingKey) {
            $messageData = json_decode($message->getBody(), true);

            return
                $exchange === 'unilab.commands' &&
                $routingKey === 'command.room_123.broadcast' &&
                is_array($messageData);
        });

    // Act
    $service = new RabbitMQService($connection, $config);
    $result = $service->sendCommandToRoom('123', ['action' => 'SHUTDOWN']);

    // Assert
    expect($result)->toBeTrue();
});

test('logs error when publishing fails', function () {
    // Arrange
    $exception = new \Exception('Failed to publish');
    $channel = \Mockery::mock(AMQPChannel::class);
    $connection = \Mockery::mock(AMQPStreamConnection::class);
    $config = \Mockery::mock(RabbitMQConfigInterface::class);

    $config->shouldReceive('getExchanges')
        ->andReturn([]);

    $config->shouldReceive('getCommandExchange')
        ->andReturn('unilab.commands');

    $config->shouldReceive('getCommandRoutingKey')
        ->andReturn('command.room_{room}.computer_{computer}');

    $connection->shouldReceive('channel')
        ->once()
        ->andReturn($channel);

    $channel->shouldReceive('exchange_declare')
        ->never();

    $channel->shouldReceive('basic_publish')
        ->once()
        ->andThrow($exception);

    Log::shouldReceive('error')
        ->once()
        ->withArgs(function (string $message, array $context) {
            return str_contains($message, 'Failed to publish message') &&
                   $context['message'] === 'Failed to publish';
        });

    // Act
    $service = new RabbitMQService($connection, $config);
    $result = $service->sendCommandToComputer('456', '123', 'SHUTDOWN');

    // Assert
    expect($result)->toBeFalse();
});

test('consumes status update message successfully', function () {
    // Arrange
    $channel = \Mockery::mock(AMQPChannel::class);
    $connection = \Mockery::mock(AMQPStreamConnection::class);
    $config = \Mockery::mock(RabbitMQConfigInterface::class);

    $statusMessage = json_encode([
        'computer_id' => '456',
        'room_id' => '123',
        'status' => 'active',
        'timestamp' => time(),
    ]);

    $amqpMessage = \Mockery::mock(AMQPMessage::class);
    $amqpMessage->shouldReceive('getBody')
        ->once()
        ->andReturn($statusMessage);

    $amqpMessage->shouldReceive('ack')
        ->once();

    // Setup exchange and queue mock data
    $config->shouldReceive('getExchanges')
        ->andReturn([
            new Exchange(
                name: 'unilab.status',
                type: 'topic',
                durable: true,
                autoDelete: false
            ),
        ]);

    $config->shouldReceive('getStatusExchange')
        ->andReturn('unilab.status');

    $config->shouldReceive('getStatusQueue')
        ->andReturn('status_updates');

    $config->shouldReceive('getStatusRoutingKey')
        ->andReturn('status.#');

    // Connection expectations
    $connection->shouldReceive('channel')
        ->once()
        ->andReturn($channel);

    $channel->shouldReceive('exchange_declare')
        ->once();

    $channel->shouldReceive('queue_declare')
        ->once()
        ->with('status_updates', false, true, false, false)
        ->andReturn(['status_updates', 0]);

    $channel->shouldReceive('queue_bind')
        ->once()
        ->with('status_updates', 'unilab.status', 'status.#');

    // Store the callback when basic_consume is called
    $storedCallback = null;
    $channel->shouldReceive('basic_consume')
        ->once()
        ->with('status_updates', '', false, false, false, false, \Mockery::on(function ($callbackFn) use (&$storedCallback) {
            $storedCallback = $callbackFn;

            return true;
        }));

    // Wait will invoke the callback once, then terminate
    $channel->shouldReceive('wait')
        ->once()
        ->andReturnUsing(function () use (&$storedCallback, $amqpMessage) {
            if (is_callable($storedCallback)) {
                $storedCallback($amqpMessage);
            }

            return null;
        });

    // Mock the log facade
    Log::shouldReceive('info')
        ->once()
        ->withArgs(function (string $message) {
            return str_contains($message, 'Received status update');
        });

    // Mock the Computer model to avoid database interactions
    $computerMock = \Mockery::mock('overload:App\Models\Computer');
    $computerMock->shouldReceive('firstWhere')
        ->with('uuid', '456')
        ->andReturn(null);

    Log::shouldReceive('warning')
        ->with(\Mockery::type('string'))
        ->andReturn(null);

    // Act
    $service = new RabbitMQService($connection, $config);
    $result = $service->consumeStatusUpdates(1); // Consume just 1 message

    // Assert
    expect($result)->toBeTrue();
});

test('publishes command to specific queue successfully', function () {
    // Arrange
    $channel = \Mockery::mock(AMQPChannel::class);
    $connection = \Mockery::mock(AMQPStreamConnection::class);
    $config = \Mockery::mock(RabbitMQConfigInterface::class);

    $queueName = 'commands.computer.456';
    $commandData = [
        'action' => 'REBOOT',
        'parameters' => ['delay' => 5],
    ];

    // Setup exchange mock data
    $config->shouldReceive('getExchanges')
        ->andReturn([
            new Exchange(
                name: 'unilab.commands',
                type: 'direct',
                durable: true,
                autoDelete: false
            ),
        ]);

    $config->shouldReceive('getCommandExchange')
        ->andReturn('unilab.commands');

    // Connection expectations
    $connection->shouldReceive('channel')
        ->once()
        ->andReturn($channel);

    $channel->shouldReceive('exchange_declare')
        ->once();

    $channel->shouldReceive('queue_declare')
        ->once()
        ->with($queueName, false, true, false, false)
        ->andReturn([$queueName, 0]);

    $channel->shouldReceive('basic_publish')
        ->once()
        ->withArgs(function (AMQPMessage $message, string $exchange, string $routingKey) use ($queueName, $commandData) {
            $messageData = json_decode($message->getBody(), true);

            return
                $exchange === 'unilab.commands' &&
                $routingKey === $queueName &&
                is_array($messageData) &&
                $messageData['action'] === $commandData['action'] &&
                $messageData['parameters']['delay'] === $commandData['parameters']['delay'];
        });

    // Act
    $service = new RabbitMQService($connection, $config);
    $result = $service->publishToQueue($queueName, $commandData);

    // Assert
    expect($result)->toBeTrue();
});
