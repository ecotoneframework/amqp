<?php


namespace Test\Ecotone\Amqp\Configuration;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\Amqp\Configuration\AmqpPublisherModule;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\InMemoryModuleMessaging;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * Class AmqpConsumerModuleTest
 * @package Test\Ecotone\Amqp\Configuration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpConsumerModuleTest extends TestCase
{
//    public function test_registering_consumer()
//    {
//        $this->assertEquals(
//            $this->createMessagingSystemConfiguration()
//                ->registerMessageHandler()
//        );
//    }

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
}