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
use Interop\Amqp\Impl\AmqpQueue;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use ReflectionException;
use Test\Ecotone\Amqp\Fixture\ErrorChannel\ErrorConfigurationContext;
use Test\Ecotone\Amqp\Fixture\FailureTransactionWithFatalError\ChannelConfiguration;
use Test\Ecotone\Amqp\Fixture\Order\OrderService;
use Test\Ecotone\Amqp\Fixture\Order\PlaceOrder;
use Test\Ecotone\Amqp\Fixture\Shop\MessagingConfiguration;
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
            case "Test\Ecotone\Amqp\Fixture\ErrorChannel":
                {
                    $objects = [
                        new \Test\Ecotone\Amqp\Fixture\ErrorChannel\OrderService()
                    ];
                }
                break;
            case "Test\Ecotone\Amqp\Fixture\DeadLetter":
                {
                    $objects = [
                        new \Test\Ecotone\Amqp\Fixture\DeadLetter\OrderService()
                    ];
                    break;
                }
            case "Test\Ecotone\Amqp\Fixture\FailureTransactionWithFatalError":
                {
                    $objects = [
                        new \Test\Ecotone\Amqp\Fixture\FailureTransactionWithFatalError\OrderService()
                    ];
                    break;
                }
        }

        $amqpConnectionFactory = new AmqpConnectionFactory(["dsn" => "amqp://{$host}:5672"]);
        self::$messagingSystem = EcotoneLiteConfiguration::createWithConfiguration(
            __DIR__ . "/../../../../",
            InMemoryPSRContainer::createFromObjects(array_merge($objects, [$amqpConnectionFactory])),
            ApplicationConfiguration::createWithDefaults()
                ->withNamespaces([$namespace])
                ->withCacheDirectoryPath(sys_get_temp_dir() . DIRECTORY_SEPARATOR . Uuid::uuid4()->toString())
        );

        $amqpConnectionFactory->createContext()->deleteQueue(new AmqpQueue(ChannelConfiguration::QUEUE_NAME));
        $amqpConnectionFactory->createContext()->deleteQueue(new AmqpQueue(\Test\Ecotone\Amqp\Fixture\FailureTransaction\ChannelConfiguration::QUEUE_NAME));
        $amqpConnectionFactory->createContext()->deleteQueue(new AmqpQueue(\Test\Ecotone\Amqp\Fixture\SuccessTransaction\ChannelConfiguration::QUEUE_NAME));
        $amqpConnectionFactory->createContext()->deleteQueue(new AmqpQueue(MessagingConfiguration::SHOPPING_QUEUE));
        $amqpConnectionFactory->createContext()->deleteQueue(new AmqpQueue(\Test\Ecotone\Amqp\Fixture\Order\ChannelConfiguration::QUEUE_NAME));
        $amqpConnectionFactory->createContext()->deleteQueue(new AmqpQueue(ErrorConfigurationContext::INPUT_CHANNEL));
        $amqpConnectionFactory->createContext()->deleteQueue(new AmqpQueue(\Test\Ecotone\Amqp\Fixture\DeadLetter\ErrorConfigurationContext::INPUT_CHANNEL));
        $amqpConnectionFactory->createContext()->deleteQueue(new AmqpQueue(\Test\Ecotone\Amqp\Fixture\DeadLetter\ErrorConfigurationContext::DEAD_LETTER_CHANNEL));
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

    /**
     * @Then there should be :amount orders
     */
    public function thereShouldBeOrders(int $amount)
    {
        $this->assertEquals(
            $amount,
            $this->getQueryBus()->convertAndSend("getOrderAmount", MediaType::APPLICATION_X_PHP_ARRAY, [])
        );
    }

    /**
     * @Then there should be :amount incorrect orders
     */
    public function thereShouldBeIncorrectOrders(int $amount)
    {
        $this->assertEquals(
            $amount,
            $this->getQueryBus()->convertAndSend("getIncorrectOrderAmount", MediaType::APPLICATION_X_PHP_ARRAY, [])
        );
    }

    /**
     * @When I call consumer :consumerName
     */
    public function iCallConsumer(string $consumerName)
    {
        self::$messagingSystem->runSeparatelyRunningEndpointBy($consumerName);
    }
}
