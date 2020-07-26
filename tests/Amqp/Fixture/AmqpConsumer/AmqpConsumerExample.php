<?php


namespace Test\Ecotone\Amqp\Fixture\AmqpConsumer;

use Ecotone\Messaging\Annotation\ClassReference;
use Ecotone\Messaging\Annotation\MessageConsumer;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use stdClass;

/**
 * @ClassReference("amqpConsumer")
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