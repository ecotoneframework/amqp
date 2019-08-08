<?php


namespace Ecotone\Amqp;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Interop\Amqp\AmqpConnectionFactory;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Endpoint\NullEntrypointGateway;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class AmqpBackedMessageChannelBuilder
 * @package Ecotone\Amqp
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpBackedMessageChannelBuilder implements MessageChannelBuilder
{
    /**
     * @var string
     */
    private $channelName;
    /**
     * @var string
     */
    private $amqpConnectionReferenceName;
    /**
     * @var int
     */
    private $receiveTimeoutInMilliseconds = AmqpInboundChannelAdapterBuilder::DEFAULT_RECEIVE_TIMEOUT;
    /**
     * @var MediaType
     */
    private $defaultConversionMediaType;
    /**
     * @var AmqpOutboundChannelAdapterBuilder
     */
    private $amqpOutboundChannelAdapter;

    /**
     * AmqpBackedMessageChannelBuilder constructor.
     *
     * @param string $channelName
     * @param string $amqpConnectionReferenceName
     *
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    private function __construct(string $channelName, string $amqpConnectionReferenceName)
    {
        $this->channelName                 = $channelName;
        $this->amqpConnectionReferenceName = $amqpConnectionReferenceName;

        $this->amqpOutboundChannelAdapter = AmqpOutboundChannelAdapterBuilder::createForDefaultExchange($this->amqpConnectionReferenceName)
            ->withAutoDeclareOnSend(true)
            ->withDefaultRoutingKey($this->channelName)
            ->withHeaderMapper("*")
            ->withDefaultPersistentMode(true);
    }

    /**
     * @param string $channelName
     * @param string $amqpConnectionReferenceName
     *
     * @return AmqpBackedMessageChannelBuilder
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public static function create(string $channelName, string $amqpConnectionReferenceName)
    {
        return new self($channelName, $amqpConnectionReferenceName);
    }

    /**
     * How long it should try to receive message
     *
     * @param int $timeoutInMilliseconds
     *
     * @return AmqpBackedMessageChannelBuilder
     */
    public function withReceiveTimeout(int $timeoutInMilliseconds) : self
    {
        $this->receiveTimeoutInMilliseconds = $timeoutInMilliseconds;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isPollable(): bool
    {
        return true;
    }

    /**
     * @param string $mediaType
     *
     * @return AmqpBackedMessageChannelBuilder
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function withDefaultConversionMediaType(string $mediaType) : self
    {
        $this->defaultConversionMediaType = MediaType::parseMediaType($mediaType);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getMessageChannelName(): string
    {
        return $this->channelName;
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
            ->build(InMemoryChannelResolver::createEmpty(), $referenceSearchService);

        $inboundChannelAdapter = new AmqpInboundChannelAdapter(
            $amqpConnectionFactory,
            NullEntrypointGateway::create(),
            $amqpAdmin,
            true,
            $this->channelName,
            $this->receiveTimeoutInMilliseconds,
            AmqpAcknowledgementCallback::AUTO_ACK,
            DefaultHeaderMapper::createAllHeadersMapping()
        );

        return new AmqpBackendMessageChannel($inboundChannelAdapter, $amqpOutboundChannelAdapter);
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [$this->amqpConnectionReferenceName];
    }
}