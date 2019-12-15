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
     * @var string
     */
    private $headerMapper = "";
    /**
     * @var string
     */
    private $defaultRoutingKey = "";
    /**
     * @var bool
     */
    private $defaultPersistentDelivery = true;

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
     * @param string $exchangeName
     * @param string|null $outputDefaultConversionMediaType
     * @param string $amqpConnectionReference
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

    public function withDefaultRoutingKey(string $defaultRoutingKey) : RegisterAmqpPublisher
    {
        $this->defaultRoutingKey = $defaultRoutingKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultRoutingKey(): string
    {
        return $this->defaultRoutingKey;
    }

    /**
     * @param string $headerMapper comma separated list of headers to be mapped.
     *                             (e.g. "\*" or "thing1*, thing2" or "*thing1")
     *
     * @return RegisterAmqpPublisher
     */
    public function withHeaderMapper(string $headerMapper) : RegisterAmqpPublisher
    {
        $this->headerMapper = $headerMapper;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDefaultPersistentDelivery(): bool
    {
        return $this->defaultPersistentDelivery;
    }

    /**
     * @param bool $defaultPersistentDelivery
     * @return RegisterAmqpPublisher
     */
    public function withDefaultPersistentDelivery(bool $defaultPersistentDelivery): RegisterAmqpPublisher
    {
        $this->defaultPersistentDelivery = $defaultPersistentDelivery;
        return $this;
    }

    public function getDefaultPersistentDelivery() : bool
    {
        return $this->defaultPersistentDelivery;
    }

    /**
     * @return bool
     */
    public function isAutoDeclareQueueOnSend(): bool
    {
        return $this->autoDeclareQueueOnSend;
    }

    /**
     * @return string
     */
    public function getHeaderMapper(): string
    {
        return $this->headerMapper;
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