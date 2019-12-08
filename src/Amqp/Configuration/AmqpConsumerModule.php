<?php


namespace Ecotone\Amqp\Configuration;

use Ecotone\Amqp\AmqpInboundChannelAdapterBuilder;
use Ecotone\Amqp\Annotation\AmqpChannelAdapter;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Config\Configuration;
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
        $amqpConsumers = $annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, AmqpChannelAdapter::class);

        $amqpInboundChannelAdapter = [];
        $serviceActivators = [];

        foreach ($amqpConsumers as $amqpConsumer) {
            /** @var MessageEndpoint $messageEndpoint */
            $messageEndpoint = $amqpConsumer->getAnnotationForClass();

            $reference = $messageEndpoint->referenceName ?? $amqpConsumer->getClassName();
            /** @var AmqpChannelAdapter $amqpConsumerAnnotation */
            $amqpConsumerAnnotation = $amqpConsumer->getAnnotationForMethod();

            $endpointId = $amqpConsumerAnnotation->endpointId;
            $amqpInboundChannelAdapter[] = AmqpInboundChannelAdapterBuilder::createWith(
                $endpointId,
                $amqpConsumerAnnotation->queueName,
                $endpointId,
                $amqpConsumerAnnotation->amqpConnectionReferenceName
            );

            $serviceActivators[] = ServiceActivatorBuilder::create($reference, $amqpConsumer->getMethodName())
                                    ->withEndpointId($endpointId . ".target")
                                    ->withInputChannelName($endpointId)
                                    ->withMethodParameterConverters($annotationParameterBuilder->createParameterConverters(
                                        InterfaceToCall::create($amqpConsumer->getClassName(), $amqpConsumer->getMethodName()),
                                        $amqpConsumerAnnotation->parameterConverters
                                    ));
        }

        return new self($amqpInboundChannelAdapter, $serviceActivators);
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
        foreach ($this->amqpInboundChannelAdapters as $amqpInboundChannelAdapter) {
            $configuration->registerConsumer($amqpInboundChannelAdapter);
        }
        foreach ($this->serviceActivators as $serviceActivator) {
            $configuration->registerMessageHandler($serviceActivator);
        }
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getRelatedReferences(): array
    {
        return [];
    }
}