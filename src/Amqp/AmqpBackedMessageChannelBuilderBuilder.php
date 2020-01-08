<?php


namespace Ecotone\Amqp;

use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Enqueue\EnqueueAcknowledgementCallback;
use Ecotone\Enqueue\EnqueueBackendMessageChannelBuilder;
use Ecotone\Enqueue\InboundMessageConverter;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Endpoint\NullEntrypointGateway;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Enqueue\AmqpLib\AmqpConnectionFactory;

/**
 * Class AmqpBackedMessageChannelBuilder
 * @package Ecotone\Amqp
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpBackedMessageChannelBuilderBuilder extends EnqueueBackendMessageChannelBuilder
{
    const PUBLISH_SUBSCRIBE_EXCHANGE_NAME_PREFIX = "ecotone.fanout.";

    /**
     * @var string
     */
    private $amqpConnectionReferenceName;
    /**
     * @var AmqpOutboundChannelAdapterBuilder
     */
    private $amqpOutboundChannelAdapter;
    /**
     * @var bool
     */
    private $isPublishSubscribe;

    /**
     * AmqpBackedMessageChannelBuilder constructor.
     *
     * @param string $channelName
     * @param string $amqpConnectionReferenceName
     * @param bool $isPublishSubscribe
     *
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    private function __construct(string $channelName, string $amqpConnectionReferenceName, bool $isPublishSubscribe)
    {
        $this->channelName = $channelName;
        $this->amqpConnectionReferenceName = $amqpConnectionReferenceName;
        $this->isPublishSubscribe = $isPublishSubscribe;

        if ($this->isPublishSubscribe) {
            $amqpOutboundChannelAdapterBuilder = AmqpOutboundChannelAdapterBuilder::create(self::PUBLISH_SUBSCRIBE_EXCHANGE_NAME_PREFIX . $channelName, $this->amqpConnectionReferenceName);
        } else {
            $amqpOutboundChannelAdapterBuilder = AmqpOutboundChannelAdapterBuilder::createForDefaultExchange($this->amqpConnectionReferenceName)
                ->withDefaultRoutingKey($this->channelName);
        }
        $this->amqpOutboundChannelAdapter = $amqpOutboundChannelAdapterBuilder
            ->withAutoDeclareOnSend(true)
            ->withHeaderMapper("*")
            ->withDefaultPersistentMode(true);

        $this->initialize($amqpConnectionReferenceName);
    }

    /**
     * @param string $channelName
     * @param string $amqpConnectionReferenceName
     *
     * @return AmqpBackedMessageChannelBuilderBuilder
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public static function createDirectChannel(string $channelName, string $amqpConnectionReferenceName = AmqpConnectionFactory::class)
    {
        return new self($channelName, $amqpConnectionReferenceName, false);
    }

    /**
     * @param string $channelName
     * @param string $amqpConnectionReferenceName
     * @return AmqpBackedMessageChannelBuilderBuilder
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public static function createPublishSubscribe(string $channelName, string $amqpConnectionReferenceName = AmqpConnectionFactory::class)
    {
        return new self($channelName, $amqpConnectionReferenceName, true);
    }

    /**
     * @return bool
     */
    public function isPublishSubscribe(): bool
    {
        return $this->isPublishSubscribe;
    }

    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return $this->amqpOutboundChannelAdapter->resolveRelatedInterfaces($interfaceToCallRegistry);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): MessageChannel
    {
        /** @var AmqpAdmin $amqpAdmin */
        $amqpAdmin = $referenceSearchService->get(AmqpAdmin::REFERENCE_NAME);
        /** @var AmqpConnectionFactory $amqpConnectionFactory */
        $amqpConnectionFactory = $referenceSearchService->get($this->amqpConnectionReferenceName);

        if ($this->defaultConversionMediaType) {
            /** @var AmqpOutboundChannelAdapter $amqpOutboundChannelAdapter */
            $this->amqpOutboundChannelAdapter = $this->amqpOutboundChannelAdapter
                ->withDefaultConversionMediaType($this->defaultConversionMediaType);
        }
        $amqpOutboundChannelAdapter = $this->amqpOutboundChannelAdapter
            ->withDefaultTimeToLive($this->timeToLive)
            ->withDefaultDeliveryDelay($this->deliveryDelay)
            ->build(InMemoryChannelResolver::createEmpty(), $referenceSearchService);

        $inboundChannelAdapter = new AmqpInboundChannelAdapter(
            new CachedConnectionFactory(new AmqpReconnectableConnectionFactory($amqpConnectionFactory)),
            NullEntrypointGateway::create(),
            $amqpAdmin,
            true,
            $this->channelName,
            $this->receiveTimeoutInMilliseconds,
            new InboundMessageConverter(
                EnqueueAcknowledgementCallback::AUTO_ACK,
                AmqpHeader::HEADER_ACKNOWLEDGE,
                DefaultHeaderMapper::createAllHeadersMapping(),
            ),
            $this->isPublishSubscribe
        );

        return new AmqpBackendMessageChannel($inboundChannelAdapter, $amqpOutboundChannelAdapter);
    }
}