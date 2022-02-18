<?php

namespace Pulsar;

use ArrayAccess;

class Error implements ArrayAccess
{
    /** @var string */
    private $error;

    /** @var string */
    private $message;

    /** @var array */
    private $context;

    public function __construct(string $error, array $context, string $message)
    {
        $this->error = $error;
        $this->context = $context;
        $this->message = $message;
    }

    public function __toString()
    {
        return $this->message;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function offsetExists($offset): bool
    {
        return property_exists($this, $offset);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    public function offsetSet($offset, $value): void
    {
        throw new \InvalidArgumentException('Modifying a validation error is not supported');
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new \InvalidArgumentException('Modifying a validation error is not supported');
    }
}
