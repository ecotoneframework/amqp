<?php


namespace Test\Ecotone\Amqp\Fixture\SuccessTransaction;

use Ecotone\Messaging\Annotation\ServiceActivator;
use Ecotone\Messaging\MessagingException;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\QueryHandler;
use InvalidArgumentException;

class OrderService
{
    private ?string $order = null;

    #[CommandHandler("order.register")]
    public function register(string $order, OrderRegisteringGateway $orderRegisteringGateway): void
    {
        $orderRegisteringGateway->place($order);
    }

    #[ServiceActivator("placeOrder", "placeOrderEndpoint")]
    public function receive(string $order): void
    {
        $this->order = $order;
    }

    #[QueryHandler("order.getOrder")]
    public function getOrder() : ?string
    {
        $order = $this->order;
        $this->order = null;

        return $order;
    }
}