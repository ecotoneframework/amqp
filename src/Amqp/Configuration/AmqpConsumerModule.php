<?php


namespace Ecotone\Amqp\Configuration;

use Ecotone\Amqp\AmqpInboundChannelAdapterBuilder;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Annotation\MessageConsumer;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;

#[ModuleAnnotation]
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
    public static function create(AnnotationFinder $annotationRegistrationService): static
    {
        $annotationParameterBuilder = ParameterConverterAnnotationFactory::create();
        $amqpConsumers = $annotationRegistrationService->findAnnotatedMethods(MessageConsumer::class);

        $amqpInboundChannelAdapters = [];
        $serviceActivators = [];

        foreach ($amqpConsumers as $amqpConsumer) {
            $reference = AnnotatedDefinitionReference::getReferenceFor($amqpConsumer);
            /** @var MessageConsumer $amqpConsumerAnnotation */
            $amqpConsumerAnnotation = $amqpConsumer->getAnnotationForMethod();

            $endpointId = $amqpConsumerAnnotation->getEndpointId();
            $serviceActivators[$endpointId] = ServiceActivatorBuilder::create($reference, $amqpConsumer->getMethodName())
                ->withEndpointId($endpointId . ".target")
                ->withInputChannelName($endpointId)
                ->withMethodParameterConverters($annotationParameterBuilder->createParameterWithDefaults(
                    InterfaceToCall::create($amqpConsumer->getClassName(), $amqpConsumer->getMethodName()),
                    false
                ));
        }

        return new self($amqpInboundChannelAdapters, $serviceActivators);
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        /** @var AmqpMessageConsumerConfiguration $extensionObject */
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
        return $extensionObject instanceof AmqpMessageConsumerConfiguration;
    }

    /**
     * @inheritDoc
     */
    public function getRelatedReferences(): array
    {
        return [];
    }
}