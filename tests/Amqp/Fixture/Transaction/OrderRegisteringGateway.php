<?php


namespace Test\Ecotone\Amqp\Fixture\Transaction;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\MessageGateway;

/**
 * @MessageEndpoint()
 */
interface OrderRegisteringGateway
{
    /**
     * @MessageGateway(requestChannel="placeOrder")
     */
    public function place(string $order): void;
}