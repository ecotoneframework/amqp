<?php

namespace Ecotone\Amqp\Publisher;

use Ecotone\Amqp\AmqpOutboundChannelAdapterBuilder;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagePublisher;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
class AmqpMessagePublisherModule implements AnnotationModule
{
    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $applicationConfiguration = ExtensionObjectResolver::resolveUnique(ServiceConfiguration::class, $extensionObjects, ServiceConfiguration::createWithDefaults());
        ;

        /** @var AmqpMessagePublisherConfiguration $amqpPublisher */
        foreach (ExtensionObjectResolver::resolve(AmqpMessagePublisherConfiguration::class, $extensionObjects) as $amqpPublisher) {
            $mediaType = $amqpPublisher->getOutputDefaultConversionMediaType() ? $amqpPublisher->getOutputDefaultConversionMediaType() : $applicationConfiguration->getDefaultSerializationMediaType();

            $messagingConfiguration = $messagingConfiguration
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create($amqpPublisher->getReferenceName(), MessagePublisher::class, 'send', $amqpPublisher->getReferenceName())
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create('data'),
                            GatewayHeaderBuilder::create('sourceMediaType', MessageHeaders::CONTENT_TYPE),
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create($amqpPublisher->getReferenceName(), MessagePublisher::class, 'sendWithMetadata', $amqpPublisher->getReferenceName())
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create('data'),
                            GatewayHeadersBuilder::create('metadata'),
                            GatewayHeaderBuilder::create('sourceMediaType', MessageHeaders::CONTENT_TYPE),
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create($amqpPublisher->getReferenceName(), MessagePublisher::class, 'convertAndSend', $amqpPublisher->getReferenceName())
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create('data'),
                            GatewayHeaderValueBuilder::create(MessageHeaders::CONTENT_TYPE, MediaType::APPLICATION_X_PHP),
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create($amqpPublisher->getReferenceName(), MessagePublisher::class, 'convertAndSendWithMetadata', $amqpPublisher->getReferenceName())
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create('data'),
                            GatewayHeadersBuilder::create('metadata'),
                            GatewayHeaderValueBuilder::create(MessageHeaders::CONTENT_TYPE, MediaType::APPLICATION_X_PHP),
                        ])
                )
                ->registerMessageHandler(
                    AmqpOutboundChannelAdapterBuilder::create($amqpPublisher->getExchangeName(), $amqpPublisher->getConnectionReference())
                        ->withEndpointId($amqpPublisher->getReferenceName() . '.handler')
                        ->withInputChannelName($amqpPublisher->getReferenceName())
                        ->withDefaultPersistentMode($amqpPublisher->getDefaultPersistentDelivery())
                        ->withAutoDeclareOnSend($amqpPublisher->isAutoDeclareOnSend())
                        ->withHeaderMapper($amqpPublisher->getHeaderMapper())
                        ->withDefaultRoutingKey($amqpPublisher->getDefaultRoutingKey())
                        ->withRoutingKeyFromHeader($amqpPublisher->getRoutingKeyFromHeader())
                        ->withDefaultConversionMediaType($mediaType)
                );
        }
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return
            $extensionObject instanceof AmqpMessagePublisherConfiguration
            || $extensionObject instanceof ServiceConfiguration;
    }

    public function getModuleExtensions(ServiceConfiguration $serviceConfiguration, array $serviceExtensions): array
    {
        return [];
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::AMQP_PACKAGE;
    }
}
