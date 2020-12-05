<?php


namespace Test\Ecotone\Amqp\Fixture\FailureTransactionWithFatalError;

use Ecotone\Messaging\Annotation\ServiceActivator;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\MessagingException;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\CommandBus;
use InvalidArgumentException;
use Ecotone\Modelling\Annotation\QueryHandler;
use Ecotone\Messaging\Annotation\Asynchronous;

class OrderService
{
    private $order = null;

    #[CommandHandler("order.register")]
    public function register(string $order, CommandBus $commandBus): void
    {
        $commandBus->sendWithRouting("makeOrder", $order);
    }

    #[Asynchronous("placeOrder")]
    #[CommandHandler("makeOrder", "placeOrderEndpoint")]
    public function placeOrder(string $order): void
    {
        $this->eventBus->publish("orderWasPlaced");
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