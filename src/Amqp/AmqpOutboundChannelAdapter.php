<?php
declare(strict_types=1);

namespace Ecotone\Amqp;

use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Enqueue\OutboundMessageConverter;
use Ecotone\Enqueue\ReconnectableConnectionFactory;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHandler;
use Enqueue\AmqpLib\AmqpConnectionFactory;
use Enqueue\AmqpTools\RabbitMqDlxDelayStrategy;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\Impl\AmqpTopic;

/**
 * Class OutboundAmqpGateway
 * @package Ecotone\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpOutboundChannelAdapter implements MessageHandler
{
    /**
     * @var CachedConnectionFactory
     */
    private $connectionFactory;
    /**
     * @var string|null
     */
    private $routingKey;
    /**
     * @var string
     */
    private $exchangeName;
    /**
     * @var AmqpAdmin
     */
    private $amqpAdmin;
    /**
     * @var bool
     */
    private $defaultPersistentDelivery;
    /**
     * @var bool
     */
    private $autoDeclare;
    /**
     * @var string|null
     */
    private $routingKeyFromHeaderName;
    /**
     * @var string|null
     */
    private $exchangeFromHeaderName;
    /**
     * @var int|null
     */
    private $defaultTimeToLive;
    /**
     * @var int|null
     */
    private $defaultDeliveryDelay;
    /**
     * @var OutboundMessageConverter
     */
    private $outboundMessageConverter;
    /**
     * @var bool
     */
    private $initialized = false;

    public function __construct(CachedConnectionFactory $connectionFactory, AmqpAdmin $amqpAdmin, string $exchangeName, ?string $routingKey, ?string $routingKeyFromHeaderName, ?string $exchangeFromHeaderName, bool $defaultPersistentDelivery, bool $autoDeclare, OutboundMessageConverter $outboundMessageConverter, ?int $defaultTimeToLive, ?int $defaultDeliveryDelay)
    {
        $this->connectionFactory = $connectionFactory;
        $this->routingKey = $routingKey;
        $this->exchangeName = $exchangeName;
        $this->amqpAdmin = $amqpAdmin;
        $this->defaultPersistentDelivery = $defaultPersistentDelivery;
        $this->autoDeclare = $autoDeclare;
        $this->routingKeyFromHeaderName = $routingKeyFromHeaderName;
        $this->exchangeFromHeaderName = $exchangeFromHeaderName;
        $this->defaultTimeToLive = $defaultTimeToLive;
        $this->defaultDeliveryDelay = $defaultDeliveryDelay;
        $this->outboundMessageConverter = $outboundMessageConverter;
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $exchangeName = $this->exchangeName;
        if ($this->exchangeFromHeaderName) {
            $exchangeName = $message->getHeaders()->containsKey($this->exchangeFromHeaderName) ? $message->getHeaders()->get($this->exchangeFromHeaderName) : $this->exchangeName;
        }
        if (!$this->initialized && $this->autoDeclare) {
            $this->amqpAdmin->declareExchangeWithQueuesAndBindings($exchangeName, $this->connectionFactory->createContext());
            $this->initialized = true;
        }

        $outboundMessage = $this->outboundMessageConverter->prepare($message);
        $messageToSend = new \Interop\Amqp\Impl\AmqpMessage($outboundMessage->getPayload(), $outboundMessage->getHeaders(), []);

        if ($this->routingKeyFromHeaderName) {
            $routingKey = $message->getHeaders()->containsKey($this->routingKeyFromHeaderName) ? $message->getHeaders()->get($this->routingKeyFromHeaderName) : $this->routingKey;
        } else {
            $routingKey = $this->routingKey;
        }

        if ($outboundMessage->getContentType()) {
            $messageToSend->setContentType($outboundMessage->getContentType());
        }

        if (!is_null($routingKey) && $routingKey !== "") {
            $messageToSend->setRoutingKey($routingKey);
        }

        $messageToSend
            ->setDeliveryMode($this->defaultPersistentDelivery ? AmqpMessage::DELIVERY_MODE_PERSISTENT : AmqpMessage::DELIVERY_MODE_NON_PERSISTENT);


        $this->connectionFactory->getProducer()
            ->setTimeToLive($this->defaultTimeToLive)
            ->setDelayStrategy(new RabbitMqDlxDelayStrategy())
            ->setDeliveryDelay($this->defaultDeliveryDelay)
            ->send(new AmqpTopic($exchangeName), $messageToSend);
    }
}