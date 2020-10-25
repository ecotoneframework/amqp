<?php


namespace Test\Ecotone\Amqp\Fixture\FailureTransaction;

use Ecotone\Amqp\AmqpBackedMessageChannelBuilder;
use Ecotone\Amqp\Configuration\AmqpConfiguration;
use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Endpoint\PollingMetadata;

class ChannelConfiguration
{
    #[ApplicationContext]
    public function registerCommandChannel(): array
    {
        return [
            AmqpBackedMessageChannelBuilder::create("placeOrder")
                ->withReceiveTimeout(1),
            PollingMetadata::create("placeOrderEndpoint")
                ->setHandledMessageLimit(1)
                ->setExecutionTimeLimitInMilliseconds(1)
                ->setErrorChannelName("errorChannel"),
            AmqpConfiguration::createWithDefaults()
                ->withTransactionOnAsynchronousEndpoints(true)
                ->withTransactionOnCommandBus(true)
        ];
    }

}