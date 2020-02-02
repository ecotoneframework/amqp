<?php
declare(strict_types=1);

namespace Ecotone\Amqp;

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
use Exception;

/**
 * Class InboundEnqueueGatewayBuilder
 * @package Ecotone\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpInboundChannelAdapterBuilder extends EnqueueInboundChannelAdapterBuilder
{
    /**
     * @var string
     */
    private $amqpConnectionReferenceName;
    /**
     * @var string
     */
    private $queueName;

    private function __construct(string $endpointId, string $queueName, ?string $requestChannelName, string $amqpConnectionReferenceName)
    {
        $this->amqpConnectionReferenceName = $amqpConnectionReferenceName;
        $this->queueName = $queueName;
        $this->initialize($endpointId, $requestChannelName, $amqpConnectionReferenceName);
    }

    public static function createWith(string $endpointId, string $queueName, ?string $requestChannelName, string $amqpConnectionReferenceName = AmqpConnectionFactory::class): self
    {
        return new self($endpointId, $queueName, $requestChannelName, $amqpConnectionReferenceName);
    }

    /**
     * @return string
     */
    public function getQueueName(): string
    {
        return $this->queueName;
    }

    public function createInboundChannelAdapter(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, PollingMetadata $pollingMetadata): AmqpInboundChannelAdapter
    {
        /** @var AmqpAdmin $amqpAdmin */
        $amqpAdmin = $referenceSearchService->get(AmqpAdmin::REFERENCE_NAME);
        /** @var AmqpConnectionFactory $amqpConnectionFactory */
        $amqpConnectionFactory = $referenceSearchService->get($this->amqpConnectionReferenceName);

        $inboundChannelAdapter = new AmqpInboundChannelAdapter(
            CachedConnectionFactory::createFor(new AmqpReconnectableConnectionFactory($amqpConnectionFactory)),
            $this->buildGatewayFor($referenceSearchService, $channelResolver, $pollingMetadata),
            $amqpAdmin,
            true,
            $this->queueName,
            $this->receiveTimeoutInMilliseconds,
            new InboundMessageConverter($this->acknowledgeMode, AmqpHeader::HEADER_ACKNOWLEDGE, $this->headerMapper),
            false
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
            $this->createInboundChannelAdapter($channelResolver, $referenceSearchService, $pollingMetadata)
        );
    }
}