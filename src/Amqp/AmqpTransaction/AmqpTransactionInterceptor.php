<?php


namespace Ecotone\Amqp\AmqpTransaction;

use Ecotone\Amqp\AmqpConsumerConnectionFactory;
use Ecotone\Amqp\AmqpPublisherConnectionFactory;
use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Enqueue\AmqpLib\AmqpContext;
use Interop\Queue\ConnectionFactory;

/**
 * https://www.rabbitmq.com/blog/2011/02/10/introducing-publisher-confirms/
 *
 * The confirm.select method enables publisher confirms on a channel.Â Â Note that a transactional channel cannot be put into confirm mode and a confirm mode channel cannot be made transactional.
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
        /** @var CachedConnectionFactory[] $connectionFactories */
        $connectionFactories = array_map(function(string $connectionReferenceName){
            return CachedConnectionFactory::createFor(new AmqpPublisherConnectionFactory($this->referenceSearchService->get($connectionReferenceName)));
        }, $amqpTransaction ? $amqpTransaction->connectionReferenceNames : $this->connectionReferenceNames);

        foreach ($connectionFactories as $connectionFactory) {
            $connectionFactory->createContext()->getLibChannel()->tx_select();
        }
        try {
            $result = $methodInvocation->proceed();

            foreach ($connectionFactories as $connectionFactory) {
                $connectionFactory->createContext()->getLibChannel()->tx_commit();
                $connectionFactory->createContext()->close(); // need to be closed in order to publish other messages outside of transaction scope.
            }
        }catch (\Throwable $exception) {
            foreach ($connectionFactories as $connectionFactory) {
                $connectionFactory->createContext()->getLibChannel()->tx_rollback();
                $connectionFactory->createContext()->close(); // need to be closed in order to publish other messages outside of transaction scope.
            }

            throw $exception;
        }

        return $result;
    }
}