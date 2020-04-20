<?php


namespace Test\Ecotone\Amqp\Fixture\AmqpConsumer;

use Ecotone\Messaging\Annotation\MessageConsumer;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use stdClass;

/**
 * Class AmqpConsumerExample
 * @package Test\Ecotone\Amqp\Fixture
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint("amqpConsumer")
 */
class AmqpConsumerExample
{
    /**
     * @MessageConsumer(
     *     endpointId="someId",
     *     parameterConverters={
     *          @Payload(parameterName="object")
     *     }
     * )
     */
    public function handle(stdClass $object): void
    {

    }
}