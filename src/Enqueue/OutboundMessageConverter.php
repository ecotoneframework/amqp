<?php


namespace Ecotone\Enqueue;


use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageConverter\HeaderMapper;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\InvalidArgumentException;

class OutboundMessageConverter
{
    /**
     * @var HeaderMapper
     */
    private $headerMapper;
    /**
     * @var ConversionService
     */
    private $conversionService;
    /**
     * @var MediaType
     */
    private $defaultConversionMediaType;

    public function __construct(HeaderMapper $headerMapper, ConversionService $conversionService, MediaType $defaultConversionMediaType)
    {
        $this->headerMapper = $headerMapper;
        $this->conversionService = $conversionService;
        $this->defaultConversionMediaType = $defaultConversionMediaType;
    }

    public function prepare(Message $convertedMessage): OutboundMessage
    {
        $applicationHeaders = $this->headerMapper->mapFromMessageHeaders($convertedMessage->getHeaders()->headers());

        $enqueueMessagePayload = $convertedMessage->getPayload();
        $mediaType = $convertedMessage->getHeaders()->hasContentType() ? $convertedMessage->getHeaders()->getContentType() : null;
        if (!is_string($enqueueMessagePayload)) {
            if (!$convertedMessage->getHeaders()->hasContentType()) {
                throw new InvalidArgumentException("Can't send outside of application. Payload has incorrect type, that can't be converted: " . TypeDescriptor::createFromVariable($enqueueMessagePayload)->toString());
            }

            $sourceType = $convertedMessage->getHeaders()->getContentType()->hasTypeParameter() ? $convertedMessage->getHeaders()->getContentType()->getTypeParameter() : TypeDescriptor::createFromVariable($enqueueMessagePayload);
            $sourceMediaType = $convertedMessage->getHeaders()->getContentType();
            $targetType = TypeDescriptor::createStringType();

            if ($this->conversionService->canConvert(
                $sourceType,
                $sourceMediaType,
                $targetType,
                $this->defaultConversionMediaType
            )) {
                $applicationHeaders[MessageHeaders::TYPE_ID] = TypeDescriptor::createFromVariable($enqueueMessagePayload)->toString();

                $mediaType = $this->defaultConversionMediaType;
                $enqueueMessagePayload = $this->conversionService->convert(
                    $enqueueMessagePayload,
                    $sourceType,
                    $convertedMessage->getHeaders()->getContentType(),
                    $targetType,
                    $mediaType
                );
            } else {
                throw new InvalidArgumentException("Can't send message to amqp channel. Payload has incorrect non-convertable type or converter is missing for: 
                 From {$sourceMediaType}:{$sourceType} to {$this->defaultConversionMediaType}:{$targetType}");
            }
        }

        if ($convertedMessage->getHeaders()->containsKey(MessageHeaders::ROUTING_SLIP)) {
            $applicationHeaders[MessageHeaders::ROUTING_SLIP] = $convertedMessage->getHeaders()->get(MessageHeaders::ROUTING_SLIP);
        }

        return new OutboundMessage($enqueueMessagePayload, $applicationHeaders, $mediaType ? $mediaType->toString() : null);
    }
}