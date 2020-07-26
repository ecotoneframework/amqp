<?php

namespace Test\Ecotone\Modelling\Fixture\OrderAggregate;

use Ecotone\Messaging\Annotation\ServiceActivator;
use Ecotone\Messaging\MessagingException;

class OrderErrorHandler
{
    /**
     * @ServiceActivator(inputChannelName=ChannelConfiguration::ERROR_CHANNEL)
     */
    public function errorConfiguration(MessagingException $exception)
    {
        throw $exception;
    }
}