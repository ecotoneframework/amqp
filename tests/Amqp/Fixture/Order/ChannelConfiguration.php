<?php


namespace Test\Ecotone\Amqp\Fixture\Order;

use Ecotone\Amqp\AmqpBackedMessageChannelBuilder;
use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Endpoint\PollingMetadata;

class ChannelConfiguration
{
    #[ApplicationContext]
    public function registerAsyncChannel(): array
    {
        return [
            AmqpBackedMessageChannelBuilder::create("orders")
                ->withReceiveTimeout(100),
            PollingMetadata::create("orders")
                ->setExecutionTimeLimitInMilliseconds(1)
                ->setHandledMessageLimit(1)
                ->setErrorChannelName("errorChannel")
        ];
    }
}