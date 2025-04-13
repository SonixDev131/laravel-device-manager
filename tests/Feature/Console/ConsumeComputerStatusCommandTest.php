<?php

declare(strict_types=1);

use App\Actions\ProcessComputerHeartbeatAction;
use App\Console\Commands\ConsumeComputerStatusCommand;
use App\Enums\ComputerStatus;
use App\Models\Computer;
use App\Models\Room;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Tests\TestCase;

uses(TestCase::class);

test('command processes valid heartbeat messages from RabbitMQ', function () {
    // Skip test if no RabbitMQ for CI environment
    if (getenv('CI') === 'true') {
        $this->markTestSkipped('Skipping RabbitMQ test in CI environment');
    }

    // Create test data
    $room = Room::factory()->create();
    $computer = Computer::factory()->create([
        'room_id' => $room->id,
        'status' => ComputerStatus::OFFLINE->value,
    ]);

    // Setup mock action
    $mockAction = $this->mock(ProcessComputerHeartbeatAction::class);
    $mockAction->shouldReceive('handle')
        ->once()
        ->with(
            $computer->id,
            $room->id,
            'online',
            [
                'cpu_usage' => 45,
                'memory_usage' => 60,
            ]
        )
        ->andReturn([
            'success' => true,
            'status_changed' => true,
            'computer_id' => $computer->id,
        ]);

    // Mock RabbitMQ connection and channel
    $mockChannel = $this->mock(AMQPChannel::class);
    $mockConnection = $this->mock(AMQPStreamConnection::class);
    $mockConnection->shouldReceive('channel')->andReturn($mockChannel);

    // Configure mocks for RabbitMQ operations
    $mockChannel->shouldReceive('exchange_declare')->once();
    $mockChannel->shouldReceive('queue_declare')->once();
    $mockChannel->shouldReceive('queue_bind')->once();
    $mockChannel->shouldReceive('basic_consume')
        ->once()
        ->andReturnUsing(function ($queue, $consumer_tag, $no_local, $no_ack, $exclusive, $nowait, $callback) use ($computer, $room) {
            // Create a test message
            $messageBody = json_encode([
                'computer_id' => $computer->id,
                'room_id' => $room->id,
                'status' => 'online',
                'timestamp' => time(),
                'metrics' => [
                    'cpu_usage' => 45,
                    'memory_usage' => 60,
                ],
            ]);

            $message = new AMQPMessage($messageBody);
            $message->shouldReceive('ack')->once();
            $message->shouldReceive('getBody')->andReturn($messageBody);

            // Call the callback with our test message
            $callback($message);
        });

    // Make channel immediately stop consuming after processing
    $mockChannel->shouldReceive('is_consuming')
        ->once()
        ->andReturn(true)
        ->andReturn(false);

    $mockChannel->shouldReceive('wait')->once();
    $mockChannel->shouldReceive('close')->once();
    $mockConnection->shouldReceive('close')->once();

    // Create a testable instance of our command with the mocked connection
    $command = new class($mockConnection) extends ConsumeComputerStatusCommand
    {
        private AMQPStreamConnection $testConnection;

        public function __construct(AMQPStreamConnection $connection)
        {
            parent::__construct();
            $this->testConnection = $connection;
        }

        protected function createConnection(): AMQPStreamConnection
        {
            return $this->testConnection;
        }
    };

    // Execute the command
    $this->artisan(get_class($command))
        ->expectsOutput('Starting computer status consumer...')
        ->expectsOutput('Successfully connected to RabbitMQ')
        ->expectsOutput('Received heartbeat from computer: '.$computer->id)
        ->expectsOutput('Processed heartbeat successfully')
        ->expectsOutput('Computer status was updated')
        ->assertSuccessful();
});

test('command handles malformed messages gracefully', function () {
    // Skip test if no RabbitMQ for CI environment
    if (getenv('CI') === 'true') {
        $this->markTestSkipped('Skipping RabbitMQ test in CI environment');
    }

    // Setup mock action
    $mockAction = $this->mock(ProcessComputerHeartbeatAction::class);
    $mockAction->shouldNotReceive('handle');

    // Mock RabbitMQ connection and channel
    $mockChannel = $this->mock(AMQPChannel::class);
    $mockConnection = $this->mock(AMQPStreamConnection::class);
    $mockConnection->shouldReceive('channel')->andReturn($mockChannel);

    // Configure mocks for RabbitMQ operations
    $mockChannel->shouldReceive('exchange_declare')->once();
    $mockChannel->shouldReceive('queue_declare')->once();
    $mockChannel->shouldReceive('queue_bind')->once();
    $mockChannel->shouldReceive('basic_consume')
        ->once()
        ->andReturnUsing(function ($queue, $consumer_tag, $no_local, $no_ack, $exclusive, $nowait, $callback) {
            // Create a malformed message
            $messageBody = '{invalid json:}';

            $message = new AMQPMessage($messageBody);
            $message->shouldReceive('reject')->once()->with(false);
            $message->shouldReceive('getBody')->andReturn($messageBody);

            // Call the callback with our malformed message
            $callback($message);
        });

    // Make channel immediately stop consuming after processing
    $mockChannel->shouldReceive('is_consuming')
        ->once()
        ->andReturn(true)
        ->andReturn(false);

    $mockChannel->shouldReceive('wait')->once();
    $mockChannel->shouldReceive('close')->once();
    $mockConnection->shouldReceive('close')->once();

    // Create a testable instance of our command with the mocked connection
    $command = new class($mockConnection) extends ConsumeComputerStatusCommand
    {
        private AMQPStreamConnection $testConnection;

        public function __construct(AMQPStreamConnection $connection)
        {
            parent::__construct();
            $this->testConnection = $connection;
        }

        protected function createConnection(): AMQPStreamConnection
        {
            return $this->testConnection;
        }
    };

    // Execute the command
    $this->artisan(get_class($command))
        ->expectsOutput('Starting computer status consumer...')
        ->expectsOutput('Successfully connected to RabbitMQ')
        ->assertSuccessful();
});
