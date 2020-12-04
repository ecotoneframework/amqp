<?php


namespace Test\Ecotone\Amqp\Fixture\SuccessTransaction;

use Ecotone\Amqp\AmqpBackedMessageChannelBuilder;
use Ecotone\Amqp\Configuration\AmqpConfiguration;
use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\Extension;
use Ecotone\Messaging\Endpoint\PollingMetadata;

/**
 * Class ChannelConfiguration
 * @package Test\Ecotone\Amqp\Fixture\Order
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ApplicationContext()
 */
class ChannelConfiguration
{
    const QUEUE_NAME = "placeOrder";

    /**
     * @Extension()
     */
    public function registerCommandChannel(): array
    {
        return [
            AmqpBackedMessageChannelBuilder::create(self::QUEUE_NAME)
                ->withReceiveTimeout(1),
            PollingMetadata::create("placeOrderEndpoint")
                ->setHandledMessageLimit(1)
                ->setExecutionTimeLimitInMilliseconds(1),
            AmqpConfiguration::createWithDefaults()
                ->withTransactionOnAsynchronousEndpoints(true)
                ->withTransactionOnCommandBus(true)
        ];
    }

}