<?php


namespace Ecotone\Amqp\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Class Amqp
 * @package Ecotone\Amqp\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class Amqp
{
    /**
     * @var string
     * @Required()
     */
    public $amqpConnectionReferenceName;
}