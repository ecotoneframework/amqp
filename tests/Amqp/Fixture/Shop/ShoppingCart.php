<?php


namespace Test\Ecotone\Amqp\Fixture\Shop;

use Ecotone\Messaging\Annotation\Consumer;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Publisher;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\QueryHandler;

/**
 * @MessageEndpoint()
 */
class ShoppingCart
{
    private $shoppingCart = [];

    /**
     * @CommandHandler(inputChannelName="addToBasket")
     */
    public function requestAddingToBasket(string $productName, Publisher $publisher) : void
    {
        $publisher->send($productName);
    }

    /**
     * @Consumer(endpointId=MessagingConfiguration::CONSUMER_ID)
     */
    public function addToBasket(string $productName) : void
    {
        $this->shoppingCart[] = $productName;
    }

    /**
     * @QueryHandler(inputChannelName="getShoppingCartList")
     */
    public function getShoppingCartList() : array
    {
        return $this->shoppingCart;
    }
}