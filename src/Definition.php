<?php

namespace Pulsar;

use ArrayAccess;

final class Definition implements ArrayAccess
{
    /** @var string[] */
    private array $ids;
    /** @var Property[] */
    private array $properties;

    /**
     * @param Property[] $properties
     */
    public function __construct(array $ids, array $properties)
    {
        $this->ids = $ids;
        $this->properties = $properties;
    }

    public function has(string $name): bool
    {
        return isset($this->properties[$name]);
    }

    public function get(string $name): ?Property
    {
        return $this->properties[$name] ?? null;
    }

    public function all(): array
    {
        return $this->properties;
    }

    public function getIds(): array
    {
        return $this->ids;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->properties[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->properties[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        throw new \RuntimeException('Modifying a model definition is not allowed.');
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new \RuntimeException('Modifying a model definition is not allowed.');
    }
}
