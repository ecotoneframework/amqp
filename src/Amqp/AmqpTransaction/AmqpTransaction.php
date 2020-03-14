<?php

namespace Ecotone\Amqp\AmqpTransaction;

use Enqueue\AmqpLib\AmqpConnectionFactory;

/**
 * @Annotation
 */
class AmqpTransaction
{
    public $connectionReferenceNames = [AmqpConnectionFactory::class];
}