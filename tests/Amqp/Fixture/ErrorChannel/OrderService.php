<?php

namespace Test\Ecotone\Amqp\Fixture\ErrorChannel;

use Ecotone\Messaging\Annotation\Asynchronous;
use Ecotone\Messaging\Annotation\ServiceActivator;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\QueryHandler;
use Test\Ecotone\Amqp\Fixture\ErrorChannel\ErrorConfigurationContext;

class OrderService
{
    private int $callCount = 0;

    private int $placedOrders = 0;

    /**
     * @CommandHandler(
     *     endpointId="orderService",
     *     inputChannelName="order.register"
     * )
     */
    #[Asynchronous(ErrorConfigurationContext::INPUT_CHANNEL)]
    public function order(string $orderName) : void
    {
        $this->callCount += 1;

        if ($this->callCount > 2) {
            $this->placedOrders++;

            return;
        }

        throw new \InvalidArgumentException("exception");
    }

    /**
     * @QueryHandler(inputChannelName="getOrderAmount")
     */
    public function getOrder() : int
    {
        return $this->placedOrders;
    }
}