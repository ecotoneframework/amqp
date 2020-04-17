<?php


namespace Test\Ecotone\Amqp\Fixture\Shop;

use Ecotone\Amqp\AmqpQueue;
use Ecotone\Amqp\Configuration\AmqpConsumerConfiguration;
use Ecotone\Amqp\Configuration\RegisterAmqpPublisher;
use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\Extension;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Publisher;

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
        return RegisterAmqpPublisher::create(Publisher::class)
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
            AmqpConsumerConfiguration::create(self::CONSUMER_ID, self::SHOPPING_QUEUE)
                ->withReceiveTimeoutInMilliseconds(1),
            PollingMetadata::create(self::CONSUMER_ID)
                ->setExecutionTimeLimitInMilliseconds(1)
        ];
    }
}