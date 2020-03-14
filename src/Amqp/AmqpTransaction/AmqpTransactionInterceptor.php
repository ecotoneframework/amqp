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
    /**
     * @var string[]
     */
    private $connectionReferenceNames;

    public function __construct(ReferenceSearchService $referenceSearchService, array $connectionReferenceNames)
    {
        $this->referenceSearchService = $referenceSearchService;
        $this->connectionReferenceNames = $connectionReferenceNames;
    }

    public function transactional(MethodInvocation $methodInvocation, ?AmqpTransaction $amqpTransaction)
    {;
        $channels = array_map(function(string $connectionReferenceName){
            $connectionFactory = CachedConnectionFactory::createFor(new AmqpReconnectableConnectionFactory($this->referenceSearchService->get($connectionReferenceName)));

            /** @var AmqpContext $context */
            $context = $connectionFactory->createContext();

            return  $context->getLibChannel();
        }, $amqpTransaction ? $amqpTransaction->connectionReferenceNames : $this->connectionReferenceNames);

        foreach ($channels as $channel) {
            $channel->tx_select();
        }
        try {
            $result = $methodInvocation->proceed();

            foreach ($channels as $channel) {
                $channel->tx_commit();
            }
        }catch (\Throwable $exception) {
            foreach ($channels as $channel) {
                $channel->tx_rollback();
            }

            throw $exception;
        }

        return $result;
    }
}