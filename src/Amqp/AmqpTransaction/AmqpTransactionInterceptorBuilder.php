<?php


namespace Ecotone\Amqp\AmqpTransaction;


use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorObjectBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;

class AmqpTransactionInterceptorBuilder implements AroundInterceptorObjectBuilder
{
    /**
     * @inheritDoc
     */
    public function getInterceptingInterfaceClassName(): string
    {
        return AmqpTransactionInterceptor::class;
    }

    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): object
    {
        return new AmqpTransactionInterceptor($referenceSearchService);
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [];
    }
}