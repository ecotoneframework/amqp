<?php
declare(strict_types=1);

namespace Ecotone\Amqp;

use Ecotone\Messaging\Endpoint\ConsumerLifecycle;
use Ecotone\Messaging\Endpoint\EntrypointGateway;
use Ecotone\Messaging\Endpoint\InterceptedChannelAdapterBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Endpoint\TaskExecutorChannelAdapter\TaskExecutorChannelAdapter;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;
use Ecotone\Messaging\MessageConverter\HeaderMapper;
use Exception;
use Interop\Amqp\AmqpConnectionFactory;
use Ramsey\Uuid\Uuid;

/**
 * Class InboundEnqueueGatewayBuilder
 * @package Ecotone\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpInboundChannelAdapterBuilder extends InterceptedChannelAdapterBuilder
{
    const DEFAULT_RECEIVE_TIMEOUT = 10000;

    /**
     * @var string
     */
    private $amqpConnectionReferenceName;
    /**
     * @var AmqpBinding[]
     */
    private $bindings;
    /**
     * @var string
     */
    private $queueName;
    /**
     * @var string
     */
    private $endpointId;
    /**
     * @var int
     */
    private $receiveTimeoutInMilliseconds = self::DEFAULT_RECEIVE_TIMEOUT;
    /**
     * @var HeaderMapper
     */
    private $headerMapper;
    /**
     * @var string
     */
    private $acknowledgeMode = AmqpAcknowledgementCallback::AUTO_ACK;
    /**
     * @var EntrypointGateway
     */
    private $inboundEntrypoint;

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
        $this->endpointId = $endpointId;
        $this->amqpConnectionReferenceName = $amqpConnectionReferenceName;
        $this->queueName = $queueName;
        $this->headerMapper = DefaultHeaderMapper::createNoMapping();
        $this->inboundEntrypoint = GatewayProxyBuilder::create($endpointId, EntrypointGateway::class, "executeEntrypoint", $requestChannelName);
        $this->addAroundInterceptor(AmqpAcknowledgeConfirmationInterceptor::createAroundInterceptor($endpointId));
    }

    /**
     * @inheritDoc
     */
    public function addAroundInterceptor(AroundInterceptorReference $aroundInterceptorReference)
    {
        $this->inboundEntrypoint->addAroundInterceptor($aroundInterceptorReference);

        return $this;
    }

//    /**
//     * @param string $exchangeName
//     * @param string $routingKey
//     * @return AmqpInboundChannelAdapterBuilder
//     * @throws \Ecotone\Messaging\MessagingException
//     */
//    public function withBinding(string $exchangeName, string $routingKey) : self
//    {
//        $this->bindings[] = AmqpBinding::createFromNames($exchangeName, $this->queueName, $routingKey);
//
//        return $this;
//    }

    /**
     * @param string $endpointId
     * @param string $queueName
     * @param string $requestChannelName
     * @param string $amqpConnectionReferenceName
     * @return AmqpInboundChannelAdapterBuilder
     * @throws Exception
     */
    public static function createWith(string $endpointId, string $queueName, string $requestChannelName, string $amqpConnectionReferenceName): self
    {
        return new self($endpointId, $queueName, $requestChannelName, $amqpConnectionReferenceName);
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        $resolvedInterfaces = $this->inboundEntrypoint->resolveRelatedInterfaces($interfaceToCallRegistry);
        $resolvedInterfaces[] = $interfaceToCallRegistry->getFor(EntrypointGateway::class, 'executeEntrypoint');

        return $resolvedInterfaces;
    }

    /**
     * @return string
     */
    public function getEndpointId(): string
    {
        return $this->endpointId;
    }

    /**
     * @param string $headerMapper
     * @return AmqpInboundChannelAdapterBuilder
     */
    public function withHeaderMapper(string $headerMapper): self
    {
        $this->headerMapper = DefaultHeaderMapper::createWith(explode(",", $headerMapper), []);

        return $this;
    }

    /**
     * How long it should try to receive message
     *
     * @param int $timeoutInMilliseconds
     * @return AmqpInboundChannelAdapterBuilder
     */
    public function withReceiveTimeout(int $timeoutInMilliseconds): self
    {
        $this->receiveTimeoutInMilliseconds = $timeoutInMilliseconds;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return array_merge([$this->amqpConnectionReferenceName], $this->inboundEntrypoint->getRequiredReferences());
    }

    /**
     * @inheritDoc
     */
    public function addBeforeInterceptor(MethodInterceptor $methodInterceptor)
    {
        $this->inboundEntrypoint->addBeforeInterceptor($methodInterceptor);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addAfterInterceptor(MethodInterceptor $methodInterceptor)
    {
        $this->inboundEntrypoint->addAfterInterceptor($methodInterceptor);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $this->inboundEntrypoint->getInterceptedInterface($interfaceToCallRegistry);
    }

    /**
     * @inheritDoc
     */
    public function withEndpointAnnotations(iterable $endpointAnnotations)
    {
        $this->inboundEntrypoint->withEndpointAnnotations($endpointAnnotations);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getEndpointAnnotations(): array
    {
        return $this->inboundEntrypoint->getEndpointAnnotations();
    }

    /**
     * @inheritDoc
     */
    public function getRequiredInterceptorNames(): iterable
    {
        return $this->inboundEntrypoint->getRequiredInterceptorNames();
    }

    /**
     * @inheritDoc
     */
    public function withRequiredInterceptorNames(iterable $interceptorNames)
    {
        $this->inboundEntrypoint->withRequiredInterceptorNames($interceptorNames);

        return $this;
    }

    public function __toString()
    {
        return "Inbound Amqp Adapter with id " . $this->endpointId;
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
                $amqpConnectionFactory,
                $gateway,
                $amqpAdmin,
                true,
                $this->queueName,
                $this->receiveTimeoutInMilliseconds,
                $this->acknowledgeMode,
                $this->headerMapper,
                false
            )
        );
    }
}