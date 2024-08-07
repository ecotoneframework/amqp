<?php

declare(strict_types=1);

namespace Test\Ecotone\Amqp\Fixture\Handler;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\ServiceActivator;
use Ecotone\Messaging\Message;

/**
 * licence Apache-2.0
 */
final class SuccessServiceActivator
{
    #[Asynchronous('async_channel')]
    #[ServiceActivator('handle_channel')]
    public function handle(Message $message): void
    {
    }

    public function __toString()
    {
        return self::class;
    }
}
