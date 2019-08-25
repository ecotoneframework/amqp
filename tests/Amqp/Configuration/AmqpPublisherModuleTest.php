<?php


namespace Test\Ecotone\Amqp\Configuration;


use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\Amqp\AmqpOutboundChannelAdapterBuilder;
use Ecotone\Amqp\AmqpPublisher;
use Ecotone\Amqp\Configuration\AmqpPublisherModule;
use Ecotone\Amqp\Configuration\RegisterAmqpPublisher;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\InMemoryModuleMessaging;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionException;

class AmqpPublisherModuleTest extends TestCase
{
    public function test_registering_single_amqp_publisher()
    {
        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(AmqpPublisher::class, AmqpPublisher::class, "send", AmqpPublisher::class)
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeaderBuilder::create("sourceMediaType", MessageHeaders::CONTENT_TYPE),
                            GatewayHeaderBuilder::create("routing", "amqpSendRouting")
                        ]),
                    GatewayProxyBuilder::create(AmqpPublisher::class, AmqpPublisher::class, "sendWithMetadata", AmqpPublisher::class)
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeadersBuilder::create("metadata"),
                            GatewayHeaderBuilder::create("sourceMediaType", MessageHeaders::CONTENT_TYPE),
                            GatewayHeaderBuilder::create("routing", "amqpSendRouting"),
                        ]),
                    GatewayProxyBuilder::create(AmqpPublisher::class, AmqpPublisher::class, "convertAndSend", AmqpPublisher::class)
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeaderBuilder::create("routing", "amqpSendRouting")
                        ]),
                    GatewayProxyBuilder::create(AmqpPublisher::class, AmqpPublisher::class, "convertAndSendWithMetadata", AmqpPublisher::class)
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeadersBuilder::create("metadata"),
                            GatewayHeaderBuilder::create("routing", "amqpSendRouting")
                        ]),
                    AmqpOutboundChannelAdapterBuilder::create("exchangeName", "amqpConnection")
                        ->withRoutingKeyFromHeader("amqpSendRouting")
                        ->withDefaultPersistentMode(true)
                ),
            $this->prepareConfiguration(
                [
                    RegisterAmqpPublisher::create("amqpConnection", AmqpPublisher::class, "exchangeName", MediaType::APPLICATION_JSON)
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
        return MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty());
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
        $cqrsMessagingModule = AmqpPublisherModule::create(InMemoryAnnotationRegistrationService::createEmpty());

        $extendedConfiguration = $this->createMessagingSystemConfiguration();
        $moduleReferenceSearchService = ModuleReferenceSearchService::createEmpty();

        $cqrsMessagingModule->prepare(
            $extendedConfiguration,
            $extensions,
            $moduleReferenceSearchService
        );

        return $extendedConfiguration;
    }

    public function test_throwing_exception()
    {
        $this->expectException(ConfigurationException::class);

        $this->prepareConfiguration(
            [
                RegisterAmqpPublisher::create("amqpConnection", "test", MediaType::APPLICATION_JSON, AmqpPublisher::class),
                RegisterAmqpPublisher::create("amqpConnection", "test", MediaType::APPLICATION_JSON, AmqpPublisher::class)
            ]
        );
    }
}