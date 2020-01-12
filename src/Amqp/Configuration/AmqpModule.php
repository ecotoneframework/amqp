<?php
declare(strict_types=1);

namespace Ecotone\Amqp\Configuration;

use Ecotone\Amqp\AmqpAdmin;
use Ecotone\Amqp\AmqpBackedMessageChannelBuilder;
use Ecotone\Amqp\AmqpBinding;
use Ecotone\Amqp\AmqpExchange;
use Ecotone\Amqp\AmqpQueue;
use Ecotone\Enqueue\EnqueueAcknowledgeConfirmationInterceptor;
use Ecotone\Enqueue\EnqueueBackendMessageChannelConsumer;
use Ecotone\Messaging\Annotation\InputOutputEndpointAnnotation;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\ApplicationConfiguration;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCall;

/**
 * Class AmqpModule
 * @package Ecotone\Amqp\Configuration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class AmqpModule implements AnnotationModule
{
    private function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService)
    {
        return new self();
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
        $amqpExchanges = [];
        $amqpQueues = [];
        $amqpBindings = [];

        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof AmqpBackedMessageChannelBuilder) {
                $amqpQueues[] = AmqpQueue::createWith($extensionObject->getMessageChannelName());
            }else if ($extensionObject instanceof AmqpExchange) {
                $amqpExchanges[] = $extensionObject;
            }else if ($extensionObject instanceof AmqpQueue) {
                $amqpQueues[] = $extensionObject;
            }else if ($extensionObject instanceof AmqpBinding) {
                $amqpBindings[] = $extensionObject;
            }
        }

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
    public function getRelatedReferences(): array
    {
        return [];
    }
}