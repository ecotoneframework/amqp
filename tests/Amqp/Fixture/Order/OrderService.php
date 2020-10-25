<?php


namespace Test\Ecotone\Amqp\Fixture\Order;

use Ecotone\Messaging\Annotation\Asynchronous;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\QueryHandler;

#[Asynchronous("orders")]
class OrderService
{
    /**
     * @var PlaceOrder[]
     */
    private $orders = [];

    /**
     * @param PlaceOrder $placeOrder
     * @CommandHandler(
     *     inputChannelName="order.register",
     *     endpointId="orderReceiver"
     * )
     */
    public function register(PlaceOrder $placeOrder) : void
    {
        $this->orders[] = $placeOrder;
    }

    /**
     * @return array
     * @QueryHandler(inputChannelName="order.getOrders")
     */
    public function getRegisteredOrders() : array
    {
        return $this->orders;
    }
}