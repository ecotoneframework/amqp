<?php


namespace Ecotone\Amqp\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Ecotone\Messaging\Annotation\EndpointAnnotation;
use Ecotone\Messaging\Annotation\Poller;

/**
 * Class AmqpConsumer
 * @package Ecotone\Amqp\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class AmqpEndpoint extends EndpointAnnotation
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