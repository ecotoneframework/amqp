<?php


namespace Test\Ecotone\Amqp\Fixture\Transaction;

use Ecotone\Amqp\AmqpTransaction\AmqpTransaction;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\LazyEventBus\LazyEventPublishing;

/**
 * @MessageEndpoint()
 * @LazyEventPublishing()
 */
interface TransactionalCommandBusExample extends CommandBus
{

}