<?php


namespace Test\Ecotone\Amqp\Fixture\Order;

use Ecotone\Amqp\AmqpBackedMessageChannelBuilder;
use Ecotone\Messaging\Annotation\ServiceContext;
use Ecotone\Messaging\Endpoint\PollingMetadata;

class ChannelConfiguration
{
    const QUEUE_NAME = "orders";

    #[ServiceContext]
    public function registerAsyncChannel() : array
    {
        return [
            AmqpBackedMessageChannelBuilder::create(self::QUEUE_NAME)
                ->withReceiveTimeout(100),
            PollingMetadata::create(self::QUEUE_NAME)
                ->setExecutionTimeLimitInMilliseconds(1)
                ->setHandledMessageLimit(1)
                ->setErrorChannelName("errorChannel")
        ];
    }
}