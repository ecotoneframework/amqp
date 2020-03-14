<?php


namespace Test\Ecotone\Amqp\Fixture\Transaction;

use Ecotone\Amqp\AmqpBackedMessageChannelBuilder;
use Ecotone\Amqp\Configuration\AmqpConfiguration;
use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\Extension;
use Ecotone\Messaging\Endpoint\PollingMetadata;

/**
 * Class ChannelConfiguration
 * @package Test\Ecotone\Amqp\Fixture\Order
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ApplicationContext()
 */
class ChannelConfiguration
{
    /**
     * @Extension()
     */
    public function registerCommandChannel() : array
    {
        return [
            AmqpBackedMessageChannelBuilder::create("placeOrder"),
            AmqpConfiguration::createWithDefaults()
                ->withDefaultTransactionOnPollabeEndpoints(true)
                ->withDefaultTransactionOnCommandBus(true)
        ];
    }

}