<?php


namespace Ecotone\Amqp\Configuration;

use Ecotone\Amqp\AmqpOutboundChannelAdapterBuilder;
use Ecotone\Amqp\AmqpPublisher;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\RequiredReference;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\MessageHeaders;

/**
 * Class AmqpPublisherModule
 * @package Ecotone\Amqp\Configuration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class AmqpPublisherModule implements AnnotationModule
{
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
        return "amqpPublisherModule";
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        $registeredReferences = [];
        /** @var RegisterAmqpPublisher $amqpPublisher */
        foreach ($extensionObjects as $amqpPublisher) {
            if (in_array($amqpPublisher->getReferenceName(), $registeredReferences)) {
                throw ConfigurationException::create("Registering two publishers under same reference name {$amqpPublisher->getReferenceName()}. You need to create publisher with specific reference using `createWithReferenceName`.");
            }

            $registeredReferences[] = $amqpPublisher->getReferenceName();
            $configuration = $configuration
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create($amqpPublisher->getReferenceName(), AmqpPublisher::class, "send", $amqpPublisher->getReferenceName())
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeaderBuilder::create("sourceMediaType", MessageHeaders::CONTENT_TYPE),
                            GatewayHeaderBuilder::create("routing", "amqpSendRouting")
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create($amqpPublisher->getReferenceName(), AmqpPublisher::class, "sendWithMetadata", $amqpPublisher->getReferenceName())
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeadersBuilder::create("metadata"),
                            GatewayHeaderBuilder::create("sourceMediaType", MessageHeaders::CONTENT_TYPE),
                            GatewayHeaderBuilder::create("routing", "amqpSendRouting"),
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create($amqpPublisher->getReferenceName(), AmqpPublisher::class, "convertAndSend", $amqpPublisher->getReferenceName())
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeaderBuilder::create("routing", "amqpSendRouting"),
                            GatewayHeaderValueBuilder::create(MessageHeaders::CONTENT_TYPE, MediaType::APPLICATION_X_PHP_OBJECT)
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create($amqpPublisher->getReferenceName(), AmqpPublisher::class, "convertAndSendWithMetadata", $amqpPublisher->getReferenceName())
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeadersBuilder::create("metadata"),
                            GatewayHeaderBuilder::create("routing", "amqpSendRouting"),
                            GatewayHeaderValueBuilder::create(MessageHeaders::CONTENT_TYPE, MediaType::APPLICATION_X_PHP_OBJECT)
                        ])
                )
                ->registerMessageHandler(
                    AmqpOutboundChannelAdapterBuilder::create($amqpPublisher->getExchangeName(), $amqpPublisher->getAmqpConnectionReference())
                        ->withEndpointId($amqpPublisher->getReferenceName() . ".handler")
                        ->withInputChannelName($amqpPublisher->getReferenceName())
                        ->withRoutingKeyFromHeader("amqpSendRouting")
                        ->withDefaultPersistentMode(true)
                        ->withAutoDeclareOnSend($amqpPublisher->isAutoDeclareQueueOnSend())
                )
                ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel($amqpPublisher->getReferenceName()));
        }
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return $extensionObject instanceof RegisterAmqpPublisher;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [];
    }
}