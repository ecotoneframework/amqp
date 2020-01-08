<?php


namespace Ecotone\Dbal;


use Ecotone\Amqp\AmqpAdmin;
use Ecotone\Amqp\AmqpInboundChannelAdapter;
use Ecotone\Amqp\AmqpReconnectableConnectionFactory;
use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Enqueue\EnqueueInboundChannelAdapterBuilder;
use Ecotone\Enqueue\InboundMessageConverter;
use Ecotone\Messaging\Endpoint\ConsumerLifecycle;
use Ecotone\Messaging\Endpoint\EntrypointGateway;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Endpoint\TaskExecutorChannelAdapter\TaskExecutorChannelAdapter;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Enqueue\AmqpLib\AmqpConnectionFactory;
use Enqueue\Dbal\DbalConnectionFactory;
use Exception;

class DbalInboundChannelAdapterBuilder extends EnqueueInboundChannelAdapterBuilder
{
    /**
     * @var string
     */
    private $connectionReferenceName;
    /**
     * @var string
     */
    private $queueName;

    /**
     * InboundAmqpEnqueueGatewayBuilder constructor.
     * @param string $endpointId
     * @param string $queueName
     * @param string|null $requestChannelName
     * @param string $amqpConnectionReferenceName
     * @throws Exception
     */
    private function __construct(string $endpointId, string $queueName, ?string $requestChannelName, string $amqpConnectionReferenceName)
    {
        $this->connectionReferenceName = $amqpConnectionReferenceName;
        $this->queueName = $queueName;
        $this->initialize($endpointId, $requestChannelName, $amqpConnectionReferenceName);
    }

    /**
     * @return string
     */
    public function getQueueName(): string
    {
        return $this->queueName;
    }

    /**
     * @param string $endpointId
     * @param string $queueName
     * @param string|null $requestChannelName
     * @param string $connectionReferenceName
     * @return self
     * @throws Exception
     */
    public static function createWith(string $endpointId, string $queueName, ?string $requestChannelName, string $connectionReferenceName = DbalConnectionFactory::class): self
    {
        return new self($endpointId, $queueName, $requestChannelName, $connectionReferenceName);
    }

    public function buildInboundChannelAdapter(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, PollingMetadata $pollingMetadata): DbalInboundChannelAdapter
    {
        /** @var DbalConnectionFactory $connectionFactory */
        $connectionFactory = $referenceSearchService->get($this->connectionReferenceName);

        $inboundChannelAdapter = new DbalInboundChannelAdapter(
            new CachedConnectionFactory(new DbalReconnectableConnectionFactory($connectionFactory)),
            $this->buildGatewayFor($referenceSearchService, $channelResolver, $pollingMetadata),
            true,
            $this->queueName,
            $this->receiveTimeoutInMilliseconds,
            new InboundMessageConverter($this->acknowledgeMode, DbalHeader::HEADER_ACKNOWLEDGE, $this->headerMapper)
        );
        return $inboundChannelAdapter;
    }

    /**
     * @inheritDoc
     */
    protected function buildAdapter(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, PollingMetadata $pollingMetadata): ConsumerLifecycle
    {
        return TaskExecutorChannelAdapter::createFrom(
            $this->endpointId,
            $pollingMetadata->setFixedRateInMilliseconds(1),
            $this->buildInboundChannelAdapter($channelResolver, $referenceSearchService, $pollingMetadata)
        );
    }
}