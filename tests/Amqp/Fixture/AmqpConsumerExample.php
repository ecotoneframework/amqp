<?php


namespace Test\Ecotone\Amqp\Fixture;

use Ecotone\Amqp\Annotation\AmqpChannelAdapter;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use stdClass;

/**
 * Class AmqpConsumerExample
 * @package Test\Ecotone\Amqp\Fixture
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class AmqpConsumerExample
{
    /**
     * @AmqpChannelAdapter(
     *     endpointId="endpointId",
     *     amqpConnectionReferenceName="amqpConnection",
     *     queueName="input",
     *     autoDeclareQueueOnSend=true,
     *     parameterConverters={
     *          @Payload(parameterName="object")
     *     },
     *     headerMapper="ecotone.*"
     * )
     * @param stdClass $object
     */
    public function handle(stdClass $object): void
    {

    }
}