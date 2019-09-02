<?php
declare(strict_types=1);

namespace Ecotone\Amqp;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Endpoint\EntrypointGateway;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageConverter\HeaderMapper;
use Ecotone\Messaging\Scheduling\TaskExecutor;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Enqueue\AmqpLib\AmqpConnectionFactory;
use Enqueue\AmqpLib\AmqpConsumer;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpMessage;
use Interop\Queue\Consumer as EnqueueConsumer;
use Throwable;

/**
 * Class InboundEnqueueGateway
 * @package Ecotone\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpInboundChannelAdapter implements TaskExecutor, EntrypointGateway
{
    /**
     * @var AmqpConnectionFactory
     */
    private $amqpConnectionFactory;
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
     * @var string
     */
    private $acknowledgeMode;
    /**
     * @var HeaderMapper
     */
    private $headerMapper;
    /**
     * @var bool
     */
    private $queueNameWithEndpointId;
    /**
     * @var bool
     */
    private $initialized = false;
    /**
     * @var AmqpConsumer[]
     */
    private $initializedConsumer = [];

    /**
     * InboundAmqpEnqueueGateway constructor.
     *
     * @param AmqpConnectionFactory $amqpConnectionFactory
     * @param EntrypointGateway $inboundAmqpGateway
     * @param AmqpAdmin $amqpAdmin
     * @param bool $declareOnStartup
     * @param string $amqpQueueName
     * @param int $receiveTimeoutInMilliseconds
     * @param string $acknowledgeMode
     * @param HeaderMapper $headerMapper
     * @param bool $queueNameWithEndpointId
     */
    public function __construct(
        AmqpConnectionFactory $amqpConnectionFactory,
        EntrypointGateway $inboundAmqpGateway,
        AmqpAdmin $amqpAdmin,
        bool $declareOnStartup,
        string $amqpQueueName,
        int $receiveTimeoutInMilliseconds,
        string $acknowledgeMode,
        HeaderMapper $headerMapper,
        bool $queueNameWithEndpointId
    )
    {
        $this->amqpConnectionFactory = $amqpConnectionFactory;
        $this->inboundAmqpGateway = $inboundAmqpGateway;
        $this->declareOnStartup = $declareOnStartup;
        $this->amqpAdmin = $amqpAdmin;
        $this->amqpQueueName = $amqpQueueName;
        $this->receiveTimeoutInMilliseconds = $receiveTimeoutInMilliseconds;
        $this->acknowledgeMode = $acknowledgeMode;
        $this->headerMapper = $headerMapper;
        $this->queueNameWithEndpointId = $queueNameWithEndpointId;
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

        $this->executeEntrypoint($message);
    }

    /**
     * @param string|null $endpointId
     * @return Message|null
     * @throws InvalidArgumentException
     */
    public function getMessage(?string $endpointId): ?Message
    {
        if (!$this->initialized || !$this->queueNameWithEndpointId) {
            $context = $this->amqpConnectionFactory->createContext();
            $this->amqpAdmin->declareQueueWithBindings($this->getQueueName($endpointId), $context);
            $this->initialized = true;
        }

        $consumer = $this->getConsumer($endpointId);

        $amqpMessage = $consumer->receive($this->receiveTimeoutInMilliseconds);

        if (!$amqpMessage) {
            return null;
        }

        return $this->toMessage($amqpMessage, $consumer);
    }

    /**
     * @param string|null $endpointId
     * @return string
     */
    private function getQueueName(?string $endpointId): string
    {
        return $this->queueNameWithEndpointId ? $this->amqpQueueName . "." . $endpointId : $this->amqpQueueName;
    }

    /**
     * @param string|null $endpointId
     * @return \Interop\Amqp\AmqpConsumer
     */
    private function getConsumer(?string $endpointId): \Interop\Amqp\AmqpConsumer
    {
        $initializedConsumerId = $endpointId ?? 0;
        if (isset($this->initializedConsumer[$initializedConsumerId])) {
            return $this->initializedConsumer[$initializedConsumerId];
        }

        /** @var AmqpContext $context */
        $context = $this->amqpConnectionFactory->createContext();

        $consumer = $context->createConsumer(new \Interop\Amqp\Impl\AmqpQueue($this->getQueueName($endpointId)));
        $this->initializedConsumer[$initializedConsumerId] = $consumer;

        return $consumer;
    }

    /**
     * @inheritDoc
     */
    private function toMessage($source, EnqueueConsumer $consumer): Message
    {
        if (!($source instanceof AmqpMessage)) {
            return null;
        }

        $messageBuilder = MessageBuilder::withPayload($source->getBody())
            ->setMultipleHeaders($this->headerMapper->mapToMessageHeaders($source->getProperties()));

        if (in_array($this->acknowledgeMode, [AmqpAcknowledgementCallback::AUTO_ACK, AmqpAcknowledgementCallback::MANUAL_ACK])) {
            if ($this->acknowledgeMode == AmqpAcknowledgementCallback::AUTO_ACK) {
                $amqpAcknowledgeCallback = AmqpAcknowledgementCallback::createWithAutoAck($consumer, $source);
            } else {
                $amqpAcknowledgeCallback = AmqpAcknowledgementCallback::createWithManualAck($consumer, $source);
            }

            $messageBuilder = $messageBuilder
                ->setHeader(AmqpHeader::HEADER_ACKNOWLEDGE, $amqpAcknowledgeCallback);
        }

        if ($source->getContentType()) {
            $messageBuilder = $messageBuilder->setContentType(MediaType::parseMediaType($source->getContentType()));
        }

        return $messageBuilder->build();
    }

    /**
     * @inheritDoc
     */
    public function executeEntrypoint($message)
    {
        Assert::isSubclassOf($message, Message::class, "Passed object to amqp inbound channel adapter is not a Message");

        $this->inboundAmqpGateway->executeEntrypoint($message);
    }
}