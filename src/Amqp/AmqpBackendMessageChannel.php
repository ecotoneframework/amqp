<?php


namespace Ecotone\Amqp;

use Ecotone\Messaging\ContextualPollableChannel;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\PollableChannel;

/**
 * Class AmqpBackedQueue
 * @package Ecotone\Amqp
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpBackendMessageChannel implements ContextualPollableChannel
{
    /**
     * @var AmqpInboundChannelAdapter
     */
    private $amqpInboundChannelAdapter;
    /**
     * @var AmqpOutboundChannelAdapter
     */
    private $amqpOutboundChannelAdapter;

    /**
     * AmqpBackedQueue constructor.
     *
     * @param AmqpInboundChannelAdapter  $amqpInboundChannelAdapter
     * @param AmqpOutboundChannelAdapter $amqpOutboundChannelAdapter
     */
    public function __construct(AmqpInboundChannelAdapter $amqpInboundChannelAdapter, AmqpOutboundChannelAdapter $amqpOutboundChannelAdapter)
    {
        $this->amqpInboundChannelAdapter = $amqpInboundChannelAdapter;
        $this->amqpOutboundChannelAdapter = $amqpOutboundChannelAdapter;
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message): void
    {
        $this->amqpOutboundChannelAdapter->handle($message);
    }

    /**
     * @inheritDoc
     */
    public function receive(): ?Message
    {
        return $this->amqpInboundChannelAdapter->getMessage(null);
    }

    public function receiveWithEndpointId(string $endpointId): ?Message
    {
        return $this->amqpInboundChannelAdapter->getMessage($endpointId);
    }

    /**
     * @inheritDoc
     */
    public function receiveWithTimeout(int $timeoutInMilliseconds): ?Message
    {
        return $this->amqpInboundChannelAdapter->getMessage(null);
    }

    /**
     * @inheritDoc
     */
    public function receiveWithEndpointIdAndTimeout(string $endpointId, int $timeoutInMilliseconds): ?Message
    {
        return $this->amqpInboundChannelAdapter->getMessage($endpointId);
    }
}