<?php


namespace Ecotone\Amqp\AmqpTransaction;

use Ecotone\Amqp\AmqpConsumerConnectionFactory;
use Ecotone\Amqp\AmqpPublisherConnectionFactory;
use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Enqueue\AmqpLib\AmqpContext;

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
        $channels = array_map(function(string $connectionReferenceName){
            $connectionFactory = CachedConnectionFactory::createFor(new AmqpPublisherConnectionFactory($this->referenceSearchService->get($connectionReferenceName)));

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
            $channel->close(); // need to be closed in order to publish other messages outside of transaction scope.
        }catch (\Throwable $exception) {
            foreach ($channels as $channel) {
                $channel->tx_rollback();
            }
            $channel->close();

            throw $exception;
        }

        return $result;
    }
}