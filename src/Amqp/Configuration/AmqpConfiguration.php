<?php


namespace Ecotone\Amqp\Configuration;


use Interop\Amqp\AmqpConnectionFactory;

class AmqpConfiguration
{
    const DEFAULT_TRANSACTION_ON_POLLABLE_ENDPOINTS = false;
    const DEFAULT_TRANSACTION_ON_COMMAND_BUS = false;

    private $defaultTransactionOnPollableEndpoints = self::DEFAULT_TRANSACTION_ON_POLLABLE_ENDPOINTS;

    private $defaultTransactionOnCommandBus = self::DEFAULT_TRANSACTION_ON_COMMAND_BUS;

    /**
     * @var array
     */
    private $defaultConnectionReferenceNames = [];

    private function __construct()
    {
    }

    public static function createWithDefaults() : self
    {
        return new self();
    }

    public function withTransactionOnAsynchronousEndpoints(bool $isTransactionEnabled) : self
    {
        $self = clone $this;
        $self->defaultTransactionOnPollableEndpoints = $isTransactionEnabled;

        return $self;
    }

    public function withTransactionOnCommandBus(bool $isTransactionEnabled) : self
    {
        $self = clone $this;
        $self->defaultTransactionOnCommandBus = $isTransactionEnabled;

        return $self;
    }

    public function withDefaultConnectionReferenceNames(array $connectionReferenceNames = [AmqpConnectionFactory::class]) : self
    {
        $self = clone $this;
        $self->defaultConnectionReferenceNames = $connectionReferenceNames;

        return $self;
    }

    /**
     * @return bool
     */
    public function isDefaultTransactionOnAsynchronousEndpoints(): bool
    {
        return $this->defaultTransactionOnPollableEndpoints;
    }

    /**
     * @return bool
     */
    public function isDefaultTransactionOnCommandBus(): bool
    {
        return $this->defaultTransactionOnCommandBus;
    }

    /**
     * @return array
     */
    public function getDefaultConnectionReferenceNames(): array
    {
        return $this->defaultConnectionReferenceNames;
    }
}