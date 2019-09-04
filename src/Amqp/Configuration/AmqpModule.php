<?php
declare(strict_types=1);

namespace Ecotone\Amqp\Configuration;

use Ecotone\Amqp\AmqpAcknowledgeConfirmationInterceptor;
use Ecotone\Amqp\AmqpAdmin;
use Ecotone\Amqp\AmqpBackedMessageChannelBuilder;
use Ecotone\Amqp\AmqpBackendMessageChannelConsumer;
use Ecotone\Amqp\AmqpBinding;
use Ecotone\Amqp\AmqpExchange;
use Ecotone\Amqp\AmqpQueue;
use Ecotone\Messaging\Annotation\InputOutputEndpointAnnotation;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;

/**
 * Class AmqpModule
 * @package Ecotone\Amqp\Configuration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class AmqpModule implements AnnotationModule
{
    /**
     * @var string[]
     */
    private $inputChannelEndpointMapping = [];

    /**
     * AmqpModule constructor.
     * @param string[] $inputChannelEndpointMapping
     */
    private function __construct(array $inputChannelEndpointMapping)
    {
        $this->inputChannelEndpointMapping = $inputChannelEndpointMapping;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService)
    {
        $inputChannelEndpointMapping = [];
        $handlers = $annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, InputOutputEndpointAnnotation::class);

        foreach ($handlers as $handler) {
            /** @var InputOutputEndpointAnnotation $methodAnnotation */
            $methodAnnotation = $handler->getAnnotationForMethod();

            $inputChannelEndpointMapping[$methodAnnotation->inputChannelName][] = $methodAnnotation->endpointId;
        }

        return new self($inputChannelEndpointMapping);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "amqpModule";
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        $publishSubscribeExchanges = [];
        $amqpExchanges = [];
        $amqpQueues = [];
        $amqpBindings = [];

        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof AmqpBackedMessageChannelBuilder) {
                if ($extensionObject->isPublishSubscribe()) {
                    $publishSubscribeExchanges[] = $extensionObject->getMessageChannelName();
                    $amqpExchanges[] = AmqpExchange::createFanoutExchange(AmqpBackedMessageChannelBuilder::PUBLISH_SUBSCRIBE_EXCHANGE_NAME_PREFIX . $extensionObject->getMessageChannelName());
                }else {
                    $amqpQueues[] = AmqpQueue::createWith($extensionObject->getMessageChannelName());
                }
            }else if ($extensionObject instanceof AmqpExchange) {
                $amqpExchanges[] = $extensionObject;
            }else if ($extensionObject instanceof AmqpQueue) {
                $amqpQueues[] = $extensionObject;
            }else if ($extensionObject instanceof AmqpBinding) {
                $amqpBindings[] = $extensionObject;
            }
        }
        foreach ($this->inputChannelEndpointMapping as $inputChannelName => $endpoints) {
            if (in_array($inputChannelName, $publishSubscribeExchanges)) {
                foreach ($endpoints as $endpoint) {
                    $queueName = $inputChannelName . "." . $endpoint;
                    $amqpQueues[] = AmqpQueue::createWith($queueName);
                    $amqpBindings[] = AmqpBinding::createFromNamesWithoutRoutingKey(
                        AmqpBackedMessageChannelBuilder::PUBLISH_SUBSCRIBE_EXCHANGE_NAME_PREFIX . $inputChannelName,
                        $queueName
                    );
                }
            }
        }

        $configuration->registerRelatedInterfaces([InterfaceToCall::create(AmqpAcknowledgeConfirmationInterceptor::class, "ack")]);
        $configuration->registerConsumerFactory(new AmqpBackendMessageChannelConsumer());
        $moduleReferenceSearchService->store(AmqpAdmin::REFERENCE_NAME, AmqpAdmin::createWith(
            $amqpExchanges, $amqpQueues, $amqpBindings
        ));
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return
            $extensionObject instanceof AmqpBackedMessageChannelBuilder
            || $extensionObject instanceof AmqpExchange
            || $extensionObject instanceof AmqpQueue
            || $extensionObject instanceof AmqpBinding;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [];
    }
}