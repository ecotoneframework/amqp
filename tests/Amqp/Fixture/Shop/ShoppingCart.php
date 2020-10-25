<?php


namespace Test\Ecotone\Amqp\Fixture\Shop;

use Ecotone\Messaging\Annotation\MessageConsumer;
use Ecotone\Messaging\MessagePublisher;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\QueryHandler;

class ShoppingCart
{
    private $shoppingCart = [];

    #[CommandHandler("addToBasket")]
    public function requestAddingToBasket(string $productName, MessagePublisher $publisher) : void
    {
        $publisher->send($productName);
    }

    #[MessageConsumer(MessagingConfiguration::CONSUMER_ID)]
    public function addToBasket(string $productName) : void
    {
        $this->shoppingCart[] = $productName;
    }

    #[QueryHandler("getShoppingCartList")]
    public function getShoppingCartList() : array
    {
        return $this->shoppingCart;
    }
}