<?php


namespace Ecotone\Amqp;


use Ecotone\Enqueue\ReconnectableConnectionFactory;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\Assert;
use Enqueue\AmqpLib\AmqpConnectionFactory;
use Enqueue\AmqpLib\AmqpContext;
use Interop\Queue\Context;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Connection\AMQPLazyConnection;
use ReflectionClass;

class AmqpConsumerConnectionFactory implements ReconnectableConnectionFactory
{
    /**
     * @var AmqpConnectionFactory
     */
    private $connectionFactory;

    public function __construct(AmqpConnectionFactory $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

    public function createContext(): Context
    {
        $context = $this->connectionFactory->createContext();

        $heartbeatOnTick = $this->connectionFactory->getConfig()->getOption('heartbeat_on_tick', true);
        if ($heartbeatOnTick) {
            register_tick_function(function (\Interop\Amqp\AmqpContext $context) {
                /** @var AMQPLazyConnection|AMQPConnection|null $connection */
                $connection = $context->getLibChannel()->getConnection();

                if ($connection) {
                    $connection->checkHeartBeat();
                }
            }, $context);
        }

        return $context;
    }

    public function getConnectionInstanceId(): int
    {
        return spl_object_id($this->connectionFactory);
    }

    /**
     * @param Context|AmqpContext|null $context
     * @return bool
     * @throws MessagingException
     */
    public function isDisconnected(?Context $context): bool
    {
        if (!$context) {
            return false;
        }

        Assert::isSubclassOf($context, AmqpContext::class, "Context must be " . AmqpContext::class);

        return !$context->getLibChannel()->is_open();
    }

    public function reconnect(): void
    {
        $reflectionClass = new ReflectionClass($this->connectionFactory);

        $connectionProperty = $reflectionClass->getProperty("connection");
        $connectionProperty->setAccessible(true);
        $connectionProperty->setValue($this->connectionFactory, null);
    }
}