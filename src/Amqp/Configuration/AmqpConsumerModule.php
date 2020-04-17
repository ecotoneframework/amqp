<?php


namespace Ecotone\Amqp\Configuration;

use Ecotone\Amqp\AmqpInboundChannelAdapterBuilder;
use Ecotone\Amqp\Annotation\AmqpChannelAdapter;
use Ecotone\Messaging\Annotation\Consumer;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\OptionalReference;
use Ecotone\Messaging\Config\RequiredReference;
use Ecotone\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ramsey\Uuid\Uuid;

/**
 * Class AmqpConsumerModule
 * @package Ecotone\Amqp\Configuration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class AmqpConsumerModule implements AnnotationModule
{
    /**
     * @var AmqpInboundChannelAdapterBuilder[]
     */
    private $amqpInboundChannelAdapters = [];
    /**
     * @var ServiceActivatorBuilder[]
     */
    private $serviceActivators = [];



    /**
     * AmqpConsumerModule constructor.
     * @param AmqpInboundChannelAdapterBuilder[] $amqpInboundChannelAdapters
     * @param ServiceActivatorBuilder[] $serviceActivators
     */
    private function __construct(array $amqpInboundChannelAdapters, array $serviceActivators)
    {
        $this->amqpInboundChannelAdapters = $amqpInboundChannelAdapters;
        $this->serviceActivators = $serviceActivators;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService)
    {
        $annotationParameterBuilder = ParameterConverterAnnotationFactory::create();
        $amqpConsumers = $annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, Consumer::class);

        $amqpInboundChannelAdapters = [];
        $serviceActivators = [];

        foreach ($amqpConsumers as $amqpConsumer) {
            /** @var MessageEndpoint $messageEndpoint */
            $messageEndpoint = $amqpConsumer->getAnnotationForClass();

            $reference = $messageEndpoint->referenceName ?? $amqpConsumer->getClassName();
            /** @var Consumer $amqpConsumerAnnotation */
            $amqpConsumerAnnotation = $amqpConsumer->getAnnotationForMethod();

            $endpointId = $amqpConsumerAnnotation->endpointId;
            $serviceActivators[$endpointId] = ServiceActivatorBuilder::create($reference, $amqpConsumer->getMethodName())
                ->withEndpointId($endpointId . ".target")
                ->withInputChannelName($endpointId)
                ->withMethodParameterConverters($annotationParameterBuilder->createParameterConvertersWithReferences(
                    InterfaceToCall::create($amqpConsumer->getClassName(), $amqpConsumer->getMethodName()),
                    $amqpConsumerAnnotation->parameterConverters,
                    $amqpConsumer,
                    false
                ));
        }

        return new self($amqpInboundChannelAdapters, $serviceActivators);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "amqpConsumerModule";
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        /** @var AmqpConsumerConfiguration $extensionObject */
        foreach ($extensionObjects as $extensionObject) {
            $inboundChannelAdapter = AmqpInboundChannelAdapterBuilder::createWith(
                $extensionObject->getEndpointId(),
                $extensionObject->getQueueName(),
                $extensionObject->getEndpointId(),
                $extensionObject->getAmqpConnectionReferenceName()
            )
                ->withHeaderMapper($extensionObject->getHeaderMapper())
                ->withReceiveTimeout($extensionObject->getReceiveTimeoutInMilliseconds());

            $configuration->registerConsumer($inboundChannelAdapter);

            if (!array_key_exists($extensionObject->getEndpointId(), $this->serviceActivators)) {
                throw ConfigurationException::create("Lack of Consumer defined under endpoint id {$extensionObject->getEndpointId()}");
            }

            $configuration->registerMessageHandler($this->serviceActivators[$extensionObject->getEndpointId()]);
        }
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return $extensionObject instanceof AmqpConsumerConfiguration;
    }

    /**
     * @inheritDoc
     */
    public function getRelatedReferences(): array
    {
        return [];
    }
}