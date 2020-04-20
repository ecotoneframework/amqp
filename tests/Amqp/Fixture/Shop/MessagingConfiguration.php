<?php


namespace Test\Ecotone\Amqp\Fixture\Shop;

use Ecotone\Amqp\AmqpQueue;
use Ecotone\Amqp\Configuration\AmqpMessageConsumerConfiguration;
use Ecotone\Amqp\Configuration\AmqpMessagePublisherConfiguration;
use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\Extension;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\MessagePublisher;

/**
 * @ApplicationContext()
 */
class MessagingConfiguration
{
    const CONSUMER_ID = "addToCart";
    const SHOPPING_QUEUE = "shopping";

    /**
     * @Extension()
     */
    public function registerPublisher()
    {
        return AmqpMessagePublisherConfiguration::create(MessagePublisher::class)
                ->withAutoDeclareQueueOnSend(true)
                ->withDefaultRoutingKey(self::SHOPPING_QUEUE);
    }

    /**
     * @Extension()
     */
    public function registerConsumer()
    {
        return [
            AmqpQueue::createWith(self::SHOPPING_QUEUE),
            AmqpMessageConsumerConfiguration::create(self::CONSUMER_ID, self::SHOPPING_QUEUE)
                ->withReceiveTimeoutInMilliseconds(1),
            PollingMetadata::create(self::CONSUMER_ID)
                ->setExecutionTimeLimitInMilliseconds(1)
        ];
    }
}