<?php


namespace Ecotone\Amqp\Configuration;

use Ecotone\Amqp\AmqpPublisher;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Conversion\MediaType;
use Enqueue\AmqpLib\AmqpConnectionFactory;
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
     * @var string|null
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
     * @var bool
     */
    private $autoDeclareQueueOnSend = false;

    /**
     * RegisterAmqpPublisher constructor.
     * @param string $amqpConnectionReference
     * @param string $exchangeName
     * @param string|null $outputDefaultConversionMediaType
     * @param string $referenceName
     */
    private function __construct(string $amqpConnectionReference, string $exchangeName, ?string $outputDefaultConversionMediaType, string $referenceName)
    {
        $this->amqpConnectionReference = $amqpConnectionReference;
        $this->outputDefaultConversionMediaType = $outputDefaultConversionMediaType;
        $this->referenceName = $referenceName;
        $this->exchangeName = $exchangeName;
    }

    /**
     * @param string $publisherReferenceName
     * @param string $amqpConnectionReference
     * @param string $exchangeName
     * @param string|null $outputDefaultConversionMediaType
     * @return RegisterAmqpPublisher
     */
    public static function create(string $publisherReferenceName, string $exchangeName = "", ?string $outputDefaultConversionMediaType = null, string $amqpConnectionReference = AmqpConnectionFactory::class): self
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
     * @param bool $autoDeclareQueueOnSend
     * @return RegisterAmqpPublisher
     */
    public function withAutoDeclareQueueOnSend(bool $autoDeclareQueueOnSend): RegisterAmqpPublisher
    {
        $this->autoDeclareQueueOnSend = $autoDeclareQueueOnSend;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAutoDeclareQueueOnSend(): bool
    {
        return $this->autoDeclareQueueOnSend;
    }

    /**
     * @return string|null
     */
    public function getOutputDefaultConversionMediaType(): ?string
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