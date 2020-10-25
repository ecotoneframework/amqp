<?php
declare(strict_types=1);

namespace Test\Ecotone\Amqp\Fixture\DeadLetter;

use Ecotone\Amqp\AmqpBackedMessageChannelBuilder;
use Ecotone\Amqp\Configuration\AmqpConfiguration;
use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\Recoverability\ErrorHandlerConfiguration;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;

class ErrorConfigurationContext
{
    const INPUT_CHANNEL       = "correctOrders";
    const ERROR_CHANNEL       = "errorChannel";
    const DEAD_LETTER_CHANNEL = "incorrectOrders";


    #[ApplicationContext]
    public function getChannels()
    {
        return [
            AmqpBackedMessageChannelBuilder::create(self::INPUT_CHANNEL)
                ->withReceiveTimeout(1),
            AmqpBackedMessageChannelBuilder::create(self::DEAD_LETTER_CHANNEL)
                ->withReceiveTimeout(1),
        ];
    }

    #[ApplicationContext]
    public function errorConfiguration()
    {
        return ErrorHandlerConfiguration::createWithDeadLetterChannel(
            self::ERROR_CHANNEL,
            RetryTemplateBuilder::exponentialBackoff(1, 1)
                ->maxRetryAttempts(2),
            self::DEAD_LETTER_CHANNEL
        );
    }

    #[ApplicationContext]
    public function pollingConfiguration()
    {
        return [
            PollingMetadata::create(self::INPUT_CHANNEL)
                ->setExecutionTimeLimitInMilliseconds(1)
                ->setHandledMessageLimit(1)
                ->setErrorChannelName(self::ERROR_CHANNEL),
            PollingMetadata::create("incorrectOrdersEndpoint")
                ->setExecutionTimeLimitInMilliseconds(1)
                ->setHandledMessageLimit(1)
        ];
    }

    #[ApplicationContext]
    public function registerAmqpConfig(): array
    {
        return [
            AmqpConfiguration::createWithDefaults()
                ->withTransactionOnAsynchronousEndpoints(true)
                ->withTransactionOnCommandBus(true)
        ];
    }
}