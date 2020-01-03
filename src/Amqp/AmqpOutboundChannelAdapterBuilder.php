<?php
declare(strict_types=1);

namespace Ecotone\Amqp;

use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;
use Ecotone\Messaging\MessageConverter\HeaderMapper;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Enqueue\AmqpLib\AmqpConnectionFactory;

/**
 * Class OutboundAmqpGatewayBuilder
 * @package Ecotone\Amqp
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpOutboundChannelAdapterBuilder implements MessageHandlerBuilder
{
    private const DEFAULT_PERSISTENT_MODE = true;
    const         DEFAULT_AUTO_DECLARE = false;
    const DEFAULT_TIME_TO_LIVE = 0;
    const DEFAULT_DELIVERY_DELAY = 0;

    /**
     * @var string
     */
    private $endpointId;
    /**
     * @var string
     */
    private $inputChannelName = "";
    /**
     * @var string
     */
    private $amqpConnectionFactoryReferenceName;
    /**
     * @var string
     */
    private $defaultRoutingKey;
    /**
     * @var string
     */
    private $routingKeyFromHeader;
    /**
     * @var string
     */
    private $exchangeFromHeader;
    /**
     * @var string
     */
    private $exchangeName;
    /**
     * @var bool
     */
    private $defaultPersistentDelivery = self::DEFAULT_PERSISTENT_MODE;
    /**
     * @var HeaderMapper
     */
    private $headerMapper;
    /**
     * @var bool
     */
    private $autoDeclare = self::DEFAULT_AUTO_DECLARE;
    /**
     * @var MediaType
     */
    private $defaultConversionMediaType;
    /**
     * @var int
     */
    private $defaultTimeToLive = self::DEFAULT_TIME_TO_LIVE;
    /**
     * @var int
     */
    private $defaultDeliveryDelay = self::DEFAULT_DELIVERY_DELAY;

    /**
     * OutboundAmqpGatewayBuilder constructor.
     *
     * @param string $exchangeName
     * @param string $amqpConnectionFactoryReferenceName
     *
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    private function __construct(string $exchangeName, string $amqpConnectionFactoryReferenceName)
    {
        $this->amqpConnectionFactoryReferenceName = $amqpConnectionFactoryReferenceName;
        $this->exchangeName = $exchangeName;
        $this->headerMapper = DefaultHeaderMapper::createNoMapping();
        $this->defaultConversionMediaType = MediaType::createApplicationXPHPSerializedObject();
    }

    /**
     * @param string $exchangeName
     * @param string $amqpConnectionFactoryReferenceName
     *
     * @return AmqpOutboundChannelAdapterBuilder
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public static function create(string $exchangeName, string $amqpConnectionFactoryReferenceName): self
    {
        return new self($exchangeName, $amqpConnectionFactoryReferenceName);
    }

    /**
     * @param string $amqpConnectionFactoryReferenceName
     *
     * @return AmqpOutboundChannelAdapterBuilder
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public static function createForDefaultExchange(string $amqpConnectionFactoryReferenceName): self
    {
        return new self("", $amqpConnectionFactoryReferenceName);
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [];
    }

    public function withDefaultTimeToLive(int $timeInMilliseconds) : self
    {
        $this->defaultTimeToLive = $timeInMilliseconds;

        return $this;
    }

    public function withDefaultDeliveryDelay(int $deliveryDelayInMilliseconds) : self
    {
        $this->defaultDeliveryDelay = $deliveryDelayInMilliseconds;

        return $this;
    }

    /**
     * @param string $routingKey
     *
     * @return AmqpOutboundChannelAdapterBuilder
     */
    public function withDefaultRoutingKey(string $routingKey): self
    {
        $this->defaultRoutingKey = $routingKey;

        return $this;
    }

    /**
     * @param string $mediaType
     *
     * @return AmqpOutboundChannelAdapterBuilder
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function withDefaultConversionMediaType(string $mediaType): self
    {
        $this->defaultConversionMediaType = MediaType::parseMediaType($mediaType);

        return $this;
    }

    /**
     * @param string $headerName
     *
     * @return AmqpOutboundChannelAdapterBuilder
     */
    public function withRoutingKeyFromHeader(string $headerName): self
    {
        $this->routingKeyFromHeader = $headerName;

        return $this;
    }

    /**
     * @param string $exchangeName
     *
     * @return AmqpOutboundChannelAdapterBuilder
     */
    public function withExchangeFromHeader(string $exchangeName): self
    {
        $this->exchangeFromHeader = $exchangeName;

        return $this;
    }

    /**
     * @param bool $isPersistent
     *
     * @return AmqpOutboundChannelAdapterBuilder
     */
    public function withDefaultPersistentMode(bool $isPersistent): self
    {
        $this->defaultPersistentDelivery = $isPersistent;

        return $this;
    }

    /**
     * @param string $headerMapper comma separated list of headers to be mapped.
     *                             (e.g. "\*" or "thing1*, thing2" or "*thing1")
     *
     * @return AmqpOutboundChannelAdapterBuilder
     */
    public function withHeaderMapper(string $headerMapper): self
    {
        $this->headerMapper = DefaultHeaderMapper::createWith([], explode(",", $headerMapper));

        return $this;
    }

    /**
     * @param bool $toDeclare
     *
     * @return AmqpOutboundChannelAdapterBuilder
     */
    public function withAutoDeclareOnSend(bool $toDeclare): self
    {
        $this->autoDeclare = $toDeclare;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        /** @var AmqpConnectionFactory $amqpConnectionFactory */
        $amqpConnectionFactory = $referenceSearchService->get($this->amqpConnectionFactoryReferenceName);
        /** @var ConversionService $conversionService */
        $conversionService = $referenceSearchService->get(ConversionService::REFERENCE_NAME);

        return new AmqpOutboundChannelAdapter(
            $amqpConnectionFactory,
            $this->autoDeclare ? $referenceSearchService->get(AmqpAdmin::REFERENCE_NAME) : AmqpAdmin::createEmpty(),
            $this->exchangeName,
            $this->defaultRoutingKey,
            $this->routingKeyFromHeader,
            $this->exchangeFromHeader,
            $this->defaultPersistentDelivery,
            $this->autoDeclare,
            $this->headerMapper,
            $conversionService,
            $this->defaultConversionMediaType,
            $this->defaultTimeToLive,
            $this->defaultDeliveryDelay
        );
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedReferences(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [$interfaceToCallRegistry->getFor(AmqpOutboundChannelAdapter::class, "handle")];
    }

    /**
     * @inheritDoc
     */
    public function withInputChannelName(string $inputChannelName)
    {
        $this->inputChannelName = $inputChannelName;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getInputMessageChannelName(): string
    {
        return $this->inputChannelName;
    }

    /**
     * @inheritDoc
     */
    public function getEndpointId(): ?string
    {
        return $this->endpointId;
    }

    /**
     * @inheritDoc
     */
    public function withEndpointId(string $endpointId)
    {
        $this->endpointId = $endpointId;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [$this->amqpConnectionFactoryReferenceName];
    }

    public function __toString()
    {
        return "Outbound Amqp Adapter for channel " . $this->inputChannelName;
    }
}