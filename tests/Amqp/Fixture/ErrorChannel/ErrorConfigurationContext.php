<?php
declare(strict_types=1);

namespace Test\Ecotone\Amqp\Fixture\ErrorChannel;

use Ecotone\Amqp\AmqpBackedMessageChannelBuilder;
use Ecotone\Amqp\Configuration\AmqpConfiguration;
use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\Recoverability\ErrorHandlerConfiguration;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;

class ErrorConfigurationContext
{
    const INPUT_CHANNEL = "correctOrders";
    const ERROR_CHANNEL = "errorChannel";


    #[ApplicationContext]
    public function getChannels()
    {
        return [
            AmqpBackedMessageChannelBuilder::create(self::INPUT_CHANNEL)
                ->withReceiveTimeout(1)
        ];
    }

    #[ApplicationContext]
    public function errorConfiguration()
    {
        return ErrorHandlerConfiguration::create(
            self::ERROR_CHANNEL,
            RetryTemplateBuilder::exponentialBackoff(1, 1)
                ->maxRetryAttempts(2)
        );
    }

    #[ApplicationContext]
    public function pollingConfiguration()
    {
        return [
            PollingMetadata::create(self::INPUT_CHANNEL)
                ->setExecutionTimeLimitInMilliseconds(1)
                ->setHandledMessageLimit(1)
                ->setErrorChannelName(self::ERROR_CHANNEL)
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