<?php

namespace Ecotone\Amqp\AmqpTransaction;

use Enqueue\AmqpLib\AmqpConnectionFactory;

/**
 * @Annotation
 */
class AmqpTransaction
{
    public $connectionReferenceName = AmqpConnectionFactory::class;
}