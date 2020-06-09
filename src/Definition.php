<?php

namespace Pulsar;

use ArrayAccess;

final class Definition implements ArrayAccess
{
    /** @var Property[] */
    private $properties;

    /**
     * @param Property[] $properties
     */
    public function __construct(array $properties)
    {
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

    public function propertyNames(): array
    {
        return array_keys($this->properties);
    }

    public function offsetExists($offset)
    {
        return isset($this->properties[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->properties[$offset] ?? null;
    }

    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException('Modifying a model definition is not allowed.');
    }

    public function offsetUnset($offset)
    {
        throw new \RuntimeException('Modifying a model definition is not allowed.');
    }
}
