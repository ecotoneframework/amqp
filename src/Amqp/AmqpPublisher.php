<?php


namespace Ecotone\Amqp;

use Ecotone\Messaging\Conversion\MediaType;

/**
 * Interface AmqpPublisher
 * @package Ecotone\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AmqpPublisher
{
//    @TODO add @AmqpConsumer for inbound channel adapter
//    remove exchange from AmqpPublisher API and add it to RegisterAmqpPublisher

    /**
     * @param string $data
     * @param string $sourceMediaType
     * @param string $routing
     */
    public function send(string $data, string $sourceMediaType = MediaType::TEXT_PLAIN, string $routing = "") : void;

    /**
     * @param string $data
     * @param string[] $metadata
     * @param string $sourceMediaType
     * @param string $routing
     */
    public function sendWithMetadata(string $data, string $sourceMediaType = MediaType::TEXT_PLAIN, array $metadata = [], string $routing = "") : void;

    /**
     * @param $data
     * @param string $routing
     * @return void
     */
    public function convertAndSend(object $data, string $routing = "") : void;

    /**
     * @param $data
     * @param string[] $metadata
     * @param string $routing
     * @return void
     */
    public function convertAndSendWithMetadata(object $data, array $metadata = [], string $routing = "") : void;
}