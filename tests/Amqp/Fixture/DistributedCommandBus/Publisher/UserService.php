<?php

namespace Test\Ecotone\Amqp\Fixture\DistributedCommandBus\Publisher;

use Ecotone\Messaging\Annotation\Parameter\Reference;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\DistributedBus;
use Test\Ecotone\Amqp\Fixture\DistributedCommandBus\Receiver\TicketServiceMessagingConfiguration;
use Test\Ecotone\Amqp\Fixture\DistributedCommandBus\Receiver\TicketServiceReceiver;

class UserService
{
    const CHANGE_BILLING_DETAILS = "changeBillingDetails";

    #[CommandHandler(self::CHANGE_BILLING_DETAILS)]
    public function changeBillingDetails(#[Reference] DistributedBus $commandBus)
    {
        $commandBus->sendCommand(
            TicketServiceMessagingConfiguration::SERVICE_NAME,
            TicketServiceReceiver::CREATE_TICKET_ENDPOINT,
            "User changed billing address"
        );
    }
}