<?php


namespace Ecotone\Enqueue;


class OutboundMessage
{
    /** @var mixed */
    private $payload;
    /** @var string[] */
    private $headers;
    /** @var string|null */
    private $contentType;

    /**
     * OutboundMessage constructor.
     * @param mixed $payload
     * @param string[] $headers
     * @param string|null $contentType
     */
    public function __construct($payload, array $headers, ?string $contentType)
    {
        $this->payload = $payload;
        $this->headers = $headers;
        $this->contentType = $contentType;
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @return string[]
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return string|null
     */
    public function getContentType(): ?string
    {
        return $this->contentType;
    }
}