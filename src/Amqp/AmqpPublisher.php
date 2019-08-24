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
    /**
     * @param string $data
     * @param string $sourceMediaType
     * @param string $exchange
     * @param string $routing
     */
    public function send(string $data, string $sourceMediaType = MediaType::TEXT_PLAIN, string $exchange = "", string $routing = "") : void;

    /**
     * @param string $data
     * @param array $metadata
     * @param string $sourceMediaType
     * @param string $exchange
     * @param string $routing
     */
    public function sendWithMetadata(string $data, string $sourceMediaType = MediaType::TEXT_PLAIN, array $metadata = [], string $exchange = "", string $routing = "") : void;

    /**
     * @param $data
     * @param string $exchange
     * @param string $routing
     * @return mixed
     */
    public function convertAndSend($data, string $exchange = "", string $routing = "") : void;

    /**
     * @param $data
     * @param array $metadata
     * @param string $exchange
     * @param string $routing
     * @return mixed
     */
    public function convertAndSendWithMetadata($data, array $metadata = [], string $exchange = "", string $routing = "") : void;
}