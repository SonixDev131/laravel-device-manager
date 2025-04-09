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
            throw $e;
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    public function close(): void
    {
        try {
            $this->channel?->close();
            $this->connection?->close();
        } catch (Exception $e) {
            Log::warning('Failed to close RabbitMQ resources: '.$e->getMessage());
        }
    }

    /**
     * Send command to a specific computer
     */
    public function sendCommandToComputer(
        string $computerId,
        string $roomId,
        string|array $command
    ): bool {
        // Format routing key for specific computer
        $routingKey = strtr(
            $this->config->getCommandRoutingKey(),
            ['{room}' => $roomId, '{computer}' => $computerId]
        );

        return $this->publish(
            $this->config->getCommandExchange(),
            $routingKey,
            $command
        );
    }

    /**
     * Send command to all computers in a room
     */
    public function sendCommandToRoom(string $roomId, string|array $command): bool
    {
        // Format routing key for room broadcast
        $routingKey = strtr(
            $this->config->getRoomBroadcastRoutingKey(),
            ['{room}' => $roomId]
        );

        return $this->publish(
            $this->config->getCommandExchange(),
            $routingKey,
            $command
        );
    }

    /**
     * Publish to a specific queue
     */
    public function publishToQueue(string $queueName, string|array $data): bool
    {
        try {
            // Ensure queue exists
            $this->channel->queue_declare(
                $queueName,
                false,  // passive
                true,   // durable
                false,  // exclusive
                false   // auto_delete
            );

            return $this->publish(
                $this->config->getCommandExchange(),
                $queueName,
                $data
            );
        } catch (Exception $e) {
            Log::error('Failed to publish to queue', [
                'queue' => $queueName,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Consume status update messages from agents
     */
    public function consumeStatusUpdates(int $messageLimit = 0): bool
    {
        try {
            // Set up the status queue
            $queueName = $this->config->getStatusQueue();
            $this->channel->queue_declare(
                $queueName,
                false,  // passive
                true,   // durable
                false,  // exclusive
                false   // auto_delete
            );

            // Bind queue to exchange
            $this->channel->queue_bind(
                $queueName,
                $this->config->getStatusExchange(),
                $this->config->getStatusRoutingKey()
            );

            // Process messages
            $messageCount = 0;
            $this->channel->basic_consume(
                $queueName,
                '',      // consumer tag
                false,   // no local
                false,   // no ack
                false,   // exclusive
                false,   // no wait
                $this->createMessageHandler($messageCount, $messageLimit)
            );

            // Wait for messages until limit reached
            while ($messageLimit <= 0 || $messageCount < $messageLimit) {
                $this->channel->wait();

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

    /**
     * Core publish method - handles the common publishing logic
     */
    private function publish(string $exchange, string $routingKey, string|array $data): bool
    {
        try {
            // Prepare message content
            $messageContent = is_array($data) ? json_encode($data) : $data;

            // Create and publish message
            $message = new AMQPMessage(
                $messageContent,
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                ]
            );

            $this->channel->basic_publish(
                $message,
                $exchange,
                $routingKey
            );

            return true;
        } catch (Exception $e) {
            Log::error('Failed to publish message', [
                'exchange' => $exchange,
                'routing_key' => $routingKey,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Create a message handler for consuming status updates
     */
    private function createMessageHandler(int &$messageCount, int $messageLimit): callable
    {
        return function (AMQPMessage $message) use (&$messageCount, $messageLimit) {
            // Process the message
            $data = json_decode($message->getBody(), true);

            Log::info('Received status update from agent', [
                'computer_id' => $data['computer_id'] ?? 'unknown',
                'status' => $data['status'] ?? 'unknown',
            ]);

            // Update computer status if data is valid
            if (isset($data['computer_id'], $data['status'])) {
                $this->updateComputerStatus($data);
            }

            // Acknowledge the message
            $message->ack();
            $messageCount++;

            // Stop consuming after limit reached
            return $messageLimit <= 0 || $messageCount < $messageLimit;
        };
    }

    /**
     * Update computer status in database
     */
    private function updateComputerStatus(array $data): void
    {
        try {
            $computer = Computer::firstWhere('uuid', $data['computer_id']);

            if ($computer) {
                $computer->update([
                    'status' => $data['status'],
                    'last_seen_at' => now(),
                ]);
            }
        } catch (Exception $e) {
            Log::warning('Failed to update computer status: '.$e->getMessage());
        }
    }

    /**
     * Set up required exchanges
     */
    private function setupExchanges(): void
    {
        foreach ($this->config->getExchanges() as $exchange) {
            $this->channel->exchange_declare(
                $exchange->name,
                $exchange->type,
                false,               // passive
                $exchange->durable,
                $exchange->autoDelete
            );
        }
    }
}
