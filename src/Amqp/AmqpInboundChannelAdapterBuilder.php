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

    /**
     * InboundAmqpEnqueueGatewayBuilder constructor.
     * @param string $endpointId
     * @param string $queueName
     * @param string $requestChannelName
     * @param string $amqpConnectionReferenceName
     * @throws Exception
     */
    private function __construct(string $endpointId, string $queueName, string $requestChannelName, string $amqpConnectionReferenceName)
    {
        $this->amqpConnectionReferenceName = $amqpConnectionReferenceName;
        $this->queueName = $queueName;
        $this->initialize($endpointId, $requestChannelName, $amqpConnectionReferenceName);
    }

    /**
     * @param string $endpointId
     * @param string $queueName
     * @param string $requestChannelName
     * @param string $amqpConnectionReferenceName
     * @return AmqpInboundChannelAdapterBuilder
     * @throws Exception
     */
    public static function createWith(string $endpointId, string $queueName, string $requestChannelName, string $amqpConnectionReferenceName = AmqpConnectionFactory::class): self
    {
        return new self($endpointId, $queueName, $requestChannelName, $amqpConnectionReferenceName);
    }

    /**
     * @inheritDoc
     */
    protected function buildAdapter(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, PollingMetadata $pollingMetadata): ConsumerLifecycle
    {
        /** @var AmqpAdmin $amqpAdmin */
        $amqpAdmin = $referenceSearchService->get(AmqpAdmin::REFERENCE_NAME);
        /** @var AmqpConnectionFactory $amqpConnectionFactory */
        $amqpConnectionFactory = $referenceSearchService->get($this->amqpConnectionReferenceName);

        /** @var EntrypointGateway $gateway */
        $gateway = $this->inboundEntrypoint
            ->withErrorChannel($pollingMetadata->getErrorChannelName())
            ->build($referenceSearchService, $channelResolver);

        return TaskExecutorChannelAdapter::createFrom(
            $this->endpointId,
            $pollingMetadata->setFixedRateInMilliseconds(1),
            new AmqpInboundChannelAdapter(
                new CachedConnectionFactory(new AmqpReconnectableConnectionFactory($amqpConnectionFactory)),
                $gateway,
                $amqpAdmin,
                true,
                $this->queueName,
                $this->receiveTimeoutInMilliseconds,
                new InboundMessageConverter($this->acknowledgeMode, AmqpHeader::HEADER_ACKNOWLEDGE, $this->headerMapper),
                false
            )
        );
    }
}