<?php


namespace Test\Ecotone\Amqp\Fixture\SuccessTransaction;

use Ecotone\Messaging\Annotation\MessageGateway;

interface OrderRegisteringGateway
{
    #[MessageGateway("placeOrder")]
    public function place(string $order): void;
}