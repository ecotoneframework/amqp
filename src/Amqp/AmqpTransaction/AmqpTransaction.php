<?php

namespace Ecotone\Amqp\AmqpTransaction;

use Enqueue\AmqpExt\AmqpConnectionFactory;

#[\Attribute]
class AmqpTransaction
{
    public $connectionReferenceNames = [AmqpConnectionFactory::class];
}