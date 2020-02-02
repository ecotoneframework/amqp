<?php


namespace Test\Ecotone\Amqp\Fixture\Transaction;

use Ecotone\Amqp\Annotation\AmqpChannelAdapter;
use Ecotone\Messaging\Annotation\Async;
use Ecotone\Messaging\Annotation\InboundChannelAdapter;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Poller;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\QueryHandler;
use Test\Ecotone\Amqp\Fixture\Transaction\OrderRegisteringGateway;

/**
 * Class OrderService
 * @package Test\Ecotone\Amqp\Fixture\Order
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class OrderService
{
    /**
     * @param string $order
     * @param \Test\Ecotone\Amqp\Fixture\Transaction\OrderRegisteringGateway $orderRegisteringGateway
     * @CommandHandler(inputChannelName="order.register")
     */
    public function register(string $order, OrderRegisteringGateway $orderRegisteringGateway) : void
    {
        $orderRegisteringGateway->place($order);

        throw new \InvalidArgumentException("test");
    }

    /**
     * @AmqpChannelAdapter(
     *     queueName="placeOrder",
     *     endpointId="placeOrderEndpoint",
     *     poller=@Poller(handledMessageLimit=1, executionTimeLimitInMilliseconds=1)
     * )
     */
    public function throwExceptionOnReceive(string $order) : void
    {
        throw new \InvalidArgumentException("Order was not rollbacked");
    }
}