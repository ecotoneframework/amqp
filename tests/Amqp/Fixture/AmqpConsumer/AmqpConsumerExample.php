<?php


namespace Test\Ecotone\Amqp\Fixture\AmqpConsumer;

use Ecotone\Messaging\Annotation\ClassReference;
use Ecotone\Messaging\Annotation\MessageConsumer;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use stdClass;

#[ClassReference("amqpConsumer")]
class AmqpConsumerExample
{
    #[MessageConsumer("someId")]
    public function handle(#[Payload] stdClass $object): void
    {

    }
}