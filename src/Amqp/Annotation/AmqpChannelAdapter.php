<?php


namespace Ecotone\Amqp\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Ecotone\Messaging\Annotation\ChannelAdapter;
use Enqueue\AmqpLib\AmqpConnectionFactory;

/**
 * Class AmqpConsumer
 * @package Ecotone\Amqp\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class AmqpChannelAdapter extends ChannelAdapter
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