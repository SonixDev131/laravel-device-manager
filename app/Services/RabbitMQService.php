<?php

declare(strict_types=1);

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Exception\AMQPIOException;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;
use Throwable;

/**
 * Service for interacting with RabbitMQ message broker
 *
 * Implements three messaging patterns:
 * - Direct commands to specific agents (via MAC addresses)
 * - Room-based commands (via direct exchange with room routing keys)
 * - System-wide broadcasts (via fanout exchange)
 */
final class RabbitMQService
{
    /**
     * Exchange name for direct commands
     */
    private const CMD_DIRECT_EXCHANGE = 'cmd.direct';

    /**
     * Exchange name for broadcast messages
     */
    private const BROADCAST_FANOUT_EXCHANGE = 'broadcast.fanout';

    /**
     * Maximum number of connection retry attempts
     */
    private const MAX_CONNECTION_ATTEMPTS = 3;

    /**
     * Process-specific connections
     *
     * @var array<string, AMQPStreamConnection>
     */
    private array $connections = [];

    /**
     * Thread-specific channels
     *
     * @var array<string, array<string, AMQPChannel>>
     */
    private array $channels = [];

    /**
     * Indicates whether RabbitMQ is available
     */
    private bool $isAvailable = true;

    /**
     * Number of connection retry attempts
     */
    private int $connectionAttempts = 0;

    /**
     * Ensure connections are closed when the service is destroyed
     */
    public function __destruct()
    {
        $this->closeAll();
    }

