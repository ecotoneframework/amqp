<?php
declare(strict_types=1);

namespace Ecotone\Amqp;

use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Enqueue\EnqueueOutboundChannelAdapterBuilder;
use Ecotone\Enqueue\OutboundMessageConverter;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Enqueue\AmqpLib\AmqpConnectionFactory;

class AmqpOutboundChannelAdapterBuilder extends EnqueueOutboundChannelAdapterBuilder
{
    private const DEFAULT_PERSISTENT_MODE = true;

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
        $this->initialize($amqpConnectionFactoryReferenceName);
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
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        /** @var AmqpConnectionFactory $amqpConnectionFactory */
        $amqpConnectionFactory = $referenceSearchService->get($this->amqpConnectionFactoryReferenceName);
        /** @var ConversionService $conversionService */
        $conversionService = $referenceSearchService->get(ConversionService::REFERENCE_NAME);

        return new AmqpOutboundChannelAdapter(
            CachedConnectionFactory::createFor(new AmqpReconnectableConnectionFactory($amqpConnectionFactory)),
            $this->autoDeclare ? $referenceSearchService->get(AmqpAdmin::REFERENCE_NAME) : AmqpAdmin::createEmpty(),
            $this->exchangeName,
            $this->defaultRoutingKey,
            $this->routingKeyFromHeader,
            $this->exchangeFromHeader,
            $this->defaultPersistentDelivery,
            $this->autoDeclare,
            new OutboundMessageConverter($this->headerMapper, $conversionService, $this->defaultConversionMediaType, $this->defaultDeliveryDelay, $this->defaultTimeToLive)
        );
    }
}