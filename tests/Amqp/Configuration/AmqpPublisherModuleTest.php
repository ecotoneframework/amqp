<?php


namespace Test\Ecotone\Amqp\Configuration;


use Ecotone\Amqp\AmqpAdmin;
use Ecotone\Amqp\AmqpBackedMessageChannelBuilder;
use Ecotone\Amqp\AmqpOutboundChannelAdapterBuilder;
use Ecotone\Amqp\AmqpPublisher;
use Ecotone\Amqp\AmqpQueue;
use Ecotone\Amqp\Configuration\AmqpModule;
use Ecotone\Amqp\Configuration\AmqpPublisherModule;
use Ecotone\Amqp\Configuration\RegisterAmqpPublisher;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
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
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use PHPUnit\Framework\TestCase;

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
                            GatewayHeaderBuilder::create("exchange", "amqpSendExchange"),
                            GatewayHeaderBuilder::create("routing", "amqpSendRouting")
                        ]),
                    GatewayProxyBuilder::create(AmqpPublisher::class, AmqpPublisher::class, "sendWithMetadata", AmqpPublisher::class)
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeadersBuilder::create("metadata"),
                            GatewayHeaderBuilder::create("sourceMediaType", MessageHeaders::CONTENT_TYPE),
                            GatewayHeaderBuilder::create("exchange", "amqpSendExchange"),
                            GatewayHeaderBuilder::create("routing", "amqpSendRouting"),
                        ]),
                    GatewayProxyBuilder::create(AmqpPublisher::class, AmqpPublisher::class, "convertAndSend", AmqpPublisher::class)
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeaderBuilder::create("exchange", "amqpSendExchange"),
                            GatewayHeaderBuilder::create("routing", "amqpSendRouting")
                        ]),
                    GatewayProxyBuilder::create(AmqpPublisher::class, AmqpPublisher::class, "convertAndSendWithMetadata", AmqpPublisher::class)
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeadersBuilder::create("metadata"),
                            GatewayHeaderBuilder::create("exchange", "amqpSendExchange"),
                            GatewayHeaderBuilder::create("routing", "amqpSendRouting")
                        ]),
                    AmqpOutboundChannelAdapterBuilder::createForDefaultExchange("amqpConnection")
                        ->withRoutingKeyFromHeader("amqpSendRouting")
                        ->withExchangeFromHeader("amqpSendExchange")
                        ->withDefaultPersistentMode(true)
                ),
            $this->prepareConfiguration(
                [
                    RegisterAmqpPublisher::create("amqpConnection", MediaType::APPLICATION_JSON)
                ]
            )
        );
    }

    public function test_throwing_exception()
    {
        $this->expectException(ConfigurationException::class);

        $this->prepareConfiguration(
            [
                RegisterAmqpPublisher::create("amqpConnection", MediaType::APPLICATION_JSON),
                RegisterAmqpPublisher::create("amqpConnection", MediaType::APPLICATION_JSON)
            ]
        );
    }


    /**
     * @param array $extensions
     *
     * @return MessagingSystemConfiguration
     * @throws MessagingException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Ecotone\Messaging\Config\ConfigurationException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     * @throws \ReflectionException
     */
    private function prepareConfiguration(array $extensions): MessagingSystemConfiguration
    {
        $cqrsMessagingModule = AmqpPublisherModule::create(InMemoryAnnotationRegistrationService::createEmpty());

        $extendedConfiguration        = $this->createMessagingSystemConfiguration();
        $moduleReferenceSearchService = ModuleReferenceSearchService::createEmpty();

        $cqrsMessagingModule->prepare(
            $extendedConfiguration,
            $extensions,
            $moduleReferenceSearchService
        );

        return $extendedConfiguration;
    }

    /**
     * @return MessagingSystemConfiguration
     * @throws MessagingException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Ecotone\Messaging\Config\ConfigurationException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     * @throws \ReflectionException
     */
    private function createMessagingSystemConfiguration(): Configuration
    {
        return MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty());
    }
}