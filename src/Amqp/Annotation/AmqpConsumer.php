<?php


namespace Ecotone\Amqp\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Class AmqpConsumer
 * @package Ecotone\Amqp\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class AmqpConsumer
{
    /**
     * @var string
     * @Required()
     */
    public $amqpConnectionReferenceName;
    /**
     * @var string
     * @Required()
     */
    public $queueName;
    /**
     * @var array
     */
    public $parameterConverters = [];
}