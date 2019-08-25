<?php


namespace Ecotone\Amqp\Configuration;

use Ecotone\Amqp\AmqpPublisher;
use Exception;

/**
 * Class RegisterAmqpPublisher
 * @package Ecotone\Amqp\Configuration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RegisterAmqpPublisher
{
    /**
     * @var string
     */
    private $amqpConnectionReference;
    /**
     * @var string
     */
    private $outputDefaultConversionMediaType;
    /**
     * @var string
     */
    private $referenceName;
    /**
     * @var string
     */
    private $exchangeName;

    /**
     * RegisterAmqpPublisher constructor.
     * @param string $amqpConnectionReference
     * @param string $exchangeName
     * @param string $outputDefaultConversionMediaType
     * @param string $referenceName
     */
    private function __construct(string $amqpConnectionReference, string $exchangeName, string $outputDefaultConversionMediaType, string $referenceName)
    {
        $this->amqpConnectionReference = $amqpConnectionReference;
        $this->outputDefaultConversionMediaType = $outputDefaultConversionMediaType;
        $this->referenceName = $referenceName;
        $this->exchangeName = $exchangeName;
    }

    /**
     * @param string $amqpConnectionReference
     * @param string $exchangeName
     * @param string $outputDefaultConversionMediaType
     * @return RegisterAmqpPublisher
     */
    public static function create(string $amqpConnectionReference, string $exchangeName, string $outputDefaultConversionMediaType): self
    {
        return new self($amqpConnectionReference, $exchangeName, $outputDefaultConversionMediaType, AmqpPublisher::class);
    }

    /**
     * @param string $amqpConnectionReference
     * @param string $exchangeName
     * @param string $outputDefaultConversionMediaType
     * @param string $publisherReferenceName
     * @return RegisterAmqpPublisher
     */
    public static function createWithReferenceName(string $amqpConnectionReference, string $exchangeName, string $outputDefaultConversionMediaType, string $publisherReferenceName): self
    {
        return new self($amqpConnectionReference, $exchangeName, $outputDefaultConversionMediaType, $publisherReferenceName);
    }

    /**
     * @return string
     */
    public function getAmqpConnectionReference(): string
    {
        return $this->amqpConnectionReference;
    }

    /**
     * @return string
     */
    public function getOutputDefaultConversionMediaType(): string
    {
        return $this->outputDefaultConversionMediaType;
    }

    /**
     * @return string
     */
    public function getReferenceName(): string
    {
        return $this->referenceName;
    }

    /**
     * @return string
     */
    public function getExchangeName(): string
    {
        return $this->exchangeName;
    }
}