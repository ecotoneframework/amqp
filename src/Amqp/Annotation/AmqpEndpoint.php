<?php


namespace Ecotone\Amqp\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Ecotone\Messaging\Annotation\EndpointAnnotation;
use Enqueue\AmqpLib\AmqpConnectionFactory;

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
    public $queueName;
    /**
     * @var string
     */
    public $amqpConnectionReferenceName = AmqpConnectionFactory::class;
    /**
     * @var array
     */
    public $parameterConverters = [];
}