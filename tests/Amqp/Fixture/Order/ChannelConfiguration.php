<?php


namespace Test\Ecotone\Amqp\Fixture\Order;

use Ecotone\Amqp\AmqpBackedMessageChannelBuilder;
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
    /**
     * @Extension()
     */
    public function registerCommandChannel() : AmqpBackedMessageChannelBuilder
    {
        return AmqpBackedMessageChannelBuilder::create("order.register");
    }

    /**
     * @Extension()
     */
    public function registerAsyncChannel() : array
    {
        return [
            AmqpBackedMessageChannelBuilder::create("orders")
                ->withReceiveTimeout(100),
            PollingMetadata::create("orders")
                ->setExecutionTimeLimitInMilliseconds(1)
                ->setHandledMessageLimit(1)
        ];
    }
}