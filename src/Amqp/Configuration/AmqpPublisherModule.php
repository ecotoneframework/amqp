<?php


namespace Ecotone\Amqp\Configuration;

use Ecotone\Amqp\AmqpOutboundChannelAdapterBuilder;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\ApplicationConfiguration;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagePublisher;

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
    public static function create(AnnotationFinder $annotationRegistrationService)
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

        /** @var AmqpMessagePublisherConfiguration $amqpPublisher */
        foreach ($extensionObjects as $amqpPublisher) {
            if (!($amqpPublisher instanceof AmqpMessagePublisherConfiguration)) {
                return;
            }

            if (in_array($amqpPublisher->getReferenceName(), $registeredReferences)) {
                throw ConfigurationException::create("Registering two publishers under same reference name {$amqpPublisher->getReferenceName()}. You need to create publisher with specific reference using `createWithReferenceName`.");
            }

            $registeredReferences[] = $amqpPublisher->getReferenceName();
            $mediaType = $amqpPublisher->getOutputDefaultConversionMediaType() ? $amqpPublisher->getOutputDefaultConversionMediaType() : $applicationConfiguration->getDefaultSerializationMediaType();

            $configuration = $configuration
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create($amqpPublisher->getReferenceName(), MessagePublisher::class, "send", $amqpPublisher->getReferenceName())
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeaderBuilder::create("sourceMediaType", MessageHeaders::CONTENT_TYPE)
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create($amqpPublisher->getReferenceName(), MessagePublisher::class, "sendWithMetadata", $amqpPublisher->getReferenceName())
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeadersBuilder::create("metadata"),
                            GatewayHeaderBuilder::create("sourceMediaType", MessageHeaders::CONTENT_TYPE)
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create($amqpPublisher->getReferenceName(), MessagePublisher::class, "convertAndSend", $amqpPublisher->getReferenceName())
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeaderValueBuilder::create(MessageHeaders::CONTENT_TYPE, MediaType::APPLICATION_X_PHP)
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create($amqpPublisher->getReferenceName(), MessagePublisher::class, "convertAndSendWithMetadata", $amqpPublisher->getReferenceName())
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
                        ->withDefaultPersistentMode($amqpPublisher->getDefaultPersistentDelivery())
                        ->withAutoDeclareOnSend($amqpPublisher->isAutoDeclareQueueOnSend())
                        ->withHeaderMapper($amqpPublisher->getHeaderMapper())
                        ->withDefaultRoutingKey($amqpPublisher->getDefaultRoutingKey())
                        ->withRoutingKeyFromHeader($amqpPublisher->getRoutingKeyFromHeader())
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
            $extensionObject instanceof AmqpMessagePublisherConfiguration
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