<?php

namespace Test\Ecotone\Amqp\Fixture\DeadLetter;

use Ecotone\Messaging\Annotation\Asynchronous;
use Ecotone\Messaging\Annotation\ServiceActivator;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\QueryHandler;
use Test\Ecotone\Amqp\Fixture\ErrorChannel\ErrorConfigurationContext;

class OrderService
{
    private int $placedOrders = 0;

    private int $incorrectOrders = 0;

    /**
     * @Asynchronous(ErrorConfigurationContext::INPUT_CHANNEL)
     * @CommandHandler(
     *     endpointId="orderService",
     *     inputChannelName="order.register"
     * )
     */
    public function order(string $orderName) : void
    {
        throw new \InvalidArgumentException("exception");
    }

    /**
     * @QueryHandler(inputChannelName="getOrderAmount")
     */
    public function getOrder() : int
    {
        return $this->placedOrders;
    }

    /**
     * @QueryHandler(inputChannelName="getIncorrectOrderAmount")
     */
    public function getIncorrectOrders() : int
    {
        return $this->incorrectOrders;
    }

    /**
     * @ServiceActivator(inputChannelName="incorrectOrders", endpointId="incorrectOrdersEndpoint")
     */
    public function storeIncorrectOrder(string $orderName) : void
    {
        $this->incorrectOrders++;
    }
}