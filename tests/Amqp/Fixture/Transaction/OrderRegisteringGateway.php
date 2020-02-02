<?php


namespace Test\Ecotone\Amqp\Fixture\Transaction;

use Ecotone\Messaging\Annotation\Gateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;

/**
 * @MessageEndpoint()
 */
interface OrderRegisteringGateway
{
    /**
     * @Gateway(requestChannel="placeOrder")
     */
    public function place(string $order): void;
}