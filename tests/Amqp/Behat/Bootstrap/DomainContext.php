<?php

namespace Test\Ecotone\Amqp\Behat\Bootstrap;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\Lite\EcotoneLiteConfiguration;
use Ecotone\Lite\InMemoryPSRContainer;
use Ecotone\Messaging\Config\ApplicationConfiguration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\AggregateNotFoundException;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\QueryBus;
use Enqueue\AmqpLib\AmqpConnectionFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use ReflectionException;
use Test\Ecotone\Amqp\Fixture\Order\OrderService;
use Test\Ecotone\Amqp\Fixture\Order\PlaceOrder;
use Test\Ecotone\Amqp\Fixture\Shop\ShoppingCart;
use Test\Ecotone\Modelling\Fixture\OrderAggregate\OrderErrorHandler;

/**
 * Defines application features from the specific context.
 */
class DomainContext extends TestCase implements Context
{
    /**
     * @var ConfiguredMessagingSystem
     */
    private static $messagingSystem;

    /**
     * @Given I active messaging for namespace :namespace
     * @param string $namespace
     * @throws AnnotationException
     * @throws ConfigurationException
     * @throws MessagingException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function iActiveMessagingForNamespace(string $namespace)
    {
        $host = getenv("RABBIT_HOST") ? getenv("RABBIT_HOST") : "localhost";

        switch ($namespace) {
            case "Test\Ecotone\Amqp\Fixture\Order":
                {
                    $objects = [
                        new OrderService(),
                        new OrderErrorHandler()
                    ];
                    break;
                }
            case "Test\Ecotone\Amqp\Fixture\FailureTransaction":
                {
                    $objects = [
                        new \Test\Ecotone\Amqp\Fixture\FailureTransaction\OrderService()
                    ];
                }
                break;
            case "Test\Ecotone\Amqp\Fixture\SuccessTransaction":
                {
                    $objects = [
                        new \Test\Ecotone\Amqp\Fixture\SuccessTransaction\OrderService()
                    ];
                }
                break;
            case "Test\Ecotone\Amqp\Fixture\Shop":
                {
                    $objects = [
                        new ShoppingCart()
                    ];
                }
                break;
        }

        self::$messagingSystem = EcotoneLiteConfiguration::createWithConfiguration(
            __DIR__ . "/../../../../",
            InMemoryPSRContainer::createFromObjects(array_merge($objects, [new AmqpConnectionFactory(["dsn" => "amqp://{$host}:5672"])])),
            ApplicationConfiguration::createWithDefaults()
                ->withNamespaces([$namespace])
                ->withCacheDirectoryPath(sys_get_temp_dir() . DIRECTORY_SEPARATOR . Uuid::uuid4()->toString())
        );
    }

    /**
     * @When I order :order
     */
    public function iOrder(string $order)
    {
        return $this->getCommandBus()->convertAndSend("order.register", MediaType::APPLICATION_X_PHP, new PlaceOrder($order));
    }

    private function getCommandBus(): CommandBus
    {
        return self::$messagingSystem->getGatewayByName(CommandBus::class);
    }

    /**
     * @When I active receiver :receiverName
     * @param string $receiverName
     */
    public function iActiveReceiver(string $receiverName)
    {
        self::$messagingSystem->runSeparatelyRunningEndpointBy($receiverName);
    }

    /**
     * @Then on the order list I should see :order
     */
    public function onTheOrderListIShouldSee(string $order)
    {
        $this->assertEquals(
            [new PlaceOrder($order)],
            $this->getQueryBus()->convertAndSend("order.getOrders", MediaType::APPLICATION_X_PHP, [])
        );
    }

    private function getQueryBus(): QueryBus
    {
        return self::$messagingSystem->getGatewayByName(QueryBus::class);
    }

    /**
     * @Then there should be nothing on the order list
     */
    public function thereShouldBeNothingOnTheOrderList()
    {
        $this->assertEquals(
            [],
            $this->getQueryBus()->convertAndSend("order.getOrders", MediaType::APPLICATION_X_PHP, [])
        );
    }

    /**
     * @When I transactionally order :order
     */
    public function iTransactionallyOrder(string $order)
    {
        $commandBus = self::$messagingSystem->getGatewayByName(CommandBus::class);

        try {
            $commandBus->convertAndSend("order.register", MediaType::APPLICATION_X_PHP, $order);
        } catch (\InvalidArgumentException $e) {
        }
    }

    /**
     * @When I add product :productName to shopping cart
     */
    public function iAddProductToShoppingCart(string $productName)
    {
        $commandBus = self::$messagingSystem->getGatewayByName(CommandBus::class);

        $commandBus->convertAndSend("addToBasket", MediaType::APPLICATION_X_PHP, $productName);
    }

    /**
     * @Then there should be product :productName in shopping cart
     */
    public function thereShouldBeProductInShoppingCart(string $productName)
    {
        /** @var QueryBus $queryBus */
        $queryBus = self::$messagingSystem->getGatewayByName(QueryBus::class);

       $this->assertEquals(
           [$productName],
           $queryBus->convertAndSend("getShoppingCartList", MediaType::APPLICATION_X_PHP, [])
       );
    }

    /**
     * @Then there should be :orderName order
     */
    public function thereShouldBeOrder(string $orderName)
    {
        $this->assertEquals(
            $orderName,
            $this->getQueryBus()->convertAndSend("order.getOrder", MediaType::APPLICATION_X_PHP_ARRAY, [])
        );
    }

    /**
     * @Then there should be no :orderName order
     */
    public function thereShouldBeNoOrder(string $orderName)
    {
        $this->assertNull(
            $this->getQueryBus()->convertAndSend("order.getOrder", MediaType::APPLICATION_X_PHP_ARRAY, [])
        );
    }
}
