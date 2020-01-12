<?php

namespace Test\Ecotone\Amqp\Configuration;

use Ecotone\Amqp\AmqpAdmin;
use Ecotone\Amqp\AmqpBackedMessageChannelBuilder;
use Ecotone\Amqp\AmqpBinding;
use Ecotone\Amqp\AmqpExchange;
use Ecotone\Amqp\AmqpQueue;
use Ecotone\Amqp\Configuration\AmqpModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\ApplicationConfiguration;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\InMemoryModuleMessaging;
use Ecotone\Messaging\Config\InMemoryReferenceTypeFromNameResolver;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\ModuleRetrievingService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\MessagingException;
use PHPUnit\Framework\TestCase;

/**
 * Class AmqpModuleTest
 * @package Test\Ecotone\Amqp
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpModuleTest extends TestCase
{
    public function test_registering_amqp_backed_message_channel()
    {
        $this->assertEquals(
            AmqpAdmin::createWith([], [AmqpQueue::createWith("some")], []),
            $this->prepareConfigurationAndRetrieveAmqpAdmin(
                [
                    AmqpBackedMessageChannelBuilder::create("some", "amqpConnection")
                ]
            )
        );
    }

    public function TODO__test_registering_amqp_backed_message_channel_with_application_media_type()
    {
        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerMessageChannel(
                    AmqpBackedMessageChannelBuilder::create("some1", "amqpConnection")
                        ->withDefaultConversionMediaType(MediaType::APPLICATION_JSON)
                )
                ->registerMessageChannel(
                    AmqpBackedMessageChannelBuilder::create("some2", "amqpConnection")
                        ->withDefaultConversionMediaType(MediaType::APPLICATION_X_PHP_SERIALIZED)
                ),
            $this->prepareConfiguration(
                [
                    AmqpBackedMessageChannelBuilder::create("some1", "amqpConnection"),
                    AmqpBackedMessageChannelBuilder::create("some2", "amqpConnection")
                        ->withDefaultConversionMediaType(MediaType::APPLICATION_X_PHP_SERIALIZED),
                    ApplicationConfiguration::createWithDefaults()
                        ->withDefaultSerializationMediaType(MediaType::APPLICATION_JSON)
                ]
            )
        );
    }

    public function test_registering_amqp_configuration()
    {
        $amqpExchange = AmqpExchange::createDirectExchange("exchange");
        $amqpQueue = AmqpQueue::createWith("queue");
        $amqpBinding = AmqpBinding::createFromNames("exchange", "queue", "route");

        $this->assertEquals(
            AmqpAdmin::createWith([$amqpExchange], [$amqpQueue], [$amqpBinding]),
            $this->prepareConfigurationAndRetrieveAmqpAdmin([$amqpExchange, $amqpQueue, $amqpBinding])
        );
    }


    /**
     * @param AnnotationRegistrationService $annotationRegistrationService
     * @param array                         $extensions
     *
     * @return MessagingSystemConfiguration
     * @throws MessagingException
     */
    private function prepareConfigurationAndRetrieveAmqpAdmin(array $extensions): AmqpAdmin
    {
        $moduleReferenceSearchService = $this->prepareConfiguration($extensions);

        return $moduleReferenceSearchService->retrieveRequired(AmqpAdmin::REFERENCE_NAME);
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
        return MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());
    }

    /**
     * @param array $extensions
     * @return ModuleReferenceSearchService
     * @throws MessagingException
     */
    private function prepareConfiguration(array $extensions): ModuleReferenceSearchService
    {
        $cqrsMessagingModule = AmqpModule::create(InMemoryAnnotationRegistrationService::createEmpty());

        $extendedConfiguration = $this->createMessagingSystemConfiguration();
        $moduleReferenceSearchService = ModuleReferenceSearchService::createEmpty();

        $cqrsMessagingModule->prepare(
            $extendedConfiguration,
            $extensions,
            $moduleReferenceSearchService
        );
        return $moduleReferenceSearchService;
    }
}