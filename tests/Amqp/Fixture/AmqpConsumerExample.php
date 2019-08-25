<?php


namespace Test\Ecotone\Amqp\Fixture;

use Ecotone\Amqp\Annotation\AmqpConsumer;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Payload;

/**
 * Class AmqpConsumerExample
 * @package Test\Ecotone\Amqp\Fixture
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class AmqpConsumerExample
{
    /**
     * @AmqpConsumer(
     *     amqpConnectionReferenceName="amqpConnection",
     *     queueName="input",
     *     parameterConverters={
     *          @Payload(parameterName="object")
     *     }
     * )
     * @param \stdClass $object
     */
    public function handle(\stdClass $object) : void
    {

    }
}