    /**
     * Publish a command directly to a specific agent by MAC address (default exchange)
     *
     * @param  string  $macAddress  MAC address of the target computer
     * @param  array<string, mixed>  $payload  Command data to send
     * @param  string  $threadId  Thread identifier for connection pooling
     * @return bool Whether the message was published successfully
     */
    public function publishToAgent(string $macAddress, array $payload, string $threadId = 'default'): bool
    {
        // Normalize MAC address format (in case it comes with different separators)
        $macAddress = str_replace([':', '-', '.'], '', $macAddress);
        $macAddress = strtoupper($macAddress);

        // Format MAC with dashes for queue name
        $queueName = implode('-', str_split($macAddress, 2));

        // Create message payload
        $message = json_encode($payload);

        if (! $message) {
            Log::error('Failed to encode message payload', ['payload' => $payload]);

            return false;
        }

        try {
            $channel = $this->getChannel('publisher', $threadId);

            // Ensure queue exists (durable=true)
            $channel->queue_declare($queueName, false, true, false, false);

            // Publish directly to the queue (empty exchange = default)
            $result = $this->doPublish(
                channel: $channel,
                message: $message,
                exchange: '', // Default exchange
                routingKey: $queueName, // Queue name as routing key for default exchange
            );

            Log::info('Command published directly to agent', [
                'mac_address' => $macAddress,
                'command_type' => $payload['type'] ?? 'unknown',
            ]);

            return $result;
        } catch (Throwable $e) {
            Log::error('Failed to publish command directly to agent', [
                'mac_address' => $macAddress,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Publish a command to all computers in a room
     *
     * @param  string  $roomId  Room identifier to target
     * @param  array<string, mixed>  $payload  Command data to send
     * @param  string  $threadId  Thread identifier for connection pooling
     * @return bool Whether the message was published successfully
     */
    public function publishToRoom(string $roomId, array $payload, string $threadId = 'default'): bool
    {
        // Create routing key for the room
        $routingKey = "room.{$roomId}";

        // Create message payload
        $message = json_encode($payload);

        if (! $message) {
            Log::error('Failed to encode message payload', ['payload' => $payload]);

            return false;
        }

        try {
            $channel = $this->getChannel('publisher', $threadId);

            // Declare the direct exchange for commands
            $channel->exchange_declare(
                self::CMD_DIRECT_EXCHANGE,
                'direct',
                false, // passive
                true,  // durable
                false  // auto_delete
            );

            // Publish to the exchange with room routing key
            $result = $this->doPublish(
                channel: $channel,
                message: $message,
                exchange: self::CMD_DIRECT_EXCHANGE,
                routingKey: $routingKey,
            );

            Log::info('Command published to room', [
                'room_id' => $roomId,
                'command_type' => $payload['type'] ?? 'unknown',
            ]);

            return $result;
        } catch (Throwable $e) {
            Log::error('Failed to publish command to room', [
                'room_id' => $roomId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Publish a broadcast message to all agents in the system
     *
     * @param  array<string, mixed>  $payload  Command data to send
     * @param  string  $threadId  Thread identifier for connection pooling
     * @return bool Whether the message was published successfully
     */
    public function publishBroadcast(array $payload, string $threadId = 'default'): bool
    {
        // Create message payload
        $message = json_encode($payload);

        if (! $message) {
            Log::error('Failed to encode message payload', ['payload' => $payload]);

            return false;
        }

        try {
            $channel = $this->getChannel('publisher', $threadId);

            // Declare the fanout exchange for broadcasts
            $channel->exchange_declare(
                self::BROADCAST_FANOUT_EXCHANGE,
                'fanout',
                false, // passive
                true,  // durable
                false  // auto_delete
            );

            // Publish to the fanout exchange (routing key is ignored)
            $result = $this->doPublish(
                channel: $channel,
                message: $message,
                exchange: self::BROADCAST_FANOUT_EXCHANGE,
                routingKey: '', // Ignored for fanout exchanges
            );

            Log::info('Broadcast message published to all agents', [
                'command_type' => $payload['type'] ?? 'unknown',
            ]);

            return $result;
        } catch (Throwable $e) {
            Log::error('Failed to publish broadcast message', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Consume messages from a RabbitMQ queue using push model (basic_consume)
     *
     * @param  string  $queueName  Queue name to consume from
     * @param  Closure(AMQPMessage): void  $callback  Function to process messages
     * @param  string  $consumerTag  Consumer identifier
     * @param  string  $threadId  Thread identifier
     */
    public function consume(
        string $queueName,
        Closure $callback,
        string $consumerTag = '',
        string $threadId = 'default'
    ): void {
        try {
            $channel = $this->getChannel('consumer', $threadId);

            // Ensure queue exists (passive=false, durable=true, exclusive=false, auto_delete=false)
            $channel->queue_declare($queueName, false, true, false, false);

            // Bind queue to the direct exchange for room-based commands
            // The binding key will be determined by the agent registration process
            /* $channel->queue_bind($queueName, self::CMD_DIRECT_EXCHANGE, "room.*"); */
            /**/
            /* // Bind queue to broadcast exchange to receive system-wide messages */
            /* $channel->queue_bind($queueName, self::BROADCAST_FANOUT_EXCHANGE, ''); */

            // Start consuming with manual acknowledgment (no_ack=false)
            $channel->basic_consume(
                $queueName,    // queue
                $consumerTag,  // consumer tag
                false,         // no local
                false,         // no ack (set to false to enable manual acknowledgment)
                false,         // exclusive
                false,         // no wait
                function (AMQPMessage $msg) use ($callback): void {
                    try {
                        // Call the user-provided callback
                        $callback($msg);
                        // Acknowledge the message
                        $msg->ack();
                    } catch (Throwable $e) {
                        // Reject the message and requeue it
                        $msg->reject(true);
                        throw new RuntimeException("Error processing message: {$e->getMessage()}", 0, $e);
                    }
                }
            );

            // Process messages until channel is closed or connection lost
            // This loop facilitates the PUSH model by waiting for incoming messages
            while ($channel->is_consuming()) {
                $channel->wait();
            }
        } catch (AMQPIOException|AMQPConnectionClosedException $e) {
            $this->isAvailable = false;

            // Close any broken connections
            $this->closeAll();

            Log::error('RabbitMQ connection error during consumption', [
                'error' => $e->getMessage(),
                'queue' => $queueName,
            ]);

            // Wait before retrying
            sleep(5);

            // Recursive retry
            $this->consume($queueName, $callback, $consumerTag, $threadId);
        } catch (Throwable $e) {
            Log::error('RabbitMQ consumption error', [
                'error' => $e->getMessage(),
                'queue' => $queueName,
            ]);

            throw new RuntimeException("Error consuming messages: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Close specific connection and its channels
     */
    public function closeConnection(string $type = 'publisher'): void
    {
        if (isset($this->channels[$type])) {
            foreach ($this->channels[$type] as $channel) {
                if ($channel->is_open()) {
                    $channel->close();
                }
            }
            unset($this->channels[$type]);
        }

        if (isset($this->connections[$type]) && $this->connections[$type]->isConnected()) {
            $this->connections[$type]->close();
            unset($this->connections[$type]);
        }
    }

    /**
     * Close all open connections and channels
     */
    public function closeAll(): void
    {
        foreach ($this->channels as $processType => $threadChannels) {
            foreach ($threadChannels as $threadId => $channel) {
                if ($channel->is_open()) {
                    $channel->close();
                }
                unset($this->channels[$processType][$threadId]);
            }
        }

        $this->channels = [];

        foreach ($this->connections as $connection) {
            if ($connection->isConnected()) {
                $connection->close();
            }
        }

        $this->connections = [];
    }

    /**
     * Check if RabbitMQ server is available
     */
    public function isAvailable(): bool
    {
        if (! $this->isAvailable && $this->connectionAttempts >= self::MAX_CONNECTION_ATTEMPTS) {
            return false;
        }

        try {
            $connection = $this->getConnection('publisher');

            return $connection->isConnected();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Internal method to handle the actual publishing with retry logic
     *
     * @param  AMQPChannel  $channel  The channel to publish on
     * @param  string  $message  The message content
     * @param  string  $exchange  Exchange name
     * @param  string  $routingKey  Routing key
     * @return bool Whether the message was published successfully
     */
    private function doPublish(
        AMQPChannel $channel,
        string $message,
        string $exchange,
        string $routingKey
    ): bool {
        if (! $this->isAvailable && $this->connectionAttempts >= self::MAX_CONNECTION_ATTEMPTS) {
            Log::warning('RabbitMQ service unavailable, message not published', [
                'exchange' => $exchange,
                'routing_key' => $routingKey,
            ]);

            return false;
        }

        try {
            // Create and publish message with delivery mode 2 (persistent)
            $msg = new AMQPMessage(
                $message,
                [
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'content_type' => 'application/json',
                    'timestamp' => time(),
                    'app_id' => config('app.name'),
                ]
            );

            $channel->basic_publish($msg, $exchange, $routingKey);

            // Reset connection attempts on success
            $this->connectionAttempts = 0;
            $this->isAvailable = true;

            return true;
        } catch (AMQPIOException|AMQPConnectionClosedException $e) {
            $this->connectionAttempts++;
            $this->isAvailable = false;

            // Close and clear any broken connections
            $this->closeAll();

            Log::error('RabbitMQ connection error', [
                'error' => $e->getMessage(),
                'attempt' => $this->connectionAttempts,
                'max_attempts' => self::MAX_CONNECTION_ATTEMPTS,
            ]);

            // Retry if under threshold
            if ($this->connectionAttempts < self::MAX_CONNECTION_ATTEMPTS) {
                Log::info('Retrying RabbitMQ connection', ['attempt' => $this->connectionAttempts]);
                // Wait a moment before retrying (50ms * attempt number)
                usleep(50000 * $this->connectionAttempts);

                return $this->doPublish($channel, $message, $exchange, $routingKey);
            }

            return false;
        } catch (Throwable $e) {
            Log::error('RabbitMQ message publishing failed', [
                'error' => $e->getMessage(),
                'exchange' => $exchange,
                'routing_key' => $routingKey,
            ]);

            return false;
        }
    }

    /**
     * Get a dedicated connection for the specified process type
     *
     * @throws AMQPIOException When connection cannot be established
     */
    private function getConnection(string $type = 'publisher'): AMQPStreamConnection
    {
        if (! isset($this->connections[$type]) || ! $this->connections[$type]->isConnected()) {
            $host = (string) config('rabbitmq.host', 'localhost');
            $port = (int) config('rabbitmq.port', 5672);
            $user = (string) config('rabbitmq.user', 'guest');
            $password = (string) config('rabbitmq.password', 'guest');
            $vhost = (string) config('rabbitmq.vhost', '/');

            Log::debug('Creating new RabbitMQ connection', [
                'type' => $type,
                'host' => $host,
                'port' => $port,
            ]);

            $this->connections[$type] = new AMQPStreamConnection(
                $host,
                $port,
                $user,
                $password,
                $vhost,
                false,  // insist
                'AMQPLAIN',  // login method
                null,  // login response
                'en_US',  // locale
                3.0,  // connection timeout in seconds
                3.0,  // read/write timeout
                null,  // context
                false,  // keepalive
                30,  // heartbeat
            );
        }

        return $this->connections[$type];
    }

    /**
     * Get or create a channel for the specified thread within a process type
     */
    private function getChannel(string $processType = 'publisher', string $threadId = 'default'): AMQPChannel
    {
        // Initialize channels array for this process type if it doesn't exist
        if (! isset($this->channels[$processType])) {
            $this->channels[$processType] = [];
        }

        // Create new channel if it doesn't exist or isn't open
        if (
            ! isset($this->channels[$processType][$threadId]) ||
            ! $this->channels[$processType][$threadId]->is_open()
        ) {

            $connection = $this->getConnection($processType);
            $this->channels[$processType][$threadId] = $connection->channel();

            // Set quality of service parameters for consumer channels
            if ($processType === 'consumer') {
                $this->channels[$processType][$threadId]->basic_qos(
                    0, // prefetch size (0 means "no specific size")
                    1,  // prefetch count (process one message at a time)
                    false // global (false means per-consumer prefetch)
                );
            }
        }

        return $this->channels[$processType][$threadId];
    }
}
