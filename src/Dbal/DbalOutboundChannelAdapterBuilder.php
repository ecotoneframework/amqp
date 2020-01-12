<?php


namespace Ecotone\Dbal;


use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Enqueue\EnqueueOutboundChannelAdapterBuilder;
use Ecotone\Enqueue\OutboundMessageConverter;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\MessageHandler;
use Enqueue\Dbal\DbalConnectionFactory;

class DbalOutboundChannelAdapterBuilder extends EnqueueOutboundChannelAdapterBuilder
{
    /**
     * @var string
     */
    private $queueName;
    /**
     * @var string
     */
    private $connectionFactoryReferenceName;

    private function __construct(string $queueName, string $connectionFactoryReferenceName)
    {
        $this->initialize($connectionFactoryReferenceName);
        $this->queueName = $queueName;
        $this->connectionFactoryReferenceName = $connectionFactoryReferenceName;
    }

    public static function create(string $queueName, string $connectionFactoryReferenceName = DbalConnectionFactory::class): self
    {
        return new self($queueName, $connectionFactoryReferenceName);
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        /** @var DbalConnectionFactory $amqpConnectionFactory */
        $amqpConnectionFactory = $referenceSearchService->get($this->connectionFactoryReferenceName);
        /** @var ConversionService $conversionService */
        $conversionService = $referenceSearchService->get(ConversionService::REFERENCE_NAME);

        return new DbalOutboundChannelAdapter(
            new CachedConnectionFactory(new DbalReconnectableConnectionFactory($amqpConnectionFactory)),
            $this->queueName,
            $this->autoDeclare,
            new OutboundMessageConverter($this->headerMapper, $conversionService, $this->defaultConversionMediaType, $this->defaultDeliveryDelay, $this->defaultTimeToLive)
        );
    }
}