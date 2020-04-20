<?php


namespace Test\Ecotone\Amqp\Fixture\Order;

use Ecotone\Messaging\Annotation\Asynchronous;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\QueryHandler;

/**
 * Class OrderService
 * @package Test\Ecotone\Amqp\Fixture\Order
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 * @Asynchronous(channelName="orders")
 */
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