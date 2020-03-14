<?php


namespace Ecotone\Amqp\AmqpTransaction;


use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorObjectBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Interop\Amqp\AmqpConnectionFactory;

class AmqpTransactionInterceptorBuilder implements AroundInterceptorObjectBuilder
{
    /**
     * @var array
     */
    private $connectionReferenceNames = [];

    public function __construct(array $connectionReferenceNames)
    {
        $this->connectionReferenceNames = $connectionReferenceNames;
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
        return new AmqpTransactionInterceptor($referenceSearchService, $this->connectionReferenceNames);
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [];
    }
}