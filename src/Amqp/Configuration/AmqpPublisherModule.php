<?php


namespace Ecotone\Amqp\Configuration;

use Ecotone\Amqp\AmqpOutboundChannelAdapterBuilder;
use Ecotone\Amqp\AmqpPublisher;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\ApplicationConfiguration;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\OptionalReference;
use Ecotone\Messaging\Config\RequiredReference;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Publisher;

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
        /** @var ApplicationConfiguration $applicationConfiguration */
        $applicationConfiguration = null;
        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof ApplicationConfiguration) {
                $applicationConfiguration = $extensionObject;
                break;
            }
        }

        /** @var RegisterAmqpPublisher $amqpPublisher */
        foreach ($extensionObjects as $amqpPublisher) {
            if (!($amqpPublisher instanceof RegisterAmqpPublisher)) {
                return;
            }

            if (in_array($amqpPublisher->getReferenceName(), $registeredReferences)) {
                throw ConfigurationException::create("Registering two publishers under same reference name {$amqpPublisher->getReferenceName()}. You need to create publisher with specific reference using `createWithReferenceName`.");
            }

            $registeredReferences[] = $amqpPublisher->getReferenceName();
            $mediaType = $amqpPublisher->getOutputDefaultConversionMediaType() ? $amqpPublisher->getOutputDefaultConversionMediaType() : $applicationConfiguration->getDefaultSerializationMediaType();

            $configuration = $configuration
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create($amqpPublisher->getReferenceName(), Publisher::class, "send", $amqpPublisher->getReferenceName())
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeaderBuilder::create("sourceMediaType", MessageHeaders::CONTENT_TYPE)
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create($amqpPublisher->getReferenceName(), Publisher::class, "sendWithMetadata", $amqpPublisher->getReferenceName())
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeadersBuilder::create("metadata"),
                            GatewayHeaderBuilder::create("sourceMediaType", MessageHeaders::CONTENT_TYPE)
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create($amqpPublisher->getReferenceName(), Publisher::class, "convertAndSend", $amqpPublisher->getReferenceName())
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeaderValueBuilder::create(MessageHeaders::CONTENT_TYPE, MediaType::APPLICATION_X_PHP)
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create($amqpPublisher->getReferenceName(), Publisher::class, "convertAndSendWithMetadata", $amqpPublisher->getReferenceName())
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeadersBuilder::create("metadata"),
                            GatewayHeaderValueBuilder::create(MessageHeaders::CONTENT_TYPE, MediaType::APPLICATION_X_PHP)
                        ])
                )
                ->registerMessageHandler(
                    AmqpOutboundChannelAdapterBuilder::create($amqpPublisher->getExchangeName(), $amqpPublisher->getAmqpConnectionReference())
                        ->withEndpointId($amqpPublisher->getReferenceName() . ".handler")
                        ->withInputChannelName($amqpPublisher->getReferenceName())
                        ->withRoutingKeyFromHeader("amqpRouting")
                        ->withDefaultPersistentMode(true)
                        ->withAutoDeclareOnSend($amqpPublisher->isAutoDeclareQueueOnSend())
                        ->withHeaderMapper($amqpPublisher->getHeaderMapper())
                        ->withDefaultConversionMediaType($mediaType)
                )
                ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel($amqpPublisher->getReferenceName()));
        }
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return
            $extensionObject instanceof RegisterAmqpPublisher
            || $extensionObject instanceof ApplicationConfiguration;
    }

    /**
     * @inheritDoc
     */
    public function getRelatedReferences(): array
    {
        return [];
    }
}