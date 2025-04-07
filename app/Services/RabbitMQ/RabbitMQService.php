<?php

declare(strict_types=1);

namespace App\Services\RabbitMQ;

use App\Models\Computer;
use Exception;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

final class RabbitMQService
{
    private ?AMQPChannel $channel = null;

    public function __construct(
        private readonly AMQPStreamConnection $connection,
        private readonly RabbitMQConfigInterface $config
    ) {
        try {
            $this->channel = $this->connection->channel();
            $this->setupExchanges();
        } catch (Exception $e) {
            Log::error('RabbitMQ connection error: '.$e->getMessage());
            throw $e; // Let the caller handle it
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    public function close(): void
    {
        try {
            if ($this->channel) {
                $this->channel->close();
            }
            if ($this->connection) {
                $this->connection->close();
            }
        } catch (Exception $e) {
            Log::warning('Failed to close RabbitMQ resources: '.$e->getMessage());
        }
    }

    public function sendCommandToComputer(
        string $computerId,
        string $roomId,
        string $commandType,
        array $options = [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]): bool
    {
        try {
            $routingKey = strtr(
                $this->config->getCommandRoutingKey(),
                [
                    '{room}' => $roomId,
                    '{computer}' => $computerId,
                ]
            );
            $message = new AMQPMessage(
                json_encode($commandType),
                $options
            );
            $this->channel->basic_publish(
                $message,
                $this->config->getCommandExchange(),
                $routingKey
            );

            return true;
        } catch (Exception $e) {
            Log::error('Failed to publish message', ['message' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Sends a command to all computers in a room
     */
    public function sendCommandToRoom(
        string $roomId,
        array $command,
        array $options = [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]
    ): bool {
        try {
            $routingKey = strtr(
                $this->config->getRoomBroadcastRoutingKey(),
                [
                    '{room}' => $roomId,
                ]
            );

            $message = new AMQPMessage(
                json_encode($command),
                $options
            );

            $this->channel->basic_publish(
                $message,
                $this->config->getCommandExchange(),
                $routingKey
            );

            return true;
        } catch (Exception $e) {
            Log::error('Failed to publish message', ['message' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Publishes a message to a specific queue
     */
    public function publishToQueue(
        string $queueName,
        array $data,
        array $options = [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]
    ): bool {
        try {
            // Declare the queue to ensure it exists
            $this->channel->queue_declare(
                $queueName,      // queue name
                false,           // passive
                true,            // durable
                false,           // exclusive
                false            // auto_delete
            );

            $message = new AMQPMessage(
                json_encode($data),
                $options
            );

            $this->channel->basic_publish(
                $message,
                $this->config->getCommandExchange(),
                $queueName
            );

            return true;
        } catch (Exception $e) {
            Log::error('Failed to publish message to queue', [
                'queue' => $queueName,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Consumes status update messages from agents
     * Updates computer status in the database
     */
    public function consumeStatusUpdates(int $messageLimit = 0): bool
    {
        try {
            // Declare status queue
            $queueName = $this->config->getStatusQueue();
            $this->channel->queue_declare(
                $queueName,      // queue
                false,           // passive
                true,            // durable
                false,           // exclusive
                false            // auto_delete
            );

            // Bind the queue to the exchange with the routing key
            $this->channel->queue_bind(
                $queueName,
                $this->config->getStatusExchange(),
                $this->config->getStatusRoutingKey()
            );

            // Set up the consumer
            $messageCount = 0;
            $this->channel->basic_consume(
                $queueName,
                '',             // consumer tag
                false,          // no local
                false,          // no ack
                false,          // exclusive
                false,          // no wait
                function (AMQPMessage $message) use (&$messageCount, $messageLimit) {
                    // Process the message
                    $data = json_decode($message->getBody(), true);

                    Log::info('Received status update from agent', [
                        'computer_id' => $data['computer_id'] ?? 'unknown',
                        'status' => $data['status'] ?? 'unknown',
                    ]);

                    // Update computer status in the database if the data is valid
                    if (isset($data['computer_id'], $data['status'])) {
                        try {
                            $computer = Computer::firstWhere('uuid', $data['computer_id']);

                            if ($computer) {
                                $computer->update([
                                    'status' => $data['status'],
                                    'last_seen_at' => now(),
                                ]);
                            }
                        } catch (Exception $e) {
                            // In test environments, this might fail due to missing DB connection
                            Log::warning('Failed to update computer status: '.$e->getMessage());
                        }
                    }

                    // Acknowledge the message
                    $message->ack();

                    // Increment message count
                    $messageCount++;

                    // Stop consuming after the limit is reached
                    if ($messageLimit > 0 && $messageCount >= $messageLimit) {
                        return false;
                    }

                    return true;
                }
            );

            // Wait for messages until the limit is reached
            while ($messageLimit <= 0 || $messageCount < $messageLimit) {
                $this->channel->wait();

                // For tests with message limits, we can break after processing the expected messages
                if ($messageLimit > 0 && $messageCount >= $messageLimit) {
                    break;
                }
            }

            return true;
        } catch (Exception $e) {
            Log::error('Error consuming status updates', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function setupExchanges(): void
    {
        // Declare exchanges
        foreach ($this->config->getExchanges() as $exchange) {
            $this->channel->exchange_declare(
                $exchange->name,        // exchange name
                $exchange->type,        // type
                false,                  // passive
                $exchange->durable,     // durable
                $exchange->autoDelete   // auto_delete
            );
        }
    }
}
