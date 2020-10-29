<?php
declare(strict_types=1);

namespace Test\Ecotone\Amqp\Fixture\DeadLetter;

use Ecotone\Amqp\AmqpBackedMessageChannelBuilder;
use Ecotone\Amqp\Configuration\AmqpConfiguration;
use Ecotone\Dbal\Configuration\DbalConfiguration;
use Ecotone\Dbal\DbalBackedMessageChannelBuilder;
use Ecotone\Dbal\Recoverability\DbalDeadLetterBuilder;
use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\Extension;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\Recoverability\ErrorHandlerConfiguration;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;

/**
 * @ApplicationContext()
 */
class ErrorConfigurationContext
{
    const INPUT_CHANNEL = "correctOrders";
    const ERROR_CHANNEL = "errorChannel";
    const DEAD_LETTER_CHANNEL = "incorrectOrders";


    /**
     * @Extension()
     */
    public function getChannels()
    {
        return [
            AmqpBackedMessageChannelBuilder::create(self::INPUT_CHANNEL)
              ->withReceiveTimeout(1),
            AmqpBackedMessageChannelBuilder::create(self::DEAD_LETTER_CHANNEL)
                ->withReceiveTimeout(1),
        ];
    }

    /**
     * @Extension()
     */
    public function errorConfiguration()
    {
        return ErrorHandlerConfiguration::createWithDeadLetterChannel(
            self::ERROR_CHANNEL,
            RetryTemplateBuilder::exponentialBackoff(1, 1)
                ->maxRetryAttempts(2),
            self::DEAD_LETTER_CHANNEL
        );
    }

    /**
     * @Extension()
     */
    public function pollingConfiguration()
    {
        return [
            PollingMetadata::create(self::INPUT_CHANNEL)
//                longer period of time, as rabbit during republishing message between queues may have a delay
                ->setExecutionTimeLimitInMilliseconds(3000)
                ->setHandledMessageLimit(1)
                ->setErrorChannelName(self::ERROR_CHANNEL),
            PollingMetadata::create("incorrectOrdersEndpoint")
                ->setExecutionTimeLimitInMilliseconds(3000)
                ->setHandledMessageLimit(1)
        ];
    }
}