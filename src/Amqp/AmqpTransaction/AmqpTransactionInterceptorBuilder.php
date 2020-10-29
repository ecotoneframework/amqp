<?php


namespace Ecotone\Amqp\AmqpTransaction;


use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorObjectBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Interop\Amqp\AmqpConnectionFactory;

class AmqpTransactionInterceptorBuilder implements AroundInterceptorObjectBuilder
{
    private AmqpTransactionInterceptor $amqpTransactionInterceptor;

    public function __construct(AmqpTransactionInterceptor $amqpTransactionInterceptor)
    {
        $this->amqpTransactionInterceptor = $amqpTransactionInterceptor;
    }

    /**
     * @inheritDoc
     */
    public function getInterceptingInterfaceClassName(): string
    {
        return AmqpTransactionInterceptor::class;
    }

    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): object
    {
        return $this->amqpTransactionInterceptor;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [];
    }
}