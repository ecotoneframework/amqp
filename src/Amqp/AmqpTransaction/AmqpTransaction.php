<?php

namespace Ecotone\Amqp\AmqpTransaction;

use Enqueue\AmqpLib\AmqpConnectionFactory;

#[\Attribute]
class AmqpTransaction
{
    public $connectionReferenceNames = [AmqpConnectionFactory::class];
}