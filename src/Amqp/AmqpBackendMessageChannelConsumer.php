<?php


namespace Ecotone\Amqp;

use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Endpoint\ConsumerLifecycle;
use Ecotone\Messaging\Endpoint\MessageHandlerConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollingConsumer\PollingConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gateway\ErrorChannelInterceptor;
use Ecotone\Messaging\Handler\Logger\Annotation\LogError;
use Ecotone\Messaging\Handler\Logger\ExceptionLoggingInterceptorBuilder;
use Ecotone\Messaging\Handler\Logger\LoggingInterceptor;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\ReferenceSearchService;

/**
 * Class AmqpBackendMessageChannelConsumer
 * @package Ecotone\Amqp
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpBackendMessageChannelConsumer implements MessageHandlerConsumerBuilder
{
    /**
     * @inheritDoc
     */
    public function isSupporting(MessageHandlerBuilder $messageHandlerBuilder, MessageChannelBuilder $relatedMessageChannel): bool
    {
        return $relatedMessageChannel instanceof AmqpBackedMessageChannelBuilder;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, MessageHandlerBuilder $messageHandlerBuilder, PollingMetadata $pollingMetadata): ConsumerLifecycle
    {
        $pollingConsumerBuilder = new PollingConsumerBuilder();

        $pollingConsumerBuilder->addAroundInterceptor(AmqpAcknowledgeConfirmationInterceptor::createAroundInterceptor($messageHandlerBuilder->getEndpointId()));
        $pollingConsumerBuilder->addAroundInterceptor(AroundInterceptorReference::createWithObjectBuilder(
            "errorLog",
            new ExceptionLoggingInterceptorBuilder(),
            "logException",
            ErrorChannelInterceptor::PRECEDENCE - 1,
            ""
        ));

        return $pollingConsumerBuilder->build($channelResolver, $referenceSearchService, $messageHandlerBuilder, $pollingMetadata);
    }
}