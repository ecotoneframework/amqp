<?php
declare(strict_types=1);

namespace Ecotone\Amqp;

use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageConverter\HeaderMapper;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Enqueue\AmqpLib\AmqpConnectionFactory;
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
     * @var AmqpConnectionFactory
     */
    private $amqpConnectionFactory;
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
     * @var HeaderMapper
     */
    private $headerMapper;
    /**
     * @var bool
     */
    private $autoDeclare;
    /**
     * @var string|null
     */
    private $routingKeyFromHeaderName;
    /**
     * @var ConversionService
     */
    private $conversionService;
    /**
     * @var MediaType
     */
    private $defaultConversionMediaType;
    /**
     * @var string|null
     */
    private $exchangeFromHeaderName;

    /**
     * OutboundAmqpGateway constructor.
     *
     * @param AmqpConnectionFactory $amqpConnectionFactory
     * @param AmqpAdmin $amqpAdmin
     * @param string $exchangeName
     * @param string|null $routingKey
     * @param string|null $routingKeyFromHeaderName
     * @param string|null $exchangeFromHeaderName
     * @param bool $defaultPersistentDelivery
     * @param bool $autoDeclare
     * @param HeaderMapper $headerMapper
     * @param ConversionService $conversionService
     * @param MediaType $defaultConversionMediaType
     */
    public function __construct(AmqpConnectionFactory $amqpConnectionFactory, AmqpAdmin $amqpAdmin, string $exchangeName, ?string $routingKey, ?string $routingKeyFromHeaderName, ?string $exchangeFromHeaderName, bool $defaultPersistentDelivery, bool $autoDeclare, HeaderMapper $headerMapper, ConversionService $conversionService, MediaType $defaultConversionMediaType)
    {
        $this->amqpConnectionFactory = $amqpConnectionFactory;
        $this->routingKey = $routingKey;
        $this->exchangeName = $exchangeName;
        $this->amqpAdmin = $amqpAdmin;
        $this->defaultPersistentDelivery = $defaultPersistentDelivery;
        $this->headerMapper = $headerMapper;
        $this->autoDeclare = $autoDeclare;
        $this->routingKeyFromHeaderName = $routingKeyFromHeaderName;
        $this->conversionService = $conversionService;
        $this->defaultConversionMediaType = $defaultConversionMediaType;
        $this->exchangeFromHeaderName = $exchangeFromHeaderName;
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        /** @var AmqpContext $context */
        $context = $this->amqpConnectionFactory->createContext();

        $exchangeName = $this->exchangeName;
        if ($this->exchangeFromHeaderName) {
            $exchangeName = $message->getHeaders()->containsKey($this->exchangeFromHeaderName) ? $message->getHeaders()->get($this->exchangeFromHeaderName) : $this->exchangeName;
        }
        if ($this->autoDeclare) {
            $this->amqpAdmin->declareExchangeWithQueuesAndBindings($exchangeName, $context);
        }

        $enqueueMessagePayload = $message->getPayload();
        $mediaType = $message->getHeaders()->hasContentType() ? $message->getHeaders()->getContentType() : null;
        if (!is_string($enqueueMessagePayload)) {
            if (!$message->getHeaders()->hasContentType()) {
                throw new InvalidArgumentException("Can't send message to amqp channel. Payload has incorrect type, that can't be converted: " . TypeDescriptor::createFromVariable($enqueueMessagePayload)->toString());
            }

            $sourceType = $message->getHeaders()->getContentType()->hasTypeParameter() ? $message->getHeaders()->getContentType()->getTypeParameter() : TypeDescriptor::createFromVariable($enqueueMessagePayload);
            $sourceMediaType = $message->getHeaders()->getContentType();
            $targetType = TypeDescriptor::createStringType();

            if ($this->conversionService->canConvert(
                $sourceType,
                $sourceMediaType,
                $targetType,
                $this->defaultConversionMediaType
            )) {
                $mediaType = $this->defaultConversionMediaType;
                $enqueueMessagePayload = $this->conversionService->convert(
                    $enqueueMessagePayload,
                    $message->getHeaders()->getContentType()->hasTypeParameter() ? $message->getHeaders()->getContentType()->getTypeParameter() : TypeDescriptor::createFromVariable($enqueueMessagePayload),
                    $message->getHeaders()->getContentType(),
                    TypeDescriptor::createStringType(),
                    $this->defaultConversionMediaType
                );
            } else {
                throw new InvalidArgumentException("Can't send message to amqp channel. Payload has incorrect non-convertable type or converter is missing. 
                 From {$sourceMediaType}:{$sourceType} to {$this->defaultConversionMediaType}:{$targetType} converted: " . TypeDescriptor::createFromVariable($enqueueMessagePayload)->toString());
            }
        }

        $applicationHeaders = $this->headerMapper->mapFromMessageHeaders($message->getHeaders()->headers());
        if ($message->getHeaders()->containsKey(MessageHeaders::TYPE_ID)) {
            $applicationHeaders[MessageHeaders::TYPE_ID] = $message->getHeaders()->get(MessageHeaders::TYPE_ID);
        }
        if ($message->getHeaders()->containsKey(MessageHeaders::ROUTING_SLIP)) {
            $applicationHeaders[MessageHeaders::ROUTING_SLIP] = $message->getHeaders()->get(MessageHeaders::ROUTING_SLIP);
        }

        $messageToSend = new \Interop\Amqp\Impl\AmqpMessage($enqueueMessagePayload, $applicationHeaders, []);


        if ($this->routingKeyFromHeaderName) {
            $routingKey = $message->getHeaders()->containsKey($this->routingKeyFromHeaderName) ? $message->getHeaders()->get($this->routingKeyFromHeaderName) : $this->routingKey;
        } else {
            $routingKey = $this->routingKey;
        }

        if ($mediaType) {
            $messageToSend->setContentType($mediaType->toString());
        }

        if (!is_null($routingKey) && $routingKey !== "") {
            $messageToSend->setRoutingKey($routingKey);
        }

        $messageToSend->setDeliveryMode($this->defaultPersistentDelivery ? AmqpMessage::DELIVERY_MODE_PERSISTENT : AmqpMessage::DELIVERY_MODE_NON_PERSISTENT);

        $context->createProducer()->send(new AmqpTopic($exchangeName), $messageToSend);
        $context->close();
    }
}