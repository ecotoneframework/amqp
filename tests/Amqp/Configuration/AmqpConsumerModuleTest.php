<?php


namespace Test\Ecotone\Amqp\Configuration;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\Amqp\AmqpInboundChannelAdapterBuilder;
use Ecotone\Amqp\Configuration\AmqpConsumerModule;
use Ecotone\Amqp\Configuration\AmqpPublisherModule;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\ApplicationConfiguration;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\InMemoryModuleMessaging;
use Ecotone\Messaging\Config\InMemoryReferenceTypeFromNameResolver;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Test\Ecotone\Amqp\Fixture\AmqpConsumerExample;

/**
 * Class AmqpConsumerModuleTest
 * @package Test\Ecotone\Amqp\Configuration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpConsumerModuleTest extends TestCase
{
    public function test_registering_consumer()
    {
        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerConsumer(AmqpInboundChannelAdapterBuilder::createWith(
                    "endpointId",
                    "input",
                    "endpointId",
                    "amqpConnection"
                ))
                ->registerMessageHandler(
                    ServiceActivatorBuilder::create(AmqpConsumerExample::class, "handle")
                        ->withEndpointId("endpointId.target")
                        ->withInputChannelName("endpointId")
                        ->withMethodParameterConverters([
                            PayloadBuilder::create("object")
                        ])
                ),
            $this->prepareConfiguration([
                AmqpConsumerExample::class
            ])
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
     * @param array $classes
     *
     * @return MessagingSystemConfiguration
     * @throws MessagingException
     * @throws AnnotationException
     * @throws ConfigurationException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    private function prepareConfiguration(array $classes): MessagingSystemConfiguration
    {
        $cqrsMessagingModule = AmqpConsumerModule::create(InMemoryAnnotationRegistrationService::createFrom($classes));

        $extendedConfiguration = $this->createMessagingSystemConfiguration();
        $moduleReferenceSearchService = ModuleReferenceSearchService::createEmpty();

        $cqrsMessagingModule->prepare(
            $extendedConfiguration,
            $classes,
            $moduleReferenceSearchService
        );

        return $extendedConfiguration;
    }
}