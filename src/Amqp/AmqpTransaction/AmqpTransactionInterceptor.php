<?php


namespace Ecotone\Amqp\AmqpTransaction;

use Ecotone\Amqp\AmqpReconnectableConnectionFactory;
use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Enqueue\AmqpLib\AmqpContext;

/**
 * Class AmqpTransactionInterceptor
 * @package Ecotone\Amqp\AmqpTransaction
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpTransactionInterceptor
{
    /**
     * @var ReferenceSearchService
     */
    private $referenceSearchService;

    public function __construct(ReferenceSearchService $referenceSearchService)
    {
        $this->referenceSearchService = $referenceSearchService;
    }

    public function transactional(MethodInvocation $methodInvocation, AmqpTransaction $amqpTransaction)
    {
        $reconnectableConnectionFactory = CachedConnectionFactory::createFor(new AmqpReconnectableConnectionFactory($this->referenceSearchService->get($amqpTransaction->connectionReferenceName)));

        /** @var AmqpContext $context */
        $context = $reconnectableConnectionFactory->createContext();

        $channel = $context->getLibChannel();

        $channel->tx_select();
        try {
            $result = $methodInvocation->proceed();
            $channel->tx_commit();
        }catch (\Throwable $exception) {
            $channel->tx_rollback();

            throw $exception;
        }

        return $result;
    }
}