<?php


namespace Test\Ecotone\Dbal\Configuration;


use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\Dbal\Configuration\DbalPublisherModule;
use Ecotone\Dbal\Configuration\RegisterDbalPublisher;
use Ecotone\Dbal\DbalOutboundChannelAdapterBuilder;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\ApplicationConfiguration;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\InMemoryModuleMessaging;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Publisher;
use Ecotone\Messaging\Support\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionException;

class DbalPublisherModuleTest extends TestCase
{
    public function test_registering_single_amqp_publisher()
    {
        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(Publisher::class, Publisher::class, "send", Publisher::class)
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeaderBuilder::create("sourceMediaType", MessageHeaders::CONTENT_TYPE)
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(Publisher::class, Publisher::class, "sendWithMetadata", Publisher::class)
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeadersBuilder::create("metadata"),
                            GatewayHeaderBuilder::create("sourceMediaType", MessageHeaders::CONTENT_TYPE)
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(Publisher::class, Publisher::class, "convertAndSend", Publisher::class)
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeaderValueBuilder::create(MessageHeaders::CONTENT_TYPE, MediaType::APPLICATION_X_PHP)
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(Publisher::class, Publisher::class, "convertAndSendWithMetadata", Publisher::class)
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeadersBuilder::create("metadata"),
                            GatewayHeaderValueBuilder::create(MessageHeaders::CONTENT_TYPE, MediaType::APPLICATION_X_PHP)
                        ])
                )
                ->registerMessageHandler(
                    DbalOutboundChannelAdapterBuilder::create("queueName", "connection")
                        ->withEndpointId(Publisher::class . ".handler")
                        ->withInputChannelName(Publisher::class)
                        ->withAutoDeclareOnSend(false)
                        ->withHeaderMapper("ecotone.*")
                        ->withDefaultConversionMediaType(MediaType::APPLICATION_JSON)
                ),
            $this->prepareConfiguration(
                [
                    RegisterDbalPublisher::create(Publisher::class, "queueName", MediaType::APPLICATION_JSON, "connection")
                        ->withAutoDeclareQueueOnSend(false)
                        ->withHeaderMapper("ecotone.*")
                ]
            )
        );
    }

    /**
     * @return MessagingSystemConfiguration
     * @throws MessagingException
     * @throws AnnotationException
     * @throws ConfigurationException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    private function createMessagingSystemConfiguration(): Configuration
    {
        return MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());
    }

    /**
     * @param array $extensions
     *
     * @return MessagingSystemConfiguration
     * @throws MessagingException
     * @throws AnnotationException
     * @throws ConfigurationException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    private function prepareConfiguration(array $extensions): MessagingSystemConfiguration
    {
        $cqrsMessagingModule = DbalPublisherModule::create(InMemoryAnnotationRegistrationService::createEmpty());

        $extendedConfiguration = $this->createMessagingSystemConfiguration();
        $moduleReferenceSearchService = ModuleReferenceSearchService::createEmpty();

        $cqrsMessagingModule->prepare(
            $extendedConfiguration,
            $extensions,
            $moduleReferenceSearchService
        );

        return $extendedConfiguration;
    }

    public function test_registering_single_dbal_publisher_with_application_conversion_media_type()
    {
        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(Publisher::class, Publisher::class, "send", Publisher::class)
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeaderBuilder::create("sourceMediaType", MessageHeaders::CONTENT_TYPE)
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(Publisher::class, Publisher::class, "sendWithMetadata", Publisher::class)
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeadersBuilder::create("metadata"),
                            GatewayHeaderBuilder::create("sourceMediaType", MessageHeaders::CONTENT_TYPE)
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(Publisher::class, Publisher::class, "convertAndSend", Publisher::class)
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeaderValueBuilder::create(MessageHeaders::CONTENT_TYPE, MediaType::APPLICATION_X_PHP)
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(Publisher::class, Publisher::class, "convertAndSendWithMetadata", Publisher::class)
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeadersBuilder::create("metadata"),
                            GatewayHeaderValueBuilder::create(MessageHeaders::CONTENT_TYPE, MediaType::APPLICATION_X_PHP)
                        ])
                )
                ->registerMessageHandler(
                    DbalOutboundChannelAdapterBuilder::create("queueName", "connection")
                        ->withEndpointId(Publisher::class . ".handler")
                        ->withInputChannelName(Publisher::class)
                        ->withAutoDeclareOnSend(true)
                        ->withHeaderMapper("")
                        ->withDefaultConversionMediaType(MediaType::APPLICATION_JSON)
                ),
            $this->prepareConfiguration(
                [
                    RegisterDbalPublisher::create(Publisher::class, "queueName", MediaType::APPLICATION_JSON, "connection"),
                    ApplicationConfiguration::createWithDefaults()
                        ->withDefaultSerializationMediaType(MediaType::APPLICATION_JSON)
                ]
            )
        );
    }

    public function test_throwing_exception()
    {
        $this->expectException(ConfigurationException::class);

        $this->prepareConfiguration(
            [
                RegisterDbalPublisher::create("test", Publisher::class, MediaType::APPLICATION_JSON, "connection"),
                RegisterDbalPublisher::create("test", Publisher::class, MediaType::APPLICATION_JSON, "connection")
            ]
        );
    }
}