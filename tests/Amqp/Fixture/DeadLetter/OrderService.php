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

    #[CommandHandler("order.register", "orderService")]
    #[Asynchronous(ErrorConfigurationContext::INPUT_CHANNEL)]
    public function order(string $orderName) : void
    {
        throw new \InvalidArgumentException("exception");
    }

    #[QueryHandler("getOrderAmount")]
    public function getOrder() : int
    {
        return $this->placedOrders;
    }

    #[QueryHandler("getIncorrectOrderAmount")]
    public function getIncorrectOrders() : int
    {
        return $this->incorrectOrders;
    }

    #[ServiceActivator("incorrectOrders", "incorrectOrdersEndpoint")]
    public function storeIncorrectOrder(string $orderName) : void
    {
        $this->incorrectOrders++;
    }
}