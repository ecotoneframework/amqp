<?php


namespace Ecotone\Dbal;


use Ecotone\Enqueue\EnqueueMessageChannelBuilder;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\MessageChannel;
use Enqueue\Dbal\DbalConnectionFactory;
use Ramsey\Uuid\Uuid;

class DbalBackedMessageChannelBuilder implements EnqueueMessageChannelBuilder
{
    /**
     * @var DbalInboundChannelAdapterBuilder
     */
    private $inboundChannelAdapter;
    /**
     * @var DbalOutboundChannelAdapterBuilder
     */
    private $outboundChannelAdapter;

    private function __construct(string $channelName, string $connectionReferenceName)
    {
        $this->inboundChannelAdapter = DbalInboundChannelAdapterBuilder::createWith(
            Uuid::uuid4()->toString(),
            $channelName,
            null,
            $connectionReferenceName
        );
        $this->outboundChannelAdapter = DbalOutboundChannelAdapterBuilder::create(
            $channelName,
            $connectionReferenceName
        );
        $this->withHeaderMapping("*");
    }

    public function withHeaderMapping(string $headerMapper): self
    {
        $this->inboundChannelAdapter->withHeaderMapper($headerMapper);
        $this->outboundChannelAdapter->withHeaderMapper($headerMapper);

        return $this;
    }

    public static function create(string $channelName, string $connectionReferenceName = DbalConnectionFactory::class): self
    {
        return new self($channelName, $connectionReferenceName);
    }

    public function withReceiveTimeout(int $timeoutInMilliseconds): self
    {
        $this->inboundChannelAdapter->withReceiveTimeout($timeoutInMilliseconds);

        return $this;
    }

    public function withDefaultTimeToLive(int $timeInMilliseconds): self
    {
        $this->outboundChannelAdapter->withDefaultTimeToLive($timeInMilliseconds);

        return $this;
    }

    public function withDefaultDeliveryDelay(int $timeInMilliseconds): self
    {
        $this->outboundChannelAdapter->withDefaultDeliveryDelay($timeInMilliseconds);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isPollable(): bool
    {
        return true;
    }

    public function withDefaultConversionMediaType(string $mediaType): self
    {
        $this->outboundChannelAdapter->withDefaultConversionMediaType($mediaType);

        return $this;
    }

    public function getDefaultConversionMediaType(): ?MediaType
    {
        return $this->outboundChannelAdapter->getDefaultConversionMediaType();
    }

    /**
     * @inheritDoc
     */
    public function getMessageChannelName(): string
    {
        return $this->inboundChannelAdapter->getQueueName();
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return array_merge($this->inboundChannelAdapter->getRequiredReferences(), $this->outboundChannelAdapter->getRequiredReferenceNames());
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): MessageChannel
    {
        $inMemoryChannelResolver = InMemoryChannelResolver::createEmpty();
        return new DbalBackedMessageChannel(
            $this->inboundChannelAdapter->buildInboundChannelAdapter($inMemoryChannelResolver, $referenceSearchService, PollingMetadata::create("")),
            $this->outboundChannelAdapter->build($inMemoryChannelResolver, $referenceSearchService)
        );
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return array_merge($this->inboundChannelAdapter->resolveRelatedInterfaces($interfaceToCallRegistry), $this->outboundChannelAdapter->resolveRelatedInterfaces($interfaceToCallRegistry));
    }
}