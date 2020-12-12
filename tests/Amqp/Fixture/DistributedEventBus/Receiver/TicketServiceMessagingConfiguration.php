<?php

namespace Test\Ecotone\Amqp\Fixture\DistributedEventBus\Receiver;

use Ecotone\Amqp\Distribution\AmqpDistributedBusConfiguration;
use Ecotone\Messaging\Annotation\ServiceContext;
use Ecotone\Messaging\Endpoint\PollingMetadata;

class TicketServiceMessagingConfiguration
{
    const SERVICE_NAME = "ticket_service";

    #[ServiceContext]
    public function configure()
    {
        return [
            AmqpDistributedBusConfiguration::createConsumer(),
            PollingMetadata::create(self::SERVICE_NAME)
                ->setHandledMessageLimit(1)
                ->setExecutionTimeLimitInMilliseconds(1)
        ];
    }
}