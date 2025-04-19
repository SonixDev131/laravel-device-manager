<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\ProcessComputerHeartbeatAction;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;

final class ConsumeComputerStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unilab:consume-computer-status
                            {--timeout=0 : Time in seconds before the command times out (0 for no timeout)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consumes computer status messages from RabbitMQ';

    /**
     * Flag to indicate if the consumer should keep running
     */
    private bool $shouldRun = true;

    /**
     * Execute the console command.
     */
    public function handle(ProcessComputerHeartbeatAction $action): int
    {
        $timeout = (int) $this->option('timeout');
        $startTime = time();

        // Register signal handlers for graceful shutdown
        if (extension_loaded('pcntl')) {
            pcntl_signal(SIGINT, fn () => $this->shouldRun = false);
            pcntl_signal(SIGTERM, fn () => $this->shouldRun = false);
        }

        $this->info('Starting computer status consumer...');

        try {
            $connection = $this->createConnection();
            $channel = $connection->channel();

            // Set up exchange and queue
            $exchangeName = config('rabbitmq.exchanges.status.name');
            $queueName = config('rabbitmq.queues.computer_status');
            $routingKey = 'computer.status';

            $channel->exchange_declare(
                exchange: $exchangeName,
                type: 'topic',
                passive: false,
                durable: true,
                auto_delete: false
            );

            $channel->queue_declare(
                queue: $queueName,
                passive: false,
                durable: true,
                exclusive: false,
                auto_delete: false
            );

            $channel->queue_bind(
                queue: $queueName,
                exchange: $exchangeName,
                routing_key: $routingKey
            );

            $this->info('Successfully connected to RabbitMQ');
            $this->info("Listening for messages on exchange: $exchangeName, queue: $queueName");

            // Set up the message callback
            $callback = function (AMQPMessage $message) use ($action): void {
                $this->processMessage($message, $action);
            };

            // Start consuming messages
            $channel->basic_consume(
                queue: $queueName,
                consumer_tag: '',
                no_local: false,
                no_ack: false,
                exclusive: false,
                nowait: false,
                callback: $callback
            );

            // Keep consuming messages until stopped or timeout
            while ($this->shouldRun && $channel->is_consuming()) {
                try {
                    $channel->wait(null, false, 1); // 1 second timeout on wait

                    if (extension_loaded('pcntl')) {
                        pcntl_signal_dispatch();
                    }

                    // Check for timeout
                    if ($timeout > 0 && (time() - $startTime) > $timeout) {
                        $this->info("Timeout of $timeout seconds reached. Shutting down...");
                        $this->shouldRun = false;
                    }
                } catch (AMQPTimeoutException) {
                    // This is normal - just a timeout on the wait
                    continue;
                }
            }

            // Clean shutdown
            $channel->close();
            $connection->close();
            $this->info('Consumer stopped gracefully');

            return self::SUCCESS;
        } catch (AMQPConnectionClosedException $e) {
            $this->error('Connection to RabbitMQ was closed: '.$e->getMessage());
            Log::error('RabbitMQ connection closed', ['exception' => $e]);

            return self::FAILURE;
        } catch (Exception $e) {
            $this->error('Error consuming messages: '.$e->getMessage());
            Log::error('Error in RabbitMQ consumer', ['exception' => $e]);

            return self::FAILURE;
        }
    }

    /**
     * Create a new connection to RabbitMQ
     */
    private function createConnection(): AMQPStreamConnection
    {
        return new AMQPStreamConnection(
            host: config('rabbitmq.connection.host'),
            port: config('rabbitmq.connection.port'),
            user: config('rabbitmq.connection.user'),
            password: config('rabbitmq.connection.password'),
            vhost: config('rabbitmq.connection.vhost')
        );
    }

    /**
     * Process an incoming message
     */
    private function processMessage(AMQPMessage $message, ProcessComputerHeartbeatAction $action): void
    {
        try {
            $body = $message->getBody();
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            $this->info('Received message: '.$body);

            $this->info('Received heartbeat from computer: '.($data['computer_id'] ?? 'unknown'));

            // Process the heartbeat data
            $result = $action->handle(
                computerId: $data['computer_id'] ?? '',
                roomId: $data['room_id'] ?? '',
                status: $data['status'] ?? 'unknown',
                metrics: $data['metrics'] ?? []
            );

            if ($result['success']) {
                $this->info('Processed heartbeat successfully');
                if ($result['status_changed'] ?? false) {
                    $this->info('Computer status was updated');
                }
            } else {
                $this->warn('Failed to process heartbeat: '.($result['message'] ?? 'Unknown error'));
            }

            // Acknowledge the message
            $message->ack();
        } catch (Exception $e) {
            $this->error('Error processing message: '.$e->getMessage());
            Log::error('Error processing RabbitMQ message', [
                'exception' => $e,
                'message' => $message->getBody(),
            ]);

            // Reject the message without requeue if it's malformed
            $message->reject(false);
        }
    }
}
