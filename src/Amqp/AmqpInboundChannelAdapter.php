<?php
declare(strict_types=1);

namespace Ecotone\Amqp;

use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Enqueue\EnqueueAcknowledgementCallback;
use Ecotone\Enqueue\InboundMessageConverter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Endpoint\EntrypointGateway;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageConverter\HeaderMapper;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Scheduling\TaskExecutor;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Interop\Amqp\AmqpMessage;
use Interop\Queue\Consumer as EnqueueConsumer;
use Throwable;

/**
 * Class InboundEnqueueGateway
 * @package Ecotone\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpInboundChannelAdapter implements TaskExecutor
{
    /**
     * @var CachedConnectionFactory
     */
    private $connectionFactory;
    /**
     * @var EntrypointGateway
     */
    private $inboundAmqpGateway;
    /**
     * @var bool
     */
    private $declareOnStartup;
    /**
     * @var AmqpAdmin
     */
    private $amqpAdmin;
    /**
     * @var string
     */
    private $amqpQueueName;
    /**
     * @var int
     */
    private $receiveTimeoutInMilliseconds;
    /**
     * @var bool
     */
    private $queueNameWithEndpointId;
    /**
     * @var bool
     */
    private $initialized = false;
    /**
     * @var InboundMessageConverter
     */
    private $inboundMessageConverter;

    public function __construct(
        CachedConnectionFactory $cachedConnectionFactory,
        EntrypointGateway $inboundAmqpGateway,
        AmqpAdmin $amqpAdmin,
        bool $declareOnStartup,
        string $amqpQueueName,
        int $receiveTimeoutInMilliseconds,
        InboundMessageConverter $inboundMessageConverter,
        bool $queueNameWithEndpointId
    )
    {
        $this->connectionFactory = $cachedConnectionFactory;
        $this->inboundAmqpGateway = $inboundAmqpGateway;
        $this->declareOnStartup = $declareOnStartup;
        $this->amqpAdmin = $amqpAdmin;
        $this->amqpQueueName = $amqpQueueName;
        $this->receiveTimeoutInMilliseconds = $receiveTimeoutInMilliseconds;
        $this->queueNameWithEndpointId = $queueNameWithEndpointId;
        $this->inboundMessageConverter = $inboundMessageConverter;
    }

    /**
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function execute(): void
    {
        $message = $this->getMessage(null);

        if (!$message) {
            return;
        }

        Assert::isSubclassOf($message, Message::class, "Passed object to amqp inbound channel adapter is not a Message");
        $this->inboundAmqpGateway->executeEntrypoint($message);
    }

    /**
     * @param string|null $endpointId
     * @return Message|null
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function getMessage(?string $endpointId): ?Message
    {
        if (!$this->initialized || !$this->queueNameWithEndpointId) {
            $this->amqpAdmin->declareQueueWithBindings($this->getQueueName($endpointId), $this->connectionFactory->createContext());
            $this->initialized = true;
        }

        $consumer = $this->connectionFactory->getConsumer(new \Interop\Amqp\Impl\AmqpQueue($this->getQueueName($endpointId)), $endpointId);

        /** @var AmqpMessage $amqpMessage */
        $amqpMessage = $consumer->receive($this->receiveTimeoutInMilliseconds);

        if (!$amqpMessage) {
            return null;
        }

        $messageBuilder = $this->inboundMessageConverter->toMessage($amqpMessage, $consumer);
        if ($amqpMessage->getContentType()) {
            $messageBuilder = $messageBuilder->setContentType(MediaType::parseMediaType($amqpMessage->getContentType()));
        }

        return $messageBuilder->build();
    }

    /**
     * @param string|null $endpointId
     * @return string
     */
    private function getQueueName(?string $endpointId): string
    {
        return $this->queueNameWithEndpointId ? $this->amqpQueueName . "." . $endpointId : $this->amqpQueueName;
    }
}